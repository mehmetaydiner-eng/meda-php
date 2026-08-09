<?php
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

$hesap = null;
if ($makbuz['hesap_id']) {
    $stmt = $pdo->prepare('SELECT * FROM hesaplar WHERE id = ?');
    $stmt->execute([$makbuz['hesap_id']]);
    $hesap = $stmt->fetch();
}

$stmt = $pdo->prepare('SELECT * FROM makbuz_detaylari WHERE makbuz_id = ?');
$stmt->execute([$id]);
$detaylar = $stmt->fetchAll();

$page_title   = 'MAKBUZ DETAY';
$breadcrumb   = 'Makbuz Detayı';
$current_page = 'makbuzlar';
$extra_css    = '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/makbuz_detay.css">';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="makbuz-detay-container">
    <div class="card-custom">
        <div class="makbuz-detay-header">
            <div class="makbuz-bilgi">
                <div class="makbuz-no"><?= e($makbuz['makbuz_no']) ?></div>
                <div><span class="label">Oluşturulma:</span> <?= $makbuz['created_at'] ? format_tarih($makbuz['created_at'], 'd.m.Y H:i') : '-' ?></div>
                <div><span class="label">Oluşturan:</span> <?= e($makbuz['created_by'] ?: 'Sistem') ?></div>
            </div>
            <div class="makbuz-bilgi text-end">
                <?php
                    $turClass = match($makbuz['makbuz_turu']) {
                        'ALIS'     => 'bg-info',
                        'SATIS'    => 'bg-success',
                        'TAHSILAT' => 'bg-warning',
                        default    => 'bg-danger',
                    };
                ?>
                <div><strong>Makbuz Türü:</strong> <span class="badge-status <?= $turClass ?>"><?= e($makbuz['makbuz_turu']) ?></span></div>
                <div><strong>Durum:</strong> <span class="durum-badge <?= $makbuz['durum'] === 'İPTAL' ? 'iptal' : 'aktif' ?>"><?= e($makbuz['durum']) ?></span></div>
                <div><strong>Tarih:</strong> <?= format_tarih($makbuz['makbuz_tarihi'], 'd.m.Y H:i') ?></div>
            </div>
        </div>

        <div class="makbuz-detay-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="info-card">
                        <div class="info-title"><i class="fas fa-user"></i> CARİ BİLGİLERİ</div>
                        <div class="info-content">
                            <div><strong><?php if ($cari): ?><a href="<?= BASE_URL ?>/cari_detay.php?id=<?= (int)$cari['id'] ?>" class="text-decoration-none"><?= e($cari['unvan']) ?></a><?php else: ?>-<?php endif; ?></strong></div>
                            <div><span class="label">Vergi No:</span> <?= e($cari['vergi_no'] ?? '-') ?></div>
                            <div><span class="label">Vergi Dairesi:</span> <?= e($cari['vergi_dairesi'] ?? '-') ?></div>
                            <div><span class="label">Telefon:</span> <?= e($cari['telefon'] ?? '-') ?></div>
                            <div><span class="label">Adres:</span> <?= e($cari['adres'] ?? '-') ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-card">
                        <div class="info-title"><i class="fas fa-university"></i> HESAP / KASA BİLGİLERİ</div>
                        <div class="info-content">
                            <?php if ($hesap): ?>
                                <div><strong><?= e($hesap['hesap_adi']) ?></strong></div>
                                <div><span class="label">Hesap Türü:</span> <?= e($hesap['hesap_turu']) ?></div>
                                <div><span class="label">Para Birimi:</span> <?= e($makbuz['para_birimi'] ?: 'TRY') ?></div>
                            <?php else: ?>
                                <div class="text-muted">Hesap seçilmemiş</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-card mt-2">
                        <div class="info-title"><i class="fas fa-info-circle"></i> ÖDEME BİLGİLERİ</div>
                        <div class="info-content">
                            <div><span class="label">Ödeme Türü:</span> <?= e($makbuz['odeme_turu'] ?: 'BELİRSİZ') ?></div>
                            <div><span class="label">Para Birimi:</span> <?= e($makbuz['para_birimi'] ?: 'TRY') ?></div>
                            <?php if (!empty($makbuz['aciklama'])): ?>
                            <div><span class="label">Açıklama:</span> <?= e($makbuz['aciklama']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-card mt-3">
                <div class="info-title"><i class="fas fa-list"></i> MAKBUZ KALEMLERİ</div>
                <div class="info-content">
                    <div class="table-responsive">
                        <table class="makbuz-table">
                            <thead>
                                <tr>
                                    <th style="width:30px;">#</th>
                                    <th style="width:30%;">ÜRÜN ADI</th>
                                    <th style="width:15%;">BARKOD</th>
                                    <th style="width:10%;" class="text-center">MİKTAR</th>
                                    <th style="width:15%;" class="text-end">BİRİM FİYAT</th>
                                    <th style="width:10%;" class="text-center">İSKONTO</th>
                                    <th style="width:20%;" class="text-end">TOPLAM</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($detaylar): ?>
                                    <?php foreach ($detaylar as $i => $detay): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td>
                                            <strong><?= e($detay['urun_adi']) ?></strong>
                                            <?php if (!empty($detay['urun_kodu'])): ?>
                                                <br><small class="text-muted">Kod: <?= e($detay['urun_kodu']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($detay['barkod'] ?: '-') ?></td>
                                        <td class="text-center"><?= number_format((float)$detay['miktar'], 2, '.', '') ?></td>
                                        <td class="text-end"><?= number_format((float)$detay['birim_fiyati'], 2, '.', '') ?></td>
                                        <td class="text-center"><?= number_format((float)$detay['iskonto'], 0, '.', '') ?>%</td>
                                        <td class="text-end"><strong><?= number_format((float)$detay['toplam_tutar'], 2, '.', '') ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center text-muted py-3">Ürün bulunmuyor</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="makbuz-toplam-box">
                        <div class="total-row">
                            <span class="label">ARA TOPLAM</span>
                            <span class="value"><?= number_format((float)$makbuz['ara_toplam'], 2, '.', '') ?></span>
                        </div>
                        <div class="total-row">
                            <span class="label">İSKONTO</span>
                            <span class="value">%<?= number_format((float)$makbuz['iskonto'], 0, '.', '') ?></span>
                        </div>
                        <div class="total-row">
                            <span class="label">İSKONTO TUTARI</span>
                            <span class="value"><?= number_format((float)$makbuz['iskonto_tutari'], 2, '.', '') ?></span>
                        </div>
                        <div class="total-row genel-toplam">
                            <span class="label">GENEL TOPLAM</span>
                            <span class="value"><?= number_format((float)$makbuz['genel_toplam'], 2, '.', '') ?> <?= e($makbuz['para_birimi'] ?: 'TRY') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($makbuz['notlar'])): ?>
            <div class="info-card mt-2">
                <div class="info-title"><i class="fas fa-sticky-note"></i> NOTLAR</div>
                <div class="info-content"><?= e($makbuz['notlar']) ?></div>
            </div>
            <?php endif; ?>

            <div class="mt-4 no-print">
                <div class="d-flex flex-wrap gap-2 btn-group-makbuz">
                    <button type="button" class="btn-yazdir" onclick="window.print()">
                        <i class="fas fa-print"></i> YAZDIR / PDF
                    </button>
                    <a href="<?= BASE_URL ?>/makbuz_cikti.php?id=<?= (int)$makbuz['id'] ?>" class="btn btn-outline-info" target="_blank">
                        <i class="fas fa-file-pdf"></i> ÇIKTI AL
                    </a>
                    <?php if ($makbuz['durum'] !== 'İPTAL'): ?>
                    <button type="button" class="btn-iptal-makbuz" onclick="makbuzIptal(<?= (int)$makbuz['id'] ?>)">
                        <i class="fas fa-times"></i> MAKBUZU İPTAL ET
                    </button>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/makbuzlar.php" class="btn-geri">
                        <i class="fas fa-arrow-left"></i> MAKBUZ LİSTESİ
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$api_base_json = json_encode(BASE_URL);
$extra_js = <<<JS
<script>
var API_BASE = {$api_base_json};

function makbuzIptal(id) {
    if (!confirm('Bu makbuzu iptal etmek istediğinize emin misiniz?\\n\\nİptal edildiğinde:\\n- Stok hareketleri geri alınacak\\n- Cari bakiyesi düzeltilecek\\n- Kasa/hesap bakiyesi düzeltilecek\\n\\nBu işlem geri alınamaz!')) {
        return;
    }
    window.location.href = API_BASE + '/makbuz_iptal.php?id=' + id + '&csrf_token=' + encodeURIComponent(CSRF_TOKEN);
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>
