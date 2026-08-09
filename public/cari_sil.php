<?php
/**
 * public/cari_sil.php
 *
 * NOT (19 Temmuz 2026 - Efe'nin bulduğu hata): Önceden bu sayfa, cariye
 * ait HERHANGİ bir fatura varsa (iptal edilmiş olsa bile) silmeyi
 * engelliyordu - bu yüzden tüm faturaları iptal edilmiş bir cari bile
 * silinemiyordu. Ayrıca `cariler` tablosuna, faturalar/stok_hareketleri
 * gibi BİRÇOK tablodan foreign key bağlantısı var - sadece "kaç fatura
 * var" kontrolü yapıp veritabanının kendisine silme komutunu vermek,
 * iptal edilmiş bir faturanın hâlâ cari_id ile bağlı olması yüzünden
 * "foreign key constraint failed" hatasına yol açıyordu.
 *
 * Yeni mantık:
 * 1) Fatura/Makbuz: sadece AKTİF (İPTAL EDİLMEMİŞ) olanlar varsa engelle.
 * 2) Teklif/Teknik Servis/Tahsilat Planı/Komisyon Hareketi: bunların
 *    "iptal" kavramı net modellenmediği için, herhangi bir tanesi varsa
 *    hâlâ engelleniyor (gerçek iş verisini kaybetme riski almıyoruz).
 * 3) Yukarıdaki kontrolleri geçtiyse (yani geriye sadece İPTAL edilmiş
 *    fatura/makbuz ve bunlara ait stok/hesap hareketi logları kalmışsa),
 *    bunların hepsinin cari_id bağlantısı NULL'a çekilip (evrak
 *    numarası/kayıt geçmişi korunuyor, sadece hangi cariye ait olduğu
 *    bilgisi kayboluyor) cari güvenle siliniyor.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_csrf(BASE_URL . '/cariler.php');

$id = safe_int($_GET['id'] ?? null);
if (!$id) {
    header('Location: ' . BASE_URL . '/cariler.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM cariler WHERE id = ?');
$stmt->execute([$id]);
$cari = $stmt->fetch();

if (!$cari) {
    http_response_code(404);
    die('Cari bulunamadı.');
}

// ===== 1. AKTİF FATURA / MAKBUZ KONTROLÜ =====
$stmt = $pdo->prepare("SELECT COUNT(*) FROM faturalar WHERE cari_id = ? AND durum != 'İPTAL'");
$stmt->execute([$id]);
if ((int)$stmt->fetchColumn() > 0) {
    flash_set($cari['unvan'] . ' silinemez! Bu cariye ait aktif (iptal edilmemiş) faturalar var.', 'danger');
    header('Location: ' . BASE_URL . '/cariler.php');
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM makbuzlar WHERE cari_id = ? AND durum != 'İPTAL'");
$stmt->execute([$id]);
if ((int)$stmt->fetchColumn() > 0) {
    flash_set($cari['unvan'] . ' silinemez! Bu cariye ait aktif (iptal edilmemiş) makbuzlar var.', 'danger');
    header('Location: ' . BASE_URL . '/cariler.php');
    exit;
}

// ===== 2. DİĞER İŞ VERİLERİ - hâlâ tamamen engelliyor =====
$digerTablolar = [
    'teklifler'             => 'teklifler',
    'teknik_servis'         => 'teknik servis kayıtları',
    'tahsilat_planlari'     => 'tahsilat planları',
    'komisyon_hareketleri'  => 'komisyon/prim hareketleri',
];
foreach ($digerTablolar as $tablo => $etiket) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$tablo} WHERE cari_id = ?");
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        flash_set($cari['unvan'] . " silinemez! Bu cariye ait {$etiket} var.", 'danger');
        header('Location: ' . BASE_URL . '/cariler.php');
        exit;
    }
}

// ===== 3. Buraya kadar geldiyse: geriye sadece İPTAL edilmiş
// fatura/makbuz ve bunlara ait stok/hesap hareketi logları kalmış demektir.
// Bunların cari bağlantısını koparıp (kayıtların kendisi silinmiyor,
// sadece hangi cariye ait olduğu bilgisi kayboluyor) cariyi güvenle
// silebiliriz. =====
$pdo->prepare("UPDATE faturalar SET cari_id = NULL WHERE cari_id = ? AND durum = 'İPTAL'")->execute([$id]);
$pdo->prepare("UPDATE makbuzlar SET cari_id = NULL WHERE cari_id = ? AND durum = 'İPTAL'")->execute([$id]);
$pdo->prepare('UPDATE stok_hareketleri SET cari_id = NULL WHERE cari_id = ?')->execute([$id]);
$pdo->prepare('UPDATE hesap_hareketleri SET cari_id = NULL WHERE cari_id = ?')->execute([$id]);

$delete = $pdo->prepare('DELETE FROM cariler WHERE id = ?');
$delete->execute([$id]);

flash_set($cari['unvan'] . ' silindi!', 'success');
header('Location: ' . BASE_URL . '/cariler.php');
exit;
