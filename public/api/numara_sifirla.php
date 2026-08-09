<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/numara_manager.php';
require_login();
require_csrf_json();
require_admin_json(); // GERİ ALINAMAZ bir işlem - sadece admin rolü kullanabilir

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Yalnızca POST metodu desteklenir.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $results = NumaraManager::resetAll($pdo);
    echo json_encode([
        'success' => true,
        'message' => 'Tüm numaralar sıfırlandı!',
        'results' => $results,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
