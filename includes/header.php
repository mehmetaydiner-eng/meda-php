<?php
/**
 * includes/header.php
 * templates/base.html dosyasının <head> + navbar + page-header kısmının PHP karşılığı.
 *
 * Bu dosyayı include etmeden önce her sayfada şu değişkenleri tanımlayın:
 *   $page_title   (string)  -> Flask: {% block page_title %}
 *   $breadcrumb   (string)  -> Flask: {% block breadcrumb %}
 *   $current_page (string)  -> nav menüsünde "active" sınıfını belirlemek için
 *                              (örn: 'index', 'yapilacaklar', 'cariler', 'stok',
 *                               'teknik_servis', 'hesaplar', 'kasa', 'hizli_islem')
 *   $extra_css    (string, opsiyonel) -> Flask: {% block extra_css %}
 *
 * require_once edilmeden önce auth.php'nin de yüklenmiş olması gerekir
 * (current_user() ve flash_get_all() fonksiyonları için).
 */

if (!isset($page_title))  $page_title = 'Ana Sayfa';
if (!isset($breadcrumb))  $breadcrumb = 'Dashboard';
if (!isset($current_page)) $current_page = '';
if (!isset($extra_css))  $extra_css = '';

$user = current_user();
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

    <?= $extra_css ?>
</head>
<body>
<script>
    // Tüm AJAX isteklerinde kullanılmak üzere CSRF token'ı JS tarafına aktarıyoruz.
    // (includes/auth.php -> csrf_token())
    var CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
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

    <!-- ===== PAGE HEADER ===== -->
    <div class="page-header">
        <h4><?= e($page_title) ?> <small>/ <?= e($breadcrumb) ?></small></h4>
        <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
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
