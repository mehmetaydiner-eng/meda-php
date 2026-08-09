<?php
/**
 * sql/make_admin.php
 *
 * Yeni kayıt olan kullanıcılar varsayılan olarak 'user' rolüyle oluşturulur.
 * "Numara Yönetimi" ekranındaki geri alınamaz "Tümünü Sıfırla" gibi tehlikeli
 * işlemler sadece 'admin' rolündeki kullanıcılara açıktır. Bu betik, belirttiğin
 * kullanıcı adını admin yapar.
 *
 * KULLANIM (komut satırından):
 *   php make_admin.php KULLANICI_ADI
 *
 * Örnek:
 *   php make_admin.php MEHMET
 */

if (php_sapi_name() !== 'cli') {
    die("Bu betik sadece komut satırından (CLI) çalıştırılmalıdır.\n");
}

require_once __DIR__ . '/../config/database.php';

$username = trim($argv[1] ?? '');
if ($username === '') {
    die("Kullanım: php make_admin.php KULLANICI_ADI\n");
}

// Kullanıcı adları veritabanında büyük harfle saklanıyor (bkz. login.php normalizasyonu)
$username = mb_strtoupper($username, 'UTF-8');

$stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE username = ?');
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    die("'{$username}' adında bir kullanıcı bulunamadı.\n");
}

if ($user['role'] === 'admin') {
    echo "'{$username}' zaten admin.\n";
    exit;
}

$update = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
$update->execute([$user['id']]);

echo "'{$username}' artık admin rolünde. Tekrar giriş yapması gerekebilir.\n";
