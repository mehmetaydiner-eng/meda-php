<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$id = safe_int($_GET['id'] ?? null);
if (!$id) {
    header('Location: ' . BASE_URL . '/teknik_servis_listesi.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM teknik_servis WHERE id = ?');
$stmt->execute([$id]);
$servis = $stmt->fetch();
if (!$servis) {
    http_response_code(404);
    die('Servis kaydı bulunamadı.');
}

$cari = null;
if ($servis['cari_id']) {
    $stmt = $pdo->prepare('SELECT * FROM cariler WHERE id = ?');
    $stmt->execute([$servis['cari_id']]);
    $cari = $stmt->fetch();
}

$stmt = $pdo->prepare('SELECT * FROM servis_malzemeler WHERE teknik_servis_id = ?');
$stmt->execute([$id]);
$malzemeler = $stmt->fetchAll();

$page_title   = 'SERVİS ÇIKTISI';
$breadcrumb   = 'Servis Raporu';
$current_page = 'teknik_servis_listesi';
$extra_css = <<<'CSS'
<style>
    @page { size: A4; margin: 15mm; }
    .print-container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; color: #212529; }
    .print-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #1a5276; padding-bottom: 15px; margin-bottom: 20px; }
    .print-header .logo-area { display: flex; align-items: center; gap: 15px; }
    .print-header .logo-area .logo-text { font-size: 24px; font-weight: 700; color: #1a5276; }
    .print-header .logo-area .firma-bilgi { font-size: 11px; color: #6c757d; line-height: 1.6; }
    .print-header .servis-no { text-align: right; font-size: 14px; font-weight: 700; color: #1a5276; }
    .print-header .servis-no small { font-size: 11px; font-weight: 400; color: #6c757d; display: block; }
    .print-section { border: 1px solid #dee2e6; border-radius: 6px; padding: 12px 15px; margin-bottom: 12px; background: #f8f9fa; }
    .print-section .section-title { font-size: 11px; font-weight: 700; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #dee2e6; padding-bottom: 6px; margin-bottom: 8px; }
    .print-section .section-content { font-size: 13px; line-height: 1.8; color: #212529; }
    .print-section .section-content .row { display: flex; flex-wrap: wrap; gap: 5px 20px; }
    .print-section .section-content .row .col { flex: 1; min-width: 150px; }
    .print-section .section-content .row .col strong { color: #495057; }
    .print-table { width: 100%; font-size: 12px; border-collapse: collapse; margin: 10px 0; }
    .print-table thead th { background: #e9ecef; padding: 6px 10px; border: 1px solid #dee2e6; text-align: left; font-weight: 600; font-size: 10px; text-transform: uppercase; color: #495057; }
    .print-table tbody td { padding: 6px 10px; border: 1px solid #dee2e6; color: #212529; }
    .print-table .text-end { text-align: right; }
    .print-table .text-center { text-align: center; }
    .print-footer { margin-top: 20px; padding-top: 15px; border-top: 2px solid #dee2e6; display: flex; justify-content: space-between; font-size: 11px; color: #6c757d; }
    .print-footer .imza { display: flex; gap: 40px; }
    .print-footer .imza .imza-alani { text-align: center; min-width: 150px; }
    .print-footer .imza .imza-alani .cizgi { border-top: 1px solid #212529; margin-top: 30px; padding-top: 5px; font-size: 10px; color: #6c757d; }
    .no-print { margin-top: 20px; text-align: center; }
    @media print {
        body { background: white !important; }
        .top-navbar, .page-header, .no-print, .btn, .sidebar, .main-content > *:not(.print-container) { display: none !important; }
        .main-content { padding: 0 !important; margin: 0 !important; }
        .print-container { padding: 10px !important; border: none !important; box-shadow: none !important; }
        .print-section { background: #f8f9fa !important; border: 1px solid #dee2e6 !important; }
    }
</style>
CSS;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="print-container" id="print-area">
    <div class="print-header">
        <div class="logo-area">
            <div>
                <div class="logo-text">MEDA</div>
                <div class="firma-bilgi">
                    <strong>MEHMET AYDINER</strong><br>
                    82.Sk. Yenice Apt: No:16 07040 Kızılsaray Mh. / Muratpaşa/ Antalya<br>
                    Tel: 0 (242) 247 6699 | mehmetaydiner@gmail.com
                </div>
            </div>
        </div>
        <div class="servis-no">
            # <?= e($servis['servis_no']) ?>
            <small>Tarih: <?= $servis['created_at'] ? format_tarih($servis['created_at'], 'd.m.Y H:i') : '-' ?></small>
        </div>
    </div>

    <div class="print-section">
        <div class="section-title">MÜŞTERİ BİLGİLERİ</div>
        <div class="section-content">
            <div class="row">
                <div class="col"><strong>Ünvan:</strong> <?= e($cari['unvan'] ?? '-') ?></div>
                <div class="col"><strong>Telefon:</strong> <?= e($cari['telefon'] ?? '-') ?></div>
                <div class="col"><strong>Email:</strong> <?= e($cari['email'] ?? '-') ?></div>
                <div class="col" style="flex: 0 0 100%;"><strong>Adres:</strong> <?= e($cari['adres'] ?? '-') ?></div>
            </div>
        </div>
    </div>

    <div class="print-section">
        <div class="section-title">CİHAZ BİLGİLERİ</div>
        <div class="section-content">
            <div class="row">
                <div class="col"><strong>Ürün Adı:</strong> <?= e($servis['urun_adi']) ?></div>
                <div class="col"><strong>Ürün Tipi:</strong> <?= e($servis['urun_tipi'] ?: '-') ?></div>
                <div class="col"><strong>Marka:</strong> <?= e($servis['marka'] ?: '-') ?></div>
                <div class="col"><strong>Model:</strong> <?= e($servis['model'] ?: '-') ?></div>
                <div class="col"><strong>Seri No:</strong> <?= e($servis['seri_no'] ?: '-') ?></div>
                <div class="col"><strong>Garanti:</strong> <?= e($servis['garanti_durumu'] ?: 'GARANTİSİZ') ?></div>
            </div>
        </div>
    </div>

    <div class="print-section">
        <div class="section-title">SERVİS BİLGİLERİ</div>
        <div class="section-content">
            <div class="row">
                <div class="col"><strong>Durum:</strong> <?= e($servis['durum']) ?></div>
                <div class="col"><strong>Geliş Tarihi:</strong> <?= $servis['gelis_tarihi'] ? format_tarih($servis['gelis_tarihi'], 'd.m.Y H:i') : '-' ?></div>
                <div class="col"><strong>Teknik Personel:</strong> <?= e($servis['teknik_personel'] ?: '-') ?></div>
                <div class="col" style="flex: 0 0 100%;"><strong>Arıza Tanımı:</strong><br><?= nl2br(e($servis['ariza_tanimi'] ?: '-')) ?></div>
                <div class="col" style="flex: 0 0 100%;"><strong>Yapılan İşlem:</strong><br><?= nl2br(e($servis['yapilan_islem'] ?: '-')) ?></div>
            </div>
        </div>
    </div>

    <div class="print-section">
        <div class="section-title">KULLANILAN MALZEMELER</div>
        <div class="section-content">
            <table class="print-table">
                <thead>
                    <tr>
                        <th style="width: 30px;">#</th>
                        <th>Malzeme Adı</th>
                        <th>Barkod</th>
                        <th class="text-center">Miktar</th>
                        <th class="text-end">Birim Fiyat</th>
                        <th class="text-end">Toplam</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($malzemeler): ?>
                        <?php foreach ($malzemeler as $i => $malzeme): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($malzeme['urun_adi']) ?></td>
                            <td><?= e($malzeme['barkod'] ?: '-') ?></td>
                            <td class="text-center"><?= number_format((float)$malzeme['miktar'], 2, '.', '') ?></td>
                            <td class="text-end"><?= number_format((float)$malzeme['birim_fiyati'], 2, '.', '') ?> ₺</td>
                            <td class="text-end"><?= number_format((float)$malzeme['toplam_tutar'], 2, '.', '') ?> ₺</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-2">Malzeme kullanılmamış</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-end"><strong>MALZEME TOPLAMI</strong></td>
                        <td class="text-end"><strong><?= number_format((float)$servis['malzeme_ucreti'], 2, '.', '') ?> ₺</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="print-section">
        <div class="section-title">ÜCRET BİLGİLERİ</div>
        <div class="section-content">
            <div class="row" style="justify-content: flex-end;">
                <div class="col" style="flex: 0 0 200px; text-align: right;">
                    <div><strong>İşçilik Ücreti:</strong> <?= number_format((float)$servis['iscilik_ucreti'], 2, '.', '') ?> ₺</div>
                    <div><strong>Malzeme Ücreti:</strong> <?= number_format((float)$servis['malzeme_ucreti'], 2, '.', '') ?> ₺</div>
                    <div style="font-size: 16px; font-weight: 700; color: #1a5276; border-top: 2px solid #dee2e6; padding-top: 5px; margin-top: 5px;">
                        <strong>TOPLAM:</strong> <?= number_format((float)$servis['toplam_ucret'], 2, '.', '') ?> ₺
                    </div>
                    <div style="font-size: 12px; margin-top: 5px;">
                        <strong>Ödeme Durumu:</strong> <?= e($servis['odeme_durumu'] ?: 'BEKLEMEDE') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($servis['notlar'])): ?>
    <div class="print-section">
        <div class="section-title">NOTLAR</div>
        <div class="section-content"><?= nl2br(e($servis['notlar'])) ?></div>
    </div>
    <?php endif; ?>

    <div class="print-footer">
        <div class="imza">
            <div class="imza-alani">
                <strong>TEKNİK PERSONEL</strong>
                <div class="cizgi"><?= e($servis['teknik_personel'] ?: '_________________') ?></div>
            </div>
            <div class="imza-alani">
                <strong>MÜŞTERİ</strong>
                <div class="cizgi"><?= e($cari['unvan'] ?? '_________________') ?></div>
            </div>
            <div class="imza-alani">
                <strong>KAŞE / İMZA</strong>
                <div class="cizgi">_________________</div>
            </div>
        </div>
        <div style="text-align: right; font-size: 10px; color: #6c757d;">
            <?= $servis['created_at'] ? format_tarih($servis['created_at'], 'd.m.Y H:i') : '-' ?><br>
            MEDA BİLGİSAYAR
        </div>
    </div>
</div>

<div class="no-print">
    <button class="btn btn-primary-custom" onclick="window.print()">
        <i class="fas fa-print"></i> YAZDIR / PDF
    </button>
    <button class="btn btn-success-custom" onclick="excelIndir()">
        <i class="fas fa-file-excel"></i> EXCEL İNDİR
    </button>
    <a href="<?= BASE_URL ?>/teknik_servis_listesi.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left"></i> GERİ
    </a>
</div>

<?php
$servisNoJson = json_encode($servis['servis_no']);
$cariUnvanJson = json_encode($cari['unvan'] ?? '-');
$urunAdiJson = json_encode($servis['urun_adi']);
$durumJson = json_encode($servis['durum']);
$toplamUcretStr = number_format((float)$servis['toplam_ucret'], 2, '.', '');

$extra_js = <<<JS
<script>
function excelIndir() {
    // NOT: Önceki sürüm bir HTML tablosunu ".xls" uzantısıyla kaydediyordu -
    // Excel'de her açılışta "dosya biçimi ve uzantısı eşleşmiyor" uyarısı
    // veriyordu. Burada onun yerine gerçek bir CSV dosyası üretiliyor -
    // Excel'de uyarısız açılır. UTF-8 BOM Türkçe karakterler için,
    // noktalı virgül ayracı Türkçe Excel'in varsayılanı için eklendi.
    var satirlar = [];
    satirlar.push(['SERVİS RAPORU - ' + {$servisNoJson}]);
    satirlar.push(['Müşteri', {$cariUnvanJson}]);
    satirlar.push(['Ürün', {$urunAdiJson}]);
    satirlar.push(['Durum', {$durumJson}]);
    satirlar.push([]);

    var table = document.querySelector('.print-table');
    table.querySelectorAll('thead tr').forEach(function(tr) {
        var hucreler = [];
        tr.querySelectorAll('th').forEach(function(th) { hucreler.push(th.textContent.trim()); });
        satirlar.push(hucreler);
    });
    table.querySelectorAll('tbody tr').forEach(function(tr) {
        var hucreler = [];
        tr.querySelectorAll('td').forEach(function(td) { hucreler.push(td.textContent.trim().replace(/\s+/g, ' ')); });
        if (hucreler.length) satirlar.push(hucreler);
    });
    table.querySelectorAll('tfoot tr').forEach(function(tr) {
        var hucreler = [];
        tr.querySelectorAll('td').forEach(function(td) { hucreler.push(td.textContent.trim().replace(/\s+/g, ' ')); });
        if (hucreler.length) satirlar.push(hucreler);
    });

    satirlar.push([]);
    satirlar.push(['Toplam Ücret', '{$toplamUcretStr} ₺']);

    var csv = satirlar.map(function(satir) {
        return satir.map(function(hucre) {
            var deger = String(hucre == null ? '' : hucre);
            if (deger.indexOf(';') !== -1 || deger.indexOf('"') !== -1 || deger.indexOf('\n') !== -1) {
                deger = '"' + deger.replace(/"/g, '""') + '"';
            }
            return deger;
        }).join(';');
    }).join('\r\n');

    var blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = {$servisNoJson} + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>
