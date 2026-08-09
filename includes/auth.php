<?php
/**
 * includes/auth.php
 * Flask-Login + werkzeug.security'nin yerini alan basit session tabanlı auth katmanı.
 *
 * Flask tarafında:
 *   - login_user(user)      -> session'a user_id yazar
 *   - current_user          -> session'daki id ile User.query.get()
 *   - @login_required       -> girişi olmayanı /login'e yönlendirir
 *   - check_password_hash() -> werkzeug hash doğrulama (scrypt/pbkdf2)
 *
 * ÖNEMLİ NOT (parola uyumluluğu):
 * Flask tarafı parolaları werkzeug'un ürettiği "scrypt:32768:8:1$..." formatında
 * saklıyordu. PHP'nin password_hash()/password_verify() fonksiyonları bcrypt/argon2
 * kullanır ve scrypt formatını doğrudan doğrulayamaz. Bu yüzden:
 *   - Yeni kayıtlar PHP'nin password_hash() (bcrypt) ile saklanacak.
 *   - Veri taşıma (migration) sırasında MEVCUT 2 kullanıcının (MEHMET, TOLGA) parolaları
 *     bu haliyle çalışmayacaktır — sql/reset_legacy_passwords.php betiği ile
 *     yeni parola belirlemeniz gerekir. Detay için sql/README.md dosyasına bakın.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Şu an giriş yapmış kullanıcıyı döndürür (dizi) ya da giriş yoksa null.
 * Aynı istek içinde tekrar tekrar sorgu atmamak için statik cache kullanır.
 */
function current_user(): ?array
{
    static $cached = null;
    static $loaded = false;

    if ($loaded) return $cached;
    $loaded = true;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    $cached = $user ?: null;
    return $cached;
}

/** Flask'taki current_user.is_authenticated karşılığı */
function is_logged_in(): bool
{
    return current_user() !== null;
}

/**
 * Giriş yapılmamışsa login sayfasına yönlendirir ve script'i durdurur.
 * Flask'taki @login_required decorator'ının karşılığı.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Kullanıcıyı oturuma yazar (Flask'taki login_user()).
 */
function login_user_session(array $user): void
{
    session_regenerate_id(true); // oturum sabitleme (session fixation) saldırılarına karşı
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
}

/** Flask'taki logout_user() karşılığı */
function logout_user_session(): void
{
    $_SESSION = [];
    session_destroy();
}

/**
 * Parolayı doğrular. Hem yeni (PHP password_hash / bcrypt) hem de eski
 * (Flask/werkzeug scrypt) formatları tanır; scrypt olanlar için uyarı döner.
 *
 * @return string 'ok' | 'legacy_unsupported' | 'invalid'
 */
function verify_password_hash(string $stored_hash, string $plain_password): string
{
    if (str_starts_with($stored_hash, 'scrypt:') || str_starts_with($stored_hash, 'pbkdf2:')) {
        // Eski Flask/werkzeug hash'i - PHP tarafında doğrudan doğrulanamaz.
        return 'legacy_unsupported';
    }

    return password_verify($plain_password, $stored_hash) ? 'ok' : 'invalid';
}

/** Yeni parola hash'i üretir (bcrypt) */
function hash_password(string $plain_password): string
{
    return password_hash($plain_password, PASSWORD_BCRYPT);
}

/**
 * Basit flash mesaj sistemi (Flask'taki flash()/get_flashed_messages() karşılığı).
 * Kategori: success | danger | warning | info
 */
function flash_set(string $message, string $category = 'info'): void
{
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = ['category' => $category, 'message' => $message];
}

/** Flash mesajları okur ve session'dan temizler (bir kere gösterilir) */
function flash_get_all(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

// ============================================================
// CSRF KORUMASI
// ============================================================
// Klasik bir "senkron token" yöntemi: oturum başına bir token üretilir,
// her POST formunda/AJAX isteğinde gizli bir alan/parametre olarak geri
// gönderilir ve sunucu tarafında oturumdakiyle karşılaştırılır. Bu, bir
// saldırganın başka bir siteden kullanıcının oturumunu kullanarak (CSRF)
// onun adına form göndermesini engeller.

/** Oturuma özel CSRF token'ı döndürür (yoksa oluşturur) */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Klasik <form> içine gömülecek gizli CSRF alanı */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Gelen CSRF token'ını doğrular. $_POST içinden veya (AJAX JSON istekleri
 * için) X-CSRF-Token header'ından okur.
 */
function csrf_is_valid(): bool
{
    $gonderilen = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $beklenen = $_SESSION['csrf_token'] ?? '';
    return $beklenen !== '' && $gonderilen !== '' && hash_equals($beklenen, $gonderilen);
}

/**
 * Klasik form tabanlı sayfalarda kullanılır: token geçersizse kullanıcıyı
 * bir flash mesajıyla geri yönlendirir ve script'i durdurur.
 */
function require_csrf(string $redirectTo): void
{
    if (!csrf_is_valid()) {
        flash_set('Güvenlik doğrulaması başarısız oldu (CSRF). Lütfen formu tekrar gönderin.', 'danger');
        header('Location: ' . $redirectTo);
        exit;
    }
}

/**
 * AJAX/JSON endpoint'lerinde kullanılır: token geçersizse JSON hata
 * döndürür (böylece fetch().then(r => r.json()) zinciri kırılmaz).
 * $tokenOverride: istek gövdesi ham JSON ise (application/json), token
 * $_POST'ta bulunmaz - bu durumda çağıran taraf decode ettiği JSON'dan
 * token'ı çıkarıp buraya açıkça geçirir.
 */
function require_csrf_json(?string $tokenOverride = null): void
{
    $gonderilen = $tokenOverride ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    $beklenen = $_SESSION['csrf_token'] ?? '';

    if ($beklenen === '' || $gonderilen === '' || !hash_equals($beklenen, $gonderilen)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Güvenlik doğrulaması başarısız oldu (CSRF). Lütfen sayfayı yenileyip tekrar deneyin.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ============================================================
// ROL BAZLI YETKİLENDİRME
// ============================================================

/** Giriş yapan kullanıcı 'admin' rolünde mi? */
function is_admin(): bool
{
    $user = current_user();
    return $user !== null && ($user['role'] ?? 'user') === 'admin';
}

/**
 * Sadece admin rolündeki kullanıcıların erişebileceği sayfalar/işlemler için.
 * Yetkisiz erişimde flash mesajıyla ana sayfaya yönlendirir.
 */
function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        flash_set('Bu işlem için yönetici yetkisi gereklidir.', 'danger');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

/** Admin yetkisi gerektiren AJAX/JSON endpoint'leri için */
function require_admin_json(): void
{
    if (!is_logged_in()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!is_admin()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Bu işlem için yönetici yetkisi gereklidir.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
