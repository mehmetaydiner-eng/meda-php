<?php
/**
 * public/teklif_olustur.php
 * Teklif oluşturma ve düzenleme ekranı (index.php stiliyle uyumlu).
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/numara_manager.php';
require_login();

$teklif_id = safe_int($_GET['id'] ?? null, 0) ?: null;
$teklif = null;
$detaylar = [];

if ($teklif_id) {
    $stmt = $pdo->prepare('SELECT * FROM teklifler WHERE id = ?');
    $stmt->execute([$teklif_id]);
    $teklif = $stmt->fetch();
    if (!$teklif) {
        http_response_code(404);
        die('Teklif bulunamadı.');
    }
    if ($teklif['cari_id']) {
        $stmt = $pdo->prepare('SELECT * FROM cariler WHERE id = ?');
        $stmt->execute([$teklif['cari_id']]);
        $teklif['cari'] = $stmt->fetch() ?: null;
    } else {
        $teklif['cari'] = null;
    }
    $stmt = $pdo->prepare('SELECT * FROM teklif_detaylari WHERE teklif_id = ?');
    $stmt->execute([$teklif_id]);
    $detaylar = $stmt->fetchAll();
}

$cariler = $pdo->query('SELECT * FROM cariler ORDER BY unvan')->fetchAll();

$page_title   = $teklif ? 'TEKLİF DÜZENLE' : 'TEKLİF OLUŞTUR';
$breadcrumb   = $teklif ? 'Teklif Düzenle' : 'Yeni Teklif';
$current_page = 'teklifler';
// extra_css kaldırıldı, artık ortak stil dosyaları yeterli.
require_once __DIR__ . '/../includes/header.php';

$now = new DateTime('now');
$default_teklif_no = $teklif ? $teklif['teklif_no'] : generate_teklif_no_nm($pdo, 'VERILEN');
$default_tarih = $teklif ? (new DateTime($teklif['teklif_tarihi']))->format('Y-m-d') : $now->format('Y-m-d');
$default_gecerlilik = $teklif && $teklif['gecerlilik_tarihi'] ? (new DateTime($teklif['gecerlilik_tarihi']))->format('Y-m-d') : $now->modify('+1 month')->format('Y-m-d');
$default_teslim = $teklif && $teklif['teslim_tarihi'] ? (new DateTime($teklif['teslim_tarihi']))->format('Y-m-d') : '';
?>

<div class="container mt-4">
    <!-- ===== KART: BAŞLIK BİLGİLERİ ===== -->
    <div class="card-custom mb-4">
        <div class="card-header-custom">
            <h5><i class="fas fa-file-signature"></i> <?= $teklif ? 'Teklif Düzenle' : 'Yeni Teklif' ?></h5>
        </div>
        <div class="p-3">
            <div class="row g-3">
                <!-- Sol: Firma Bilgileri -->
                <div class="col-md-6">
                    <div style="border-right: 2px solid #dee2e6; padding-right: 20px;">
                        <strong>MEDA BİLGİSAYAR</strong>
                        <p class="text-muted small" style="margin:4px 0;">82.Sk. Yenice Apt: No:16 07040 Kızılsaray Mh. / Muratpaşa / Antalya</p>
                        <div style="display:flex; gap:15px; flex-wrap:wrap; font-size:12px;">
                            <span><i class="fas fa-phone"></i> 0 (242) 247 6699</span>
                            <span><i class="fas fa-globe"></i> www.medabilgisayar.net</span>
                        </div>
                    </div>
                </div>
                <!-- Sağ: Teklif Bilgileri -->
                <div class="col-md-6">
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <label class="form-label small">Teklif No</label>
                            <input type="text" id="teklif-no" class="form-control form-control-sm" value="<?= e($default_teklif_no) ?>" readonly style="background:#f1f3f5; font-weight:bold;">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small">Tarih</label>
                            <input type="date" id="teklif-tarihi" class="form-control form-control-sm" value="<?= $default_tarih ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small">Geçerlilik Tarihi</label>
                            <input type="date" id="gecerlilik-tarihi" class="form-control form-control-sm" value="<?= $default_gecerlilik ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small">Teslim Tarihi</label>
                            <input type="date" id="teslim-tarihi" class="form-control form-control-sm" value="<?= $default_teslim ?>">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small">Teklif Türü</label>
                            <select id="teklif-turu" class="form-select form-select-sm">
                                <option value="VERILEN" <?= (!$teklif || $teklif['teklif_turu'] === 'VERILEN') ? 'selected' : '' ?>>VERILEN</option>
                                <option value="ALINAN" <?= ($teklif && $teklif['teklif_turu'] === 'ALINAN') ? 'selected' : '' ?>>ALINAN</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small">Tip</label>
                            <select id="teklif-tipi" class="form-select form-select-sm">
                                <option value="STANDART" <?= (!$teklif || $teklif['teklif_tipi'] === 'STANDART') ? 'selected' : '' ?>>STANDART</option>
                                <option value="HİZMET" <?= ($teklif && $teklif['teklif_tipi'] === 'HİZMET') ? 'selected' : '' ?>>HİZMET</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small">Para Birimi</label>
                            <select id="teklif-para-birimi" class="form-select form-select-sm">
                                <option value="TRY" <?= (!$teklif || $teklif['para_birimi'] === 'TRY') ? 'selected' : '' ?>>TRY (₺)</option>
                                <option value="USD" <?= ($teklif && $teklif['para_birimi'] === 'USD') ? 'selected' : '' ?>>USD ($)</option>
                                <option value="EUR" <?= ($teklif && $teklif['para_birimi'] === 'EUR') ? 'selected' : '' ?>>EUR (€)</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small">Durum</label>
                            <select id="teklif-durum" class="form-select form-select-sm">
                                <option value="TASLAK" <?= (!$teklif || $teklif['durum'] === 'TASLAK') ? 'selected' : '' ?>>TASLAK</option>
                                <option value="BEKLEMEDE" <?= ($teklif && $teklif['durum'] === 'BEKLEMEDE') ? 'selected' : '' ?>>BEKLEMEDE</option>
                                <option value="ONAYLANDI" <?= ($teklif && $teklif['durum'] === 'ONAYLANDI') ? 'selected' : '' ?>>ONAYLANDI</option>
                                <option value="REDDEDILDI" <?= ($teklif && $teklif['durum'] === 'REDDEDILDI') ? 'selected' : '' ?>>REDDEDILDI</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== KART: MÜŞTERİ BİLGİLERİ ===== -->
    <div class="card-custom mb-4">
        <div class="card-header-custom">
            <h5><i class="fas fa-user"></i> Müşteri / Tedarikçi</h5>
        </div>
        <div class="p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small">Cari Seçin</label>
                    <select id="cari-select" class="form-select form-select-sm" onchange="cariSec(this.value)">
                        <option value="">Seçin...</option>
                        <?php foreach ($cariler as $cari): ?>
                        <option value="<?= (int)$cari['id'] ?>" <?= ($teklif && $teklif['cari_id'] == $cari['id']) ? 'selected' : '' ?>>
                            <?= e($cari['unvan']) ?> (<?= e($cari['vergi_no'] ?: 'VKN YOK') ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Ünvan</label>
                    <input type="text" id="alici-unvan" class="form-control form-control-sm" value="<?= e($teklif['cari']['unvan'] ?? '') ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Adres</label>
                    <span id="alici-adres" class="d-block py-1"><?= e($teklif['cari']['adres'] ?? '-') ?></span>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Vergi Dairesi / No</label>
                    <span id="alici-vkn" class="d-block py-1"><?= e($teklif['cari']['vergi_dairesi'] ?? '-') ?> / <?= e($teklif['cari']['vergi_no'] ?? '-') ?></span>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Telefon</label>
                    <span id="alici-tel" class="d-block py-1"><?= e($teklif['cari']['telefon'] ?? '-') ?></span>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">E-Posta</label>
                    <span id="alici-email" class="d-block py-1"><?= e($teklif['cari']['email'] ?? '-') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== KART: KALEMLER TABLOSU ===== -->
    <div class="card-custom mb-4">
        <div class="card-header-custom">
            <h5><i class="fas fa-list"></i> Teklif Kalemleri</h5>
        </div>
        <div class="p-0">
            <div class="table-responsive">
                <table class="table-custom" id="teklif-kalemleri">
                    <thead>
                        <tr>
                            <th style="width:35px;" class="text-center">#</th>
                            <th>Mal / Hizmet Tanımı</th>
                            <th style="width:10%;" class="text-center">Miktar</th>
                            <th style="width:15%;" class="text-end">Birim Fiyat</th>
                            <th style="width:10%;" class="text-center">İskonto %</th>
                            <th style="width:10%;" class="text-center">KDV %</th>
                            <th style="width:18%;" class="text-end">Toplam</th>
                            <th style="width:35px;" class="text-center">İşlem</th>
                        </tr>
                    </thead>
                    <tbody id="teklif-kalem-tbody">
                        <?php if ($detaylar): ?>
                            <?php foreach ($detaylar as $i => $detay): ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td><input type="text" class="form-control form-control-sm urun-adi-input" value="<?= e($detay['urun_adi']) ?>"></td>
                                <td class="text-center"><input type="number" class="form-control form-control-sm miktar-input" value="<?= number_format((float)$detay['miktar'], 0, '.', '') ?>" min="0.01" step="0.01" style="width:70px; display:inline-block; text-align:center;" onchange="satirGuncelle(this)"></td>
                                <td class="text-end"><input type="number" class="form-control form-control-sm fiyat-input" value="<?= number_format((float)$detay['birim_fiyati'], 2, '.', '') ?>" min="0" step="0.01" style="width:100px; display:inline-block; text-align:right;" onchange="satirGuncelle(this)"></td>
                                <td class="text-center"><input type="number" class="form-control form-control-sm iskonto-input" value="<?= number_format((float)$detay['iskonto'], 2, '.', '') ?>" min="0" max="100" step="0.5" style="width:60px; display:inline-block; text-align:center;" onchange="satirGuncelle(this)"></td>
                                <td class="text-center"><input type="number" class="form-control form-control-sm kdv-input" value="<?= number_format((float)($detay['vergi_orani'] ?: 18), 0, '.', '') ?>" min="0" max="100" step="0.5" style="width:60px; display:inline-block; text-align:center;" onchange="satirGuncelle(this)"></td>
                                <td class="text-end"><strong class="satir-toplam"><?= number_format((float)$detay['toplam_tutar'], 2, '.', '') ?></strong></td>
                                <td class="text-center">
                                    <button class="btn btn-outline-danger btn-sm" onclick="satirSil(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr id="bos-kalem">
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-plus-circle fa-2x d-block mb-2"></i>
                                    Ürün eklemek için aşağıdaki formu kullanın
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== TOPLAM KARTI ===== -->
    <div class="card-custom mb-4">
        <div class="card-header-custom">
            <h5><i class="fas fa-calculator"></i> Özet</h5>
        </div>
        <div class="p-3">
            <div class="row justify-content-end">
                <div class="col-md-4">
                    <div class="border rounded p-3">
                        <div class="d-flex justify-content-between"><span>Ara Toplam</span><strong id="ara-toplam"><?= number_format((float)($teklif['ara_toplam'] ?? 0), 2, '.', '') ?></strong></div>
                        <div class="d-flex justify-content-between"><span>Toplam İskonto</span><strong id="iskonto-tutari"><?= number_format((float)($teklif['iskonto_tutari'] ?? 0), 2, '.', '') ?></strong></div>
                        <div class="d-flex justify-content-between"><span>Toplam KDV</span><strong id="vergi-tutari"><?= number_format((float)($teklif['vergi_tutari'] ?? 0), 2, '.', '') ?></strong></div>
                        <hr>
                        <div class="d-flex justify-content-between fs-5 fw-bold"><span>Genel Toplam</span><span id="genel-toplam"><?= number_format((float)($teklif['genel_toplam'] ?? 0), 2, '.', '') ?></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== ÜRÜN EKLEME KARTI ===== -->
    <div class="card-custom mb-4 no-print">
        <div class="card-header-custom">
            <h5><i class="fas fa-plus-circle"></i> Kalem Ekle</h5>
        </div>
        <div class="p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Ürün Arama</label>
                    <input type="text" id="urun-ara" class="form-control form-control-sm" placeholder="Ürün adı veya barkod...">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-primary-custom btn-sm w-100" onclick="urunAra()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Seçilen Ürün</label>
                    <select id="urun-listesi" class="form-select form-select-sm" onchange="urunSec(this)">
                        <option value="">Seçin...</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Miktar</label>
                    <input type="number" id="urun-miktar" class="form-control form-control-sm" value="1" min="0.01" step="0.01">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Fiyat</label>
                    <input type="number" id="urun-fiyat" class="form-control form-control-sm" step="0.01" value="0">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">İsk. %</label>
                    <input type="number" id="urun-iskonto" class="form-control form-control-sm" value="0" min="0" max="100" step="1">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">KDV %</label>
                    <input type="number" id="urun-kdv" class="form-control form-control-sm" value="18" min="0" max="100" step="1">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-success-custom btn-sm w-100" onclick="kalemEkle()">
                        <i class="fas fa-plus"></i> EKLE
                    </button>
                </div>
            </div>
            <div class="mt-3">
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#yeniUrunModal">
                    <i class="fas fa-box"></i> Listede Olmayan Yeni Ürün Ekle
                </button>
            </div>
        </div>
    </div>

    <!-- ===== KONU / NOTLAR / KAYDET ===== -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card-custom h-100">
                <div class="card-header-custom">
                    <h5><i class="fas fa-pencil-alt"></i> Teklif Konusu</h5>
                </div>
                <div class="p-3">
                    <input type="text" id="teklif-konu" class="form-control form-control-sm" placeholder="Teklif konusu..." value="<?= e($teklif['konu'] ?? '') ?>" required>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-custom h-100">
                <div class="card-header-custom">
                    <h5><i class="fas fa-sticky-note"></i> Açıklama / Şartlar</h5>
                </div>
                <div class="p-3">
                    <textarea id="teklif-aciklama" class="form-control form-control-sm" rows="3" placeholder="Ödeme koşulları, teslimat süresi, vb..."><?= e($teklif['aciklama'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card-custom">
                <div class="card-header-custom">
                    <h5><i class="fas fa-comment"></i> İç Notlar</h5>
                </div>
                <div class="p-3">
                    <textarea id="teklif-notlar" class="form-control form-control-sm" rows="2" placeholder="Sadece personelin görebileceği notlar..."><?= e($teklif['notlar'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== BUTONLAR ===== -->
    <div class="d-flex gap-2 justify-content-end mb-4 no-print">
        <button class="btn btn-success-custom" onclick="teklifKaydet()">
            <i class="fas fa-save"></i> KAYDET
        </button>
        <a href="<?= BASE_URL ?>/teklifler.php" class="btn btn-outline-secondary">İPTAL</a>
    </div>
</div>

<!-- ===== YENİ ÜRÜN MODAL (Bootstrap ile uyumlu) ===== -->
<div class="modal fade" id="yeniUrunModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-box"></i> HIZLI ÜRÜN EKLE</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Ürün Kodu <span class="text-danger">*</span></label>
                        <input type="text" id="modal_urun_kodu" class="form-control" placeholder="PR-X">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ürün Adı <span class="text-danger">*</span></label>
                        <input type="text" id="modal_urun_adi" class="form-control" placeholder="Örn: 24 Port Switch">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Barkod</label>
                        <div class="input-group">
                            <input type="text" id="modal_barkod" class="form-control" placeholder="Boş ise otomatik üretilir">
                            <button class="btn btn-outline-primary" type="button" onclick="modalBarkodOlustur()"><i class="fas fa-barcode"></i></button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Satış Fiyatı</label>
                        <input type="number" id="modal_satis_fiyati" class="form-control" value="0" step="0.01">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Döviz</label>
                        <select id="modal_satis_doviz" class="form-select">
                            <option value="TL">TL</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Alış Fiyatı</label>
                        <input type="number" id="modal_alis_fiyati" class="form-control" value="0" step="0.01">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Döviz</label>
                        <select id="modal_alis_doviz" class="form-select">
                            <option value="TL">TL</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kategori</label>
                        <input type="text" id="modal_kategori" class="form-control" placeholder="AĞ ÜRÜNLERİ">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Birim</label>
                        <select id="modal_birim" class="form-select">
                            <option value="ADET">ADET</option>
                            <option value="METRE">METRE</option>
                            <option value="SAAT">SAAT</option>
                            <option value="PAKET">PAKET</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Durum</label>
                        <select id="modal_urun_tipi" class="form-select">
                            <option value="SIFIR">SIFIR</option>
                            <option value="2.EL">2.EL</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Başlangıç Stok</label>
                        <input type="number" id="modal_stok_miktari" class="form-control" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Min. Stok</label>
                        <input type="number" id="modal_min_stok" class="form-control" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Max. Stok</label>
                        <input type="number" id="modal_max_stok" class="form-control" value="0">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Seri No / Detay</label>
                        <input type="text" id="modal_seri_no" class="form-control" placeholder="SN-1234">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Açıklama</label>
                        <textarea id="modal_aciklama" class="form-control" rows="2" placeholder="Ürün açıklaması..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İPTAL</button>
                <button type="button" class="btn btn-success-custom" onclick="modalUrunKaydet()">ÜRÜNÜ EKLE</button>
            </div>
        </div>
    </div>
</div>

<?php
$teklif_id_json = json_encode($teklif_id);
$api_base_json  = json_encode(BASE_URL);
$extra_js = <<<JS
<script>
var TEKLIF_ID = {$teklif_id_json};
var API_BASE = {$api_base_json};

function cariSec(id) {
    if (!id) {
        document.getElementById('alici-unvan').value = '';
        document.getElementById('alici-adres').textContent = '-';
        document.getElementById('alici-vkn').textContent = '-';
        document.getElementById('alici-tel').textContent = '-';
        document.getElementById('alici-email').textContent = '-';
        return;
    }
    fetch(API_BASE + '/api/cari_detay.php?id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) {
                alert(data.error);
                return;
            }
            document.getElementById('alici-unvan').value = data.unvan || '';
            document.getElementById('alici-adres').textContent = data.adres || '-';
            document.getElementById('alici-vkn').textContent = (data.vergi_dairesi || '-') + ' / ' + (data.vergi_no || '-');
            document.getElementById('alici-tel').textContent = data.telefon || '-';
            document.getElementById('alici-email').textContent = data.email || '-';
        });
}

function urunAra() {
    var q = document.getElementById('urun-ara').value.trim();
    if (!q) return;

    fetch(API_BASE + '/api/stok_ara.php?q=' + encodeURIComponent(q))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var select = document.getElementById('urun-listesi');
            select.innerHTML = '<option value="">Seçin...</option>';
            data.forEach(function(u) {
                var opt = document.createElement('option');
                opt.value = u.id;
                opt.dataset.ad = u.urun_adi;
                opt.dataset.fiyat = u.satis_fiyati;
                opt.text = u.urun_adi + ' (' + u.satis_fiyati + ' ' + u.satis_fiyati_doviz + ')';
                select.appendChild(opt);
            });
            if (data.length > 0) {
                select.focus();
            }
        });
}

function urunSec(el) {
    var opt = el.options[el.selectedIndex];
    if (!opt || !opt.value) return;
    document.getElementById('urun-fiyat').value = opt.dataset.fiyat || 0;
}

function kalemEkle() {
    var select = document.getElementById('urun-listesi');
    var opt = select.options[select.selectedIndex];
    
    var urunAd = '';
    if (opt && opt.value) {
        urunAd = opt.dataset.ad;
    } else {
        urunAd = document.getElementById('urun-ara').value.trim();
    }

    if (!urunAd) {
        alert('Lütfen eklenecek ürünün adını yazın veya aramadan seçin!');
        return;
    }

    var miktar = parseFloat(document.getElementById('urun-miktar').value) || 1;
    var fiyat = parseFloat(document.getElementById('urun-fiyat').value) || 0;
    var iskonto = parseFloat(document.getElementById('urun-iskonto').value) || 0;
    var kdv = parseFloat(document.getElementById('urun-kdv').value) || 18;

    var tbody = document.getElementById('teklif-kalem-tbody');
    var bos = document.getElementById('bos-kalem');
    if (bos) bos.remove();

    var sira = tbody.querySelectorAll('tr').length + 1;
    var tr = document.createElement('tr');
    
    var satirToplam = miktar * fiyat;
    var iskontoTutar = satirToplam * (iskonto / 100);
    var matrah = satirToplam - iskontoTutar;
    var kdvTutar = matrah * (kdv / 100);
    var toplam = matrah + kdvTutar;

    tr.innerHTML = 
        '<td class="text-center">' + sira + '</td>' +
        '<td><input type="text" class="form-control form-control-sm urun-adi-input" value="' + urunAd.toUpperCase() + '"></td>' +
        '<td class="text-center"><input type="number" class="form-control form-control-sm miktar-input" value="' + miktar + '" min="0.01" step="0.01" style="width:70px; display:inline-block; text-align:center;" onchange="satirGuncelle(this)"></td>' +
        '<td class="text-end"><input type="number" class="form-control form-control-sm fiyat-input" value="' + fiyat.toFixed(2) + '" min="0" step="0.01" style="width:100px; display:inline-block; text-align:right;" onchange="satirGuncelle(this)"></td>' +
        '<td class="text-center"><input type="number" class="form-control form-control-sm iskonto-input" value="' + iskonto + '" min="0" max="100" step="0.5" style="width:60px; display:inline-block; text-align:center;" onchange="satirGuncelle(this)"></td>' +
        '<td class="text-center"><input type="number" class="form-control form-control-sm kdv-input" value="' + kdv + '" min="0" max="100" step="0.5" style="width:60px; display:inline-block; text-align:center;" onchange="satirGuncelle(this)"></td>' +
        '<td class="text-end"><strong class="satir-toplam">' + toplam.toFixed(2) + '</strong></td>' +
        '<td class="text-center"><button class="btn btn-outline-danger btn-sm" onclick="satirSil(this)"><i class="fas fa-times"></i></button></td>';

    tbody.appendChild(tr);
    
    // Formu temizle
    document.getElementById('urun-ara').value = '';
    document.getElementById('urun-listesi').innerHTML = '<option value="">Seçin...</option>';
    document.getElementById('urun-miktar').value = '1';
    document.getElementById('urun-fiyat').value = '0';
    document.getElementById('urun-iskonto').value = '0';
    
    hesaplaToplam();
}

function satirSil(btn) {
    btn.closest('tr').remove();
    
    var tbody = document.getElementById('teklif-kalem-tbody');
    var rows = tbody.querySelectorAll('tr');
    if (rows.length === 0) {
        tbody.innerHTML = '<tr id="bos-kalem"><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-plus-circle fa-2x d-block mb-2"></i> Ürün eklemek için aşağıdaki formu kullanın</td></tr>';
    } else {
        rows.forEach(function(row, idx) {
            row.cells[0].textContent = idx + 1;
        });
    }
    hesaplaToplam();
}

function satirGuncelle(input) {
    var tr = input.closest('tr');
    var miktar = parseFloat(tr.querySelector('.miktar-input').value) || 0;
    var fiyat = parseFloat(tr.querySelector('.fiyat-input').value) || 0;
    var iskonto = parseFloat(tr.querySelector('.iskonto-input').value) || 0;
    var kdv = parseFloat(tr.querySelector('.kdv-input').value) || 18;

    var satirToplam = miktar * fiyat;
    var iskontoTutar = satirToplam * (iskonto / 100);
    var matrah = satirToplam - iskontoTutar;
    var kdvTutar = matrah * (kdv / 100);
    var toplam = matrah + kdvTutar;

    tr.querySelector('.satir-toplam').textContent = toplam.toFixed(2);
    hesaplaToplam();
}

function hesaplaToplam() {
    var araToplam = 0.0;
    var toplamIskonto = 0.0;
    var toplamKdv = 0.0;

    document.querySelectorAll('#teklif-kalem-tbody tr').forEach(function(row) {
        if (row.id === 'bos-kalem') return;
        var miktar = parseFloat(row.querySelector('.miktar-input').value) || 0;
        var fiyat = parseFloat(row.querySelector('.fiyat-input').value) || 0;
        var iskonto = parseFloat(row.querySelector('.iskonto-input').value) || 0;
        var kdv = parseFloat(row.querySelector('.kdv-input').value) || 18;

        var satirToplam = miktar * fiyat;
        var iskontoTutar = satirToplam * (iskonto / 100);
        var matrah = satirToplam - iskontoTutar;
        var kdvTutar = matrah * (kdv / 100);

        araToplam += satirToplam;
        toplamIskonto += iskontoTutar;
        toplamKdv += kdvTutar;
    });

    var genelToplam = araToplam - toplamIskonto + toplamKdv;

    document.getElementById('ara-toplam').textContent = araToplam.toFixed(2);
    document.getElementById('iskonto-tutari').textContent = toplamIskonto.toFixed(2);
    document.getElementById('vergi-tutari').textContent = toplamKdv.toFixed(2);
    document.getElementById('genel-toplam').textContent = genelToplam.toFixed(2);
}

function teklifKaydet() {
    var rows = document.querySelectorAll('#teklif-kalem-tbody tr');
    if (rows.length === 0 || (rows.length === 1 && rows[0].id === 'bos-kalem')) {
        alert('Lütfen en az bir kalem ekleyin!');
        return;
    }

    var cariId = document.getElementById('cari-select').value;
    if (!cariId) {
        alert('Lütfen bir müşteri (cari) seçin!');
        return;
    }

    var konu = document.getElementById('teklif-konu').value.trim();
    if (!konu) {
        alert('Lütfen teklif konusunu yazın!');
        return;
    }

    var formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    if (TEKLIF_ID) formData.append('teklif_id', TEKLIF_ID);
    formData.append('teklif_no', document.getElementById('teklif-no').value);
    formData.append('teklif_tarihi', document.getElementById('teklif-tarihi').value);
    formData.append('gecerlilik_tarihi', document.getElementById('gecerlilik-tarihi').value);
    formData.append('teslim_tarihi', document.getElementById('teslim-tarihi').value);
    formData.append('teklif_turu', document.getElementById('teklif-turu').value);
    formData.append('teklif_tipi', document.getElementById('teklif-tipi').value);
    formData.append('para_birimi', document.getElementById('teklif-para-birimi').value);
    formData.append('durum', document.getElementById('teklif-durum').value);
    formData.append('cari_id', cariId);
    formData.append('konu', konu);
    formData.append('aciklama', document.getElementById('teklif-aciklama').value);
    formData.append('notlar', document.getElementById('teklif-notlar').value);

    rows.forEach(function(row) {
        if (row.id === 'bos-kalem') return;
        formData.append('urun_adi[]', row.querySelector('.urun-adi-input').value);
        formData.append('miktar[]', row.querySelector('.miktar-input').value);
        formData.append('fiyat[]', row.querySelector('.fiyat-input').value);
        formData.append('iskonto[]', row.querySelector('.iskonto-input').value);
        formData.append('kdv[]', row.querySelector('.kdv-input').value);
    });

    fetch(API_BASE + '/teklif_kaydet.php', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                alert(data.message || 'Teklif başarıyla kaydedildi!');
                window.location.href = API_BASE + '/teklifler.php';
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(function(err) { alert('Hata oluştu: ' + err); });
}

// Hızlı ürün ekleme fonksiyonları
function modalBarkodOlustur() {
    var prefix = '869';
    var random = '';
    for (var i = 0; i < 9; i++) random += Math.floor(Math.random() * 10);
    var check = Math.floor(Math.random() * 10);
    document.getElementById('modal_barkod').value = prefix + random + check;
}

function modalUrunKaydet() {
    var urun_kodu = document.getElementById('modal_urun_kodu').value.trim().toUpperCase();
    var urun_adi = document.getElementById('modal_urun_adi').value.trim().toUpperCase();
    if (!urun_kodu || !urun_adi) { alert('Ürün kodu ve adı zorunludur!'); return; }

    var satis_fiyati = parseFloat(document.getElementById('modal_satis_fiyati').value) || 0;
    var satis_doviz = document.getElementById('modal_satis_doviz').value;
    var barkod = document.getElementById('modal_barkod').value.trim();

    var formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('urun_kodu', urun_kodu);
    formData.append('urun_adi', urun_adi);
    formData.append('barkod', barkod);
    formData.append('seri_no', document.getElementById('modal_seri_no').value.trim().toUpperCase());
    formData.append('kategori', document.getElementById('modal_kategori').value.trim().toUpperCase());
    formData.append('birim', document.getElementById('modal_birim').value);
    formData.append('urun_tipi', document.getElementById('modal_urun_tipi').value);
    formData.append('alis_fiyati', document.getElementById('modal_alis_fiyati').value);
    formData.append('alis_fiyati_doviz', document.getElementById('modal_alis_doviz').value);
    formData.append('satis_fiyati', satis_fiyati);
    formData.append('satis_fiyati_doviz', satis_doviz);
    formData.append('stok_miktari', document.getElementById('modal_stok_miktari').value);
    formData.append('min_stok', document.getElementById('modal_min_stok').value);
    formData.append('max_stok', document.getElementById('modal_max_stok').value);
    formData.append('aciklama', document.getElementById('modal_aciklama').value.trim().toUpperCase());

    fetch(API_BASE + '/api/stok_ekle_ajax.php', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var select = document.getElementById('urun-listesi');
                var opt = document.createElement('option');
                opt.value = data.urun_id;
                opt.dataset.fiyat = satis_fiyati;
                opt.dataset.ad = urun_adi;
                opt.text = urun_adi + ' (' + satis_fiyati + ' ' + satis_doviz + ')';
                select.appendChild(opt);
                select.value = data.urun_id;
                document.getElementById('urun-fiyat').value = satis_fiyati;

                bootstrap.Modal.getInstance(document.getElementById('yeniUrunModal')).hide();
                alert('Ürün eklendi!');
            } else {
                alert('Hata: ' + data.message);
            }
        });
}

document.getElementById('urun-ara').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        urunAra();
    }
});
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>