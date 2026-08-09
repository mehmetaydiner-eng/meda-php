<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/numara_manager.php';
require_login();

// ===== TÜM EVRAKLARI ÇEK =====
$faturalar = $pdo->query(
    'SELECT f.*, c.unvan AS cari_unvan FROM faturalar f LEFT JOIN cariler c ON c.id = f.cari_id ORDER BY f.created_at DESC'
)->fetchAll();
$makbuzlar = $pdo->query(
    'SELECT m.*, c.unvan AS cari_unvan FROM makbuzlar m LEFT JOIN cariler c ON c.id = m.cari_id ORDER BY m.created_at DESC'
)->fetchAll();
$teklifler = $pdo->query(
    'SELECT t.*, c.unvan AS cari_unvan FROM teklifler t LEFT JOIN cariler c ON c.id = t.cari_id ORDER BY t.created_at DESC'
)->fetchAll();
$servisler = $pdo->query(
    'SELECT s.*, c.unvan AS cari_unvan FROM teknik_servis s LEFT JOIN cariler c ON c.id = s.cari_id ORDER BY s.created_at DESC'
)->fetchAll();

// ===== SİPARİŞLERİ DE EKLE =====
$siparisler = $pdo->query(
    'SELECT s.*, c.unvan AS cari_unvan,
            (SELECT SUM(toplam_tutar) FROM siparis_detaylari WHERE siparis_id = s.id) AS genel_toplam
     FROM siparisler s
     LEFT JOIN cariler c ON c.id = s.cari_id
     ORDER BY s.created_at DESC'
)->fetchAll();

// ===== İSTATİSTİKLER =====
$toplam_fatura = count($faturalar);
$toplam_makbuz = count($makbuzlar);
$toplam_teklif = count($teklifler);
$toplam_servis = count($servisler);
$toplam_siparis = count($siparisler);
$toplam_evrak  = $toplam_fatura + $toplam_makbuz + $toplam_teklif + $toplam_servis + $toplam_siparis;

$toplam_earsiv = 0;
foreach ($faturalar as $f) {
    if ($f['fatura_tipi'] === 'E-ARŞİV') $toplam_earsiv++;
}

// ===== NUMARA BİLGİLERİ =====
$numara_bilgileri = [
    'fatura' => NumaraManager::getInfo($pdo, 'FAT'),
    'earsiv' => NumaraManager::getInfo($pdo, 'EAR'),
    'stm'    => NumaraManager::getInfo($pdo, 'STM'),
    'alm'    => NumaraManager::getInfo($pdo, 'ALM'),
    'thm'    => NumaraManager::getInfo($pdo, 'THM'),
    'odm'    => NumaraManager::getInfo($pdo, 'ODM'),
    'teklif' => NumaraManager::getInfo($pdo, 'VT'),
    'servis' => NumaraManager::getInfo($pdo, 'SRV'),
    'siparis'=> NumaraManager::getInfo($pdo, 'SP'),
];

$page_title   = 'EVRAKLAR';
$breadcrumb   = 'Tüm Evraklar';
$current_page = 'evraklar';
// extra_css kaldırıldı, tema değişkenleri kullanılacak
require_once __DIR__ . '/../includes/header.php';

$sayac = 0;
?>

<style>
    /* ===== Tema Değişkenleri ile Uyumlu Evrak Stilleri ===== */
    .evrak-container .evrak-ozet {
        background: var(--bg-secondary, #1e1e1e);
        border: 1px solid var(--border-color, #2a2a2a);
        border-radius: 8px;
        padding: 12px 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .evrak-container .evrak-ozet:hover {
        border-color: var(--text-muted, #6a6a6a);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
    .evrak-container .evrak-ozet .ozet-sayi {
        font-size: 24px;
        font-weight: 700;
        line-height: 1.2;
    }
    .evrak-container .evrak-ozet .ozet-label {
        font-size: 11px;
        color: var(--text-muted, #6a6a6a);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 4px;
    }
    /* Renkler tema değişkenleriyle */
    .evrak-container .evrak-ozet .ozet-sayi.fatura { color: #4ac8d4; }
    .evrak-container .evrak-ozet .ozet-sayi.earsiv { color: #4ad46a; }
    .evrak-container .evrak-ozet .ozet-sayi.makbuz { color: #8ad4a0; }
    .evrak-container .evrak-ozet .ozet-sayi.teklif { color: #d4c84a; }
    .evrak-container .evrak-ozet .ozet-sayi.servis { color: #d4844a; }
    .evrak-container .evrak-ozet .ozet-sayi.siparis { color: #c84ad4; }

    /* Numara paneli */
    .evrak-container .numara-panel {
        background: var(--bg-secondary, #1e1e1e);
        border: 1px solid var(--border-color, #2a2a2a);
        border-radius: 8px;
        padding: 10px 14px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px 16px;
    }
    .evrak-container .numara-panel .panel-title {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted, #6a6a6a);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-right: 6px;
    }
    .evrak-container .numara-item {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        background: var(--bg-input, #121212);
        padding: 2px 8px 2px 6px;
        border-radius: 12px;
        border: 1px solid var(--border-color, #2a2a2a);
    }
    .evrak-container .numara-item .label {
        color: var(--text-muted, #6a6a6a);
        font-size: 9px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .evrak-container .numara-item .value {
        font-weight: 700;
        font-size: 12px;
        cursor: pointer;
        color: var(--text-primary, #e0e0e0);
        padding: 0 4px;
        border-radius: 4px;
        transition: background 0.15s;
    }
    .evrak-container .numara-item .value:hover {
        background: var(--bg-hover, #2a2a2a);
    }
    .evrak-container .numara-item .value.fatura { color: #4ac8d4; }
    .evrak-container .numara-item .value.earsiv { color: #4ad46a; }
    .evrak-container .numara-item .value.stm { color: #8ad4a0; }
    .evrak-container .numara-item .value.alm { color: #d4c84a; }
    .evrak-container .numara-item .value.thm { color: #d4844a; }
    .evrak-container .numara-item .value.odm { color: #c84ad4; }
    .evrak-container .numara-item .value.teklif { color: #d4c84a; }
    .evrak-container .numara-item .value.servis { color: #d4844a; }
    .evrak-container .numara-item .value.siparis { color: #c84ad4; }
    .evrak-container .numara-item .copy-btn {
        background: transparent;
        border: none;
        color: var(--text-muted, #6a6a6a);
        padding: 0 2px;
        font-size: 10px;
        cursor: pointer;
        transition: color 0.15s;
    }
    .evrak-container .numara-item .copy-btn:hover {
        color: var(--text-primary, #e0e0e0);
    }

    /* Badge'ler */
    .evrak-container .badge-evrak {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        background: var(--bg-input, #2a2a2a);
        color: var(--text-primary, #e0e0e0);
        border: 1px solid var(--border-color, #3a3a3a);
    }
    .badge-evrak.fatura { background: #1a3a4a; color: #4ac8d4; border-color: #2a5a6a; }
    .badge-evrak.makbuz { background: #1a4a2a; color: #8ad4a0; border-color: #2a6a3a; }
    .badge-evrak.teklif { background: #4a4a1a; color: #d4c84a; border-color: #6a6a2a; }
    .badge-evrak.servis { background: #4a2a1a; color: #d4844a; border-color: #6a3a2a; }
    .badge-evrak.siparis { background: #3a1a4a; color: #c84ad4; border-color: #5a2a6a; }
    .badge-evrak.tur-satis { background: #1a4a2a; color: #4ad46a; border-color: #2a6a3a; }
    .badge-evrak.tur-alis { background: #4a2a1a; color: #d4844a; border-color: #6a3a2a; }
    .badge-evrak.tur-tahsilat { background: #1a3a4a; color: #4ac8d4; border-color: #2a5a6a; }
    .badge-evrak.tur-odeme { background: #4a1a1a; color: #d44a4a; border-color: #6a2a2a; }
    .badge-evrak.tur-verilen { background: #2a2a4a; color: #8a8ad4; border-color: #3a3a6a; }
    .badge-evrak.tur-alinan { background: #4a2a2a; color: #d48a8a; border-color: #6a3a3a; }
    .badge-evrak.tur-servis { background: #4a2a1a; color: #d4844a; border-color: #6a3a2a; }
    .badge-evrak.tur-siparis { background: #3a1a4a; color: #c84ad4; border-color: #5a2a6a; }
    .badge-evrak.durum-beklemede { background: #4a4a1a; color: #d4c84a; border-color: #6a6a2a; }
    .badge-evrak.durum-taslak { background: #3a3a3a; color: #8a8a8a; border-color: #5a5a5a; }
    .badge-evrak.durum-iptal { background: #4a1a1a; color: #d44a4a; border-color: #6a2a2a; }
    .badge-evrak.durum-onaylandi { background: #1a4a2a; color: #4ad46a; border-color: #2a6a3a; }
    .badge-evrak.durum-olusturuldu { background: #1a3a4a; color: #4ac8d4; border-color: #2a5a6a; }
    .badge-evrak.durum-islemde { background: #4a2a1a; color: #d4844a; border-color: #6a3a2a; }
    .badge-evrak.durum-faturalandi { background: #1a4a2a; color: #4ad46a; border-color: #2a6a3a; }

    /* Butonlar */
    .evrak-container .btn-evrak-duzenle,
    .evrak-container .btn-evrak-cikti,
    .evrak-container .btn-evrak-sil {
        background: transparent;
        border: none;
        color: var(--text-muted, #6a6a6a);
        padding: 2px 6px;
        font-size: 13px;
        transition: color 0.15s;
        text-decoration: none;
        display: inline-block;
    }
    .evrak-container .btn-evrak-duzenle:hover { color: #4ac8d4; }
    .evrak-container .btn-evrak-cikti:hover { color: #4ad46a; }
    .evrak-container .btn-evrak-sil:hover { color: #d44a4a; }

    /* Filtreler */
    .evrak-container .evrak-filtre .form-control,
    .evrak-container .evrak-filtre .form-select {
        background: var(--bg-input, #121212);
        border: 1px solid var(--border-color, #2a2a2a);
        color: var(--text-primary, #e0e0e0);
        font-size: 12px;
    }
    .evrak-container .evrak-filtre .form-control:focus,
    .evrak-container .evrak-filtre .form-select:focus {
        border-color: #4ad46a;
        box-shadow: 0 0 0 2px rgba(74, 212, 106, 0.2);
    }
    /* Tablo */
    .evrak-container .evrak-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .evrak-container .evrak-table th {
        background: var(--bg-secondary, #1e1e1e);
        color: var(--text-muted, #6a6a6a);
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 8px 10px;
        border-bottom: 2px solid var(--border-color, #2a2a2a);
        text-align: left;
    }
    .evrak-container .evrak-table td {
        padding: 6px 10px;
        border-bottom: 1px solid var(--border-color, #2a2a2a);
        color: var(--text-primary, #e0e0e0);
        vertical-align: middle;
    }
    .evrak-container .evrak-table tr:hover td {
        background: var(--bg-hover, #2a2a2a);
    }
    .evrak-container .evrak-table .text-tarih {
        font-size: 12px;
        color: var(--text-muted, #6a6a6a);
    }
    .evrak-container .evrak-table .text-end {
        text-align: right;
    }
    .evrak-container .evrak-table .text-center {
        text-align: center;
    }
    .evrak-container .evrak-table a {
        color: var(--text-primary, #e0e0e0);
        text-decoration: none;
    }
    .evrak-container .evrak-table a:hover {
        color: #4ad46a;
    }
</style>

<div class="evrak-container">

    <!-- ===== ÖZET KARTLARI (Tıklanabilir) ===== -->
    <div class="row g-2 mb-2">
        <div class="col-md-2 col-4">
            <div class="evrak-ozet" onclick="filtreleTip('FATURA')">
                <div class="ozet-sayi fatura"><?= $toplam_fatura ?></div>
                <div class="ozet-label">Faturalar</div>
            </div>
        </div>
        <div class="col-md-2 col-4">
            <div class="evrak-ozet" onclick="filtreleTip('EARSIV')">
                <div class="ozet-sayi earsiv"><?= $toplam_earsiv ?></div>
                <div class="ozet-label">E-Arşiv</div>
            </div>
        </div>
        <div class="col-md-2 col-4">
            <div class="evrak-ozet" onclick="filtreleTip('MAKBUZ')">
                <div class="ozet-sayi makbuz"><?= $toplam_makbuz ?></div>
                <div class="ozet-label">Makbuzlar</div>
            </div>
        </div>
        <div class="col-md-2 col-4">
            <div class="evrak-ozet" onclick="filtreleTip('TEKLIF')">
                <div class="ozet-sayi teklif"><?= $toplam_teklif ?></div>
                <div class="ozet-label">Teklifler</div>
            </div>
        </div>
        <div class="col-md-2 col-4">
            <div class="evrak-ozet" onclick="filtreleTip('SERVIS')">
                <div class="ozet-sayi servis"><?= $toplam_servis ?></div>
                <div class="ozet-label">Servis</div>
            </div>
        </div>
        <div class="col-md-2 col-4">
            <div class="evrak-ozet" onclick="filtreleTip('SIPARIS')">
                <div class="ozet-sayi siparis"><?= $toplam_siparis ?></div>
                <div class="ozet-label">Siparişler</div>
            </div>
        </div>
    </div>

    <!-- ===== NUMARA BİLGİ PANELİ ===== -->
    <div class="row g-2 mb-2">
        <div class="col-md-12">
            <div class="numara-panel">
                <span class="panel-title"><i class="fas fa-hashtag"></i> SIRADAKİ NUMARALAR:</span>

                <?php
                    $numaraGosterimleri = [
                        ['id' => 'fatura-numara', 'prefix' => 'FAT', 'key' => 'fatura', 'label' => 'E-FATURA', 'cls' => 'fatura', 'varsayilan' => 'MED2026000000001'],
                        ['id' => 'earsiv-numara', 'prefix' => 'EAR', 'key' => 'earsiv', 'label' => 'E-ARŞİV', 'cls' => 'earsiv', 'varsayilan' => 'GIB2026000000001'],
                        ['id' => 'stm-numara', 'prefix' => 'STM', 'key' => 'stm', 'label' => 'SATIŞ MAK.', 'cls' => 'stm', 'varsayilan' => 'STM-2026-0001'],
                        ['id' => 'alm-numara', 'prefix' => 'ALM', 'key' => 'alm', 'label' => 'ALIŞ MAK.', 'cls' => 'alm', 'varsayilan' => 'ALM-2026-0001'],
                        ['id' => 'thm-numara', 'prefix' => 'THM', 'key' => 'thm', 'label' => 'TAHSİLAT', 'cls' => 'thm', 'varsayilan' => 'THM-2026-0001'],
                        ['id' => 'odm-numara', 'prefix' => 'ODM', 'key' => 'odm', 'label' => 'ÖDEME', 'cls' => 'odm', 'varsayilan' => 'ODM-2026-0001'],
                        ['id' => 'teklif-numara', 'prefix' => 'VT', 'key' => 'teklif', 'label' => 'TEKLİF', 'cls' => 'teklif', 'varsayilan' => 'VT-2026-0001'],
                        ['id' => 'servis-numara', 'prefix' => 'SRV', 'key' => 'servis', 'label' => 'SERVİS', 'cls' => 'servis', 'varsayilan' => 'SRV-2026-0001'],
                        ['id' => 'siparis-numara', 'prefix' => 'SP', 'key' => 'siparis', 'label' => 'SİPARİŞ', 'cls' => 'siparis', 'varsayilan' => 'SP-2026-0001'],
                    ];
                ?>
                <?php foreach ($numaraGosterimleri as $ng): ?>
                <span class="numara-item">
                    <span class="label"><?= e($ng['label']) ?></span>
                    <span class="value <?= e($ng['cls']) ?>" id="<?= e($ng['id']) ?>" onclick="numaraDuzenle('<?= e($ng['id']) ?>', '<?= e($ng['prefix']) ?>')">
                        <?= e($numara_bilgileri[$ng['key']]['siradaki_format'] ?? $ng['varsayilan']) ?>
                    </span>
                    <button class="copy-btn" onclick="kopyala(document.getElementById('<?= e($ng['id']) ?>').textContent)" title="Kopyala">
                        <i class="fas fa-copy"></i>
                    </button>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ===== EVRAK LİSTESİ ===== -->
    <div class="card-custom">
        <div class="card-header-custom">
            <h5><i class="fas fa-folder-open"></i> TÜM EVRAKLAR</h5>
            <div>
                <span class="text-muted" style="font-size: 11px;">Toplam: <strong><?= $toplam_evrak ?></strong> evrak</span>
            </div>
        </div>

        <div style="padding: 10px 15px;">
            <div class="evrak-filtre">
                <div class="row g-1">
                    <div class="col-md-3">
                        <input type="text" id="evrakArama" class="form-control" placeholder="Evrak No veya Cari ara...">
                    </div>
                    <div class="col-md-2">
                        <select id="evrakTipFiltre" class="form-select">
                            <option value="TUMU">TÜM TİPLER</option>
                            <option value="FATURA">Faturalar</option>
                            <option value="EARSIV">E-Arşiv</option>
                            <option value="MAKBUZ">Makbuzlar</option>
                            <option value="TEKLIF">Teklifler</option>
                            <option value="SERVIS">Servis</option>
                            <option value="SIPARIS">Siparişler</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="evrakTurFiltre" class="form-select">
                            <option value="TUMU">TÜM TÜRLER</option>
                            <option value="SATIŞ">Satış</option>
                            <option value="ALIŞ">Alış</option>
                            <option value="İADE">İade</option>
                            <option value="TAHSİLAT">Tahsilat</option>
                            <option value="ÖDEME">Ödeme</option>
                            <option value="VERILEN">Verilen</option>
                            <option value="ALINAN">Alınan</option>
                            <option value="SIPARIS">Sipariş</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="evrakDurumFiltre" class="form-select">
                            <option value="TUMU">TÜM DURUMLAR</option>
                            <option value="AKTİF">Aktif</option>
                            <option value="BEKLEMEDE">Beklemede</option>
                            <option value="TASLAK">Taslak</option>
                            <option value="İPTAL">İptal</option>
                            <option value="ONAYLANDI">Onaylandı</option>
                            <option value="OLUŞTURULDU">Oluşturuldu</option>
                            <option value="İŞLEMDE">İşlemde</option>
                            <option value="FATURALANDI">Faturalandı</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn-primary-custom w-100" onclick="evrakFiltrele()">
                            <i class="fas fa-search"></i> FİLTRELE
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="evrak-table" id="tumEvrakTable">
                    <thead>
                        <tr>
                            <th style="width:25px;">#</th>
                            <th style="min-width:130px;">Evrak No</th>
                            <th style="width:85px;">Tarih</th>
                            <th>Cari</th>
                            <th style="width:75px;">Tip</th>
                            <th style="width:75px;">Tür</th>
                            <th class="text-end" style="width:85px;">Tutar</th>
                            <th style="width:85px;">Durum</th>
                            <th style="width:85px;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- ===== FATURALAR ===== -->
                        <?php foreach ($faturalar as $fatura): $sayac++; ?>
                        <tr data-tip="FATURA" data-tur="<?= e($fatura['fatura_turu']) ?>" data-durum="<?= e($fatura['durum']) ?>">
                            <td><?= $sayac ?></td>
                            <td><a href="<?= BASE_URL ?>/fatura_olustur.php?id=<?= (int)$fatura['id'] ?>" class="text-decoration-none"><strong><?= e($fatura['fatura_no']) ?></strong></a></td>
                            <td class="text-tarih"><?= $fatura['fatura_tarihi'] ? format_tarih($fatura['fatura_tarihi']) : '-' ?></td>
                            <td><?= $fatura['cari_unvan'] ? '<a href="' . BASE_URL . '/cari_detay.php?id=' . (int)$fatura['cari_id'] . '" class="text-decoration-none">' . e($fatura['cari_unvan']) . '</a>' : '-' ?></td>
                            <td><span class="badge-evrak fatura">FATURA</span></td>
                            <td><span class="badge-evrak tur-<?= mb_strtolower($fatura['fatura_turu'] ?: '', 'UTF-8') ?>"><?= e($fatura['fatura_turu'] ?: 'BELİRSİZ') ?></span></td>
                            <td class="text-end"><?= number_format((float)$fatura['genel_toplam'], 2, '.', '') ?> ₺</td>
                            <td><span class="badge-evrak durum-<?= mb_strtolower($fatura['durum'] ?: '', 'UTF-8') ?>"><?= e($fatura['durum'] ?: 'OLUŞTURULDU') ?></span></td>
                            <td>
                                <a href="<?= BASE_URL ?>/fatura_duzenle.php?id=<?= (int)$fatura['id'] ?>" class="btn-evrak-duzenle" title="Düzenle"><i class="fas fa-edit"></i></a>
                                <a href="<?= BASE_URL ?>/fatura_cikti.php?id=<?= (int)$fatura['id'] ?>" class="btn-evrak-cikti" title="Çıktı"><i class="fas fa-print"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <!-- ===== MAKBUZLAR ===== -->
                        <?php foreach ($makbuzlar as $makbuz): $sayac++; ?>
                        <tr data-tip="MAKBUZ" data-tur="<?= e($makbuz['makbuz_turu']) ?>" data-durum="<?= e($makbuz['durum']) ?>">
                            <td><?= $sayac ?></td>
                            <td><a href="<?= BASE_URL ?>/makbuz_detay.php?id=<?= (int)$makbuz['id'] ?>" class="text-decoration-none"><strong><?= e($makbuz['makbuz_no']) ?></strong></a></td>
                            <td class="text-tarih"><?= $makbuz['makbuz_tarihi'] ? format_tarih($makbuz['makbuz_tarihi']) : '-' ?></td>
                            <td><?= $makbuz['cari_unvan'] ? '<a href="' . BASE_URL . '/cari_detay.php?id=' . (int)$makbuz['cari_id'] . '" class="text-decoration-none">' . e($makbuz['cari_unvan']) . '</a>' : '-' ?></td>
                            <td><span class="badge-evrak makbuz">MAKBUZ</span></td>
                            <td><span class="badge-evrak tur-<?= mb_strtolower($makbuz['makbuz_turu'] ?: '', 'UTF-8') ?>"><?= e($makbuz['makbuz_turu'] ?: 'BELİRSİZ') ?></span></td>
                            <td class="text-end"><?= number_format((float)$makbuz['genel_toplam'], 2, '.', '') ?> ₺</td>
                            <td><span class="badge-evrak durum-<?= mb_strtolower($makbuz['durum'] ?: '', 'UTF-8') ?>"><?= e($makbuz['durum'] ?: 'OLUŞTURULDU') ?></span></td>
                            <td>
                                <a href="<?= BASE_URL ?>/makbuz_detay.php?id=<?= (int)$makbuz['id'] ?>" class="btn-evrak-duzenle" title="Detay"><i class="fas fa-eye"></i></a>
                                <a href="<?= BASE_URL ?>/makbuz_cikti.php?id=<?= (int)$makbuz['id'] ?>" class="btn-evrak-cikti" title="Çıktı"><i class="fas fa-print"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <!-- ===== TEKLİFLER ===== -->
                        <?php foreach ($teklifler as $teklif): $sayac++; ?>
                        <tr data-tip="TEKLIF" data-tur="<?= e($teklif['teklif_turu']) ?>" data-durum="<?= e($teklif['durum']) ?>">
                            <td><?= $sayac ?></td>
                            <td><a href="<?= BASE_URL ?>/teklif_olustur.php?id=<?= (int)$teklif['id'] ?>" class="text-decoration-none"><strong><?= e($teklif['teklif_no']) ?></strong></a></td>
                            <td class="text-tarih"><?= $teklif['teklif_tarihi'] ? format_tarih($teklif['teklif_tarihi']) : '-' ?></td>
                            <td><?= $teklif['cari_unvan'] ? '<a href="' . BASE_URL . '/cari_detay.php?id=' . (int)$teklif['cari_id'] . '" class="text-decoration-none">' . e($teklif['cari_unvan']) . '</a>' : '-' ?></td>
                            <td><span class="badge-evrak teklif">TEKLİF</span></td>
                            <td><span class="badge-evrak tur-<?= mb_strtolower($teklif['teklif_turu'] ?: '', 'UTF-8') ?>"><?= e($teklif['teklif_turu'] ?: 'BELİRSİZ') ?></span></td>
                            <td class="text-end"><?= number_format((float)$teklif['genel_toplam'], 2, '.', '') ?> ₺</td>
                            <td><span class="badge-evrak durum-<?= mb_strtolower($teklif['durum'] ?: '', 'UTF-8') ?>"><?= e($teklif['durum'] ?: 'TASLAK') ?></span></td>
                            <td>
                                <a href="<?= BASE_URL ?>/teklif_olustur.php?id=<?= (int)$teklif['id'] ?>" class="btn-evrak-duzenle" title="Detay"><i class="fas fa-eye"></i></a>
                                <a href="<?= BASE_URL ?>/teklif_cikti.php?id=<?= (int)$teklif['id'] ?>" class="btn-evrak-cikti" title="Çıktı"><i class="fas fa-print"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <!-- ===== SERVİSLER ===== -->
                        <?php foreach ($servisler as $servis): $sayac++; ?>
                        <tr data-tip="SERVIS" data-tur="SERVIS" data-durum="<?= e($servis['durum']) ?>">
                            <td><?= $sayac ?></td>
                            <td><a href="<?= BASE_URL ?>/teknik_servis_duzenle.php?id=<?= (int)$servis['id'] ?>" class="text-decoration-none"><strong><?= e($servis['servis_no']) ?></strong></a></td>
                            <td class="text-tarih"><?= $servis['created_at'] ? format_tarih($servis['created_at']) : '-' ?></td>
                            <td><?= $servis['cari_unvan'] ? '<a href="' . BASE_URL . '/cari_detay.php?id=' . (int)$servis['cari_id'] . '" class="text-decoration-none">' . e($servis['cari_unvan']) . '</a>' : '-' ?></td>
                            <td><span class="badge-evrak servis">SERVİS</span></td>
                            <td><span class="badge-evrak tur-servis">TEKNİK SERVİS</span></td>
                            <td class="text-end"><?= number_format((float)$servis['toplam_ucret'], 2, '.', '') ?> ₺</td>
                            <td><span class="badge-evrak durum-<?= mb_strtolower($servis['durum'] ?: '', 'UTF-8') ?>"><?= e($servis['durum'] ?: 'BEKLEMEDE') ?></span></td>
                            <td>
                                <a href="<?= BASE_URL ?>/teknik_servis_duzenle.php?id=<?= (int)$servis['id'] ?>" class="btn-evrak-duzenle" title="Düzenle"><i class="fas fa-edit"></i></a>
                                <a href="<?= BASE_URL ?>/teknik_servis_cikti.php?id=<?= (int)$servis['id'] ?>" class="btn-evrak-cikti" title="Çıktı"><i class="fas fa-print"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <!-- ===== SİPARİŞLER ===== -->
                        <?php foreach ($siparisler as $siparis): $sayac++; ?>
                        <tr data-tip="SIPARIS" data-tur="SIPARIS" data-durum="<?= e($siparis['durum']) ?>">
                            <td><?= $sayac ?></td>
                            <td><a href="<?= BASE_URL ?>/siparis_olustur.php?id=<?= (int)$siparis['id'] ?>" class="text-decoration-none"><strong><?= e($siparis['siparis_no']) ?></strong></a></td>
                            <td class="text-tarih"><?= $siparis['siparis_tarihi'] ? format_tarih($siparis['siparis_tarihi']) : '-' ?></td>
                            <td><?= $siparis['cari_unvan'] ? '<a href="' . BASE_URL . '/cari_detay.php?id=' . (int)$siparis['cari_id'] . '" class="text-decoration-none">' . e($siparis['cari_unvan']) . '</a>' : '-' ?></td>
                            <td><span class="badge-evrak siparis">SİPARİŞ</span></td>
                            <td><span class="badge-evrak tur-siparis">SİPARİŞ</span></td>
                            <td class="text-end"><?= number_format((float)$siparis['genel_toplam'], 2, '.', '') ?> ₺</td>
                            <td><span class="badge-evrak durum-<?= mb_strtolower($siparis['durum'] ?: '', 'UTF-8') ?>"><?= e($siparis['durum'] ?: 'BEKLEMEDE') ?></span></td>
                            <td>
                                <a href="<?= BASE_URL ?>/siparis_olustur.php?id=<?= (int)$siparis['id'] ?>" class="btn-evrak-duzenle" title="Düzenle"><i class="fas fa-edit"></i></a>
                                <?php if ($siparis['durum'] === 'BEKLEMEDE'): ?>
                                <a href="<?= BASE_URL ?>/siparis_sil.php?id=<?= (int)$siparis['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" class="btn-evrak-sil" title="Sil" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if ($sayac === 0): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-folder-open fa-3x d-block mb-3" style="color: #3a3a3a;"></i>
                                Henüz hiç evrak bulunmuyor.<br>
                                <small class="text-muted">Fatura, makbuz, teklif, servis veya sipariş oluşturun.</small>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ===== NUMARA DÜZENLEME MODAL ===== -->
<div class="modal fade" id="numaraDuzenleModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-edit"></i> NUMARA DÜZENLE</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="duzenlenecekAlan" value="">
                <input type="hidden" id="duzenlenecekPrefix" value="">

                <div class="mb-2">
                    <label class="form-label" style="color: var(--text-muted, #8a8a8a); font-size: 11px;">Yeni Numara</label>
                    <input type="text" id="yeniNumaraInput" class="form-control"
                           style="background: var(--bg-input, #121212); border: 1px solid var(--border-color, #2a2a2a); color: var(--text-primary, #e0e0e0); font-size: 14px; font-weight: 700;">
                </div>
                <div class="mb-2">
                    <label class="form-label" style="color: var(--text-muted, #8a8a8a); font-size: 11px;">Sıradaki Numara</label>
                    <input type="number" id="siraNumaraInput" class="form-control"
                           style="background: var(--bg-input, #121212); border: 1px solid var(--border-color, #2a2a2a); color: var(--text-primary, #e0e0e0); font-size: 14px; font-weight: 700;"
                           placeholder="0001" min="1" step="1">
                    <small style="color: var(--text-muted, #6a6a6a); font-size: 10px;">
                        <i class="fas fa-info-circle"></i>
                        Sadece sayı girin (örn. 53).
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İPTAL</button>
                <button type="button" class="btn btn-success btn-sm" onclick="numaraKaydet()">
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

// ========== FİLTRELEME ==========
function evrakFiltrele() {
    var arama = document.getElementById('evrakArama').value.toLowerCase();
    var tip = document.getElementById('evrakTipFiltre').value;
    var tur = document.getElementById('evrakTurFiltre').value;
    var durum = document.getElementById('evrakDurumFiltre').value;

    var rows = document.querySelectorAll('#tumEvrakTable tbody tr');
    rows.forEach(function(row) {
        if (row.querySelector('td[colspan]')) return;

        var goster = true;
        var text = row.textContent.toLowerCase();
        var rowTip = row.dataset.tip || '';
        var rowTur = row.dataset.tur || '';
        var rowDurum = row.dataset.durum || '';

        if (arama && !text.includes(arama)) goster = false;
        if (tip !== 'TUMU' && rowTip !== tip) goster = false;
        if (tur !== 'TUMU' && rowTur !== tur) goster = false;
        if (durum !== 'TUMU' && rowDurum !== durum) goster = false;

        row.style.display = goster ? '' : 'none';
    });
}

// Özet kartlarına tıklayınca filtrele
function filtreleTip(tip) {
    document.getElementById('evrakTipFiltre').value = tip;
    evrakFiltrele();
}

// ========== NUMARA DÜZENLEME ==========
function numaraDuzenle(elementId, prefix) {
    var element = document.getElementById(elementId);
    if (!element) return;

    document.getElementById('duzenlenecekAlan').value = elementId;
    document.getElementById('duzenlenecekPrefix').value = prefix;
    document.getElementById('yeniNumaraInput').value = element.textContent.trim();
    document.getElementById('siraNumaraInput').value = '';

    var modal = new bootstrap.Modal(document.getElementById('numaraDuzenleModal'));
    modal.show();
}

function numaraKaydet() {
    var elementId = document.getElementById('duzenlenecekAlan').value;
    var prefix = document.getElementById('duzenlenecekPrefix').value;
    var yeniNumara = document.getElementById('yeniNumaraInput').value.trim();
    var siraNumara = document.getElementById('siraNumaraInput').value.trim();

    // Sıradaki numara kontrolü – sadece sayı olmalı
    if (siraNumara !== '') {
        var num = parseInt(siraNumara, 10);
        if (isNaN(num) || num < 1) {
            showToast('❌ Lütfen geçerli bir sayı girin (örnek: 53)', 'error');
            return;
        }
        // Sayı ise işleme devam et
        fetch(API_BASE + '/api/numara_ayarla.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prefix: prefix, yeni_numara: num, csrf_token: CSRF_TOKEN })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('✅ Sıradaki numara ' + num + ' olarak ayarlandı!', 'success');
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                showToast('❌ Hata: ' + data.message, 'error');
            }
        })
        .catch(function(error) { showToast('❌ Hata: ' + error, 'error'); });
    }

    // Yeni numara güncelleme (tam numara)
    if (yeniNumara !== '') {
        fetch(API_BASE + '/api/numara_guncelle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prefix: prefix, yeni_numara: yeniNumara, csrf_token: CSRF_TOKEN })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById(elementId).textContent = yeniNumara;
                showToast('✅ Numara güncellendi: ' + yeniNumara, 'success');
            } else {
                showToast('❌ Hata: ' + data.message, 'error');
            }
        })
        .catch(function(error) { showToast('❌ Hata: ' + error, 'error'); });
    }

    var modal = bootstrap.Modal.getInstance(document.getElementById('numaraDuzenleModal'));
    if (modal) modal.hide();
}

function kopyala(text) {
    navigator.clipboard.writeText(text).then(function() {
        var toast = document.createElement('div');
        toast.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: #1a3a1a; color: #8ad4a0; padding: 8px 16px; border-radius: 4px; border: 1px solid #2a5a2a; z-index: 9999; font-size: 12px;';
        toast.innerHTML = '✅ Kopyalandı: ' + text;
        document.body.appendChild(toast);
        setTimeout(function() { toast.remove(); }, 2000);
    }).catch(function() {
        alert('Kopyalandı: ' + text);
    });
}

function showToast(message, type) {
    var toast = document.createElement('div');
    var bgColor = type === 'success' ? '#1a3a1a' : '#3a1a1a';
    var textColor = type === 'success' ? '#8ad4a0' : '#d44a4a';
    var borderColor = type === 'success' ? '#2a5a2a' : '#5a2a2a';

    toast.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: ' + bgColor + '; color: ' + textColor + '; padding: 12px 20px; border-radius: 6px; border: 1px solid ' + borderColor + '; z-index: 9999; font-size: 13px; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.5); max-width: 400px;';
    toast.innerHTML = message;
    document.body.appendChild(toast);

    setTimeout(function() { toast.remove(); }, 4000);
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('evrakArama').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') evrakFiltrele();
    });
    document.getElementById('evrakTipFiltre').addEventListener('change', evrakFiltrele);
    document.getElementById('evrakTurFiltre').addEventListener('change', evrakFiltrele);
    document.getElementById('evrakDurumFiltre').addEventListener('change', evrakFiltrele);
});
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>