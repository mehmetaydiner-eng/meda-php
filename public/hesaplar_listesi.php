<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$hesaplar = $pdo->query('SELECT * FROM hesaplar ORDER BY hesap_adi')->fetchAll();
$toplam_bakiye = (float)($pdo->query('SELECT SUM(bakiye) FROM hesaplar')->fetchColumn() ?: 0);

$stmt = $pdo->prepare('SELECT COUNT(*) FROM hesaplar WHERE hesap_turu = ?');
$stmt->execute(['BANKA']);
$banka_sayisi = (int)$stmt->fetchColumn();
$stmt->execute(['KASA']);
$kasa_sayisi = (int)$stmt->fetchColumn();
$stmt->execute(['KOMISYON']);
$komisyon_sayisi = (int)$stmt->fetchColumn();
$stmt->execute(['VERESİYE']);
$veresiye_sayisi = (int)$stmt->fetchColumn();

$page_title   = 'Hesaplar';
$breadcrumb   = 'Hesap Yönetimi';
$current_page = 'hesaplar_listesi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card-custom text-center">
            <h5 class="text-muted">TOPLAM BAKİYE</h5>
            <h2 class="text-white"><?= number_format($toplam_bakiye, 2, '.', '') ?> ₺</h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom text-center">
            <h5 class="text-muted">BANKA HESAPLARI</h5>
            <h2 class="text-info"><?= (int)$banka_sayisi ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom text-center">
            <h5 class="text-muted">KASA HESAPLARI</h5>
            <h2 class="text-warning"><?= (int)$kasa_sayisi ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom text-center">
            <h5 class="text-muted">VERESİYE HESAPLARI</h5>
            <h2 class="text-danger"><?= (int)$veresiye_sayisi ?></h2>
        </div>
    </div>
</div>

<div class="card-custom">
    <div class="card-header-custom">
        <h5><i class="fas fa-university"></i> HESAP LİSTESİ</h5>
        <a href="<?= BASE_URL ?>/hesap_ekle.php" class="btn btn-success-custom btn-sm">
            <i class="fas fa-plus"></i> YENİ HESAP
        </a>
    </div>
    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Kod</th>
                    <th>Hesap Adı</th>
                    <th>Tür</th>
                    <th>Para Birimi</th>
                    <th>Bakiye</th>
                    <th>Banka/IBAN</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($hesaplar): ?>
                    <?php foreach ($hesaplar as $hesap): ?>
                    <?php
                        $turClass = match($hesap['hesap_turu']) {
                            'BANKA'     => 'bg-info',
                            'KASA'      => 'bg-warning',
                            'VERESİYE'  => 'bg-danger',
                            'KOMISYON'  => 'bg-success',
                            default     => 'bg-secondary',
                        };
                        $pasifMi = !(int)$hesap['is_active'];
                    ?>
                    <tr<?= $pasifMi ? ' style="opacity:0.55;"' : '' ?>>
                        <td><code><?= e($hesap['hesap_kodu']) ?></code></td>
                        <td>
                            <strong><?= e($hesap['hesap_adi']) ?></strong>
                            <?php if ($pasifMi): ?>
                                <span class="badge-status bg-secondary" style="font-size:9px;">PASİF</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge-status <?= $turClass ?>"><?= e($hesap['hesap_turu']) ?></span></td>
                        <td><?= e($hesap['para_birimi']) ?></td>
                        <td class="<?= $hesap['bakiye'] > 0 ? 'text-success' : ($hesap['bakiye'] < 0 ? 'text-danger' : '') ?>">
                            <?= number_format((float)$hesap['bakiye'], 2, '.', '') ?> ₺
                        </td>
                        <td>
                            <?php if (!empty($hesap['banka_adi'])): ?>
                                <?= e($hesap['banka_adi']) ?>
                                <?php if (!empty($hesap['iban'])): ?><br><small class="text-muted"><?= e($hesap['iban']) ?></small><?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>/hesap_hareketleri.php?id=<?= (int)$hesap['id'] ?>" class="btn btn-outline-info" title="Hareketler">
                                    <i class="fas fa-list"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/hesap_duzenle.php?id=<?= (int)$hesap['id'] ?>" class="btn btn-outline-primary" title="Düzenle">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($pasifMi): ?>
                                    <a href="<?= BASE_URL ?>/hesap_durum_degistir.php?id=<?= (int)$hesap['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>"
                                       class="btn btn-outline-success" title="Aktif Et"
                                       onclick="return confirm('<?= e($hesap['hesap_adi']) ?> hesabını tekrar aktif etmek istediğinize emin misiniz?')">
                                        <i class="fas fa-check-circle"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>/hesap_durum_degistir.php?id=<?= (int)$hesap['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>"
                                       class="btn btn-outline-danger" title="Pasife Al"
                                       onclick="return confirm('<?= e($hesap['hesap_adi']) ?> hesabını pasife almak istediğinize emin misiniz?\n\nBu hesap artık yeni işlemlerde seçilemeyecek, ama geçmiş hareketleri ve raporları olduğu gibi kalacak (silinmeyecek).')">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="fas fa-university fa-3x d-block mb-3"></i>
                        Henüz hesap eklenmemiş.<br>
                        <a href="<?= BASE_URL ?>/hesap_ekle.php" class="btn btn-success-custom btn-sm mt-2">
                            <i class="fas fa-plus"></i> İLK HESABI EKLE
                        </a>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
