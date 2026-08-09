<?php
/**
 * public/fatura_xml_onizleme.php
 *
 * Efe'nin isteği üzerine (19 Temmuz 2026): XML'den ayrıştırılan faturayı
 * kaydetmeden ÖNCE gösteren önizleme ekranı. Cari/ürün eşleşmeleri
 * burada açıkça işaretleniyor (yeni açılacak / güncellenecek / mevcut
 * kullanılacak), satış fiyatı gibi alanlar burada düzenlenebiliyor.
 * Hiçbir DB yazması bu sayfada olmuyor - "Kaydet" butonu
 * fatura_xml_kaydet.php'ye gönderiyor.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$veri = $_SESSION['xml_fatura_onizleme'] ?? null;
if (!$veri) {
    flash_set('Önizlenecek bir XML verisi yok - lütfen önce bir dosya yükleyin.', 'warning');
    header('Location: ' . BASE_URL . '/alis_fatura_olustur.php');
    exit;
}

$tedarikci = $veri['tedarikci'];
$fatura = $veri['fatura'];
$kalemler = $veri['kalemler'];
$iptalEdilmisEslesme = $veri['iptal_edilmis_eslesme'] ?? null;

// ===== CARİ EŞLEŞTİRME (sadece OKUMA - burada hiçbir yazma yok) =====
$mevcutCari = null;
if ($tedarikci['vkn'] !== '') {
    $stmt = $pdo->prepare('SELECT * FROM cariler WHERE vergi_no = ? LIMIT 1');
    $stmt->execute([$tedarikci['vkn']]);
    $mevcutCari = $stmt->fetch() ?: null;
}
$cariDurumu = 'yeni'; // yeni | guncellenecek | ayni
if ($mevcutCari) {
    $cariDurumu = (trim($mevcutCari['unvan']) !== trim($tedarikci['unvan'])) ? 'guncellenecek' : 'ayni';
}

// ===== ÜRÜN EŞLEŞTİRME (sadece OKUMA) =====
foreach ($kalemler as $i => $kalem) {
    $mevcutUrun = null;
    if ($kalem['stok_kodu'] !== '') {
        $stmt = $pdo->prepare('SELECT * FROM urunler WHERE urun_kodu = ? LIMIT 1');
        $stmt->execute([$kalem['stok_kodu']]);
        $mevcutUrun = $stmt->fetch() ?: null;
    }
    $kalemler[$i]['mevcut_urun'] = $mevcutUrun;
}

$page_title   = 'XML FATURA ÖNİZLEME';
$breadcrumb   = 'XML\'den İçe Aktarım Önizleme';
$current_page = 'faturalar';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">

        <div class="alert alert-info alert-kalici">
            <i class="fas fa-info-circle"></i>
            Bu, XML'den ayrıştırılan verinin <strong>önizlemesidir</strong> - henüz hiçbir şey
            kaydedilmedi. Aşağıdaki bilgileri kontrol edip gerekirse düzenleyin, sonra en alttaki
            <strong>"Kaydet"</strong> butonuna basın.
        </div>

        <!-- TEDARİKÇİ ÖNİZLEME -->
        <div class="card-custom mb-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-truck"></i> TEDARİKÇİ</h5>
                <?php if ($cariDurumu === 'yeni'): ?>
                    <span class="badge-status bg-success">YENİ CARİ AÇILACAK</span>
                <?php elseif ($cariDurumu === 'guncellenecek'): ?>
                    <span class="badge-status bg-warning">MEVCUT CARİ GÜNCELLENECEK</span>
                <?php else: ?>
                    <span class="badge-status bg-secondary">MEVCUT CARİ KULLANILACAK</span>
                <?php endif; ?>
            </div>
            <div class="card-body-custom">
                <?php if ($cariDurumu === 'guncellenecek'): ?>
                    <div class="alert alert-warning alert-kalici py-2" style="font-size:13px;">
                        Aynı VKN (<?= e($tedarikci['vkn']) ?>) ile kayıtlı cari
                        "<strong><?= e($mevcutCari['unvan']) ?></strong>" bulundu, ama unvanı
                        XML'dekinden farklı. Kaydedince bu carinin bilgileri aşağıdaki yeni
                        bilgilerle <strong>güncellenecek</strong>.
                    </div>
                <?php endif; ?>
                <input type="hidden" name="mevcut_cari_id" value="<?= $mevcutCari ? (int)$mevcutCari['id'] : '' ?>">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Ünvan</label>
                        <input type="text" name="cari_unvan" class="form-control" value="<?= e($tedarikci['unvan']) ?>" form="onizlemeForm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">VKN/TCKN</label>
                        <input type="text" name="cari_vkn" class="form-control" value="<?= e($tedarikci['vkn']) ?>" form="onizlemeForm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Vergi Dairesi</label>
                        <input type="text" name="cari_vergi_dairesi" class="form-control" value="<?= e($tedarikci['vergi_dairesi']) ?>" form="onizlemeForm">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Adres</label>
                        <input type="text" name="cari_adres" class="form-control" value="<?= e($tedarikci['adres']) ?>" form="onizlemeForm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="cari_telefon" class="form-control" value="<?= e($tedarikci['telefon']) ?>" form="onizlemeForm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">E-posta</label>
                        <input type="text" name="cari_email" class="form-control" value="<?= e($tedarikci['email']) ?>" form="onizlemeForm">
                    </div>
                </div>
            </div>
        </div>

        <!-- FATURA BİLGİLERİ -->
        <div class="card-custom mb-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-file-invoice"></i> FATURA BİLGİLERİ</h5>
            </div>
            <div class="card-body-custom">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label">Fatura No (tedarikçinin)</label>
                        <input type="text" class="form-control" value="<?= e($fatura['fatura_no']) ?>" disabled>
                        <small class="text-muted">Bizim sistemimiz kendi fatura numarasını oluşturacak, bu sadece referans.</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fatura Tarihi</label>
                        <input type="date" name="fatura_tarihi" class="form-control" value="<?= e($fatura['tarih']) ?>" form="onizlemeForm">
                        <small class="text-muted">Ürün/stok hareketlerine de bu tarih işlenecek.</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Para Birimi</label>
                        <input type="text" class="form-control" value="<?= e($fatura['para_birimi']) ?>" disabled>
                        <input type="hidden" name="para_birimi" value="<?= e($fatura['para_birimi']) ?>" form="onizlemeForm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">XML Genel Toplam</label>
                        <input type="text" class="form-control" value="<?= number_format($fatura['genel_toplam'], 2, ',', '.') ?>" disabled>
                    </div>
                </div>
            </div>
        </div>

        <!-- ÜRÜN KALEMLERİ -->
        <form id="onizlemeForm" method="POST" action="<?= BASE_URL ?>/fatura_xml_kaydet.php">
        <?= csrf_field() ?>
        <input type="hidden" name="kaynak_uuid" value="<?= e($fatura['uuid'] ?? '') ?>">
        <input type="hidden" name="kaynak_fatura_no" value="<?= e($fatura['fatura_no'] ?? '') ?>">

        <?php if ($iptalEdilmisEslesme): ?>
        <div class="alert alert-warning alert-kalici">
            <i class="fas fa-exclamation-triangle"></i>
            Bu fatura (aynı UUID/kaynak fatura no), sistemde <strong>İPTAL EDİLMİŞ</strong>
            bir kayıtla eşleşiyor (Fatura No: <strong><?= e($iptalEdilmisEslesme['fatura_no']) ?></strong>).
            Muhtemelen tedarikçi bu faturayı düzelttip yeniden gönderdi. Yine de yeni bir fatura
            olarak kaydetmek istediğinizden emin misiniz?
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="iptal_onay" value="1" id="iptalOnayKutusu" required>
                <label class="form-check-label" for="iptalOnayKutusu">
                    Evet, iptal edilmiş kayda rağmen bunu yeni bir fatura olarak kaydet.
                </label>
            </div>
        </div>
        <?php endif; ?>
        <div class="card-custom mb-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-boxes"></i> ÜRÜN KALEMLERİ (<?= count($kalemler) ?>)</h5>
            </div>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Durum</th>
                            <th>Stok Kodu</th>
                            <th>Ürün Adı</th>
                            <th class="text-end">Miktar</th>
                            <th class="text-end">Alış Fiyatı</th>
                            <th class="text-end">Satış Fiyatı (%30 öneri)</th>
                            <th class="text-end">KDV %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kalemler as $i => $kalem): ?>
                        <tr>
                            <td>
                                <?php if ($kalem['mevcut_urun']): ?>
                                    <span class="badge-status bg-warning" style="font-size:9px;">EŞLEŞTİ - GÜNCELLENECEK</span>
                                <?php else: ?>
                                    <span class="badge-status bg-success" style="font-size:9px;">YENİ ÜRÜN</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="text" name="stok_kodu[]" class="form-control form-control-sm" value="<?= e($kalem['stok_kodu']) ?>">
                                <input type="hidden" name="mevcut_urun_id[]" value="<?= $kalem['mevcut_urun'] ? (int)$kalem['mevcut_urun']['id'] : '' ?>">
                            </td>
                            <td><input type="text" name="urun_adi[]" class="form-control form-control-sm" value="<?= e($kalem['urun_adi']) ?>"></td>
                            <td><input type="number" name="miktar[]" class="form-control form-control-sm text-end" value="<?= e((string)$kalem['miktar']) ?>" step="0.01"></td>
                            <td><input type="number" name="alis_fiyati[]" class="form-control form-control-sm text-end" value="<?= e((string)$kalem['birim_fiyat']) ?>" step="0.01"></td>
                            <td><input type="number" name="satis_fiyati[]" class="form-control form-control-sm text-end" value="<?= e((string)$kalem['onerilen_satis_fiyati']) ?>" step="0.01"></td>
                            <td><input type="number" name="kdv_orani[]" class="form-control form-control-sm text-end" value="<?= e((string)$kalem['kdv_orani']) ?>" step="0.01"></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-between mb-4">
            <a href="<?= BASE_URL ?>/alis_fatura_olustur.php" class="btn btn-outline-secondary">
                <i class="fas fa-times"></i> VAZGEÇ
            </a>
            <button type="submit" class="btn btn-success-custom">
                <i class="fas fa-check"></i> KAYDET
            </button>
        </div>
        </form>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
