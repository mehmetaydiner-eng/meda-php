<?php
/**
 * public/siparis_olustur.php
 * Sipariş oluşturma / düzenleme (tema ile uyumlu)
 * URL'den ?cari_id=123 ile gelirse otomatik seçer.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/numara_manager.php';
require_login();

$siparis_id = safe_int($_GET['id'] ?? null, 0) ?: null;
$siparis = null;
$detaylar = [];
$url_cari_id = safe_int($_GET['cari_id'] ?? null, 0) ?: null;

if ($siparis_id) {
    $stmt = $pdo->prepare('SELECT * FROM siparisler WHERE id = ?');
    $stmt->execute([$siparis_id]);
    $siparis = $stmt->fetch();
    if (!$siparis) {
        http_response_code(404);
        die('Sipariş bulunamadı.');
    }
    $stmt = $pdo->prepare('SELECT * FROM siparis_detaylari WHERE siparis_id = ?');
    $stmt->execute([$siparis_id]);
    $detaylar = $stmt->fetchAll();
}

$cariler = $pdo->query('SELECT * FROM cariler ORDER BY unvan')->fetchAll();

// Seçili cari ID'sini belirle: önce sipariş varsa onun cari_id'si, yoksa URL'deki cari_id
$selected_cari_id = $siparis ? (int)$siparis['cari_id'] : ($url_cari_id ?: 0);

$page_title = $siparis ? 'SİPARİŞ DÜZENLE' : 'YENİ SİPARİŞ';
$breadcrumb = $siparis ? 'Sipariş Düzenle' : 'Yeni Sipariş';
$current_page = 'siparisler';
require_once __DIR__ . '/../includes/header.php';

$now = new DateTime('now');
// Önizleme için preview_siparis_no kullan (artırmaz)
$default_no = $siparis ? $siparis['siparis_no'] : preview_siparis_no($pdo);
$default_tarih = $siparis ? (new DateTime($siparis['siparis_tarihi']))->format('Y-m-d') : $now->format('Y-m-d');
?>

<div class="container mt-4">
    <!-- ===== SİPARİŞ BİLGİLERİ KARTI ===== -->
    <div class="card-custom mb-4">
        <div class="card-header-custom">
            <h5><i class="fas fa-shopping-cart"></i> <?= $siparis ? 'SİPARİŞ DÜZENLE' : 'YENİ SİPARİŞ' ?></h5>
            <div>
                <span class="badge-status <?= $siparis && $siparis['durum'] === 'BEKLEMEDE' ? 'bg-warning' : 'bg-success' ?>">
                    <?= $siparis ? e($siparis['durum']) : 'BEKLEMEDE' ?>
                </span>
            </div>
        </div>
        <div class="p-3">
            <div class="row g-3">
                <div class="col-md-6">
                    <div style="font-size: 14px; color: var(--text-muted, #8a8a8a);">
                        <strong>No:</strong> <?= e($default_no) ?>
                    </div>
                    <div style="font-size: 14px; color: var(--text-muted, #8a8a8a); margin-top: 4px;">
                        <strong>Tarih:</strong> <?= $now->format('d.m.Y H:i') ?>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <div style="font-size: 14px; color: var(--text-muted, #8a8a8a);">
                        <strong>Durum:</strong>
                        <span class="badge-status <?= $siparis && $siparis['durum'] === 'BEKLEMEDE' ? 'bg-warning' : 'bg-success' ?>">
                            <?= $siparis ? e($siparis['durum']) : 'BEKLEMEDE' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="siparis_kaydet.php" id="siparisForm">
        <?= csrf_field() ?>
        <input type="hidden" name="siparis_id" value="<?= $siparis_id ?>">
        <input type="hidden" name="siparis_no" value="<?= e($default_no) ?>">

        <!-- ===== CARİ BİLGİLERİ KARTI ===== -->
        <div class="card-custom mb-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-user"></i> CARİ BİLGİLERİ</h5>
            </div>
            <div class="p-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small">CARİ / MÜŞTERİ <span class="text-danger">*</span></label>
                        <select name="cari_id" class="form-select form-select-sm" required>
                            <option value="">Seçin...</option>
                            <?php foreach ($cariler as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= $selected_cari_id === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= e($c['unvan']) ?> - <?= e($c['vergi_no'] ?: 'VKN YOK') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Sipariş Tarihi</label>
                        <input type="date" name="siparis_tarihi" class="form-control form-control-sm" value="<?= $default_tarih ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== ÜRÜN EKLEME KARTI ===== -->
        <div class="card-custom mb-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-plus-circle"></i> ÜRÜN EKLE</h5>
                <button type="button" class="btn btn-primary-custom btn-sm" data-bs-toggle="modal" data-bs-target="#yeniUrunModal">
                    <i class="fas fa-plus-circle"></i> YENİ ÜRÜN
                </button>
            </div>
            <div class="p-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">ÜRÜN ARA</label>
                        <input type="text" id="urun-ara" class="form-control form-control-sm" placeholder="Ürün adı veya barkod...">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small">&nbsp;</label>
                        <button type="button" class="btn btn-primary-custom btn-sm w-100" onclick="urunAra()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">ÜRÜN SEÇ</label>
                        <select id="urun-listesi" class="form-select form-select-sm">
                            <option value="">Seçin...</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">MİKTAR</label>
                        <input type="number" id="urun-miktar" class="form-control form-control-sm" value="1" min="0.01" step="0.01">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">FİYAT</label>
                        <input type="number" id="urun-fiyat" class="form-control form-control-sm" step="0.01" value="0">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small">&nbsp;</label>
                        <button type="button" class="btn btn-success-custom btn-sm w-100" onclick="kalemEkle()">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== SİPARİŞ KALEMLERİ TABLOSU ===== -->
        <div class="card-custom mb-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-list"></i> SİPARİŞ KALEMLERİ</h5>
            </div>
            <div class="p-0">
                <div class="table-responsive">
                    <table class="table-custom" id="siparis-kalemleri">
                        <thead>
                            <tr>
                                <th style="width:30px;">#</th>
                                <th style="width:25%;">ÜRÜN ADI</th>
                                <th style="width:15%;">BARKOD</th>
                                <th style="width:10%;" class="text-center">MİKTAR</th>
                                <th style="width:15%;" class="text-end">BİRİM FİYAT</th>
                                <th style="width:10%;" class="text-center">İSKONTO %</th>
                                <th style="width:10%;" class="text-center">KDV %</th>
                                <th style="width:15%;" class="text-end">TOPLAM</th>
                                <th style="width:35px;" class="text-center">İŞLEM</th>
                            </tr>
                        </thead>
                        <tbody id="siparis-kalem-tbody">
                            <?php if ($detaylar): ?>
                                <?php foreach ($detaylar as $i => $d): ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td><input type="hidden" name="urun_ids[]" value="<?= (int)$d['urun_id'] ?>"><?= e($d['urun_adi']) ?></td>
                                    <td><?= e($d['barkod'] ?: '-') ?></td>
                                    <td class="text-center"><input type="number" class="form-control form-control-sm miktar-input" name="miktarlar[]" value="<?= number_format((float)$d['miktar'], 2, '.', '') ?>" min="0.01" step="0.01" onchange="satirGuncelle(this)"></td>
                                    <td class="text-end"><input type="number" class="form-control form-control-sm fiyat-input" name="fiyatlar[]" value="<?= number_format((float)$d['birim_fiyati'], 2, '.', '') ?>" min="0" step="0.01" onchange="satirGuncelle(this)"></td>
                                    <td class="text-center"><input type="number" class="form-control form-control-sm iskonto-input" name="iskontolar[]" value="<?= number_format((float)$d['iskonto'], 2, '.', '') ?>" min="0" max="100" step="0.5" onchange="satirGuncelle(this)"></td>
                                    <td class="text-center"><input type="number" class="form-control form-control-sm kdv-input" name="kdvler[]" value="<?= number_format((float)$d['vergi_orani'], 0, '.', '') ?>" min="0" max="100" step="0.5" onchange="satirGuncelle(this)"></td>
                                    <td class="text-end toplam-tutar"><?= number_format((float)$d['toplam_tutar'], 2, '.', '') ?></td>
                                    <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm" onclick="satirSil(this)"><i class="fas fa-times"></i></button></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr id="bos-kalem">
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fas fa-plus-circle fa-2x d-block mb-2"></i>
                                        Ürün eklemek için yukarıdaki formu kullanın
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7" class="text-end"><strong>ARA TOPLAM</strong></td>
                                <td class="text-end" id="ara-toplam">0.00</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="7" class="text-end"><strong>TOPLAM İSKONTO</strong></td>
                                <td class="text-end" id="iskonto-tutari">0.00</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="7" class="text-end"><strong>TOPLAM KDV</strong></td>
                                <td class="text-end" id="vergi-tutari">0.00</td>
                                <td></td>
                            </tr>
                            <tr style="border-top: 2px solid var(--border-color, #2a2a2a);">
                                <td colspan="7" class="text-end" style="font-size: 15px; font-weight: 700;">GENEL TOPLAM</td>
                                <td class="text-end" style="font-size: 15px; font-weight: 700; color: var(--success, #4ad46a);" id="genel-toplam">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- ===== AÇIKLAMA KARTI ===== -->
        <div class="card-custom mb-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-sticky-note"></i> AÇIKLAMA / NOT</h5>
            </div>
            <div class="p-3">
                <textarea name="aciklama" class="form-control form-control-sm" rows="2" placeholder="Sipariş notu..."><?= e($siparis['aciklama'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- ===== BUTONLAR ===== -->
        <div class="d-flex gap-2 justify-content-end">
            <button type="submit" class="btn btn-success-custom">
                <i class="fas fa-save"></i> SİPARİŞİ KAYDET
            </button>
            <a href="<?= BASE_URL ?>/siparisler.php" class="btn btn-outline-secondary">İPTAL</a>
        </div>
    </form>
</div>

<!-- ===== YENİ ÜRÜN MODAL ===== -->
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
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İPTAL</button>
                <button type="button" class="btn btn-success-custom btn-sm" onclick="modalUrunKaydet()">
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

function urunAra() {
    var q = document.getElementById('urun-ara').value.trim();
    if (q.length < 2) return;

    fetch(API_BASE + '/api/stok_ara.php?q=' + encodeURIComponent(q))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var select = document.getElementById('urun-listesi');
            select.innerHTML = '<option value="">Ürün seçin...</option>';
            data.forEach(function(urun) {
                select.innerHTML += '<option value="' + urun.id + '" data-fiyat="' + urun.satis_fiyati + '" data-ad="' + urun.urun_adi + '" data-barkod="' + (urun.barkod || '') + '">' + urun.urun_adi + ' - ' + (urun.barkod || 'BARKOD YOK') + ' (' + urun.satis_fiyati + ' TL)</option>';
            });
        });
}

document.getElementById('urun-listesi').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    if (selected.value) {
        document.getElementById('urun-fiyat').value = selected.dataset.fiyat || 0;
    }
});

function kalemEkle() {
    var select = document.getElementById('urun-listesi');
    var selected = select.options[select.selectedIndex];
    if (!selected.value) { alert('Lütfen bir ürün seçin!'); return; }

    var miktar = parseFloat(document.getElementById('urun-miktar').value) || 1;
    var fiyat = parseFloat(document.getElementById('urun-fiyat').value) || 0;
    var iskonto = 0;
    var kdv = 18;
    var satirToplam = miktar * fiyat;
    var iskontoTutar = satirToplam * (iskonto / 100);
    var matrah = satirToplam - iskontoTutar;
    var kdvTutar = matrah * (kdv / 100);
    var toplam = matrah + kdvTutar;

    var tbody = document.getElementById('siparis-kalem-tbody');
    var bos = document.getElementById('bos-kalem');
    if (bos) bos.remove();

    var index = tbody.children.length + 1;
    var row = document.createElement('tr');
    row.innerHTML =
        '<td>' + index + '</td>' +
        '<td><input type="hidden" name="urun_ids[]" value="' + selected.value + '">' + selected.dataset.ad + '</td>' +
        '<td>' + (selected.dataset.barkod || '-') + '</td>' +
        '<td class="text-center"><input type="number" class="form-control form-control-sm miktar-input" name="miktarlar[]" value="' + miktar.toFixed(2) + '" min="0.01" step="0.01" onchange="satirGuncelle(this)"></td>' +
        '<td class="text-end"><input type="number" class="form-control form-control-sm fiyat-input" name="fiyatlar[]" value="' + fiyat.toFixed(2) + '" min="0" step="0.01" onchange="satirGuncelle(this)"></td>' +
        '<td class="text-center"><input type="number" class="form-control form-control-sm iskonto-input" name="iskontolar[]" value="' + iskonto.toFixed(0) + '" min="0" max="100" step="0.5" onchange="satirGuncelle(this)"></td>' +
        '<td class="text-center"><input type="number" class="form-control form-control-sm kdv-input" name="kdvler[]" value="' + kdv.toFixed(0) + '" min="0" max="100" step="0.5" onchange="satirGuncelle(this)"></td>' +
        '<td class="text-end toplam-tutar">' + toplam.toFixed(2) + '</td>' +
        '<td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm" onclick="satirSil(this)"><i class="fas fa-times"></i></button></td>';
    tbody.appendChild(row);
    hesaplaToplam();
}

function satirGuncelle(input) {
    var row = input.closest('tr');
    var miktar = parseFloat(row.querySelector('.miktar-input').value) || 0;
    var fiyat = parseFloat(row.querySelector('.fiyat-input').value) || 0;
    var iskonto = parseFloat(row.querySelector('.iskonto-input').value) || 0;
    var kdv = parseFloat(row.querySelector('.kdv-input').value) || 18;
    var satirToplam = miktar * fiyat;
    var iskontoTutar = satirToplam * (iskonto / 100);
    var matrah = satirToplam - iskontoTutar;
    var kdvTutar = matrah * (kdv / 100);
    var toplam = matrah + kdvTutar;
    row.querySelector('.toplam-tutar').textContent = toplam.toFixed(2);
    hesaplaToplam();
}

function satirSil(btn) {
    var row = btn.closest('tr');
    row.remove();
    document.querySelectorAll('#siparis-kalem-tbody tr').forEach(function(tr, i) { tr.cells[0].textContent = i + 1; });
    hesaplaToplam();
}

function hesaplaToplam() {
    var araToplam = 0, toplamIskonto = 0, toplamKdv = 0;
    document.querySelectorAll('#siparis-kalem-tbody tr').forEach(function(row) {
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

// Modal fonksiyonları
function modalBarkodOlustur() {
    var prefix = '869';
    var random = '';
    for (var i = 0; i < 9; i++) random += Math.floor(Math.random() * 10);
    document.getElementById('modal_barkod').value = prefix + random + Math.floor(Math.random() * 10);
}

function modalUrunKaydet() {
    var urun_kodu = document.getElementById('modal_urun_kodu').value.trim().toUpperCase();
    var urun_adi = document.getElementById('modal_urun_adi').value.trim().toUpperCase();
    if (!urun_kodu) { alert('Ürün kodu zorunludur!'); return; }
    if (!urun_adi) { alert('Ürün adı zorunludur!'); return; }

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
                bootstrap.Modal.getInstance(document.getElementById('yeniUrunModal')).hide();
                alert('Ürün başarıyla eklendi! Şimdi listeye ekleyebilirsiniz.');

                var select = document.getElementById('urun-listesi');
                var option = document.createElement('option');
                option.value = data.urun_id;
                option.dataset.fiyat = satis_fiyati;
                option.dataset.ad = urun_adi;
                option.dataset.barkod = barkod || '';
                option.text = urun_adi + ' - ' + (barkod || 'BARKOD YOK') + ' (' + satis_fiyati + ' TL)';
                select.appendChild(option);
                select.value = data.urun_id;
                document.getElementById('urun-fiyat').value = satis_fiyati;
            } else {
                alert('Hata: ' + (data.message || 'Ürün eklenemedi!'));
            }
        })
        .catch(function(error) { alert('Hata oluştu: ' + error); });
}

document.getElementById('urun-ara').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') urunAra();
});
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>