<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_csrf(BASE_URL . '/siparisler.php');

$id = safe_int($_GET['id'] ?? 0);
if (!$id) {
    flash_set('Geçersiz sipariş ID!', 'danger');
    header('Location: ' . BASE_URL . '/siparisler.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM siparisler WHERE id = ?');
    $stmt->execute([$id]);
    $siparis = $stmt->fetch();
    if (!$siparis) {
        throw new Exception('Sipariş bulunamadı.');
    }
    if ($siparis['durum'] !== 'BEKLEMEDE') {
        throw new Exception('Bu sipariş silinemez (durum: ' . $siparis['durum'] . ').');
    }

    // Sadece sipariş detaylarını ve siparişi sil
    // Cari bakiyesine DOKUNMA (sipariş zaten bakiyeyi etkilemiyor)
    $pdo->prepare('DELETE FROM siparis_detaylari WHERE siparis_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM siparisler WHERE id = ?')->execute([$id]);

    $pdo->commit();
    flash_set('Sipariş başarıyla silindi!', 'success');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash_set('Hata: ' . $e->getMessage(), 'danger');
}

header('Location: ' . BASE_URL . '/siparisler.php');
exit;
?>