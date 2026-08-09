<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

/** Flask'taki cari_turu normalizasyon mantığı (hem form hem ajax'ta aynı) */
function normalize_cari_turu(string $raw): string
{
    $raw = mb_strtoupper($raw, 'UTF-8');
    if (str_contains($raw, 'TEDARIK') || str_contains($raw, 'TEDARİK')) {
        return 'TEDARİKÇİ';
    }
    if (str_contains($raw, 'PERSONEL')) {
        return 'PERSONEL';
    }
    if (str_contains($raw, 'MUSTERI') || str_contains($raw, 'MÜŞTERİ')) {
        return 'MÜŞTERİ';
    }
    return 'MÜŞTERİ';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(BASE_URL . '/cari_ekle.php');

    $unvan         = turkce_upper(trim($_POST['unvan'] ?? ''));
    $vergi_no      = turkce_upper(trim($_POST['vergi_no'] ?? ''));
    $vergi_dairesi = turkce_upper(trim($_POST['vergi_dairesi'] ?? ''));
    $adres         = turkce_upper(trim($_POST['adres'] ?? ''));
    $telefon       = trim($_POST['telefon'] ?? '');
    $email         = mb_strtolower(trim($_POST['email'] ?? ''), 'UTF-8');
    $yetkili       = turkce_upper(trim($_POST['yetkili'] ?? ''));
    $cari_turu     = normalize_cari_turu($_POST['cari_turu'] ?? 'MÜŞTERİ');
    $aciklama      = turkce_upper(trim($_POST['aciklama'] ?? ''));

    if ($unvan === '') {
        flash_set('Cari ünvanı zorunludur!', 'danger');
        header('Location: ' . BASE_URL . '/cari_ekle.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM cariler WHERE unvan = ?');
    $stmt->execute([$unvan]);
    if ($stmt->fetch()) {
        flash_set('Bu ünvan zaten kayıtlı!', 'danger');
        header('Location: ' . BASE_URL . '/cari_ekle.php');
        exit;
    }

    if ($vergi_no !== '') {
        $stmt = $pdo->prepare('SELECT id FROM cariler WHERE vergi_no = ?');
        $stmt->execute([$vergi_no]);
        if ($stmt->fetch()) {
            flash_set('Bu vergi numarası zaten kayıtlı!', 'danger');
            header('Location: ' . BASE_URL . '/cari_ekle.php');
            exit;
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO cariler (unvan, vergi_no, vergi_dairesi, adres, telefon, email, yetkili, cari_turu, aciklama, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
    );
    $stmt->execute([$unvan, $vergi_no, $vergi_dairesi, $adres, $telefon, $email, $yetkili, $cari_turu, $aciklama]);

    flash_set($unvan . ' başarıyla eklendi!', 'success');
    header('Location: ' . BASE_URL . '/cariler.php');
    exit;
}

$page_title   = 'Yeni Cari Ekle';
$breadcrumb   = 'Cari Ekleme';
$current_page = 'cari_ekle';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <!-- Sol Menü -->
    <div class="col-md-3" style="padding-right: 15px;">
        <div class="card-custom" style="position: sticky; top: 80px;">
            <div class="card-header-custom">
                <h5 style="font-size: 14px;"><i class="fas fa-users" style="color: #6a6a6a;"></i> Cari Menü</h5>
            </div>
            <div class="cari-menu">
                <div class="menu-item">
                    <a href="<?= BASE_URL ?>/cari_ekle.php" class="menu-link active">
                        <i class="fas fa-plus-circle"></i> Yeni Cari Ekle
                    </a>
                </div>
                <div class="menu-item">
                    <a href="<?= BASE_URL ?>/cariler.php" class="menu-link">
                        <i class="fas fa-list"></i> Cari Listesi
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Sağ Taraf - Form -->
    <div class="col-md-9">
        <div class="card-custom">
            <div class="card-header-custom">
                <h5><i class="fas fa-user-plus"></i> Yeni Cari Ekle</h5>
                <a href="<?= BASE_URL ?>/cariler.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-left"></i> Geri
                </a>
            </div>

            <form method="POST">
                <?= csrf_field() ?>                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label" style="color: #c0c0c0;">Ünvan <span class="text-danger">*</span></label>
                        <input type="text" name="unvan" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" style="color: #c0c0c0;">Vergi No</label>
                        <input type="text" name="vergi_no" class="form-control" placeholder="1234567890">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="color: #c0c0c0;">Vergi Dairesi</label>
                        <input type="text" name="vergi_dairesi" class="form-control" placeholder="İstanbul">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" style="color: #c0c0c0;">Telefon</label>
                        <input type="text" name="telefon" class="form-control" placeholder="0212 555 55 55">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="color: #c0c0c0;">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="info@firma.com">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" style="color: #c0c0c0;">Yetkili Kişi</label>
                        <input type="text" name="yetkili" class="form-control" placeholder="Ahmet Yılmaz">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">CARİ TÜRÜ <span class="text-danger">*</span></label>
                        <select name="cari_turu" class="form-select" required>
                            <option value="MÜŞTERİ" selected>MÜŞTERİ</option>
                            <option value="TEDARİKÇİ">TEDARİKÇİ</option>
                            <option value="PERSONEL">PERSONEL (Prim Alacak Kişi)</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label" style="color: #c0c0c0;">Adres</label>
                        <textarea name="adres" class="form-control" rows="3" placeholder="Adres bilgisi..."></textarea>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label" style="color: #c0c0c0;">Açıklama</label>
                        <textarea name="aciklama" class="form-control" rows="2" placeholder="Ek açıklama..."></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success-custom">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                    <a href="<?= BASE_URL ?>/cariler.php" class="btn btn-outline-primary">İptal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
