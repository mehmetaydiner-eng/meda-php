<?php
/**
 * public/fatura_iptal.php
 *
 * NOT: Orijinal Flask uygulamasında fatura silme/iptal özelliği HİÇ YOKTU
 * (fatura_listesi.html'deki "SİL" butonu href="#" ile tamamen pasifti).
 * Burada gerçek bir "iptal" özelliği eklendi - ama BİLİNÇLİ OLARAK sadece
 * durum değişikliği yapıyor, stok/bakiye GERİ ALMIYOR. Sebebi:
 *
 * Bir fatura üç farklı yoldan oluşabiliyor ve her biri farklı yan etkiler
 * yaratıyor:
 *   - fatura_kaydet.php (Fatura Oluştur)      -> stok/bakiye HİÇ değişmez
 *   - fatura_alis_kaydet.php (Alış Faturası)  -> SADECE stok değişir
 *   - hizli_islem_yap.php (Hızlı İşlem)       -> stok + cari + hesap bakiyesi değişir
 *
 * Veritabanında faturanın hangi yoldan geldiğini kesin olarak ayırt eden bir
 * alan yok. Bu belirsizlikle "otomatik geri alma" yapmaya çalışmak, bazı
 * faturalarda stoku YANLIŞ YÖNE değiştirme riski taşır (örn. hiç stok
 * düşürmemiş bir faturayı iptal ederken stoku yanlışlıkla artırmak gibi).
 * Bu yüzden burada güvenli tarafta kalındı: fatura sadece "İPTAL" durumuna
 * alınır (Makbuz'daki gibi, kalıcı olarak silinmez, evrak numarası ve kayıt
 * geçmişi korunur). Stok/bakiye düzeltmesi gerekiyorsa, cari/ürün sayfasından
 * manuel bir "Hesap Hareketi" veya stok düzenlemesi eklenmesi önerilir.
 *
 * İSTİSNA (19 Temmuz 2026 - Efe'nin bulduğu hata): fatura_xml_kaydet.php
 * (XML'den içe aktarma) üzerinden gelen faturalar için BU BELİRSİZLİK
 * YOK - o kodun stok_miktari'nı nasıl artırdığını, cari.bakiye'yi nasıl
 * borçlandırdığını KESİN OLARAK biliyoruz (biz yazdık). Bu yüzden SADECE
 * bu tür faturalar için (gib_uuid dolu olması, o alanı sadece XML içe
 * aktarma dolduruyor) iptal edilince stok VE cari bakiyesi otomatik
 * olarak geri alınıyor. Diğer üç yoldan gelen faturalar için hâlâ hiçbir
 * otomatik geri alma yapılmıyor (yukarıdaki belirsizlik hâlâ geçerli).
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_csrf(BASE_URL . '/faturalar.php');

$id = safe_int($_GET['id'] ?? null);
if (!$id) {
    header('Location: ' . BASE_URL . '/faturalar.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM faturalar WHERE id = ?');
$stmt->execute([$id]);
$fatura = $stmt->fetch();
if (!$fatura) {
    http_response_code(404);
    die('Fatura bulunamadı.');
}

if ($fatura['durum'] === 'İPTAL') {
    flash_set('Fatura zaten iptal edilmiş!', 'warning');
    header('Location: ' . BASE_URL . '/faturalar.php');
    exit;
}

$xmlIleGeldi = !empty($fatura['gib_uuid']);
$geriAlmaNotu = 'Not: Stok/bakiye otomatik geri alınmadı (bkz. sayfa üstü bilgi notu), gerekirse manuel düzeltme yapın.';

try {
    $pdo->beginTransaction();

    $pdo->prepare("UPDATE faturalar SET durum = 'İPTAL', updated_at = datetime('now','localtime') WHERE id = ?")->execute([$id]);

    if ($xmlIleGeldi) {
        // ===== STOK GERİ ALMA =====
        $stmt = $pdo->prepare('SELECT * FROM fatura_detaylari WHERE fatura_id = ?');
        $stmt->execute([$id]);
        $detaylar = $stmt->fetchAll();

        foreach ($detaylar as $detay) {
            if (!$detay['urun_id']) continue;

            $stmt2 = $pdo->prepare('SELECT stok_miktari FROM urunler WHERE id = ?');
            $stmt2->execute([$detay['urun_id']]);
            $urun = $stmt2->fetch();
            if (!$urun) continue;

            $stokOncesi = (float)$urun['stok_miktari'];
            $miktar = (float)$detay['miktar'];
            // ALIŞ faturası stok EKLEMİŞTİ (+miktar) - iptalde bu geri
            // ÇIKARILIYOR. (fatura_turu her zaman ALIŞ olsa da, ileride
            // başka bir tür XML akışı eklenirse diye işaret kontrolü
            // korunuyor.)
            $degisim = $fatura['fatura_turu'] === 'ALIŞ' ? -$miktar : $miktar;
            $stokSonrasi = $stokOncesi + $degisim;

            $pdo->prepare('UPDATE urunler SET stok_miktari = ?, updated_at = datetime(\'now\',\'localtime\') WHERE id = ?')
                ->execute([$stokSonrasi, $detay['urun_id']]);

            stok_hareketi_ekle(
                $pdo, (int)$detay['urun_id'], 'İPTAL', $degisim, $stokOncesi, $stokSonrasi,
                $fatura['fatura_no'], "Fatura İptali (XML'den içe aktarılmıştı) - {$fatura['fatura_no']}",
                $fatura['cari_id'] ? (int)$fatura['cari_id'] : null
            );
        }

        // ===== CARİ BAKİYESİ GERİ ALMA =====
        // XML'den içe aktarma her zaman tam veresiye (kalan = genel
        // toplam) olarak kaydedip cari.bakiye'yi bu kadar artırıyordu -
        // iptalde bu kesin olarak biliniyor, doğrudan geri çıkarılabilir.
        if ($fatura['cari_id']) {
            $pdo->prepare('UPDATE cariler SET bakiye = bakiye - ? WHERE id = ?')
                ->execute([(float)$fatura['genel_toplam'], $fatura['cari_id']]);
        }

        $geriAlmaNotu = 'XML\'den içe aktarılmış bir fatura olduğu için stok ve cari bakiyesi otomatik olarak geri alındı.';
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash_set('Fatura iptal edilirken hata oluştu: ' . $e->getMessage(), 'danger');
    header('Location: ' . BASE_URL . '/faturalar.php');
    exit;
}

flash_set('Fatura iptal edildi! No: ' . $fatura['fatura_no'] . ' — ' . $geriAlmaNotu, 'success');
header('Location: ' . BASE_URL . '/faturalar.php');
exit;
