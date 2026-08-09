<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pagination.php';
require_login();

$sayfa = get_current_page();
$perPage = 30;

$toplam = (int)$pdo->query('SELECT COUNT(*) FROM teknik_servis')->fetchColumn();
$toplam_sayfa = (int)ceil($toplam / $perPage);

$stmt = $pdo->prepare(
    'SELECT s.*, c.unvan AS cari_unvan FROM teknik_servis s LEFT JOIN cariler c ON c.id = s.cari_id
     ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', pagination_offset($sayfa, $perPage), PDO::PARAM_INT);
$stmt->execute();
$servisler = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM teknik_servis WHERE durum = ?");
$stmt->execute(['BEKLEMEDE']);
$bekleyen = (int)$stmt->fetchColumn();
$stmt->execute(['İŞLEMDE']);
$islemde = (int)$stmt->fetchColumn();
$stmt->execute(['TAMAMLANDI']);
$tamamlanan = (int)$stmt->fetchColumn();

// Kart popup'ları için ayrı, sayfalanmamış sorgular (her biri en fazla 100
// kayıtla sınırlı - bir "hızlı bakış" raporu olduğu için).
$popupServisSorgu = "SELECT s.id, s.servis_no, s.urun_adi, s.durum, c.unvan AS cari_unvan
     FROM teknik_servis s LEFT JOIN cariler c ON c.id = s.cari_id";
$popup_tumservisler = $pdo->query($popupServisSorgu . ' ORDER BY s.created_at DESC')->fetchAll();
$stmt = $pdo->prepare($popupServisSorgu . ' WHERE s.durum = ? ORDER BY s.created_at DESC');
$stmt->execute(['BEKLEMEDE']);
$popup_bekleyenservisler = $stmt->fetchAll();
$stmt->execute(['İŞLEMDE']);
$popup_islemdeservisler = $stmt->fetchAll();
$stmt->execute(['TAMAMLANDI']);
$popup_tamamlananservisler = $stmt->fetchAll();

$page_title   = 'TEKNİK SERVİS';
$breadcrumb   = 'Servis Listesi';
$current_page = 'teknik_servis_listesi';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- İstatistik Kartları -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card-custom text-center" style="cursor:pointer;" onclick="servisStatPopup('tumservisler')" title="Detayları görmek için tıkla">
            <h5 class="text-muted">TOPLAM SERVİS</h5>
            <h2 class="text-white"><?= (int)$toplam ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom text-center" style="cursor:pointer;" onclick="servisStatPopup('bekleyen')" title="Detayları görmek için tıkla">
            <h5 class="text-muted">BEKLEYEN</h5>
            <h2 class="text-warning"><?= (int)$bekleyen ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom text-center" style="cursor:pointer;" onclick="servisStatPopup('islemde')" title="Detayları görmek için tıkla">
            <h5 class="text-muted">İŞLEMDE</h5>
            <h2 class="text-info"><?= (int)$islemde ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom text-center" style="cursor:pointer;" onclick="servisStatPopup('tamamlanan')" title="Detayları görmek için tıkla">
            <h5 class="text-muted">TAMAMLANAN</h5>
            <h2 class="text-success"><?= (int)$tamamlanan ?></h2>
        </div>
    </div>
</div>

<!-- ===== İSTATİSTİK DETAY POPUP ===== -->
<div class="modal fade" id="servisStatModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="servisStatBaslik">Detay</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="servisStatIcerik"></div>
            </div>
        </div>
    </div>
</div>

<script>
var SERVIS_STAT_VERILERI = {
    tumservisler: <?= json_encode($popup_tumservisler, JSON_UNESCAPED_UNICODE) ?>,
    bekleyen: <?= json_encode($popup_bekleyenservisler, JSON_UNESCAPED_UNICODE) ?>,
    islemde: <?= json_encode($popup_islemdeservisler, JSON_UNESCAPED_UNICODE) ?>,
    tamamlanan: <?= json_encode($popup_tamamlananservisler, JSON_UNESCAPED_UNICODE) ?>
};
var SERVIS_STAT_BASLIKLAR = {
    tumservisler: 'Tüm Servis Kayıtları (<?= (int)$toplam ?>)',
    bekleyen: 'Bekleyen Servisler (<?= (int)$bekleyen ?>)',
    islemde: 'İşlemdeki Servisler (<?= (int)$islemde ?>)',
    tamamlanan: 'Tamamlanan Servisler (<?= (int)$tamamlanan ?>)'
};

function servisStatPopup(anahtar) {
    var veri = SERVIS_STAT_VERILERI[anahtar];
    document.getElementById('servisStatBaslik').textContent = SERVIS_STAT_BASLIKLAR[anahtar];

    var icerikEl = document.getElementById('servisStatIcerik');
    if (!veri || veri.length === 0) {
        icerikEl.innerHTML = '<p class="text-muted text-center py-3">Bu kategoride kayıt yok.</p>';
    } else {
        icerikEl.innerHTML = '<div class="list-group">' + veri.map(function(s) {
            return '<a href="teknik_servis_duzenle.php?id=' + s.id + '" class="list-group-item list-group-item-action" style="background:transparent;border-color:var(--border-color);color:var(--text-primary);">' +
                '<div class="d-flex justify-content-between">' +
                '<strong>' + s.servis_no + '</strong><span class="text-muted">' + (s.durum || '-') + '</span>' +
                '</div>' +
                '<small class="text-muted">' + (s.cari_unvan || '-') + ' • ' + (s.urun_adi || '-') + '</small>' +
                '</a>';
        }).join('') + '</div>';
    }

    var modal = new bootstrap.Modal(document.getElementById('servisStatModal'));
    modal.show();
}
</script>

<!-- Servis Listesi -->
<div class="card-custom">
    <div class="card-header-custom">
        <h5><i class="fas fa-tools"></i> SERVİS LİSTESİ</h5>
        <a href="<?= BASE_URL ?>/teknik_servis_ekle.php" class="btn btn-success-custom btn-sm">
            <i class="fas fa-plus"></i> YENİ SERVİS
        </a>
    </div>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Servis No</th>
                    <th>Müşteri</th>
                    <th>Ürün</th>
                    <th>Marka/Model</th>
                    <th>Seri No</th>
                    <th>Durum</th>
                    <th>Toplam Ücret</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($servisler): ?>
                    <?php foreach ($servisler as $servis): ?>
                    <?php
                        $durumClass = match($servis['durum']) {
                            'BEKLEMEDE'      => 'bg-warning',
                            'İŞLEMDE'        => 'bg-info',
                            'TAMAMLANDI'     => 'bg-success',
                            'TESLİM EDİLDİ'  => 'bg-primary',
                            default          => 'bg-danger',
                        };
                    ?>
                    <tr>
                        <td><a href="<?= BASE_URL ?>/teknik_servis_duzenle.php?id=<?= (int)$servis['id'] ?>" class="text-decoration-none"><strong><?= e($servis['servis_no']) ?></strong></a></td>
                        <td><?= $servis['cari_unvan'] ? '<a href="' . BASE_URL . '/cari_detay.php?id=' . (int)$servis['cari_id'] . '" class="text-decoration-none">' . e($servis['cari_unvan']) . '</a>' : '-' ?></td>
                        <td><?= e($servis['urun_adi']) ?></td>
                        <td><?= e($servis['marka'] ?: '-') ?> / <?= e($servis['model'] ?: '-') ?></td>
                        <td><?= e($servis['seri_no'] ?: '-') ?></td>
                        <td><span class="badge-status <?= $durumClass ?>"><?= e($servis['durum']) ?></span></td>
                        <td><?= number_format((float)$servis['toplam_ucret'], 2, '.', '') ?> ₺</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>/teknik_servis_duzenle.php?id=<?= (int)$servis['id'] ?>" class="btn btn-outline-primary" title="Düzenle">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/teknik_servis_cikti.php?id=<?= (int)$servis['id'] ?>" class="btn btn-outline-info" title="Çıktı" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/teknik_servis_sil.php?id=<?= (int)$servis['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" class="btn btn-outline-danger" title="Sil"
                                   onclick="return confirm('<?= e($servis['servis_no']) ?> silmek istediğinize emin misiniz?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="fas fa-tools fa-3x d-block mb-3"></i>
                        Henüz servis kaydı bulunmuyor.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?= render_pagination_ozet($sayfa, $perPage, $toplam) ?>
        <?= render_pagination($sayfa, $toplam_sayfa, BASE_URL . '/teknik_servis_listesi.php') ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
