<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$q        = trim($_GET['q'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');

$sql = 'SELECT * FROM urunler WHERE 1=1';
$params = [];

if ($q !== '') {
    $sql .= ' AND (urun_adi LIKE ? OR urun_kodu LIKE ? OR barkod LIKE ? OR seri_no LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}

if ($kategori !== '') {
    $sql .= ' AND kategori = ?';
    $params[] = turkce_upper($kategori);
}

$sql .= ' ORDER BY urun_adi LIMIT 20';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$urunler = $stmt->fetchAll();

$result = array_map(function ($u) {
    return [
        'id'                 => (int)$u['id'],
        'urun_kodu'          => $u['urun_kodu'],
        'urun_adi'           => $u['urun_adi'],
        'barkod'             => $u['barkod'],
        'seri_no'            => $u['seri_no'],
        'urun_tipi'          => $u['urun_tipi'],
        'alis_fiyati'        => (float)$u['alis_fiyati'],
        'alis_fiyati_doviz'  => $u['alis_fiyati_doviz'],
        'satis_fiyati'       => (float)$u['satis_fiyati'],
        'satis_fiyati_doviz' => $u['satis_fiyati_doviz'],
        'stok_miktari'       => (float)$u['stok_miktari'],
        'min_stok'           => (float)$u['min_stok'],
        'kategori'           => $u['kategori'],
        'birim'              => $u['birim'],
        'resim'              => $u['resim'],
    ];
}, $urunler);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
