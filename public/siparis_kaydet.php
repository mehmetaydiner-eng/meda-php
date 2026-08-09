<?php
/**
 * public/siparis_kaydet.php
 * Sipariş kaydetme işlemi (yeni veya düzenleme)
 * Sadece siparişi ve detaylarını kaydeder. Cari bakiyesi değişmez, stok düşmez.
 * Teknik servis ekleme mantığıyla aynı yapıdadır.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/numara_manager.php';
require_login();

// Sadece POST ile gelen istekleri kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/siparisler.php');
    exit;
}

require_csrf(BASE_URL . '/siparisler.php');

try {
    // ===== 1. FORM VERİLERİNİ AL =====
    $siparis_id = safe_int($_POST['siparis_id'] ?? null, 0) ?: null;
    $cari_id = safe_int($_POST['cari_id'] ?? null, 0);
    $siparis_no = trim($_POST['siparis_no'] ?? '');
    $siparis_tarihi = trim($_POST['siparis_tarihi'] ?? date('Y-m-d'));
    $aciklama = trim($_POST['aciklama'] ?? '');

    // ===== 2. ZORUNLU ALAN KONTROLLERİ =====
    if (!$cari_id) {
        flash_set('Lütfen bir cari seçin!', 'danger');
        header('Location: ' . BASE_URL . '/siparis_olustur.php' . ($siparis_id ? '?id=' . $siparis_id : ''));
        exit;
    }

    if (empty($siparis_no)) {
        $siparis_no = generate_siparis_no($pdo);
    }

    // ===== 3. ÜRÜN VERİLERİNİ AL =====
    $urun_ids = $_POST['urun_ids'] ?? [];
    $miktarlar = $_POST['miktarlar'] ?? [];
    $fiyatlar = $_POST['fiyatlar'] ?? [];
    $iskontolar = $_POST['iskontolar'] ?? [];
    $kdvler = $_POST['kdvler'] ?? [];

    if (empty($urun_ids) || count($urun_ids) === 0) {
        flash_set('Lütfen en az bir ürün ekleyin!', 'danger');
        header('Location: ' . BASE_URL . '/siparis_olustur.php' . ($siparis_id ? '?id=' . $siparis_id : ''));
        exit;
    }

    // ===== 4. VERİTABANI İŞLEMLERİ =====
    $pdo->beginTransaction();

    if ($siparis_id) {
        // ---- MEVCUT SİPARİŞİ GÜNCELLE ----
        $stmt = $pdo->prepare('SELECT * FROM siparisler WHERE id = ?');
        $stmt->execute([$siparis_id]);
        $mevcut = $stmt->fetch();
        if (!$mevcut) {
            throw new Exception('Sipariş bulunamadı.');
        }
        if ($mevcut['durum'] !== 'BEKLEMEDE') {
            throw new Exception('Bu sipariş artık düzenlenemez (durum: ' . $mevcut['durum'] . ').');
        }

        $update = $pdo->prepare('
            UPDATE siparisler 
            SET cari_id = ?, siparis_tarihi = ?, aciklama = ?, updated_at = datetime(\'now\',\'localtime\')
            WHERE id = ?
        ');
        $update->execute([$cari_id, $siparis_tarihi . ' ' . date('H:i:s'), $aciklama, $siparis_id]);

        // Eski detayları sil
        $pdo->prepare('DELETE FROM siparis_detaylari WHERE siparis_id = ?')->execute([$siparis_id]);
    } else {
        // ---- YENİ SİPARİŞ OLUŞTUR ----
        $insert = $pdo->prepare('
            INSERT INTO siparisler (siparis_no, cari_id, siparis_tarihi, durum, aciklama, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'), datetime(\'now\',\'localtime\'))
        ');
        $insert->execute([$siparis_no, $cari_id, $siparis_tarihi . ' ' . date('H:i:s'), 'BEKLEMEDE', $aciklama]);
        $siparis_id = (int)$pdo->lastInsertId();
    }

    // ===== 5. SİPARİŞ DETAYLARINI EKLE =====
    $insertDetay = $pdo->prepare('
        INSERT INTO siparis_detaylari 
        (siparis_id, urun_id, urun_adi, urun_kodu, barkod, birim, miktar, birim_fiyati, iskonto, iskonto_tutari, vergi_orani, vergi_tutari, ara_toplam, toplam_tutar, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))
    ');

    foreach ($urun_ids as $i => $urun_id) {
        if (empty($urun_id)) continue;

        $stmt = $pdo->prepare('SELECT * FROM urunler WHERE id = ?');
        $stmt->execute([$urun_id]);
        $urun = $stmt->fetch();
        if (!$urun) continue;

        $miktar = safe_float($miktarlar[$i] ?? 1, 1);
        $fiyat = safe_float($fiyatlar[$i] ?? 0, 0);
        $iskonto_orani = safe_float($iskontolar[$i] ?? 0, 0);
        $kdv_orani = safe_float($kdvler[$i] ?? 18, 18);

        $satir_toplam = $miktar * $fiyat;
        $iskonto_tutari = $satir_toplam * ($iskonto_orani / 100);
        $matrah = $satir_toplam - $iskonto_tutari;
        $kdv_tutari = $matrah * ($kdv_orani / 100);
        $toplam_tutar = $matrah + $kdv_tutari;

        $insertDetay->execute([
            $siparis_id,
            $urun_id,
            $urun['urun_adi'],
            $urun['urun_kodu'] ?? '',
            $urun['barkod'] ?? '',
            $urun['birim'] ?? 'ADET',
            $miktar,
            $fiyat,
            $iskonto_orani,
            $iskonto_tutari,
            $kdv_orani,
            $kdv_tutari,
            $satir_toplam,
            $toplam_tutar,
        ]);
    }

    // ===== 6. İŞLEMİ TAMAMLA =====
    // NOT: Cari bakiyesine hiç dokunulmaz! Stok düşülmez!
    $pdo->commit();

    flash_set(($siparis_id ? 'Sipariş güncellendi' : 'Sipariş oluşturuldu') . '! No: ' . $siparis_no, 'success');
    header('Location: ' . BASE_URL . '/siparisler.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash_set('Hata: ' . $e->getMessage(), 'danger');
    header('Location: ' . BASE_URL . '/siparis_olustur.php' . ($siparis_id ? '?id=' . $siparis_id : ''));
    exit;
}
?>