<?php
/**
 * public/siparis_servis_faturalandir.php
 * Seçili sipariş ve servisleri tek bir faturada birleştirir.
 * Stok düşer, cari borçlanır, sipariş ve servis durumları güncellenir.
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
                throw new Exception('Farklı carilere ait siparişler tek faturada birleştirilemez.');
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
                throw new Exception('Farklı carilere ait servisler tek faturada birleştirilemez.');
            }
        }
    }

    if ($cari_id === null) {
        throw new Exception('Geçerli bir cari bulunamadı.');
    }

    // ===== 3. FATURA OLUŞTUR =====
    $fatura_no = generate_fatura_no_nm($pdo);
    $fatura_tarihi = date('Y-m-d H:i:s');
    $para_birimi = 'TRY';
    $odeme_turu = 'VERESİYE';

    $insertFatura = $pdo->prepare(
        'INSERT INTO faturalar
            (fatura_no, fatura_tarihi, cari_id, fatura_turu, durum, odeme_turu, para_birimi, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'), datetime(\'now\',\'localtime\'))'
    );
    $insertFatura->execute([$fatura_no, $fatura_tarihi, $cari_id, 'SATIS', 'OLUŞTURULDU', $odeme_turu, $para_birimi]);
    $fatura_id = (int)$pdo->lastInsertId();

    // ===== 4. SİPARİŞ DETAYLARINI EKLE =====
    $insertDetay = $pdo->prepare('
        INSERT INTO fatura_detaylari
            (fatura_id, urun_id, urun_adi, urun_kodu, barkod, birim, miktar, birim_fiyati,
             iskonto, iskonto_tutari, vergi_orani, vergi_tutari, ara_toplam, toplam_tutar, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))
    ');

    $ara_toplam = 0;
    $toplam_iskonto = 0;
    $toplam_vergi = 0;

    // Sipariş detaylarını ekle
    foreach ($siparisler as $siparis) {
        $stmt = $pdo->prepare('SELECT * FROM siparis_detaylari WHERE siparis_id = ?');
        $stmt->execute([$siparis['id']]);
        $detaylar = $stmt->fetchAll();
        foreach ($detaylar as $d) {
            $miktar = (float)$d['miktar'];
            $fiyat = (float)$d['birim_fiyati'];
            $iskonto_orani = (float)$d['iskonto'];
            $kdv_orani = (float)($d['vergi_orani'] ?? 18);

            $satir_toplam = $miktar * $fiyat;
            $iskonto_tutari = $satir_toplam * ($iskonto_orani / 100);
            $matrah = $satir_toplam - $iskonto_tutari;
            $kdv_tutari = $matrah * ($kdv_orani / 100);
            $toplam_tutar = $matrah + $kdv_tutari;

            $ara_toplam += $satir_toplam;
            $toplam_iskonto += $iskonto_tutari;
            $toplam_vergi += $kdv_tutari;

            $insertDetay->execute([
                $fatura_id,
                $d['urun_id'],
                $d['urun_adi'],
                $d['urun_kodu'] ?? '',
                $d['barkod'] ?? '',
                $d['birim'] ?? 'ADET',
                $miktar,
                $fiyat,
                $iskonto_orani,
                $iskonto_tutari,
                $kdv_orani,
                $kdv_tutari,
                $satir_toplam,
                $toplam_tutar,
            ]);

            // Stok düş
            $stmt = $pdo->prepare('SELECT stok_miktari FROM urunler WHERE id = ?');
            $stmt->execute([$d['urun_id']]);
            $stok_oncesi = (float)$stmt->fetchColumn();
            $yeni_stok = $stok_oncesi - $miktar;
            $pdo->prepare('UPDATE urunler SET stok_miktari = ? WHERE id = ?')->execute([$yeni_stok, $d['urun_id']]);
            stok_hareketi_ekle($pdo, (int)$d['urun_id'], 'SATIŞ', -$miktar, $stok_oncesi, $yeni_stok, $fatura_no, "Sipariş Fatura - $fatura_no", $cari_id);
        }
    }

    // ===== 5. SERVİS DETAYLARINI EKLE (Tek kalem olarak) =====
    foreach ($servisler as $servis) {
        $servis_adi = "Teknik Servis - " . $servis['servis_no'] . " - " . $servis['urun_adi'];
        $miktar = 1;
        $fiyat = (float)$servis['toplam_ucret'];
        $iskonto_orani = 0;
        $kdv_orani = 18; // varsayılan

        $satir_toplam = $miktar * $fiyat;
        $iskonto_tutari = 0;
        $kdv_tutari = $satir_toplam * ($kdv_orani / 100);
        $toplam_tutar = $satir_toplam + $kdv_tutari;

        $ara_toplam += $satir_toplam;
        $toplam_vergi += $kdv_tutari;

        $insertDetay->execute([
            $fatura_id,
            null, // urun_id yok
            $servis_adi,
            '',
            '',
            'ADET',
            $miktar,
            $fiyat,
            $iskonto_orani,
            $iskonto_tutari,
            $kdv_orani,
            $kdv_tutari,
            $satir_toplam,
            $toplam_tutar,
        ]);

        // Servis için stok düşmüyoruz (hizmet olduğu için)
    }

    // ===== 6. FATURA TOPLAMLARINI GÜNCELLE =====
    $genel_toplam = $ara_toplam - $toplam_iskonto + $toplam_vergi;
    $pdo->prepare(
        'UPDATE faturalar SET ara_toplam=?, iskonto_tutari=?, vergi_tutari=?, genel_toplam=? WHERE id=?'
    )->execute([$ara_toplam, $toplam_iskonto, $toplam_vergi, $genel_toplam, $fatura_id]);

    // ===== 7. CARİ BAKİYEYİ GÜNCELLE (DOĞRU İŞARET: MÜŞTERİ BORÇLANIR) =====
    $stmt = $pdo->prepare('SELECT bakiye FROM cariler WHERE id = ?');
    $stmt->execute([$cari_id]);
    $eski_bakiye = (float)$stmt->fetchColumn();
    $yeni_bakiye = $eski_bakiye - $genel_toplam; // MÜŞTERİ BORÇLANIR -> NEGATİF
    $pdo->prepare('UPDATE cariler SET bakiye = ? WHERE id = ?')->execute([$yeni_bakiye, $cari_id]);

    // ===== 8. HESAP HAREKETİ EKLE =====
    $insertHareket = $pdo->prepare(
        'INSERT INTO hesap_hareketleri
            (cari_id, hareket_turu, islem_turu, tutar, aciklama, tarih, referans_no, cari_bakiye_oncesi, cari_bakiye_sonrasi, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
    );
    $insertHareket->execute([
        $cari_id,
        'FATURA',
        'BORÇ',
        $genel_toplam,
        "Fatura No: $fatura_no",
        date('Y-m-d H:i:s'),
        $fatura_no,
        $eski_bakiye,
        $yeni_bakiye,
    ]);

    // ===== 9. SİPARİŞ VE SERVİS DURUMLARINI GÜNCELLE =====
    if (!empty($siparis_ids)) {
        $placeholders = implode(',', array_fill(0, count($siparis_ids), '?'));
        $pdo->prepare("UPDATE siparisler SET durum = 'FATURALANDI', updated_at = datetime('now','localtime') WHERE id IN ($placeholders)")
            ->execute($siparis_ids);
    }
    if (!empty($servis_ids)) {
        $placeholders = implode(',', array_fill(0, count($servis_ids), '?'));
        $pdo->prepare("UPDATE teknik_servis SET durum = 'FATURALANDI', updated_at = datetime('now','localtime') WHERE id IN ($placeholders)")
            ->execute($servis_ids);
    }

    $pdo->commit();

    flash_set("Seçili sipariş ve servisler başarıyla faturaya dönüştürüldü. Fatura No: $fatura_no", 'success');
    header('Location: ' . BASE_URL . '/fatura_olustur.php?id=' . $fatura_id);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash_set('Hata: ' . $e->getMessage(), 'danger');
    header('Location: ' . BASE_URL . '/cari_detay.php?id=' . ($cari_id ?? 0));
    exit;
}
?>