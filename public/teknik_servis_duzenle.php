<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$id = safe_int($_GET['id'] ?? null);
if (!$id) {
    header('Location: ' . BASE_URL . '/teknik_servis_listesi.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM teknik_servis WHERE id = ?');
$stmt->execute([$id]);
$servis = $stmt->fetch();
if (!$servis) {
    http_response_code(404);
    die('Servis kaydı bulunamadı.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(BASE_URL . '/teknik_servis_duzenle.php?id=' . $id);

    $cari_id = safe_int($_POST['cari_id'] ?? null, 0) ?: null;
    $urun_adi = turkce_upper(trim($_POST['urun_adi'] ?? ''));
    $marka = turkce_upper(trim($_POST['marka'] ?? ''));
    $model = turkce_upper(trim($_POST['model'] ?? ''));
    $seri_no = turkce_upper(trim($_POST['seri_no'] ?? ''));
    $urun_tipi = trim($_POST['urun_tipi'] ?? '');
    $ariza_tanimi = turkce_upper(trim($_POST['ariza_tanimi'] ?? ''));
    $yapilan_islem = turkce_upper(trim($_POST['yapilan_islem'] ?? ''));
    $notlar = turkce_upper(trim($_POST['notlar'] ?? ''));
    $durum = trim($_POST['durum'] ?? 'BEKLEMEDE');
    $garanti_durumu = trim($_POST['garanti_durumu'] ?? 'GARANTİSİZ');
    $iscilik_ucreti = safe_float($_POST['iscilik_ucreti'] ?? null);
    $teknik_personel = turkce_upper(trim($_POST['teknik_personel'] ?? ''));
    $odeme_durumu = trim($_POST['odeme_durumu'] ?? 'BEKLEMEDE');
    $aksesuarlar = turkce_upper(trim($_POST['aksesuarlar'] ?? ''));
    $kusurlar = turkce_upper(trim($_POST['kusurlar'] ?? ''));
    $teslim_tarihi_str = trim($_POST['teslim_tarihi'] ?? '');
    $teslim_tarihi = $teslim_tarihi_str !== '' ? $teslim_tarihi_str . ' 00:00:00' : null;

    $pdo->beginTransaction();

    $update = $pdo->prepare(
        'UPDATE teknik_servis SET
            cari_id=?, urun_adi=?, marka=?, model=?, seri_no=?, urun_tipi=?, ariza_tanimi=?,
            yapilan_islem=?, notlar=?, durum=?, garanti_durumu=?, iscilik_ucreti=?, teknik_personel=?,
            odeme_durumu=?, aksesuarlar=?, kusurlar=?, teslim_tarihi=?, updated_at=datetime(\'now\',\'localtime\')
         WHERE id=?'
    );
    $update->execute([
        $cari_id, $urun_adi, $marka, $model, $seri_no, $urun_tipi, $ariza_tanimi,
        $yapilan_islem, $notlar, $durum, $garanti_durumu, $iscilik_ucreti, $teknik_personel,
        $odeme_durumu, $aksesuarlar, $kusurlar, $teslim_tarihi, $id,
    ]);

    $pdo->prepare('DELETE FROM servis_malzemeler WHERE teknik_servis_id = ?')->execute([$id]);

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
            $id, $urun['id'], $urun['urun_adi'], $urun['urun_kodu'], $urun['barkod'],
            $miktar, $urun['birim'] ?: 'ADET', $fiyat, $toplam,
        ]);
    }

    $toplam_ucret = $iscilik_ucreti + $malzeme_toplam;
    $pdo->prepare('UPDATE teknik_servis SET malzeme_ucreti = ?, toplam_ucret = ? WHERE id = ?')
        ->execute([$malzeme_toplam, $toplam_ucret, $id]);

    $pdo->commit();

    flash_set('Servis kaydı güncellendi!', 'success');
    header('Location: ' . BASE_URL . '/teknik_servis_duzenle.php?id=' . $id);
    exit;
}

$cariler = $pdo->query('SELECT * FROM cariler ORDER BY unvan')->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM cariler WHERE id = ?');
$stmt->execute([$servis['cari_id']]);
$servisCari = $servis['cari_id'] ? $stmt->fetch() : null;

$stmt = $pdo->prepare('SELECT * FROM servis_malzemeler WHERE teknik_servis_id = ?');
$stmt->execute([$id]);
$malzemeler = $stmt->fetchAll();

$page_title   = 'TEKNİK SERVİS DÜZENLE';
$breadcrumb   = 'Servis Düzenleme';
$current_page = 'teknik_servis_duzenle';
require_once __DIR__ . '/../includes/header.php';

function durum_badge($durum): string {
    $map = [
        'BEKLEMEDE' => 'warning',
        'İŞLEMDE' => 'info',
        'TAMAMLANDI' => 'success',
        'TESLİM EDİLDİ' => 'primary',
        'İPTAL' => 'danger',
        'İADE' => 'secondary',
        'ONAY BEKLENİYOR' => 'warning',
        'ONAYLANMADI' => 'danger',
    ];
    return $map[$durum] ?? 'secondary';
}
?>

<div class="container mt-4">

    <!-- Başlık Kartı -->
    <div class="card-custom mb-4">
        <div class="card-header-custom">
            <h5><i class="fas fa-tools"></i> Teknik Servis Düzenle</h5>
            <div>
                <span class="badge bg-<?= durum_badge($servis['durum']) ?>"><?= e($servis['durum']) ?></span>
                <span class="badge bg-<?= $servis['odeme_durumu'] === 'ÖDENDİ' ? 'success' : ($servis['odeme_durumu'] === 'KISMİ ÖDENDİ' ? 'warning' : 'secondary') ?>">
                    <?= e($servis['odeme_durumu']) ?>
                </span>
                <span class="badge bg-<?= $servis['garanti_durumu'] === 'GARANTİLİ' ? 'success' : 'secondary' ?>">
                    <?= e($servis['garanti_durumu']) ?>
                </span>
            </div>
        </div>
        <div class="p-3">
            <div class="row">
                <div class="col-md-6">
                    <strong>Servis No:</strong> <?= e($servis['servis_no']) ?><br>
                    <strong>Geliş Tarihi:</strong> <?= $servis['gelis_tarihi'] ? format_tarih($servis['gelis_tarihi'], 'd.m.Y H:i') : '-' ?>
                </div>
                <div class="col-md-6 text-md-end">
                    <?php if (!empty($servis['barkod'])): ?>
                        <img src="https://barcode.tec-it.com/barcode.ashx?data=<?= urlencode($servis['barkod']) ?>&code=Code128&dpi=96" alt="Barkod" style="height:40px;">
                        <span class="text-muted ms-2"><?= e($servis['barkod']) ?></span>
                    <?php else: ?>
                        <span class="text-muted">Barkod yok</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <form method="POST">
        <?= csrf_field() ?>

        <!-- MÜŞTERİ BİLGİLERİ -->
        <div class="card-custom mb-4">
            <div class="card-header-custom">
                <h5><i class="fas fa-user"></i> Müşteri Bilgileri</h5>
            </div>
            <div class="p-3">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Müşteri <span class="text-danger">*</span></label>
                        <select name="cari_id" class="form-select" required onchange="cariSec(this.value)">
                            <option value="">Müşteri seçin...</option>
                            <?php foreach ($cariler as $cari): ?>
                            <option value="<?= (int)$cari['id'] ?>" <?= $servis['cari_id'] == $cari['id'] ? 'selected' : '' ?>>
                                <?= e($cari['unvan']) ?> - <?= e($cari['vergi_no'] ?: 'VERGİ NO YOK') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#yeniCariModal">
                            <i class="fas fa-plus-circle"></i> YENİ CARİ EKLE
                        </button>
                    </div>
                </div>
                <div id="cari-detay" class="row g-2 mt-2" style="font-size: 12px; color: #8a8a8a; display: <?= $servisCari ? 'flex' : 'none' ?>;">
                    <div class="col-md-4"><strong>Ünvan:</strong> <span id="cari-unvan"><?= e($servisCari['unvan'] ?? '-') ?></span></div>
                    <div class="col-md-4"><strong>Telefon:</strong> <span id="cari-tel"><?= e($servisCari['telefon'] ?? '-') ?></span></div>
                    <div class="col-md-4"><strong>Email:</strong> <span id="cari-email"><?= e($servisCari['email'] ?? '-') ?></span></div>
                    <div class="col-md-12"><strong>Adres:</strong> <span id="cari-adres"><?= e($servisCari['adres'] ?? '-') ?></span></div>
                </div>
            </div>
        </div>

        <!-- CİHAZ BİLGİLERİ -->
        <div class="card-custom mb-4">
            <div class="card-header-custom">
                <h5><i class="fas fa-laptop"></i> Cihaz Bilgileri</h5>
            </div>
            <div class="p-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Ürün Adı <span class="text-danger">*</span></label>
                        <input type="text" name="urun_adi" class="form-control" value="<?= e($servis['urun_adi']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Ürün Tipi</label>
                        <select name="urun_tipi" class="form-select">
                            <?php foreach (['LAPTOP','MASAÜSTÜ','TABLET','TELEFON','YAZICI','MONİTÖR','DİĞER'] as $tip): ?>
                            <option value="<?= $tip ?>" <?= $servis['urun_tipi'] === $tip ? 'selected' : '' ?>><?= $tip ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Marka</label>
                        <input type="text" name="marka" class="form-control" value="<?= e($servis['marka'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" class="form-control" value="<?= e($servis['model'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Seri Numarası</label>
                        <input type="text" name="seri_no" class="form-control" value="<?= e($servis['seri_no'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Garanti Durumu</label>
                        <select name="garanti_durumu" class="form-select">
                            <option value="GARANTİSİZ" <?= $servis['garanti_durumu'] === 'GARANTİSİZ' ? 'selected' : '' ?>>GARANTİSİZ</option>
                            <option value="GARANTİLİ" <?= $servis['garanti_durumu'] === 'GARANTİLİ' ? 'selected' : '' ?>>GARANTİLİ</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Aksesuar</label>
                        <input type="text" name="aksesuarlar" class="form-control" value="<?= e($servis['aksesuarlar'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cihaz Notları</label>
                        <input type="text" name="kusurlar" class="form-control" value="<?= e($servis['kusurlar'] ?? '') ?>">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Bildirilen Arıza</label>
                        <input type="text" name="ariza_tanimi" class="form-control" value="<?= e($servis['ariza_tanimi'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- RAPOR -->
        <div class="card-custom mb-4">
            <div class="card-header-custom">
                <h5><i class="fas fa-clipboard-list"></i> Rapor</h5>
            </div>
            <div class="p-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tespit Edilen Arıza</label>
                        <textarea name="yapilan_islem" class="form-control" rows="3"><?= e($servis['yapilan_islem'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sonuç</label>
                        <textarea name="notlar" class="form-control" rows="3"><?= e($servis['notlar'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Durum</label>
                        <select name="durum" class="form-select">
                            <?php foreach (['BEKLEMEDE','İŞLEMDE','TAMAMLANDI','TESLİM EDİLDİ','İPTAL','İADE','ONAY BEKLENİYOR','ONAYLANMADI'] as $d): ?>
                            <option value="<?= $d ?>" <?= $servis['durum'] === $d ? 'selected' : '' ?>><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ödeme Durumu</label>
                        <select name="odeme_durumu" class="form-select">
                            <?php foreach (['BEKLEMEDE','KISMİ ÖDENDİ','ÖDENDİ'] as $od): ?>
                            <option value="<?= $od ?>" <?= $servis['odeme_durumu'] === $od ? 'selected' : '' ?>><?= $od ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">İlgili Kişi</label>
                        <input type="text" name="teknik_personel" class="form-control" value="<?= e($servis['teknik_personel'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Teslim Tarihi</label>
                        <input type="date" name="teslim_tarihi" class="form-control" value="<?= $servis['teslim_tarihi'] ? date('Y-m-d', strtotime($servis['teslim_tarihi'])) : '' ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- KULLANILAN ÜRÜNLER -->
        <div class="card-custom mb-4">
            <div class="card-header-custom">
                <h5><i class="fas fa-boxes"></i> Kullanılan Ürünler</h5>
            </div>
            <div class="p-3">
                <div class="row g-2 mb-3">
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
                        <button type="button" class="btn btn-success-custom btn-sm w-100" onclick="malzemeEkle()">
                            <i class="fas fa-plus"></i> EKLE
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <button type="button" class="btn btn-warning btn-sm w-100" data-bs-toggle="modal" data-bs-target="#yeniUrunModal">
                        <i class="fas fa-plus-circle"></i> STOKTA OLMAYAN ÜRÜN EKLE
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table-custom" id="malzeme-tablosu">
                        <thead>
                            <tr>
                                <th style="width:30px;">#</th>
                                <th>Ürün Adı</th>
                                <th>Barkod</th>
                                <th style="width:80px;">Miktar</th>
                                <th style="width:100px;">Birim Fiyat</th>
                                <th style="width:100px;">Toplam</th>
                                <th style="width:35px;">İşlem</th>
                            </tr>
                        </thead>
                        <tbody id="malzeme-tbody">
                            <?php if ($malzemeler): ?>
                                <?php foreach ($malzemeler as $i => $malzeme): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <input type="hidden" name="malzeme_urun_ids[]" value="<?= (int)$malzeme['urun_id'] ?>">
                                        <?= e($malzeme['urun_adi']) ?>
                                    </td>
                                    <td><?= e($malzeme['barkod'] ?: '-') ?></td>
                                    <td><input type="number" class="form-control form-control-sm miktar-input" name="malzeme_miktarlar[]" value="<?= number_format((float)$malzeme['miktar'], 2, '.', '') ?>" min="0.01" step="0.01" onchange="malzemeSatirGuncelle(this)"></td>
                                    <td><input type="number" class="form-control form-control-sm fiyat-input" name="malzeme_fiyatlar[]" value="<?= number_format((float)$malzeme['birim_fiyati'], 2, '.', '') ?>" min="0" step="0.01" onchange="malzemeSatirGuncelle(this)"></td>
                                    <td class="text-end"><input type="text" class="form-control form-control-sm toplam-input" value="<?= number_format((float)$malzeme['toplam_tutar'], 2, '.', '') ?>" readonly></td>
                                    <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="malzemeSatirSil(this)"><i class="fas fa-times"></i></button></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr id="bos-malzeme">
                                    <td colspan="7" class="text-center text-muted py-2">Henüz ürün eklenmemiş</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end"><strong>Ürün Toplamı</strong></td>
                                <td class="text-end" id="malzeme-toplam"><?= number_format((float)$servis['malzeme_ucreti'], 2, '.', '') ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- ÜCRET BİLGİLERİ -->
        <div class="card-custom mb-4">
            <div class="card-header-custom">
                <h5><i class="fas fa-money-bill-wave"></i> Ücret Bilgileri</h5>
            </div>
            <div class="p-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Teknik Servis Bedeli</label>
                        <input type="number" name="iscilik_ucreti" class="form-control" step="0.01" value="<?= number_format((float)$servis['iscilik_ucreti'], 2, '.', '') ?>" onchange="hesaplaToplam()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ürün Toplamı</label>
                        <input type="text" class="form-control" id="malzeme-ucreti" value="<?= number_format((float)$servis['malzeme_ucreti'], 2, '.', '') ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Toplam Ücret</label>
                        <input type="text" class="form-control" id="toplam-ucret" value="<?= number_format((float)$servis['toplam_ucret'], 2, '.', '') ?>" readonly style="font-weight:700; color:#4ad46a;">
                    </div>
                </div>
            </div>
        </div>

        <!-- BUTONLAR -->
        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-success-custom"><i class="fas fa-save"></i> GÜNCELLE</button>
            <a href="<?= BASE_URL ?>/teknik_servis_listesi.php" class="btn btn-outline-secondary">İPTAL</a>
            <a href="<?= BASE_URL ?>/teknik_servis_cikti.php?id=<?= (int)$servis['id'] ?>" class="btn btn-outline-info" target="_blank"><i class="fas fa-print"></i> ÇIKTI</a>
            <a href="<?= BASE_URL ?>/teknik_servis_sil.php?id=<?= (int)$servis['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" class="btn btn-outline-danger" onclick="return confirm('<?= e($servis['servis_no']) ?> silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i> SİL</a>
        </div>
    </form>
</div>

<!-- YENİ CARİ EKLE MODAL -->
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
                        <label class="form-label">ÜNVAN <span class="text-danger">*</span></label>
                        <input type="text" id="modal_cari_unvan" class="form-control" placeholder="Firma Ünvanı" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">CARİ TÜRÜ</label>
                        <select id="modal_cari_turu" class="form-select">
                            <option value="MÜŞTERİ">MÜŞTERİ</option>
                            <option value="TEDARİKÇİ">TEDARİKÇİ</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">VERGİ NO</label>
                        <input type="text" id="modal_cari_vergi_no" class="form-control" placeholder="1234567890">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">VERGİ DAİRESİ</label>
                        <input type="text" id="modal_cari_vd" class="form-control" placeholder="İSTANBUL">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">TELEFON</label>
                        <input type="text" id="modal_cari_tel" class="form-control" placeholder="0212 555 55 55">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">EMAIL</label>
                        <input type="email" id="modal_cari_email" class="form-control" placeholder="info@firma.com">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">YETKİLİ KİŞİ</label>
                        <input type="text" id="modal_cari_yetkili" class="form-control" placeholder="Ahmet Yılmaz">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">ADRES</label>
                        <textarea id="modal_cari_adres" class="form-control" rows="2" placeholder="Adres bilgisi..."></textarea>
                    </div>
                </div>
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

<!-- YENİ ÜRÜN EKLE MODAL -->
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
                            <input type="text" id="modal_barkod" class="form-control" placeholder="OTOMATİK">
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
                            <select id="modal_alis_doviz" class="form-select" style="max-width:80px;">
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
                            <select id="modal_satis_doviz" class="form-select" style="max-width:80px;">
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
                        <textarea id="modal_aciklama" class="form-control" rows="2" placeholder="ÜRÜN AÇIKLAMASI..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İPTAL</button>
                <button type="button" class="btn btn-success" onclick="modalUrunKaydet()">
                    <i class="fas fa-save"></i> KAYDET VE EKLE
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

function malzemeEkle() {
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
        '<td class="text-end"><input type="text" class="form-control form-control-sm toplam-input" value="' + selected.dataset.fiyat + '" readonly></td>' +
        '<td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="malzemeSatirSil(this)"><i class="fas fa-times"></i></button></td>';
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