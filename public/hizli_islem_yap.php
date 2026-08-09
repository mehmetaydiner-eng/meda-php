<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/numara_manager.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/hizli_islem.php');
    exit;
}

require_csrf(BASE_URL . '/hizli_islem.php');

try {
    // ===== 1. FORM VERİLERİNİ AL =====
    $islem_turu   = mb_strtoupper(trim($_POST['islem_turu'] ?? 'SATIS'), 'UTF-8');
    $belge_turu   = mb_strtoupper(trim($_POST['belge_turu'] ?? 'FAT'), 'UTF-8');
    $cari_id      = $_POST['cari_id'] ?? '';
    $aciklama     = trim($_POST['aciklama'] ?? '');
    $evrak_no     = mb_strtoupper(trim($_POST['evrak_no'] ?? ''), 'UTF-8');
    $evrak_tarihi = $_POST['evrak_tarihi'] ?? '';

    // ===== ÖDEME DAĞILIMI (çoklu kanal/kasa) =====
    // NOT: Önceden tek bir "Ödeme Kanalı" + tek bir "Hesap/Kasa" seçimi
    // vardı - tüm satış tutarı tek bir kasaya giriyordu. Artık bir satış
    // birden fazla kanaldan (nakit + havale + kredi kartı gibi), birden
    // fazla kasaya bölünerek ödenebiliyor. Ödenen toplam satış tutarından
    // az olabilir - kalan kısım otomatik olarak veresiye/borç sayılır
    // (cari bakiyesi zaten tam satış tutarı kadar etkileniyor, ayrıca
    // hiçbir kasaya girmemiş olan kısım için bir hesap hareketi oluşmaz).
    $odeme_turleri_raw    = $_POST['odeme_turu'] ?? [];
    $odeme_hesap_idler_raw = $_POST['odeme_hesap_id'] ?? [];
    $odeme_tutarlar_raw   = $_POST['odeme_tutar'] ?? [];

    $odemeSatirlari = [];
    foreach ($odeme_tutarlar_raw as $i => $tutarRaw) {
        $tutar = safe_float($tutarRaw);
        if ($tutar <= 0) continue;
        $odemeSatirlari[] = [
            'turu'     => mb_strtoupper(trim($odeme_turleri_raw[$i] ?? 'NAKİT'), 'UTF-8'),
            'hesap_id' => safe_int($odeme_hesap_idler_raw[$i] ?? null, 0) ?: null,
            'tutar'    => $tutar,
        ];
    }

    // Makbuz/fatura kaydındaki tek "odeme_turu" alanı için özet metin (örn.
    // "NAKİT + HAVALE") ve o kaydın "hesap_id" alanı için ilk geçerli kasa
    // (asıl kasa/tutar detayları her ödeme için ayrı ayrı hesap_hareketleri
    // satırlarında tutuluyor - bkz. aşağısı).
    $odemeOzetTurleri = array_values(array_unique(array_column($odemeSatirlari, 'turu')));
    $odeme_kanali = $odemeOzetTurleri ? implode(' + ', $odemeOzetTurleri) : 'VERESİYE';
    $ilkOdemeHesapId = null;
    foreach ($odemeSatirlari as $os) {
        if ($os['hesap_id']) { $ilkOdemeHesapId = $os['hesap_id']; break; }
    }
    $hesap = null;
    if ($ilkOdemeHesapId) {
        $stmt = $pdo->prepare('SELECT * FROM hesaplar WHERE id = ?');
        $stmt->execute([$ilkOdemeHesapId]);
        $hesap = $stmt->fetch();
    }

    // ===== 2. CARİ KONTROLÜ =====
    if ($cari_id === '' || $cari_id === 'null' || $cari_id === '0') {
        flash_set('Lütfen geçerli bir cari hesap seçin!', 'danger');
        header('Location: ' . BASE_URL . '/hizli_islem.php');
        exit;
    }
    $cari_id_int = safe_int($cari_id, 0);
    if (!$cari_id_int) {
        flash_set('Geçersiz cari seçimi!', 'danger');
        header('Location: ' . BASE_URL . '/hizli_islem.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM cariler WHERE id = ?');
    $stmt->execute([$cari_id_int]);
    $cari = $stmt->fetch();
    if (!$cari) {
        flash_set('Seçilen cari hesap bulunamadı!', 'danger');
        header('Location: ' . BASE_URL . '/hizli_islem.php');
        exit;
    }

    // ===== 3. TARİH KONTROLÜ =====
    // NOT: Önceden burada sadece tarih (evrak_tarihi) girildiğinde saat
    // KASITSIZ OLARAK "00:00:00"a sabitleniyordu - bu yüzden Hesap
    // Hareketleri/Kasa listelerinde her işlem gece yarısı olmuş gibi
    // görünüyordu (Efe'nin bulduğu gerçek bir hata). Artık kullanıcının
    // seçtiği TARİH korunuyor ama SAAT kısmı gerçek işlem anının saatini
    // (şu anki saat) alıyor - böylece aynı gün içindeki işlemlerin
    // kronolojik sırası da doğru kalıyor.
    if ($evrak_tarihi && preg_match('/^\d{4}-\d{2}-\d{2}$/', $evrak_tarihi)) {
        $tarih_obj = $evrak_tarihi . ' ' . date('H:i:s');
    } else {
        $tarih_obj = date('Y-m-d H:i:s');
    }

    // ===== 4. SEPET VERİLERİNİ AL =====
    $urun_ids   = $_POST['urun_ids'] ?? [];
    $miktarlar  = $_POST['miktarlar'] ?? [];
    $fiyatlar   = $_POST['fiyatlar'] ?? [];
    $iskontolar = $_POST['iskontolar'] ?? [];
    $kdvler     = $_POST['kdvler'] ?? [];

    if (empty($urun_ids)) {
        flash_set('Lütfen en az bir ürün ekleyin!', 'danger');
        header('Location: ' . BASE_URL . '/hizli_islem.php');
        exit;
    }

    // ===== 5. EVRAK NO KONTROLÜ =====
    if ($islem_turu === 'ALIS') {
        if ($evrak_no === '') {
            flash_set('Alış işleminde tedarikçi fatura numarası girmek ZORUNLUDUR!', 'danger');
            header('Location: ' . BASE_URL . '/hizli_islem.php');
            exit;
        }
        $stmt = $pdo->prepare('SELECT id FROM faturalar WHERE fatura_no = ?');
        $stmt->execute([$evrak_no]);
        if ($stmt->fetch()) {
            flash_set('Bu fatura numarası zaten kullanılıyor: ' . $evrak_no, 'danger');
            header('Location: ' . BASE_URL . '/hizli_islem.php');
            exit;
        }
        $stmt = $pdo->prepare('SELECT id FROM makbuzlar WHERE makbuz_no = ?');
        $stmt->execute([$evrak_no]);
        if ($stmt->fetch()) {
            flash_set('Bu makbuz numarası zaten kullanılıyor: ' . $evrak_no, 'danger');
            header('Location: ' . BASE_URL . '/hizli_islem.php');
            exit;
        }
    } elseif (in_array($islem_turu, ['SATIS', 'IADE'], true)) {
        if ($evrak_no !== '') {
            $stmt = $pdo->prepare('SELECT id FROM faturalar WHERE fatura_no = ?');
            $stmt->execute([$evrak_no]);
            if ($stmt->fetch()) {
                flash_set('Bu fatura numarası zaten kullanılıyor: ' . $evrak_no, 'danger');
                header('Location: ' . BASE_URL . '/hizli_islem.php');
                exit;
            }
            $stmt = $pdo->prepare('SELECT id FROM makbuzlar WHERE makbuz_no = ?');
            $stmt->execute([$evrak_no]);
            if ($stmt->fetch()) {
                flash_set('Bu makbuz numarası zaten kullanılıyor: ' . $evrak_no, 'danger');
                header('Location: ' . BASE_URL . '/hizli_islem.php');
                exit;
            }
        }
    }

    $pdo->beginTransaction();

    // ===== 6. BELGE OLUŞTUR =====
    $isMakbuz = in_array($belge_turu, ['STM', 'MAKBUZ'], true);

    if ($isMakbuz) {
        $makbuz_turu = ['SATIS' => 'SATIS', 'ALIS' => 'ALIS', 'IADE' => 'IADE'][$islem_turu] ?? 'SATIS';

        if ($islem_turu === 'ALIS') {
            $makbuz_no = $evrak_no;
        } else {
            $makbuz_no = generate_makbuz_no_nm($pdo, $makbuz_turu);
            if ($evrak_no !== '') $makbuz_no = $evrak_no;
        }

        $insert = $pdo->prepare(
            'INSERT INTO makbuzlar
                (makbuz_no, makbuz_tarihi, makbuz_turu, cari_id, hesap_id, para_birimi,
                 odeme_turu, aciklama, durum, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'), datetime(\'now\',\'localtime\'))'
        );
        $insert->execute([
            $makbuz_no, $tarih_obj, $makbuz_turu, $cari_id_int, $hesap['id'] ?? null,
            'TRY', $odeme_kanali, $aciklama, 'OLUŞTURULDU', current_user()['username'] ?? '',
        ]);
        $belge_id = (int)$pdo->lastInsertId();
        $belge_tablo = 'makbuzlar';
        $detay_tablo = 'makbuz_detaylari';
        $detay_fk = 'makbuz_id';
        $has_vergi = false;
        $belge_no = $makbuz_no;
    } else {
        $fatura_turu = ['SATIS' => 'SATIŞ', 'ALIS' => 'ALIŞ', 'IADE' => 'İADE'][$islem_turu] ?? 'SATIŞ';
        $fatura_tipi = $belge_turu === 'FAT' ? 'E-FATURA' : 'E-ARŞİV';
        $fatura_senaryosu = $belge_turu === 'EAR' ? 'E-ARŞİV' : 'TEMEL';

        if ($islem_turu === 'ALIS') {
            $fatura_no = $evrak_no;
        } else {
            $fatura_no = $belge_turu === 'EAR' ? generate_earsiv_no_nm($pdo) : generate_fatura_no_nm($pdo);
            if ($evrak_no !== '') $fatura_no = $evrak_no;
        }

        $insert = $pdo->prepare(
            'INSERT INTO faturalar
                (fatura_no, fatura_tarihi, cari_id, fatura_turu, fatura_tipi, fatura_senaryosu,
                 odeme_turu, para_birimi, durum, aciklama, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'), datetime(\'now\',\'localtime\'))'
        );
        $insert->execute([
            $fatura_no, $tarih_obj, $cari_id_int, $fatura_turu, $fatura_tipi, $fatura_senaryosu,
            $odeme_kanali, 'TRY', 'OLUŞTURULDU', $aciklama,
        ]);
        $belge_id = (int)$pdo->lastInsertId();
        $belge_tablo = 'faturalar';
        $detay_tablo = 'fatura_detaylari';
        $detay_fk = 'fatura_id';
        $belge_no = $fatura_no;
        $has_vergi = true;
    }

    // ===== 7. SEPET KALEMLERİNİ EKLE =====
    $hareketTuruAdi = ['SATIS' => 'SATIŞ', 'ALIS' => 'ALIŞ', 'IADE' => 'İADE'][$islem_turu] ?? 'SATIŞ';
    $ara_toplam = 0.0;
    $toplam_iskonto = 0.0;
    $toplam_kdv = 0.0;

    for ($i = 0; $i < count($urun_ids); $i++) {
        $urunId = safe_int($urun_ids[$i] ?? null);
        if (!$urunId) continue;

        $stmt = $pdo->prepare('SELECT * FROM urunler WHERE id = ?');
        $stmt->execute([$urunId]);
        $urun = $stmt->fetch();
        if (!$urun) continue;

        $miktar = safe_float($miktarlar[$i] ?? 1, 1);
        $birim_fiyat = safe_float($fiyatlar[$i] ?? 0, 0);
        $iskonto = safe_float($iskontolar[$i] ?? 0, 0);
        $kdv_orani = safe_float($kdvler[$i] ?? 20, 20);

        $satir_toplam = $miktar * $birim_fiyat;
        $iskonto_tutari = $satir_toplam * ($iskonto / 100);
        $iskonto_sonrasi = $satir_toplam - $iskonto_tutari;
        $kdv_tutari = $iskonto_sonrasi * ($kdv_orani / 100);
        $genel_toplam = $iskonto_sonrasi + $kdv_tutari;

        $ara_toplam += $satir_toplam;
        $toplam_iskonto += $iskonto_tutari;
        $toplam_kdv += $kdv_tutari;

        if ($has_vergi) {
            $insertDetay = $pdo->prepare(
                "INSERT INTO {$detay_tablo}
                    ({$detay_fk}, urun_id, urun_adi, urun_kodu, barkod, birim, miktar,
                     birim_fiyati, iskonto, iskonto_tutari, vergi_orani, vergi_tutari,
                     ara_toplam, toplam_tutar, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now','localtime'))"
            );
            $insertDetay->execute([
                $belge_id, $urun['id'], $urun['urun_adi'], $urun['urun_kodu'], $urun['barkod'],
                $urun['birim'] ?: 'ADET', $miktar, $birim_fiyat, $iskonto, $iskonto_tutari,
                $kdv_orani, $kdv_tutari, $satir_toplam, $genel_toplam,
            ]);
        } else {
            $insertDetay = $pdo->prepare(
                "INSERT INTO {$detay_tablo}
                    ({$detay_fk}, urun_id, urun_adi, urun_kodu, barkod, birim, miktar,
                     birim_fiyati, iskonto, iskonto_tutari, ara_toplam, toplam_tutar, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now','localtime'))"
            );
            $insertDetay->execute([
                $belge_id, $urun['id'], $urun['urun_adi'], $urun['urun_kodu'], $urun['barkod'],
                $urun['birim'] ?: 'ADET', $miktar, $birim_fiyat, $iskonto, $iskonto_tutari,
                $satir_toplam, $genel_toplam,
            ]);
        }

        // ===== STOK GÜNCELLEME =====
        $stokOncesi = (float)$urun['stok_miktari'];
        if ($islem_turu === 'SATIS') {
            $yeniStok = $stokOncesi - $miktar;
            $hareketMiktar = -$miktar;
        } else { // ALIS veya IADE
            $yeniStok = $stokOncesi + $miktar;
            $hareketMiktar = $miktar;
        }
        $updateStok = $pdo->prepare('UPDATE urunler SET stok_miktari = ? WHERE id = ?');
        $updateStok->execute([$yeniStok, $urun['id']]);

        stok_hareketi_ekle(
            $pdo, (int)$urun['id'], $hareketTuruAdi, $hareketMiktar,
            $stokOncesi, $yeniStok, $belge_no, "Hızlı İşlem - {$belge_no}", $cari_id_int
        );
    }

    // ===== 8. TOPLAMLARI GÜNCELLE =====
    $iskonto_orani = $ara_toplam > 0 ? ($toplam_iskonto / $ara_toplam * 100) : 0;
    $genel_toplam_belge = $ara_toplam - $toplam_iskonto + $toplam_kdv;

    if ($has_vergi) {
        $updateBelge = $pdo->prepare(
            'UPDATE faturalar SET ara_toplam=?, iskonto=?, iskonto_tutari=?, vergi_orani=?, vergi_tutari=?, genel_toplam=?, updated_at=datetime(\'now\',\'localtime\') WHERE id=?'
        );
        $updateBelge->execute([$ara_toplam, $iskonto_orani, $toplam_iskonto, 20, $toplam_kdv, $genel_toplam_belge, $belge_id]);
    } else {
        $updateBelge = $pdo->prepare(
            'UPDATE makbuzlar SET ara_toplam=?, iskonto=?, iskonto_tutari=?, vergi_tutari=?, genel_toplam=?, updated_at=datetime(\'now\',\'localtime\') WHERE id=?'
        );
        $updateBelge->execute([$ara_toplam, $iskonto_orani, $toplam_iskonto, $toplam_kdv, $genel_toplam_belge, $belge_id]);
    }

    // ===== 9. CARİ BAKİYE GÜNCELLE =====
    // NOT: Önceden cari bakiyesi HER ZAMAN tam satış/alış tutarı kadar
    // etkileniyordu - ödeme yapılıp yapılmadığına bakılmaksızın. Bu, Efe'nin
    // bulduğu gerçek bir hatayı yaratıyordu: müşteri anında peşin (nakit/
    // kredi kartı) ödese bile cari hesabı sanki borçlanmış gibi görünüyordu.
    // Artık cari bakiyesi sadece ÖDENMEYEN (kalan) tutar kadar etkileniyor -
    // tam ödenen bir satışta cari bakiyesi hiç değişmez (borç da alacak da
    // oluşmaz), sadece kısmi/hiç ödenmeyen kısım borç/alacak olarak yansır.
    // NOT (19 Temmuz 2026): "VERESİYE" ödeme türü satırları GERÇEK bir
    // ödeme değildir - bilerek borca ayrılan kısmı işaretlemek için
    // kullanılıyor. Bu yüzden "ödenen toplam" hesabına DAHİL EDİLMİYOR
    // (aksi halde veresiye tutarı yanlışlıkla "ödendi" sayılıp cari
    // borcunu azaltırdı). Bu satırların hiçbir kasa hareketi de
    // oluşturmaması gerekiyor - bu zaten aşağıdaki döngüde hesap_id boş
    // olduğu için otomatik sağlanıyor (VERESİYE seçilince arayüz kasa
    // alanını temizliyor), ama ekstra güvenlik için de kontrol ediliyor.
    $toplamOdenenTutar = array_sum(array_column(
        array_filter($odemeSatirlari, fn($os) => $os['turu'] !== 'VERESİYE'),
        'tutar'
    ));
    $kalanTutar = $genel_toplam_belge - $toplamOdenenTutar;

    if ($islem_turu === 'SATIS') {
        $yeniCariBakiye = (float)$cari['bakiye'] - $kalanTutar;
    } else { // ALIS veya IADE
        $yeniCariBakiye = (float)$cari['bakiye'] + $kalanTutar;
    }
    $pdo->prepare('UPDATE cariler SET bakiye = ? WHERE id = ?')->execute([$yeniCariBakiye, $cari_id_int]);

    // ===== 10-11. HER ÖDEME SATIRI İÇİN: HESAP BAKİYESİNİ GÜNCELLE + DEFTER SATIRI OLUŞTUR =====
    // NOT: Bu adım orijinal Flask uygulamasında hiç yoktu - Hızlı İşlem
    // üzerinden yapılan satış/alış/iade işlemleri cari ve hesap bakiyesini
    // doğru güncelliyordu ama hesap_hareketleri tablosuna (defter görünümüne)
    // hiç satır düşmüyordu. Burada eklenen bu adımla artık her Hızlı İşlem
    // sonrasında gerçek bir defter satırı da oluşuyor.
    //
    // Efe'nin isteği üzerine (15 Temmuz 2026), bir satışın TEK bir kasaya
    // değil, BİRDEN FAZLA kasaya/kanala bölünerek ödenebilmesi eklendi -
    // bu yüzden artık tek bir hesap_hareketi değil, her ödeme satırı için
    // AYRI bir hesap_hareketi satırı oluşuyor (kendi tutarı ve kendi
    // kasasıyla). SATIŞ/İADE hesaba para girişi, ALIŞ hesaptan para çıkışı
    // anlamına gelir. Ödenmeyen (kalan) kısım için hiçbir kasa hareketi
    // oluşmaz - bu kısım otomatik olarak veresiye/borç sayılır (cari
    // bakiyesi zaten tam satış tutarı kadar güncellendi).
    $islemTuruHareket = $islem_turu === 'ALIS' ? 'ÇIKIŞ' : 'GİRİŞ';

    // NOT: Efe'nin isteği üzerine (16 Temmuz 2026) - ödeme hareketlerinin
    // etiketi artık "SATIŞ/ALIŞ" değil, gerçek para akışı yönünü yansıtan
    // "TAHSİLAT/ÖDEME" oluyor: SATIŞ/İADE'de müşteriden para tahsil
    // ediyoruz (TAHSİLAT), ALIŞ'ta tedarikçiye ödeme yapıyoruz (ÖDEME).
    // Stok hareketi etiketi ($hareketTuruAdi, yukarıda) hâlâ SATIŞ/ALIŞ/
    // İADE olarak kalıyor - o, stoğun yönünü anlatıyor, ödemenin değil.
    $odemeHareketEtiketi = $islem_turu === 'ALIS' ? 'ÖDEME' : 'TAHSİLAT';

    $insertHareket = $pdo->prepare(
        'INSERT INTO hesap_hareketleri
            (hesap_id, cari_id, hareket_no, hareket_tarihi, islem_turu, hareket_turu, tutar,
             para_birimi, aciklama, referans_no, odeme_turu, cari_bakiye_oncesi, cari_bakiye_sonrasi,
             hesap_bakiye_oncesi, hesap_bakiye_sonrasi, created_by, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
    );

    foreach ($odemeSatirlari as $odemeSatiri) {
        if ($odemeSatiri['turu'] === 'VERESİYE') continue; // veresiye satırları hiçbir kasaya dokunmaz
        if (!$odemeSatiri['hesap_id']) continue; // kasa seçilmediyse sadece "ödendi" bilgisi kalır, kasa hareketi oluşmaz

        $stmt = $pdo->prepare('SELECT * FROM hesaplar WHERE id = ?');
        $stmt->execute([$odemeSatiri['hesap_id']]);
        $buHesap = $stmt->fetch();
        if (!$buHesap) continue;

        $buOncesi = (float)$buHesap['bakiye'];
        $buSonrasi = $islem_turu === 'ALIS' ? ($buOncesi - $odemeSatiri['tutar']) : ($buOncesi + $odemeSatiri['tutar']);
        $pdo->prepare('UPDATE hesaplar SET bakiye = ? WHERE id = ?')->execute([$buSonrasi, $odemeSatiri['hesap_id']]);

        $insertHareket->execute([
            $odemeSatiri['hesap_id'], $cari_id_int, generate_hareket_no(), $tarih_obj, $islemTuruHareket,
            'HIZLI_' . $odemeHareketEtiketi, $odemeSatiri['tutar'], 'TRY',
            "Hızlı İşlem - {$hareketTuruAdi} ({$belge_no}) - {$odemeSatiri['turu']}", $belge_no, $odemeSatiri['turu'],
            (float)$cari['bakiye'], $yeniCariBakiye, $buOncesi, $buSonrasi,
            current_user()['username'] ?? '',
        ]);
    }

    $pdo->commit();

    // ===== 11. SONUÇ MESAJI =====
    $islem_adi = ['SATIS' => 'SATIŞ', 'ALIS' => 'ALIŞ', 'IADE' => 'İADE'][$islem_turu] ?? $islem_turu;
    $mesaj = "{$islem_adi} işlemi başarıyla tamamlandı!";

    if ($isMakbuz) {
        $stmt = $pdo->prepare('SELECT makbuz_no FROM makbuzlar WHERE id = ?');
        $stmt->execute([$belge_id]);
        $mesaj .= ' Makbuz No: ' . $stmt->fetchColumn();
    } else {
        $stmt = $pdo->prepare('SELECT fatura_no FROM faturalar WHERE id = ?');
        $stmt->execute([$belge_id]);
        $mesaj .= ' Fatura No: ' . $stmt->fetchColumn();
    }
    $mesaj .= ' | Tarih: ' . date('d.m.Y', strtotime($tarih_obj));

    flash_set($mesaj, 'success');

    // Efe'nin isteği üzerine (18 Temmuz 2026): işlem tamamlandıktan sonra
    // ilgili carinin Cari Detay sayfasına yönlendiriliyoruz - böylece
    // güncel hesap hareketini/bakiyeyi hemen görebiliyor.
    //
    // SATIŞ işleminden sonra Prim popup'ının da açılabilmesi için satış
    // tutarı/referans/fatura_id bilgilerini yönlendirme URL'sine ekliyoruz
    // (bu sayfa geleneksel form POST + redirect kullanıyor, AJAX değil - popup'ı
    // JS ile "başarılı kayıt sonrası" göstermenin tek yolu bu). Prim popup'ı
    // kapatıldıktan/tamamlandıktan SONRA JS tarafı Cari Detay'a yönlendiriyor
    // (bkz. hizli_islem_script.php - primHayirYonlendir/primKaydet).
    if ($islem_turu === 'SATIS') {
        $primParams = http_build_query([
            'prim_sor'   => 1,
            'tutar'      => $genel_toplam_belge,
            'ref'        => $belge_no,
            'fatura_id'  => $isMakbuz ? '' : $belge_id,
            'makbuz_id'  => $isMakbuz ? $belge_id : '',
            'cari_id'    => $cari_id_int,
        ]);
        header('Location: ' . BASE_URL . '/hizli_islem.php?' . $primParams);
    } else {
        header('Location: ' . BASE_URL . '/cari_detay.php?id=' . $cari_id_int);
    }
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash_set('İşlem sırasında hata oluştu: ' . $e->getMessage(), 'danger');
    header('Location: ' . BASE_URL . '/hizli_islem.php');
    exit;
}
