<?php
/**
 * config/config.php
 * Genel site ayarları. Flask tarafındaki config.py + app.config karşılığı.
 */

// ========== ORTAM (GELİŞTİRME / CANLI) ==========
// XAMPP'te yerel geliştirme yaparken 'development' bırak - hatalar ekranda
// görünsün ki debug edebilesin. Siteyi gerçek bir sunucuya/canlıya
// taşıdığında bunu MUTLAKA 'production' yap - yoksa veritabanı bilgileri,
// dosya yolları gibi hassas detaylar hata mesajlarında herkese görünür olur.
define('APP_ENV', 'development'); // 'development' | 'production'

if (APP_ENV === 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Zaman dilimi (Flask tarafı datetime.utcnow() kullanıyordu; biz yerel saat kullanacağız)
date_default_timezone_set('Europe/Istanbul');

// Site adı (base.html <title> ve navbar'daki MEDA yazısı)
define('SITE_ADI', 'MEDA BİLGİSAYAR');

// ========== KÖK URL (OTOMATİK ALGILAMA) ==========
// XAMPP farklı portlarda çalışabildiği için (8080, 8081, vb.) BASE_URL'i
// sabit yazmak yerine o an hangi adres/porttan girildiyse ondan otomatik
// üretiyoruz. Böylece port değişse bile hiçbir dosyayı düzenlemene gerek kalmaz.
//
// Sadece klasör yolunu (host/port İÇERMEYEN kısmı) burada tanımlıyoruz.
// htdocs içindeki klasör adını değiştirirsen SADECE bu satırı güncelle:
define('APP_BASE_PATH', '/meda-php/public');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
// $_SERVER['HTTP_HOST'] portu da içerir (örn. localhost:8081) - bu yüzden
// port ne olursa olsun doğru adres otomatik oluşur.
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

define('BASE_URL', $protocol . '://' . $host . APP_BASE_PATH);

// Flask'taki SECRET_KEY karşılığı (CSRF token / oturum işlemlerinde kullanılacak)
define('APP_SECRET', 'meda-gizli-anahtar-2024');

// Oturum (session) ayarları - auth.php içinde session_start() çağrılmadan önce yüklenmeli
ini_set('session.cookie_httponly', 1);
// Eğer https kullanıyorsanız aşağıdaki satırı aktif edin:
// ini_set('session.cookie_secure', 1);
