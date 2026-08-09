<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_csrf_json();

header('Content-Type: application/json; charset=utf-8');

function normalize_cari_turu_ajax(string $raw): string
{
    $raw = mb_strtoupper($raw, 'UTF-8');
    if (str_contains($raw, 'TEDARIK') || str_contains($raw, 'TEDARİK')) {
        return 'TEDARİKÇİ';
    }
    if (str_contains($raw, 'MUSTERI') || str_contains($raw, 'MÜŞTERİ')) {
        return 'MÜŞTERİ';
    }
    return 'MÜŞTERİ';
}

try {
    $unvan     = turkce_upper(trim($_POST['unvan'] ?? ''));
    $cari_turu = normalize_cari_turu_ajax($_POST['cari_turu'] ?? 'MÜŞTERİ');

    $vergi_no      = turkce_upper(trim($_POST['vergi_no'] ?? ''));
    $vergi_dairesi = turkce_upper(trim($_POST['vergi_dairesi'] ?? ''));
    $adres         = turkce_upper(trim($_POST['adres'] ?? ''));
    $telefon       = trim($_POST['telefon'] ?? '');
    $email         = mb_strtolower(trim($_POST['email'] ?? ''), 'UTF-8');
    $yetkili       = turkce_upper(trim($_POST['yetkili'] ?? ''));

    if ($unvan === '') {
        echo json_encode(['success' => false, 'message' => 'Ünvan zorunludur!'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM cariler WHERE unvan = ?');
    $stmt->execute([$unvan]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bu ünvan zaten kayıtlı!'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($vergi_no !== '') {
        $stmt = $pdo->prepare('SELECT id FROM cariler WHERE vergi_no = ?');
        $stmt->execute([$vergi_no]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Bu vergi numarası zaten kayıtlı!'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $insert = $pdo->prepare(
        'INSERT INTO cariler (unvan, cari_turu, vergi_no, vergi_dairesi, adres, telefon, email, yetkili, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
    );
    $insert->execute([$unvan, $cari_turu, $vergi_no, $vergi_dairesi, $adres, $telefon, $email, $yetkili]);

    $cariId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success'   => true,
        'message'   => 'Cari başarıyla eklendi!',
        'cari_id'   => $cariId,
        'unvan'     => $unvan,
        'cari_turu' => $cari_turu,
        'vergi_no'  => $vergi_no,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
