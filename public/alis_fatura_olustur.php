<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$cariler = $pdo->query('SELECT * FROM cariler ORDER BY unvan')->fetchAll();

$page_title   = 'ALIŞ FATURASI - E-FATURA';
$breadcrumb   = 'Alış E-Fatura Oluştur';
$current_page = 'faturalar';
$extra_css    = '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/alis_fatura_olustur.css">';
require_once __DIR__ . '/../includes/header.php';

$now = new DateTime('now');
?>

<div class="efatura-container">

    <!-- XML'DEN İÇE AKTAR (Efe'nin isteği - 19 Temmuz 2026) -->
    <div class="efatura-info-box no-print" style="border: 2px dashed #4ad46a; margin-bottom: 16px;">
        <div class="info-title"><i class="fas fa-file-code"></i> XML'DEN FATURA İÇE AKTAR (GİB e-Fatura)</div>
        <div class="info-content">
            <form method="POST" action="<?= BASE_URL ?>/fatura_xml_yukle.php" enctype="multipart/form-data" class="row g-2 align-items-end">
                <?= csrf_field() ?>
                <div class="col-md-8">
                    <label class="form-label" style="font-size:12px;">Tedarikçiden gelen e-Fatura XML dosyasını seçin</label>
                    <input type="file" name="xml_dosya" accept=".xml" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="efatura-btn efatura-btn-primary w-100">
                        <i class="fas fa-upload"></i> YÜKLE VE ÖNİZLE
                    </button>
                </div>
            </form>
            <small class="text-muted d-block mt-1">
                Yüklediğinde hiçbir şey hemen kaydedilmez - önce bir önizleme ekranı çıkar,
                orada kontrol edip düzenleyip "Kaydet" dedikten sonra sisteme işlenir.
            </small>
        </div>
    </div>

    <!-- HEADER -->
    <div class="efatura-header">
        <div class="logo-area">
            <div class="logo-icon">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="firma-bilgi">
                <strong>MEDA TEKNOLOJİ A.Ş.</strong><br>
                Vergi No: 1234567890 | İSTANBUL<br>
                Tel: 0212 555 55 55 | info@meda.com
            </div>
        </div>
        <div class="fatura-bilgi">
            <div class="fatura-no"># ALIŞ FATURASI</div>
            <div>Tarih: <?= $now->format('d.m.Y H:i') ?></div>
            <div>Tip: E-FATURA</div>
        </div>
    </div>

    <!-- BODY -->
    <div class="efatura-body">

        <!-- TEDARİKÇİ BİLGİLERİ -->
        <div class="efatura-info-box">
            <div class="info-title">TEDARİKÇİ BİLGİLERİ</div>
            <div class="info-content">
                <select name="cari_id" id="cari-id-select" class="form-select form-select-sm" required>
                    <option value="">TEDARİKÇİ SEÇİN...</option>
                    <?php foreach ($cariler as $cari): ?>
                    <option value="<?= (int)$cari['id'] ?>">
                        <?= e($cari['unvan']) ?> (<?= e($cari['cari_turu'] ?: 'BELİRSİZ') ?>) - <?= e($cari['vergi_no'] ?: 'VERGİ NO YOK') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div id="cari-detay" class="mt-2" style="font-size: 12px; color: #8a8a8a;">
                    <div><strong>Vergi No:</strong> <span id="cari-vergi">-</span></div>
                    <div><strong>Vergi Dairesi:</strong> <span id="cari-vd">-</span></div>
                    <div><strong>Adres:</strong> <span id="cari-adres">-</span></div>
                    <div><strong>Telefon:</strong> <span id="cari-tel">-</span></div>
                </div>
            </div>
        </div>

        <!-- FATURA BİLGİLERİ -->
        <div class="efatura-info-box">
            <div class="info-title">FATURA BİLGİLERİ</div>
            <div class="info-content">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="label">Fatura No</div>
                        <div><strong>OTOMATİK</strong></div>
                    </div>
                    <div class="col-6">
                        <div class="label">Fatura Tarihi</div>
                        <div><?= $now->format('d.m.Y') ?></div>
                    </div>
                    <div class="col-6">
                        <div class="label">Fatura Türü</div>
                        <select name="fatura_turu" class="form-select form-select-sm">
                            <option value="ALIŞ" selected>ALIŞ</option>
                            <option value="SATIŞ">SATIŞ</option>
                            <option value="İADE">İADE</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <div class="label">Ödeme Türü</div>
                        <select name="odeme_turu" class="form-select form-select-sm">
                            <option value="NAKİT">NAKİT</option>
                            <option value="KREDİ KARTI">KREDİ KARTI</option>
                            <option value="BANKA HAVALESİ">BANKA HAVALESİ</option>
                            <option value="ÇEK">ÇEK</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <div class="label">Para Birimi</div>
                        <select name="para_birimi" class="form-select form-select-sm">
                            <option value="TRY">TL</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- FATURA KALEMLERİ TABLOSU -->
        <div class="efatura-info-box">
            <div class="info-title">FATURA KALEMLERİ</div>
            <div class="info-content">
                <div class="table-responsive">
                    <table class="efatura-table" id="fatura-kalemleri">
                        <thead>
                            <tr>
                                <th style="width: 30px;">#</th>
                                <th style="width: 25%;">ÜRÜN ADI</th>
                                <th style="width: 15%;">BARKOD</th>
                                <th style="width: 12%;" class="text-end">MİKTAR</th>
                                <th style="width: 15%;" class="text-end">BİRİM FİYAT</th>
                                <th style="width: 10%;" class="text-end">İSKONTO</th>
                                <th style="width: 18%;" class="text-end">TOPLAM</th>
                                <th style="width: 35px;" class="text-center">İŞLEM</th>
                            </tr>
                        </thead>
                        <tbody id="fatura-kalem-tbody">
                            <tr id="bos-kalem">
                                <td colspan="8" class="text-center text-muted py-3">
                                    <i class="fas fa-plus-circle fa-2x d-block mb-2"></i>
                                    Ürün eklemek için aşağıdaki formu kullanın
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" class="text-end"><strong>ARA TOPLAM</strong></td>
                                <td class="text-end" id="ara-toplam">0.00</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5"></td>
                                <td class="text-end"><strong>KDV (%20)</strong></td>
                                <td class="text-end" id="vergi-tutari">0.00</td>
                                <td></td>
                            </tr>
                            <tr style="border-top: 2px solid #2a2a2a;">
                                <td colspan="6" class="text-end" style="font-size: 15px; font-weight: 700;">
                                    GENEL TOPLAM
                                </td>
                                <td class="text-end" style="font-size: 15px; font-weight: 700; color: #4ad46a;" id="genel-toplam">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- ÜRÜN EKLEME FORMU -->
        <div class="efatura-info-box mt-3 no-print">
            <div class="info-title">ÜRÜN EKLE</div>
            <div class="info-content">
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" id="urun-ara" class="form-control form-control-sm" placeholder="Ürün adı veya barkod ara...">
                    </div>
                    <div class="col-md-2">
                        <button class="efatura-btn efatura-btn-primary w-100" onclick="urunAra()">
                            <i class="fas fa-search"></i> ARA
                        </button>
                    </div>
                    <div class="col-md-6" id="urun-secim">
                        <select id="urun-listesi" class="form-select form-select-sm">
                            <option value="">Ürün seçin...</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Miktar</label>
                        <input type="number" id="urun-miktar" class="form-control form-control-sm" value="1" min="1" step="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fiyat</label>
                        <input type="number" id="urun-fiyat" class="form-control form-control-sm" step="0.01" value="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">İskonto</label>
                        <input type="number" id="urun-iskonto" class="form-control form-control-sm" value="0" min="0" max="100" step="0.5">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button class="efatura-btn efatura-btn-success w-100" onclick="kalemEkle()">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- NOTLAR VE İŞLEMLER -->
        <div class="row g-3 mt-2">
            <div class="col-md-6">
                <div class="efatura-info-box">
                    <div class="info-title">FATURA NOTU</div>
                    <div class="info-content">
                        <textarea name="aciklama" id="fatura-notu" class="form-control form-control-sm" rows="2" placeholder="Fatura notu..."></textarea>
                    </div>
                </div>
            </div>
            <div class="col-md-6 no-print">
                <div class="efatura-info-box">
                    <div class="info-title">İŞLEMLER</div>
                    <div class="info-content">
                        <div class="d-flex flex-wrap gap-2">
                            <button class="efatura-btn efatura-btn-success" onclick="faturaKaydet()">
                                <i class="fas fa-save"></i> KAYDET
                            </button>
                            <button class="efatura-btn efatura-btn-primary" onclick="faturaYazdir()">
                                <i class="fas fa-print"></i> YAZDIR
                            </button>
                            <button class="efatura-btn efatura-btn-danger" onclick="faturaIptal()">
                                <i class="fas fa-times"></i> İPTAL
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- İMZA ALANI -->
        <div class="row mt-3" style="border-top: 1px solid #2a2a2a; padding-top: 15px;">
            <div class="col-6">
                <div style="font-size: 11px; color: #8a8a8a;">
                    <strong>SATICI</strong><br>
                    <span style="font-size: 13px; color: #e0e0e0;">MEDA TEKNOLOJİ A.Ş.</span>
                </div>
            </div>
            <div class="col-6 text-end">
                <div style="font-size: 11px; color: #8a8a8a;">
                    <strong>TEDARİKÇİ</strong><br>
                    <span style="font-size: 13px; color: #e0e0e0;" id="tedarikci-unvan">TEDARİKÇİ SEÇİLMEDİ</span>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
$api_base_json = json_encode(BASE_URL);
$extra_js = <<<JS
<script>
var API_BASE = {$api_base_json};

// ========== CARİ DETAY GETİR ==========
document.getElementById('cari-id-select').addEventListener('change', function() {
    var id = this.value;
    var selectEl = this;
    if (id) {
        fetch(API_BASE + '/api/cari_detay.php?id=' + id)
            .then(function(response) { return response.json(); })
            .then(function(data) {
                document.getElementById('cari-vergi').textContent = data.vergi_no || '-';
                document.getElementById('cari-vd').textContent = data.vergi_dairesi || '-';
                document.getElementById('cari-adres').textContent = data.adres || '-';
                document.getElementById('cari-tel').textContent = data.telefon || '-';
                document.getElementById('tedarikci-unvan').textContent = selectEl.options[selectEl.selectedIndex].text.split(' - ')[0];
            });
    }
});

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
                select.innerHTML += '<option value="' + urun.id + '" data-fiyat="' + urun.alis_fiyati + '" data-ad="' + urun.urun_adi + '" data-barkod="' + (urun.barkod || '') + '">' + urun.urun_adi + ' - ' + (urun.barkod || 'BARKOD YOK') + ' (' + urun.alis_fiyati + ' ' + (urun.alis_fiyati_doviz || 'TL') + ')</option>';
            });
        });
}

// ========== ÜRÜN SEÇ ==========
document.getElementById('urun-listesi').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    if (selected.value) {
        document.getElementById('urun-fiyat').value = selected.dataset.fiyat || 0;
    }
});

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
    var toplam = miktar * fiyat * (1 - iskonto / 100);

    var tbody = document.getElementById('fatura-kalem-tbody');
    var bos = document.getElementById('bos-kalem');
    if (bos) bos.remove();

    var row = document.createElement('tr');
    var index = tbody.children.length + 1;
    // NOT: urun_id artık satıra data-urun-id olarak yazılıyor - kaydederken
    // isimden eşleştirmeye (kırılgan) gerek kalmıyor.
    row.dataset.urunId = selected.value;
    row.innerHTML =
        '<td>' + index + '</td>' +
        '<td>' + selected.dataset.ad + '</td>' +
        '<td>' + (selected.dataset.barkod || '-') + '</td>' +
        '<td class="text-end">' + miktar.toFixed(2) + '</td>' +
        '<td class="text-end">' + fiyat.toFixed(2) + '</td>' +
        '<td class="text-end">' + iskonto.toFixed(0) + '%</td>' +
        '<td class="text-end">' + toplam.toFixed(2) + '</td>' +
        '<td class="text-center"><button class="efatura-btn efatura-btn-danger efatura-btn-sm" onclick="satirSil(this)"><i class="fas fa-times"></i></button></td>';
    tbody.appendChild(row);
    hesaplaToplam();
}

// ========== SATIR SİL ==========
function satirSil(btn) {
    var row = btn.closest('tr');
    row.remove();
    document.querySelectorAll('#fatura-kalem-tbody tr').forEach(function(tr, i) {
        tr.cells[0].textContent = i + 1;
    });
    hesaplaToplam();
}

// ========== TOPLAM HESAPLA ==========
function hesaplaToplam() {
    var araToplam = 0;
    document.querySelectorAll('#fatura-kalem-tbody tr').forEach(function(row) {
        if (row.id === 'bos-kalem') return;
        araToplam += parseFloat(row.cells[6].textContent) || 0;
    });

    var vergiTutar = araToplam * 0.20;
    var genelToplam = araToplam + vergiTutar;

    document.getElementById('ara-toplam').textContent = araToplam.toFixed(2);
    document.getElementById('vergi-tutari').textContent = vergiTutar.toFixed(2);
    document.getElementById('genel-toplam').textContent = genelToplam.toFixed(2);
}

// ========== FATURA KAYDET ==========
function faturaKaydet() {
    var formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);

    var cariSelect = document.getElementById('cari-id-select');
    if (cariSelect && cariSelect.value) {
        formData.append('cari_id', cariSelect.value);
    } else {
        alert('Lütfen bir cari seçin!');
        return;
    }

    var faturaTuru = document.querySelector('select[name="fatura_turu"]');
    if (faturaTuru) formData.append('fatura_turu', faturaTuru.value);

    var odemeTuru = document.querySelector('select[name="odeme_turu"]');
    if (odemeTuru) formData.append('odeme_turu', odemeTuru.value);

    var paraBirimi = document.querySelector('select[name="para_birimi"]');
    if (paraBirimi) formData.append('para_birimi', paraBirimi.value);

    var aciklama = document.getElementById('fatura-notu');
    if (aciklama) formData.append('aciklama', aciklama.value);

    document.querySelectorAll('#fatura-kalem-tbody tr').forEach(function(row) {
        if (row.id === 'bos-kalem') return;
        formData.append('urun_ids[]', row.dataset.urunId);
        formData.append('miktarlar[]', row.cells[3].textContent.trim());
        formData.append('fiyatlar[]', row.cells[4].textContent.trim());
        formData.append('iskontolar[]', row.cells[5].textContent.trim().replace('%', ''));
    });

    fetch(API_BASE + '/fatura_alis_kaydet.php', { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                alert('Alış faturası başarıyla kaydedildi!\\nFatura No: ' + data.fatura_no);
                window.location.href = API_BASE + '/faturalar.php';
            } else {
                alert('Hata: ' + (data.message || 'Fatura kaydedilemedi!'));
            }
        })
        .catch(function(error) { alert('Hata oluştu: ' + error); });
}

function faturaYazdir() {
    window.print();
}

function faturaIptal() {
    if (confirm('Faturayı iptal etmek istediğinize emin misiniz?')) {
        window.location.href = API_BASE + '/cariler.php';
    }
}

document.getElementById('urun-ara').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') urunAra();
});
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>
