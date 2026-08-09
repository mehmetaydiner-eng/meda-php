<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_csrf_json();

header('Content-Type: application/json; charset=utf-8');

try {
    $cari_id      = safe_int($_POST['cari_id'] ?? null, 0) ?: null;
    $hesap_id     = safe_int($_POST['hesap_id'] ?? null, 0) ?: null;
    // NOT: hareket_turu ve islem_turu sabit <select> seçenekleridir (TAHSİLAT,
    // ÖDEME, GİRİŞ, ÇIKIŞ vb.) - serbest metin değildir. turkce_upper() burada
    // KASITLI OLARAK kullanılmıyor çünkü İ->I (noktasız) dönüşümü yaparak
    // "GİRİŞ" değerini "GIRIŞ"a çeviriyor ve daha sonraki karşılaştırmalar
    // (== 'GİRİŞ') hep başarısız oluyor. Bu, orijinal Flask uygulamasında da
    // var olan bir hataydı; burada düzeltildi.
    $hareket_turu = trim($_POST['hareket_turu'] ?? '');
    $islem_turu   = trim($_POST['islem_turu'] ?? '');
    $tutar        = safe_float($_POST['tutar'] ?? null);
    $tarih_str    = trim($_POST['tarih'] ?? '');
    $aciklama     = turkce_upper(trim($_POST['aciklama'] ?? ''));
    $referans_no  = turkce_upper(trim($_POST['referans_no'] ?? ''));
    $ilgili_kisi  = turkce_upper(trim($_POST['ilgili_kisi'] ?? ''));
    $odeme_turu   = trim($_POST['odeme_turu'] ?? ''); // sabit select değeri, turkce_upper uygulanmıyor

    if (!$cari_id) {
        echo json_encode(['success' => false, 'message' => 'Cari seçimi zorunludur!'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($hareket_turu === '') {
        echo json_encode(['success' => false, 'message' => 'Hareket türü zorunludur!'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($islem_turu === '') {
        echo json_encode(['success' => false, 'message' => 'İşlem türü zorunludur!'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($tutar <= 0) {
        echo json_encode(['success' => false, 'message' => "Tutar 0'dan büyük olmalıdır!"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // NOT: "TARİH" alanı sadece tarih seçtiriyor (saat yok) - saat kısmını
    // boş bırakmak yerine gerçek işlem anının saatini (şu anki saat) ekliyoruz,
    // aksi halde kayıt gece yarısı (00:00:00) olmuş gibi görünürdü (Efe'nin
    // Hızlı İşlem'de bulduğu aynı hata sınıfı - bkz. hizli_islem_yap.php'deki
    // aynı düzeltme).
    if ($tarih_str !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tarih_str)) {
        $tarih = $tarih_str . ' ' . date('H:i:s');
    } else {
        $tarih = $tarih_str !== '' ? $tarih_str : date('Y-m-d H:i:s');
    }

    $pdo->beginTransaction();

    // Cari bakiyesini oku
    $stmt = $pdo->prepare('SELECT bakiye FROM cariler WHERE id = ?');
    $stmt->execute([$cari_id]);
    $cariRow = $stmt->fetch();
    $cari_bakiye_oncesi = $cariRow ? (float)$cariRow['bakiye'] : 0;

    // Hesap bakiyesini oku (varsa)
    $hesap_bakiye_oncesi = 0;
    if ($hesap_id) {
        $stmt = $pdo->prepare('SELECT bakiye FROM hesaplar WHERE id = ?');
        $stmt->execute([$hesap_id]);
        $hesapRow = $stmt->fetch();
        $hesap_bakiye_oncesi = $hesapRow ? (float)$hesapRow['bakiye'] : 0;
    }

    $cari_bakiye_sonrasi = $cari_bakiye_oncesi;
    $hesap_bakiye_sonrasi = $hesap_bakiye_oncesi;

    if ($cariRow) {
        $cari_bakiye_sonrasi = $islem_turu === 'GİRİŞ'
            ? $cari_bakiye_oncesi + $tutar
            : $cari_bakiye_oncesi - $tutar;

        $update = $pdo->prepare('UPDATE cariler SET bakiye = ? WHERE id = ?');
        $update->execute([$cari_bakiye_sonrasi, $cari_id]);
    }

    if ($hesap_id) {
        $hesap_bakiye_sonrasi = $islem_turu === 'GİRİŞ'
            ? $hesap_bakiye_oncesi + $tutar
            : $hesap_bakiye_oncesi - $tutar;

        $update = $pdo->prepare('UPDATE hesaplar SET bakiye = ? WHERE id = ?');
        $update->execute([$hesap_bakiye_sonrasi, $hesap_id]);
    }

    $insert = $pdo->prepare(
        'INSERT INTO hesap_hareketleri
            (cari_id, hesap_id, hareket_no, hareket_tarihi, islem_turu, hareket_turu, tutar,
             para_birimi, aciklama, referans_no, ilgili_kisi, odeme_turu, tarih,
             cari_bakiye_oncesi, cari_bakiye_sonrasi, hesap_bakiye_oncesi, hesap_bakiye_sonrasi, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
    );
    $insert->execute([
        $cari_id, $hesap_id, generate_hareket_no(), $tarih, $islem_turu, $hareket_turu, $tutar,
        'TRY', $aciklama, $referans_no, $ilgili_kisi, $odeme_turu, $tarih,
        $cari_bakiye_oncesi, $cari_bakiye_sonrasi, $hesap_id ? $hesap_bakiye_oncesi : null, $hesap_id ? $hesap_bakiye_sonrasi : null,
    ]);

    $pdo->commit();

    echo json_encode([
        'success'     => true,
        'message'     => 'Hareket kaydedildi!',
        'cari_bakiye' => $cari_bakiye_sonrasi,
        'hesap_bakiye'=> $hesap_bakiye_sonrasi,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
