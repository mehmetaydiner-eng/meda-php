<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$id = safe_int($_GET['id'] ?? null);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Geçersiz id']);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM cariler WHERE id = ?');
$stmt->execute([$id]);
$cari = $stmt->fetch();

if (!$cari) {
    http_response_code(404);
    echo json_encode(['error' => 'Cari bulunamadı']);
    exit;
}

echo json_encode([
    'unvan'         => $cari['unvan'],
    'vergi_no'      => $cari['vergi_no'],
    'vergi_dairesi' => $cari['vergi_dairesi'],
    'adres'         => $cari['adres'],
    'telefon'       => $cari['telefon'],
    'email'         => $cari['email'],
    'bakiye'        => (float)($cari['bakiye'] ?? 0),
], JSON_UNESCAPED_UNICODE);
