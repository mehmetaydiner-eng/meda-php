<?php
/**
 * NOT: Orijinal Flask uygulamasında `/makbuz/cikti/<id>` route'u vardı ve
 * `render_template('makbuz_cikti.html', ...)` çağırıyordu ama bu şablon
 * dosyası ARŞİVDE HİÇ YOKTU. Yani orijinalde "ÇIKTI" butonuna tıklamak
 * TemplateNotFound hatasıyla (500) çöküyordu - fatura_xml ve stok_barkod
 * ile aynı türden bir eksiklik. Burada fatura_cikti.php ile aynı desende
 * gerçek, çalışan bir çıktı sayfası kuruldu.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$id = safe_int($_GET['id'] ?? null);
if (!$id) {
    header('Location: ' . BASE_URL . '/makbuzlar.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM makbuzlar WHERE id = ?');
$stmt->execute([$id]);
$makbuz = $stmt->fetch();
if (!$makbuz) {
    http_response_code(404);
    die('Makbuz bulunamadı.');
}

$cari = null;
if ($makbuz['cari_id']) {
    $stmt = $pdo->prepare('SELECT * FROM cariler WHERE id = ?');
    $stmt->execute([$makbuz['cari_id']]);
    $cari = $stmt->fetch();
}

$stmt = $pdo->prepare('SELECT * FROM makbuz_detaylari WHERE makbuz_id = ?');
$stmt->execute([$id]);
$detaylar = $stmt->fetchAll();

$firma_bilgileri = [
    'unvan'         => 'MEDA TEKNOLOJİ A.Ş.',
    'vergi_no'      => '1234567890',
    'vergi_dairesi' => 'İSTANBUL',
    'adres'         => 'İSTANBUL/TÜRKİYE',
    'telefon'       => '0212 555 55 55',
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Makbuz <?= e($makbuz['makbuz_no']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; background: white; padding: 20px; color: #000; }
        .makbuz-container { max-width: 800px; margin: 0 auto; border: 1px solid #000; padding: 20px; }
        .makbuz-header { display: flex; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px; }
        .makbuz-header .firma-bilgi { font-size: 12px; line-height: 1.6; }
        .makbuz-header .firma-bilgi strong { font-size: 16px; }
        .makbuz-header .makbuz-bilgi { text-align: right; font-size: 12px; line-height: 1.8; }
        .makbuz-header .makbuz-bilgi .makbuz-no { font-size: 18px; font-weight: bold; }
        .bilgi-kutusu { border: 1px solid #000; padding: 10px 15px; margin-bottom: 15px; font-size: 12px; line-height: 1.8; }
        .bilgi-kutusu .baslik { font-weight: bold; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 8px; }
        .makbuz-table { width: 100%; font-size: 11px; border-collapse: collapse; margin: 10px 0; }
        .makbuz-table th { background: #f0f0f0; padding: 6px 8px; border: 1px solid #000; text-align: left; font-weight: bold; }
        .makbuz-table td { padding: 6px 8px; border: 1px solid #000; }
        .makbuz-table .text-end { text-align: right; }
        .makbuz-table .text-center { text-align: center; }
        .total-box { border: 1px solid #000; padding: 10px 15px; margin-top: 10px; font-size: 12px; }
        .total-box .row { display: flex; justify-content: space-between; padding: 3px 0; }
        .total-box .row.genel { border-top: 2px solid #000; padding-top: 8px; margin-top: 5px; font-size: 16px; font-weight: bold; }
        .imza { margin-top: 20px; padding-top: 15px; border-top: 1px solid #000; font-size: 11px; display: flex; justify-content: space-between; }
        @media print {
            body { padding: 10px; }
            .makbuz-container { border: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="makbuz-container">
        <div class="makbuz-header">
            <div class="firma-bilgi">
                <strong><?= e($firma_bilgileri['unvan']) ?></strong><br>
                Vergi No: <?= e($firma_bilgileri['vergi_no']) ?> | <?= e($firma_bilgileri['vergi_dairesi']) ?><br>
                <?= e($firma_bilgileri['adres']) ?><br>
                Tel: <?= e($firma_bilgileri['telefon']) ?>
            </div>
            <div class="makbuz-bilgi">
                <div class="makbuz-no"><?= e($makbuz['makbuz_turu']) ?> MAKBUZU #<?= e($makbuz['makbuz_no']) ?></div>
                <div>Tarih: <?= format_tarih($makbuz['makbuz_tarihi'], 'd.m.Y H:i') ?></div>
                <div>Durum: <?= e($makbuz['durum']) ?></div>
            </div>
        </div>

        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
            <div style="flex: 1; border: 1px solid #000; padding: 10px 15px;">
                <div style="font-weight: bold; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 8px;">
                    SATICI
                </div>
                <div style="font-size: 12px; line-height: 1.8;">
                    <?= e($firma_bilgileri['unvan']) ?><br>
                    Vergi No: <?= e($firma_bilgileri['vergi_no']) ?><br>
                    <?= e($firma_bilgileri['vergi_dairesi']) ?><br>
                    Tel: <?= e($firma_bilgileri['telefon']) ?>
                </div>
            </div>
            <div style="flex: 1; border: 1px solid #000; padding: 10px 15px;">
                <div style="font-weight: bold; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 8px;">
                    CARİ / MÜŞTERİ
                </div>
                <div style="font-size: 12px; line-height: 1.8;">
                    <?= e($cari['unvan'] ?? '-') ?><br>
                    Vergi No: <?= e($cari['vergi_no'] ?? '-') ?><br>
                    <?= e($cari['vergi_dairesi'] ?? '-') ?><br>
                    Tel: <?= e($cari['telefon'] ?? '-') ?>
                </div>
            </div>
        </div>

        <?php if ($detaylar): ?>
        <table class="makbuz-table">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th style="width: 30%;">ÜRÜN ADI</th>
                    <th style="width: 15%;">BARKOD</th>
                    <th style="width: 12%;" class="text-center">MİKTAR</th>
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
                    <td class="text-center"><?= number_format((float)$detay['miktar'], 2, '.', '') ?></td>
                    <td class="text-end"><?= number_format((float)$detay['birim_fiyati'], 2, '.', '') ?></td>
                    <td class="text-end"><?= number_format((float)$detay['iskonto'], 0, '.', '') ?>%</td>
                    <td class="text-end"><?= number_format((float)$detay['toplam_tutar'], 2, '.', '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="font-size: 12px; margin: 10px 0;">Bu makbuzda ürün kalemi bulunmuyor (tahsilat/ödeme makbuzu).</p>
        <?php endif; ?>

        <div class="total-box">
            <div class="row"><span>ARA TOPLAM</span><span><?= number_format((float)$makbuz['ara_toplam'], 2, '.', '') ?></span></div>
            <div class="row"><span>İSKONTO</span><span>%<?= number_format((float)$makbuz['iskonto'], 0, '.', '') ?> (<?= number_format((float)$makbuz['iskonto_tutari'], 2, '.', '') ?>)</span></div>
            <div class="row genel"><span>GENEL TOPLAM</span><span><?= number_format((float)$makbuz['genel_toplam'], 2, '.', '') ?> <?= e($makbuz['para_birimi'] ?: 'TRY') ?></span></div>
        </div>

        <?php if (!empty($makbuz['aciklama'])): ?>
        <div style="margin-top: 10px; font-size: 11px; color: #555;">
            <strong>Açıklama:</strong> <?= e($makbuz['aciklama']) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($makbuz['notlar'])): ?>
        <div style="margin-top: 5px; font-size: 11px; color: #555;">
            <strong>Not:</strong> <?= e($makbuz['notlar']) ?>
        </div>
        <?php endif; ?>

        <div class="imza">
            <div><strong>SATICI</strong><br><?= e($firma_bilgileri['unvan']) ?></div>
            <div><strong>CARİ</strong><br><?= e($cari['unvan'] ?? '-') ?></div>
            <div><strong>TARİH</strong><br><?= format_tarih($makbuz['makbuz_tarihi']) ?></div>
        </div>

        <div class="no-print" style="margin-top: 20px; text-align: center;">
            <button onclick="window.print()" style="padding: 8px 20px; cursor: pointer;">Yazdır</button>
        </div>
    </div>
</body>
</html>
