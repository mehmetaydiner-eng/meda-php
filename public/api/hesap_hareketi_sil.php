<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_csrf_json($_GET['csrf_token'] ?? null);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Yalnızca DELETE metodu desteklenir.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = safe_int($_GET['id'] ?? null);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz id.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT * FROM hesap_hareketleri WHERE id = ?');
    $stmt->execute([$id]);
    $hareket = $stmt->fetch();

    if (!$hareket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Hareket bulunamadı.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo->beginTransaction();

    if ($hareket['cari_id']) {
        $delta = $hareket['islem_turu'] === 'GİRİŞ' ? -$hareket['tutar'] : $hareket['tutar'];
        $update = $pdo->prepare('UPDATE cariler SET bakiye = bakiye + ? WHERE id = ?');
        $update->execute([$delta, $hareket['cari_id']]);
    }

    if ($hareket['hesap_id']) {
        $delta = $hareket['islem_turu'] === 'GİRİŞ' ? -$hareket['tutar'] : $hareket['tutar'];
        $update = $pdo->prepare('UPDATE hesaplar SET bakiye = bakiye + ? WHERE id = ?');
        $update->execute([$delta, $hareket['hesap_id']]);
    }

    $delete = $pdo->prepare('DELETE FROM hesap_hareketleri WHERE id = ?');
    $delete->execute([$id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Hareket silindi!'], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
