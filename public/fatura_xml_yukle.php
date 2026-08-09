<?php
/**
 * public/fatura_xml_yukle.php
 *
 * Efe'nin isteği üzerine (19 Temmuz 2026): alis_fatura_olustur.php'den
 * yüklenen bir GİB e-Fatura XML'ini ayrıştırıp, sonucu session'a koyup
 * önizleme sayfasına yönlendirir. Burada HİÇBİR veritabanı yazması
 * yapılmıyor - sadece okuma/ayrıştırma.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/fatura_xml_parser.php';
require_login();
require_csrf(BASE_URL . '/alis_fatura_olustur.php');

if (!isset($_FILES['xml_dosya']) || $_FILES['xml_dosya']['error'] !== UPLOAD_ERR_OK) {
    flash_set('Lütfen bir XML dosyası seçin.', 'danger');
    header('Location: ' . BASE_URL . '/alis_fatura_olustur.php');
    exit;
}

$dosyaAdi = $_FILES['xml_dosya']['name'];
if (!preg_match('/\.xml$/i', $dosyaAdi)) {
    flash_set('Lütfen geçerli bir XML dosyası (.xml uzantılı) seçin.', 'danger');
    header('Location: ' . BASE_URL . '/alis_fatura_olustur.php');
    exit;
}

$icerik = file_get_contents($_FILES['xml_dosya']['tmp_name']);
if ($icerik === false || trim($icerik) === '') {
    flash_set('XML dosyası okunamadı ya da boş.', 'danger');
    header('Location: ' . BASE_URL . '/alis_fatura_olustur.php');
    exit;
}

try {
    $parser = new FaturaXmlParser($icerik);
    $ayristirilmis = $parser->ayristir();
} catch (Throwable $e) {
    flash_set('XML ayrıştırılamadı: ' . $e->getMessage(), 'danger');
    header('Location: ' . BASE_URL . '/alis_fatura_olustur.php');
    exit;
}

if (empty($ayristirilmis['kalemler'])) {
    flash_set('XML içinde hiç ürün kalemi (InvoiceLine) bulunamadı.', 'warning');
    header('Location: ' . BASE_URL . '/alis_fatura_olustur.php');
    exit;
}

// ===== MÜKERRER FATURA KONTROLÜ =====
// Efe'nin bulduğu hata (19 Temmuz 2026): aynı XML dosyası tekrar tekrar
// yüklenip her seferinde ayrı bir fatura olarak kaydedilebiliyordu -
// hiçbir mükerrer kontrolü yoktu. Artık iki farklı kritere göre kontrol
// ediliyor:
// 1) GİB UUID (cbc:UUID) - GİB'in kendi standardına göre HER e-Faturada
//    benzersizdir, en güvenilir kontrol bu.
// 2) Tedarikçinin kendi fatura no'su (cbc:ID, örn. "EFS2026000003854") -
//    UUID bir sebeple boş/eksik gelirse yedek kontrol.
//
// NOT (19 Temmuz 2026 - devamı): Eşleşen kayıt İPTAL edilmişse tamamen
// engellemek yerine - Efe'nin isteği üzerine - kullanıcıya SORULUYOR.
// İptal edilmiş bir faturanın "düzeltilmiş"/"yeniden gönderilmiş" hali
// aynı numara/UUID ile gelebilir, bunu engellemek doğru olmaz ama
// sessizce de geçilmemeli - her seferinde açıkça onay isteniyor.
$uuid = $ayristirilmis['fatura']['uuid'] ?? '';
$kaynakFaturaNo = $ayristirilmis['fatura']['fatura_no'] ?? '';

$mevcutFatura = null;
if ($uuid !== '') {
    $stmt = $pdo->prepare('SELECT id, fatura_no, durum FROM faturalar WHERE gib_uuid = ? LIMIT 1');
    $stmt->execute([$uuid]);
    $mevcutFatura = $stmt->fetch() ?: null;
}
if (!$mevcutFatura && $kaynakFaturaNo !== '') {
    $stmt = $pdo->prepare(
        "SELECT id, fatura_no, durum FROM faturalar WHERE aciklama LIKE ? LIMIT 1"
    );
    $stmt->execute(['%Kaynak Fatura No: ' . $kaynakFaturaNo . '%']);
    $mevcutFatura = $stmt->fetch() ?: null;
}

if ($mevcutFatura && $mevcutFatura['durum'] !== 'İPTAL') {
    flash_set(
        "Bu fatura (Tedarikçi Fatura No: {$kaynakFaturaNo}) zaten daha önce içe aktarılmış! "
        . "Sistemdeki fatura no: {$mevcutFatura['fatura_no']}. Mükerrer kayıt oluşturulmadı.",
        'danger'
    );
    header('Location: ' . BASE_URL . '/fatura_duzenle.php?id=' . $mevcutFatura['id']);
    exit;
}

// Ayrıştırılmış veriyi session'a koy - önizleme sayfası buradan okuyacak.
// Kaydet'e basılana kadar hiçbir DB yazması yok. Eşleşen kayıt iptal
// edilmişse, bu bilgiyi de session'a ekliyoruz - önizleme sayfası
// kullanıcıya açıkça soracak.
$ayristirilmis['iptal_edilmis_eslesme'] = $mevcutFatura ?: null;
$_SESSION['xml_fatura_onizleme'] = $ayristirilmis;

header('Location: ' . BASE_URL . '/fatura_xml_onizleme.php');
exit;
