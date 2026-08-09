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
    if (ctype_digit($trimmed)) {
        $sayi = (int)$trimmed;
    } else {
        // Tam biçimlendirilmiş evrak numarası girildiyse (ör. MED2026000000046),
        // sabit ÖNEK + YIL kısmını çıkarıp geriye kalanı sıra numarası say.
        // NOT: Önceden sadece "sondaki rakam dizisini al" yapılıyordu -
        // 'sayi' formatında (FAT/EAR) önek+yıl+sıra hiç ayraçsız bitişik
        // olduğundan bu, yılı da sıraya dahil edip MED20262026... gibi
        // çift yıl üretiyordu.
        $ayar = NUMARA_AYARLARI[$prefix] ?? null;
        $beklenenOnEk = $ayar
            ? ($ayar['format'] === 'sayi'
                ? $ayar['prefix_ozel'] . $ayar['yil']
                : $ayar['prefix_ozel'] . '-' . $ayar['yil'] . '-')
            : '';
        $kalan = ($beklenenOnEk !== '' && stripos($trimmed, $beklenenOnEk) === 0)
            ? substr($trimmed, strlen($beklenenOnEk))
            : $trimmed;

        preg_match('/\d+$/', $kalan, $matches);
        if (empty($matches)) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz format. Sayı veya tam numara girin.']);
            exit;
        }
        $sayi = (int)$matches[0];
    }

    if ($sayi < 1) {
        echo json_encode(['success' => false, 'message' => 'Numara 1\'den küçük olamaz!']);
        exit;
    }

    [$success, $message] = NumaraManager::setNext($pdo, $prefix, $sayi);
    echo json_encode(['success' => $success, 'message' => $message]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}