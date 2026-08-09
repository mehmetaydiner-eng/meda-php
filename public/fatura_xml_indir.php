<?php
/**
 * NOT: Bu route de orijinal Flask uygulamasında yoktu (bkz. fatura_xml_olustur.php
 * içindeki açıklama). Burada gerçek bir .xml dosya indirmesi olarak eklendi.
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

// Daha önce oluşturulmuş XML varsa onu kullan, yoksa yeniden üret
if (!empty($fatura['xml_content'])) {
    $xmlContent = $fatura['xml_content'];
} else {
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
        'unvan' => 'MEDA TEKNOLOJİ A.Ş.', 'vergi_no' => '1234567890',
        'vergi_dairesi' => 'İSTANBUL', 'sehir' => 'İSTANBUL', 'website' => 'www.meda.com.tr',
    ];
    $xmlGenerator = new FaturaXML($fatura, $cari, $detaylar, $firma_bilgileri);
    $xmlContent = $xmlGenerator->olustur();
}

$dosyaAdi = $fatura['fatura_no'] . '.xml';

header('Content-Type: application/xml; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $dosyaAdi . '"');
header('Content-Length: ' . strlen($xmlContent));
echo $xmlContent;
