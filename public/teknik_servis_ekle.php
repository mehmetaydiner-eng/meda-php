<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/numara_manager.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(BASE_URL . '/teknik_servis_ekle.php');

    $cari_id = safe_int($_POST['cari_id'] ?? null, 0) ?: null;
    $urun_adi = turkce_upper(trim($_POST['urun_adi'] ?? ''));
    $marka = turkce_upper(trim($_POST['marka'] ?? ''));
    $model = turkce_upper(trim($_POST['model'] ?? ''));
    $seri_no = turkce_upper(trim($_POST['seri_no'] ?? ''));
    $urun_tipi = trim($_POST['urun_tipi'] ?? '');
    $ariza_tanimi = turkce_upper(trim($_POST['ariza_tanimi'] ?? ''));
    $yapilan_islem = turkce_upper(trim($_POST['yapilan_islem'] ?? ''));
    $notlar = turkce_upper(trim($_POST['notlar'] ?? ''));
    $garanti_durumu = trim($_POST['garanti_durumu'] ?? 'GARANTİSİZ');
    $iscilik_ucreti = safe_float($_POST['iscilik_ucreti'] ?? null);
    $teknik_personel = turkce_upper(trim($_POST['teknik_personel'] ?? ''));
    $durum = trim($_POST['durum'] ?? 'BEKLEMEDE');
    $odeme_durumu = trim($_POST['odeme_durumu'] ?? 'BEKLEMEDE');
    $aksesuarlar = turkce_upper(trim($_POST['aksesuarlar'] ?? ''));
    $kusurlar = turkce_upper(trim($_POST['kusurlar'] ?? ''));

    if (!$cari_id) {
        flash_set('Lütfen bir cari seçin!', 'danger');
        header('Location: ' . BASE_URL . '/teknik_servis_ekle.php');
        exit;
    }
    if ($urun_adi === '') {
        flash_set('Ürün adı zorunludur!', 'danger');
        header('Location: ' . BASE_URL . '/teknik_servis_ekle.php');
        exit;
    }

    $servis_no = generate_servis_no_nm($pdo);

    $pdo->beginTransaction();

    $insert = $pdo->prepare(
        'INSERT INTO teknik_servis
            (servis_no, cari_id, urun_adi, marka, model, seri_no, urun_tipi, ariza_tanimi,
             yapilan_islem, notlar, garanti_durumu, iscilik_ucreti, teknik_personel, durum,
             odeme_durumu, aksesuarlar, kusurlar, gelis_tarihi, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'), datetime(\'now\',\'localtime\'), datetime(\'now\',\'localtime\'))'
    );
    $insert->execute([
        $servis_no, $cari_id, $urun_adi, $marka, $model, $seri_no, $urun_tipi, $ariza_tanimi,
        $yapilan_islem, $notlar, $garanti_durumu, $iscilik_ucreti, $teknik_personel, $durum,
        $odeme_durumu, $aksesuarlar, $kusurlar,
    ]);
    $servis_id = (int)$pdo->lastInsertId();

    $urun_ids  = $_POST['malzeme_urun_ids'] ?? [];
    $miktarlar = $_POST['malzeme_miktarlar'] ?? [];
    $fiyatlar  = $_POST['malzeme_fiyatlar'] ?? [];

    $malzeme_toplam = 0.0;
    for ($i = 0; $i < count($urun_ids); $i++) {
        if (empty($urun_ids[$i])) continue;

        $stmt = $pdo->prepare('SELECT * FROM urunler WHERE id = ?');
        $stmt->execute([$urun_ids[$i]]);
        $urun = $stmt->fetch();
        if (!$urun) continue;

        $miktar = safe_float($miktarlar[$i] ?? 1, 1);
        $fiyat = safe_float($fiyatlar[$i] ?? $urun['satis_fiyati'], (float)$urun['satis_fiyati']);
        $toplam = $miktar * $fiyat;
        $malzeme_toplam += $toplam;

        $insertMalzeme = $pdo->prepare(
            'INSERT INTO servis_malzemeler
                (teknik_servis_id, urun_id, urun_adi, urun_kodu, barkod, miktar, birim, birim_fiyati, toplam_tutar, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
        );
        $insertMalzeme->execute([
            $servis_id, $urun['id'], $urun['urun_adi'], $urun['urun_kodu'], $urun['barkod'],
            $miktar, $urun['birim'] ?: 'ADET', $fiyat, $toplam,
        ]);

        $stokOncesi = (float)$urun['stok_miktari'];
        $yeniStok = $stokOncesi - $miktar;
        $pdo->prepare('UPDATE urunler SET stok_miktari = ? WHERE id = ?')->execute([$yeniStok, $urun['id']]);

        stok_hareketi_ekle(
            $pdo, (int)$urun['id'], 'SERVİS', -$miktar,
            $stokOncesi, $yeniStok, $servis_no, "Teknik Servis Malzemesi - {$servis_no}", $cari_id
        );
    }

    $toplam_ucret = $iscilik_ucreti + $malzeme_toplam;
    $pdo->prepare('UPDATE teknik_servis SET malzeme_ucreti = ?, toplam_ucret = ? WHERE id = ?')
        ->execute([$malzeme_toplam, $toplam_ucret, $servis_id]);

    $pdo->commit();

    flash_set('Servis kaydı oluşturuldu! No: ' . $servis_no, 'success');
    header('Location: ' . BASE_URL . '/teknik_servis_duzenle.php?id=' . $servis_id);
    exit;
}

$cariler = $pdo->query('SELECT * FROM cariler ORDER BY unvan')->fetchAll();

$page_title   = 'YENİ SERVİS KAYDI';
$breadcrumb   = 'Servis Ekleme';
$current_page = 'teknik_servis_ekle';
// extra_css kaldırıldı, artık tema ile uyumlu
require_once __DIR__ . '/../includes/header.php';

$now = new DateTime('now');
?>

<div class="container mt-4">
    <div class="row g-3">
        <!-- ===== SERVİS BİLGİLERİ KARTI ===== -->
        <div class="col-md-6">
            <div class="card-custom">
                <div class="card-header-custom">
                    <h5><i class="fas fa-barcode"></i> SERVİS BİLGİLERİ</h5>
                </div>
                <div class="p-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div style="font-size: 16px; font-weight: 700; color: var(--text-muted, #8a8a8a);">
                                OTO-OLUŞACAK
                            </div>
                        </div>
                        <div class="col-md-6 text-center">
                            <span style="color: var(--text-muted, #6a6a6a); font-size: 12px;">
                                Kayıt sonrası barkod oluşacak
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== TARİH BİLGİLERİ KARTI ===== -->
        <div class="col-md-6">
            <div class="card-custom">
                <div class="card-header-custom">
                    <h5><i class="fas fa-clock"></i> TARİH BİLGİLERİ</h5>
                </div>
                <div class="p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Geliş Tarihi</label>
                            <div style="font-size: 13px; color: var(--text-primary, #e0e0e0);">
                                <?= $now->format('d.m.Y H:i') ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Teslim Tarihi</label>
                            <input type="date" name="teslim_tarihi" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" class="mt-3">
        <?= csrf_field() ?>
        <!-- ===== MÜŞTERİ BİLGİLERİ KARTI ===== -->
        <div class="card-custom mb-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-user"></i> MÜŞTERİ BİLGİLERİ</h5>
            </div>
            <div class="p-3">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small">Müşteri <span class="text-danger">*</span></label>
                        <select name="cari_id" class="form-select form-select-sm" required onchange="cariSec(this.value)">
                            <option value="">Müşteri seçin...</option>
                            <?php foreach ($cariler as $cari): ?>
                            <option value="<?= (int)$cari['id'] ?>"><?= e($cari['unvan']) ?> - <?= e($cari['vergi_no'] ?: 'VERGİ NO YOK') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary-custom btn-sm w-100" data-bs-toggle="modal" data-bs-target="#yeniCariModal">
                            <i class="fas fa-plus-circle"></i> YENİ CARİ EKLE
                        </button>
                    </div>
                </div>
                <div id="cari-detay" class="row g-2 mt-2" style="font-size: 12px; color: var(--text-muted, #8a8a8a); display: none;">
                    <div class="col-md-4"><strong>Ünvan:</strong> <span id="cari-unvan">-</span></div>
                    <div class="col-md-4"><strong>Telefon:</strong> <span id="cari-tel">-</span></div>
                    <div class="col-md-4"><strong>Email:</strong> <span id="cari-email">-</span></div>
                    <div class="col-md-12"><strong>Adres:</strong> <span id="cari-adres">-</span></div>
                </div>
            </div>
        </div>

        <!-- ===== CİHAZ BİLGİLERİ KARTI ===== -->
        <div class="card-custom mb-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-laptop"></i> CİHAZ BİLGİLERİ</h5>
            </div>
            <div class="p-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small">Ürün Adı <span class="text-danger">*</span></label>
                        <input type="text" name="urun_adi" class="form-control form-control-sm" required placeholder="LAPTOP">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Ürün Tipi</label>
                        <select name="urun_tipi" class="form-select form-select-sm">
                            <option value="LAPTOP">LAPTOP</option>
                            <option value="MASAÜSTÜ">MASAÜSTÜ</option>
                            <option value="TABLET">TABLET</option>
                            <option value="TELEFON">TELEFON</option>
                            <option value="YAZICI">YAZICI</option>
                            <option value="MONİTÖR">MONİTÖR</option>
                            <option value="DİĞER">DİĞER</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Marka</label>
                        <input type="text" name="marka" class="form-control form-control-sm" placeholder="HP">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Model</label>
                        <input type="text" name="model" class="form-control form-control-sm" placeholder="Pavilion 15">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Seri Numarası</label>
                        <input type="text" name="seri_no" class="form-control form-control-sm" placeholder="SN-2024-001">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Garanti Durumu</label>
                        <select name="garanti_durumu" class="form-select form-select-sm">
                            <option value="GARANTİSİZ">GARANTİSİZ</option>
                            <option value="GARANTİLİ">GARANTİLİ</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Aksesuar</label>
                        <input type="text" name="aksesuarlar" class="form-control form-control-sm" placeholder="Şarj aleti, mouse...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Cihaz Notları</label>
                        <input type="text" name="kusurlar" class="form-control form-control-sm" placeholder="Çizik, darbe, ekran kırığı...">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small">Bildirilen Arıza</label>
                        <input type="text" name="ariza_tanimi" class="form-control form-control-sm" placeholder="Müşterinin bildirdiği arıza...">
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== RAPOR KARTI ===== -->
        <div class="card-custom mb-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-clipboard-list"></i> RAPOR</h5>
            </div>
            <div class="p-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small">Tespit Edilen Arıza</label>
                        <textarea name="yapilan_islem" class="form-control form-control-sm" rows="3" placeholder="Teknisyen tarafından tespit edilen arıza..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Sonuç</label>
                        <textarea name="notlar" class="form-control form-control-sm" rows="3" placeholder="Yapılan işlem ve sonuç..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Durum</label>
                        <select name="durum" class="form-select form-select-sm">
                            <option value="BEKLEMEDE">BEKLEMEDE</option>
                            <option value="İŞLEMDE">İŞLEMDE</option>
                            <option value="TAMAMLANDI">TAMAMLANDI</option>
                            <option value="TESLİM EDİLDİ">TESLİM EDİLDİ</option>
                            <option value="İPTAL">İPTAL</option>
                            <option value="İADE">İADE</option>
                            <option value="ONAY BEKLENİYOR">ONAY BEKLENİYOR</option>
                            <option value="ONAYLANMADI">ONAYLANMADI</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Ödeme Durumu</label>
                        <select name="odeme_durumu" class="form-select form-select-sm">
                            <option value="BEKLEMEDE">BEKLEMEDE</option>
                            <option value="KISMİ ÖDENDİ">KISMİ ÖDENDİ</option>
                            <option value="ÖDENDİ">ÖDENDİ</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">İlgili Kişi</label>
                        <input type="text" name="teknik_personel" class="form-control form-control-sm" placeholder="Ahmet Yılmaz">
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== KULLANILAN ÜRÜNLER KARTI ===== -->
        <div class="card-custom mb-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-boxes"></i> KULLANILAN ÜRÜNLER</h5>
            </div>
            <div class="p-3">
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <input type="text" id="malzeme-ara" class="form-control form-control-sm" placeholder="Ürün adı veya barkod ara...">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary-custom btn-sm w-100" onclick="malzemeAra()">
                            <i class="fas fa-search"></i> ARA
                        </button>
                    </div>
                    <div class="col-md-4">
                        <select id="malzeme-listesi" class="form-select form-select-sm">
                            <option value="">Ürün seçin...</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-success-custom btn-sm w-100" onclick="malzemeEkle(event)">
                            <i class="fas fa-plus"></i> EKLE
                        </button>
                    </div>
                </div>
                <div class="mt-2 mb-2">
                    <button type="button" class="btn btn-warning-custom btn-sm w-100" data-bs-toggle="modal" data-bs-target="#yeniUrunModal" style="font-size: 11px; padding: 4px 12px;">
                        <i class="fas fa-plus-circle"></i> STOKTA OLMAYAN ÜRÜN EKLE
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table-custom" id="malzeme-tablosu">
                        <thead>
                            <tr>
                                <th style="width: 30px;">#</th>
                                <th>Ürün Adı</th>
                                <th>Barkod</th>
                                <th style="width: 70px;">Miktar</th>
                                <th style="width: 90px;">Birim Fiyat</th>
                                <th style="width: 90px;">Toplam</th>
                                <th style="width: 35px;">İşlem</th>
                            </tr>
                        </thead>
                        <tbody id="malzeme-tbody">
                            <tr id="bos-malzeme">
                                <td colspan="7" class="text-center text-muted py-2">Henüz ürün eklenmemiş</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end"><strong>ÜRÜN TOPLAMI</strong></td>
                                <td class="text-end" id="malzeme-toplam">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- ===== ÜCRET BİLGİLERİ KARTI ===== -->
        <div class="card-custom mb-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-money-bill-wave"></i> ÜCRET BİLGİLERİ</h5>
            </div>
            <div class="p-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small">TEKNİK SERVİS BEDELİ</label>
                        <input type="number" name="iscilik_ucreti" class="form-control form-control-sm" step="0.01" value="0" onchange="hesaplaToplam()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">ÜRÜN TOPLAMI</label>
                        <input type="text" class="form-control form-control-sm" id="malzeme-ucreti" value="0.00" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">TOPLAM ÜCRET</label>
                        <input type="text" class="form-control form-control-sm" id="toplam-ucret" value="0.00" readonly style="font-weight: 700; color: var(--success, #4ad46a);">
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== BUTONLAR ===== -->
        <div class="d-flex gap-2 justify-content-end">
            <button type="submit" class="btn btn-success-custom">
                <i class="fas fa-save"></i> KAYDET
            </button>
            <a href="<?= BASE_URL ?>/teknik_servis_listesi.php" class="btn btn-outline-secondary">İPTAL</a>
        </div>
    </form>
</div>

<!-- ===== YENİ CARİ EKLE MODAL ===== -->
<div class="modal fade" id="yeniCariModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> YENİ CARİ EKLE</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label small">ÜNVAN <span class="text-danger">*</span></label>
                        <input type="text" id="modal_cari_unvan" class="form-control form-control-sm" placeholder="Firma Ünvanı" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small">CARİ TÜRÜ</label>
                        <select id="modal_cari_turu" class="form-select form-select-sm">
                            <option value="MÜŞTERİ">MÜŞTERİ</option>
                            <option value="TEDARİKÇİ">TEDARİKÇİ</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">VERGİ NO</label>
                        <input type="text" id="modal_cari_vergi_no" class="form-control form-control-sm" placeholder="1234567890">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">VERGİ DAİRESİ</label>
                        <input type="text" id="modal_cari_vd" class="form-control form-control-sm" placeholder="İSTANBUL">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">TELEFON</label>
                        <input type="text" id="modal_cari_tel" class="form-control form-control-sm" placeholder="0212 555 55 55">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">EMAIL</label>
                        <input type="email" id="modal_cari_email" class="form-control form-control-sm" placeholder="info@firma.com">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small">YETKİLİ KİŞİ</label>
                        <input type="text" id="modal_cari_yetkili" class="form-control form-control-sm" placeholder="Ahmet Yılmaz">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small">ADRES</label>
                        <textarea id="modal_cari_adres" class="form-control form-control-sm" rows="2" placeholder="Adres bilgisi..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İPTAL</button>
                <button type="button" class="btn btn-success-custom btn-sm" onclick="modalCariKaydet()">
                    <i class="fas fa-save"></i> KAYDET
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== YENİ ÜRÜN EKLE MODAL ===== -->
<div class="modal fade" id="yeniUrunModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-box"></i> YENİ ÜRÜN EKLE</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small">ÜRÜN KODU <span class="text-danger">*</span></label>
                        <input type="text" id="modal_urun_kodu" class="form-control form-control-sm" placeholder="PR-001" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">ÜRÜN ADI <span class="text-danger">*</span></label>
                        <input type="text" id="modal_urun_adi" class="form-control form-control-sm" placeholder="LAPTOP" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">BARKOD</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="modal_barkod" class="form-control" placeholder="OTOMATİK">
                            <button type="button" class="btn btn-outline-primary" onclick="modalBarkodOlustur()">
                                <i class="fas fa-qrcode"></i> OLUŞTUR
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">SERİ NUMARASI</label>
                        <input type="text" id="modal_seri_no" class="form-control form-control-sm" placeholder="SN-2024-001">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">KATEGORİ</label>
                        <input type="text" id="modal_kategori" class="form-control form-control-sm" placeholder="BİLGİSAYAR">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">BİRİM</label>
                        <select id="modal_birim" class="form-select form-select-sm">
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
                        <label class="form-label small">ÜRÜN TİPİ</label>
                        <select id="modal_urun_tipi" class="form-select form-select-sm">
                            <option value="SIFIR">SIFIR</option>
                            <option value="2.EL">2.EL</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">ALIŞ FİYATI</label>
                        <div class="input-group input-group-sm">
                            <input type="number" id="modal_alis_fiyati" class="form-control" step="0.01" value="0">
                            <select id="modal_alis_doviz" class="form-select" style="max-width: 80px;">
                                <option value="TL">TL</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">SATIŞ FİYATI</label>
                        <div class="input-group input-group-sm">
                            <input type="number" id="modal_satis_fiyati" class="form-control" step="0.01" value="0">
                            <select id="modal_satis_doviz" class="form-select" style="max-width: 80px;">
                                <option value="TL">TL</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">STOK MİKTARI</label>
                        <input type="number" id="modal_stok_miktari" class="form-control form-control-sm" step="0.01" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">MIN. STOK</label>
                        <input type="number" id="modal_min_stok" class="form-control form-control-sm" step="0.01" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">MAX. STOK</label>
                        <input type="number" id="modal_max_stok" class="form-control form-control-sm" step="0.01" value="0">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small">AÇIKLAMA</label>
                        <textarea id="modal_aciklama" class="form-control form-control-sm" rows="2" placeholder="ÜRÜN AÇIKLAMASI..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İPTAL</button>
                <button type="button" class="btn btn-success-custom btn-sm" onclick="modalUrunKaydet()">
                    <i class="fas fa-save"></i> KAYDET
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$api_base_json = json_encode(BASE_URL);
$extra_js = <<<JS
<script>
var API_BASE = {$api_base_json};

function cariSec(id) {
    if (!id) { document.getElementById('cari-detay').style.display = 'none'; return; }
    fetch(API_BASE + '/api/cari_detay.php?id=' + id)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            document.getElementById('cari-detay').style.display = 'flex';
            document.getElementById('cari-unvan').textContent = data.unvan || '-';
            document.getElementById('cari-tel').textContent = data.telefon || '-';
            document.getElementById('cari-email').textContent = data.email || '-';
            document.getElementById('cari-adres').textContent = data.adres || '-';
        });
}

function modalCariKaydet() {
    var unvan = document.getElementById('modal_cari_unvan').value.trim().toUpperCase();
    var cari_turu = document.getElementById('modal_cari_turu').value;
    if (!unvan) { alert('Ünvan zorunludur!'); return; }

    var formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('unvan', unvan);
    formData.append('cari_turu', cari_turu);
    formData.append('vergi_no', document.getElementById('modal_cari_vergi_no').value.trim().toUpperCase());
    formData.append('vergi_dairesi', document.getElementById('modal_cari_vd').value.trim().toUpperCase());
    formData.append('telefon', document.getElementById('modal_cari_tel').value.trim());
    formData.append('email', document.getElementById('modal_cari_email').value.trim().toLowerCase());
    formData.append('yetkili', document.getElementById('modal_cari_yetkili').value.trim().toUpperCase());
    formData.append('adres', document.getElementById('modal_cari_adres').value.trim().toUpperCase());

    fetch(API_BASE + '/api/cari_ekle_ajax.php', { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                var select = document.querySelector('select[name="cari_id"]');
                var option = document.createElement('option');
                option.value = data.cari_id;
                option.text = data.unvan + ' - ' + (data.vergi_no || 'VERGİ NO YOK') + ' (' + data.cari_turu + ')';
                select.appendChild(option);
                select.value = data.cari_id;
                cariSec(data.cari_id);

                bootstrap.Modal.getInstance(document.getElementById('yeniCariModal')).hide();

                document.getElementById('modal_cari_unvan').value = '';
                document.getElementById('modal_cari_turu').value = 'MÜŞTERİ';
                document.getElementById('modal_cari_vergi_no').value = '';
                document.getElementById('modal_cari_vd').value = '';
                document.getElementById('modal_cari_tel').value = '';
                document.getElementById('modal_cari_email').value = '';
                document.getElementById('modal_cari_yetkili').value = '';
                document.getElementById('modal_cari_adres').value = '';

                alert('Cari başarıyla eklendi!');
            } else {
                alert('Hata: ' + (data.message || 'Cari eklenemedi!'));
            }
        })
        .catch(function(error) { alert('Hata oluştu: ' + error); });
}

function malzemeAra() {
    var q = document.getElementById('malzeme-ara').value.trim();
    if (q.length < 2) return;
    fetch(API_BASE + '/api/stok_ara.php?q=' + encodeURIComponent(q))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var select = document.getElementById('malzeme-listesi');
            select.innerHTML = '<option value="">Ürün seçin...</option>';
            data.forEach(function(urun) {
                select.innerHTML += '<option value="' + urun.id + '" data-fiyat="' + urun.satis_fiyati + '" data-ad="' + urun.urun_adi + '" data-barkod="' + (urun.barkod || '') + '">' + urun.urun_adi + ' - ' + (urun.barkod || 'BARKOD YOK') + '</option>';
            });
        });
}

function malzemeEkle(event) {
    // Form submit olmasın diye
    if (event) event.preventDefault();

    var select = document.getElementById('malzeme-listesi');
    var selected = select.options[select.selectedIndex];
    if (!selected.value) { alert('Lütfen bir ürün seçin!'); return; }

    var tbody = document.getElementById('malzeme-tbody');
    var bos = document.getElementById('bos-malzeme');
    if (bos) bos.remove();

    var index = tbody.children.length + 1;
    var row = document.createElement('tr');
    row.innerHTML =
        '<td>' + index + '</td>' +
        '<td><input type="hidden" name="malzeme_urun_ids[]" value="' + selected.value + '">' + selected.dataset.ad + '</td>' +
        '<td>' + (selected.dataset.barkod || '-') + '</td>' +
        '<td><input type="number" class="form-control form-control-sm miktar-input" name="malzeme_miktarlar[]" value="1" min="0.01" step="0.01" onchange="malzemeSatirGuncelle(this)"></td>' +
        '<td><input type="number" class="form-control form-control-sm fiyat-input" name="malzeme_fiyatlar[]" value="' + selected.dataset.fiyat + '" min="0" step="0.01" onchange="malzemeSatirGuncelle(this)"></td>' +
        '<td class="text-end"><input type="text" class="form-control form-control-sm toplam-input" value="' + selected.dataset.fiyat + '" readonly style="text-align:right;"></td>' +
        '<td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm" onclick="malzemeSatirSil(this)"><i class="fas fa-times"></i></button></td>';
    tbody.appendChild(row);
    malzemeHesapla();
}

function malzemeSatirGuncelle(input) {
    var row = input.closest('tr');
    var miktar = parseFloat(row.querySelector('.miktar-input').value) || 0;
    var fiyat = parseFloat(row.querySelector('.fiyat-input').value) || 0;
    row.querySelector('.toplam-input').value = (miktar * fiyat).toFixed(2);
    malzemeHesapla();
}

function malzemeSatirSil(btn) {
    var row = btn.closest('tr');
    row.remove();
    document.querySelectorAll('#malzeme-tbody tr').forEach(function(tr, i) { tr.cells[0].textContent = i + 1; });
    malzemeHesapla();
}

function malzemeHesapla() {
    var toplam = 0;
    document.querySelectorAll('#malzeme-tbody tr').forEach(function(row) {
        var input = row.querySelector('.toplam-input');
        if (input) toplam += parseFloat(input.value) || 0;
    });
    document.getElementById('malzeme-toplam').textContent = toplam.toFixed(2);
    document.getElementById('malzeme-ucreti').value = toplam.toFixed(2);
    hesaplaToplam();
}

function hesaplaToplam() {
    var iscilik = parseFloat(document.querySelector('input[name="iscilik_ucreti"]').value) || 0;
    var malzeme = parseFloat(document.getElementById('malzeme-ucreti').value) || 0;
    document.getElementById('toplam-ucret').value = (iscilik + malzeme).toFixed(2);
}

function modalBarkodOlustur() {
    var prefix = '869';
    var random = '';
    for (var i = 0; i < 9; i++) { random += Math.floor(Math.random() * 10); }
    document.getElementById('modal_barkod').value = prefix + random + Math.floor(Math.random() * 10);
}

function modalUrunKaydet() {
    var urun_kodu = document.getElementById('modal_urun_kodu').value.trim().toUpperCase();
    var urun_adi = document.getElementById('modal_urun_adi').value.trim().toUpperCase();
    if (!urun_kodu) { alert('Ürün kodu zorunludur!'); return; }
    if (!urun_adi) { alert('Ürün adı zorunludur!'); return; }

    var formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('urun_kodu', urun_kodu);
    formData.append('urun_adi', urun_adi);
    formData.append('barkod', document.getElementById('modal_barkod').value.trim());
    formData.append('seri_no', document.getElementById('modal_seri_no').value.trim().toUpperCase());
    formData.append('kategori', document.getElementById('modal_kategori').value.trim().toUpperCase());
    formData.append('birim', document.getElementById('modal_birim').value);
    formData.append('urun_tipi', document.getElementById('modal_urun_tipi').value);
    formData.append('alis_fiyati', parseFloat(document.getElementById('modal_alis_fiyati').value) || 0);
    formData.append('alis_fiyati_doviz', document.getElementById('modal_alis_doviz').value);
    formData.append('satis_fiyati', parseFloat(document.getElementById('modal_satis_fiyati').value) || 0);
    formData.append('satis_fiyati_doviz', document.getElementById('modal_satis_doviz').value);
    formData.append('stok_miktari', parseFloat(document.getElementById('modal_stok_miktari').value) || 0);
    formData.append('min_stok', parseFloat(document.getElementById('modal_min_stok').value) || 0);
    formData.append('max_stok', parseFloat(document.getElementById('modal_max_stok').value) || 0);
    formData.append('aciklama', document.getElementById('modal_aciklama').value.trim().toUpperCase());

    fetch(API_BASE + '/api/stok_ekle_ajax.php', { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                var select = document.getElementById('malzeme-listesi');
                var option = document.createElement('option');
                option.value = data.urun_id;
                option.dataset.fiyat = data.satis_fiyati;
                option.dataset.ad = data.urun_adi;
                option.dataset.barkod = data.barkod || '';
                option.text = data.urun_adi + ' - ' + (data.barkod || 'BARKOD YOK');
                select.appendChild(option);
                select.value = data.urun_id;
                bootstrap.Modal.getInstance(document.getElementById('yeniUrunModal')).hide();
                alert('Ürün başarıyla eklendi!');
            } else {
                alert('Hata: ' + (data.message || 'Ürün eklenemedi!'));
            }
        })
        .catch(function(error) { alert('Hata oluştu: ' + error); });
}

document.addEventListener('DOMContentLoaded', function() { hesaplaToplam(); });
document.getElementById('malzeme-ara').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') { malzemeAra(); }
});
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>