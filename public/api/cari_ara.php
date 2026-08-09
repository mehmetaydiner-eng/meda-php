<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$q   = trim($_GET['q'] ?? '');
$tur = trim($_GET['tur'] ?? '');

$sql = 'SELECT id, unvan, vergi_no, telefon, email, cari_turu, bakiye FROM cariler WHERE 1=1';
$params = [];

if ($q !== '') {
    $sql .= ' AND (unvan LIKE ? OR vergi_no LIKE ? OR telefon LIKE ? OR email LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}

if ($tur !== '' && $tur !== 'TÜMÜ') {
    $sql .= ' AND cari_turu = ?';
    $params[] = turkce_upper($tur);
}

$sql .= ' ORDER BY unvan LIMIT 50';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cariler = $stmt->fetchAll();

$result = array_map(function ($c) {
    return [
        'id'        => (int)$c['id'],
        'unvan'     => $c['unvan'],
        'vergi_no'  => $c['vergi_no'],
        'telefon'   => $c['telefon'],
        'email'     => $c['email'],
        'cari_turu' => $c['cari_turu'],
        'bakiye'    => (float)($c['bakiye'] ?? 0),
    ];
}, $cariler);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
