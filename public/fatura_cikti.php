<?php
require_once __DIR__ . '/../includes/auth.php';
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

$cari = null;
if ($fatura['cari_id']) {
    $stmt = $pdo->prepare('SELECT * FROM cariler WHERE id = ?');
    $stmt->execute([$fatura['cari_id']]);
    $cari = $stmt->fetch();
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
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fatura <?= e($fatura['fatura_no']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; background: white; padding: 20px; color: #000; }
        .fatura-container { max-width: 900px; margin: 0 auto; border: 1px solid #000; padding: 20px; }
        .fatura-header { display: flex; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px; }
        .fatura-header .firma-bilgi { font-size: 12px; line-height: 1.6; }
        .fatura-header .firma-bilgi strong { font-size: 16px; }
        .fatura-header .fatura-bilgi { text-align: right; font-size: 12px; line-height: 1.8; }
        .fatura-header .fatura-bilgi .fatura-no { font-size: 18px; font-weight: bold; }
        .fatura-table { width: 100%; font-size: 11px; border-collapse: collapse; margin: 10px 0; }
        .fatura-table th { background: #f0f0f0; padding: 6px 8px; border: 1px solid #000; text-align: left; font-weight: bold; }
        .fatura-table td { padding: 6px 8px; border: 1px solid #000; }
        .fatura-table .text-end { text-align: right; }
        .fatura-table .text-center { text-align: center; }
        .imza { margin-top: 20px; padding-top: 15px; border-top: 1px solid #000; font-size: 11px; display: flex; justify-content: space-between; }
        @media print {
            body { padding: 10px; }
            .fatura-container { border: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="fatura-container">
        <div class="fatura-header">
            <div class="firma-bilgi">
                <strong><?= e($firma_bilgileri['unvan']) ?></strong><br>
                Vergi No: <?= e($firma_bilgileri['vergi_no']) ?> | <?= e($firma_bilgileri['sehir']) ?><br>
                Tel: 0212 555 55 55 | info@meda.com
            </div>
            <div class="fatura-bilgi">
                <div class="fatura-no">FATURA #<?= e($fatura['fatura_no']) ?></div>
                <div>Tarih: <?= format_tarih($fatura['fatura_tarihi']) ?></div>
                <div>Vade: <?= $fatura['vade_tarihi'] ? format_tarih($fatura['vade_tarihi']) : '-' ?></div>
            </div>
        </div>

        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
            <div style="flex: 1; border: 1px solid #000; padding: 10px 15px;">
                <div style="font-weight: bold; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 8px;">SATICI</div>
                <div style="font-size: 12px; line-height: 1.8;">
                    <?= e($firma_bilgileri['unvan']) ?><br>
                    Vergi No: <?= e($firma_bilgileri['vergi_no']) ?><br>
                    <?= e($firma_bilgileri['sehir']) ?><br>
                    Tel: 0212 555 55 55
                </div>
            </div>
            <div style="flex: 1; border: 1px solid #000; padding: 10px 15px;">
                <div style="font-weight: bold; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 8px;">ALICI</div>
                <div style="font-size: 12px; line-height: 1.8;">
                    <?= e($cari['unvan'] ?? '-') ?><br>
                    Vergi No: <?= e($cari['vergi_no'] ?? '-') ?><br>
                    <?= e($cari['vergi_dairesi'] ?? '-') ?><br>
                    Tel: <?= e($cari['telefon'] ?? '-') ?>
                </div>
            </div>
        </div>

        <table class="fatura-table">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th style="width: 30%;">ÜRÜN ADI</th>
                    <th style="width: 15%;">BARKOD</th>
                    <th style="width: 12%;" class="text-end">MİKTAR</th>
                    <th style="width: 15%;" class="text-end">BİRİM FİYAT</th>
                    <th style="width: 10%;" class="text-end">İSKONTO</th>
                    <th style="width: 18%;" class="text-end">TOPLAM</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detaylar as $i => $detay): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($detay['urun_adi']) ?></td>
                    <td><?= e($detay['barkod'] ?: '-') ?></td>
                    <td class="text-end"><?= number_format((float)$detay['miktar'], 2, '.', '') ?></td>
                    <td class="text-end"><?= number_format((float)$detay['birim_fiyati'], 2, '.', '') ?></td>
                    <td class="text-end"><?= number_format((float)$detay['iskonto'], 0, '.', '') ?>%</td>
                    <td class="text-end"><?= number_format((float)$detay['toplam_tutar'], 2, '.', '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-end"><strong>ARA TOPLAM</strong></td>
                    <td class="text-end"><?= number_format((float)$fatura['ara_toplam'], 2, '.', '') ?></td>
                </tr>
                <tr>
                    <td colspan="5"></td>
                    <td class="text-end"><strong>İSKONTO</strong></td>
                    <td class="text-end">%<?= number_format((float)$fatura['iskonto'], 0, '.', '') ?></td>
                </tr>
                <tr>
                    <td colspan="5"></td>
                    <td class="text-end"><strong>KDV (%20)</strong></td>
                    <td class="text-end"><?= number_format((float)$fatura['vergi_tutari'], 2, '.', '') ?></td>
                </tr>
                <tr style="border-top: 2px solid #000;">
                    <td colspan="6" class="text-end" style="font-size: 16px; font-weight: bold;">GENEL TOPLAM</td>
                    <td class="text-end" style="font-size: 16px; font-weight: bold;"><?= number_format((float)$fatura['genel_toplam'], 2, '.', '') ?></td>
                </tr>
            </tfoot>
        </table>

        <?php if (!empty($fatura['aciklama'])): ?>
        <div style="margin-top: 10px; font-size: 11px; color: #555;">
            <strong>Not:</strong> <?= e($fatura['aciklama']) ?>
        </div>
        <?php endif; ?>

        <div class="imza">
            <div><strong>SATICI</strong><br><?= e($firma_bilgileri['unvan']) ?></div>
            <div><strong>ALICI</strong><br><?= e($cari['unvan'] ?? '-') ?></div>
            <div><strong>TARİH</strong><br><?= format_tarih($fatura['fatura_tarihi']) ?></div>
        </div>
    </div>
</body>
</html>
