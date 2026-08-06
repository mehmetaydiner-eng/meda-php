<?php
/**
 * config/database.php
 * PDO/SQLite bağlantı ayarları.
 *
 * NOT: Proje MySQL/MariaDB'den SQLite'a geçirildi (18 Temmuz 2026). Artık
 * ayrı bir veritabanı sunucusu (XAMPP'te MySQL servisi) çalıştırmaya hiç
 * gerek yok - PHP'nin kendi içindeki pdo_sqlite eklentisi yeterli.
 * XAMPP'in PHP kurulumunda bu eklenti genelde zaten aktiftir; değilse
 * php.ini'de "extension=pdo_sqlite" satırının başındaki ";" işaretini
 * kaldırıp Apache'yi yeniden başlatman yeterli.
 */
require_once __DIR__ . '/config.php';

// Veritabanı dosyası "public/" klasörünün DIŞINDA tutuluyor - böylece
// tarayıcıdan doğrudan "http://.../meda.sqlite" gibi bir adresle
// indirilemez/erişilemez (public/ klasörü web sunucusunun kök dizini).
define('DB_PATH', __DIR__ . '/../data/meda.sqlite');
define('DB_SCHEMA_PATH', __DIR__ . '/../sql/schema.sql');

$dataKlasoru = dirname(DB_PATH);
if (!is_dir($dataKlasoru)) {
    @mkdir($dataKlasoru, 0755, true);
}

$veriTabaniYeniOlusturuluyor = !file_exists(DB_PATH);

$dsn = 'sqlite:' . DB_PATH;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, null, null, $options);

    // SQLite'da foreign key (yabancı anahtar) zorlaması VARSAYILAN OLARAK
    // KAPALI gelir - her bağlantıda ayrıca açılması gerekiyor.
    $pdo->exec('PRAGMA foreign_keys = ON;');

    // WAL modu: aynı anda birden fazla kullanıcı/sekme okuma+yazma
    // yaptığında "database is locked" hatalarını büyük ölçüde azaltır.
    $pdo->exec('PRAGMA journal_mode = WAL;');

    // Veritabanı dosyası ilk kez oluşturuluyorsa (henüz hiç tablo yoksa),
    // şemayı otomatik olarak içine yükle - böylece kurulum için ayrıca
    // "sqlite3 ... < schema.sql" komutu çalıştırmaya gerek kalmıyor.
    if ($veriTabaniYeniOlusturuluyor && file_exists(DB_SCHEMA_PATH)) {
        $semaSql = file_get_contents(DB_SCHEMA_PATH);
        $pdo->exec($semaSql);
    }

    // NOT (19 Temmuz 2026): "kategoriler" tablosu schema.sql'e SONRADAN
    // eklendi - zaten çalışan (eski) bir veritabanında bu tablo hiç
    // olmayabilir. Kullanıcının ayrıca bir migrasyon komutu çalıştırmasına
    // gerek kalmasın diye, tablo yoksa burada otomatik oluşturuluyor ve
    // ürünlerde daha önce serbest metin olarak girilmiş kategoriler
    // otomatik olarak içine aktarılıyor.
    if (!$veriTabaniYeniOlusturuluyor) {
        $tabloVarMi = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='kategoriler'"
        )->fetchColumn();
        if (!$tabloVarMi) {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS kategoriler (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    kategori_adi VARCHAR(100) NOT NULL UNIQUE,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
            );
            $pdo->exec(
                "INSERT OR IGNORE INTO kategoriler (kategori_adi)
                 SELECT DISTINCT kategori FROM urunler WHERE kategori IS NOT NULL AND TRIM(kategori) != ''"
            );
        }
    }
} catch (PDOException $e) {
    die('Veritabanı bağlantı hatası: ' . $e->getMessage());
}
