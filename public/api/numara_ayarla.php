<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/numara_manager.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

try {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    require_csrf_json($data['csrf_token'] ?? null);

    $prefix = $data['prefix'] ?? null;
    $yeniNumaraRaw = $data['yeni_numara'] ?? null;

    if (!$prefix || $yeniNumaraRaw === null) {
        echo json_encode(['success' => false, 'message' => 'Prefix ve numara gerekli!']);
        exit;
    }

    $trimmed = trim((string)$yeniNumaraRaw);
    if (!ctype_digit($trimmed)) {
        echo json_encode([
            'success' => false,
            'message' => "Bu alana sadece SAF SAYI girilebilir (örn. 53) - '{$trimmed}' geçerli bir sayı değil.",
        ]);
        exit;
    }

    // Yıl, evrak numarasına formatla() içinde otomatik ekleniyor - kullanıcı
    // buraya yılı da (ör. "20260005") yazarsa üretilen numarada yıl iki kez
    // görünür (STM-2026-20260005 gibi). Bu yanlış kullanımı erken yakala.
    $ayar = NUMARA_AYARLARI[$prefix] ?? null;
    if ($ayar) {
        $haneSayisi = $ayar['format'] === 'sayi' ? ($ayar['hane_sayisi'] ?? 4) : 4;
        if (strlen($trimmed) > $haneSayisi && str_starts_with($trimmed, $ayar['yil'])) {
            $oneriSira = (int)substr($trimmed, strlen($ayar['yil']));
            echo json_encode([
                'success' => false,
                'message' => "Yıl ({$ayar['yil']}) otomatik ekleniyor, sadece SIRA NUMARASINI girin - "
                    . "'{$trimmed}' yerine örn. '{$oneriSira}' yazın.",
            ]);
            exit;
        }
    }

    [$success, $message] = NumaraManager::setNext($pdo, $prefix, (int)$trimmed);
    echo json_encode(['success' => $success, 'message' => $message]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}