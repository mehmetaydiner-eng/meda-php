<?php
/**
 * public/api/prim_ekle.php
 * Prim ekleme API'si - manuel formdan da çağrılabilir.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

function generate_prim_no(): string
{
    return 'PRM' . date('YmdHis') . '-' . random_int(1000, 9999);
}

try {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    require_csrf_json($data['csrf_token'] ?? null);

    $cari_id = safe_int($data['cari_id'] ?? null, 0) ?: null;
    $tutar = safe_float($data['tutar'] ?? null);
    $oran = isset($data['oran']) && $data['oran'] !== null ? safe_float($data['oran']) : 0; // 0 ata
    $matrah = isset($data['matrah']) && $data['matrah'] !== null ? safe_float($data['matrah']) : 0; // 0 ata
    $referans_no = trim((string)($data['referans_no'] ?? ''));
    $fatura_id = safe_int($data['fatura_id'] ?? null, 0) ?: null;
    $makbuz_id = safe_int($data['makbuz_id'] ?? null, 0) ?: null;
    $aciklama = turkce_upper(trim((string)($data['aciklama'] ?? '')));

    if (!$cari_id) {
        echo json_encode(['success' => false, 'message' => 'Prim verilecek kişi seçilmedi!'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($tutar <= 0) {
        echo json_encode(['success' => false, 'message' => "Prim tutarı 0'dan büyük olmalıdır!"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM cariler WHERE id = ? AND cari_turu = 'PERSONEL'");
    $stmt->execute([$cari_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz personel/prim kişisi!'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $now = date('Y-m-d H:i:s');
    $prim_no = generate_prim_no();

    $insert = $pdo->prepare(
        'INSERT INTO komisyon_hareketleri
            (hesap_id, cari_id, fatura_id, komisyon_no, tarih, komisyon_turu, matrah, oran, tutar,
             para_birimi, aciklama, referans_no, odeme_durumu, created_at, updated_at)
         VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $insert->execute([
        $cari_id,
        $fatura_id,
        $prim_no,
        $now,
        'SATIŞ_PRİMİ',
        $matrah,
        $oran,
        $tutar,
        'TRY',
        $aciklama,
        $referans_no,
        'BEKLEMEDE',
        $now,
        $now,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Prim kaydı oluşturuldu!',
        'prim_id' => (int)$pdo->lastInsertId(),
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}