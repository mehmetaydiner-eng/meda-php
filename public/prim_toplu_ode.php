<?php
/**
 * public/prim_toplu_ode.php
 * Seçili primleri toplu olarak öder.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_csrf(BASE_URL . '/primler.php');

$prim_ids = $_POST['prim_ids'] ?? [];
$hesap_id = safe_int($_POST['hesap_id'] ?? null);

if (empty($prim_ids) || !$hesap_id) {
    flash_set('Geçersiz istek - prim veya kasa bilgisi eksik.', 'danger');
    header('Location: ' . BASE_URL . '/primler.php');
    exit;
}

// Prim ID'lerini temizle
$prim_ids = array_filter(array_map('intval', $prim_ids));
if (empty($prim_ids)) {
    flash_set('Geçerli prim seçilmedi!', 'danger');
    header('Location: ' . BASE_URL . '/primler.php');
    exit;
}

// Kasa kontrolü
$stmt = $pdo->prepare('SELECT * FROM hesaplar WHERE id = ? AND is_active = 1');
$stmt->execute([$hesap_id]);
$hesap = $stmt->fetch();
if (!$hesap) {
    flash_set('Seçilen kasa bulunamadı ya da pasif!', 'danger');
    header('Location: ' . BASE_URL . '/primler.php');
    exit;
}

// Placeholder oluştur
$placeholders = implode(',', array_fill(0, count($prim_ids), '?'));

try {
    $pdo->beginTransaction();

    // Primleri getir (sadece BEKLEMEDE olanlar)
    $stmt = $pdo->prepare("SELECT * FROM komisyon_hareketleri WHERE id IN ($placeholders) AND odeme_durumu = 'BEKLEMEDE' AND komisyon_turu = 'SATIŞ_PRİMİ'");
    $stmt->execute($prim_ids);
    $primler = $stmt->fetchAll();

    if (count($primler) === 0) {
        throw new Exception('Ödenecek geçerli prim bulunamadı (zaten ödenmiş veya iptal edilmiş olabilir).');
    }

    $toplam_tutar = 0;
    $prim_id_list = [];

    foreach ($primler as $prim) {
        $tutar = (float)$prim['tutar'];
        $toplam_tutar += $tutar;
        $prim_id_list[] = (int)$prim['id'];
    }

    // Kasa bakiyesini güncelle (toplu para çıkışı)
    $stokOncesiBakiye = (float)$hesap['bakiye'];
    $yeniBakiye = $stokOncesiBakiye - $toplam_tutar;
    $pdo->prepare('UPDATE hesaplar SET bakiye = ? WHERE id = ?')->execute([$yeniBakiye, $hesap_id]);

    // Primleri ÖDENDİ yap
    $updatePlaceholders = implode(',', array_fill(0, count($prim_id_list), '?'));
    $updateStmt = $pdo->prepare("UPDATE komisyon_hareketleri SET odeme_durumu = 'ÖDENDİ', hesap_id = ?, odeme_tarihi = datetime('now','localtime'), updated_at = datetime('now','localtime') WHERE id IN ($updatePlaceholders)");
    $updateStmt->execute(array_merge([$hesap_id], $prim_id_list));

    // Hesap hareketi oluştur (tek bir toplu hareket)
    $insertHareket = $pdo->prepare(
        'INSERT INTO hesap_hareketleri
            (hesap_id, cari_id, hareket_no, hareket_tarihi, islem_turu, hareket_turu, tutar,
             para_birimi, aciklama, referans_no, hesap_bakiye_oncesi, hesap_bakiye_sonrasi, created_by, created_at)
         VALUES (?, NULL, ?, datetime(\'now\',\'localtime\'), ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
    );
    $insertHareket->execute([
        $hesap_id,
        generate_hareket_no(),
        'ÇIKIŞ',
        'TOPLU_PRİM_ÖDEME',
        $toplam_tutar,
        'TRY',
        'Toplu Prim Ödemesi - ' . count($prim_id_list) . ' prim',
        'TOPLU-' . date('YmdHis'),
        $stokOncesiBakiye,
        $yeniBakiye,
        current_user()['username'] ?? '',
    ]);

    $pdo->commit();

    flash_set(
        count($prim_id_list) . ' prim toplam ' . number_format($toplam_tutar, 2, ',', '.') . ' ₺ olarak ' . e($hesap['hesap_adi']) . ' kasasından ödendi.',
        'success'
    );

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash_set('Ödeme sırasında hata oluştu: ' . $e->getMessage(), 'danger');
}

header('Location: ' . BASE_URL . '/primler.php');
exit;
?>