<?php
/**
 * public/hesap_durum_degistir.php
 *
 * Bir Hesap/Kasa'yı aktif/pasif durumu arasında geçirir. BİLİNÇLİ OLARAK
 * bir "silme" (DELETE) özelliği DEĞİLDİR - hesap kaydı ve ona ait TÜM
 * hesap_hareketleri satırları veritabanında olduğu gibi kalır. Pasife
 * alınan bir hesap sadece yeni işlem ekranlarındaki (Hızlı İşlem, Kasa
 * Ana, Makbuz Oluştur, Tahsilat Makbuzu) seçim listelerinden çıkar - böylece
 * yanlışlıkla artık kullanılmayan bir kasaya yeni işlem düşmesi engellenir,
 * ama geçmişe dönük raporlar (Hesap Hareketleri, Kasa Raporu) etkilenmez.
 */

require_once __DIR__ . '/../includes/auth.php';
require_login();
require_csrf(BASE_URL . '/hesaplar_listesi.php');

$id = safe_int($_GET['id'] ?? null);
if (!$id) {
    header('Location: ' . BASE_URL . '/hesaplar_listesi.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM hesaplar WHERE id = ?');
$stmt->execute([$id]);
$hesap = $stmt->fetch();

if (!$hesap) {
    http_response_code(404);
    die('Hesap bulunamadı.');
}

$yeniDurum = (int)$hesap['is_active'] ? 0 : 1;
$pdo->prepare('UPDATE hesaplar SET is_active = ? WHERE id = ?')->execute([$yeniDurum, $id]);

if ($yeniDurum) {
    flash_set($hesap['hesap_adi'] . ' tekrar aktif edildi.', 'success');
} else {
    flash_set(
        $hesap['hesap_adi'] . ' pasife alındı. Bu hesap artık yeni işlemlerde seçilemeyecek, ' .
        'ama geçmiş hareketleri ve raporları korunuyor (hiçbir veri silinmedi).',
        'success'
    );
}

header('Location: ' . BASE_URL . '/hesaplar_listesi.php');
exit;
