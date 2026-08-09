<?php
/**
 * public/siparis_servis_makbuz_olustur.php
 * Seçili sipariş ve servisleri tek bir makbuza dönüştürür.
 * Stok düşürme isteğe bağlıdır (yorum satırı olarak duruyor), cari borçlanır.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/numara_manager.php';
require_login();
require_csrf(BASE_URL . '/cari_detay.php');

$siparis_ids = array_filter(array_map('intval', explode(',', $_POST['siparis_ids'] ?? '')));
$servis_ids = array_filter(array_map('intval', explode(',', $_POST['servis_ids'] ?? '')));

if (empty($siparis_ids) && empty($servis_ids)) {
    flash_set('Hiç sipariş veya servis seçilmedi.', 'danger');
    header('Location: ' . BASE_URL . '/siparisler.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // ===== 1. SEÇİLİ SİPARİŞLERİ KONTROL ET =====
    $siparisler = [];
    $cari_id = null;
    if (!empty($siparis_ids)) {
        $placeholders = implode(',', array_fill(0, count($siparis_ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM siparisler WHERE id IN ($placeholders) AND durum = 'BEKLEMEDE'");
        $stmt->execute($siparis_ids);
        $siparisler = $stmt->fetchAll();
        if (count($siparisler) !== count($siparis_ids)) {
            throw new Exception('Bazı siparişler zaten faturalanmış veya iptal edilmiş.');
        }
        foreach ($siparisler as $s) {
            if ($cari_id === null) $cari_id = (int)$s['cari_id'];
            elseif ((int)$s['cari_id'] !== $cari_id) {
                throw new Exception('Farklı carilere ait siparişler tek makbuzda birleştirilemez.');
            }
        }
    }

    // ===== 2. SEÇİLİ SERVİSLERİ KONTROL ET =====
    $servisler = [];
    if (!empty($servis_ids)) {
        $placeholders = implode(',', array_fill(0, count($servis_ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM teknik_servis WHERE id IN ($placeholders) AND durum IN ('BEKLEMEDE', 'İŞLEMDE')");
        $stmt->execute($servis_ids);
        $servisler = $stmt->fetchAll();
        if (count($servisler) !== count($servis_ids)) {
            throw new Exception('Bazı servisler zaten faturalanmış veya tamamlanmış.');
        }
        foreach ($servisler as $s) {
            $servis_cari = (int)$s['cari_id'];
            if ($cari_id === null) $cari_id = $servis_cari;
            elseif ($servis_cari !== $cari_id) {
                throw new Exception('Farklı carilere ait servisler tek makbuzda birleştirilemez.');
            }
        }
    }

    if ($cari_id === null) {
        throw new Exception('Geçerli bir cari bulunamadı.');
    }

    // ===== 3. MAKBUZ OLUŞTUR =====
    $makbuz_no = generate_makbuz_no_nm($pdo, 'SATIS');
    $makbuz_tarihi = date('Y-m-d H:i:s');

    $insertMakbuz = $pdo->prepare(
        'INSERT INTO makbuzlar
            (makbuz_no, makbuz_tarihi, cari_id, makbuz_turu, durum, para_birimi, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'), datetime(\'now\',\'localtime\'))'
    );
    $insertMakbuz->execute([$makbuz_no, $makbuz_tarihi, $cari_id, 'SATIS', 'OLUŞTURULDU', 'TRY']);
    $makbuz_id = (int)$pdo->lastInsertId();

    // ===== 4. SİPARİŞ DETAYLARINI EKLE =====
    $insertDetay = $pdo->prepare('
        INSERT INTO makbuz_detaylari
            (makbuz_id, urun_id, urun_adi, urun_kodu, barkod, birim, miktar, birim_fiyati,
             iskonto, iskonto_tutari, ara_toplam, toplam_tutar, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))
    ');

    $ara_toplam = 0;
    $toplam_iskonto = 0;

    // Sipariş detaylarını ekle
    foreach ($siparisler as $siparis) {
        $stmt = $pdo->prepare('SELECT * FROM siparis_detaylari WHERE siparis_id = ?');
        $stmt->execute([$siparis['id']]);
        $detaylar = $stmt->fetchAll();
        foreach ($detaylar as $d) {
            $miktar = (float)$d['miktar'];
            $fiyat = (float)$d['birim_fiyati'];
            $iskonto_orani = (float)$d['iskonto'];

            $satir_toplam = $miktar * $fiyat;
            $iskonto_tutari = $satir_toplam * ($iskonto_orani / 100);
            $toplam_tutar = $satir_toplam - $iskonto_tutari;

            $ara_toplam += $satir_toplam;
            $toplam_iskonto += $iskonto_tutari;

            $insertDetay->execute([
                $makbuz_id,
                $d['urun_id'],
                $d['urun_adi'],
                $d['urun_kodu'] ?? '',
                $d['barkod'] ?? '',
                $d['birim'] ?? 'ADET',
                $miktar,
                $fiyat,
                $iskonto_orani,
                $iskonto_tutari,
                $satir_toplam,
                $toplam_tutar,
            ]);

            // Stok düş (MAKBUZDA DA STOK DÜŞSÜN İSTİYORSAN AKTİFLEŞTİR)
            /*
            $stmt = $pdo->prepare('SELECT stok_miktari FROM urunler WHERE id = ?');
            $stmt->execute([$d['urun_id']]);
            $stok_oncesi = (float)$stmt->fetchColumn();
            $yeni_stok = $stok_oncesi - $miktar;
            $pdo->prepare('UPDATE urunler SET stok_miktari = ? WHERE id = ?')->execute([$yeni_stok, $d['urun_id']]);
            stok_hareketi_ekle($pdo, (int)$d['urun_id'], 'SATIŞ', -$miktar, $stok_oncesi, $yeni_stok, $makbuz_no, "Makbuz - $makbuz_no", $cari_id);
            */
        }
    }

    // ===== 5. SERVİS DETAYLARINI EKLE (Tek kalem olarak) =====
    foreach ($servisler as $servis) {
        $servis_adi = "Teknik Servis - " . $servis['servis_no'] . " - " . $servis['urun_adi'];
        $miktar = 1;
        $fiyat = (float)$servis['toplam_ucret'];
        $iskonto_orani = 0;

        $satir_toplam = $miktar * $fiyat;
        $toplam_tutar = $satir_toplam;
        $ara_toplam += $satir_toplam;

        $insertDetay->execute([
            $makbuz_id,
            null,
            $servis_adi,
            '',
            '',
            'ADET',
            $miktar,
            $fiyat,
            $iskonto_orani,
            0,
            $satir_toplam,
            $toplam_tutar,
        ]);
    }

    $genel_toplam = $ara_toplam - $toplam_iskonto;
    $pdo->prepare(
        'UPDATE makbuzlar SET ara_toplam=?, iskonto_tutari=?, genel_toplam=? WHERE id=?'
    )->execute([$ara_toplam, $toplam_iskonto, $genel_toplam, $makbuz_id]);

    // ===== 6. CARİ BAKİYEYİ GÜNCELLE (DOĞRU İŞARET: MÜŞTERİ BORÇLANIR) =====
    $stmt = $pdo->prepare('SELECT bakiye FROM cariler WHERE id = ?');
    $stmt->execute([$cari_id]);
    $eski_bakiye = (float)$stmt->fetchColumn();
    $yeni_bakiye = $eski_bakiye - $genel_toplam; // MÜŞTERİ BORÇLANIR -> NEGATİF
    $pdo->prepare('UPDATE cariler SET bakiye = ? WHERE id = ?')->execute([$yeni_bakiye, $cari_id]);

    // ===== 7. HESAP HAREKETİ EKLE =====
    $insertHareket = $pdo->prepare(
        'INSERT INTO hesap_hareketleri
            (cari_id, hareket_turu, islem_turu, tutar, aciklama, tarih, referans_no, cari_bakiye_oncesi, cari_bakiye_sonrasi, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
    );
    $insertHareket->execute([
        $cari_id,
        'MAKBUZ_SATIS',
        'BORÇ',
        $genel_toplam,
        "Toplu Makbuz No: $makbuz_no",
        date('Y-m-d H:i:s'),
        $makbuz_no,
        $eski_bakiye,
        $yeni_bakiye,
    ]);

    // ===== 8. SİPARİŞ VE SERVİS DURUMLARINI GÜNCELLE =====
    if (!empty($siparis_ids)) {
        $placeholders = implode(',', array_fill(0, count($siparis_ids), '?'));
        $pdo->prepare("UPDATE siparisler SET durum = 'MAKBUZLANDI', updated_at = datetime('now','localtime') WHERE id IN ($placeholders)")
            ->execute($siparis_ids);
    }
    if (!empty($servis_ids)) {
        $placeholders = implode(',', array_fill(0, count($servis_ids), '?'));
        $pdo->prepare("UPDATE teknik_servis SET durum = 'MAKBUZLANDI', updated_at = datetime('now','localtime') WHERE id IN ($placeholders)")
            ->execute($servis_ids);
    }

    $pdo->commit();

    flash_set("Seçili sipariş ve servisler başarıyla makbuza dönüştürüldü. Makbuz No: $makbuz_no", 'success');
    header('Location: ' . BASE_URL . '/makbuz_detay.php?id=' . $makbuz_id);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash_set('Hata: ' . $e->getMessage(), 'danger');
    header('Location: ' . BASE_URL . '/cari_detay.php?id=' . ($cari_id ?? 0));
    exit;
}
?>