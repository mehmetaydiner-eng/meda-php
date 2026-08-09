<?php
/**
 * public/vadeli_takip.php
 *
 * NOT: Orijinal Flask uygulamasında `/vadeli-takip` route'u vardı ama
 * `render_template('vadeli_takip.html', ...)` çağırdığı şablon dosyası
 * ARŞİVDE HİÇ YOKTU (makbuz_cikti, numara_yonetim ile aynı eksiklik).
 *
 * DAHA ÖNEMLİSİ: `TahsilatPlanı` / `TahsilatTaksit` kayıtlarını OLUŞTURAN
 * hiçbir route/şablon da yoktu - yani bu özellik orijinal uygulamada
 * baştan sona hiç bağlanmamış, tamamen ölü bir özellikti. Bu sayfa route
 * mantığını birebir taşıyor ve gerçek bir şablonla çalışıyor, ama
 * `tahsilat_planlari` / `tahsilat_taksitleri` tablolarına veri ekleyen bir
 * ekran hâlâ yok - bu yüzden sayfa (veritabanına elle veri girilmediği
 * sürece) her zaman boş görünecektir. Bu, orijinal uygulamanın bilinen bir
 * sınırlaması olarak kabul edildi; "Tahsilat Planı Oluştur" gibi tamamen
 * yeni bir özellik icat etmek bu dönüştürme işinin kapsamı dışında.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$bugun = date('Y-m-d');
$yediGunSonra = date('Y-m-d', strtotime('+7 days'));

$stmt = $pdo->prepare(
    "SELECT tt.*, tp.baslik, tp.cari_id, c.unvan AS cari_unvan
     FROM tahsilat_taksitleri tt
     LEFT JOIN tahsilat_planlari tp ON tp.id = tt.plan_id
     LEFT JOIN cariler c ON c.id = tp.cari_id
     WHERE DATE(tt.vade_tarihi) = ? AND tt.durum = 'BEKLIYOR'
     ORDER BY tt.vade_tarihi"
);
$stmt->execute([$bugun]);
$bugun_vade = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT tt.*, tp.baslik, tp.cari_id, c.unvan AS cari_unvan
     FROM tahsilat_taksitleri tt
     LEFT JOIN tahsilat_planlari tp ON tp.id = tt.plan_id
     LEFT JOIN cariler c ON c.id = tp.cari_id
     WHERE DATE(tt.vade_tarihi) < ? AND tt.durum = 'BEKLIYOR'
     ORDER BY tt.vade_tarihi"
);
$stmt->execute([$bugun]);
$gecikmis = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT tt.*, tp.baslik, tp.cari_id, c.unvan AS cari_unvan
     FROM tahsilat_taksitleri tt
     LEFT JOIN tahsilat_planlari tp ON tp.id = tt.plan_id
     LEFT JOIN cariler c ON c.id = tp.cari_id
     WHERE DATE(tt.vade_tarihi) > ? AND DATE(tt.vade_tarihi) <= ? AND tt.durum = 'BEKLIYOR'
     ORDER BY tt.vade_tarihi"
);
$stmt->execute([$bugun, $yediGunSonra]);
$yaklasan = $stmt->fetchAll();

$page_title   = 'VADELİ TAKİP';
$breadcrumb   = 'Tahsilat Vade Takibi';
$current_page = 'vadeli_takip';
require_once __DIR__ . '/../includes/header.php';

/** Bir taksit satırını tablo satırı olarak render eder */
function vadeli_satir_render(array $taksit): string
{
    $cariHtml = !empty($taksit['cari_unvan'])
        ? '<a href="' . BASE_URL . '/cari_detay.php?id=' . (int)$taksit['cari_id'] . '" class="text-decoration-none">' . e($taksit['cari_unvan']) . '</a>'
        : '-';
    $baslik = $taksit['baslik'] ?? '-';
    $tarih = format_tarih($taksit['vade_tarihi']);
    $tutar = number_format((float)$taksit['tutar'], 2, '.', '') . ' ' . e($taksit['para_birimi'] ?: 'TRY');
    return "<tr>
        <td>{$tarih}</td>
        <td>{$cariHtml}</td>
        <td>" . e($baslik) . "</td>
        <td>Taksit #" . (int)$taksit['taksit_no'] . "</td>
        <td class=\"text-end\">{$tutar}</td>
    </tr>";
}
?>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Bu sayfa, taksitli tahsilat planlarının vade takibini gösterir. Şu an sistemde
    <strong>taksitli tahsilat planı oluşturma ekranı bulunmuyor</strong> (orijinal Flask
    uygulamasında da bu özelliğin veri girişi hiç yapılmamıştı) - bu yüzden aşağıdaki
    listeler veritabanına elle veri eklenmediği sürece boş görünecektir.
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card-custom text-center" style="border-color: #5a2a2a;">
            <h5 class="text-muted">GECİKMİŞ TAKSİTLER</h5>
            <h2 class="text-danger"><?= count($gecikmis) ?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom text-center" style="border-color: #5a4a1a;">
            <h5 class="text-muted">BUGÜN VADESİ GELEN</h5>
            <h2 class="text-warning"><?= count($bugun_vade) ?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom text-center">
            <h5 class="text-muted">7 GÜN İÇİNDE YAKLAŞAN</h5>
            <h2 class="text-info"><?= count($yaklasan) ?></h2>
        </div>
    </div>
</div>

<div class="card-custom mb-3">
    <div class="card-header-custom">
        <h5><i class="fas fa-exclamation-circle text-danger"></i> GECİKMİŞ TAKSİTLER</h5>
    </div>
    <div class="table-responsive">
        <table class="table-custom">
            <thead><tr><th>Vade Tarihi</th><th>Cari</th><th>Plan</th><th>Taksit</th><th class="text-end">Tutar</th></tr></thead>
            <tbody>
                <?php if ($gecikmis): ?>
                    <?php foreach ($gecikmis as $t) echo vadeli_satir_render($t); ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">Gecikmiş taksit bulunmuyor.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card-custom mb-3">
    <div class="card-header-custom">
        <h5><i class="fas fa-clock text-warning"></i> BUGÜN VADESİ GELENLER (<?= format_tarih($bugun) ?>)</h5>
    </div>
    <div class="table-responsive">
        <table class="table-custom">
            <thead><tr><th>Vade Tarihi</th><th>Cari</th><th>Plan</th><th>Taksit</th><th class="text-end">Tutar</th></tr></thead>
            <tbody>
                <?php if ($bugun_vade): ?>
                    <?php foreach ($bugun_vade as $t) echo vadeli_satir_render($t); ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">Bugün vadesi gelen taksit bulunmuyor.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card-custom">
    <div class="card-header-custom">
        <h5><i class="fas fa-calendar-alt text-info"></i> ÖNÜMÜZDEKİ 7 GÜN İÇİNDE YAKLAŞANLAR</h5>
    </div>
    <div class="table-responsive">
        <table class="table-custom">
            <thead><tr><th>Vade Tarihi</th><th>Cari</th><th>Plan</th><th>Taksit</th><th class="text-end">Tutar</th></tr></thead>
            <tbody>
                <?php if ($yaklasan): ?>
                    <?php foreach ($yaklasan as $t) echo vadeli_satir_render($t); ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">Yaklaşan taksit bulunmuyor.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
