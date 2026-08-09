<?php
/**
 * includes/header.php
 * templates/base.html dosyasının <head> + navbar + page-header kısmının PHP karşılığı.
 */

if (!isset($page_title))  $page_title = 'Ana Sayfa';
if (!isset($breadcrumb))  $breadcrumb = 'Dashboard';
if (!isset($current_page)) $current_page = '';
if (!isset($extra_css))  $extra_css = '';

$user = current_user();

// Evraklar alt menüsü için active kontrolü
$is_evrak_page = in_array($current_page, ['faturalar', 'makbuzlar', 'siparisler', 'teklifler', 'evraklar']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(SITE_ADI) ?></title>

    <!-- Google Fonts - Yedek Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Font Awesome 5 (Yedek) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">

    <!-- Arama ve Dropdown Tema Uyumu Stilleri -->
    <style>
        /* ===== Arama Çubuğu (page-header içinde, sola yaslı, 2 kat geniş) ===== */
        .search-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
            flex: 0 1 560px;  /* 2 katına çıkarıldı */
            min-width: 300px;
            max-width: 560px;
            margin-right: auto;  /* sola yaslamak için */
        }
        .search-wrapper .search-input {
            width: 100%;
            padding: 6px 12px 6px 32px;
            border-radius: 20px;
            border: 1px solid var(--border-color, #2a2a2a);
            background: var(--bg-input, #1a1a1a);
            color: var(--text-primary, #e0e0e0);
            font-size: 13px;
            transition: all 0.3s ease;
        }
        .search-wrapper .search-input:focus {
            outline: none;
            border-color: #4ad46a;
            box-shadow: 0 0 0 2px rgba(74, 212, 106, 0.2);
        }
        .search-wrapper .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted, #6a6a6a);
            font-size: 12px;
        }
        .search-wrapper .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--bg-secondary, #1e1e1e);
            border: 1px solid var(--border-color, #2a2a2a);
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
            display: none;
            z-index: 1050;
            margin-top: 4px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
            min-width: 560px;  /* input ile aynı genişlik */
        }
        .search-wrapper .search-results.show {
            display: block;
        }
        .search-wrapper .search-result-item {
            display: flex;
            align-items: center;
            padding: 8px 14px;
            color: var(--text-primary, #e0e0e0);
            text-decoration: none;
            border-bottom: 1px solid var(--border-color, #2a2a2a);
            transition: background 0.15s;
            cursor: pointer;
            gap: 10px;
        }
        .search-wrapper .search-result-item:last-child {
            border-bottom: none;
        }
        .search-wrapper .search-result-item:hover {
            background: var(--bg-hover, #2a2a2a);
        }
        .search-wrapper .search-result-item .result-icon {
            width: 28px;
            text-align: center;
            color: var(--text-muted, #6a6a6a);
        }
        .search-wrapper .search-result-item .result-title {
            flex: 1;
            font-size: 13px;
        }
        .search-wrapper .search-result-item .result-type {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 10px;
            background: var(--bg-input, #2a2a2a);
            color: var(--text-muted, #6a6a6a);
            text-transform: uppercase;
        }
        .search-wrapper .search-loading {
            padding: 10px;
            text-align: center;
            color: var(--text-muted, #6a6a6a);
            font-size: 13px;
        }
        .search-wrapper .search-empty {
            padding: 10px;
            text-align: center;
            color: var(--text-muted, #6a6a6a);
            font-size: 13px;
        }

        /* ===== page-header düzeni (tek satır) ===== */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: nowrap;  /* öğeler tek satırda kalsın */
            gap: 15px;
            padding: 10px 0;
        }
        .page-header h4 {
            flex-shrink: 0;
            margin: 0;
        }
        .page-header .right-group {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-shrink: 0;
        }

        /* ===== Dropdown Menü Tema Uyumu ===== */
        .dropdown-menu {
            background: var(--bg-secondary, #1e1e1e);
            border: 1px solid var(--border-color, #2a2a2a);
            border-radius: 8px;
            padding: 6px 0;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }
        .dropdown-menu .dropdown-item {
            color: var(--text-primary, #e0e0e0);
            padding: 8px 20px;
            transition: background 0.15s;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dropdown-menu .dropdown-item i {
            width: 18px;
            color: var(--text-muted, #6a6a6a);
        }
        .dropdown-menu .dropdown-item:hover {
            background: var(--bg-hover, #2a2a2a);
            color: var(--text-primary, #e0e0e0);
        }
        .dropdown-menu .dropdown-divider {
            border-color: var(--border-color, #2a2a2a);
            margin: 4px 0;
        }
        .dropdown-menu .dropdown-item.active {
            background: var(--bg-hover, #2a2a2a);
            color: #4ad46a;
        }

        /* Mobil uyum */
        @media (max-width: 768px) {
            .page-header {
                flex-wrap: wrap;
            }
            .search-wrapper {
                max-width: 100%;
                flex: 1 1 auto;
                margin-right: 0;
                min-width: 200px;
            }
            .search-wrapper .search-results {
                min-width: 100%;
            }
            .dropdown-menu {
                background: var(--bg-secondary, #1e1e1e);
                border: none;
                box-shadow: none;
            }
        }
    </style>

    <?= $extra_css ?>
</head>
<body>
<script>
    var CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
    var BASE_URL = <?= json_encode(BASE_URL) ?>;
</script>

<!-- ========== ÜST MENÜ ========== -->
<nav class="top-navbar">
    <a href="<?= BASE_URL ?>/index.php" class="navbar-brand">
        <i class="fas fa-cubes"></i>
        <span>MEDA</span>
    </a>

    <button class="hamburger" id="hamburger">
        <i class="fas fa-bars"></i>
    </button>

    <ul class="nav-menu" id="navMenu">
        <li>
            <a href="<?= BASE_URL ?>/yapilacaklar.php" class="<?= $current_page === 'yapilacaklar' ? 'active' : '' ?>">
                <i class="fas fa-tasks"></i> Yapılacaklar
                <?php if ($user): ?>
                    <span class="badge-count" id="todo-badge">0</span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/hizli_islem.php" class="<?= $current_page === 'hizli_islem' ? 'active' : '' ?>">
                <i class="fas fa-bolt"></i> Hızlı İşlem
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/cariler.php" class="<?= in_array($current_page, ['cariler', 'cari_ekle', 'cari_duzenle', 'cari_detay']) ? 'active' : '' ?>">
                <i class="fas fa-users"></i> Cariler
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/stok_listesi.php" class="<?= in_array($current_page, ['stok_listesi', 'stok_ekle', 'stok_duzenle']) ? 'active' : '' ?>">
                <i class="fas fa-boxes"></i> Stok
            </a>
        </li>

        <!-- Evraklar Dropdown -->
        <li class="dropdown">
            <a href="#" class="dropdown-toggle <?= $is_evrak_page ? 'active' : '' ?>" id="evraklarDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-folder-open"></i> Evraklar <span class="caret"></span>
            </a>
            <ul class="dropdown-menu" aria-labelledby="evraklarDropdown">
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/faturalar.php"><i class="fas fa-file-invoice"></i> Faturalar</a></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/makbuzlar.php"><i class="fas fa-receipt"></i> Makbuzlar</a></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/siparisler.php"><i class="fas fa-shopping-cart"></i> Siparişler</a></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/teklifler.php"><i class="fas fa-file-signature"></i> Teklifler</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/evraklar.php"><i class="fas fa-folder-open"></i> Tüm Evraklar</a></li>
            </ul>
        </li>

        <li>
            <a href="<?= BASE_URL ?>/teknik_servis_listesi.php" class="<?= in_array($current_page, ['teknik_servis_listesi', 'teknik_servis_ekle', 'teknik_servis_duzenle']) ? 'active' : '' ?>">
                <i class="fas fa-tools"></i> Teknik Servis
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/hesaplar_listesi.php" class="<?= in_array($current_page, ['hesaplar_listesi', 'hesap_ekle', 'hesap_duzenle', 'hesap_hareketleri']) ? 'active' : '' ?>">
                <i class="fas fa-landmark"></i> Hesaplar
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/kasa_ana.php" class="<?= in_array($current_page, ['kasa_ana', 'kasa_detay', 'kasa_rapor']) ? 'active' : '' ?>">
                <i class="fas fa-cash-register"></i> Kasa
            </a>
        </li>
    </ul>

    <div class="navbar-right">
        <div class="user-info">
            <div class="user-avatar">
                <?= $user ? e(mb_strtoupper(mb_substr($user['username'], 0, 1, 'UTF-8'), 'UTF-8')) : 'M' ?>
            </div>
            <span><?= $user ? e($user['username']) : 'Misafir' ?></span>
        </div>
        <?php if ($user): ?>
            <a href="<?= BASE_URL ?>/numara_yonetim.php" class="logout-btn" title="Numara Yönetimi"><i class="fas fa-hashtag"></i></a>
            <?php
            $__hicAdminYokMu = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() === 0;
            if (is_admin() || $__hicAdminYokMu):
            ?>
            <a href="<?= BASE_URL ?>/kullanici_yonetim.php" class="logout-btn" title="Kullanıcı Yönetimi"><i class="fas fa-user-shield"></i></a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/login.php" class="logout-btn"><i class="fas fa-sign-in-alt"></i></a>
        <?php endif; ?>
    </div>
</nav>

<!-- ========== İÇERİK ========== -->
<div class="main-content">
    <?php foreach (flash_get_all() as $msg): ?>
        <?php
            $icon = 'info-circle';
            if ($msg['category'] === 'success') $icon = 'check-circle';
            elseif ($msg['category'] === 'danger') $icon = 'exclamation-circle';
            elseif ($msg['category'] === 'warning') $icon = 'warning';
        ?>
        <div class="alert alert-<?= e($msg['category']) ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $icon ?> me-2"></i>
            <?= e($msg['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <!-- ===== PAGE HEADER (Arama Çubuğu Sola Yaslı, Geniş) ===== -->
    <div class="page-header">
        <h4><?= e($page_title) ?> <small>/ <?= e($breadcrumb) ?></small></h4>

        <!-- Arama Çubuğu (Sola yaslandı, genişlik 560px) -->
        <div class="search-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" id="globalSearch" placeholder="Cari, ürün, fatura, makbuz, sipariş ara..." autocomplete="off">
            <div class="search-results" id="searchResults"></div>
        </div>

        <!-- Sağ Grup (Tema, Döviz, Tarih) -->
        <div class="right-group">
            <!-- TEMA DEĞİŞTİRİCİ -->
            <div class="theme-switcher">
                <span class="theme-label">TEMA</span>
                <button class="theme-btn theme-btn-dark active" onclick="setTheme('dark')" title="Koyu Tema"></button>
                <button class="theme-btn theme-btn-light" onclick="setTheme('light')" title="Açık Tema"></button>
                <button class="theme-btn theme-btn-gray" onclick="setTheme('gray')" title="Gri Tema"></button>
            </div>

            <!-- Döviz Kurları -->
            <div id="doviz-container">
                <span style="display: flex; align-items: center; gap: 4px;">
                    <span style="color: #4ad46a; font-weight: 700;">$</span>
                    <span id="usd-kur" style="color: var(--text-primary, #e0e0e0); font-weight: 600;">---</span>
                    <span style="color: var(--text-muted, #6a6a6a); font-size: 10px;">TL</span>
                </span>
                <span style="color: var(--border-color, #2a2a2a);">|</span>
                <span style="display: flex; align-items: center; gap: 4px;">
                    <span style="color: #4ac8d4; font-weight: 700;">€</span>
                    <span id="eur-kur" style="color: var(--text-primary, #e0e0e0); font-weight: 600;">---</span>
                    <span style="color: var(--text-muted, #6a6a6a); font-size: 10px;">TL</span>
                </span>
                <span id="kur-guncelleme" style="color: var(--text-muted, #6a6a6a); font-size: 9px; margin-left: 4px;"></span>
            </div>

            <!-- Tarih -->
            <span class="date-text">
                <i class="far fa-calendar-alt"></i>
                <?= e(turkce_tarih_uzun()) ?>
            </span>
        </div>
    </div>

    <!-- İçerik buradan başlar -->
<?php
// Arama JS
?>
<script>
    // ===== GLOBAL ARAMA (Canlı Arama) =====
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('globalSearch');
        const resultsContainer = document.getElementById('searchResults');
        let searchTimeout = null;

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();

            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            if (query.length < 2) {
                resultsContainer.classList.remove('show');
                resultsContainer.innerHTML = '';
                return;
            }

            searchTimeout = setTimeout(function() {
                performSearch(query);
            }, 300);
        });

        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = this.value.trim();
                if (query.length >= 2) {
                    performSearch(query);
                }
            }
        });

        let selectedIndex = -1;
        searchInput.addEventListener('keydown', function(e) {
            const items = resultsContainer.querySelectorAll('.search-result-item');
            if (items.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                highlightItem(items, selectedIndex);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                highlightItem(items, selectedIndex);
            } else if (e.key === 'Enter') {
                if (selectedIndex >= 0 && selectedIndex < items.length) {
                    e.preventDefault();
                    items[selectedIndex].click();
                }
            }
        });

        function highlightItem(items, index) {
            items.forEach((el, i) => {
                el.style.background = i === index ? 'var(--bg-hover, #2a2a2a)' : '';
            });
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-wrapper')) {
                resultsContainer.classList.remove('show');
                resultsContainer.innerHTML = '';
                selectedIndex = -1;
            }
        });

        function performSearch(query) {
            if (query.length < 2) return;

            resultsContainer.innerHTML = '<div class="search-loading"><i class="fas fa-spinner fa-spin"></i> Aranıyor...</div>';
            resultsContainer.classList.add('show');

            fetch(BASE_URL + '/api/genel_ara.php?q=' + encodeURIComponent(query))
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.results && data.results.length > 0) {
                        let html = '';
                        const iconMap = {
                            'cari': 'fa-users',
                            'urun': 'fa-box',
                            'fatura': 'fa-file-invoice',
                            'makbuz': 'fa-receipt',
                            'siparis': 'fa-shopping-cart'
                        };
                        data.results.forEach(item => {
                            const icon = iconMap[item.type] || 'fa-link';
                            html += `
                                <a href="${item.link}" class="search-result-item">
                                    <span class="result-icon"><i class="fas ${icon}"></i></span>
                                    <span class="result-title">${item.title}</span>
                                    <span class="result-type">${item.type_label || item.type}</span>
                                </a>
                            `;
                        });
                        resultsContainer.innerHTML = html;
                    } else {
                        resultsContainer.innerHTML = '<div class="search-empty">Sonuç bulunamadı</div>';
                    }
                })
                .catch(error => {
                    console.error('Arama hatası:', error);
                    resultsContainer.innerHTML = '<div class="search-empty">Arama sırasında hata oluştu. Lütfen tekrar deneyin.</div>';
                });
        }
    });
</script>