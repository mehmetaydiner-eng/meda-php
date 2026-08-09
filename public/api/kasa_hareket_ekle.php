<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_csrf_json();

header('Content-Type: application/json; charset=utf-8');

try {
    $kasa_id = safe_int($_POST['kasa_id'] ?? null, 0) ?: null;
    $cari_id = safe_int($_POST['cari_id'] ?? null, 0) ?: null;
    // NOT: hareket_turu/islem_turu sabit <select> değerleridir - turkce_upper()
    // uygulanmıyor (Cariler modülünde bulunan İ/I hatasını burada tekrarlamamak için).
    $islem_turu = trim($_POST['islem_turu'] ?? '');
    $hareket_turu = trim($_POST['hareket_turu'] ?? '');
    $tutar = safe_float($_POST['tutar'] ?? null);
    $aciklama = turkce_upper(trim($_POST['aciklama'] ?? ''));
    $referans_no = turkce_upper(trim($_POST['referans_no'] ?? ''));
    $odeme_turu = trim($_POST['odeme_turu'] ?? '');
    $tarih_str = trim($_POST['tarih'] ?? '');

    if (!$kasa_id) {
        echo json_encode(['success' => false, 'message' => 'Kasa seçimi zorunludur!'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($tutar <= 0) {
        echo json_encode(['success' => false, 'message' => "Tutar 0'dan büyük olmalıdır!"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $tarih = $tarih_str !== '' ? $tarih_str . ' ' . date('H:i:s') : date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("SELECT * FROM hesaplar WHERE id = ? AND hesap_turu = 'KASA'");
    $stmt->execute([$kasa_id]);
    $kasa = $stmt->fetch();
    if (!$kasa) {
        echo json_encode(['success' => false, 'message' => 'Geçerli bir kasa hesabı seçin!'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $cari = null;
    if ($cari_id) {
        $stmt = $pdo->prepare('SELECT * FROM cariler WHERE id = ?');
        $stmt->execute([$cari_id]);
        $cari = $stmt->fetch();
    }

    $isGiris = in_array($islem_turu, ['GELEN', 'GİRİŞ'], true);
    $kasaBakiyeOncesi = (float)$kasa['bakiye'];
    $kasaBakiyeSonrasi = $isGiris ? $kasaBakiyeOncesi + $tutar : $kasaBakiyeOncesi - $tutar;

    $pdo->beginTransaction();

    $pdo->prepare('UPDATE hesaplar SET bakiye = ? WHERE id = ?')->execute([$kasaBakiyeSonrasi, $kasa_id]);

    if ($cari) {
        $cariBakiyeSonrasi = $isGiris ? (float)$cari['bakiye'] - $tutar : (float)$cari['bakiye'] + $tutar;
        $pdo->prepare('UPDATE cariler SET bakiye = ? WHERE id = ?')->execute([$cariBakiyeSonrasi, $cari_id]);
    }

    $insert = $pdo->prepare(
        'INSERT INTO hesap_hareketleri
            (hesap_id, cari_id, hareket_no, hareket_tarihi, islem_turu, hareket_turu, tutar,
             para_birimi, aciklama, referans_no, odeme_turu, hesap_bakiye_oncesi, hesap_bakiye_sonrasi, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
    );
    $insert->execute([
        $kasa_id, $cari_id, generate_hareket_no(), $tarih, $islem_turu, $hareket_turu, $tutar,
        'TRY', $aciklama, $referans_no, $odeme_turu, $kasaBakiyeOncesi, $kasaBakiyeSonrasi,
    ]);

    $pdo->commit();

    echo json_encode([
        'success'     => true,
        'message'     => 'Kasa hareketi başarıyla kaydedildi!',
        'yeni_bakiye' => $kasaBakiyeSonrasi,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
