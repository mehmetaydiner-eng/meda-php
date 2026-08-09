<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_csrf(BASE_URL . '/makbuzlar.php');

$id = safe_int($_GET['id'] ?? null);
if (!$id) {
    header('Location: ' . BASE_URL . '/makbuzlar.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM makbuzlar WHERE id = ?');
$stmt->execute([$id]);
$makbuz = $stmt->fetch();
if (!$makbuz) {
    http_response_code(404);
    die('Makbuz bulunamadı.');
}

if ($makbuz['durum'] === 'İPTAL') {
    flash_set('Makbuz zaten iptal edilmiş!', 'warning');
    header('Location: ' . BASE_URL . '/makbuzlar.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM makbuz_detaylari WHERE makbuz_id = ?');
    $stmt->execute([$id]);
    $detaylar = $stmt->fetchAll();

    foreach ($detaylar as $detay) {
        if (!$detay['urun_id']) continue;
        $stmt2 = $pdo->prepare('SELECT stok_miktari FROM urunler WHERE id = ?');
        $stmt2->execute([$detay['urun_id']]);
        $urunStok = $stmt2->fetchColumn();
        if ($urunStok === false) continue;

        $stokOncesi = (float)$urunStok;
        if ($makbuz['makbuz_turu'] === 'SATIS') {
            $yeniStok = $stokOncesi + (float)$detay['miktar'];
            $hareketMiktar = (float)$detay['miktar'];
        } elseif ($makbuz['makbuz_turu'] === 'ALIS') {
            $yeniStok = $stokOncesi - (float)$detay['miktar'];
            $hareketMiktar = -(float)$detay['miktar'];
        } else {
            $yeniStok = $stokOncesi;
            $hareketMiktar = 0.0;
        }
        $pdo->prepare('UPDATE urunler SET stok_miktari = ? WHERE id = ?')->execute([$yeniStok, $detay['urun_id']]);

        if ($hareketMiktar !== 0.0) {
            stok_hareketi_ekle(
                $pdo, (int)$detay['urun_id'], 'İPTAL', $hareketMiktar,
                $stokOncesi, $yeniStok, $makbuz['makbuz_no'], "Makbuz İptali - {$makbuz['makbuz_no']}", $makbuz['cari_id']
            );
        }
    }

    if ($makbuz['cari_id']) {
        $stmt = $pdo->prepare('SELECT bakiye FROM cariler WHERE id = ?');
        $stmt->execute([$makbuz['cari_id']]);
        $cariBakiye = $stmt->fetchColumn();

        if ($cariBakiye !== false) {
            if (in_array($makbuz['makbuz_turu'], ['SATIS', 'TAHSILAT'], true)) {
                $yeniCariBakiye = (float)$cariBakiye + (float)$makbuz['genel_toplam'];
            } else {
                $yeniCariBakiye = (float)$cariBakiye - (float)$makbuz['genel_toplam'];
            }
            $pdo->prepare('UPDATE cariler SET bakiye = ? WHERE id = ?')->execute([$yeniCariBakiye, $makbuz['cari_id']]);
        }
    }

    if ($makbuz['hesap_id']) {
        $stmt = $pdo->prepare('SELECT bakiye FROM hesaplar WHERE id = ?');
        $stmt->execute([$makbuz['hesap_id']]);
        $hesapBakiye = $stmt->fetchColumn();

        if ($hesapBakiye !== false) {
            if (in_array($makbuz['makbuz_turu'], ['SATIS', 'TAHSILAT'], true)) {
                $yeniHesapBakiye = (float)$hesapBakiye - (float)$makbuz['genel_toplam'];
            } else {
                $yeniHesapBakiye = (float)$hesapBakiye + (float)$makbuz['genel_toplam'];
            }
            $pdo->prepare('UPDATE hesaplar SET bakiye = ? WHERE id = ?')->execute([$yeniHesapBakiye, $makbuz['hesap_id']]);
        }
    }

    $pdo->prepare("UPDATE makbuzlar SET durum = 'İPTAL', updated_at = datetime('now','localtime') WHERE id = ?")->execute([$id]);

    $pdo->commit();

    flash_set('Makbuz iptal edildi! No: ' . $makbuz['makbuz_no'], 'success');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash_set('İptal sırasında hata oluştu: ' . $e->getMessage(), 'danger');
}

header('Location: ' . BASE_URL . '/makbuzlar.php');
exit;
