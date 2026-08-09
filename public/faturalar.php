<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pagination.php';
require_login();

$sayfa = get_current_page();
$perPage = 30;

$toplam_fatura = (int)$pdo->query('SELECT COUNT(*) FROM faturalar')->fetchColumn();
$toplam_sayfa = (int)ceil($toplam_fatura / $perPage);

$stmt = $pdo->prepare(
    'SELECT f.*, c.unvan AS cari_unvan FROM faturalar f LEFT JOIN cariler c ON c.id = f.cari_id
     ORDER BY f.fatura_tarihi DESC LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', pagination_offset($sayfa, $perPage), PDO::PARAM_INT);
$stmt->execute();
$faturalar = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM faturalar WHERE fatura_turu = ?");
$stmt->execute(['SATIŞ']);
$toplam_satis = (int)$stmt->fetchColumn();
$stmt->execute(['ALIŞ']);
$toplam_alis = (int)$stmt->fetchColumn();
$toplam_tutar = (float)($pdo->query('SELECT SUM(genel_toplam) FROM faturalar')->fetchColumn() ?: 0);

$page_title   = 'FATURA LİSTESİ';
$breadcrumb   = 'Tüm Faturalar';
$current_page = 'faturalar';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- İstatistik Kartları -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card-custom text-center">
            <h5 class="text-muted">TOPLAM FATURA</h5>
            <h2 class="text-white"><?= (int)$toplam_fatura ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom text-center">
            <h5 class="text-muted">SATIŞ FATURALARI</h5>
            <h2 class="text-success"><?= (int)$toplam_satis ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom text-center">
            <h5 class="text-muted">ALIŞ FATURALARI</h5>
            <h2 class="text-info"><?= (int)$toplam_alis ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom text-center">
            <h5 class="text-muted">TOPLAM TUTAR</h5>
            <h2 class="text-warning"><?= number_format($toplam_tutar, 2, '.', '') ?> ₺</h2>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    "İptal Et" butonu faturayı <strong>İPTAL</strong> durumuna alır (kalıcı olarak
    silmez, evrak numarası korunur) ama stok/bakiyeyi otomatik geri almaz - fatura
    üç farklı yoldan (Fatura Oluştur / Alış Faturası / Hızlı İşlem) gelebildiği ve
    her biri farklı yan etkiler yarattığı için otomatik geri alma yanlış yöne stok
    hareketi riski taşırdı. Gerekirse ilgili cari/ürün sayfasından manuel düzeltme
    yapabilirsiniz.
</div>

<!-- Fatura Listesi -->
<div class="card-custom">
    <div class="card-header-custom">
        <h5><i class="fas fa-list"></i> FATURA LİSTESİ</h5>
        <div>
            <a href="<?= BASE_URL ?>/fatura_olustur.php" class="btn btn-success-custom btn-sm">
                <i class="fas fa-plus"></i> YENİ SATIŞ FATURASI
            </a>
            <a href="<?= BASE_URL ?>/alis_fatura_olustur.php" class="btn btn-outline-info btn-sm">
                <i class="fas fa-plus"></i> YENİ ALIŞ FATURASI
            </a>
            <a href="<?= BASE_URL ?>/cariler.php" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-arrow-left"></i> GERİ
            </a>
        </div>
    </div>

    <!-- Filtreleme -->
    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" id="faturaArama" class="form-control" placeholder="FATURA NO VEYA MÜŞTERİ ARA...">
        </div>
        <div class="col-md-3">
            <select id="faturaTurFiltre" class="form-select">
                <option value="TÜMÜ">TÜMÜ</option>
                <option value="SATIŞ">SATIŞ</option>
                <option value="ALIŞ">ALIŞ</option>
                <option value="İADE">İADE</option>
            </select>
        </div>
        <div class="col-md-3">
            <select id="faturaTipFiltre" class="form-select">
                <option value="TÜMÜ">TÜM TİPLER</option>
                <option value="E-FATURA">E-FATURA</option>
                <option value="E-ARŞİV">E-ARŞİV</option>
                <option value="KAĞIT">KAĞIT</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary-custom w-100" onclick="filtrele()">
                <i class="fas fa-search"></i> FİLTRELE
            </button>
        </div>
    </div>

    <!-- Tablo -->
    <div class="table-responsive">
        <table class="table-custom" id="faturaTable">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th>FATURA NO</th>
                    <th>TARİH</th>
                    <th>MÜŞTERİ</th>
                    <th>TÜR</th>
                    <th>TİP</th>
                    <th class="text-end">TUTAR</th>
                    <th>DURUM</th>
                    <th style="width: 220px;">İŞLEMLER</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($faturalar): ?>
                    <?php foreach ($faturalar as $i => $fatura): ?>
                    <?php
                        $turClass = $fatura['fatura_turu'] === 'SATIŞ' ? 'bg-success' : ($fatura['fatura_turu'] === 'ALIŞ' ? 'bg-info' : 'bg-warning');
                        $tipClass = $fatura['fatura_tipi'] === 'E-FATURA' ? 'bg-info' : ($fatura['fatura_tipi'] === 'E-ARŞİV' ? 'bg-success' : 'bg-secondary');
                        $durumClass = match($fatura['durum']) {
                            'OLUŞTURULDU' => 'bg-info',
                            'GÖNDERİLDİ'  => 'bg-warning',
                            'ONAYLANDI'   => 'bg-success',
                            default       => 'bg-danger',
                        };
                    ?>
                    <tr data-tur="<?= e($fatura['fatura_turu']) ?>" data-tip="<?= e($fatura['fatura_tipi']) ?>">
                        <td><?= $i + 1 ?></td>
                        <td><a href="<?= BASE_URL ?>/fatura_olustur.php?id=<?= (int)$fatura['id'] ?>" class="text-decoration-none"><strong><?= e($fatura['fatura_no']) ?></strong></a></td>
                        <td><?= format_tarih($fatura['fatura_tarihi'], 'd.m.Y H:i') ?></td>
                        <td><?= $fatura['cari_unvan'] ? '<a href="' . BASE_URL . '/cari_detay.php?id=' . (int)$fatura['cari_id'] . '" class="text-decoration-none">' . e($fatura['cari_unvan']) . '</a>' : '<span class="text-muted">CARİ YOK</span>' ?></td>
                        <td><span class="badge-status <?= $turClass ?>"><?= e($fatura['fatura_turu'] ?: 'BELİRSİZ') ?></span></td>
                        <td><span class="badge-status <?= $tipClass ?>"><?= e($fatura['fatura_tipi'] ?: 'KAĞIT') ?></span></td>
                        <td class="text-end"><?= number_format((float)$fatura['genel_toplam'], 2, '.', '') ?> ₺</td>
                        <td><span class="badge-status <?= $durumClass ?>"><?= e($fatura['durum'] ?: 'TASLAK') ?></span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>/fatura_duzenle.php?id=<?= (int)$fatura['id'] ?>" class="btn btn-outline-primary" title="DÜZENLE">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/fatura_cikti.php?id=<?= (int)$fatura['id'] ?>" class="btn btn-outline-info" title="ÇIKTI" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/fatura_xml_olustur.php?id=<?= (int)$fatura['id'] ?>" class="btn btn-outline-success" title="XML OLUŞTUR" target="_blank">
                                    <i class="fas fa-file-code"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/fatura_xml_indir.php?id=<?= (int)$fatura['id'] ?>" class="btn btn-outline-warning" title="XML İNDİR">
                                    <i class="fas fa-download"></i>
                                </a>
                                <?php if ($fatura['durum'] !== 'İPTAL'): ?>
                                <a href="<?= BASE_URL ?>/fatura_iptal.php?id=<?= (int)$fatura['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" class="btn btn-outline-danger" title="İptal Et"
                                   onclick="return confirm('Bu faturayı iptal etmek istediğinize emin misiniz?\n\nNot: Stok/bakiye otomatik geri alınmaz, gerekirse manuel düzeltme yapmanız gerekir.')">
                                    <i class="fas fa-ban"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        <i class="fas fa-file-invoice fa-3x d-block mb-3"></i>
                        HENÜZ FATURA BULUNMUYOR.<br>
                        <a href="<?= BASE_URL ?>/fatura_olustur.php" class="btn btn-success-custom btn-sm mt-2">
                            <i class="fas fa-plus"></i> İLK FATURAYI OLUŞTUR
                        </a>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?= render_pagination_ozet($sayfa, $perPage, $toplam_fatura) ?>
        <?= render_pagination($sayfa, $toplam_sayfa, BASE_URL . '/faturalar.php') ?>
    </div>
</div>

<!-- XML Oluşturma Kılavuzu -->
<div class="card-custom mt-3">
    <div class="card-header-custom">
        <h5><i class="fas fa-info-circle"></i> XML FATURA İŞLEMLERİ</h5>
    </div>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="border rounded p-3 text-center">
                <i class="fas fa-file-code fa-2x mb-2" style="color: #6ac8d4;"></i>
                <h6>1. XML OLUŞTUR</h6>
                <p class="text-muted small">Faturayı GİB uyumlu XML formatına dönüştür</p>
                <span class="badge-status bg-info">OTOMATİK</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded p-3 text-center">
                <i class="fas fa-download fa-2x mb-2" style="color: #4ad46a;"></i>
                <h6>2. XML İNDİR</h6>
                <p class="text-muted small">XML dosyasını bilgisayarına indir</p>
                <span class="badge-status bg-success">TEK TIK</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded p-3 text-center">
                <i class="fas fa-upload fa-2x mb-2" style="color: #d4c84a;"></i>
                <h6>3. GİB'E YÜKLE</h6>
                <p class="text-muted small">GİB portalına giriş yaparak XML'i yükle</p>
                <span class="badge-status bg-warning">MANUEL</span>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = <<<'JS'
<script>
function filtrele() {
    var arama = document.getElementById('faturaArama').value.toLowerCase();
    var tur = document.getElementById('faturaTurFiltre').value;
    var tip = document.getElementById('faturaTipFiltre').value;

    var rows = document.querySelectorAll('#faturaTable tbody tr');
    rows.forEach(function(row) {
        var goster = true;
        var text = row.textContent.toLowerCase();

        if (arama && !text.includes(arama)) goster = false;
        if (tur !== 'TÜMÜ' && row.dataset.tur !== tur) goster = false;
        if (tip !== 'TÜMÜ' && row.dataset.tip !== tip) goster = false;

        row.style.display = goster ? '' : 'none';
    });
}

document.getElementById('faturaArama').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') filtrele();
});
document.getElementById('faturaTurFiltre').addEventListener('change', filtrele);
document.getElementById('faturaTipFiltre').addEventListener('change', filtrele);
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>
