<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_csrf_json();

header('Content-Type: application/json; charset=utf-8');

try {
    $urun_kodu = turkce_upper(trim($_POST['urun_kodu'] ?? ''));
    $urun_adi  = turkce_upper(trim($_POST['urun_adi'] ?? ''));
    $barkod    = trim($_POST['barkod'] ?? '');
    $seri_no   = turkce_upper(trim($_POST['seri_no'] ?? ''));
    $urun_tipi = turkce_upper(trim($_POST['urun_tipi'] ?? 'SIFIR'));
    $kategori  = turkce_upper(trim($_POST['kategori'] ?? ''));
    $birim     = turkce_upper(trim($_POST['birim'] ?? 'ADET'));

    $alis_fiyati        = safe_float($_POST['alis_fiyati'] ?? null);
    $alis_fiyati_doviz  = turkce_upper(trim($_POST['alis_fiyati_doviz'] ?? 'TL'));
    $satis_fiyati       = safe_float($_POST['satis_fiyati'] ?? null);
    $satis_fiyati_doviz = turkce_upper(trim($_POST['satis_fiyati_doviz'] ?? 'TL'));
    $stok_miktari       = safe_float($_POST['stok_miktari'] ?? null);
    $min_stok           = safe_float($_POST['min_stok'] ?? null);
    $max_stok           = safe_float($_POST['max_stok'] ?? null);
    $aciklama           = turkce_upper(trim($_POST['aciklama'] ?? ''));

    if ($barkod === '') {
        $barkod = generate_barkod();
    }

    if ($urun_kodu === '') {
        echo json_encode(['success' => false, 'message' => 'Ürün kodu zorunludur!'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($urun_adi === '') {
        echo json_encode(['success' => false, 'message' => 'Ürün adı zorunludur!'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM urunler WHERE urun_kodu = ?');
    $stmt->execute([$urun_kodu]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bu ürün kodu zaten kullanılıyor!'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($barkod !== '') {
        $stmt = $pdo->prepare('SELECT id FROM urunler WHERE barkod = ?');
        $stmt->execute([$barkod]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Bu barkod zaten kullanılıyor!'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $insert = $pdo->prepare(
        'INSERT INTO urunler
            (urun_kodu, urun_adi, barkod, seri_no, urun_tipi, kategori, birim,
             alis_fiyati, alis_fiyati_doviz, satis_fiyati, satis_fiyati_doviz,
             stok_miktari, min_stok, max_stok, aciklama, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'), datetime(\'now\',\'localtime\'))'
    );
    $insert->execute([
        $urun_kodu, $urun_adi, $barkod, $seri_no, $urun_tipi, $kategori, $birim,
        $alis_fiyati, $alis_fiyati_doviz, $satis_fiyati, $satis_fiyati_doviz,
        $stok_miktari, $min_stok, $max_stok, $aciklama,
    ]);

    echo json_encode([
        'success'      => true,
        'message'      => 'Ürün başarıyla eklendi!',
        'urun_id'      => (int)$pdo->lastInsertId(),
        'urun_kodu'    => $urun_kodu,
        'urun_adi'     => $urun_adi,
        'barkod'       => $barkod,
        'satis_fiyati' => $satis_fiyati,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
