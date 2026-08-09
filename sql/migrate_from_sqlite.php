<?php
/**
 * sql/migrate_from_sqlite.php
 *
 * ⚠️ ARTIK GEÇERLİ DEĞİL (18 Temmuz 2026): Bu betik, orijinal Flask
 * uygulamasının SQLite verisini MySQL'e aktarmak için yazılmıştı. Proje
 * şimdi MySQL'den SQLite'a geri geçirildiği için (bkz. README "SQLite'a
 * Geçiş" bölümü) bu betiğin bir işlevi kalmadı - $pdo artık zaten SQLite'a
 * bağlanıyor, bu betiği ÇALIŞTIRMA. Sadece tarihsel referans için
 * bırakıldı.
 *
 * Eski Flask uygulamasının "instance/meda.db" (SQLite) dosyasındaki
 * VERİLERİ yeni MySQL veritabanına aktarır. Şemanın (schema.sql)
 * MySQL tarafında ÖNCEDEN oluşturulmuş olması gerekir.
 *
 * KULLANIM (komut satırından):
 *   php migrate_from_sqlite.php /tam/yol/meda.db
 *
 * Notlar:
 *  - users tablosu BİLİNÇLİ OLARAK atlanır çünkü Flask/werkzeug parola
 *    hash'leri (scrypt) PHP tarafında doğrulanamaz. Kullanıcıları
 *    register.php üzerinden yeniden oluşturun ya da reset_legacy_passwords.php
 *    betiğini kullanın.
 *  - "personel*" tabloları schema.sql'de yok, bu yüzden atlanır.
 *  - Bu betik sadece CLI (komut satırı) üzerinden çalıştırılmak için
 *    tasarlanmıştır; web'den erişime KAPALI tutun / sunucuya yüklemeyin.
 */

if (php_sapi_name() !== 'cli') {
    die("Bu betik sadece komut satırından (CLI) çalıştırılmalıdır.\n");
}

require_once __DIR__ . '/../config/database.php'; // $pdo (MySQL) burada hazır olacak

$sqlitePath = $argv[1] ?? null;
if (!$sqlitePath || !file_exists($sqlitePath)) {
    die("Kullanım: php migrate_from_sqlite.php /tam/yol/meda.db\n");
}

$sqlite = new PDO('sqlite:' . $sqlitePath);
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Aktarılacak tablolar - foreign key sırasına dikkat edildi (önce bağımsız tablolar)
$tables = [
    'cariler',
    'urunler',
    'hesaplar',
    'teknik_servis',
    'servis_malzemeler',
    'faturalar',
    'fatura_detaylari',
    'todos',
    'hesap_hareketleri',
    'komisyon_hareketleri',
    'tahsilat_planlari',
    'tahsilat_taksitleri',
    'makbuzlar',
    'makbuz_detaylari',
    'teklifler',
    'teklif_detaylari',
];

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

foreach ($tables as $table) {
    echo "-> {$table} aktarılıyor... ";

    try {
        $rows = $sqlite->query("SELECT * FROM {$table}")->fetchAll();
    } catch (PDOException $e) {
        echo "ATLANDI (SQLite'da tablo bulunamadı)\n";
        continue;
    }

    if (empty($rows)) {
        echo "0 kayıt (boş)\n";
        continue;
    }

    $columns = array_keys($rows[0]);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $columnList = implode(', ', $columns);

    $insert = $pdo->prepare("INSERT INTO {$table} ({$columnList}) VALUES ({$placeholders})");

    $count = 0;
    foreach ($rows as $row) {
        $insert->execute(array_values($row));
        $count++;
    }

    echo "{$count} kayıt aktarıldı\n";
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "\nTamamlandı. users tablosu manuel olarak ele alınmalı (bkz. reset_legacy_passwords.php).\n";
