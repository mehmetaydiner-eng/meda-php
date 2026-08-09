<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/numara_manager.php';
require_login();
require_csrf_json();

header('Content-Type: application/json; charset=utf-8');

try {
    $fatura_no          = trim($_POST['fatura_no'] ?? '');
    $fatura_tarihi_str  = trim($_POST['fatura_tarihi'] ?? '');
    $fatura_tipi        = mb_strtoupper(trim($_POST['fatura_tipi'] ?? 'SATIŞ'), 'UTF-8');
    $fatura_senaryo     = mb_strtoupper(trim($_POST['fatura_senaryo'] ?? 'TİCARİ'), 'UTF-8');
    $fatura_ettn        = trim($_POST['fatura_ettn'] ?? '') ?: strtoupper(bin2hex(random_bytes(16)));
    $para_birimi        = trim($_POST['para_birimi'] ?? 'TL');
    $aciklama           = trim($_POST['aciklama'] ?? '');
    $fatura_id_existing = safe_int($_POST['fatura_id'] ?? null, 0) ?: null;

    $alici_unvan  = turkce_upper(trim($_POST['alici_unvan'] ?? ''));
    $alici_adres  = trim($_POST['alici_adres'] ?? '');
    $alici_vd     = trim($_POST['alici_vd'] ?? '');
    $alici_vkn    = trim($_POST['alici_vkn'] ?? '');
    $alici_tel    = trim($_POST['alici_tel'] ?? '');
    $alici_email  = trim($_POST['alici_email'] ?? '');

    if ($alici_unvan === '') {
        echo json_encode(['success' => false, 'message' => 'Alıcı bilgileri eksik!'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($fatura_no === '') {
        $fatura_no = generate_fatura_no_nm($pdo);
    }

    // Aynı fatura numarası başka bir faturada kullanılıyor mu? (kendisi hariç)
    $stmt = $pdo->prepare('SELECT id FROM faturalar WHERE fatura_no = ?');
    $stmt->execute([$fatura_no]);
    $existing = $stmt->fetch();
    if ($existing && (!$fatura_id_existing || (int)$existing['id'] !== $fatura_id_existing)) {
        echo json_encode([
            'success' => false,
            'message' => 'Bu fatura numarası zaten kullanılıyor! Mevcut Fatura: ' . $fatura_no,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $fatura_tarihi = $fatura_tarihi_str !== '' ? $fatura_tarihi_str . ' ' . date('H:i:s') : date('Y-m-d H:i:s');

    $pdo->beginTransaction();

    // Cariyi bul ya da oluştur (Flask: Cari.query.filter_by(unvan=...).first())
    $stmt = $pdo->prepare('SELECT id FROM cariler WHERE unvan = ?');
    $stmt->execute([$alici_unvan]);
    $cariRow = $stmt->fetch();

    if ($cariRow) {
        $cari_id = (int)$cariRow['id'];
    } else {
        $insertCari = $pdo->prepare(
            'INSERT INTO cariler (unvan, adres, vergi_dairesi, vergi_no, telefon, email, cari_turu, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
        );
        $insertCari->execute([$alici_unvan, $alici_adres, $alici_vd, $alici_vkn, $alici_tel, $alici_email, 'MÜŞTERİ']);
        $cari_id = (int)$pdo->lastInsertId();
    }

    // ===== DÜZENLEME Mİ, YENİ KAYIT MI? =====
    // NOT: Orijinal Flask kodu fatura_id gönderilse bile HER ZAMAN yeni bir
    // Fatura kaydı oluşturuyordu (güncelleme mantığı hiç yoktu) - "düzenle"
    // ekranından kaydetmek aslında yeni bir fatura oluşturuyordu ve eskisi
    // veritabanında yetim (kullanılmayan) bir kayıt olarak kalıyordu. Burada
    // bu düzeltildi: fatura_id gönderilmişse VE o fatura gerçekten
    // mevcutsa, yeni kayıt oluşturmak yerine GERÇEK BİR UPDATE yapılıyor
    // (detay kalemleri de silinip yeniden ekleniyor - teknik_servis_duzenle.php'de
    // kullanılan aynı "sil ve yeniden ekle" deseni).
    $mevcutFatura = null;
    if ($fatura_id_existing) {
        $stmt = $pdo->prepare('SELECT id FROM faturalar WHERE id = ?');
        $stmt->execute([$fatura_id_existing]);
        $mevcutFatura = $stmt->fetch();
    }

    if ($mevcutFatura) {
        // ---- GÜNCELLEME ----
        $updateFatura = $pdo->prepare(
            'UPDATE faturalar SET
                fatura_no=?, fatura_tarihi=?, cari_id=?, fatura_turu=?, fatura_tipi=?, fatura_senaryosu=?,
                para_birimi=?, aciklama=?, gib_uuid=?, updated_at=datetime(\'now\',\'localtime\')
             WHERE id=?'
        );
        $updateFatura->execute([
            $fatura_no, $fatura_tarihi, $cari_id, $fatura_tipi, 'E-FATURA', $fatura_senaryo,
            $para_birimi, $aciklama, $fatura_ettn, $fatura_id_existing,
        ]);
        $belge_id = $fatura_id_existing;

        // Mevcut kalemleri temizle - yenileri birazdan eklenecek
        $pdo->prepare('DELETE FROM fatura_detaylari WHERE fatura_id = ?')->execute([$belge_id]);
    } else {
        // ---- YENİ KAYIT ----
        $insertFatura = $pdo->prepare(
            'INSERT INTO faturalar
                (fatura_no, fatura_tarihi, cari_id, fatura_turu, fatura_tipi, fatura_senaryosu,
                 durum, para_birimi, aciklama, gib_uuid, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'), datetime(\'now\',\'localtime\'))'
        );
        $insertFatura->execute([
            $fatura_no, $fatura_tarihi, $cari_id, $fatura_tipi, 'E-FATURA', $fatura_senaryo,
            'OLUŞTURULDU', $para_birimi, $aciklama, $fatura_ettn,
        ]);
        $belge_id = (int)$pdo->lastInsertId();
    }
    $fatura_id = $belge_id;

    $urun_adi_list         = $_POST['urun_adi'] ?? [];
    $miktar_list           = $_POST['miktar'] ?? [];
    $fiyat_list            = $_POST['fiyat'] ?? [];
    $iskonto_list          = $_POST['iskonto'] ?? [];
    $iskonto_nedeni_list   = $_POST['iskonto_nedeni'] ?? [];
    $kdv_list              = $_POST['kdv'] ?? [];

    $ara_toplam = 0.0;
    $toplam_iskonto = 0.0;
    $toplam_kdv = 0.0;

    for ($i = 0; $i < count($urun_adi_list); $i++) {
        if (trim($urun_adi_list[$i] ?? '') === '') continue;

        $urun_adi = turkce_upper(trim($urun_adi_list[$i]));
        $miktar = safe_float($miktar_list[$i] ?? 1, 1);
        $birim_fiyat = safe_float($fiyat_list[$i] ?? 0, 0);
        $iskonto_orani = safe_float($iskonto_list[$i] ?? 0, 0);
        $iskonto_nedeni = $iskonto_nedeni_list[$i] ?? '';
        $kdv_orani = safe_float($kdv_list[$i] ?? 20, 20);

        // Ürünü bul (ad ya da koda göre), yoksa otomatik oluştur
        $stmt = $pdo->prepare('SELECT * FROM urunler WHERE urun_adi = ? OR urun_kodu = ? LIMIT 1');
        $stmt->execute([$urun_adi, $urun_adi]);
        $urun = $stmt->fetch();

        if (!$urun) {
            $yeniKod = 'PR-' . date('YmdHis') . '-' . random_int(1000, 9999) . '-' . $i; // çakışmayı önlemek için rastgele bileşen eklendi
            $insertUrun = $pdo->prepare(
                'INSERT INTO urunler (urun_kodu, urun_adi, urun_tipi, birim, alis_fiyati, satis_fiyati, stok_miktari, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'), datetime(\'now\',\'localtime\'))'
            );
            $insertUrun->execute([$yeniKod, $urun_adi, 'SIFIR', 'ADET', $birim_fiyat * 0.8, $birim_fiyat, 0]);
            $urun_id = (int)$pdo->lastInsertId();
            $urun_kodu = $yeniKod;
            $barkod = null;
        } else {
            $urun_id = (int)$urun['id'];
            $urun_kodu = $urun['urun_kodu'];
            $barkod = $urun['barkod'];
        }

        $satir_toplam = $miktar * $birim_fiyat;
        $iskonto_tutari = $satir_toplam * ($iskonto_orani / 100);
        $iskonto_sonrasi = $satir_toplam - $iskonto_tutari;
        $kdv_tutari = $iskonto_sonrasi * ($kdv_orani / 100);
        $genel_satir_toplam = $iskonto_sonrasi + $kdv_tutari;

        $ara_toplam += $satir_toplam;
        $toplam_iskonto += $iskonto_tutari;
        $toplam_kdv += $kdv_tutari;

        $insertDetay = $pdo->prepare(
            'INSERT INTO fatura_detaylari
                (fatura_id, urun_id, urun_adi, urun_kodu, barkod, birim, miktar, birim_fiyati,
                 iskonto, iskonto_tutari, vergi_orani, vergi_tutari, ara_toplam, toplam_tutar, aciklama, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
        );
        $insertDetay->execute([
            $fatura_id, $urun_id, $urun_adi, $urun_kodu, $barkod, 'ADET', $miktar, $birim_fiyat,
            $iskonto_orani, $iskonto_tutari, $kdv_orani, $kdv_tutari, $satir_toplam, $genel_satir_toplam, $iskonto_nedeni,
        ]);
    }

    $iskonto_orani_toplam = $ara_toplam > 0 ? ($toplam_iskonto / $ara_toplam * 100) : 0;
    $genel_toplam = $ara_toplam - $toplam_iskonto + $toplam_kdv;

    // ===== ÖDEME DAĞILIMI (çoklu kanal/kasa) =====
    // NOT: Bu sayfa (Fatura Oluştur) önceden HİÇ bir mali harekete yol
    // açmıyordu - sadece bir belge (fatura) kaydediyordu, ne cari bakiyesi
    // ne de bir kasa bakiyesi hiç etkilenmiyordu. Efe'nin isteği üzerine
    // (15 Temmuz 2026) burada da Hızlı İşlem/Makbuz Oluştur'daki ile aynı
    // çoklu ödeme (nakit + havale + kredi kartı gibi bölünebilen) desteği
    // eklendi. Bu, Fatura Oluştur'un artık gerçek bir mali hareket
    // oluşturan bir akış haline geldiği anlamına gelir - bilinçli bir
    // tasarım kararı, diğer iki satış ekranıyla tutarlılık için.
    $odeme_turleri_raw     = $_POST['odeme_turu'] ?? [];
    $odeme_hesap_idler_raw = $_POST['odeme_hesap_id'] ?? [];
    $odeme_tutarlar_raw    = $_POST['odeme_tutar'] ?? [];

    $odemeSatirlari = [];
    foreach ($odeme_tutarlar_raw as $i => $tutarRaw) {
        $tutarBu = safe_float($tutarRaw);
        if ($tutarBu <= 0) continue;
        $odemeSatirlari[] = [
            'turu'     => trim($odeme_turleri_raw[$i] ?? 'NAKİT'),
            'hesap_id' => safe_int($odeme_hesap_idler_raw[$i] ?? null, 0) ?: null,
            'tutar'    => $tutarBu,
        ];
    }
    $odemeOzetTurleri = array_values(array_unique(array_column($odemeSatirlari, 'turu')));
    $odeme_turu_ozet = $odemeOzetTurleri ? implode(' + ', $odemeOzetTurleri) : 'VERESİYE';

    $updateFatura = $pdo->prepare(
        'UPDATE faturalar SET ara_toplam=?, iskonto=?, iskonto_tutari=?, vergi_orani=20, vergi_tutari=?, genel_toplam=?, odeme_turu=?, updated_at=datetime(\'now\',\'localtime\') WHERE id=?'
    );
    $updateFatura->execute([$ara_toplam, $iskonto_orani_toplam, $toplam_iskonto, $toplam_kdv, $genel_toplam, $odeme_turu_ozet, $fatura_id]);

    // Cari bakiyesini güncelle (SATIŞ azaltır, ALIŞ artırır - Hızlı İşlem'deki
    // aynı kural). Fatura düzenleme (UPDATE) durumunda burada TEKRAR
    // uygulamıyoruz - sadece yeni oluşturulan bir faturada bakiye etkileniyor,
    // aksi halde aynı faturayı birden çok kez kaydetmek bakiyeyi yanlış
    // katlardı.
    if (!$mevcutFatura) {
        $stmt = $pdo->prepare('SELECT * FROM cariler WHERE id = ?');
        $stmt->execute([$cari_id]);
        $cariRowBakiye = $stmt->fetch();

        // NOT: Önceden cari bakiyesi HER ZAMAN tam fatura tutarı kadar
        // etkileniyordu - ödeme dağılımında gerçekte ne kadar tahsil/ödeme
        // yapıldığına bakılmaksızın. Artık sadece ÖDENMEYEN (kalan) tutar
        // cari bakiyesini etkiliyor - tam ödenen bir faturada cari bakiyesi
        // hiç değişmez (ne borç ne alacak oluşur).
        // NOT (19 Temmuz 2026): "VERESİYE" ödeme türü satırları gerçek bir
        // ödeme değildir - ödenen toplama dahil edilmiyor (bkz.
        // hizli_islem_yap.php'deki aynı not).
        $toplamOdenenTutar = array_sum(array_column(
            array_filter($odemeSatirlari, fn($os) => $os['turu'] !== 'VERESİYE'),
            'tutar'
        ));
        $kalanTutar = $genel_toplam - $toplamOdenenTutar;

        if ($cariRowBakiye) {
            $cariOncesi = (float)$cariRowBakiye['bakiye'];
            $cariSonrasi = $fatura_tipi === 'ALIŞ' ? ($cariOncesi + $kalanTutar) : ($cariOncesi - $kalanTutar);
            $pdo->prepare('UPDATE cariler SET bakiye = ? WHERE id = ?')->execute([$cariSonrasi, $cari_id]);
        }

        // Her ödeme satırı için: ilgili kasanın bakiyesini güncelle + gerçek
        // bir hesap hareketi (defter) satırı oluştur. Efe'nin isteği üzerine
        // (16 Temmuz 2026), etiket artık "SATIŞ/ALIŞ" değil, gerçek para
        // akışı yönünü yansıtan "TAHSİLAT/ÖDEME" oluyor.
        $islemTuruHareket = $fatura_tipi === 'ALIŞ' ? 'ÇIKIŞ' : 'GİRİŞ';
        $odemeHareketEtiketi = $fatura_tipi === 'ALIŞ' ? 'ÖDEME' : 'TAHSİLAT';
        $insertHareket = $pdo->prepare(
            'INSERT INTO hesap_hareketleri
                (hesap_id, cari_id, hareket_no, hareket_tarihi, islem_turu, hareket_turu, tutar,
                 para_birimi, aciklama, referans_no, odeme_turu, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
        );

        foreach ($odemeSatirlari as $odemeSatiri) {
            if ($odemeSatiri['turu'] === 'VERESİYE') continue; // veresiye satırları hiçbir kasaya dokunmaz
            if (!$odemeSatiri['hesap_id']) continue; // kasa seçilmediyse sadece "ödendi" bilgisi kalır

            $stmt = $pdo->prepare('SELECT * FROM hesaplar WHERE id = ?');
            $stmt->execute([$odemeSatiri['hesap_id']]);
            $buHesap = $stmt->fetch();
            if (!$buHesap) continue;

            $buOncesi = (float)$buHesap['bakiye'];
            $buSonrasi = $fatura_tipi === 'ALIŞ' ? ($buOncesi - $odemeSatiri['tutar']) : ($buOncesi + $odemeSatiri['tutar']);
            $pdo->prepare('UPDATE hesaplar SET bakiye = ? WHERE id = ?')->execute([$buSonrasi, $odemeSatiri['hesap_id']]);

            $insertHareket->execute([
                $odemeSatiri['hesap_id'], $cari_id, generate_hareket_no(), date('Y-m-d H:i:s'), $islemTuruHareket,
                'FATURA_' . $odemeHareketEtiketi, $odemeSatiri['tutar'], $para_birimi,
                "Fatura {$fatura_tipi} - {$fatura_no} - {$odemeSatiri['turu']}", $fatura_no, $odemeSatiri['turu'],
            ]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success'   => true,
        'message'   => 'Fatura başarıyla kaydedildi!',
        'fatura_id' => $fatura_id,
        'fatura_no' => $fatura_no,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
