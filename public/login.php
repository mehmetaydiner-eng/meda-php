<?php
require_once __DIR__ . '/../includes/auth.php';

// Zaten giriş yapmışsa ana sayfaya yönlendir
if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(BASE_URL . '/login.php');

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Flask tarafı: username.replace('İ','I').replace('i','ı').upper()
    $username = str_replace(['İ', 'i'], ['I', 'ı'], $username);
    $username = mb_strtoupper($username, 'UTF-8');

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $result = verify_password_hash($user['password'], $password);

        if ($result === 'ok') {
            login_user_session($user);
            flash_set('Giriş başarılı!', 'success');
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        } elseif ($result === 'legacy_unsupported') {
            flash_set('Bu hesabın parolası eski sistemden aktarıldı, lütfen yöneticinizden parola sıfırlama isteyin.', 'warning');
        } else {
            flash_set('Kullanıcı adı veya şifre hatalı!', 'danger');
        }
    } else {
        flash_set('Kullanıcı adı veya şifre hatalı!', 'danger');
    }
}

$page_title  = 'Giriş Yap';
$breadcrumb  = 'Login';
$current_page = 'login';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-4">
        <div class="card-custom">
            <div class="text-center mb-4">
                <i class="fas fa-cubes" style="font-size: 48px; color: #8a8a8a;"></i>
                <h4 class="mt-2" style="color: #e0e0e0;">MEDA</h4>
                <p class="text-muted">Hesabınıza giriş yapın</p>
            </div>
            <form method="POST">
                <?= csrf_field() ?>                <div class="mb-3">
                    <label class="form-label" style="color: #c0c0c0;">Kullanıcı Adı</label>
                    <input type="text" name="username" class="form-control" style="background: #1a1a1a; border: 1px solid #3a3a3a; color: #e0e0e0;" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="color: #c0c0c0;">Şifre</label>
                    <input type="password" name="password" class="form-control" style="background: #1a1a1a; border: 1px solid #3a3a3a; color: #e0e0e0;" required>
                </div>
                <button type="submit" class="btn btn-primary-custom w-100">Giriş Yap</button>
            </form>
            <div class="text-center mt-3">
                <a href="<?= BASE_URL ?>/register.php" class="text-muted">Hesabın yok mu? Kayıt ol</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
