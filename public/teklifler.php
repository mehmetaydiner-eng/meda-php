<?php
/**
 * public/teklifler.php
 * Sistemdeki tüm tekliflerin listelendiği ve yönetildiği sayfa.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pagination.php';
require_login();

$sayfa = get_current_page();
$perPage = 30;

// İstatistikler - TÜM teklifler üzerinden (sadece o anki sayfadan değil),
// bu yüzden ayrı SQL sorgularıyla hesaplanıyor.
$toplam_teklif = (int)$pdo->query('SELECT COUNT(*) FROM teklifler')->fetchColumn();
$toplam_sayfa = (int)ceil($toplam_teklif / $perPage);
$onaylanan = (int)$pdo->query("SELECT COUNT(*) FROM teklifler WHERE durum = 'ONAYLANDI'")->fetchColumn();
$bekleyen_taslak = $toplam_teklif - $onaylanan;
$toplam_tutar = (float)($pdo->query('SELECT SUM(genel_toplam) FROM teklifler')->fetchColumn() ?: 0);

$stmt = $pdo->prepare('
    SELECT t.*, c.unvan AS cari_unvan
    FROM teklifler t
    LEFT JOIN cariler c ON c.id = t.cari_id
    ORDER BY t.teklif_tarihi DESC, t.id DESC
    LIMIT :limit OFFSET :offset
');
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', pagination_offset($sayfa, $perPage), PDO::PARAM_INT);
$stmt->execute();
$teklifler = $stmt->fetchAll();

$page_title   = 'TEKLİF YÖNETİMİ';
$breadcrumb   = 'Teklif Listesi';
$current_page = 'teklifler';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- İstatistik Kartları -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card-custom text-center">
            <h5 class="text-muted">TOPLAM TEKLİF</h5>
            <h2 class="text-white"><?= $toplam_teklif ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom text-center">
            <h5 class="text-muted">ONAYLANAN TEKLİFLER</h5>
            <h2 class="text-success"><?= $onaylanan ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom text-center">
            <h5 class="text-muted">BEKLEYEN & TASLAK</h5>
            <h2 class="text-warning"><?= $bekleyen_taslak ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom text-center">
            <h5 class="text-muted">TOPLAM TEKLİF TUTARI</h5>
            <h2 class="text-info"><?= number_format($toplam_tutar, 2, ',', '.') ?> ₺</h2>
        </div>
    </div>
</div>

<!-- Teklif Listesi Kartı -->
<div class="card-custom">
    <div class="card-header-custom">
        <h5><i class="fas fa-file-signature"></i> TEKLİF LİSTESİ</h5>
        <div>
            <a href="<?= BASE_URL ?>/teklif_olustur.php" class="btn btn-success-custom btn-sm">
                <i class="fas fa-plus"></i> YENİ TEKLİF OLUŞTUR
            </a>
            <a href="<?= BASE_URL ?>/cariler.php" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-arrow-left"></i> GERİ
            </a>
        </div>
    </div>

    <!-- Filtreleme & Arama -->
    <div class="row g-2 mb-3">
        <div class="col-md-6">
            <input type="text" id="teklifArama" class="form-control" placeholder="Teklif no, müşteri adı veya konu ile ara...">
        </div>
        <div class="col-md-3">
            <select id="teklifDurumFiltre" class="form-select">
                <option value="TÜMÜ">TÜM DURUMLAR</option>
                <option value="TASLAK">TASLAK</option>
                <option value="BEKLEMEDE">BEKLEMEDE</option>
                <option value="ONAYLANDI">ONAYLANDI</option>
                <option value="REDDEDILDI">REDDEDILDI</option>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary-custom w-100" onclick="filtrele()">
                <i class="fas fa-search"></i> FİLTRELE
            </button>
        </div>
    </div>

    <!-- Tablo -->
    <div class="table-responsive">
        <table class="table-custom" id="teklifTable">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th>TEKLİF NO</th>
                    <th>TARİH</th>
                    <th>MÜŞTERİ</th>
                    <th>KONU</th>
                    <th class="text-end">TUTAR</th>
                    <th>TÜR</th>
                    <th>DURUM</th>
                    <th style="width: 220px;" class="text-end">İŞLEMLER</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($teklifler): ?>
                    <?php foreach ($teklifler as $i => $t): ?>
                        <?php
                        $durumClass = match($t['durum']) {
                            'ONAYLANDI' => 'bg-success',
                            'REDDEDILDI' => 'bg-danger',
                            'BEKLEMEDE' => 'bg-warning text-dark',
                            default => 'bg-secondary',
                        };
                        $turClass = $t['teklif_turu'] === 'VERILEN' ? 'bg-info' : 'bg-secondary';
                        ?>
                        <tr data-durum="<?= e($t['durum']) ?>">
                            <td><?= $i + 1 ?></td>
                            <td><a href="<?= BASE_URL ?>/teklif_olustur.php?id=<?= (int)$t['id'] ?>" class="text-decoration-none"><strong><?= e($t['teklif_no']) ?></strong></a></td>
                            <td><?= format_tarih($t['teklif_tarihi']) ?></td>
                            <td><?= $t['cari_unvan'] ? '<a href="' . BASE_URL . '/cari_detay.php?id=' . (int)$t['cari_id'] . '" class="text-decoration-none">' . e($t['cari_unvan']) . '</a>' : '<span class="text-muted">Cari Tanımlı Değil</span>' ?></td>
                            <td><?= e($t['konu']) ?></td>
                            <td class="text-end"><strong><?= number_format((float)$t['genel_toplam'], 2, ',', '.') ?></strong> <?= e($t['para_birimi']) ?></td>
                            <td><span class="badge <?= $turClass ?>" style="font-size: 10px;"><?= e($t['teklif_turu']) ?></span></td>
                            <td><span class="badge <?= $durumClass ?>" style="font-size: 10px;"><?= e($t['durum'] ?: 'TASLAK') ?></span></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>/teklif_cikti.php?id=<?= $t['id'] ?>" class="btn btn-outline-info" title="ÇIKTI / YAZDIR" target="_blank">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/teklif_olustur.php?id=<?= $t['id'] ?>" class="btn btn-outline-primary" title="DÜZENLE">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($t['durum'] !== 'ONAYLANDI'): ?>
                                        <a href="<?= BASE_URL ?>/teklif_durum.php?id=<?= $t['id'] ?>&durum=ONAYLANDI&csrf_token=<?= urlencode(csrf_token()) ?>" class="btn btn-outline-success" title="ONAYLA" onclick="return confirm('Bu teklifi onaylamak istediğinize emin misiniz?')">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($t['durum'] !== 'REDDEDILDI' && $t['durum'] !== 'ONAYLANDI'): ?>
                                        <a href="<?= BASE_URL ?>/teklif_durum.php?id=<?= $t['id'] ?>&durum=REDDEDILDI&csrf_token=<?= urlencode(csrf_token()) ?>" class="btn btn-outline-warning" title="REDDET" onclick="return confirm('Bu teklifi reddetmek istediğinize emin misiniz?')">
                                            <i class="fas fa-ban"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($t['durum'] === 'ONAYLANDI' || $t['durum'] === 'REDDEDILDI'): ?>
                                        <a href="<?= BASE_URL ?>/teklif_durum.php?id=<?= $t['id'] ?>&durum=TASLAK&csrf_token=<?= urlencode(csrf_token()) ?>" class="btn btn-outline-secondary" title="DRAFT YAP" onclick="return confirm('Teklifi tekrar taslak durumuna çekmek istiyor musunuz?')">
                                            <i class="fas fa-undo"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>/teklif_sil.php?id=<?= $t['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" class="btn btn-outline-danger" title="SİL" onclick="return confirm('Bu teklif kaydını ve detaylarını tamamen silmek istediğinize emin misiniz?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-file-signature fa-3x d-block mb-3 text-secondary"></i>
                            Kayıtlı herhangi bir teklif bulunmuyor.<br>
                            <a href="<?= BASE_URL ?>/teklif_olustur.php" class="btn btn-success-custom btn-sm mt-2">
                                <i class="fas fa-plus"></i> İLK TEKLİFİ OLUŞTUR
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?= render_pagination_ozet($sayfa, $perPage, $toplam_teklif) ?>
        <?= render_pagination($sayfa, $toplam_sayfa, BASE_URL . '/teklifler.php') ?>
    </div>
</div>

<?php
$extra_js = <<<'JS'
<script>
function filtrele() {
    var arama = document.getElementById('teklifArama').value.toLowerCase().trim();
    var durum = document.getElementById('teklifDurumFiltre').value;

    var rows = document.querySelectorAll('#teklifTable tbody tr');
    rows.forEach(function(row) {
        if (row.cells.length < 2) return; // Boş tablo satırı uyarısı ise geç
        
        var text = row.textContent.toLowerCase();
        var rowDurum = row.dataset.durum;
        
        var goster = true;
        if (arama && !text.includes(arama)) goster = false;
        if (durum !== 'TÜMÜ' && rowDurum !== durum) goster = false;
        
        row.style.display = goster ? '' : 'none';
    });
}

document.getElementById('teklifArama').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') filtrele();
});
document.getElementById('teklifDurumFiltre').addEventListener('change', filtrele);
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>
