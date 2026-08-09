<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

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

$hesaplar = $pdo->query("SELECT * FROM hesaplar WHERE is_active = 1 AND hesap_turu != 'VERESİYE' ORDER BY hesap_adi")->fetchAll();

// ===== FATURALAR =====
$stmt = $pdo->prepare('SELECT * FROM faturalar WHERE cari_id = ? ORDER BY fatura_tarihi DESC');
$stmt->execute([$id]);
$faturalar = $stmt->fetchAll();

// ===== MAKBUZLAR =====
$stmt = $pdo->prepare('SELECT * FROM makbuzlar WHERE cari_id = ? ORDER BY makbuz_tarihi DESC');
$stmt->execute([$id]);
$makbuzlar = $stmt->fetchAll();

// ===== TEKNİK SERVİSLER =====
$stmt = $pdo->prepare('SELECT * FROM teknik_servis WHERE cari_id = ? ORDER BY created_at DESC');
$stmt->execute([$id]);
$servisler = $stmt->fetchAll();

// ===== SİPARİŞLER =====
$stmt = $pdo->prepare('
    SELECT s.*,
           (SELECT SUM(toplam_tutar) FROM siparis_detaylari WHERE siparis_id = s.id) AS genel_toplam
    FROM siparisler s
    WHERE s.cari_id = ?
    ORDER BY s.created_at DESC
');
$stmt->execute([$id]);
$siparisler = $stmt->fetchAll();

// ===== BEKLEYEN SİPARİŞ TOPLAMI (durum = BEKLEMEDE) =====
$stmt = $pdo->prepare('
    SELECT COALESCE(SUM(sd.toplam_tutar), 0) AS toplam
    FROM siparisler s
    JOIN siparis_detaylari sd ON sd.siparis_id = s.id
    WHERE s.cari_id = ? AND s.durum = "BEKLEMEDE"
');
$stmt->execute([$id]);
$bekleyen_siparis_toplam = (float)$stmt->fetchColumn();

// ===== BEKLEYEN SERVİS TOPLAMI (durum = BEKLEMEDE veya İŞLEMDE) =====
$stmt = $pdo->prepare('
    SELECT COALESCE(SUM(toplam_ucret), 0) AS toplam
    FROM teknik_servis
    WHERE cari_id = ? AND durum IN ("BEKLEMEDE", "İŞLEMDE")
');
$stmt->execute([$id]);
$bekleyen_servis_toplam = (float)$stmt->fetchColumn();

// ===== TOPLAM BAKİYE =====
$toplam_bakiye = (float)$cari['bakiye'] + $bekleyen_siparis_toplam + $bekleyen_servis_toplam;

// ===== HESAP HAREKETLERİ =====
$stmt = $pdo->prepare('SELECT * FROM hesap_hareketleri WHERE cari_id = ? ORDER BY tarih DESC');
$stmt->execute([$id]);
$hesap_hareketleri = $stmt->fetchAll();

$page_title   = 'CARİ DETAY';
$breadcrumb   = 'Cari Detayı';
$current_page = 'cari_detay';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <!-- Sol Menü -->
    <div class="col-md-3" style="padding-right: 15px;">
        <div class="card-custom" style="position: sticky; top: 80px;">
            <div class="card-header-custom">
                <h5 style="font-size: 14px;"><i class="fas fa-users" style="color: #6a6a6a;"></i> CARİ MENÜ</h5>
            </div>
            <div class="cari-menu">
                <div class="menu-item">
                    <a href="<?= BASE_URL ?>/cari_ekle.php" class="menu-link">
                        <i class="fas fa-plus-circle"></i> YENİ CARİ EKLE
                    </a>
                </div>
                <div class="menu-item">
                    <a href="<?= BASE_URL ?>/cariler.php" class="menu-link">
                        <i class="fas fa-list"></i> CARİ LİSTESİ
                    </a>
                </div>
                <div class="menu-item">
                    <a href="<?= BASE_URL ?>/cari_detay.php?id=<?= (int)$cari['id'] ?>" class="menu-link active">
                        <i class="fas fa-id-card"></i> CARİ DETAY
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Sağ Taraf - Detay -->
    <div class="col-md-9">
        <div class="card-custom">
            <div class="card-header-custom">
                <h5><i class="fas fa-id-card"></i> <?= e($cari['unvan']) ?></h5>
                <div>
                    <a href="<?= BASE_URL ?>/cari_duzenle.php?id=<?= (int)$cari['id'] ?>" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit"></i> DÜZENLE
                    </a>
                    <a href="<?= BASE_URL ?>/cariler.php" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-arrow-left"></i> GERİ
                    </a>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <p><strong>Ünvan:</strong> <?= e($cari['unvan']) ?></p>
                    <p><strong>Vergi No:</strong> <?= e($cari['vergi_no'] ?: '-') ?></p>
                    <p><strong>Vergi Dairesi:</strong> <?= e($cari['vergi_dairesi'] ?: '-') ?></p>
                    <p><strong>Telefon:</strong> <?= e($cari['telefon'] ?: '-') ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Email:</strong> <?= e($cari['email'] ?: '-') ?></p>
                    <p><strong>Yetkili:</strong> <?= e($cari['yetkili'] ?: '-') ?></p>
                    <p><strong>Tür:</strong>
                        <span class="badge-status <?= $cari['cari_turu'] === 'MÜŞTERİ' ? 'bg-success' : 'bg-info' ?>">
                            <?= e($cari['cari_turu'] ?: 'BELİRSİZ') ?>
                        </span>
                    </p>
                </div>
                <?php if (!empty($cari['adres'])): ?>
                <div class="col-md-12">
                    <p><strong>Adres:</strong><br><?= nl2br(e($cari['adres'])) ?></p>
                </div>
                <?php endif; ?>
                <div class="col-md-12">
                    <p><strong>Kayıt Tarihi:</strong> <?= $cari['created_at'] ? format_tarih($cari['created_at'], 'd.m.Y H:i') : '-' ?></p>
                </div>
            </div>

            <!-- ===== BAKİYE ÖZETİ ===== -->
            <div class="row g-2 mt-3" style="border-top: 1px solid var(--border-color); padding-top: 15px;">
                <div class="col-md-6">
                    <div class="border rounded p-3" style="border-color: var(--border-color);">
                        <div class="d-flex justify-content-between">
                            <span><strong>Mevcut Bakiye (Fatura/Makbuz):</strong></span>
                            <span class="<?= (float)$cari['bakiye'] > 0 ? 'text-success' : ((float)$cari['bakiye'] < 0 ? 'text-danger' : '') ?>">
                                <?= number_format(abs((float)$cari['bakiye']), 2, '.', '') ?> ₺
                                <?php if ((float)$cari['bakiye'] > 0): ?>
                                    <span class="badge-status bg-success" style="font-size:10px;">ALACAK</span>
                                    <small style="color: #8a8a8a; font-size:9px;">(Biz müşteriye borçluyuz)</small>
                                <?php elseif ((float)$cari['bakiye'] < 0): ?>
                                    <span class="badge-status bg-danger" style="font-size:10px;">BORÇ</span>
                                    <small style="color: #8a8a8a; font-size:9px;">(Müşteri bize borçlu)</small>
                                <?php else: ?>
                                    <span class="badge-status bg-secondary" style="font-size:10px;">0</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span><strong>Bekleyen Siparişler:</strong></span>
                            <span class="text-warning"><?= number_format($bekleyen_siparis_toplam, 2, '.', '') ?> ₺</span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span><strong>Bekleyen Servisler:</strong></span>
                            <span class="text-warning"><?= number_format($bekleyen_servis_toplam, 2, '.', '') ?> ₺</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between" style="font-size: 18px; font-weight: 700;">
                            <span>TOPLAM BAKİYE:</span>
                            <span class="<?= $toplam_bakiye > 0 ? 'text-success' : ($toplam_bakiye < 0 ? 'text-danger' : '') ?>">
                                <?= number_format(abs($toplam_bakiye), 2, '.', '') ?> ₺
                                <?php if ($toplam_bakiye > 0): ?>
                                    <span class="badge-status bg-success" style="font-size:11px;">ALACAK</span>
                                    <small style="color: #8a8a8a; font-size:9px;">(Biz müşteriye borçluyuz)</small>
                                <?php elseif ($toplam_bakiye < 0): ?>
                                    <span class="badge-status bg-danger" style="font-size:11px;">BORÇ</span>
                                    <small style="color: #8a8a8a; font-size:9px;">(Müşteri bize borçlu)</small>
                                <?php else: ?>
                                    <span class="badge-status bg-secondary" style="font-size:11px;">0</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <small class="text-muted">* Toplam Bakiye = Mevcut Bakiye + Bekleyen Siparişler + Bekleyen Servisler</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3" style="border-color: var(--border-color);">
                        <strong>Hızlı İşlemler</strong>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <a href="<?= BASE_URL ?>/siparis_olustur.php?cari_id=<?= (int)$cari['id'] ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-shopping-cart"></i> Yeni Sipariş
                            </a>
                            <a href="<?= BASE_URL ?>/fatura_olustur.php?cari_id=<?= (int)$cari['id'] ?>" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-file-invoice"></i> Yeni Fatura
                            </a>
                            <a href="<?= BASE_URL ?>/makbuz_olustur.php?tur=SATIS&cari_id=<?= (int)$cari['id'] ?>" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-receipt"></i> Yeni Makbuz
                            </a>
                            <a href="<?= BASE_URL ?>/teknik_servis_ekle.php?cari_id=<?= (int)$cari['id'] ?>" class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-tools"></i> Yeni Servis
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== FATURALAR TABLOSU ===== -->
        <div class="card-custom mt-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-file-invoice"></i> FATURALAR</h5>
                <span class="text-muted"><?= count($faturalar) ?> adet</span>
            </div>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Fatura No</th>
                            <th>Tarih</th>
                            <th>Tür</th>
                            <th class="text-end">Tutar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($faturalar): ?>
                            <?php foreach ($faturalar as $fatura): ?>
                            <tr>
                                <td><a href="<?= BASE_URL ?>/fatura_olustur.php?id=<?= (int)$fatura['id'] ?>" class="text-decoration-none"><strong><?= e($fatura['fatura_no']) ?></strong></a></td>
                                <td><?= $fatura['fatura_tarihi'] ? format_tarih($fatura['fatura_tarihi']) : '-' ?></td>
                                <td>
                                    <span class="badge-status <?= $fatura['fatura_turu'] === 'SATIŞ' ? 'bg-success' : 'bg-info' ?>">
                                        <?= e($fatura['fatura_turu'] ?: 'BELİRSİZ') ?>
                                    </span>
                                </td>
                                <td class="text-end"><?= number_format((float)$fatura['genel_toplam'], 2, '.', '') ?> ₺</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Henüz fatura yok</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== MAKBUZLAR TABLOSU ===== -->
        <div class="card-custom mt-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-receipt"></i> MAKBUZLAR</h5>
                <span class="text-muted"><?= count($makbuzlar) ?> adet</span>
            </div>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Makbuz No</th>
                            <th>Tarih</th>
                            <th>Tür</th>
                            <th class="text-end">Tutar</th>
                            <th>Durum</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($makbuzlar): ?>
                            <?php foreach ($makbuzlar as $makbuz): ?>
                            <?php
                                $turClass = match($makbuz['makbuz_turu']) {
                                    'ALIS'     => 'bg-info',
                                    'SATIS'    => 'bg-success',
                                    'TAHSILAT' => 'bg-warning',
                                    'ODEME'    => 'bg-danger',
                                    default    => 'bg-secondary',
                                };
                                $durumClass = $makbuz['durum'] === 'İPTAL' ? 'bg-danger' : 'bg-success';
                            ?>
                            <tr>
                                <td><a href="<?= BASE_URL ?>/makbuz_detay.php?id=<?= (int)$makbuz['id'] ?>" class="text-decoration-none"><strong><?= e($makbuz['makbuz_no']) ?></strong></a></td>
                                <td><?= $makbuz['makbuz_tarihi'] ? format_tarih($makbuz['makbuz_tarihi'], 'd.m.Y H:i') : '-' ?></td>
                                <td><span class="badge-status <?= $turClass ?>"><?= e($makbuz['makbuz_turu']) ?></span></td>
                                <td class="text-end"><?= number_format((float)$makbuz['genel_toplam'], 2, '.', '') ?> ₺</td>
                                <td><span class="badge-status <?= $durumClass ?>"><?= e($makbuz['durum'] ?: 'OLUŞTURULDU') ?></span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>/makbuz_detay.php?id=<?= (int)$makbuz['id'] ?>" class="btn btn-outline-info" title="Detay">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/makbuz_cikti.php?id=<?= (int)$makbuz['id'] ?>" class="btn btn-outline-primary" title="Çıktı" target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">Henüz makbuz yok</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== TEKNİK SERVİSLER TABLOSU (Checkbox + Butonlar Eklendi) ===== -->
        <div class="card-custom mt-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-tools"></i> TEKNİK SERVİSLER</h5>
                <div>
                    <span class="text-muted"><?= count($servisler) ?> adet</span>
                    <button class="btn btn-success-custom btn-sm ms-2" onclick="seciliFaturaOlustur()">
                        <i class="fas fa-file-invoice"></i> Fatura Oluştur
                    </button>
                    <button class="btn btn-primary-custom btn-sm ms-1" onclick="seciliMakbuzOlustur()">
                        <i class="fas fa-receipt"></i> Makbuz Oluştur
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table-custom" id="servisTable">
                    <thead>
                        <tr>
                            <th style="width:30px;"><input type="checkbox" id="selectAllServis" onchange="toggleAllServis(this)"></th>
                            <th>Servis No</th>
                            <th>Ürün</th>
                            <th>Durum</th>
                            <th class="text-end">Ücret</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($servisler): ?>
                            <?php foreach ($servisler as $servis): ?>
                            <?php
                                $durumClass = match($servis['durum']) {
                                    'BEKLEMEDE' => 'bg-warning',
                                    'İŞLEMDE'   => 'bg-info',
                                    'TAMAMLANDI'=> 'bg-success',
                                    'FATURALANDI'=> 'bg-success',
                                    default     => 'bg-danger',
                                };
                                $disabled = in_array($servis['durum'], ['BEKLEMEDE', 'İŞLEMDE']) ? '' : 'disabled';
                            ?>
                            <tr>
                                <td><input type="checkbox" class="servis-check" value="<?= (int)$servis['id'] ?>" <?= $disabled ?>></td>
                                <td><a href="<?= BASE_URL ?>/teknik_servis_duzenle.php?id=<?= (int)$servis['id'] ?>" class="text-decoration-none"><strong><?= e($servis['servis_no']) ?></strong></a></td>
                                <td><?= e($servis['urun_adi']) ?></td>
                                <td><span class="badge-status <?= $durumClass ?>"><?= e($servis['durum']) ?></span></td>
                                <td class="text-end"><?= number_format((float)($servis['toplam_ucret'] ?? 0), 2, '.', '') ?> ₺</td>
                                <td>
                                    <a href="<?= BASE_URL ?>/teknik_servis_duzenle.php?id=<?= (int)$servis['id'] ?>" class="btn btn-outline-primary btn-sm" title="Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">Henüz servis kaydı yok</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== SİPARİŞLER TABLOSU (Checkbox + Butonlar Eklendi) ===== -->
        <div class="card-custom mt-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-shopping-cart"></i> SİPARİŞLER</h5>
                <div>
                    <span class="text-muted"><?= count($siparisler) ?> adet</span>
                    <button class="btn btn-success-custom btn-sm ms-2" onclick="seciliFaturaOlustur()">
                        <i class="fas fa-file-invoice"></i> Fatura Oluştur
                    </button>
                    <button class="btn btn-primary-custom btn-sm ms-1" onclick="seciliMakbuzOlustur()">
                        <i class="fas fa-receipt"></i> Makbuz Oluştur
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table-custom" id="siparisTable">
                    <thead>
                        <tr>
                            <th style="width:30px;"><input type="checkbox" id="selectAllSiparis" onchange="toggleAllSiparis(this)"></th>
                            <th>Sipariş No</th>
                            <th>Tarih</th>
                            <th>Durum</th>
                            <th class="text-end">Tutar</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($siparisler): ?>
                            <?php foreach ($siparisler as $siparis): ?>
                            <?php
                                $durumClass = match($siparis['durum']) {
                                    'BEKLEMEDE'    => 'bg-warning',
                                    'FATURALANDI'  => 'bg-success',
                                    'MAKBUZLANDI'  => 'bg-info',
                                    'İPTAL'        => 'bg-danger',
                                    default        => 'bg-secondary',
                                };
                                $disabled = $siparis['durum'] === 'BEKLEMEDE' ? '' : 'disabled';
                            ?>
                            <tr>
                                <td><input type="checkbox" class="siparis-check" value="<?= (int)$siparis['id'] ?>" <?= $disabled ?>></td>
                                <td><a href="<?= BASE_URL ?>/siparis_olustur.php?id=<?= (int)$siparis['id'] ?>" class="text-decoration-none"><strong><?= e($siparis['siparis_no']) ?></strong></a></td>
                                <td><?= $siparis['siparis_tarihi'] ? format_tarih($siparis['siparis_tarihi']) : '-' ?></td>
                                <td><span class="badge-status <?= $durumClass ?>"><?= e($siparis['durum']) ?></span></td>
                                <td class="text-end"><?= number_format((float)($siparis['genel_toplam'] ?? 0), 2, '.', '') ?> ₺</td>
                                <td>
                                    <a href="<?= BASE_URL ?>/siparis_olustur.php?id=<?= (int)$siparis['id'] ?>" class="btn btn-outline-primary btn-sm" title="Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">Henüz sipariş yok</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== HESAP HAREKETLERİ ===== -->
        <div class="card-custom mt-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-exchange-alt"></i> HESAP HAREKETLERİ</h5>
                <div>
                    <button class="btn btn-success-custom btn-sm" data-bs-toggle="modal" data-bs-target="#yeniHareketModal">
                        <i class="fas fa-plus"></i> YENİ HAREKET
                    </button>
                    <span class="text-muted"><?= count($hesap_hareketleri) ?> kayıt</span>
                </div>
            </div>

            <!-- Filtreler -->
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <input type="date" id="hareket_baslangic" class="form-control form-control-sm" placeholder="Başlangıç">
                </div>
                <div class="col-md-3">
                    <input type="date" id="hareket_bitis" class="form-control form-control-sm" placeholder="Bitiş">
                </div>
                <div class="col-md-3">
                    <select id="hareket_tur_filtre" class="form-select form-select-sm">
                        <option value="TÜMÜ">TÜMÜ</option>
                        <option value="TAHSİLAT">TAHSİLAT</option>
                        <option value="ÖDEME">ÖDEME</option>
                        <option value="KOMİSYON">KOMİSYON</option>
                        <option value="VERESİYE">VERESİYE</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary-custom btn-sm w-100" onclick="hareketleriFiltrele()">
                        <i class="fas fa-search"></i> FİLTRELE
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table-custom" id="hareketTablo">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Tür</th>
                            <th>İşlem</th>
                            <th class="text-end">Tutar</th>
                            <th>Açıklama</th>
                            <th>Referans</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody id="hareketTbody">
                        <?php if ($hesap_hareketleri): ?>
                            <?php foreach ($hesap_hareketleri as $hareket): ?>
                            <?php
                                $hareketTurClass = match(true) {
                                    $hareket['hareket_turu'] === 'TAHSİLAT' => 'bg-success',
                                    $hareket['hareket_turu'] === 'ÖDEME'    => 'bg-danger',
                                    $hareket['hareket_turu'] === 'KOMİSYON' => 'bg-warning',
                                    $hareket['hareket_turu'] === 'VERESİYE' => 'bg-info',
                                    str_starts_with($hareket['hareket_turu'] ?? '', 'HIZLI_TAHSİLAT') => 'bg-success',
                                    str_starts_with($hareket['hareket_turu'] ?? '', 'HIZLI_ÖDEME')    => 'bg-info',
                                    str_starts_with($hareket['hareket_turu'] ?? '', 'MAKBUZ_')       => 'bg-primary',
                                    str_starts_with($hareket['hareket_turu'] ?? '', 'FATURA_TAHSİLAT') => 'bg-success',
                                    str_starts_with($hareket['hareket_turu'] ?? '', 'FATURA_ÖDEME')    => 'bg-info',
                                    $hareket['hareket_turu'] === 'PRİM_ÖDEME'      => 'bg-danger',
                                    $hareket['hareket_turu'] === 'SİPARİŞ'         => 'bg-primary',
                                    default    => 'bg-secondary',
                                };
                                // NOT: 'GELEN'/'GIDEN' de GİRİŞ/ÇIKIŞ ile eş anlamlı kullanılıyor
                                // (bkz. hesap_hareketleri.php, kasa_ana.php, kasa_rapor.php'deki aynı kontrol) -
                                // makbuz_olustur.php bu değerleri yazıyor, burada da tanınmazsa rozet/toplamlar yanlış çıkar.
                                $hareketGirisMi = in_array($hareket['islem_turu'], ['GİRİŞ', 'GELEN'], true);
                                $islemClass = $hareketGirisMi ? 'bg-success' : 'bg-danger';
                                $tutarClass = $hareketGirisMi ? 'text-success' : 'text-danger';
                            ?>
                            <tr>
                                <td><?= $hareket['tarih'] ? format_tarih($hareket['tarih']) : '-' ?></td>
                                <td><span class="badge-status <?= $hareketTurClass ?>"><?= e($hareket['hareket_turu']) ?></span></td>
                                <td><span class="badge-status <?= $islemClass ?>"><?= e($hareket['islem_turu']) ?></span></td>
                                <td class="text-end <?= $tutarClass ?>"><?= number_format((float)$hareket['tutar'], 2, '.', '') ?> ₺</td>
                                <td><?= e($hareket['aciklama'] ?: '-') ?></td>
                                <td><?= e($hareket['referans_no'] ?: '-') ?></td>
                                <td>
                                    <button class="btn btn-danger btn-sm" onclick="hareketSil(<?= (int)$hareket['id'] ?>)" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-3">Henüz hareket yok</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr style="border-top: 2px solid var(--border-color);">
                            <td colspan="3" class="text-end"><strong>TOPLAM GİRİŞ</strong></td>
                            <td class="text-end text-success" id="toplam_giris">0.00 ₺</td>
                            <td colspan="3"></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end"><strong>TOPLAM ÇIKIŞ</strong></td>
                            <td class="text-end text-danger" id="toplam_cikis">0.00 ₺</td>
                            <td colspan="3"></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end" style="font-weight: 700; font-size: 15px;"><strong>NET BAKİYE</strong></td>
                            <td class="text-end" id="net_bakiye" style="font-weight: 700; font-size: 15px;">0.00 ₺</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <small class="text-muted">* Net Bakiye, sadece aşağıda listelenen hesap hareketlerinin (gerçek kasa/nakit giriş-çıkışlarının) toplamıdır - üstteki "Mevcut Bakiye" ise veresiye/tahsil edilmemiş tutarları da içerir, bu yüzden ikisi genelde birbirinden farklı çıkar (bu normaldir).</small>
        </div>
    </div>
</div>

<!-- ===== YENİ HAREKET EKLE MODAL ===== -->
<div class="modal fade" id="yeniHareketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background: var(--bg-secondary); color: var(--text-primary);">
            <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> YENİ HESAP HAREKETİ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="yeniHareketForm">
                    <input type="hidden" name="cari_id" value="<?= (int)$cari['id'] ?>">

                    <div class="mb-3">
                        <label class="form-label">HAREKET TÜRÜ <span class="text-danger">*</span></label>
                        <select name="hareket_turu" class="form-select" required>
                            <option value="TAHSİLAT">TAHSİLAT</option>
                            <option value="ÖDEME">ÖDEME</option>
                            <option value="KOMİSYON">KOMİSYON</option>
                            <option value="VERESİYE">VERESİYE</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">İŞLEM TÜRÜ <span class="text-danger">*</span></label>
                        <select name="islem_turu" class="form-select" required>
                            <option value="GİRİŞ">GİRİŞ (Para Girişi)</option>
                            <option value="ÇIKIŞ">ÇIKIŞ (Para Çıkışı)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">TUTAR <span class="text-danger">*</span></label>
                        <input type="number" name="tutar" class="form-control" step="0.01" placeholder="0.00" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">HANGİ KASADAN/HESAPTAN? <span class="text-danger">*</span></label>
                        <select name="hesap_id" class="form-select" required>
                            <option value="">-- Seçin --</option>
                            <?php foreach ($hesaplar as $h): ?>
                                <option value="<?= (int)$h['id'] ?>"><?= e($h['hesap_adi']) ?> (<?= number_format((float)$h['bakiye'], 2, '.', '') ?> ₺)</option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">
                            Bu, gerçek bir kasa/hesap hareketi oluşturur - seçtiğin kasanın bakiyesi de
                            bu tutar kadar değişir.
                        </small>
                        <?php if (!$hesaplar): ?>
                            <br><small class="text-warning">Aktif bir hesap/kasa yok. <a href="<?= BASE_URL ?>/hesap_ekle.php" target="_blank">Buradan ekleyebilirsiniz</a>.</small>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">TARİH <span class="text-danger">*</span></label>
                        <input type="date" name="tarih" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">AÇIKLAMA</label>
                        <textarea name="aciklama" class="form-control" rows="2" placeholder="İşlem açıklaması..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">REFERANS NO (Fatura/Makbuz No)</label>
                        <input type="text" name="referans_no" class="form-control" placeholder="FAT-2024-001">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">İLGİLİ KİŞİ</label>
                        <input type="text" name="ilgili_kisi" class="form-control" placeholder="Ahmet Yılmaz">
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İPTAL</button>
                <button type="button" class="btn btn-success" onclick="hareketKaydet()">
                    <i class="fas fa-save"></i> KAYDET
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = <<<'JS'
<script>
// ========== HAREKET KAYDET ==========
function hareketKaydet() {
    var form = document.getElementById('yeniHareketForm');
    var formData = new FormData(form);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch('api/hesap_hareketi_ekle.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            alert('Hareket başarıyla kaydedildi!');
            location.reload();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(function(error) {
        alert('Hata oluştu: ' + error);
    });
}

// ========== HAREKET SİL ==========
function hareketSil(id) {
    if (!confirm('Bu hareketi silmek istediğinize emin misiniz?')) return;

    fetch('api/hesap_hareketi_sil.php?id=' + id + '&csrf_token=' + encodeURIComponent(CSRF_TOKEN), {
        method: 'DELETE'
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            alert('Hareket silindi!');
            location.reload();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(function(error) {
        alert('Hata oluştu: ' + error);
    });
}

// ========== HAREKET FİLTRELE ==========
function hareketleriFiltrele() {
    var baslangic = document.getElementById('hareket_baslangic').value;
    var bitis = document.getElementById('hareket_bitis').value;
    var tur = document.getElementById('hareket_tur_filtre').value;

    var rows = document.querySelectorAll('#hareketTbody tr');
    var toplamGiris = 0;
    var toplamCikis = 0;

    rows.forEach(function(row) {
        if (row.cells.length < 4) return;
        var tarih = row.cells[0].textContent.trim();
        var turText = row.cells[1].textContent.trim();
        var tutarText = row.cells[3].textContent.trim().replace(' ₺', '');
        var tutar = parseFloat(tutarText) || 0;
        var islemTuru = row.cells[2].textContent.trim();

        var goster = true;

        if (baslangic && tarih < baslangic.replace(/-/g, '.')) {
            goster = false;
        }
        if (bitis && tarih > bitis.replace(/-/g, '.')) {
            goster = false;
        }

        if (tur !== 'TÜMÜ' && turText !== tur) {
            goster = false;
        }

        row.style.display = goster ? '' : 'none';

        if (goster) {
            if (islemTuru === 'GİRİŞ' || islemTuru === 'GELEN') {
                toplamGiris += tutar;
            } else {
                toplamCikis += tutar;
            }
        }
    });

    document.getElementById('toplam_giris').textContent = toplamGiris.toFixed(2) + ' ₺';
    document.getElementById('toplam_cikis').textContent = toplamCikis.toFixed(2) + ' ₺';
    document.getElementById('net_bakiye').textContent = (toplamGiris - toplamCikis).toFixed(2) + ' ₺';
}

// ========== SEÇİLİ SİPARİŞ VE SERVİSLERİ FATURA OLUŞTUR ==========
function toggleAllSiparis(master) {
    document.querySelectorAll('.siparis-check:not(:disabled)').forEach(chk => chk.checked = master.checked);
}
function toggleAllServis(master) {
    document.querySelectorAll('.servis-check:not(:disabled)').forEach(chk => chk.checked = master.checked);
}

function seciliFaturaOlustur() {
    var siparisIds = [];
    document.querySelectorAll('.siparis-check:checked').forEach(chk => siparisIds.push(chk.value));
    var servisIds = [];
    document.querySelectorAll('.servis-check:checked').forEach(chk => servisIds.push(chk.value));

    if (siparisIds.length === 0 && servisIds.length === 0) {
        alert('Lütfen en az bir sipariş veya servis seçin!');
        return;
    }
    if (!confirm('Seçili sipariş ve servislerle fatura oluşturmak istediğinize emin misiniz?')) return;

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'siparis_servis_faturalandir.php';
    var inputSiparis = document.createElement('input');
    inputSiparis.type = 'hidden';
    inputSiparis.name = 'siparis_ids';
    inputSiparis.value = siparisIds.join(',');
    form.appendChild(inputSiparis);
    var inputServis = document.createElement('input');
    inputServis.type = 'hidden';
    inputServis.name = 'servis_ids';
    inputServis.value = servisIds.join(',');
    form.appendChild(inputServis);
    var csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = 'csrf_token';
    csrf.value = CSRF_TOKEN;
    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
}

// ========== SEÇİLİ SİPARİŞ VE SERVİSLERİ MAKBUZ OLUŞTUR ==========
function seciliMakbuzOlustur() {
    var siparisIds = [];
    document.querySelectorAll('.siparis-check:checked').forEach(chk => siparisIds.push(chk.value));
    var servisIds = [];
    document.querySelectorAll('.servis-check:checked').forEach(chk => servisIds.push(chk.value));

    if (siparisIds.length === 0 && servisIds.length === 0) {
        alert('Lütfen en az bir sipariş veya servis seçin!');
        return;
    }
    if (!confirm('Seçili sipariş ve servislerle makbuz oluşturmak istediğinize emin misiniz?')) return;

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'siparis_servis_makbuz_olustur.php';
    var inputSiparis = document.createElement('input');
    inputSiparis.type = 'hidden';
    inputSiparis.name = 'siparis_ids';
    inputSiparis.value = siparisIds.join(',');
    form.appendChild(inputSiparis);
    var inputServis = document.createElement('input');
    inputServis.type = 'hidden';
    inputServis.name = 'servis_ids';
    inputServis.value = servisIds.join(',');
    form.appendChild(inputServis);
    var csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = 'csrf_token';
    csrf.value = CSRF_TOKEN;
    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
}

// Sayfa yüklendiğinde toplamları otomatik hesapla
document.addEventListener('DOMContentLoaded', function() {
    hareketleriFiltrele();
});
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>