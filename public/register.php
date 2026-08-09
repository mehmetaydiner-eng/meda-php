<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(BASE_URL . '/register.php');

    $username_raw = trim($_POST['username'] ?? '');
    $username = str_replace(['İ', 'i'], ['I', 'ı'], $username_raw);
    $username = turkce_upper($username);

    $email = mb_strtolower(trim($_POST['email'] ?? ''), 'UTF-8');
    $password = $_POST['password'] ?? '';
    $full_name = turkce_upper(trim($_POST['full_name'] ?? ''));

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $usernameExists = (bool)$stmt->fetch();

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $emailExists = (bool)$stmt->fetch();

    if ($usernameExists) {
        flash_set('Bu kullanıcı adı zaten kullanılıyor!', 'danger');
        header('Location: ' . BASE_URL . '/register.php');
        exit;
    }

    if ($emailExists) {
        flash_set('Bu email zaten kayıtlı!', 'danger');
        header('Location: ' . BASE_URL . '/register.php');
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO users (username, email, password, full_name, role, is_active, created_at)
         VALUES (?, ?, ?, ?, ?, 1, datetime(\'now\',\'localtime\'))'
    );
    $stmt->execute([$username, $email, hash_password($password), $full_name, 'user']);

    flash_set('Hesap oluşturuldu! Giriş yapabilirsiniz.', 'success');
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$page_title  = 'Kayıt Ol';
$breadcrumb  = 'Register';
$current_page = 'register';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-4">
        <div class="card-custom">
            <div class="text-center mb-4">
                <i class="fas fa-cubes" style="font-size: 48px; color: #8a8a8a;"></i>
                <h4 class="mt-2" style="color: #e0e0e0;">MEDA</h4>
                <p class="text-muted">Yeni hesap oluşturun</p>
            </div>
            <form method="POST">
                <?= csrf_field() ?>                <div class="mb-3">
                    <label class="form-label" style="color: #c0c0c0;">Kullanıcı Adı</label>
                    <input type="text" name="username" class="form-control" style="background: #1a1a1a; border: 1px solid #3a3a3a; color: #e0e0e0;" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="color: #c0c0c0;">E-posta</label>
                    <input type="email" name="email" class="form-control" style="background: #1a1a1a; border: 1px solid #3a3a3a; color: #e0e0e0;" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="color: #c0c0c0;">Şifre</label>
                    <input type="password" name="password" class="form-control" style="background: #1a1a1a; border: 1px solid #3a3a3a; color: #e0e0e0;" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="color: #c0c0c0;">Ad Soyad</label>
                    <input type="text" name="full_name" class="form-control" style="background: #1a1a1a; border: 1px solid #3a3a3a; color: #e0e0e0;">
                </div>
                <button type="submit" class="btn btn-primary-custom w-100">Kayıt Ol</button>
            </form>
            <div class="text-center mt-3">
                <a href="<?= BASE_URL ?>/login.php" class="text-muted">Zaten hesabın var mı? Giriş yap</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
