<?php
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['count' => 0]);
    exit;
}

$user = current_user();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM todos WHERE user_id = ? AND status = 'Bekliyor'");
$stmt->execute([$user['id']]);
$count = (int)$stmt->fetchColumn();

echo json_encode(['count' => $count], JSON_UNESCAPED_UNICODE);
