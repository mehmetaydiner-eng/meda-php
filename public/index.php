<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title   = 'Ana Sayfa';
$breadcrumb   = 'Dashboard';
$current_page = 'index';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="stat-cards">
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-tasks"></i></div><div class="stat-number" id="yapilacaklar">0</div><div class="stat-label">Yapılacaklar</div></div>
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-bolt"></i></div><div class="stat-number" id="hizli_satis">0</div><div class="stat-label">Hızlı Satış</div></div>
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-number" id="toplam_cari">0</div><div class="stat-label">Cariler</div></div>
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-boxes"></i></div><div class="stat-number" id="toplam_stok">0</div><div class="stat-label">Stok</div></div>
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-tools"></i></div><div class="stat-number" id="toplam_servis">0</div><div class="stat-label">Teknik Servis</div></div>
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-landmark"></i></div><div class="stat-number" id="toplam_hesap">0</div><div class="stat-label">Hesaplar</div></div>
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-cash-register"></i></div><div class="stat-number" id="kasa_bakiye">0</div><div class="stat-label">Kasa</div></div>
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-user-tie"></i></div><div class="stat-number" id="toplam_personel">0</div><div class="stat-label">Personel</div></div>
</div>

<div class="card-custom">
    <div class="card-header-custom">
        <h5><i class="fas fa-bolt"></i> Hızlı İşlemler</h5>
    </div>
    <div class="quick-actions">
        <a href="<?= BASE_URL ?>/cari_ekle.php" class="btn btn-primary-custom"><i class="fas fa-user-plus"></i> Cari Ekle</a>
        <a href="#" class="btn btn-success-custom"><i class="fas fa-arrow-up"></i> Satış Fatura</a>
        <a href="<?= BASE_URL ?>/hizli_islem.php" class="btn btn-outline-primary"><i class="fas fa-bolt"></i> Hızlı Satış</a>
        <a href="<?= BASE_URL ?>/teknik_servis_ekle.php" class="btn btn-outline-info"><i class="fas fa-tools"></i> Yeni Servis</a>
        <a href="<?= BASE_URL ?>/kasa_ana.php" class="btn btn-outline-danger"><i class="fas fa-cash-register"></i> Kasa İşlem</a>
        <a href="<?= BASE_URL ?>/fatura_olustur.php" class="btn btn-outline-success"><i class="fas fa-file-invoice"></i> Yeni Fatura</a>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card-custom">
            <div class="card-header-custom">
                <h5><i class="fas fa-clock"></i> Son Faturalar</h5>
                <a href="#" class="btn btn-primary-custom btn-sm">Tümü</a>
            </div>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead><tr><th>Fatura No</th><th>Cari</th><th>Tarih</th><th class="text-end">Tutar</th></tr></thead>
                    <tbody id="sonFaturalarBody">
                        <tr><td colspan="4" class="text-center text-muted py-3">Henüz fatura yok</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-custom">
            <div class="card-header-custom">
                <h5><i class="fas fa-tools"></i> Son Servisler</h5>
                <a href="#" class="btn btn-primary-custom btn-sm">Tümü</a>
            </div>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead><tr><th>Servis No</th><th>Cari</th><th>Ürün</th><th>Durum</th></tr></thead>
                    <tbody id="sonServislerBody">
                        <tr><td colspan="4" class="text-center text-muted py-3">Henüz servis kaydı yok</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card-custom">
            <div class="card-header-custom">
                <h5><i class="fas fa-calendar-day"></i> Bugünün Özeti</h5>
                <span class="text-muted"><?= date('d.m.Y') ?></span>
            </div>
            <div class="row g-3">
                <div class="col-6 col-md-3"><div class="border rounded p-3 text-center"><h6 class="text-muted">Gelen Fatura</h6><h3 class="text-success" id="ozetGelenFatura">0</h3></div></div>
                <div class="col-6 col-md-3"><div class="border rounded p-3 text-center"><h6 class="text-muted">Giden Fatura</h6><h3 class="text-danger" id="ozetGidenFatura">0</h3></div></div>
                <div class="col-6 col-md-3"><div class="border rounded p-3 text-center"><h6 class="text-muted">Servis Kaydı</h6><h3 class="text-warning" id="ozetServisKaydi">0</h3></div></div>
                <div class="col-6 col-md-3"><div class="border rounded p-3 text-center"><h6 class="text-muted">Kasa İşlem</h6><h3 class="text-primary" id="ozetKasaIslem">0</h3></div></div>
            </div>
        </div>
    </div>
</div>

<?php
$dashboard_api_url = BASE_URL . '/api/dashboard_data.php';
$base_url_js = json_encode(BASE_URL);
$extra_js = <<<JS
<script>
var BASE_URL_JS = {$base_url_js};
fetch('{$dashboard_api_url}')
    .then(response => response.json())
    .then(data => {
        document.getElementById('yapilacaklar').textContent = data.yapilacaklar || 0;
        document.getElementById('hizli_satis').textContent = data.hizli_satis || 0;
        document.getElementById('toplam_cari').textContent = data.toplam_cari || 0;
        document.getElementById('toplam_stok').textContent = data.toplam_stok || 0;
        document.getElementById('toplam_servis').textContent = data.toplam_servis || 0;
        document.getElementById('toplam_hesap').textContent = data.toplam_hesap || 0;
        document.getElementById('kasa_bakiye').textContent = (data.kasa_bakiye || 0).toFixed(2);
        document.getElementById('toplam_personel').textContent = data.toplam_personel || 0;

        // NOT: "Son Faturalar" / "Son Servisler" / "Bugünün Özeti" daha önce
        // tamamen statik (sabit) HTML idi, hiç gerçek veriye bağlı değildi.
        // Efe'nin bulduğu bu hatadan sonra artık aşağıdaki gibi dolduruluyor.
        var faturaTbody = document.getElementById('sonFaturalarBody');
        if (data.son_faturalar && data.son_faturalar.length > 0) {
            faturaTbody.innerHTML = data.son_faturalar.map(function(f) {
                var tarih = f.fatura_tarihi ? f.fatura_tarihi.split(' ')[0].split('-').reverse().join('.') : '-';
                var tutar = parseFloat(f.genel_toplam || 0).toFixed(2);
                return '<tr>' +
                    '<td><a href="' + BASE_URL_JS + '/fatura_olustur.php?id=' + f.id + '" class="text-decoration-none">' + (f.fatura_no || '-') + '</a></td>' +
                    '<td>' + (f.cari_unvan || '-') + '</td>' +
                    '<td>' + tarih + '</td>' +
                    '<td class="text-end">' + tutar + ' ₺</td>' +
                    '</tr>';
            }).join('');
        }

        var servisTbody = document.getElementById('sonServislerBody');
        if (data.son_servisler && data.son_servisler.length > 0) {
            servisTbody.innerHTML = data.son_servisler.map(function(s) {
                return '<tr>' +
                    '<td><a href="' + BASE_URL_JS + '/teknik_servis_duzenle.php?id=' + s.id + '" class="text-decoration-none">' + (s.servis_no || '-') + '</a></td>' +
                    '<td>' + (s.cari_unvan || '-') + '</td>' +
                    '<td>' + (s.urun_adi || '-') + '</td>' +
                    '<td>' + (s.durum || '-') + '</td>' +
                    '</tr>';
            }).join('');
        }

        if (data.bugun_ozet) {
            document.getElementById('ozetGelenFatura').textContent = data.bugun_ozet.gelen_fatura || 0;
            document.getElementById('ozetGidenFatura').textContent = data.bugun_ozet.giden_fatura || 0;
            document.getElementById('ozetServisKaydi').textContent = data.bugun_ozet.servis_kaydi || 0;
            document.getElementById('ozetKasaIslem').textContent = data.bugun_ozet.kasa_islem || 0;
        }
    });
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>
