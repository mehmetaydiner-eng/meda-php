<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/numara_manager.php';
require_login();

$cariler = $pdo->query('SELECT * FROM cariler ORDER BY unvan')->fetchAll();
$hesaplar = $pdo->query("SELECT * FROM hesaplar WHERE is_active = 1 AND hesap_turu != 'VERESİYE'")->fetchAll();
$personeller = $pdo->query("SELECT * FROM cariler WHERE cari_turu = 'PERSONEL' ORDER BY unvan")->fetchAll();

$islem_tipleri = ['SATIS', 'ALIS', 'IADE'];
$belge_tipleri = ['FAT', 'EAR', 'MAKBUZ'];
$odeme_kanallari = ['NAKİT', 'KREDİ KARTI', 'BANKA HAVALESİ', 'EFT', 'KAPIDA ÖDEME', 'ÇEK', 'VERESİYE'];

$page_title   = 'Hızlı İşlem';
$breadcrumb   = 'Hızlı İşlem';
$current_page = 'hizli_islem';
$extra_css    = '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/hizli_islem.css">';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="hizli-container">

    <!-- ===== 1. KART: ÜRÜN ARAMA ===== -->
    <div class="card-custom">
        <div class="card-header-custom">
            <h5><i class="fas fa-search"></i> Ürün Ara</h5>
            <div class="d-flex gap-1">
                <button class="btn-yeni-cari" data-bs-toggle="modal" data-bs-target="#yeniCariModal">
                    <i class="fas fa-user-plus"></i> CARİ EKLE
                </button>
                <button class="btn-yeni-urun" data-bs-toggle="modal" data-bs-target="#yeniUrunModal">
                    <i class="fas fa-box"></i> ÜRÜN EKLE
                </button>
            </div>
        </div>

        <div class="card-body-custom">
            <div class="row g-1">
                <!-- Arama -->
                <div class="col-md-4">
                    <div class="arama-grubu">
                        <input type="text" id="urunArama" class="form-control-custom"
                               placeholder="Ürün adı, kod veya barkod ara..."
                               style="font-size: 12px; padding: 3px 10px; height: 30px; border-radius: 4px 0 0 4px;">
                        <button class="btn-ara" onclick="urunAra()" style="border-radius: 0 4px 4px 0; height: 30px;">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <!-- Evrak No -->
                <div class="col-md-3">
                    <div class="evrak-no-box" id="evrakNoBox">
                        <span class="label"><i class="fas fa-hashtag"></i> Evrak No</span>
                        <span class="no-value" id="evrakNoGoster">-</span>
                        <button class="btn-edit" id="evrakNoDuzenleBtn" onclick="evrakNoDuzenle()" title="Numarayı düzenle">
                            <i class="fas fa-pen"></i>
                        </button>
                    </div>
                </div>

                <!-- Evrak Tarihi -->
                <div class="col-md-3">
                    <div class="evrak-no-box" style="border-color: var(--badge-info-text);">
                        <span class="label"><i class="fas fa-calendar-alt"></i> Tarih</span>
                        <input type="date" id="evrakTarihi" class="form-control-custom"
                               value="<?= date('Y-m-d') ?>"
                               style="border: none !important; background: transparent !important;
                                      color: var(--badge-info-text) !important; font-size: 12px; font-weight: 600;
                                      padding: 0 4px; width: 130px; height: 26px;">
                    </div>
                </div>

                <!-- Sepete ekleme parametreleri (Gizli) -->
                <div class="col-md-2" style="display: none;">
                    <div class="row g-1">
                        <div class="col-4">
                            <label class="form-label-custom" style="font-size:8px;">Adet</label>
                            <input type="number" id="eklenecekMiktar" class="form-control-custom" value="1" min="1" step="1"
                                   style="font-size: 11px; padding: 2px 6px; height: 28px;">
                        </div>
                        <div class="col-4">
                            <label class="form-label-custom" style="font-size:8px;">İsk%</label>
                            <input type="number" id="eklenecekIskonto" class="form-control-custom" value="0" min="0" max="100" step="1"
                                   style="font-size: 11px; padding: 2px 6px; height: 28px;">
                        </div>
                        <div class="col-4">
                            <label class="form-label-custom" style="font-size:8px;">KDV%</label>
                            <input type="number" id="eklenecekKdv" class="form-control-custom" value="20" min="0" max="100" step="1"
                                   style="font-size: 11px; padding: 2px 6px; height: 28px;">
                        </div>
                    </div>
                    <div class="row g-1 mt-1">
                        <div class="col-8">
                            <label class="form-label-custom" style="font-size:8px;">Fiyat</label>
                            <input type="number" id="eklenecekFiyat" class="form-control-custom" value="0" min="0" step="0.01"
                                   style="font-size: 11px; padding: 2px 6px; height: 28px;">
                        </div>
                        <div class="col-4">
                            <label class="form-label-custom" style="font-size:8px;">&nbsp;</label>
                            <button class="btn-sepete-ekle w-100" onclick="sepeteEkleFromSelected()"
                                    style="height: 28px; font-size: 10px; padding: 2px 4px;">
                                <i class="fas fa-cart-plus"></i> EKLE
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ürün arama sonuçları -->
            <div id="urunListesiToolbar" style="display: none; text-align: right; margin-bottom: 6px;">
                <button type="button" class="btn-sepete-ekle" style="font-size: 11px; padding: 4px 10px;" onclick="secilileriSepeteEkle()">
                    <i class="fas fa-layer-group"></i> SEÇİLİLERİ SEPETE EKLE
                </button>
            </div>
            <div id="urunListesiSonuc">
                <div class="bos-mesaj">
                    <i class="fas fa-box"></i>
                    Arama yaparak ürünleri listeleyin
                </div>
            </div>
        </div>
    </div>

    <!-- ===== 2. KART: İŞLEM AYARLARI ===== -->
    <div class="card-custom">
        <div class="card-header-custom">
            <h5><i class="fas fa-cog"></i> İşlem Ayarları</h5>
        </div>
        <div class="card-body-custom">
            <div class="row g-1">
                <!-- İşlem Türü -->
                <div class="col-md-2">
                    <label class="form-label-custom">İşlem</label>
                    <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn-islem islem-btn" data-value="SATIS" onclick="islemDegistir(this)">
                            <i class="fas fa-arrow-up"></i> SATIŞ
                        </button>
                        <button type="button" class="btn-islem islem-btn" data-value="ALIS" onclick="islemDegistir(this)">
                            <i class="fas fa-arrow-down"></i> ALIŞ
                        </button>
                        <button type="button" class="btn-islem islem-btn" data-value="IADE" onclick="islemDegistir(this)">
                            <i class="fas fa-undo"></i> İADE
                        </button>
                    </div>
                    <input type="hidden" id="islemTuru" name="islem_turu" value="SATIS">
                </div>

                <!-- Belge Türü -->
                <div class="col-md-3">
                    <label class="form-label-custom">Belge</label>
                    <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn-belge belge-btn aktif" data-value="FAT" onclick="belgeDegistir(this)">E-FATURA</button>
                        <button type="button" class="btn-belge belge-btn" data-value="EAR" onclick="belgeDegistir(this)">E-ARŞİV</button>
                        <button type="button" class="btn-belge belge-btn" data-value="MAKBUZ" onclick="belgeDegistir(this)">MAKBUZ</button>
                    </div>
                    <input type="hidden" id="belgeTuru" name="belge_turu" value="FAT">
                </div>

                <!-- Cari / Müşteri -->
                <div class="col-md-4">
                    <label class="form-label-custom">Cari / Müşteri <span class="text-danger">*</span></label>
                    <div class="cari-search-wrapper">
                        <input type="text" id="cariAramaInput" class="form-control-custom cari-input"
                               placeholder="Cari ara..." style="font-size: 11px; padding: 2px 8px; height: 28px;"
                               oninput="cariAra(this.value)">
                        <select name="cari_id" id="cariSelect" class="form-select-custom cari-select"
                                style="font-size: 11px; padding: 2px 8px; height: 28px;" required>
                            <option value="">-- Seçin --</option>
                            <?php foreach ($cariler as $cari): ?>
                            <option value="<?= (int)$cari['id'] ?>"><?= e($cari['unvan']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn-yeni-cari" data-bs-toggle="modal" data-bs-target="#yeniCariModal"
                                style="padding: 2px 10px; font-size: 11px; height: 28px;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <input type="hidden" id="cariIdHidden" name="cari_id_hidden" value="">
                </div>
            </div>
        </div>
    </div>

    <!-- ===== 3. KART: SEPET ===== -->
    <div class="card-custom">
        <div class="card-header-custom">
            <h5><i class="fas fa-shopping-cart"></i> <span id="sepetBaslik">SATIŞ</span> SEPETİ</h5>
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-outline-warning btn-sm px-2" id="karToggleBtn" 
                        onclick="karGosterGizle()" style="font-size: 14px; font-weight: bold; line-height: 1; min-width: 30px;">
                    .
                </button>
                <button type="button" class="btn-danger-sm" onclick="sepetTemizle()">
                    <i class="fas fa-trash"></i> TEMİZLE
                </button>
            </div>
        </div>

        <div class="card-body-custom">
            <form method="POST" action="<?= BASE_URL ?>/hizli_islem_yap.php" id="islemForm">
                <?= csrf_field() ?>

                <div class="table-responsive">
                    <table class="table-custom" id="sepetTablo">
                        <thead>
                            <tr>
                                <th style="width:25px;">#</th>
                                <th>Ürün</th>
                                <th style="width:65px;" class="text-center">Miktar</th>
                                <th style="width:85px;" class="text-end">Fiyat</th>
                                <th style="width:85px;" class="text-end alis-fiyat-col" id="alisFiyatCol">Alış Fiyatı</th>
                                <th style="width:55px;" class="text-center">İsk%</th>
                                <th style="width:55px;" class="text-center">KDV%</th>
                                <th style="width:80px;" class="text-end">Toplam</th>
                                <th style="width:25px;" class="text-center">Sil</th>
                            </tr>
                        </thead>
                        <tbody id="sepetTbody">
                            <tr id="bos-sepet">
                                <td colspan="9" class="text-center text-muted py-2" style="font-size: 11px;">
                                    <i class="fas fa-shopping-cart d-block mb-1" style="font-size: 18px;"></i>
                                    Sepet boş
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" class="text-end"><strong>ARA TOPLAM</strong></td>
                                <td class="text-end" id="araToplam">0</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="6" class="text-end"><strong>İSKONTO</strong></td>
                                <td class="text-end" id="toplamIskonto">0</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="6" class="text-end"><strong>KDV</strong></td>
                                <td class="text-end" id="toplamKdv">0</td>
                                <td></td>
                            </tr>
                            <tr style="border-top: 2px solid var(--badge-success-text);">
                                <td colspan="6" class="text-end" style="font-size: 14px; font-weight: 700; color: var(--badge-success-text);">
                                    GENEL TOPLAM
                                </td>
                                <td class="text-end" style="font-size: 14px; font-weight: 700; color: var(--badge-success-text);" id="genelToplam">0</td>
                                <td></td>
                            </tr>
                            <!-- NET KAR SATIRI (Gizli) -->
                            <tr id="netKarRow" style="display: none; border-top: 2px solid var(--badge-warning-text);">
                                <td colspan="6" class="text-end" style="font-weight: 700; color: var(--badge-warning-text);">K</td>
                                <td class="text-end" id="netKarValue" style="font-weight: 700; color: var(--badge-warning-text);">0</td>
                                <td class="text-end" id="netKarYarim" style="font-weight: 700; color: var(--badge-warning-text); font-size: 12px;">0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="row g-1 mt-1">
                    <div class="col-md-8">
                        <label class="form-label-custom">Açıklama / Not</label>
                        <input type="text" id="aciklama" name="aciklama" class="form-control-custom"
                               placeholder="Opsiyonel açıklama..." style="font-size: 12px; padding: 3px 10px; height: 30px;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-custom">&nbsp;</label>
                        <button type="submit" class="btn-islem-yap" id="islemYapBtn" disabled>
                            <i class="fas fa-check-circle"></i> <span id="islemYapText">SATIŞ</span> YAP
                        </button>
                    </div>
                </div>
                <div class="text-center mt-1">
                    <small class="text-muted" style="font-size: 9px;">Sepete ürün ekledikten sonra işlemi tamamlayın</small>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== ÖDEME DAĞILIMI ===== -->
    <div class="card-custom" style="margin-top: 10px;">
        <div class="card-header-custom">
            <h5><i class="fas fa-money-bill-wave"></i> ÖDEME DAĞILIMI</h5>
            <button type="button" class="btn-sepete-ekle" style="font-size: 11px; padding: 4px 10px;" onclick="odemeSatiriEkle()">
                <i class="fas fa-plus"></i> ÖDEME EKLE
            </button>
        </div>
        <div id="odemeSatirlari"></div>
        <div class="d-flex justify-content-end gap-4 mt-2" style="font-size: 12px;">
            <span>Genel Toplam: <strong id="odemeGenelToplamGoster">0</strong> ₺</span>
            <span>Ödenen: <strong id="odemeOdenenGoster" class="text-success">0</strong> ₺</span>
            <span>Kalan: <strong id="odemeKalanGoster">0</strong> ₺</span>
        </div>
        <small class="text-muted d-block mt-1">
            Ödemeyi birden fazla kanaldan (nakit + havale + kredi kartı gibi) bölebilirsin.
            Ödenen toplam, genel toplamdan az olabilir - kalan tutar veresiye/borç olarak izlenir.
        </small>
    </div>
</div>

<!-- ===== MODALLAR ===== -->
<!-- Evrak No Modal -->
<div class="modal fade" id="evrakNoModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-edit"></i> EVRAK NUMARASI</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label" style="color: var(--text-secondary); font-size: 11px;">Belge Türü</label>
                    <select id="evrakNoBelge" class="form-select" style="background: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary); font-size: 12px;">
                        <option value="FAT">E-FATURA</option>
                        <option value="EAR">E-ARŞİV</option>
                        <option value="STM">SATIŞ MAKBUZU</option>
                        <option value="ALM">ALIŞ MAKBUZU</option>
                        <option value="THM">TAHSİLAT</option>
                        <option value="ODM">ÖDEME</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label" style="color: var(--text-secondary); font-size: 11px;">Numara</label>
                    <input type="text" id="evrakNoInput" class="form-control" style="background: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary); font-size: 14px; font-weight: 700;">
                </div>
                <small style="color: var(--badge-warning-text); font-size: 10px;">
                    <i class="fas fa-info-circle"></i> Numara manuel değiştirilebilir. Boş bırakırsanız otomatik atanır.
                </small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İPTAL</button>
                <button type="button" class="btn btn-success btn-sm" onclick="evrakNoKaydet()">
                    <i class="fas fa-save"></i> KAYDET
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Yeni Cari Modal -->
<div class="modal fade" id="yeniCariModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> YENİ CARİ EKLE</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="yeniCariForm" onsubmit="return false;">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label" style="color: var(--text-secondary);">Ünvan <span class="text-danger">*</span></label>
                            <input type="text" id="modal_cari_unvan" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="color: var(--text-secondary);">Vergi No</label>
                            <input type="text" id="modal_cari_vergi_no" class="form-control" placeholder="1234567890">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="color: var(--text-secondary);">Vergi Dairesi</label>
                            <input type="text" id="modal_cari_vd" class="form-control" placeholder="İstanbul">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="color: var(--text-secondary);">Telefon</label>
                            <input type="text" id="modal_cari_tel" class="form-control" placeholder="0212 555 55 55">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="color: var(--text-secondary);">Email</label>
                            <input type="email" id="modal_cari_email" class="form-control" placeholder="info@firma.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="color: var(--text-secondary);">Yetkili Kişi</label>
                            <input type="text" id="modal_cari_yetkili" class="form-control" placeholder="Ahmet Yılmaz">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="color: var(--text-secondary);">Cari Türü</label>
                            <select id="modal_cari_turu" class="form-select">
                                <option value="MÜŞTERİ">MÜŞTERİ</option>
                                <option value="TEDARİKÇİ">TEDARİKÇİ</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" style="color: var(--text-secondary);">Adres</label>
                            <textarea id="modal_cari_adres" class="form-control" rows="3" placeholder="Adres bilgisi..."></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" style="color: var(--text-secondary);">Açıklama</label>
                            <textarea id="modal_cari_aciklama" class="form-control" rows="2" placeholder="Ek açıklama..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İPTAL</button>
                <button type="button" class="btn btn-success" onclick="modalCariKaydet()">
                    <i class="fas fa-save"></i> KAYDET
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Yeni Ürün Modal -->
<div class="modal fade" id="yeniUrunModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-box"></i> YENİ ÜRÜN EKLE</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="yeniUrunForm" onsubmit="return false;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ÜRÜN KODU <span class="text-danger">*</span></label>
                            <input type="text" id="modal_urun_kodu" class="form-control" placeholder="PR-001" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ÜRÜN ADI <span class="text-danger">*</span></label>
                            <input type="text" id="modal_urun_adi" class="form-control" placeholder="LAPTOP" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">BARKOD</label>
                            <div class="input-group">
                                <input type="text" id="modal_barkod" class="form-control" placeholder="OTOMATİK OLUŞTURULACAK">
                                <button type="button" class="btn btn-outline-primary" onclick="modalBarkodOlustur()">
                                    <i class="fas fa-qrcode"></i> OLUŞTUR
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SERİ NUMARASI</label>
                            <input type="text" id="modal_seri_no" class="form-control" placeholder="SN-2024-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">KATEGORİ</label>
                            <input type="text" id="modal_kategori" class="form-control" placeholder="BİLGİSAYAR">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">BİRİM</label>
                            <select id="modal_birim" class="form-select">
                                <option value="ADET">ADET</option>
                                <option value="KG">KG</option>
                                <option value="METRE">METRE</option>
                                <option value="LİTRE">LİTRE</option>
                                <option value="SAAT">SAAT</option>
                                <option value="PAKET">PAKET</option>
                                <option value="KUTU">KUTU</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ÜRÜN TİPİ</label>
                            <select id="modal_urun_tipi" class="form-select">
                                <option value="SIFIR">SIFIR</option>
                                <option value="2.EL">2.EL</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ALIŞ FİYATI</label>
                            <div class="input-group">
                                <input type="number" id="modal_alis_fiyati" class="form-control" step="0.01" value="0">
                                <select id="modal_alis_doviz" class="form-select" style="max-width: 80px;">
                                    <option value="TL">TL</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SATIŞ FİYATI</label>
                            <div class="input-group">
                                <input type="number" id="modal_satis_fiyati" class="form-control" step="0.01" value="0">
                                <select id="modal_satis_doviz" class="form-select" style="max-width: 80px;">
                                    <option value="TL">TL</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">STOK MİKTARI</label>
                            <input type="number" id="modal_stok_miktari" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">MIN. STOK</label>
                            <input type="number" id="modal_min_stok" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">MAX. STOK</label>
                            <input type="number" id="modal_max_stok" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">AÇIKLAMA</label>
                            <textarea id="modal_aciklama" class="form-control" rows="3" placeholder="ÜRÜN AÇIKLAMASI..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İPTAL</button>
                <button type="button" class="btn btn-success" onclick="modalUrunKaydet()">
                    <i class="fas fa-save"></i> KAYDET
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== ÜRÜN DETAY MODAL ===== -->
<div class="modal fade" id="urunDetayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                <h5 class="modal-title">
                    <i class="fas fa-box" style="color: var(--badge-success-text);"></i>
                    <span id="urunDetayBaslik">ÜRÜN DETAYI</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="urunDetayLoading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <div class="mt-2" style="color: var(--text-secondary);">Yükleniyor...</div>
                </div>
                <div id="urunDetayIcerik" style="display: none;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-title">📋 TEMEL BİLGİLER</div>
                                <div class="info-content">
                                    <div class="detay-satir"><span class="detay-label">Ürün Kodu:</span><span class="detay-value" id="detay_urun_kodu">-</span></div>
                                    <div class="detay-satir"><span class="detay-label">Ürün Adı:</span><span class="detay-value" id="detay_urun_adi">-</span></div>
                                    <div class="detay-satir"><span class="detay-label">Barkod:</span><span class="detay-value" id="detay_barkod">-</span></div>
                                    <div class="detay-satir"><span class="detay-label">Seri No:</span><span class="detay-value" id="detay_seri_no">-</span></div>
                                    <div class="detay-satir"><span class="detay-label">Kategori:</span><span class="detay-value" id="detay_kategori">-</span></div>
                                    <div class="detay-satir"><span class="detay-label">Birim:</span><span class="detay-value" id="detay_birim">-</span></div>
                                    <div class="detay-satir"><span class="detay-label">Ürün Tipi:</span><span class="detay-value" id="detay_urun_tipi">-</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-title">💰 FİYAT & STOK</div>
                                <div class="info-content">
                                    <div class="detay-satir" style="border-bottom: 2px solid var(--border-color); padding-bottom: 10px; margin-bottom: 10px;">
                                        <span class="detay-label" style="font-size: 14px;">Satış Fiyatı:</span>
                                        <span class="detay-value" id="detay_satis_fiyati" style="font-size: 28px; font-weight: 700; color: var(--badge-success-text);">-</span>
                                    </div>
                                    <div class="detay-satir alis-fiyat-satiri">
                                        <span class="detay-label">Alış Fiyatı:</span>
                                        <span class="alis-fiyat-container">
                                            <span class="detay-value alis-fiyat-gizli" id="detay_alis_fiyati">•••••</span>
                                            <button class="btn-alis-goster" onclick="alisFiyatiToggle()" title="Alış fiyatını göster/gizle">
                                                <span class="nokta">•</span>
                                            </button>
                                        </span>
                                    </div>
                                    <div class="detay-satir"><span class="detay-label">Stok Miktarı:</span><span class="detay-value" id="detay_stok_miktari" style="font-weight: 700;">-</span></div>
                                    <div class="detay-satir"><span class="detay-label">Min. Stok:</span><span class="detay-value" id="detay_min_stok">-</span></div>
                                    <div class="detay-satir"><span class="detay-label">Max. Stok:</span><span class="detay-value" id="detay_max_stok">-</span></div>
                                    <div class="detay-satir" style="border-top: 1px solid var(--border-color); padding-top: 8px; margin-top: 8px;">
                                        <span class="detay-label">Kayıt Tarihi:</span><span class="detay-value" id="detay_created_at" style="font-size: 11px; color: var(--text-muted);">-</span>
                                    </div>
                                </div>
                            </div>
                            <div class="info-card mt-2">
                                <div class="info-title">📝 AÇIKLAMA</div>
                                <div class="info-content">
                                    <div id="detay_aciklama" style="font-size: 12px; color: var(--text-primary); line-height: 1.6;">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">KAPAT</button>
                <button type="button" class="btn btn-success btn-sm" onclick="detaydanSepeteEkle()">
                    <i class="fas fa-cart-plus"></i> SEPETE EKLE
                </button>
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
                        <button type="button" class="btn btn-success" onclick="primEvet()">
                            <i class="fas fa-check"></i> EVET
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> HAYIR
                        </button>
                    </div>
                </div>

                <div id="primDetayAlani" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label">Prim Verilecek Kişi <span class="text-danger">*</span></label>
                        <select id="primKisi" class="form-select">
                            <option value="">-- Seçin --</option>
                            <?php foreach ($personeller as $p): ?>
                                <option value="<?= (int)$p['id'] ?>"><?= e($p['unvan']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!$personeller): ?>
                            <small class="text-warning">
                                Henüz PERSONEL türünde bir cari yok.
                                <a href="<?= BASE_URL ?>/cari_ekle.php" target="_blank">Buradan ekleyebilirsiniz</a>
                                (Cari Türü: PERSONEL).
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Hesaplama Yöntemi</label>
                        <div class="d-flex gap-3">
                            <label class="d-flex align-items-center gap-1">
                                <input type="radio" name="primYontem" value="SABIT" checked onchange="primYontemDegisti()"> Sabit Tutar
                            </label>
                            <label class="d-flex align-items-center gap-1">
                                <input type="radio" name="primYontem" value="ORAN" onchange="primYontemDegisti()"> Satıştan Oranla
                            </label>
                        </div>
                    </div>

                    <div id="primSabitAlani" class="mb-3">
                        <label class="form-label">Prim Tutarı (₺)</label>
                        <input type="number" id="primTutarSabit" class="form-control" step="0.01" min="0" value="0">
                    </div>

                    <div id="primOranAlani" class="mb-3" style="display: none;">
                        <label class="form-label">Oran (%)</label>
                        <input type="number" id="primOranYuzde" class="form-control" step="0.1" min="0" max="100" value="0" oninput="primOranHesapla()">
                        <small class="text-muted">Hesaplanan tutar: <strong id="primHesaplananTutar">0.00</strong> ₺</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Açıklama (opsiyonel)</label>
                        <input type="text" id="primAciklama" class="form-control" placeholder="Örn: Temmuz ayı satış primi">
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="primModalFooter" style="display: none;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İPTAL</button>
                <button type="button" class="btn btn-success btn-sm" onclick="primKaydet()">
                    <i class="fas fa-save"></i> KAYDET
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/hizli_islem_script.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>