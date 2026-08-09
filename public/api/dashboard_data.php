<?php
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$user = current_user();

if ($user) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM todos WHERE user_id = ? AND status = 'Bekliyor'");
    $stmt->execute([$user['id']]);
    $bekleyen_todo = (int)$stmt->fetchColumn();
} else {
    $bekleyen_todo = 0;
}

$toplam_cari   = (int)$pdo->query('SELECT COUNT(*) FROM cariler')->fetchColumn();
$toplam_stok   = (int)$pdo->query('SELECT COUNT(*) FROM urunler')->fetchColumn();
$toplam_servis = (int)$pdo->query('SELECT COUNT(*) FROM teknik_servis')->fetchColumn();
$toplam_hesap  = (int)$pdo->query('SELECT COUNT(*) FROM hesaplar')->fetchColumn();
$toplam_makbuz = (int)$pdo->query('SELECT COUNT(*) FROM makbuzlar')->fetchColumn();
$toplam_teklif = (int)$pdo->query('SELECT COUNT(*) FROM teklifler')->fetchColumn();

// NOT (18 Temmuz 2026): Efe'nin bulduğu iki hata burada düzeltildi:
// 1) "toplam_personel" hiç sorgulanmıyordu, JS tarafı hep 0 gösteriyordu.
// 2) "hizli_satis" ise sabit "3" değeri döndürüyordu - bunu ORİJİNAL FLASK
//    UYGULAMASINDAN kalan bir yorum satırı da doğruluyordu ("Flask
//    tarafında da sabit değer olarak dönüyordu"). Artık gerçek bir toplam
//    satış sayısı (makbuz + fatura, SATIŞ türünde) hesaplanıyor.
$toplam_personel = (int)$pdo->query("SELECT COUNT(*) FROM cariler WHERE cari_turu = 'PERSONEL'")->fetchColumn();
$hizli_satis = (int)$pdo->query("SELECT COUNT(*) FROM makbuzlar WHERE makbuz_turu = 'SATIS'")->fetchColumn()
             + (int)$pdo->query("SELECT COUNT(*) FROM faturalar WHERE fatura_turu = 'SATIŞ'")->fetchColumn();

$kasa_bakiye = (float)($pdo->query(
    "SELECT SUM(bakiye) FROM hesaplar WHERE hesap_turu = 'KASA'"
)->fetchColumn() ?: 0);

// NOT: "Son Faturalar" ve "Son Servisler" tabloları, "Bugünün Özeti"
// kartlarıyla birlikte index.php'de tamamen STATİK (sabit) HTML olarak
// duruyordu - hiçbir gerçek veriye bağlı değildi ("Henüz fatura yok" /
// "Henüz servis kaydı yok" metinleri hep sabitti, üstteki 8 kartın aksine
// hiç AJAX ile güncellenmiyordu). Bu, Efe'nin bulduğu en önemli hatalardan
// biri - artık gerçek verilerle dolduruluyor.

$stmt = $pdo->query(
    "SELECT f.id, f.fatura_no, f.fatura_tarihi, f.genel_toplam, c.unvan AS cari_unvan
     FROM faturalar f
     LEFT JOIN cariler c ON c.id = f.cari_id
     ORDER BY f.created_at DESC, f.id DESC
     LIMIT 5"
);
$son_faturalar = $stmt->fetchAll();

$stmt = $pdo->query(
    "SELECT ts.id, ts.servis_no, ts.urun_adi, ts.durum, c.unvan AS cari_unvan
     FROM teknik_servis ts
     LEFT JOIN cariler c ON c.id = ts.cari_id
     ORDER BY ts.created_at DESC, ts.id DESC
     LIMIT 5"
);
$son_servisler = $stmt->fetchAll();

// "Bugünün Özeti": SQLite'ta DATE(sutun) = DATE('now','localtime') ile
// bugüne ait kayıtlar filtreleniyor (uygulamanın geri kalanında da
// kullanılan aynı yerel saat dilimi yaklaşımı).
$bugun_gelen_fatura = (int)$pdo->query(
    "SELECT COUNT(*) FROM faturalar WHERE fatura_turu = 'SATIŞ' AND DATE(created_at) = DATE('now','localtime')"
)->fetchColumn();
$bugun_giden_fatura = (int)$pdo->query(
    "SELECT COUNT(*) FROM faturalar WHERE fatura_turu = 'ALIŞ' AND DATE(created_at) = DATE('now','localtime')"
)->fetchColumn();
$bugun_servis_kaydi = (int)$pdo->query(
    "SELECT COUNT(*) FROM teknik_servis WHERE DATE(created_at) = DATE('now','localtime')"
)->fetchColumn();
$bugun_kasa_islem = (int)$pdo->query(
    "SELECT COUNT(*) FROM hesap_hareketleri WHERE DATE(hareket_tarihi) = DATE('now','localtime')"
)->fetchColumn();

echo json_encode([
    'yapilacaklar'    => $bekleyen_todo,
    'hizli_satis'     => $hizli_satis,
    'toplam_cari'     => $toplam_cari,
    'toplam_stok'     => $toplam_stok,
    'toplam_servis'   => $toplam_servis,
    'toplam_hesap'    => $toplam_hesap,
    'kasa_bakiye'     => $kasa_bakiye,
    'toplam_makbuz'   => $toplam_makbuz,
    'toplam_teklif'   => $toplam_teklif,
    'toplam_personel' => $toplam_personel,
    'son_faturalar'   => $son_faturalar,
    'son_servisler'   => $son_servisler,
    'bugun_ozet'      => [
        'gelen_fatura' => $bugun_gelen_fatura,
        'giden_fatura' => $bugun_giden_fatura,
        'servis_kaydi' => $bugun_servis_kaydi,
        'kasa_islem'   => $bugun_kasa_islem,
    ],
], JSON_UNESCAPED_UNICODE);
