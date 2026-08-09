<?php
/**
 * public/fatura_xml_kaydet.php
 *
 * Efe'nin isteği üzerine (19 Temmuz 2026): fatura_xml_onizleme.php'de
 * onaylanan (ve gerekirse düzenlenen) veriyi gerçekten veritabanına
 * yazar - cari oluşturma/güncelleme, ürün oluşturma/güncelleme, fatura
 * + fatura_detaylari + stok_hareketleri kaydı.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/numara_manager.php';
require_login();
require_csrf(BASE_URL . '/alis_fatura_olustur.php');

$mevcutCariId = safe_int($_POST['mevcut_cari_id'] ?? null, 0) ?: null;
$cariUnvan = trim($_POST['cari_unvan'] ?? '');
$cariVkn = trim($_POST['cari_vkn'] ?? '');
$cariVergiDairesi = trim($_POST['cari_vergi_dairesi'] ?? '');
$cariAdres = trim($_POST['cari_adres'] ?? '');
$cariTelefon = trim($_POST['cari_telefon'] ?? '');
$cariEmail = trim($_POST['cari_email'] ?? '');

$faturaTarihiStr = trim($_POST['fatura_tarihi'] ?? '');
$paraBirimi = trim($_POST['para_birimi'] ?? 'TRY');
$kaynakUuid = trim($_POST['kaynak_uuid'] ?? '');
$kaynakFaturaNo = trim($_POST['kaynak_fatura_no'] ?? '');

$stokKodlari  = $_POST['stok_kodu'] ?? [];
$mevcutUrunIdler = $_POST['mevcut_urun_id'] ?? [];
$urunAdlari   = $_POST['urun_adi'] ?? [];
$miktarlar    = $_POST['miktar'] ?? [];
$alisFiyatlari = $_POST['alis_fiyati'] ?? [];
$satisFiyatlari = $_POST['satis_fiyati'] ?? [];
$kdvOranlari  = $_POST['kdv_orani'] ?? [];

if ($cariUnvan === '') {
    flash_set('Tedarikçi ünvanı boş olamaz.', 'danger');
    header('Location: ' . BASE_URL . '/fatura_xml_onizleme.php');
    exit;
}
if (empty($urunAdlari)) {
    flash_set('Hiç ürün kalemi yok.', 'danger');
    header('Location: ' . BASE_URL . '/alis_fatura_olustur.php');
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $faturaTarihiStr)) {
    $faturaTarihiStr = date('Y-m-d');
}
// Fatura tarihi + gerçek işlem saati (Efe'nin bulduğu "saat 00:00" hatası
// sınıfına düşmemek için - bkz. hizli_islem_yap.php'deki aynı düzeltme).
$faturaTarihi = $faturaTarihiStr . ' ' . date('H:i:s');

// ===== MÜKERRER FATURA KONTROLÜ (ikinci savunma hattı) =====
// fatura_xml_yukle.php zaten bu kontrolü yapıp önizlemeye göndermeden
// önce engelliyor, ama biri eski bir önizleme sayfasını (browser geri
// tuşu vb.) tekrar gönderirse diye burada da tekrar kontrol ediliyor.
//
// NOT (19 Temmuz 2026 - devamı): Eşleşen kayıt İPTAL edilmişse tamamen
// engellemek yerine - Efe'nin isteği üzerine - kullanıcının önizleme
// sayfasında işaretlediği "iptal_onay" onay kutusu kontrol ediliyor.
// Bu kutu işaretlenmeden kayıt yapılmıyor (sunucu tarafında da
// doğrulanıyor - sadece tarayıcıdaki JS/HTML "required" özelliğine
// güvenilmiyor).
$iptalOnay = ($_POST['iptal_onay'] ?? '') === '1';

$mevcutFatura = null;
if ($kaynakUuid !== '') {
    $stmt = $pdo->prepare('SELECT id, fatura_no, durum FROM faturalar WHERE gib_uuid = ? LIMIT 1');
    $stmt->execute([$kaynakUuid]);
    $mevcutFatura = $stmt->fetch() ?: null;
}
if (!$mevcutFatura && $kaynakFaturaNo !== '') {
    $stmt = $pdo->prepare("SELECT id, fatura_no, durum FROM faturalar WHERE aciklama LIKE ? LIMIT 1");
    $stmt->execute(['%Kaynak Fatura No: ' . $kaynakFaturaNo . '%']);
    $mevcutFatura = $stmt->fetch() ?: null;
}

if ($mevcutFatura) {
    if ($mevcutFatura['durum'] !== 'İPTAL') {
        flash_set(
            "Bu fatura zaten daha önce içe aktarılmış! Sistemdeki fatura no: {$mevcutFatura['fatura_no']}. Mükerrer kayıt oluşturulmadı.",
            'danger'
        );
        header('Location: ' . BASE_URL . '/fatura_duzenle.php?id=' . $mevcutFatura['id']);
        exit;
    }
    if (!$iptalOnay) {
        flash_set(
            "Bu fatura, İPTAL EDİLMİŞ bir kayıtla (Fatura No: {$mevcutFatura['fatura_no']}) eşleşiyor. "
            . 'Yine de kaydetmek için önizleme sayfasındaki onay kutusunu işaretlemeniz gerekiyor.',
            'warning'
        );
        header('Location: ' . BASE_URL . '/fatura_xml_onizleme.php');
        exit;
    }
}

try {
    $pdo->beginTransaction();

    // ===== 1. CARİ OLUŞTUR / GÜNCELLE =====
    // NOT (19 Temmuz 2026 - Efe'nin bulduğu hata): Önceden burada sadece
    // önizleme sayfasından gelen gizli "mevcut_cari_id" alanına
    // güveniliyordu. Bu alan bir sebeple boş/eski gelirse (tarayıcı geri
    // tuşu, eski bir önizleme sayfasının tekrar gönderilmesi vb.) aynı
    // VKN ile tekrar tekrar YENİ cari oluşuyordu - ekranda aynı tedarikçi
    // birden fazla kez listeleniyordu. Artık burada da VKN'ye göre
    // BAĞIMSIZ, YETKİLİ bir kontrol yapılıyor - forma güvenmek yerine
    // veritabanının kendisi soruluyor. Boş VKN geldiyse (nadir durum),
    // yine de formdaki mevcut_cari_id'ye güveniliyor.
    $cariId = null;
    if ($cariVkn !== '') {
        $stmt = $pdo->prepare('SELECT id FROM cariler WHERE vergi_no = ? LIMIT 1');
        $stmt->execute([$cariVkn]);
        $bulunanCari = $stmt->fetch();
        if ($bulunanCari) {
            $cariId = (int)$bulunanCari['id'];
        }
    }
    if (!$cariId && $mevcutCariId) {
        $cariId = $mevcutCariId;
    }

    if ($cariId) {
        $stmt = $pdo->prepare(
            'UPDATE cariler SET unvan=?, vergi_no=?, vergi_dairesi=?, adres=?, telefon=?, email=? WHERE id=?'
        );
        $stmt->execute([$cariUnvan, $cariVkn, $cariVergiDairesi, $cariAdres, $cariTelefon, $cariEmail, $cariId]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO cariler (unvan, vergi_no, vergi_dairesi, adres, telefon, email, cari_turu, aciklama, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
        );
        $stmt->execute([$cariUnvan, $cariVkn, $cariVergiDairesi, $cariAdres, $cariTelefon, $cariEmail, 'TEDARİKÇİ', 'XML faturadan otomatik oluşturuldu.']);
        $cariId = (int)$pdo->lastInsertId();
    }

    // ===== 2. FATURA BAŞLIĞI =====
    $faturaNo = generate_fatura_no_nm($pdo);
    $aciklama = "XML'den içe aktarıldı." . ($kaynakFaturaNo !== '' ? " (Kaynak Fatura No: {$kaynakFaturaNo})" : '');
    $insertFatura = $pdo->prepare(
        'INSERT INTO faturalar
            (fatura_no, fatura_tarihi, cari_id, fatura_turu, fatura_tipi, fatura_senaryosu,
             durum, odeme_turu, para_birimi, gib_uuid, aciklama, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'), datetime(\'now\',\'localtime\'))'
    );
    $insertFatura->execute([
        $faturaNo, $faturaTarihi, $cariId, 'ALIŞ', 'E-FATURA', 'TEMEL', 'OLUŞTURULDU', 'VERESİYE', $paraBirimi,
        $kaynakUuid !== '' ? $kaynakUuid : null, $aciklama,
    ]);
    $faturaId = (int)$pdo->lastInsertId();

    // ===== 3. ÜRÜN KALEMLERİ =====
    $araToplam = 0.0;
    $kdvToplamGenel = 0.0;

    for ($i = 0; $i < count($urunAdlari); $i++) {
        if (trim($urunAdlari[$i]) === '') continue;

        $stokKodu = trim($stokKodlari[$i] ?? '');
        $urunAdi = trim($urunAdlari[$i]);
        $miktar = safe_float($miktarlar[$i] ?? 1, 1);
        $alisFiyati = safe_float($alisFiyatlari[$i] ?? 0, 0);
        $satisFiyati = safe_float($satisFiyatlari[$i] ?? 0, 0);
        $kdvOrani = safe_float($kdvOranlari[$i] ?? 20, 20);
        $mevcutUrunId = safe_int($mevcutUrunIdler[$i] ?? null, 0) ?: null;

        if ($mevcutUrunId) {
            // Eşleşen ürün bulundu - Efe'nin onayladığı gibi alış/satış
            // fiyatı yeni değerlerle güncellenir (eski fiyat geçmişi zaten
            // stok_hareketleri'nde duruyor, kaybolmuyor).
            $stmt = $pdo->prepare('SELECT * FROM urunler WHERE id = ?');
            $stmt->execute([$mevcutUrunId]);
            $urun = $stmt->fetch();

            $stokOncesi = (float)$urun['stok_miktari'];
            $yeniStok = $stokOncesi + $miktar;

            $pdo->prepare(
                'UPDATE urunler SET urun_adi=?, alis_fiyati=?, alis_fiyati_doviz=?, satis_fiyati=?, satis_fiyati_doviz=?, stok_miktari=?, updated_at=datetime(\'now\',\'localtime\') WHERE id=?'
            )->execute([$urunAdi, $alisFiyati, $paraBirimi, $satisFiyati, $paraBirimi, $yeniStok, $mevcutUrunId]);

            $urunId = $mevcutUrunId;
        } else {
            // Yeni ürün - Efe'nin onayladığı gibi kategorisiz açılıyor,
            // sonra elle atanabilir.
            if ($stokKodu === '') {
                $stokKodu = 'XML-' . substr(md5($urunAdi . microtime()), 0, 8);
            }
            $stmt = $pdo->prepare(
                'INSERT INTO urunler (urun_kodu, urun_adi, urun_tipi, alis_fiyati, alis_fiyati_doviz, satis_fiyati, satis_fiyati_doviz, stok_miktari, min_stok, birim, aciklama, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'), datetime(\'now\',\'localtime\'))'
            );
            $stmt->execute([
                $stokKodu, $urunAdi, 'SIFIR', $alisFiyati, $paraBirimi, $satisFiyati, $paraBirimi,
                $miktar, 0, 'ADET', 'XML faturadan otomatik oluşturuldu.',
            ]);
            $urunId = (int)$pdo->lastInsertId();
            $stokOncesi = 0;
            $yeniStok = $miktar;
        }

        stok_hareketi_ekle(
            $pdo, $urunId, 'ALIŞ', $miktar, $stokOncesi, $yeniStok,
            $faturaNo, "XML'den İçe Aktarılan Alış Faturası - {$faturaNo}", $cariId
        );

        $satirToplam = $miktar * $alisFiyati;
        $kdvTutari = $satirToplam * ($kdvOrani / 100);
        $genelSatirToplam = $satirToplam + $kdvTutari;

        $araToplam += $satirToplam;
        $kdvToplamGenel += $kdvTutari;

        $pdo->prepare(
            'INSERT INTO fatura_detaylari
                (fatura_id, urun_id, urun_adi, urun_kodu, birim, miktar, birim_fiyati,
                 iskonto, iskonto_tutari, vergi_orani, vergi_tutari, ara_toplam, toplam_tutar, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
        )->execute([
            $faturaId, $urunId, $urunAdi, $stokKodu, 'ADET', $miktar, $alisFiyati,
            $kdvOrani, $kdvTutari, $satirToplam, $genelSatirToplam,
        ]);
    }

    $genelToplam = $araToplam + $kdvToplamGenel;
    $kdvOraniOrtalama = $araToplam > 0 ? ($kdvToplamGenel / $araToplam * 100) : 20;

    $pdo->prepare(
        'UPDATE faturalar SET ara_toplam=?, vergi_orani=?, vergi_tutari=?, genel_toplam=?, updated_at=datetime(\'now\',\'localtime\') WHERE id=?'
    )->execute([$araToplam, round($kdvOraniOrtalama, 2), $kdvToplamGenel, $genelToplam, $faturaId]);

    // Bu fatura tamamı veresiye (henüz ödeme girilmedi) olarak
    // kaydedildiği için cari borçlanıyor (ALIŞ = kalan kadar bakiye artışı,
    // sistemin geri kalanındaki borç/alacak mantığıyla tutarlı).
    $pdo->prepare('UPDATE cariler SET bakiye = bakiye + ? WHERE id = ?')->execute([$genelToplam, $cariId]);

    $pdo->commit();

    unset($_SESSION['xml_fatura_onizleme']);

    flash_set("XML'den fatura başarıyla içe aktarıldı! Fatura No: {$faturaNo}", 'success');
    header('Location: ' . BASE_URL . '/cari_detay.php?id=' . $cariId);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash_set('Hata: ' . $e->getMessage(), 'danger');
    header('Location: ' . BASE_URL . '/alis_fatura_olustur.php');
    exit;
}
