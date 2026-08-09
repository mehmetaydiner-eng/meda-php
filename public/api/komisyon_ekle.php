<?php
/**
 * NOT: Orijinal Flask uygulamasında bu endpoint ('/api/komisyon/ekle') hiçbir
 * şablon/arayüzden çağrılmıyordu - yani tamamen ölü/bağlanmamış bir API'ydi.
 * Burada birebir aynı mantıkla taşındı (ileride bir "Komisyon Ekle" arayüzü
 * yapılırsa hazır olsun diye), ama şu an sistemde bunu çağıran bir buton yok.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();
require_csrf_json();

header('Content-Type: application/json; charset=utf-8');

/** Flask: generate_komisyon_no() - çakışmayı önlemek için rastgele bileşen eklendi (bkz. generate_hareket_no) */
function generate_komisyon_no(): string
{
    return 'KOM' . date('YmdHis') . '-' . random_int(1000, 9999);
}

try {
    $cari_id = safe_int($_POST['cari_id'] ?? null, 0) ?: null;
    $hesap_id = safe_int($_POST['hesap_id'] ?? null, 0) ?: null;
    $fatura_id = safe_int($_POST['fatura_id'] ?? null, 0) ?: null;

    $komisyon_turu = turkce_upper(trim($_POST['komisyon_turu'] ?? ''));
    $matrah = safe_float($_POST['matrah'] ?? null);
    $oran = safe_float($_POST['oran'] ?? null);
    $aciklama = turkce_upper(trim($_POST['aciklama'] ?? ''));
    $referans_no = turkce_upper(trim($_POST['referans_no'] ?? ''));

    if (!$cari_id) {
        echo json_encode(['success' => false, 'message' => 'Cari seçimi zorunludur!'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($matrah <= 0) {
        echo json_encode(['success' => false, 'message' => "Matrah 0'dan büyük olmalıdır!"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $tutar = $matrah * ($oran / 100);

    $insert = $pdo->prepare(
        'INSERT INTO komisyon_hareketleri
            (hesap_id, cari_id, fatura_id, komisyon_no, tarih, komisyon_turu, matrah, oran, tutar,
             para_birimi, aciklama, referans_no, odeme_durumu, created_at, updated_at)
         VALUES (?, ?, ?, ?, datetime(\'now\',\'localtime\'), ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'), datetime(\'now\',\'localtime\'))'
    );
    $insert->execute([
        $hesap_id, $cari_id, $fatura_id, generate_komisyon_no(), $komisyon_turu, $matrah, $oran, $tutar,
        'TRY', $aciklama, $referans_no, 'BEKLEMEDE',
    ]);

    echo json_encode([
        'success'     => true,
        'message'     => 'Komisyon başarıyla kaydedildi!',
        'komisyon_id' => (int)$pdo->lastInsertId(),
        'tutar'       => $tutar,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
