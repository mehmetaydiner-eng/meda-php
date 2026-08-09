<?php
/**
 * public/kategori_sil.php
 *
 * Bir kategoriyi TANIMLI KATEGORİLER listesinden siler. Ürünlerin
 * `kategori` alanı serbest metin olarak saklandığı için (foreign key
 * değil), bu silme işlemi mevcut ürünlerin kategori bilgisini HİÇ
 * etkilemez - sadece bundan sonra yeni ürün eklerken dropdown'da bu
 * seçenek görünmez.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_csrf(BASE_URL . '/kategori_yonetim.php');

$id = safe_int($_GET['id'] ?? null);
if (!$id) {
    header('Location: ' . BASE_URL . '/kategori_yonetim.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM kategoriler WHERE id = ?');
$stmt->execute([$id]);
$kategori = $stmt->fetch();

if (!$kategori) {
    http_response_code(404);
    die('Kategori bulunamadı.');
}

$pdo->prepare('DELETE FROM kategoriler WHERE id = ?')->execute([$id]);

flash_set($kategori['kategori_adi'] . ' kategorisi silindi.', 'success');
header('Location: ' . BASE_URL . '/kategori_yonetim.php');
exit;
