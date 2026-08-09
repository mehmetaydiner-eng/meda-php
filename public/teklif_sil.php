<?php
/**
 * public/teklif_sil.php
 * Teklif ve ona ait detay kalemlerini siler.
 */

require_once __DIR__ . '/../includes/auth.php';
require_login();
require_csrf(BASE_URL . '/teklifler.php');

$id = safe_int($_GET['id'] ?? null);
if (!$id) {
    header('Location: ' . BASE_URL . '/teklifler.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM teklifler WHERE id = ?');
$stmt->execute([$id]);
$teklif = $stmt->fetch();

if (!$teklif) {
    http_response_code(404);
    die('Teklif bulunamadı.');
}

try {
    $pdo->beginTransaction();

    // Önce teklif detaylarını sil
    $stmt = $pdo->prepare('DELETE FROM teklif_detaylari WHERE teklif_id = ?');
    $stmt->execute([$id]);

    // Sonra teklifin kendisini sil
    $stmt = $pdo->prepare('DELETE FROM teklifler WHERE id = ?');
    $stmt->execute([$id]);

    $pdo->commit();

    flash_set("{$teklif['teklif_no']} numaralı teklif başarıyla silindi.", 'success');
} catch (Exception $e) {
    $pdo->rollBack();
    flash_set("Teklif silinirken hata oluştu: " . $e->getMessage(), 'danger');
}

header('Location: ' . BASE_URL . '/teklifler.php');
exit;
