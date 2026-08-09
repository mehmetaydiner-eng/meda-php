<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    $like = '%' . $q . '%';
    $sql = 'SELECT id, urun_kodu, urun_adi, barkod, seri_no,
                   satis_fiyati, satis_fiyati_doviz,
                   alis_fiyati, alis_fiyati_doviz,
                   stok_miktari, birim
            FROM urunler
            WHERE urun_adi LIKE ? OR urun_kodu LIKE ? OR barkod LIKE ? OR seri_no LIKE ?';
    $params = [$like, $like, $like, $like];

    if (ctype_digit($q)) {
        $sql .= ' OR id = ?';
        $params[] = (int)$q;
    }

    $sql .= ' ORDER BY urun_adi LIMIT 50';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} else {
    $stmt = $pdo->prepare('SELECT id, urun_kodu, urun_adi, barkod, seri_no,
                                   satis_fiyati, satis_fiyati_doviz,
                                   alis_fiyati, alis_fiyati_doviz,
                                   stok_miktari, birim
                            FROM urunler ORDER BY urun_adi LIMIT 50');
    $stmt->execute();
}

$urunler = $stmt->fetchAll();

$result = array_map(function ($u) {
    return [
        'id'                 => (int)$u['id'],
        'urun_kodu'          => $u['urun_kodu'],
        'urun_adi'           => $u['urun_adi'],
        'barkod'             => $u['barkod'],
        'seri_no'            => $u['seri_no'],
        'satis_fiyati'       => (float)$u['satis_fiyati'],
        'satis_fiyati_doviz' => $u['satis_fiyati_doviz'] ?: 'TL',
        'alis_fiyati'        => (float)($u['alis_fiyati'] ?? 0),
        'alis_fiyati_doviz'  => $u['alis_fiyati_doviz'] ?: 'TL',
        'stok_miktari'       => (float)$u['stok_miktari'],
        'birim'              => $u['birim'] ?: 'ADET',
    ];
}, $urunler);

echo json_encode($result, JSON_UNESCAPED_UNICODE);