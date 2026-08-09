<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/numara_manager.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

try {
    $prefix = $_GET['prefix'] ?? 'FAT';
    $numara = NumaraManager::getNext($pdo, $prefix);
    echo json_encode(['success' => true, 'numara' => $numara], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
