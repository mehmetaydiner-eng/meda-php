<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$id = safe_int($_GET['id'] ?? null);
if (!$id) {
    header('Location: ' . BASE_URL . '/hesaplar_listesi.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM hesaplar WHERE id = ?');
$stmt->execute([$id]);
$hesap = $stmt->fetch();
if (!$hesap) {
    http_response_code(404);
    die('Hesap bulunamadı.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(BASE_URL . '/hesap_duzenle.php?id=' . $id);

    $hesap_adi = turkce_upper(trim($_POST['hesap_adi'] ?? ''));
    // NOT: hesap_turu sabit bir <select> değeridir - turkce_upper() BURADA
    // KASITLI OLARAK kullanılmıyor (bkz. hesap_ekle.php'deki aynı not - 'VERESİYE'
    // değerindeki noktalı İ karakteri mangle olurdu).
    $hesap_turu = trim($_POST['hesap_turu'] ?? '');
    $para_birimi = turkce_upper(trim($_POST['para_birimi'] ?? 'TRY'));
    $aciklama = turkce_upper(trim($_POST['aciklama'] ?? ''));

    $banka_adi = turkce_upper(trim($_POST['banka_adi'] ?? ''));
    $sube_adi = turkce_upper(trim($_POST['sube_adi'] ?? ''));
    $iban = mb_strtoupper(trim($_POST['iban'] ?? ''), 'UTF-8');
    $hesap_no = trim($_POST['hesap_no'] ?? '');

    $komisyon_orani = safe_float($_POST['komisyon_orani'] ?? null, 0);
    $komisyon_turu = turkce_upper(trim($_POST['komisyon_turu'] ?? ''));

    $update = $pdo->prepare(
        'UPDATE hesaplar SET hesap_adi=?, hesap_turu=?, para_birimi=?, aciklama=?,
         banka_adi=?, sube_adi=?, iban=?, hesap_no=?, komisyon_orani=?, komisyon_turu=?, updated_at=datetime(\'now\',\'localtime\')
         WHERE id=?'
    );
    $update->execute([
        $hesap_adi, $hesap_turu, $para_birimi, $aciklama,
        $banka_adi, $sube_adi, $iban, $hesap_no, $komisyon_orani, $komisyon_turu, $id,
    ]);

    flash_set($hesap_adi . ' güncellendi!', 'success');
    header('Location: ' . BASE_URL . '/hesaplar_listesi.php');
    exit;
}

$stmt = $pdo->prepare("SELECT SUM(tutar) FROM hesap_hareketleri WHERE hesap_id = ? AND islem_turu IN ('GELEN','GİRİŞ')");
$stmt->execute([$id]);
$gelen_toplam = (float)($stmt->fetchColumn() ?: 0);

$stmt = $pdo->prepare("SELECT SUM(tutar) FROM hesap_hareketleri WHERE hesap_id = ? AND islem_turu IN ('GIDEN','ÇIKIŞ')");
$stmt->execute([$id]);
$giden_toplam = (float)($stmt->fetchColumn() ?: 0);

$page_title   = 'HESAP DÜZENLE';
$breadcrumb   = 'Hesap Düzenleme';
$current_page = 'hesaplar_listesi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card-custom">
            <div class="card-header-custom">
                <h5><i class="fas fa-edit"></i> HESAP DÜZENLE - <?= e($hesap['hesap_adi']) ?></h5>
                <div>
                    <a href="<?= BASE_URL ?>/hesap_hareketleri.php?id=<?= (int)$hesap['id'] ?>" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-list"></i> HAREKETLER
                    </a>
                    <a href="<?= BASE_URL ?>/hesaplar_listesi.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-arrow-left"></i> GERİ
                    </a>
                </div>
            </div>

            <form method="POST">
                <?= csrf_field() ?>                <div class="row g-3">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Hesap Kodu:</strong> <?= e($hesap['hesap_kodu']) ?>
                            <br><small class="text-muted">Oluşturulma: <?= $hesap['created_at'] ? format_tarih($hesap['created_at'], 'd.m.Y H:i') : '-' ?></small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Hesap Adı <span class="text-danger">*</span></label>
                        <input type="text" name="hesap_adi" class="form-control" value="<?= e($hesap['hesap_adi']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hesap Türü <span class="text-danger">*</span></label>
                        <select name="hesap_turu" class="form-select" required>
                            <?php foreach (['BANKA','KASA','VERESİYE','KOMISYON','POS','VIRMAN'] as $tur): ?>
                            <option value="<?= $tur ?>" <?= $hesap['hesap_turu'] === $tur ? 'selected' : '' ?>><?= $tur === 'KOMISYON' ? 'KOMİSYON' : ($tur === 'VIRMAN' ? 'VİRMAN' : $tur) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Para Birimi</label>
                        <select name="para_birimi" class="form-select">
                            <option value="TRY" <?= $hesap['para_birimi'] === 'TRY' ? 'selected' : '' ?>>TL</option>
                            <option value="USD" <?= $hesap['para_birimi'] === 'USD' ? 'selected' : '' ?>>USD</option>
                            <option value="EUR" <?= $hesap['para_birimi'] === 'EUR' ? 'selected' : '' ?>>EUR</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mevcut Bakiye</label>
                        <input type="text" class="form-control" value="<?= number_format((float)$hesap['bakiye'], 2, '.', '') ?> <?= e($hesap['para_birimi']) ?>" readonly
                               style="font-weight: 600; color: <?= $hesap['bakiye'] > 0 ? '#4ad46a' : ($hesap['bakiye'] < 0 ? '#d44a4a' : '#e0e0e0') ?>;">
                        <small class="text-muted">Bakiye değişikliği için hareket ekleyin</small>
                    </div>

                    <div class="col-12"><hr><h6 class="text-muted"><i class="fas fa-university"></i> BANKA BİLGİLERİ</h6></div>
                    <div class="col-md-6">
                        <label class="form-label">Banka Adı</label>
                        <input type="text" name="banka_adi" class="form-control" value="<?= e($hesap['banka_adi'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Şube Adı</label>
                        <input type="text" name="sube_adi" class="form-control" value="<?= e($hesap['sube_adi'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">IBAN</label>
                        <input type="text" name="iban" class="form-control" value="<?= e($hesap['iban'] ?? '') ?>" placeholder="TR00 0000 0000 0000 0000 0000 00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hesap No</label>
                        <input type="text" name="hesap_no" class="form-control" value="<?= e($hesap['hesap_no'] ?? '') ?>">
                    </div>

                    <div class="col-12"><hr><h6 class="text-muted"><i class="fas fa-percent"></i> KOMİSYON BİLGİLERİ</h6></div>
                    <div class="col-md-6">
                        <label class="form-label">Komisyon Oranı (%)</label>
                        <input type="number" name="komisyon_orani" class="form-control" step="0.01" value="<?= e((string)($hesap['komisyon_orani'] ?? 0)) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Komisyon Türü</label>
                        <select name="komisyon_turu" class="form-select">
                            <option value="">Seçin...</option>
                            <option value="SABIT" <?= $hesap['komisyon_turu'] === 'SABIT' ? 'selected' : '' ?>>SABİT</option>
                            <option value="YUZDE" <?= $hesap['komisyon_turu'] === 'YUZDE' ? 'selected' : '' ?>>YÜZDE</option>
                            <option value="KAR_PAYI" <?= $hesap['komisyon_turu'] === 'KAR_PAYI' ? 'selected' : '' ?>>KAR PAYI</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Açıklama</label>
                        <textarea name="aciklama" class="form-control" rows="3"><?= e($hesap['aciklama'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success-custom">
                        <i class="fas fa-save"></i> GÜNCELLE
                    </button>
                    <a href="<?= BASE_URL ?>/hesaplar_listesi.php" class="btn btn-outline-primary">İPTAL</a>
                </div>
            </form>
        </div>

        <!-- HESAP ÖZETİ -->
        <div class="card-custom mt-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-chart-bar"></i> HESAP ÖZETİ</h5>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-center p-3" style="background: #121212; border-radius: 8px; border: 1px solid #2a2a2a;">
                        <h6 class="text-muted">TOPLAM BAKİYE</h6>
                        <h3 class="<?= $hesap['bakiye'] > 0 ? 'text-success' : ($hesap['bakiye'] < 0 ? 'text-danger' : '') ?>">
                            <?= number_format((float)$hesap['bakiye'], 2, '.', '') ?> ₺
                        </h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3" style="background: #121212; border-radius: 8px; border: 1px solid #2a2a2a;">
                        <h6 class="text-muted">GELEN TOPLAM</h6>
                        <h3 class="text-success"><?= number_format($gelen_toplam, 2, '.', '') ?> ₺</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3" style="background: #121212; border-radius: 8px; border: 1px solid #2a2a2a;">
                        <h6 class="text-muted">GİDEN TOPLAM</h6>
                        <h3 class="text-danger"><?= number_format($giden_toplam, 2, '.', '') ?> ₺</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
