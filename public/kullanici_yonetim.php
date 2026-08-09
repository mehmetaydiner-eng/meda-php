<?php
/**
 * public/kullanici_yonetim.php
 *
 * Efe'nin isteği üzerine (19 Temmuz 2026): bir kullanıcıyı admin yapmak
 * için `sql/make_admin.php` betiğini komut satırından çalıştırmak
 * gerekiyordu - bu, web üzerinden tek tıkla yapılabilen bir ekrana
 * çevrildi.
 *
 * "Tavuk-yumurta" sorunu: sistemde HİÇ admin yoksa (örn. ilk kurulum),
 * bu sayfaya sadece admin'lerin erişebilmesi kimsenin admin
 * olamamasına yol açardı. Bu yüzden: sistemde hiç admin YOKSA, giriş
 * yapmış herhangi bir kullanıcı bu sayfaya erişip ilk admin'i
 * atayabilir. En az bir admin oluştuktan sonra sayfa normal şekilde
 * sadece admin'lere kapanır.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$hicAdminYok = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() === 0;

if (!$hicAdminYok) {
    require_admin();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(BASE_URL . '/kullanici_yonetim.php');

    $hedefId = safe_int($_POST['user_id'] ?? null);
    $yeniRol = $_POST['yeni_rol'] === 'admin' ? 'admin' : 'user';

    if (!$hedefId) {
        flash_set('Geçersiz kullanıcı.', 'danger');
        header('Location: ' . BASE_URL . '/kullanici_yonetim.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$hedefId]);
    $hedefUser = $stmt->fetch();

    if (!$hedefUser) {
        flash_set('Kullanıcı bulunamadı.', 'danger');
        header('Location: ' . BASE_URL . '/kullanici_yonetim.php');
        exit;
    }

    // Güvenlik: sistemdeki TEK admin'in kendi admin yetkisini (ya da
    // başka birinin admin yetkisini kaldırırken sonuncusu olduysa)
    // kaldırmasına izin verme - aksi halde kimse admin işlemlerine
    // erişemez hale gelebilir.
    if ($yeniRol === 'user' && $hedefUser['role'] === 'admin') {
        $adminSayisi = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        if ($adminSayisi <= 1) {
            flash_set('Sistemdeki TEK admin\'in yetkisi kaldırılamaz - önce başka birini admin yapın.', 'danger');
            header('Location: ' . BASE_URL . '/kullanici_yonetim.php');
            exit;
        }
    }

    $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$yeniRol, $hedefId]);

    if ($yeniRol === 'admin') {
        flash_set($hedefUser['username'] . ' artık admin! Sayfayı yenilediğinde yeni yetkileri hemen aktif olacak.', 'success');
    } else {
        flash_set($hedefUser['username'] . ' artık normal kullanıcı.', 'success');
    }
    header('Location: ' . BASE_URL . '/kullanici_yonetim.php');
    exit;
}

$kullanicilar = $pdo->query('SELECT * FROM users ORDER BY username')->fetchAll();
$suankiKullaniciId = current_user()['id'] ?? null;

$page_title   = 'KULLANICI YÖNETİMİ';
$breadcrumb   = 'Kullanıcılar ve Yetkiler';
$current_page = 'kullanici_yonetim';
require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($hicAdminYok): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i>
    Sistemde henüz hiç admin yok - bu yüzden bu sayfa şu an herkese açık.
    Kendinizi (ya da başka birini) admin yapın, ondan sonra bu sayfa
    sadece admin'lere görünür olacak.
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card-custom">
            <div class="card-header-custom">
                <h5><i class="fas fa-user-shield"></i> KULLANICILAR (<?= count($kullanicilar) ?>)</h5>
            </div>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Kullanıcı Adı</th>
                            <th>E-posta</th>
                            <th>Yetki</th>
                            <th style="width: 160px;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kullanicilar as $u): ?>
                        <tr>
                            <td>
                                <strong><?= e($u['username']) ?></strong>
                                <?php if ((int)$u['id'] === (int)$suankiKullaniciId): ?>
                                    <span class="badge-status bg-secondary" style="font-size:9px;">SEN</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($u['email']) ?></td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge-status bg-danger">ADMİN</span>
                                <?php else: ?>
                                    <span class="badge-status bg-secondary">KULLANICI</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="d-inline"
                                      onsubmit="return confirm('<?= e($u['username']) ?> için yetkiyi değiştirmek istediğinize emin misiniz?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <input type="hidden" name="yeni_rol" value="user">
                                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-user-minus"></i> ADMİNLİKTEN AL
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="yeni_rol" value="admin">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="fas fa-user-shield"></i> ADMİN YAP
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <small class="text-muted d-block mt-2">
            Admin yetkisi, Numara Yönetimi'ndeki "Tümünü Sıfırla" gibi
            geri alınamaz işlemler için gerekiyor.
        </small>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
