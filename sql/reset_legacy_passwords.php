<?php
/**
 * sql/reset_legacy_passwords.php
 *
 * Flask tarafındaki users tablosunu (scrypt hash'li parolalarla) PHP'ye
 * uyumlu hale getirmek için kullanılır. Aşağıdaki $kullanicilar dizisine
 * mevcut kullanıcı adlarını ve KENDİLERİNE VERECEĞİNİZ yeni parolaları
 * girip, kullanıcıya haber vererek bu betiği bir kez çalıştırın.
 *
 * KULLANIM:
 *   php reset_legacy_passwords.php
 *
 * Betik çalıştıktan sonra bu dosyayı SUNUCUDAN SİLİN veya erişime kapatın.
 */

if (php_sapi_name() !== 'cli') {
    die("Bu betik sadece komut satırından (CLI) çalıştırılmalıdır.\n");
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// ========== BURAYI DÜZENLEYİN ==========
// eski meda.db'de kayıtlı kullanıcı adları: MEHMET, TOLGA
$kullanicilar = [
    'MEHMET' => 'gecici-sifre-1', // <-- gerçek/güvenli bir parola ile değiştirin
    'TOLGA'  => 'gecici-sifre-2', // <-- gerçek/güvenli bir parola ile değiştirin
];
// ========================================

foreach ($kullanicilar as $username => $yeniSifre) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "UYARI: '{$username}' kullanıcı adı bulunamadı, atlandı.\n";
        continue;
    }

    $hash = hash_password($yeniSifre);
    $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $update->execute([$hash, $user['id']]);

    echo "'{$username}' kullanıcısının parolası güncellendi.\n";
}

echo "\nTamamlandı. Kullanıcılara yeni parolalarını iletmeyi unutmayın.\n";
