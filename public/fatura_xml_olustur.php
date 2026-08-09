<?php
/**
 * NOT: Orijinal Flask uygulamasında bu route hiç yoktu (fatura_listesi.html
 * şablonu var olmayan 'fatura_xml_olustur' endpoint'ine link veriyordu ve
 * bu, en az bir fatura varken /faturalar sayfasının çökmesine sebep oluyordu).
 * Burada utils/fatura_xml.py'deki (kullanılmayan) mantık temel alınarak
 * gerçek bir XML önizleme sayfası kuruldu.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/fatura_xml.php';
require_login();

$id = safe_int($_GET['id'] ?? null);
if (!$id) {
    header('Location: ' . BASE_URL . '/faturalar.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM faturalar WHERE id = ?');
$stmt->execute([$id]);
$fatura = $stmt->fetch();
if (!$fatura) {
    http_response_code(404);
    die('Fatura bulunamadı.');
}

$cari = [];
if ($fatura['cari_id']) {
    $stmt = $pdo->prepare('SELECT * FROM cariler WHERE id = ?');
    $stmt->execute([$fatura['cari_id']]);
    $cari = $stmt->fetch() ?: [];
}

$stmt = $pdo->prepare('SELECT * FROM fatura_detaylari WHERE fatura_id = ?');
$stmt->execute([$id]);
$detaylar = $stmt->fetchAll();

$firma_bilgileri = [
    'unvan'         => 'MEDA TEKNOLOJİ A.Ş.',
    'vergi_no'      => '1234567890',
    'vergi_dairesi' => 'İSTANBUL',
    'sehir'         => 'İSTANBUL',
    'website'       => 'www.meda.com.tr',
];

$xmlGenerator = new FaturaXML($fatura, $cari, $detaylar, $firma_bilgileri);
$xmlContent = $xmlGenerator->olustur();

// Üretilen XML'i faturaya kaydet (Flask'ta xml_content kolonu bunun için vardı)
$pdo->prepare('UPDATE faturalar SET xml_content = ? WHERE id = ?')->execute([$xmlContent, $id]);

$page_title   = 'XML ÖNİZLEME';
$breadcrumb   = 'E-Fatura XML';
$current_page = 'faturalar';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card-custom">
    <div class="card-header-custom">
        <h5><i class="fas fa-file-code"></i> XML ÖNİZLEME - <?= e($fatura['fatura_no']) ?></h5>
        <div>
            <a href="<?= BASE_URL ?>/fatura_xml_indir.php?id=<?= (int)$id ?>" class="btn btn-success-custom btn-sm">
                <i class="fas fa-download"></i> XML İNDİR
            </a>
            <a href="<?= BASE_URL ?>/faturalar.php" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-arrow-left"></i> GERİ
            </a>
        </div>
    </div>
    <pre style="background: #0d0d0d; color: #a0e0a0; padding: 15px; border-radius: 6px; font-size: 12px; max-height: 600px; overflow: auto; white-space: pre-wrap;"><?= e($xmlContent) ?></pre>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
