<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pagination.php';
require_login();

$sayfa = get_current_page();
$perPage = 30;

$toplam_cari = (int)$pdo->query('SELECT COUNT(*) FROM cariler')->fetchColumn();
$toplam_sayfa = (int)ceil($toplam_cari / $perPage);

$stmt = $pdo->prepare('SELECT * FROM cariler ORDER BY unvan LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', pagination_offset($sayfa, $perPage), PDO::PARAM_INT);
$stmt->execute();
$cariler = $stmt->fetchAll();

// Kart popup'ları için - sayfalanmamış, tür bazlı listeler (performans için
// her biri en fazla 100 kayıtla sınırlı, bir "hızlı bakış" raporu olduğu için).
$popup_musteriler  = $pdo->query("SELECT id, unvan, telefon FROM cariler WHERE cari_turu = 'MÜŞTERİ' ORDER BY unvan")->fetchAll();
$popup_tedarikciler = $pdo->query("SELECT id, unvan, telefon FROM cariler WHERE cari_turu = 'TEDARİKÇİ' ORDER BY unvan")->fetchAll();
$popup_personeller = $pdo->query("SELECT id, unvan, telefon FROM cariler WHERE cari_turu = 'PERSONEL' ORDER BY unvan")->fetchAll();
$popup_tumcariler  = $pdo->query('SELECT id, unvan, cari_turu FROM cariler ORDER BY unvan')->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM cariler WHERE cari_turu = ?");
$stmt->execute(['MÜŞTERİ']);
$musteri_sayisi = (int)$stmt->fetchColumn();

$stmt->execute(['TEDARİKÇİ']);
$tedarikci_sayisi = (int)$stmt->fetchColumn();

$stmt->execute(['PERSONEL']);
$personel_sayisi = (int)$stmt->fetchColumn();

$page_title   = 'CARİLER';
$breadcrumb   = 'Cari Yönetimi';
$current_page = 'cariler';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-0" style="gap: 0;">
    <!-- ========== SOL MENÜ ========== -->
    <div class="col-md-3" style="padding-right: 15px;">
        <div class="card-custom" style="min-height: 500px; position: sticky; top: 80px;">
            <div class="card-header-custom" style="border-bottom: 1px solid var(--border-color, #2a2a2a); padding-bottom: 10px;">
                <h5 style="font-size: 14px; margin: 0;">
                    <i class="fas fa-users" style="color: var(--text-muted, #6a6a6a);"></i> CARİ MENÜ
                </h5>
            </div>

            <div class="cari-menu">
                <div class="menu-item">
                    <a href="<?= BASE_URL ?>/cari_ekle.php" class="menu-link">
                        <i class="fas fa-plus-circle"></i>
                        <span>YENİ CARİ EKLE</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="<?= BASE_URL ?>/cariler.php" class="menu-link active">
                        <i class="fas fa-list"></i>
                        <span>CARİ LİSTESİ</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="#" class="menu-link dropdown-toggle" data-target="filter-dropdown">
                        <i class="fas fa-filter"></i>
                        <span>FİLTRELEME</span>
                        <i class="fas fa-chevron-right arrow"></i>
                    </a>
                    <ul class="sub-menu" id="filter-dropdown">
                        <li><a href="#" onclick="filterCariler('TÜMÜ')"><i class="fas fa-users"></i> TÜMÜ</a></li>
                        <li><a href="#" onclick="filterCariler('MÜŞTERİ')"><i class="fas fa-user-check"></i> MÜŞTERİ</a></li>
                        <li><a href="#" onclick="filterCariler('TEDARİKÇİ')"><i class="fas fa-building"></i> TEDARİKÇİ</a></li>
                        <li><a href="#" onclick="filterCariler('PERSONEL')"><i class="fas fa-id-badge"></i> PERSONEL</a></li>
                    </ul>
                </div>
                <div class="menu-item">
                    <a href="<?= BASE_URL ?>/faturalar.php" class="menu-link">
                        <i class="fas fa-file-invoice"></i>
                        <span>FATURALAR</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="<?= BASE_URL ?>/makbuzlar.php" class="menu-link">
                        <i class="fas fa-receipt"></i>
                        <span>MAKBUZLAR</span>
                    </a>
                <div class="menu-item">
    <a href="<?= BASE_URL ?>/siparisler.php" class="menu-link">
        <i class="fas fa-shopping-cart"></i>
        <span>SİPARİŞLER</span>
    </a>
</div>
                </div>
                <div class="menu-item">
                    <a href="<?= BASE_URL ?>/teklifler.php" class="menu-link">
                        <i class="fas fa-file-signature"></i>
                        <span>TEKLİFLER</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="<?= BASE_URL ?>/evraklar.php" class="menu-link">
                        <i class="fas fa-folder-open"></i>
                        <span>EVRAKLAR</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="<?= BASE_URL ?>/vadeli_takip.php" class="menu-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>VADELİ TAKİP</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="<?= BASE_URL ?>/primler.php" class="menu-link">
                        <i class="fas fa-hand-holding-usd"></i>
                        <span>PRİMLER</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== SAĞ TARAF - İÇERİK ========== -->
    <div class="col-md-9">
        <!-- İstatistik Kartları -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card-custom text-center" style="cursor:pointer;" onclick="cariStatPopup('tumcariler')" title="Detayları görmek için tıkla">
                    <h5 class="text-muted">TOPLAM CARİ</h5>
                    <h2 class="text-white"><?= (int)$toplam_cari ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-custom text-center" style="cursor:pointer; border-color: var(--badge-success-text, #4ad46a);" onclick="cariStatPopup('musteriler')" title="Detayları görmek için tıkla">
                    <h5 class="text-muted">MÜŞTERİLER</h5>
                    <h2 style="color: var(--badge-success-text, #4ad46a);"><?= (int)$musteri_sayisi ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-custom text-center" style="cursor:pointer; border-color: var(--badge-info-text, #4ac8d4);" onclick="cariStatPopup('tedarikciler')" title="Detayları görmek için tıkla">
                    <h5 class="text-muted">TEDARİKÇİLER</h5>
                    <h2 style="color: var(--badge-info-text, #4ac8d4);"><?= (int)$tedarikci_sayisi ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-custom text-center" style="cursor:pointer; border-color: var(--badge-warning-text, #d4a84a);" onclick="cariStatPopup('personeller')" title="Detayları görmek için tıkla">
                    <h5 class="text-muted">PERSONEL</h5>
                    <h2 style="color: var(--badge-warning-text, #d4a84a);"><?= (int)$personel_sayisi ?></h2>
                </div>
            </div>
        </div>

        <!-- ===== İSTATİSTİK DETAY POPUP ===== -->
        <div class="modal fade" id="cariStatModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cariStatBaslik">Detay</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="cariStatIcerik"></div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $cariTurRozet = ['MÜŞTERİ' => 'badge-musteri', 'TEDARİKÇİ' => 'badge-tedarikci', 'PERSONEL' => 'badge-personel'];
        ?>
        <script>
        var CARI_STAT_VERILERI = {
            tumcariler: <?= json_encode($popup_tumcariler, JSON_UNESCAPED_UNICODE) ?>,
            musteriler: <?= json_encode($popup_musteriler, JSON_UNESCAPED_UNICODE) ?>,
            tedarikciler: <?= json_encode($popup_tedarikciler, JSON_UNESCAPED_UNICODE) ?>,
            personeller: <?= json_encode($popup_personeller, JSON_UNESCAPED_UNICODE) ?>
        };
        var CARI_STAT_BASLIKLAR = {
            tumcariler: 'Tüm Cariler (<?= (int)$toplam_cari ?>)',
            musteriler: 'Müşteriler (<?= (int)$musteri_sayisi ?>)',
            tedarikciler: 'Tedarikçiler (<?= (int)$tedarikci_sayisi ?>)',
            personeller: 'Personel (<?= (int)$personel_sayisi ?>)'
        };
        var CARI_ROZET_SINIFI = { 'MÜŞTERİ': 'badge-musteri', 'TEDARİKÇİ': 'badge-tedarikci', 'PERSONEL': 'badge-personel' };

        function cariStatPopup(anahtar) {
            var veri = CARI_STAT_VERILERI[anahtar];
            document.getElementById('cariStatBaslik').textContent = CARI_STAT_BASLIKLAR[anahtar];

            var icerikEl = document.getElementById('cariStatIcerik');
            if (!veri || veri.length === 0) {
                icerikEl.innerHTML = '<p class="text-muted text-center py-3">Bu kategoride kayıt yok.</p>';
            } else {
                icerikEl.innerHTML = '<div class="list-group">' + veri.map(function(c) {
                    var rozet = anahtar === 'tumcariler'
                        ? '<span class="' + (CARI_ROZET_SINIFI[c.cari_turu] || 'badge-belirsiz') + '" style="font-size:9px;">' + (c.cari_turu || '-') + '</span>'
                        : (c.telefon ? '<small class="text-muted">' + c.telefon + '</small>' : '');
                    return '<a href="cari_detay.php?id=' + c.id + '" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" style="background:transparent;border-color:var(--border-color);color:var(--text-primary);">' +
                        '<span>' + c.unvan + '</span>' + rozet +
                        '</a>';
                }).join('') + '</div>';
            }

            var modal = new bootstrap.Modal(document.getElementById('cariStatModal'));
            modal.show();
        }
        </script>

        <!-- Cari Listesi -->
        <div class="card-custom">
            <div class="card-header-custom">
                <h5><i class="fas fa-list"></i> CARİ LİSTESİ</h5>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= BASE_URL ?>/cari_ekle.php" class="btn btn-success-custom btn-sm">
                        <i class="fas fa-plus"></i> YENİ CARİ EKLE
                    </a>
                </div>
            </div>

            <!-- Arama -->
            <div class="row g-2 mb-3">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text" style="background: var(--bg-input, #121212); border: 1px solid var(--border-color, #2a2a2a); color: var(--text-muted, #6a6a6a);">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="cariArama" class="form-control" placeholder="ÜNVAN, VERGİ NO VEYA TELEFON İLE ARA...">
                    </div>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary-custom w-100" onclick="cariAra()">
                        <i class="fas fa-search"></i> ARA
                    </button>
                </div>
            </div>

            <!-- Tablo -->
            <div class="table-responsive">
                <table class="table-custom" id="cariTable">
                    <thead>
                        <tr>
                            <th style="width: 30px;">#</th>
                            <th style="width: 30%;">ÜNVAN</th>
                            <th style="width: 10%;">VERGİ NO</th>
                            <th style="width: 10%;">TELEFON</th>
                            <th style="width: 10%;">TÜR</th>
                            <th style="width: 10%;">BAKİYE</th>
                            <th style="width: 20%;">İŞLEMLER</th>
                        </tr>
                    </thead>
                    <tbody id="cariTableBody">
                        <?php if ($cariler): ?>
                            <?php foreach ($cariler as $i => $cari): ?>
                            <tr data-id="<?= (int)$cari['id'] ?>" data-tur="<?= e($cari['cari_turu'] ?: 'MÜŞTERİ') ?>">
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/cari_detay.php?id=<?= (int)$cari['id'] ?>" class="text-decoration-none uzun-isim" title="<?= e($cari['unvan']) ?>">
                                        <strong><?= e($cari['unvan']) ?></strong>
                                    </a>
                                    <?php if (!empty($cari['yetkili'])): ?>
                                        <br><small class="text-muted">YETKİLİ: <?= e($cari['yetkili']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($cari['vergi_no'] ?: '-') ?></td>
                                <td><?= e($cari['telefon'] ?: '-') ?></td>
                                <td>
                                    <?php if ($cari['cari_turu'] === 'MÜŞTERİ'): ?>
                                        <span class="badge-musteri">MÜŞTERİ</span>
                                    <?php elseif ($cari['cari_turu'] === 'TEDARİKÇİ'): ?>
                                        <span class="badge-tedarikci">TEDARİKÇİ</span>
                                    <?php elseif ($cari['cari_turu'] === 'PERSONEL'): ?>
                                        <span class="badge-personel">PERSONEL</span>
                                    <?php else: ?>
                                        <span class="badge-belirsiz"><?= e($cari['cari_turu'] ?: 'MÜŞTERİ') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="<?= $cari['bakiye'] > 0 ? 'text-success' : ($cari['bakiye'] < 0 ? 'text-danger' : '') ?>">
                                    <?= number_format(abs((float)$cari['bakiye']), 2, '.', '') ?> ₺
                                    <?php if ((float)$cari['bakiye'] > 0): ?>
                                        <small>(ALACAK)</small>
                                    <?php elseif ((float)$cari['bakiye'] < 0): ?>
                                        <small>(BORÇ)</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" style="flex-wrap: nowrap;">
                                        <a href="<?= BASE_URL ?>/cari_detay.php?id=<?= (int)$cari['id'] ?>" class="btn btn-outline-info btn-sm" title="DETAY">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/cari_duzenle.php?id=<?= (int)$cari['id'] ?>" class="btn btn-outline-primary btn-sm" title="DÜZENLE">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/cari_sil.php?id=<?= (int)$cari['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" class="btn btn-outline-danger btn-sm" title="SİL"
                                           onclick="return confirm('<?= e($cari['unvan']) ?> SİLMEK İSTEDİĞİNİZE EMİN MİSİNİZ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-users fa-3x d-block mb-3"></i>
                                    HENÜZ CARİ KAYDI BULUNMUYOR.<br>
                                    <a href="<?= BASE_URL ?>/cari_ekle.php" class="btn btn-success-custom btn-sm mt-2">
                                        <i class="fas fa-plus"></i> İLK CARİYİ EKLE
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?= render_pagination_ozet($sayfa, $perPage, $toplam_cari) ?>
            <?= render_pagination($sayfa, $toplam_sayfa, BASE_URL . '/cariler.php') ?>
        </div>
    </div>
</div>

<?php
$extra_js = <<<JS
<script>
// ========== ALT MENÜ AÇ/KAPA ==========
document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        var targetId = this.getAttribute('data-target');
        var subMenu = document.getElementById(targetId);
        var arrow = this.querySelector('.arrow');
        subMenu.classList.toggle('open');
        if (arrow) arrow.classList.toggle('open');
    });
});

// ========== CARİ TÜRÜNE GÖRE FİLTRELE ==========
function filterCariler(tur) {
    var rows = document.querySelectorAll('#cariTableBody tr[data-tur]');
    rows.forEach(function(row) {
        if (tur === 'TÜMÜ' || row.getAttribute('data-tur') === tur) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// ========== CARİ ARA (AJAX) ==========
function cariAra() {
    var q = document.getElementById('cariArama').value;

    var paginationNav = document.querySelector('nav[aria-label="Sayfalama"]');
    if (paginationNav) {
        paginationNav.style.display = q ? 'none' : '';
    }

    fetch('api/cari_ara.php?q=' + encodeURIComponent(q))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var tbody = document.getElementById('cariTableBody');
            if (!data.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Sonuç bulunamadı.</td></tr>';
                return;
            }
            var html = '';
            data.forEach(function(c, i) {
                var badgeClass = c.cari_turu === 'MÜŞTERİ' ? 'badge-musteri' : (c.cari_turu === 'TEDARİKÇİ' ? 'badge-tedarikci' : (c.cari_turu === 'PERSONEL' ? 'badge-personel' : 'badge-belirsiz'));
                var bakiyeClass = c.bakiye > 0 ? 'text-success' : (c.bakiye < 0 ? 'text-danger' : '');
                var bakiyeEtiket = c.bakiye > 0 ? ' <small>(ALACAK)</small>' : (c.bakiye < 0 ? ' <small>(BORÇ)</small>' : '');
                html += '<tr data-id="' + c.id + '" data-tur="' + (c.cari_turu || 'MÜŞTERİ') + '">' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td><a href="cari_detay.php?id=' + c.id + '" class="text-decoration-none"><strong>' + c.unvan + '</strong></a></td>' +
                    '<td>' + (c.vergi_no || '-') + '</td>' +
                    '<td>' + (c.telefon || '-') + '</td>' +
                    '<td><span class="' + badgeClass + '">' + (c.cari_turu || 'MÜŞTERİ') + '</span></td>' +
                    '<td class="' + bakiyeClass + '">' + Number(Math.abs(c.bakiye || 0)).toFixed(2) + ' ₺' + bakiyeEtiket + '</td>' +
                    '<td>' +
                        '<div class="btn-group btn-group-sm" style="flex-wrap: nowrap;">' +
                            '<a href="cari_detay.php?id=' + c.id + '" class="btn btn-outline-info btn-sm" title="DETAY"><i class="fas fa-eye"></i></a>' +
                            '<a href="cari_duzenle.php?id=' + c.id + '" class="btn btn-outline-primary btn-sm" title="DÜZENLE"><i class="fas fa-edit"></i></a>' +
                            '<a href="cari_sil.php?id=' + c.id + '&csrf_token=' + encodeURIComponent(CSRF_TOKEN) + '" class="btn btn-outline-danger btn-sm" title="SİL" onclick="return confirm(\\'' + c.unvan + ' SİLMEK İSTEDİĞİNİZE EMİN MİSİNİZ?\\')"><i class="fas fa-trash"></i></a>' +
                        '</div>' +
                    '</td>' +
                '</tr>';
            });
            tbody.innerHTML = html;
        });
}

document.getElementById('cariArama').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); cariAra(); }
});
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>
