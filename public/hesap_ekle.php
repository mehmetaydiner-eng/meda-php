<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

/** Flask: generate_hesap_kodu() */
function generate_hesap_kodu(): string
{
    $random = '';
    for ($i = 0; $i < 6; $i++) $random .= random_int(0, 9);
    return 'HSP' . $random;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(BASE_URL . '/hesap_ekle.php');

    $hesap_kodu = trim($_POST['hesap_kodu'] ?? '') ?: generate_hesap_kodu();
    $hesap_adi = turkce_upper(trim($_POST['hesap_adi'] ?? ''));
    // NOT: hesap_turu sabit bir <select> değeridir - turkce_upper() BURADA
    // KASITLI OLARAK kullanılmıyor çünkü 'VERESİYE' değeri gerçek bir noktalı
    // İ içeriyor ve turkce_upper() bunu 'VERESIYE'ye (noktasız I) çevirip
    // her yerdeki karşılaştırmaları (=== 'VERESİYE') bozardı - projede
    // defalarca bulduğumuz aynı İ/I hata sınıfı.
    $hesap_turu = trim($_POST['hesap_turu'] ?? '');
    $para_birimi = turkce_upper(trim($_POST['para_birimi'] ?? 'TRY'));
    $bakiye = safe_float($_POST['bakiye'] ?? null, 0);
    $aciklama = turkce_upper(trim($_POST['aciklama'] ?? ''));

    $banka_adi = turkce_upper(trim($_POST['banka_adi'] ?? ''));
    $sube_adi = turkce_upper(trim($_POST['sube_adi'] ?? ''));
    $iban = mb_strtoupper(trim($_POST['iban'] ?? ''), 'UTF-8');
    $hesap_no = trim($_POST['hesap_no'] ?? '');

    $komisyon_orani = safe_float($_POST['komisyon_orani'] ?? null, 0);
    $komisyon_turu = turkce_upper(trim($_POST['komisyon_turu'] ?? ''));

    if ($hesap_adi === '') {
        flash_set('Hesap adı zorunludur!', 'danger');
        header('Location: ' . BASE_URL . '/hesap_ekle.php');
        exit;
    }
    if ($hesap_turu === '') {
        flash_set('Hesap türü zorunludur!', 'danger');
        header('Location: ' . BASE_URL . '/hesap_ekle.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM hesaplar WHERE hesap_kodu = ?');
    $stmt->execute([$hesap_kodu]);
    if ($stmt->fetch()) {
        flash_set('Bu hesap kodu zaten kullanılıyor!', 'danger');
        header('Location: ' . BASE_URL . '/hesap_ekle.php');
        exit;
    }

    $insert = $pdo->prepare(
        'INSERT INTO hesaplar
            (hesap_kodu, hesap_adi, hesap_turu, para_birimi, bakiye, aciklama,
             banka_adi, sube_adi, iban, hesap_no, komisyon_orani, komisyon_turu,
             is_active, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, datetime(\'now\',\'localtime\'), datetime(\'now\',\'localtime\'))'
    );
    $insert->execute([
        $hesap_kodu, $hesap_adi, $hesap_turu, $para_birimi, $bakiye, $aciklama,
        $banka_adi, $sube_adi, $iban, $hesap_no, $komisyon_orani, $komisyon_turu,
    ]);

    flash_set($hesap_adi . ' başarıyla eklendi!', 'success');
    header('Location: ' . BASE_URL . '/hesaplar_listesi.php');
    exit;
}

$page_title   = 'Yeni Hesap Ekle';
$breadcrumb   = 'Hesap Ekleme';
$current_page = 'hesaplar_listesi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card-custom">
            <div class="card-header-custom">
                <h5><i class="fas fa-plus-circle"></i> YENİ HESAP EKLE</h5>
                <a href="<?= BASE_URL ?>/hesaplar_listesi.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-left"></i> GERİ
                </a>
            </div>
            <form method="POST">
                <?= csrf_field() ?>                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Hesap Adı <span class="text-danger">*</span></label>
                        <input type="text" name="hesap_adi" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hesap Türü <span class="text-danger">*</span></label>
                        <select name="hesap_turu" class="form-select" required>
                            <option value="">Seçin...</option>
                            <option value="BANKA">BANKA</option>
                            <option value="KASA">KASA</option>
                            <option value="VERESİYE">VERESİYE</option>
                            <option value="KOMISYON">KOMİSYON</option>
                            <option value="POS">POS</option>
                            <option value="VIRMAN">VİRMAN</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Para Birimi</label>
                        <select name="para_birimi" class="form-select">
                            <option value="TRY">TL</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Açılış Bakiyesi</label>
                        <input type="number" name="bakiye" class="form-control" step="0.01" value="0">
                    </div>

                    <div class="col-12"><hr><h6 class="text-muted">Banka Bilgileri</h6></div>
                    <div class="col-md-6">
                        <label class="form-label">Banka Adı</label>
                        <input type="text" name="banka_adi" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Şube Adı</label>
                        <input type="text" name="sube_adi" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">IBAN</label>
                        <input type="text" name="iban" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hesap No</label>
                        <input type="text" name="hesap_no" class="form-control">
                    </div>

                    <div class="col-12"><hr><h6 class="text-muted">Komisyon Bilgileri</h6></div>
                    <div class="col-md-6">
                        <label class="form-label">Komisyon Oranı (%)</label>
                        <input type="number" name="komisyon_orani" class="form-control" step="0.01" value="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Komisyon Türü</label>
                        <select name="komisyon_turu" class="form-select">
                            <option value="">Seçin...</option>
                            <option value="SABIT">SABİT</option>
                            <option value="YUZDE">YÜZDE</option>
                            <option value="KAR_PAYI">KAR PAYI</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Açıklama</label>
                        <textarea name="aciklama" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-success-custom">
                        <i class="fas fa-save"></i> KAYDET
                    </button>
                    <a href="<?= BASE_URL ?>/hesaplar_listesi.php" class="btn btn-outline-primary">İPTAL</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
