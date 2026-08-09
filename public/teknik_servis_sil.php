<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_csrf(BASE_URL . '/teknik_servis_listesi.php');

$id = safe_int($_GET['id'] ?? null);
if (!$id) {
    header('Location: ' . BASE_URL . '/teknik_servis_listesi.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM teknik_servis WHERE id = ?');
$stmt->execute([$id]);
$servis = $stmt->fetch();
if (!$servis) {
    http_response_code(404);
    die('Servis kaydı bulunamadı.');
}

$pdo->beginTransaction();
// İlişkili servis malzemelerini de sil (FK bütünlüğü için - orijinal
// SQLAlchemy modelinde cascade tanımlı değildi ama ilişkili kayıtları
// yetim bırakmamak için burada temizliyoruz)
$pdo->prepare('DELETE FROM servis_malzemeler WHERE teknik_servis_id = ?')->execute([$id]);
$pdo->prepare('DELETE FROM teknik_servis WHERE id = ?')->execute([$id]);
$pdo->commit();

flash_set('Servis kaydı silindi! No: ' . $servis['servis_no'], 'success');
header('Location: ' . BASE_URL . '/teknik_servis_listesi.php');
exit;
