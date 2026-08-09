<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pagination.php';
require_login();

$sayfa = get_current_page();
$perPage = 30;

$toplam_makbuz = (int)$pdo->query('SELECT COUNT(*) FROM makbuzlar')->fetchColumn();
$toplam_sayfa = (int)ceil($toplam_makbuz / $perPage);

$stmt = $pdo->prepare(
    'SELECT m.*, c.unvan AS cari_unvan FROM makbuzlar m LEFT JOIN cariler c ON c.id = m.cari_id
     ORDER BY m.makbuz_tarihi DESC LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', pagination_offset($sayfa, $perPage), PDO::PARAM_INT);
$stmt->execute();
$makbuzlar = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM makbuzlar WHERE makbuz_turu = ?');
$stmt->execute(['ALIS']);
$toplam_alis = (int)$stmt->fetchColumn();
$stmt->execute(['SATIS']);
$toplam_satis = (int)$stmt->fetchColumn();
$stmt->execute(['TAHSILAT']);
$toplam_tahsilat = (int)$stmt->fetchColumn();
$stmt->execute(['ODEME']);
$toplam_odeme = (int)$stmt->fetchColumn();
$toplam_tutar = (float)($pdo->query('SELECT SUM(genel_toplam) FROM makbuzlar')->fetchColumn() ?: 0);

$page_title   = 'MAKBUZ LİSTESİ';
$breadcrumb   = 'Tüm Makbuzlar';
$current_page = 'makbuzlar';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card-custom text-center">
            <h5 class="text-muted">TOPLAM MAKBUZ</h5>
            <h2 class="text-white"><?= (int)$toplam_makbuz ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom text-center">
            <h5 class="text-muted">ALIŞ</h5>
            <h2 class="text-info"><?= (int)$toplam_alis ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom text-center">
            <h5 class="text-muted">SATIŞ</h5>
            <h2 class="text-success"><?= (int)$toplam_satis ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom text-center">
            <h5 class="text-muted">TAHSİLAT / ÖDEME</h5>
            <h2 class="text-warning"><?= (int)$toplam_tahsilat + (int)$toplam_odeme ?></h2>
        </div>
    </div>
</div>

<div class="card-custom">
    <div class="card-header-custom">
        <h5><i class="fas fa-receipt"></i> MAKBUZ LİSTESİ</h5>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>/makbuz_olustur.php?tur=ALIS" class="btn btn-info btn-sm">
                <i class="fas fa-arrow-down"></i> ALIŞ
            </a>
            <a href="<?= BASE_URL ?>/makbuz_olustur.php?tur=SATIS" class="btn btn-success btn-sm">
                <i class="fas fa-arrow-up"></i> SATIŞ
            </a>
            <a href="<?= BASE_URL ?>/makbuz_olustur.php?tur=TAHSILAT" class="btn btn-warning btn-sm">
                <i class="fas fa-hand-holding-usd"></i> TAHSİLAT
            </a>
            <a href="<?= BASE_URL ?>/makbuz_olustur.php?tur=ODEME" class="btn btn-danger btn-sm">
                <i class="fas fa-money-bill-wave"></i> ÖDEME
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Makbuz No</th>
                    <th>Tarih</th>
                    <th>Tür</th>
                    <th>Cari</th>
                    <th class="text-end">Tutar</th>
                    <th>Ödeme</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($makbuzlar): ?>
                    <?php foreach ($makbuzlar as $makbuz): ?>
                    <?php
                        $turClass = match($makbuz['makbuz_turu']) {
                            'ALIS'  => 'bg-info',
                            'SATIS' => 'bg-success',
                            'TAHSILAT' => 'bg-warning',
                            default => 'bg-danger',
                        };
                    ?>
                    <tr>
                        <td><a href="<?= BASE_URL ?>/makbuz_detay.php?id=<?= (int)$makbuz['id'] ?>" class="text-decoration-none"><strong><?= e($makbuz['makbuz_no']) ?></strong></a></td>
                        <td><?= format_tarih($makbuz['makbuz_tarihi'], 'd.m.Y H:i') ?></td>
                        <td><span class="badge-status <?= $turClass ?>"><?= e($makbuz['makbuz_turu']) ?></span></td>
                        <td><?= $makbuz['cari_unvan'] ? '<a href="' . BASE_URL . '/cari_detay.php?id=' . (int)$makbuz['cari_id'] . '" class="text-decoration-none">' . e($makbuz['cari_unvan']) . '</a>' : '-' ?></td>
                        <td class="text-end"><?= number_format((float)$makbuz['genel_toplam'], 2, '.', '') ?> ₺</td>
                        <td><?= e($makbuz['odeme_turu']) ?></td>
                        <td><span class="badge-status <?= $makbuz['durum'] === 'İPTAL' ? 'bg-danger' : 'bg-success' ?>"><?= e($makbuz['durum']) ?></span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>/makbuz_detay.php?id=<?= (int)$makbuz['id'] ?>" class="btn btn-outline-info" title="Detay">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/makbuz_cikti.php?id=<?= (int)$makbuz['id'] ?>" class="btn btn-outline-primary" title="Çıktı" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                                <?php if ($makbuz['durum'] !== 'İPTAL'): ?>
                                <a href="<?= BASE_URL ?>/makbuz_iptal.php?id=<?= (int)$makbuz['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" class="btn btn-outline-danger" title="İptal"
                                   onclick="return confirm('Makbuzu iptal etmek istediğinize emin misiniz? Stok ve hesap hareketleri geri alınacaktır.')">
                                    <i class="fas fa-times"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="fas fa-receipt fa-3x d-block mb-3"></i>
                        Henüz makbuz bulunmuyor.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="border-top: 2px solid var(--border-color);">
                    <td colspan="4" class="text-end"><strong>GENEL TOPLAM</strong></td>
                    <td class="text-end"><strong><?= number_format($toplam_tutar, 2, '.', '') ?> ₺</strong></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
        <?= render_pagination_ozet($sayfa, $perPage, $toplam_makbuz) ?>
        <?= render_pagination($sayfa, $toplam_sayfa, BASE_URL . '/makbuzlar.php') ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
