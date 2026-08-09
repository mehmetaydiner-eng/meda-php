<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$personeller = $pdo->query("SELECT * FROM cariler WHERE cari_turu = 'PERSONEL' ORDER BY unvan")->fetchAll();
$hesaplar = $pdo->query("SELECT * FROM hesaplar WHERE is_active = 1 AND hesap_turu != 'VERESİYE' ORDER BY hesap_adi")->fetchAll();

$fatura_id = safe_int($_GET['id'] ?? null, 0) ?: null;
$fatura = null;
$detaylar = [];

if ($fatura_id) {
    $stmt = $pdo->prepare('SELECT * FROM faturalar WHERE id = ?');
    $stmt->execute([$fatura_id]);
    $fatura = $stmt->fetch();
    if (!$fatura) {
        http_response_code(404);
        die('Fatura bulunamadı.');
    }
    if ($fatura['cari_id']) {
        $stmt = $pdo->prepare('SELECT * FROM cariler WHERE id = ?');
        $stmt->execute([$fatura['cari_id']]);
        $fatura['cari'] = $stmt->fetch() ?: null;
    } else {
        $fatura['cari'] = null;
    }
    $stmt = $pdo->prepare('SELECT * FROM fatura_detaylari WHERE fatura_id = ?');
    $stmt->execute([$fatura_id]);
    $detaylar = $stmt->fetchAll();
}

$cariler = $pdo->query('SELECT * FROM cariler ORDER BY unvan')->fetchAll();

$page_title   = 'SATIŞ FATURASI - E-FATURA';
$breadcrumb   = 'E-Fatura Oluştur';
$current_page = 'faturalar';
// extra_css kaldırıldı
require_once __DIR__ . '/../includes/header.php';

$now = new DateTime('now');
?>

<style>
    /* Tema değişkenleri ile uyumlu fatura stilleri */
    .fatura-container .satici-bilgi input,
    .fatura-container .alici-bilgi input,
    .fatura-container .alici-bilgi select {
        background: var(--bg-input, #121212);
        border: 1px solid var(--border-color, #2a2a2a);
        color: var(--text-primary, #e0e0e0);
        border-radius: 4px;
        padding: 2px 8px;
        font-size: 13px;
    }
    .fatura-container .satici-bilgi input:focus,
    .fatura-container .alici-bilgi input:focus,
    .fatura-container .alici-bilgi select:focus {
        border-color: #4ad46a;
        outline: none;
        box-shadow: 0 0 0 2px rgba(74, 212, 106, 0.2);
    }
    .fatura-container .satici-bilgi strong {
        font-size: 16px;
        color: var(--text-primary, #e0e0e0);
    }
    .fatura-container .fatura-bilgi .etiket {
        color: var(--text-muted, #6a6a6a);
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-block;
        width: 90px;
    }
    .fatura-container .fatura-bilgi > div {
        margin-bottom: 4px;
        display: flex;
        align-items: center;
    }
    .fatura-container .fatura-no-input {
        font-weight: 700;
        color: var(--badge-success-text, #4ad46a);
        background: transparent;
        border: none;
        font-size: 16px;
        width: 200px;
    }
    .fatura-container .fatura-bilgi-satiri {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 20px;
        padding: 8px 0;
        border-top: 1px solid var(--border-color, #2a2a2a);
        border-bottom: 1px solid var(--border-color, #2a2a2a);
        margin: 10px 0;
    }
    .fatura-container .fatura-bilgi-satiri .bilgi-item {
        font-size: 12px;
        color: var(--text-muted, #6a6a6a);
    }
    .fatura-container .fatura-bilgi-satiri .bilgi-item .value {
        color: var(--text-primary, #e0e0e0);
        font-weight: 600;
        margin-left: 4px;
    }
    /* Tablo içi inputlar */
    .fatura-container .table-custom input[type="text"],
    .fatura-container .table-custom input[type="number"] {
        background: var(--bg-input, #121212);
        border: 1px solid var(--border-color, #2a2a2a);
        color: var(--text-primary, #e0e0e0);
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 12px;
    }
    .fatura-container .table-custom input:focus {
        border-color: #4ad46a;
        outline: none;
        box-shadow: 0 0 0 2px rgba(74, 212, 106, 0.2);
    }
    .fatura-container .toplam-bolumu {
        margin-top: 15px;
        display: flex;
        justify-content: flex-end;
    }
    .fatura-container .toplam-kutusu {
        background: var(--bg-secondary, #1e1e1e);
        border: 1px solid var(--border-color, #2a2a2a);
        border-radius: 6px;
        padding: 12px 20px;
        min-width: 250px;
    }
    .fatura-container .total-row {
        display: flex;
        justify-content: space-between;
        padding: 4px 0;
        font-size: 13px;
        color: var(--text-primary, #e0e0e0);
    }
    .fatura-container .total-row.genel-toplam {
        border-top: 2px solid var(--border-color, #2a2a2a);
        margin-top: 4px;
        padding-top: 8px;
        font-size: 16px;
        font-weight: 700;
        color: var(--badge-success-text, #4ad46a);
    }
    .fatura-container .urun-ekle-formu {
        background: var(--bg-secondary, #1e1e1e);
        border: 1px solid var(--border-color, #2a2a2a);
        border-radius: 6px;
        padding: 12px 15px;
        margin-top: 15px;
    }
    .fatura-container .urun-ekle-formu .form-label {
        font-size: 10px;
        color: var(--text-muted, #6a6a6a);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 2px;
    }
    .fatura-container .btn-ekle {
        height: 31px;
        padding: 5px 12px;
        font-size: 11px;
        white-space: nowrap;
    }
    @media (max-width: 768px) {
        .fatura-container .fatura-bilgi > div {
            flex-wrap: wrap;
        }
        .fatura-container .fatura-bilgi .etiket {
            width: auto;
            margin-right: 6px;
        }
        .fatura-container .toplam-kutusu {
            min-width: 100%;
        }
    }
</style>

<div class="container mt-4 fatura-container">

    <!-- ===== HEADER KARTI ===== -->
    <div class="card-custom mb-3">
        <div class="card-header-custom">
            <h5><i class="fas fa-file-invoice"></i> FATURA BİLGİLERİ</h5>
        </div>
        <div class="p-3">
            <div class="row g-3">
                <div class="col-md-6 satici-bilgi">
                    <strong>MEHMET AYDINER</strong>
                    <input type="text" id="satici-adres" class="form-control form-control-sm mt-1" value="82.Sk. Yenice Apt: No:16 07040 Kızılsaray Mh. / Muratpaşa/ Antalya">
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        <span class="small">Tel: <input type="text" id="satici-tel" class="form-control form-control-sm d-inline-block" style="width:150px;" value="0 (242) 247 6699"></span>
                        <span class="small">Web: <input type="text" id="satici-web" class="form-control form-control-sm d-inline-block" style="width:200px;" value="http://www.medabilgisayar.net"></span>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        <span class="small">E-Posta: <input type="text" id="satici-email" class="form-control form-control-sm d-inline-block" style="width:200px;" value="mehmetaydiner@gmail.com"></span>
                        <span class="small">Vergi Dairesi: <input type="text" id="satici-vd" class="form-control form-control-sm d-inline-block" style="width:120px;" value="Üçkaplar"></span>
                        <span class="small">TCKN: <input type="text" id="satici-tckn" class="form-control form-control-sm d-inline-block" style="width:130px;" value="39292069044"></span>
                    </div>
                </div>
                <div class="col-md-6 fatura-bilgi">
                    <div><span class="etiket">Fatura No:</span><input type="text" id="fatura-no" class="fatura-no-input" value="<?= e($fatura['fatura_no'] ?? 'MED2026000000039') ?>"></div>
                    <div><span class="etiket">Fatura Tarihi:</span><input type="date" id="fatura-tarihi" class="form-control form-control-sm" style="width:160px;" value="<?= $now->format('Y-m-d') ?>"></div>
                    <div><span class="etiket">Fatura Tipi:</span>
                        <select id="fatura-tipi" class="form-select form-select-sm" style="width:130px;">
                            <option value="SATIŞ" selected>SATIŞ</option>
                            <option value="ALIŞ">ALIŞ</option>
                            <option value="İADE">İADE</option>
                        </select>
                    </div>
                    <div><span class="etiket">Senaryo:</span>
                        <select id="fatura-senaryo" class="form-select form-select-sm" style="width:130px;">
                            <option value="TİCARİ" selected>TİCARİ</option>
                            <option value="TEMEL">TEMEL</option>
                            <option value="İADE">İADE</option>
                        </select>
                    </div>
                    <div><span class="etiket">Özelleştirme:</span>
                        <select id="fatura-ozellestirme" class="form-select form-select-sm" style="width:130px;">
                            <option value="TR1.2" selected>TR1.2</option>
                            <option value="TR1.1">TR1.1</option>
                        </select>
                    </div>
                    <div><span class="etiket">ETTN:</span><input type="text" id="fatura-ettn" class="form-control form-control-sm" style="width:200px;" value="<?= e($fatura['gib_uuid'] ?? '462A22CE-8F58-4B60-8370-A61922D430CA') ?>"></div>
                    <div><span class="etiket">Para Birimi:</span>
                        <select id="fatura-para-birimi" class="form-select form-select-sm" style="width:130px;">
                            <option value="TL" selected>TL</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== ALICI BİLGİLERİ KARTI ===== -->
    <div class="card-custom mb-3">
        <div class="card-header-custom">
            <h5><i class="fas fa-user"></i> ALICI BİLGİLERİ</h5>
        </div>
<div class="col-md-12">
                    <select id="cari-select" class="form-select form-select-sm" onchange="cariSec(this.value)">
                        <option value="">MÜŞTERİ SEÇİN...</option>
                        <?php foreach ($cariler as $cari): ?>
                        <option value="<?= (int)$cari['id'] ?>" <?= ($fatura && $fatura['cari_id'] == $cari['id']) ? 'selected' : '' ?>>
                            <?= e($cari['unvan']) ?> - <?= e($cari['vergi_no'] ?: 'VERGİ NO YOK') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

        <div class="p-3 alici-bilgi">
            <div class="row g-2">
                <div class="col-md-12">
                    <label class="form-label small">Ünvan / Ad Soyad</label>
                    <input type="text" id="alici-unvan" class="form-control form-control-sm" value="<?= e($fatura['cari']['unvan'] ?? '') ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label small">Adres</label>
                    <input type="text" id="alici-adres" class="form-control form-control-sm" value="<?= e($fatura['cari']['adres'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Vergi Dairesi</label>
                    <input type="text" id="alici-vd" class="form-control form-control-sm" value="<?= e($fatura['cari']['vergi_dairesi'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">VKN</label>
                    <input type="text" id="alici-vkn" class="form-control form-control-sm" value="<?= e($fatura['cari']['vergi_no'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Telefon</label>
                    <input type="text" id="alici-tel" class="form-control form-control-sm" value="<?= e($fatura['cari']['telefon'] ?? '') ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label small">E-Posta</label>
                    <input type="text" id="alici-email" class="form-control form-control-sm" value="<?= e($fatura['cari']['email'] ?? '') ?>">
                </div>
                
            </div>
        </div>
    </div>

    <!-- ===== ÜRÜN EKLEME FORMU (ALICI BİLGİLERİNİN ALTINA TAŞINDI) ===== -->
    <div class="urun-ekle-formu">
        <div style="font-size: 11px; font-weight: 700; color: var(--text-muted, #8a8a8a); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
            <i class="fas fa-plus-circle"></i> ÜRÜN EKLE
        </div>
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">ÜRÜN ARA</label>
                <input type="text" id="urun-ara" class="form-control form-control-sm" placeholder="Ürün adı veya barkod ara...">
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-primary-custom btn-sm w-100" onclick="urunAra()" style="height:31px;">
                    <i class="fas fa-search"></i> ARA
                </button>
            </div>
            <div class="col-md-4">
                <label class="form-label">ÜRÜN SEÇ</label>
                <select id="urun-listesi" class="form-select form-select-sm">
                    <option value="">Seçin...</option>
                </select>
            </div>
            <div class="col-md-4" style="display: flex; gap: 6px; align-items: flex-end;">
                <div style="flex:1;"><label class="form-label">MİKTAR</label><input type="number" id="urun-miktar" class="form-control form-control-sm" value="1" min="0.01" step="0.01"></div>
                <div style="flex:1;"><label class="form-label">FİYAT</label><input type="number" id="urun-fiyat" class="form-control form-control-sm" step="0.01" value="0"></div>
                <div style="flex:1;"><label class="form-label">İSK%</label><input type="number" id="urun-iskonto" class="form-control form-control-sm" value="0" min="0" max="100" step="1"></div>
                <div style="flex:1;"><label class="form-label">KDV%</label><input type="number" id="urun-kdv" class="form-control form-control-sm" value="20" min="0" max="100" step="1"></div>
                <div><label class="form-label">&nbsp;</label><button class="btn btn-success-custom btn-sm" onclick="kalemEkle()" style="height:31px; white-space:nowrap;"><i class="fas fa-plus"></i> EKLE</button></div>
            </div>
        </div>
        <div class="mt-3 pt-2" style="border-top:1px solid var(--border-color, #2a2a2a);">
            <button type="button" class="btn btn-warning-custom btn-sm w-100" data-bs-toggle="modal" data-bs-target="#yeniUrunModal">
                <i class="fas fa-plus-circle"></i> YENİ ÜRÜN EKLE
            </button>
        </div>
    </div>

    <!-- ===== FATURA ÖZET BİLGİ SATIRI ===== -->
    <div class="fatura-bilgi-satiri">
        <span class="bilgi-item"><span class="label">ETTN:</span><span class="value" id="ettn-goster"><?= e($fatura['gib_uuid'] ?? '462A22CE-8F58-4B60-8370-A61922D430CA') ?></span></span>
        <span class="bilgi-item"><span class="label">Özelleştirme:</span><span class="value" id="ozellestirme-goster">TR1.2</span></span>
        <span class="bilgi-item"><span class="label">Senaryo:</span><span class="value" id="senaryo-goster">TİCARİ</span></span>
        <span class="bilgi-item"><span class="label">Fatura Tipi:</span><span class="value" id="fatura-tipi-goster">SATIŞ</span></span>
        <span class="bilgi-item"><span class="label">Fatura No:</span><span class="value" id="fatura-no-goster"><?= e($fatura['fatura_no'] ?? 'MED2026000000039') ?></span></span>
        <span class="bilgi-item"><span class="label">Fatura Tarihi:</span><span class="value" id="fatura-tarihi-goster"><?= $now->format('d-m-Y') ?></span></span>
    </div>

    <!-- ===== FATURA KALEMLERİ TABLOSU ===== -->
    <div class="card-custom mb-3">
        <div class="card-header-custom">
            <h5><i class="fas fa-list"></i> FATURA KALEMLERİ</h5>
        </div>
        <div class="p-0">
            <div class="table-responsive">
                <table class="table-custom" id="fatura-kalemleri">
                    <thead>
                        <tr>
                            <th style="width:35px;" class="text-center">#</th>
                            <th style="width:18%;">MAL / HİZMET</th>
                            <th style="width:8%;" class="text-center">MİKTAR</th>
                            <th style="width:12%;" class="text-end">BİRİM FİYAT</th>
                            <th style="width:8%;" class="text-center">İSKONTO %</th>
                            <th style="width:10%;" class="text-end">İSKONTO TUTARI</th>
                            <th style="width:8%;">İSKONTO NEDENİ</th>
                            <th style="width:8%;" class="text-center">KDV %</th>
                            <th style="width:10%;" class="text-end">KDV TUTARI</th>
                            <th style="width:8%;" class="text-end">DİĞER VERGİLER</th>
                            <th style="width:14%;" class="text-end">TOPLAM</th>
                            <th style="width:30px;" class="text-center">İŞLEM</th>
                        </tr>
                    </thead>
                    <tbody id="fatura-kalem-tbody">
                        <?php if ($detaylar): ?>
                            <?php foreach ($detaylar as $i => $detay): ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td><input type="text" class="form-control form-control-sm urun-adi-input" value="<?= e($detay['urun_adi']) ?>"></td>
                                <td class="text-center"><input type="number" class="form-control form-control-sm miktar-input" value="<?= number_format((float)$detay['miktar'], 0, '.', '') ?>" min="0.01" step="0.01" style="width:70px; display:inline-block; text-align:center;" onchange="satirGuncelle(this)"></td>
                                <td class="text-end"><input type="number" class="form-control form-control-sm fiyat-input" value="<?= number_format((float)$detay['birim_fiyati'], 2, '.', '') ?>" min="0" step="0.01" style="width:100px; display:inline-block; text-align:right;" onchange="satirGuncelle(this)"></td>
                                <td class="text-center"><input type="number" class="form-control form-control-sm iskonto-input" value="<?= number_format((float)$detay['iskonto'], 2, '.', '') ?>" min="0" max="100" step="0.5" style="width:60px; display:inline-block; text-align:center;" onchange="satirGuncelle(this)"></td>
                                <td class="text-end iskonto-tutar"><?= number_format((float)$detay['iskonto_tutari'], 2, '.', '') ?></td>
                                <td><input type="text" class="form-control form-control-sm iskonto-nedeni" value="İskonto -" style="width:100%;"></td>
                                <td class="text-center"><input type="number" class="form-control form-control-sm kdv-input" value="<?= number_format((float)($detay['vergi_orani'] ?: 18), 0, '.', '') ?>" min="0" max="100" step="0.5" style="width:60px; display:inline-block; text-align:center;" onchange="satirGuncelle(this)"></td>
                                <td class="text-end kdv-tutar"><?= number_format((float)($detay['vergi_tutari'] ?: 0), 2, '.', '') ?></td>
                                <td class="text-end"><input type="text" class="form-control form-control-sm diger-vergiler" value="-" style="width:60px; text-align:right;"></td>
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
                                <td colspan="12" class="text-center text-muted py-3">
                                    <i class="fas fa-plus-circle fa-2x d-block mb-2"></i>
                                    Ürün eklemek için yukarıdaki formu kullanın
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== TOPLAM BÖLÜMÜ ===== -->
    <div class="toplam-bolumu">
        <div class="toplam-kutusu">
            <div class="total-row"><span>MAL HİZMET TOPLAMI</span><span id="ara-toplam"><?= number_format((float)($fatura['ara_toplam'] ?? 0), 2, '.', '') ?></span></div>
            <div class="total-row"><span>İSKONTO TUTARI</span><span id="iskonto-tutari"><?= number_format((float)($fatura['iskonto_tutari'] ?? 0), 2, '.', '') ?></span></div>
            <div class="total-row"><span>KDV TUTARI</span><span id="vergi-tutari"><?= number_format((float)($fatura['vergi_tutari'] ?? 0), 2, '.', '') ?></span></div>
            <div class="total-row genel-toplam"><span>GENEL TOPLAM</span><span id="genel-toplam"><?= number_format((float)($fatura['genel_toplam'] ?? 0), 2, '.', '') ?></span></div>
        </div>
    </div>

    <!-- ===== ÖDEME DAĞILIMI ===== -->
    <div class="card-custom mt-3">
        <div class="card-header-custom">
            <h5><i class="fas fa-money-bill-wave"></i> ÖDEME DAĞILIMI</h5>
            <button type="button" class="btn btn-primary-custom btn-sm" onclick="odemeSatiriEkle()">
                <i class="fas fa-plus"></i> ÖDEME EKLE
            </button>
        </div>
        <div class="p-3">
            <div id="odemeSatirlari"></div>
            <div class="d-flex justify-content-end gap-4 mt-2" style="font-size: 12px;">
                <span>Genel Toplam: <strong id="odemeGenelToplamGoster">0.00</strong></span>
                <span>Ödenen: <strong id="odemeOdenenGoster" class="text-success">0.00</strong></span>
                <span>Kalan: <strong id="odemeKalanGoster">0.00</strong></span>
            </div>
            <small class="text-muted d-block mt-1">Ödemeyi birden fazla kanaldan bölebilirsin. Kalan tutar veresiye/borç olarak izlenir.</small>
        </div>
    </div>

    <!-- ===== NOTLAR VE İŞLEMLER ===== -->
    <div class="row g-3 mt-2">
        <div class="col-md-6">
            <div class="card-custom">
                <div class="card-header-custom"><h5><i class="fas fa-sticky-note"></i> FATURA NOTU</h5></div>
                <div class="p-3"><textarea id="fatura-notu" class="form-control form-control-sm" rows="2" placeholder="Fatura notu..."><?= e($fatura['aciklama'] ?? '') ?></textarea></div>
            </div>
        </div>
        <div class="col-md-6 no-print">
            <div class="card-custom">
                <div class="card-header-custom"><h5><i class="fas fa-tools"></i> İŞLEMLER</h5></div>
                <div class="p-3 d-flex flex-wrap gap-2">
                    <button class="btn btn-success-custom btn-sm" onclick="faturaKaydet()"><i class="fas fa-save"></i> KAYDET</button>
                    <button class="btn btn-primary-custom btn-sm" onclick="window.print()"><i class="fas fa-print"></i> YAZDIR</button>
                    <?php if ($fatura): ?>
                    <a class="btn btn-warning-custom btn-sm" href="<?= BASE_URL ?>/fatura_xml_olustur.php?id=<?= (int)$fatura['id'] ?>" target="_blank"><i class="fas fa-file-code"></i> XML</a>
                    <?php endif; ?>
                    <button class="btn btn-danger-custom btn-sm" onclick="faturaIptal()"><i class="fas fa-times"></i> İPTAL</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODALLAR ===== -->
<!-- Yeni Ürün Modal -->
<div class="modal fade" id="yeniUrunModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-box"></i> YENİ ÜRÜN EKLE</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="yeniUrunForm" onsubmit="return false;">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label small">ÜRÜN KODU <span class="text-danger">*</span></label><input type="text" id="modal_urun_kodu" class="form-control form-control-sm" placeholder="PR-001" required></div>
                        <div class="col-md-6"><label class="form-label small">ÜRÜN ADI <span class="text-danger">*</span></label><input type="text" id="modal_urun_adi" class="form-control form-control-sm" placeholder="LAPTOP" required></div>
                        <div class="col-md-6"><label class="form-label small">BARKOD</label><div class="input-group input-group-sm"><input type="text" id="modal_barkod" class="form-control" placeholder="OTOMATİK"><button type="button" class="btn btn-outline-primary" onclick="modalBarkodOlustur()"><i class="fas fa-qrcode"></i> OLUŞTUR</button></div></div>
                        <div class="col-md-6"><label class="form-label small">SERİ NUMARASI</label><input type="text" id="modal_seri_no" class="form-control form-control-sm" placeholder="SN-2024-001"></div>
                        <div class="col-md-6"><label class="form-label small">KATEGORİ</label><input type="text" id="modal_kategori" class="form-control form-control-sm" placeholder="BİLGİSAYAR"></div>
                        <div class="col-md-6"><label class="form-label small">BİRİM</label><select id="modal_birim" class="form-select form-select-sm"><option value="ADET">ADET</option><option value="KG">KG</option><option value="METRE">METRE</option><option value="LİTRE">LİTRE</option><option value="SAAT">SAAT</option><option value="PAKET">PAKET</option><option value="KUTU">KUTU</option></select></div>
                        <div class="col-md-6"><label class="form-label small">ÜRÜN TİPİ</label><select id="modal_urun_tipi" class="form-select form-select-sm"><option value="SIFIR">SIFIR</option><option value="2.EL">2.EL</option></select></div>
                        <div class="col-md-6"><label class="form-label small">ALIŞ FİYATI</label><div class="input-group input-group-sm"><input type="number" id="modal_alis_fiyati" class="form-control" step="0.01" value="0"><select id="modal_alis_doviz" class="form-select" style="max-width:80px;"><option value="TL">TL</option><option value="USD">USD</option><option value="EUR">EUR</option></select></div></div>
                        <div class="col-md-6"><label class="form-label small">SATIŞ FİYATI</label><div class="input-group input-group-sm"><input type="number" id="modal_satis_fiyati" class="form-control" step="0.01" value="0"><select id="modal_satis_doviz" class="form-select" style="max-width:80px;"><option value="TL">TL</option><option value="USD">USD</option><option value="EUR">EUR</option></select></div></div>
                        <div class="col-md-4"><label class="form-label small">STOK MİKTARI</label><input type="number" id="modal_stok_miktari" class="form-control form-control-sm" step="0.01" value="0"></div>
                        <div class="col-md-4"><label class="form-label small">MIN. STOK</label><input type="number" id="modal_min_stok" class="form-control form-control-sm" step="0.01" value="0"></div>
                        <div class="col-md-4"><label class="form-label small">MAX. STOK</label><input type="number" id="modal_max_stok" class="form-control form-control-sm" step="0.01" value="0"></div>
                        <div class="col-md-12"><label class="form-label small">AÇIKLAMA</label><textarea id="modal_aciklama" class="form-control form-control-sm" rows="2" placeholder="ÜRÜN AÇIKLAMASI..."></textarea></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İPTAL</button>
                <button type="button" class="btn btn-success-custom btn-sm" onclick="modalUrunKaydet()"><i class="fas fa-save"></i> KAYDET VE EKLE</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== PRİM POPUP ===== -->
<div class="modal fade" id="primModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-hand-holding-usd"></i> PRİM İŞLEMİ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="primSoruAlani" class="text-center py-3">
                    <p style="font-size: 15px;">Bu satış için <strong>prim işlemi</strong> yapılacak mı?</p>
                    <p class="text-muted" style="font-size: 12px;">Satış tutarı: <strong id="primSatisTutariGoster">-</strong></p>
                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <button type="button" class="btn btn-success btn-sm" onclick="primEvet()"><i class="fas fa-check"></i> EVET</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="primHayirYonlendir()"><i class="fas fa-times"></i> HAYIR</button>
                    </div>
                </div>
                <div id="primDetayAlani" style="display: none;">
                    <div class="mb-3"><label class="form-label small">Prim Verilecek Kişi <span class="text-danger">*</span></label><select id="primKisi" class="form-select form-select-sm"><option value="">-- Seçin --</option><?php foreach ($personeller as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['unvan']) ?></option><?php endforeach; ?></select><?php if (!$personeller): ?><small class="text-warning">Henüz PERSONEL türünde bir cari yok. <a href="<?= BASE_URL ?>/cari_ekle.php" target="_blank">Buradan ekleyebilirsiniz</a>.</small><?php endif; ?></div>
                    <div class="mb-3"><label class="form-label small">Hesaplama Yöntemi</label><div class="d-flex gap-3"><label><input type="radio" name="primYontem" value="SABIT" checked onchange="primYontemDegisti()"> Sabit Tutar</label><label><input type="radio" name="primYontem" value="ORAN" onchange="primYontemDegisti()"> Satıştan Oranla</label></div></div>
                    <div id="primSabitAlani" class="mb-3"><label class="form-label small">Prim Tutarı (₺)</label><input type="number" id="primTutarSabit" class="form-control form-control-sm" step="0.01" min="0" value="0"></div>
                    <div id="primOranAlani" class="mb-3" style="display: none;"><label class="form-label small">Oran (%)</label><input type="number" id="primOranYuzde" class="form-control form-control-sm" step="0.1" min="0" max="100" value="0" oninput="primOranHesapla()"><small class="text-muted">Hesaplanan tutar: <strong id="primHesaplananTutar">0.00</strong> ₺</small></div>
                    <div class="mb-3"><label class="form-label small">Açıklama (opsiyonel)</label><input type="text" id="primAciklama" class="form-control form-control-sm"></div>
                </div>
            </div>
            <div class="modal-footer" id="primModalFooter" style="display: none;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="primHayirYonlendir()">İPTAL</button>
                <button type="button" class="btn btn-success btn-sm" onclick="primKaydet()"><i class="fas fa-save"></i> KAYDET</button>
            </div>
        </div>
    </div>
</div>

<?php
$api_base_json = json_encode(BASE_URL);
$fatura_id_js = (int)($fatura['id'] ?? 0);
$odeme_hesaplari_json = json_encode(array_map(fn($h) => ['id' => (int)$h['id'], 'ad' => $h['hesap_adi']], $hesaplar));
$odeme_kanallari_json = json_encode(['NAKİT', 'KREDİ KARTI', 'BANKA HAVALESİ', 'EFT', 'ÇEK', 'SENET', 'VERESİYE']);
$extra_js = <<<JS
<script>
var API_BASE = {$api_base_json};
var FATURA_ID = {$fatura_id_js};
var ODEME_HESAPLARI = {$odeme_hesaplari_json};
var ODEME_KANALLARI = {$odeme_kanallari_json};
var odemeSatirSayaci = 0;
var primSatisTutari = 0;
var primReferansNo = '';

// ========== CARİ SEÇ ==========
function cariSec(id) {
    if (!id) return;
    fetch(API_BASE + '/api/cari_detay.php?id=' + id)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            document.getElementById('alici-unvan').value = data.unvan || '';
            document.getElementById('alici-adres').value = data.adres || '';
            document.getElementById('alici-vd').value = data.vergi_dairesi || '';
            document.getElementById('alici-vkn').value = data.vergi_no || '';
            document.getElementById('alici-tel').value = data.telefon || '';
            document.getElementById('alici-email').value = data.email || '';
        });
}

// ========== ÜRÜN ARA ==========
function urunAra() {
    var q = document.getElementById('urun-ara').value.trim();
    if (q.length < 2) return;
    fetch(API_BASE + '/api/stok_ara.php?q=' + encodeURIComponent(q))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var select = document.getElementById('urun-listesi');
            select.innerHTML = '<option value="">Ürün seçin...</option>';
            data.forEach(function(urun) {
                select.innerHTML += '<option value="' + urun.id + '" data-fiyat="' + urun.satis_fiyati + '" data-ad="' + urun.urun_adi + '" data-barkod="' + (urun.barkod || '') + '">' + urun.urun_adi + ' - ' + (urun.barkod || 'BARKOD YOK') + ' (' + urun.satis_fiyati + ' ' + (urun.satis_fiyati_doviz || 'TL') + ')</option>';
            });
        });
}

// ========== KALEM EKLE ==========
function kalemEkle() {
    var select = document.getElementById('urun-listesi');
    var selected = select.options[select.selectedIndex];
    if (!selected.value) {
        alert('Lütfen bir ürün seçin!');
        return;
    }
    var miktar = parseFloat(document.getElementById('urun-miktar').value) || 1;
    var fiyat = parseFloat(document.getElementById('urun-fiyat').value) || 0;
    var iskonto = parseFloat(document.getElementById('urun-iskonto').value) || 0;
    var kdvOran = parseFloat(document.getElementById('urun-kdv').value) || 18;
    var satirToplam = miktar * fiyat;
    var iskontoTutar = satirToplam * (iskonto / 100);
    var iskontoSonrasi = satirToplam - iskontoTutar;
    var kdvTutar = iskontoSonrasi * (kdvOran / 100);
    var genelSatirToplam = iskontoSonrasi + kdvTutar;

    var tbody = document.getElementById('fatura-kalem-tbody');
    var bos = document.getElementById('bos-kalem');
    if (bos) bos.remove();

    var index = tbody.children.length + 1;
    var row = document.createElement('tr');
    row.innerHTML =
        '<td class="text-center">' + index + '</td>' +
        '<td><input type="text" class="form-control form-control-sm urun-adi-input" value="' + selected.dataset.ad + '"></td>' +
        '<td class="text-center"><input type="number" class="form-control form-control-sm miktar-input" value="' + miktar.toFixed(2) + '" min="0.01" step="0.01" style="width:70px; display:inline-block; text-align:center;" onchange="satirGuncelle(this)"></td>' +
        '<td class="text-end"><input type="number" class="form-control form-control-sm fiyat-input" value="' + fiyat.toFixed(2) + '" min="0" step="0.01" style="width:100px; display:inline-block; text-align:right;" onchange="satirGuncelle(this)"></td>' +
        '<td class="text-center"><input type="number" class="form-control form-control-sm iskonto-input" value="' + iskonto.toFixed(0) + '" min="0" max="100" step="0.5" style="width:60px; display:inline-block; text-align:center;" onchange="satirGuncelle(this)"></td>' +
        '<td class="text-end iskonto-tutar">' + iskontoTutar.toFixed(2) + '</td>' +
        '<td><input type="text" class="form-control form-control-sm iskonto-nedeni" value="İskonto -"></td>' +
        '<td class="text-center"><input type="number" class="form-control form-control-sm kdv-input" value="' + kdvOran + '" min="0" max="100" step="0.5" style="width:60px; display:inline-block; text-align:center;" onchange="satirGuncelle(this)"></td>' +
        '<td class="text-end kdv-tutar">' + kdvTutar.toFixed(2) + '</td>' +
        '<td class="text-end"><input type="text" class="form-control form-control-sm diger-vergiler" value="-"></td>' +
        '<td class="text-end"><strong class="satir-toplam">' + genelSatirToplam.toFixed(2) + '</strong></td>' +
        '<td class="text-center"><button class="btn btn-outline-danger btn-sm" onclick="satirSil(this)"><i class="fas fa-times"></i></button></td>';
    tbody.appendChild(row);
    hesaplaToplam();
}

function satirSil(btn) {
    var row = btn.closest('tr');
    row.remove();
    document.querySelectorAll('#fatura-kalem-tbody tr').forEach(function(tr, i) {
        tr.cells[0].textContent = i + 1;
    });
    hesaplaToplam();
}

function satirGuncelle(input) {
    var row = input.closest('tr');
    if (!row || row.id === 'bos-kalem') return;
    var miktar = parseFloat(row.querySelector('.miktar-input').value) || 0;
    var fiyat = parseFloat(row.querySelector('.fiyat-input').value) || 0;
    var iskonto = parseFloat(row.querySelector('.iskonto-input').value) || 0;
    var kdvOran = parseFloat(row.querySelector('.kdv-input').value) || 0;
    var satirToplam = miktar * fiyat;
    var iskontoTutar = satirToplam * (iskonto / 100);
    var iskontoSonrasi = satirToplam - iskontoTutar;
    var kdvTutar = iskontoSonrasi * (kdvOran / 100);
    var genelSatirToplam = iskontoSonrasi + kdvTutar;
    row.querySelector('.iskonto-tutar').textContent = iskontoTutar.toFixed(2);
    row.querySelector('.kdv-tutar').textContent = kdvTutar.toFixed(2);
    row.querySelector('.satir-toplam').textContent = genelSatirToplam.toFixed(2);
    hesaplaToplam();
}

function hesaplaToplam() {
    var araToplam = 0, toplamIskonto = 0, toplamKdv = 0;
    document.querySelectorAll('#fatura-kalem-tbody tr').forEach(function(row) {
        if (row.id === 'bos-kalem') return;
        araToplam += parseFloat(row.querySelector('.satir-toplam').textContent) || 0;
        toplamIskonto += parseFloat(row.querySelector('.iskonto-tutar').textContent) || 0;
        toplamKdv += parseFloat(row.querySelector('.kdv-tutar').textContent) || 0;
    });
    document.getElementById('ara-toplam').textContent = araToplam.toFixed(2);
    document.getElementById('iskonto-tutari').textContent = toplamIskonto.toFixed(2);
    document.getElementById('vergi-tutari').textContent = toplamKdv.toFixed(2);
    document.getElementById('genel-toplam').textContent = araToplam.toFixed(2);
    odemeOzetGuncelle();
}

// ÖDEME DAĞILIMI (çoklu kanal/kasa)
function odemeTuruDegisti(selectEl) {
    var satir = selectEl.closest('.odeme-satiri');
    if (!satir) return;
    var hesapSelect = satir.querySelector('.odeme-hesap-select');
    if (!hesapSelect) return;
    if (selectEl.value === 'VERESİYE') {
        hesapSelect.value = '';
        hesapSelect.disabled = true;
    } else {
        hesapSelect.disabled = false;
    }
}

function odemeSatiriEkle() {
    odemeSatirSayaci++;
    var satirId = odemeSatirSayaci;
    var kanalOptions = ODEME_KANALLARI.map(function(k) {
        return '<option value="' + k + '"' + (k === 'NAKİT' ? ' selected' : '') + '>' + k + '</option>';
    }).join('');
    var hesapOptions = '<option value="">-- Kasa Seçin --</option>' + ODEME_HESAPLARI.map(function(h) {
        return '<option value="' + h.id + '">' + h.ad + '</option>';
    }).join('');
    var satir = document.createElement('div');
    satir.className = 'row g-1 mb-1 odeme-satiri';
    satir.dataset.satirId = satirId;
    satir.innerHTML =
        '<div class="col-4"><select class="form-select form-select-sm odeme-turu-select" onchange="odemeTuruDegisti(this)">' + kanalOptions + '</select></div>' +
        '<div class="col-4"><select class="form-select form-select-sm odeme-hesap-select">' + hesapOptions + '</select></div>' +
        '<div class="col-3"><input type="number" class="form-control form-control-sm odeme-tutar-input" step="0.01" min="0" value="0" oninput="odemeOzetGuncelle()"></div>' +
        '<div class="col-1"><button type="button" class="btn btn-outline-danger btn-sm" onclick="odemeSatiriSil(' + satirId + ')"><i class="fas fa-times"></i></button></div>';
    document.getElementById('odemeSatirlari').appendChild(satir);
    odemeOzetGuncelle();
}

function odemeSatiriSil(satirId) {
    var satir = document.querySelector('.odeme-satiri[data-satir-id="' + satirId + '"]');
    if (satir) satir.remove();
    odemeOzetGuncelle();
}

function odemeOzetGuncelle() {
    var genelToplam = parseFloat(document.getElementById('genel-toplam').textContent) || 0;
    var odenenToplam = 0;
    document.querySelectorAll('.odeme-tutar-input').forEach(function(input) {
        odenenToplam += parseFloat(input.value) || 0;
    });
    var kalan = genelToplam - odenenToplam;
    document.getElementById('odemeGenelToplamGoster').textContent = genelToplam.toFixed(2);
    document.getElementById('odemeOdenenGoster').textContent = odenenToplam.toFixed(2);
    var kalanEl = document.getElementById('odemeKalanGoster');
    kalanEl.textContent = kalan.toFixed(2);
    kalanEl.className = kalan > 0.01 ? 'text-warning' : (kalan < -0.01 ? 'text-danger' : 'text-success');
}

function modalBarkodOlustur() {
    var prefix = '869', random = '';
    for (var i = 0; i < 9; i++) random += Math.floor(Math.random() * 10);
    document.getElementById('modal_barkod').value = prefix + random + Math.floor(Math.random() * 10);
}

function modalUrunKaydet() {
    var urun_kodu = document.getElementById('modal_urun_kodu').value.trim().toUpperCase();
    var urun_adi = document.getElementById('modal_urun_adi').value.trim().toUpperCase();
    if (!urun_kodu || !urun_adi) { alert('Ürün kodu ve adı zorunludur!'); return; }
    var satis_fiyati = parseFloat(document.getElementById('modal_satis_fiyati').value) || 0;
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
    formData.append('satis_fiyati_doviz', document.getElementById('modal_satis_doviz').value);
    formData.append('stok_miktari', document.getElementById('modal_stok_miktari').value);
    formData.append('min_stok', document.getElementById('modal_min_stok').value);
    formData.append('max_stok', document.getElementById('modal_max_stok').value);
    formData.append('aciklama', document.getElementById('modal_aciklama').value.trim().toUpperCase());
    fetch(API_BASE + '/api/stok_ekle_ajax.php', { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                var select = document.getElementById('urun-listesi');
                var option = document.createElement('option');
                option.value = data.urun_id;
                option.dataset.fiyat = satis_fiyati;
                option.dataset.ad = urun_adi;
                option.dataset.barkod = barkod || '';
                option.text = urun_adi + ' - ' + (barkod || 'BARKOD YOK') + ' (' + satis_fiyati + ' ' + (document.getElementById('modal_satis_doviz').value) + ')';
                select.appendChild(option);
                select.value = data.urun_id;
                document.getElementById('urun-fiyat').value = satis_fiyati;
                bootstrap.Modal.getInstance(document.getElementById('yeniUrunModal')).hide();
                alert('Ürün başarıyla eklendi!');
            } else {
                alert('Hata: ' + (data.message || 'Ürün eklenemedi!'));
            }
        });
}

document.getElementById('yeniUrunModal').addEventListener('hidden.bs.modal', function() {
    ['modal_urun_kodu','modal_urun_adi','modal_barkod','modal_seri_no','modal_kategori','modal_aciklama'].forEach(function(id) {
        document.getElementById(id).value = '';
    });
    ['modal_stok_miktari','modal_min_stok','modal_max_stok','modal_alis_fiyati','modal_satis_fiyati'].forEach(function(id) {
        document.getElementById(id).value = '0';
    });
});

function faturaKaydet() {
    var rows = document.querySelectorAll('#fatura-kalem-tbody tr');
    if (rows.length === 0 || (rows.length === 1 && rows[0].id === 'bos-kalem')) {
        alert('Lütfen en az bir ürün ekleyin!');
        return false;
    }
    var formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    if (FATURA_ID) formData.append('fatura_id', FATURA_ID);
    formData.append('fatura_no', document.getElementById('fatura-no').value);
    formData.append('fatura_tarihi', document.getElementById('fatura-tarihi').value);
    formData.append('fatura_tipi', document.getElementById('fatura-tipi').value);
    formData.append('fatura_senaryo', document.getElementById('fatura-senaryo').value);
    formData.append('fatura_ozellestirme', document.getElementById('fatura-ozellestirme').value);
    formData.append('fatura_ettn', document.getElementById('fatura-ettn').value);
    formData.append('para_birimi', document.getElementById('fatura-para-birimi').value);
    formData.append('aciklama', document.getElementById('fatura-notu').value);
    formData.append('alici_unvan', document.getElementById('alici-unvan').value);
    formData.append('alici_adres', document.getElementById('alici-adres').value);
    formData.append('alici_vd', document.getElementById('alici-vd').value);
    formData.append('alici_vkn', document.getElementById('alici-vkn').value);
    formData.append('alici_tel', document.getElementById('alici-tel').value);
    formData.append('alici_email', document.getElementById('alici-email').value);
    document.querySelectorAll('#fatura-kalem-tbody tr').forEach(function(row) {
        if (row.id === 'bos-kalem') return;
        formData.append('urun_adi[]', row.querySelector('.urun-adi-input').value);
        formData.append('miktar[]', row.querySelector('.miktar-input').value);
        formData.append('fiyat[]', row.querySelector('.fiyat-input').value);
        formData.append('iskonto[]', row.querySelector('.iskonto-input').value);
        formData.append('iskonto_nedeni[]', row.querySelector('.iskonto-nedeni').value);
        formData.append('kdv[]', row.querySelector('.kdv-input').value);
        formData.append('diger_vergiler[]', row.querySelector('.diger-vergiler').value);
    });
    document.querySelectorAll('.odeme-satiri').forEach(function(satir) {
        var tutar = parseFloat(satir.querySelector('.odeme-tutar-input').value) || 0;
        if (tutar <= 0) return;
        formData.append('odeme_turu[]', satir.querySelector('.odeme-turu-select').value);
        formData.append('odeme_hesap_id[]', satir.querySelector('.odeme-hesap-select').value);
        formData.append('odeme_tutar[]', tutar);
    });
    fetch(API_BASE + '/fatura_kaydet.php', { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                if (document.getElementById('fatura-tipi').value === 'SATIŞ') {
                    primPopupGoster();
                } else {
                    alert('Fatura başarıyla kaydedildi!');
                    window.location.href = API_BASE + '/faturalar.php';
                }
            } else {
                alert('Hata: ' + (data.message || 'Fatura kaydedilemedi!'));
            }
        });
}

function primPopupGoster() {
    var tutarText = document.getElementById('genel-toplam').textContent.replace(/\./g, '').replace(',', '.');
    primSatisTutari = parseFloat(tutarText) || 0;
    primReferansNo = document.getElementById('fatura-no').value;
    document.getElementById('primSatisTutariGoster').textContent = primSatisTutari.toFixed(2) + ' ₺ (' + primReferansNo + ')';
    document.getElementById('primSoruAlani').style.display = 'block';
    document.getElementById('primDetayAlani').style.display = 'none';
    document.getElementById('primModalFooter').style.display = 'none';
    var modal = new bootstrap.Modal(document.getElementById('primModal'));
    modal.show();
}

function primHayirYonlendir() {
    var modal = bootstrap.Modal.getInstance(document.getElementById('primModal'));
    if (modal) modal.hide();
    window.location.href = API_BASE + '/faturalar.php';
}

function primEvet() {
    document.getElementById('primSoruAlani').style.display = 'none';
    document.getElementById('primDetayAlani').style.display = 'block';
    document.getElementById('primModalFooter').style.display = 'flex';
}

function primYontemDegisti() {
    var yontem = document.querySelector('input[name="primYontem"]:checked').value;
    document.getElementById('primSabitAlani').style.display = yontem === 'SABIT' ? 'block' : 'none';
    document.getElementById('primOranAlani').style.display = yontem === 'ORAN' ? 'block' : 'none';
    if (yontem === 'ORAN') primOranHesapla();
}

function primOranHesapla() {
    var oran = parseFloat(document.getElementById('primOranYuzde').value) || 0;
    document.getElementById('primHesaplananTutar').textContent = (primSatisTutari * oran / 100).toFixed(2);
}

function primKaydet() {
    var kisiId = document.getElementById('primKisi').value;
    if (!kisiId) { alert('Lütfen prim verilecek kişiyi seçin!'); return; }
    var yontem = document.querySelector('input[name="primYontem"]:checked').value;
    var tutar, oran;
    if (yontem === 'SABIT') {
        tutar = parseFloat(document.getElementById('primTutarSabit').value) || 0;
        oran = null;
    } else {
        oran = parseFloat(document.getElementById('primOranYuzde').value) || 0;
        tutar = primSatisTutari * oran / 100;
    }
    if (tutar <= 0) { alert('Geçerli bir prim tutarı girin!'); return; }
    fetch(API_BASE + '/api/prim_ekle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            cari_id: kisiId, tutar: tutar, oran: oran, matrah: primSatisTutari,
            referans_no: primReferansNo, fatura_id: FATURA_ID || null,
            aciklama: document.getElementById('primAciklama').value, csrf_token: CSRF_TOKEN,
        }),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) alert('Prim kaydedilemedi: ' + data.message);
        window.location.href = API_BASE + '/faturalar.php';
    })
    .catch(function() { window.location.href = API_BASE + '/faturalar.php'; });
}

function faturaIptal() {
    if (confirm('Faturayı iptal etmek istediğinize emin misiniz?')) {
        window.location.href = API_BASE + '/faturalar.php';
    }
}

// Fatura no ve gösterge senkronizasyonu
document.getElementById('fatura-no').addEventListener('input', function() {
    document.getElementById('fatura-no-goster').textContent = this.value;
});

document.getElementById('urun-ara').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') urunAra();
});

document.addEventListener('DOMContentLoaded', function() {
    hesaplaToplam();
    odemeSatiriEkle();
    // Fatura no senkronu
    var faturaNoInput = document.getElementById('fatura-no');
    document.getElementById('fatura-no-goster').textContent = faturaNoInput.value;
    document.getElementById('fatura-tarihi').addEventListener('input', function() {
        var date = new Date(this.value);
        document.getElementById('fatura-tarihi-goster').textContent = date.toLocaleDateString('tr-TR');
    });
    document.getElementById('fatura-tipi').addEventListener('change', function() {
        document.getElementById('fatura-tipi-goster').textContent = this.value;
    });
    document.getElementById('fatura-senaryo').addEventListener('change', function() {
        document.getElementById('senaryo-goster').textContent = this.value;
    });
    document.getElementById('fatura-ozellestirme').addEventListener('change', function() {
        document.getElementById('ozellestirme-goster').textContent = this.value;
    });
    document.getElementById('fatura-ettn').addEventListener('input', function() {
        document.getElementById('ettn-goster').textContent = this.value;
    });
});
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>