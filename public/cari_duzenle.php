<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$id = safe_int($_GET['id'] ?? null);
if (!$id) {
    header('Location: ' . BASE_URL . '/cariler.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM cariler WHERE id = ?');
$stmt->execute([$id]);
$cari = $stmt->fetch();

if (!$cari) {
    http_response_code(404);
    die('Cari bulunamadı.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(BASE_URL . '/cari_duzenle.php?id=' . $id);

    $unvan         = turkce_upper(trim($_POST['unvan'] ?? ''));
    $vergi_no      = turkce_upper(trim($_POST['vergi_no'] ?? ''));
    $vergi_dairesi = turkce_upper(trim($_POST['vergi_dairesi'] ?? ''));
    $adres         = turkce_upper(trim($_POST['adres'] ?? ''));
    $telefon       = trim($_POST['telefon'] ?? '');
    $email         = mb_strtolower(trim($_POST['email'] ?? ''), 'UTF-8');
    $yetkili       = turkce_upper(trim($_POST['yetkili'] ?? ''));
    // NOT: cari_turu sabit bir <select> değeridir (MÜŞTERİ/TEDARİKÇİ) - serbest
    // metin değildir. turkce_upper() BURADA KASITLI OLARAK kullanılmıyor çünkü
    // İ->I (noktasız) dönüşümü yaparak "MÜŞTERİ"yi "MÜŞTERI"ye, "TEDARİKÇİ"yi
    // "TEDARIKÇİ"ye çeviriyor ve cariler.php'deki rozet/filtre karşılaştırmaları
    // (== 'MÜŞTERİ') bir daha hiç eşleşmiyor. Bu, Hızlı İşlem modülünde bulduğumuz
    // aynı hata sınıfıydı - burada da (bu dosyaya özgü, sonradan fark edilen) bir
    // örneği bulunup düzeltildi.
    $cari_turu     = trim($_POST['cari_turu'] ?? 'MÜŞTERİ');
    $aciklama      = turkce_upper(trim($_POST['aciklama'] ?? ''));

    $update = $pdo->prepare(
        'UPDATE cariler SET unvan = ?, vergi_no = ?, vergi_dairesi = ?, adres = ?, telefon = ?,
         email = ?, yetkili = ?, cari_turu = ?, aciklama = ? WHERE id = ?'
    );
    $update->execute([$unvan, $vergi_no, $vergi_dairesi, $adres, $telefon, $email, $yetkili, $cari_turu, $aciklama, $id]);

    flash_set($unvan . ' güncellendi!', 'success');
    header('Location: ' . BASE_URL . '/cariler.php');
    exit;
}

$page_title   = 'CARİ DÜZENLE';
$breadcrumb   = 'Cari Düzenleme';
$current_page = 'cari_duzenle';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <!-- ========== SOL MENÜ ========== -->
    <div class="col-md-3" style="padding-right: 15px;">
        <div class="card-custom" style="position: sticky; top: 80px;">
            <div class="card-header-custom">
                <h5 style="font-size: 14px;"><i class="fas fa-users" style="color: #6a6a6a;"></i> CARİ MENÜ</h5>
            </div>
            <div class="cari-menu">
                <div class="menu-item">
                    <a href="<?= BASE_URL ?>/cari_ekle.php" class="menu-link">
                        <i class="fas fa-plus-circle"></i> YENİ CARİ EKLE
                    </a>
                </div>
                <div class="menu-item">
                    <a href="<?= BASE_URL ?>/cariler.php" class="menu-link">
                        <i class="fas fa-list"></i> CARİ LİSTESİ
                    </a>
                </div>
                <div class="menu-item">
                    <a href="<?= BASE_URL ?>/cari_duzenle.php?id=<?= (int)$cari['id'] ?>" class="menu-link active">
                        <i class="fas fa-edit"></i> CARİ DÜZENLE
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== SAĞ TARAF - DÜZENLEME FORMU ========== -->
    <div class="col-md-9">
        <div class="card-custom">
            <div class="card-header-custom">
                <h5><i class="fas fa-edit"></i> CARİ DÜZENLE - <?= e($cari['unvan']) ?></h5>
                <a href="<?= BASE_URL ?>/cariler.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-left"></i> GERİ
                </a>
            </div>

            <form method="POST">
                <?= csrf_field() ?>                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label" style="color: #c0c0c0;">ÜNVAN <span class="text-danger">*</span></label>
                        <input type="text" name="unvan" class="form-control" value="<?= e($cari['unvan']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" style="color: #c0c0c0;">VERGİ NO</label>
                        <input type="text" name="vergi_no" class="form-control" value="<?= e($cari['vergi_no'] ?? '') ?>" placeholder="1234567890">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="color: #c0c0c0;">VERGİ DAİRESİ</label>
                        <input type="text" name="vergi_dairesi" class="form-control" value="<?= e($cari['vergi_dairesi'] ?? '') ?>" placeholder="ANTALYA KURUMLAR">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" style="color: #c0c0c0;">TELEFON</label>
                        <input type="text" name="telefon" class="form-control" value="<?= e($cari['telefon'] ?? '') ?>" placeholder="0212 555 55 55">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="color: #c0c0c0;">EMAIL</label>
                        <input type="email" name="email" class="form-control" value="<?= e($cari['email'] ?? '') ?>" placeholder="info@firma.com">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" style="color: #c0c0c0;">YETKİLİ KİŞİ</label>
                        <input type="text" name="yetkili" class="form-control" value="<?= e($cari['yetkili'] ?? '') ?>" placeholder="Ahmet Yılmaz">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="color: #c0c0c0;">CARİ TÜRÜ</label>
                        <select name="cari_turu" class="form-select">
                            <option value="MÜŞTERİ" <?= $cari['cari_turu'] === 'MÜŞTERİ' ? 'selected' : '' ?>>MÜŞTERİ</option>
                            <option value="TEDARİKÇİ" <?= $cari['cari_turu'] === 'TEDARİKÇİ' ? 'selected' : '' ?>>TEDARİKÇİ</option>
                            <option value="PERSONEL" <?= $cari['cari_turu'] === 'PERSONEL' ? 'selected' : '' ?>>PERSONEL (Prim Alacak Kişi)</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label" style="color: #c0c0c0;">ADRES</label>
                        <textarea name="adres" class="form-control" rows="3" placeholder="Adres bilgisi..."><?= e($cari['adres'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label" style="color: #c0c0c0;">AÇIKLAMA</label>
                        <textarea name="aciklama" class="form-control" rows="2" placeholder="Ek açıklama..."><?= e($cari['aciklama'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success-custom">
                        <i class="fas fa-save"></i> GÜNCELLE
                    </button>
                    <a href="<?= BASE_URL ?>/cariler.php" class="btn btn-outline-primary">İPTAL</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
