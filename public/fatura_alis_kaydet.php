<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/numara_manager.php';
require_login();
require_csrf_json();

header('Content-Type: application/json; charset=utf-8');

try {
    $cari_id      = safe_int($_POST['cari_id'] ?? null, 0) ?: null;
    $fatura_turu  = mb_strtoupper(trim($_POST['fatura_turu'] ?? 'ALIŞ'), 'UTF-8');
    $odeme_turu   = mb_strtoupper(trim($_POST['odeme_turu'] ?? 'NAKİT'), 'UTF-8');
    $para_birimi  = trim($_POST['para_birimi'] ?? 'TRY');
    $aciklama     = trim($_POST['aciklama'] ?? '');

    $urun_ids   = $_POST['urun_ids'] ?? [];
    $miktarlar  = $_POST['miktarlar'] ?? [];
    $fiyatlar   = $_POST['fiyatlar'] ?? [];
    $iskontolar = $_POST['iskontolar'] ?? [];

    if (!$cari_id) {
        echo json_encode(['success' => false, 'message' => 'Lütfen bir tedarikçi seçin!'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (empty($urun_ids)) {
        echo json_encode(['success' => false, 'message' => 'Lütfen en az bir ürün ekleyin!'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo->beginTransaction();

    $fatura_no = generate_fatura_no_nm($pdo);

    $insertFatura = $pdo->prepare(
        'INSERT INTO faturalar
            (fatura_no, fatura_tarihi, cari_id, fatura_turu, fatura_tipi, fatura_senaryosu,
             durum, odeme_turu, para_birimi, aciklama, created_at, updated_at)
         VALUES (?, datetime(\'now\',\'localtime\'), ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'), datetime(\'now\',\'localtime\'))'
    );
    $insertFatura->execute([
        $fatura_no, $cari_id, 'ALIŞ', 'E-FATURA', 'TEMEL', 'OLUŞTURULDU', $odeme_turu, $para_birimi, $aciklama,
    ]);
    $fatura_id = (int)$pdo->lastInsertId();

    $ara_toplam = 0.0;
    $toplam_iskonto = 0.0;

    for ($i = 0; $i < count($urun_ids); $i++) {
        if (empty($urun_ids[$i])) continue;

        $stmt = $pdo->prepare('SELECT * FROM urunler WHERE id = ?');
        $stmt->execute([$urun_ids[$i]]);
        $urun = $stmt->fetch();
        if (!$urun) continue;

        $miktar = safe_float($miktarlar[$i] ?? 1, 1);
        $birim_fiyat = safe_float($fiyatlar[$i] ?? $urun['alis_fiyati'], (float)$urun['alis_fiyati']);
        $iskonto_orani = safe_float($iskontolar[$i] ?? 0, 0);

        $satir_toplam = $miktar * $birim_fiyat;
        $iskonto_tutari = $satir_toplam * ($iskonto_orani / 100);
        $iskonto_sonrasi = $satir_toplam - $iskonto_tutari;
        $kdv_tutari = $iskonto_sonrasi * 0.20;
        $genel_satir_toplam = $iskonto_sonrasi + $kdv_tutari;

        $ara_toplam += $satir_toplam;
        $toplam_iskonto += $iskonto_tutari;

        $insertDetay = $pdo->prepare(
            'INSERT INTO fatura_detaylari
                (fatura_id, urun_id, urun_adi, urun_kodu, barkod, birim, miktar, birim_fiyati,
                 iskonto, iskonto_tutari, vergi_orani, vergi_tutari, ara_toplam, toplam_tutar, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
        );
        $insertDetay->execute([
            $fatura_id, $urun['id'], $urun['urun_adi'], $urun['urun_kodu'], $urun['barkod'],
            $urun['birim'] ?: 'ADET', $miktar, $birim_fiyat, $iskonto_orani, $iskonto_tutari,
            20, $kdv_tutari, $satir_toplam, $genel_satir_toplam,
        ]);

        // Alış = stok artışı + alış fiyatı güncellenir (Flask ile birebir aynı)
        $stokOncesi = (float)$urun['stok_miktari'];
        $yeniStok = $stokOncesi + $miktar;
        $updateUrun = $pdo->prepare('UPDATE urunler SET stok_miktari = ?, alis_fiyati = ? WHERE id = ?');
        $updateUrun->execute([$yeniStok, $birim_fiyat, $urun['id']]);

        stok_hareketi_ekle(
            $pdo, (int)$urun['id'], 'ALIŞ', $miktar,
            $stokOncesi, $yeniStok, $fatura_no, "Alış Faturası - {$fatura_no}", $cari_id
        );
    }

    $iskonto_orani_toplam = $ara_toplam > 0 ? ($toplam_iskonto / $ara_toplam * 100) : 0;
    $vergi_tutari = ($ara_toplam - $toplam_iskonto) * 0.20;
    $genel_toplam = $ara_toplam - $toplam_iskonto + $vergi_tutari;

    $updateFatura = $pdo->prepare(
        'UPDATE faturalar SET ara_toplam=?, iskonto=?, iskonto_tutari=?, vergi_orani=20, vergi_tutari=?, genel_toplam=?, updated_at=datetime(\'now\',\'localtime\') WHERE id=?'
    );
    $updateFatura->execute([$ara_toplam, $iskonto_orani_toplam, $toplam_iskonto, $vergi_tutari, $genel_toplam, $fatura_id]);

    // NOT: Orijinal Flask kodu burada cari.bakiye'yi HİÇ güncellemiyordu
    // (Hızlı İşlem'in aksine). Bu tutarsızlık orijinalde de vardı, aynen
    // korundu - README'deki "Açık Konular" bölümüne not düşüldü.

    $pdo->commit();

    echo json_encode([
        'success'   => true,
        'message'   => 'Alış faturası başarıyla kaydedildi!',
        'fatura_id' => $fatura_id,
        'fatura_no' => $fatura_no,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
