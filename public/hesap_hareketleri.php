<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$id = safe_int($_GET['id'] ?? null);
if (!$id) {
    header('Location: ' . BASE_URL . '/hesaplar_listesi.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM hesaplar WHERE id = ?');
$stmt->execute([$id]);
$hesap = $stmt->fetch();
if (!$hesap) {
    http_response_code(404);
    die('Hesap bulunamadı.');
}

$stmt = $pdo->prepare('SELECT h.*, c.unvan AS cari_unvan FROM hesap_hareketleri h LEFT JOIN cariler c ON c.id = h.cari_id WHERE h.hesap_id = ? ORDER BY h.hareket_tarihi DESC');
$stmt->execute([$id]);
$hareketler = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT SUM(tutar) FROM hesap_hareketleri WHERE hesap_id = ? AND islem_turu IN ('GELEN','GİRİŞ')");
$stmt->execute([$id]);
$gelen_toplam = (float)($stmt->fetchColumn() ?: 0);

$stmt = $pdo->prepare("SELECT SUM(tutar) FROM hesap_hareketleri WHERE hesap_id = ? AND islem_turu IN ('GIDEN','ÇIKIŞ')");
$stmt->execute([$id]);
$giden_toplam = (float)($stmt->fetchColumn() ?: 0);

$cariler = $pdo->query('SELECT * FROM cariler ORDER BY unvan')->fetchAll();

$page_title   = 'HESAP HAREKETLERİ';
$breadcrumb   = 'Hesap Hareketleri';
$current_page = 'hesaplar_listesi';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Hesap Bilgileri -->
<div class="card-custom mb-4">
    <div class="card-header-custom">
        <h5><i class="fas fa-university"></i> <?= e($hesap['hesap_adi']) ?></h5>
        <div>
            <?php
                $turClass = match($hesap['hesap_turu']) {
                    'BANKA'     => 'bg-info',
                    'KASA'      => 'bg-warning',
                    'KOMISYON'  => 'bg-success',
                    default     => 'bg-secondary',
                };
            ?>
            <span class="badge-status <?= $turClass ?>"><?= e($hesap['hesap_turu']) ?></span>
            <span class="text-muted ms-2">Kod: <?= e($hesap['hesap_kodu']) ?></span>
            <a href="<?= BASE_URL ?>/hesap_duzenle.php?id=<?= (int)$hesap['id'] ?>" class="btn btn-outline-primary btn-sm ms-2">
                <i class="fas fa-edit"></i> DÜZENLE
            </a>
            <a href="<?= BASE_URL ?>/hesaplar_listesi.php" class="btn btn-outline-info btn-sm ms-1">
                <i class="fas fa-arrow-left"></i> GERİ
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-3">
            <div class="text-center p-3" style="background: #121212; border-radius: 8px; border: 1px solid #2a2a2a;">
                <h6 class="text-muted">MEVCUT BAKİYE</h6>
                <h3 class="<?= $hesap['bakiye'] > 0 ? 'text-success' : ($hesap['bakiye'] < 0 ? 'text-danger' : '') ?>">
                    <?= number_format((float)$hesap['bakiye'], 2, '.', '') ?> <?= e($hesap['para_birimi']) ?>
                </h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center p-3" style="background: #121212; border-radius: 8px; border: 1px solid #2a2a2a;">
                <h6 class="text-muted">TOPLAM GELEN</h6>
                <h3 class="text-success"><?= number_format($gelen_toplam, 2, '.', '') ?> <?= e($hesap['para_birimi']) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center p-3" style="background: #121212; border-radius: 8px; border: 1px solid #2a2a2a;">
                <h6 class="text-muted">TOPLAM GİDEN</h6>
                <h3 class="text-danger"><?= number_format($giden_toplam, 2, '.', '') ?> <?= e($hesap['para_birimi']) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center p-3" style="background: #121212; border-radius: 8px; border: 1px solid #2a2a2a;">
                <h6 class="text-muted">NET DURUM</h6>
                <h3 class="<?= ($gelen_toplam - $giden_toplam) >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= number_format($gelen_toplam - $giden_toplam, 2, '.', '') ?> <?= e($hesap['para_birimi']) ?>
                </h3>
            </div>
        </div>
    </div>
</div>

<!-- Hareket Listesi -->
<div class="card-custom">
    <div class="card-header-custom">
        <h5><i class="fas fa-list"></i> HAREKET LİSTESİ</h5>
        <div>
            <button class="btn btn-success-custom btn-sm" data-bs-toggle="modal" data-bs-target="#yeniHareketModal">
                <i class="fas fa-plus"></i> YENİ HAREKET
            </button>
            <button class="btn btn-outline-primary btn-sm" onclick="window.print()">
                <i class="fas fa-print"></i> YAZDIR
            </button>
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
                <option value="VIRMAN">VİRMAN</option>
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
                    <th>Hareket No</th>
                    <th>Tarih</th>
                    <th>Tür</th>
                    <th>İşlem</th>
                    <th class="text-end">Tutar</th>
                    <th>Cari</th>
                    <th>Açıklama</th>
                    <th>Referans</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody id="hareketTbody">
                <?php if ($hareketler): ?>
                    <?php foreach ($hareketler as $hareket): ?>
                    <?php
                        $hareketTurClass = match(true) {
                            $hareket['hareket_turu'] === 'TAHSİLAT' => 'bg-success',
                            $hareket['hareket_turu'] === 'ÖDEME'    => 'bg-danger',
                            $hareket['hareket_turu'] === 'KOMİSYON' => 'bg-warning',
                            $hareket['hareket_turu'] === 'VERESİYE' => 'bg-info',
                            $hareket['hareket_turu'] === 'VIRMAN'   => 'bg-primary',
                            str_starts_with($hareket['hareket_turu'] ?? '', 'HIZLI_TAHSİLAT') => 'bg-success',
                            str_starts_with($hareket['hareket_turu'] ?? '', 'HIZLI_ÖDEME')    => 'bg-info',
                            str_starts_with($hareket['hareket_turu'] ?? '', 'MAKBUZ_')       => 'bg-primary',
                                    str_starts_with($hareket['hareket_turu'] ?? '', 'FATURA_TAHSİLAT') => 'bg-success',
                                    str_starts_with($hareket['hareket_turu'] ?? '', 'FATURA_ÖDEME')    => 'bg-info',
                            $hareket['hareket_turu'] === 'PRİM_ÖDEME'      => 'bg-danger',
                            default    => 'bg-secondary',
                        };
                        $isGiris = in_array($hareket['islem_turu'], ['GELEN', 'GİRİŞ'], true);
                    ?>
                    <tr>
                        <td><code><?= e($hareket['hareket_no'] ?: '-') ?></code></td>
                        <td><?= $hareket['hareket_tarihi'] ? format_tarih($hareket['hareket_tarihi'], 'd.m.Y H:i') : ($hareket['tarih'] ? format_tarih($hareket['tarih']) : '-') ?></td>
                        <td><span class="badge-status <?= $hareketTurClass ?>"><?= e($hareket['hareket_turu']) ?></span></td>
                        <td><span class="badge-status <?= $isGiris ? 'bg-success' : 'bg-danger' ?>"><?= e($hareket['islem_turu']) ?></span></td>
                        <td class="text-end <?= $isGiris ? 'text-success' : 'text-danger' ?>">
                            <?= number_format((float)$hareket['tutar'], 2, '.', '') ?> <?= e($hareket['para_birimi'] ?: 'TRY') ?>
                        </td>
                        <td>
                            <?php if ($hareket['cari_unvan']): ?>
                                <a href="<?= BASE_URL ?>/cari_detay.php?id=<?= (int)$hareket['cari_id'] ?>" class="text-decoration-none"><?= e($hareket['cari_unvan']) ?></a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($hareket['aciklama'] ?: '-') ?></td>
                        <td><?= e($hareket['referans_no'] ?: '-') ?></td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm" onclick="hareketSil(<?= (int)$hareket['id'] ?>)" title="Sil">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        <i class="fas fa-exchange-alt fa-3x d-block mb-3"></i>
                        Henüz hareket yok.<br>
                        <button type="button" class="btn btn-success-custom btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#yeniHareketModal">
                            <i class="fas fa-plus"></i> İLK HAREKETİ EKLE
                        </button>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="border-top: 2px solid var(--border-color);">
                    <td colspan="4" class="text-end"><strong>TOPLAM GELEN</strong></td>
                    <td class="text-end text-success" id="toplam_giris"><?= number_format($gelen_toplam, 2, '.', '') ?></td>
                    <td colspan="4"></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-end"><strong>TOPLAM GİDEN</strong></td>
                    <td class="text-end text-danger" id="toplam_cikis"><?= number_format($giden_toplam, 2, '.', '') ?></td>
                    <td colspan="4"></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-end" style="font-weight: 700; font-size: 15px;"><strong>NET BAKİYE</strong></td>
                    <td class="text-end" id="net_bakiye" style="font-weight: 700; font-size: 15px; <?= ($gelen_toplam - $giden_toplam) >= 0 ? 'color: #4ad46a;' : 'color: #d44a4a;' ?>">
                        <?= number_format($gelen_toplam - $giden_toplam, 2, '.', '') ?>
                    </td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- ===== YENİ HAREKET EKLE MODAL ===== -->
<div class="modal fade" id="yeniHareketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: var(--bg-secondary); color: var(--text-primary);">
            <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> YENİ HESAP HAREKETİ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="yeniHareketForm">
                    <input type="hidden" name="hesap_id" value="<?= (int)$hesap['id'] ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">CARİ / MÜŞTERİ <span class="text-danger">*</span></label>
                            <select name="cari_id" class="form-select" required>
                                <option value="">Seçin...</option>
                                <?php foreach ($cariler as $cari): ?>
                                <option value="<?= (int)$cari['id'] ?>"><?= e($cari['unvan']) ?> - <?= e($cari['vergi_no'] ?: 'VERGİ NO YOK') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">HAREKET TÜRÜ <span class="text-danger">*</span></label>
                            <select name="hareket_turu" class="form-select" required>
                                <option value="TAHSİLAT">TAHSİLAT</option>
                                <option value="ÖDEME">ÖDEME</option>
                                <option value="KOMİSYON">KOMİSYON</option>
                                <option value="VERESİYE">VERESİYE</option>
                                <option value="VIRMAN">VİRMAN</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">İŞLEM TÜRÜ <span class="text-danger">*</span></label>
                            <select name="islem_turu" class="form-select" required>
                                <option value="GİRİŞ">GİRİŞ (Para Girişi)</option>
                                <option value="ÇIKIŞ">ÇIKIŞ (Para Çıkışı)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ÖDEME TÜRÜ</label>
                            <select name="odeme_turu" class="form-select">
                                <option value="">Seçin...</option>
                                <option value="NAKİT">NAKİT</option>
                                <option value="KREDİ KARTI">KREDİ KARTI</option>
                                <option value="BANKA HAVALESİ">BANKA HAVALESİ</option>
                                <option value="EFT">EFT</option>
                                <option value="ÇEK">ÇEK</option>
                                <option value="SENET">SENET</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TUTAR <span class="text-danger">*</span></label>
                            <input type="number" name="tutar" class="form-control" step="0.01" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TARİH <span class="text-danger">*</span></label>
                            <input type="date" name="tarih" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">REFERANS NO (Fatura/Makbuz No)</label>
                            <input type="text" name="referans_no" class="form-control" placeholder="FAT-2024-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">İLGİLİ KİŞİ</label>
                            <input type="text" name="ilgili_kisi" class="form-control" placeholder="Ahmet Yılmaz">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">AÇIKLAMA</label>
                            <textarea name="aciklama" class="form-control" rows="2" placeholder="İşlem açıklaması..."></textarea>
                        </div>
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
$api_base_json = json_encode(BASE_URL);
$extra_js = <<<JS
<script>
var API_BASE = {$api_base_json};

function hareketKaydet() {
    var form = document.getElementById('yeniHareketForm');
    var formData = new FormData(form);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(API_BASE + '/api/hesap_hareketi_ekle.php', { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                alert('Hareket başarıyla kaydedildi!');
                location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(function(error) { alert('Hata oluştu: ' + error); });
}

function hareketSil(id) {
    if (!confirm('Bu hareketi silmek istediğinize emin misiniz?')) return;

    fetch(API_BASE + '/api/hesap_hareketi_sil.php?id=' + id + '&csrf_token=' + encodeURIComponent(CSRF_TOKEN), { method: 'DELETE' })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                alert('Hareket silindi!');
                location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(function(error) { alert('Hata oluştu: ' + error); });
}

function hareketleriFiltrele() {
    var baslangic = document.getElementById('hareket_baslangic').value;
    var bitis = document.getElementById('hareket_bitis').value;
    var tur = document.getElementById('hareket_tur_filtre').value;

    var rows = document.querySelectorAll('#hareketTbody tr');
    var toplamGiris = 0;
    var toplamCikis = 0;

    rows.forEach(function(row) {
        if (row.cells.length < 5) return;
        var tarihText = row.cells[1].textContent.trim();
        var turText = row.cells[2].textContent.trim();
        var tutarText = row.cells[4].textContent.trim().replace(/[^0-9.,-]/g, '').replace(',', '.');
        var tutar = parseFloat(tutarText) || 0;
        var islemTuru = row.cells[3].textContent.trim();

        var goster = true;

        if (baslangic) {
            var parts = tarihText.split('.');
            if (parts.length >= 3) {
                var isoStr = parts[2].substring(0,4) + '-' + parts[1].padStart(2,'0') + '-' + parts[0].padStart(2,'0');
                if (isoStr < baslangic) goster = false;
            }
        }
        if (bitis && goster) {
            var parts = tarihText.split('.');
            if (parts.length >= 3) {
                var isoStr = parts[2].substring(0,4) + '-' + parts[1].padStart(2,'0') + '-' + parts[0].padStart(2,'0');
                if (isoStr > bitis) goster = false;
            }
        }
        if (tur !== 'TÜMÜ' && turText !== tur) goster = false;

        row.style.display = goster ? '' : 'none';

        if (goster) {
            if (islemTuru === 'GİRİŞ' || islemTuru === 'GELEN') {
                toplamGiris += tutar;
            } else {
                toplamCikis += tutar;
            }
        }
    });

    document.getElementById('toplam_giris').textContent = toplamGiris.toFixed(2);
    document.getElementById('toplam_cikis').textContent = toplamCikis.toFixed(2);
    var net = toplamGiris - toplamCikis;
    var netEl = document.getElementById('net_bakiye');
    netEl.textContent = net.toFixed(2);
    netEl.style.color = net >= 0 ? '#4ad46a' : '#d44a4a';
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>
