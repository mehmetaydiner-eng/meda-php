<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$id = safe_int($_GET['id'] ?? null);
if (!$id) {
    http_response_code(404);
    echo json_encode(['error' => 'Geçersiz id']);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM urunler WHERE id = ?');
$stmt->execute([$id]);
$urun = $stmt->fetch();

if (!$urun) {
    http_response_code(404);
    echo json_encode(['error' => 'Ürün bulunamadı']);
    exit;
}

echo json_encode([
    'id'                 => (int)$urun['id'],
    'urun_kodu'          => $urun['urun_kodu'],
    'urun_adi'           => $urun['urun_adi'],
    'barkod'             => $urun['barkod'] ?: '-',
    'seri_no'            => $urun['seri_no'] ?: '-',
    'urun_tipi'          => $urun['urun_tipi'] ?: 'SIFIR',
    'kategori'           => $urun['kategori'] ?: '-',
    'birim'              => $urun['birim'] ?: 'ADET',
    'alis_fiyati'        => (float)($urun['alis_fiyati'] ?: 0),
    'alis_fiyati_doviz'  => $urun['alis_fiyati_doviz'] ?: 'TL',
    'satis_fiyati'       => (float)($urun['satis_fiyati'] ?: 0),
    'satis_fiyati_doviz' => $urun['satis_fiyati_doviz'] ?: 'TL',
    'stok_miktari'       => (float)($urun['stok_miktari'] ?: 0),
    'min_stok'           => (float)($urun['min_stok'] ?: 0),
    'max_stok'           => (float)($urun['max_stok'] ?: 0),
    'aciklama'           => $urun['aciklama'] ?: '-',
    'created_at'         => $urun['created_at'] ? format_tarih($urun['created_at'], 'd.m.Y H:i') : '-',
], JSON_UNESCAPED_UNICODE);
