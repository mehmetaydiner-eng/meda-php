<?php
/**
 * public/prim_ode.php
 *
 * Bekleyen bir prim kaydını "ÖDENDİ" durumuna alır VE seçilen kasadan
 * gerçekten para çıkarır: hesaplar.bakiye güncellenir + gerçek bir
 * hesap_hareketi (defter) satırı oluşturulur. Bu, Efe'nin "prim
 * ödendiğinde bir kasadan gerçekten para çıksın" isteğini karşılıyor.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_csrf(BASE_URL . '/primler.php');

$id = safe_int($_GET['id'] ?? null);
$hesap_id = safe_int($_GET['hesap_id'] ?? null);

if (!$id || !$hesap_id) {
    flash_set('Geçersiz istek - prim veya kasa bilgisi eksik.', 'danger');
    header('Location: ' . BASE_URL . '/primler.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM komisyon_hareketleri WHERE id = ? AND komisyon_turu = 'SATIŞ_PRİMİ'");
$stmt->execute([$id]);
$prim = $stmt->fetch();

if (!$prim) {
    http_response_code(404);
    die('Prim kaydı bulunamadı.');
}

if ($prim['odeme_durumu'] === 'ÖDENDİ') {
    flash_set('Bu prim zaten ödenmiş!', 'warning');
    header('Location: ' . BASE_URL . '/primler.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM hesaplar WHERE id = ? AND is_active = 1');
$stmt->execute([$hesap_id]);
$hesap = $stmt->fetch();

if (!$hesap) {
    flash_set('Seçilen kasa bulunamadı ya da pasif!', 'danger');
    header('Location: ' . BASE_URL . '/primler.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $tutar = (float)$prim['tutar'];
    $stokOncesiBakiye = (float)$hesap['bakiye'];
    $yeniBakiye = $stokOncesiBakiye - $tutar;

    // 1. Kasa bakiyesini güncelle (para çıkışı)
    $pdo->prepare('UPDATE hesaplar SET bakiye = ? WHERE id = ?')->execute([$yeniBakiye, $hesap_id]);

    // 2. Prim kaydını ÖDENDİ yap
    $pdo->prepare(
        "UPDATE komisyon_hareketleri SET odeme_durumu = 'ÖDENDİ', hesap_id = ?, odeme_tarihi = datetime('now','localtime'), updated_at = datetime('now','localtime') WHERE id = ?"
    )->execute([$hesap_id, $id]);

    // 3. Gerçek bir hesap hareketi (defter) satırı oluştur - Hesap Hareketleri
    // sayfasında görünür, kasa raporlarında doğru şekilde yansır.
    $insertHareket = $pdo->prepare(
        'INSERT INTO hesap_hareketleri
            (hesap_id, cari_id, hareket_no, hareket_tarihi, islem_turu, hareket_turu, tutar,
             para_birimi, aciklama, referans_no, hesap_bakiye_oncesi, hesap_bakiye_sonrasi, created_by, created_at)
         VALUES (?, ?, ?, datetime(\'now\',\'localtime\'), ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
    );
    $insertHareket->execute([
        $hesap_id, $prim['cari_id'], generate_hareket_no(), 'ÇIKIŞ', 'PRİM_ÖDEME', $tutar,
        'TRY', 'Prim Ödemesi - ' . $prim['komisyon_no'], $prim['komisyon_no'],
        $stokOncesiBakiye, $yeniBakiye, current_user()['username'] ?? '',
    ]);

    $pdo->commit();

    flash_set('Prim ödemesi tamamlandı! ' . number_format($tutar, 2, ',', '.') . ' ₺ ' . e($hesap['hesap_adi']) . ' kasasından düşüldü.', 'success');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash_set('Ödeme sırasında hata oluştu: ' . $e->getMessage(), 'danger');
}

header('Location: ' . BASE_URL . '/primler.php');
exit;
