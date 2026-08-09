<?php
/**
 * public/kategori_yonetim.php
 *
 * Efe'nin isteği üzerine (19 Temmuz 2026): ürün eklerken kategori artık
 * serbest metin olarak yazılmıyor - burada tanımlanan kategoriler arasından
 * bir dropdown ile seçiliyor. Bu sayfa o listeyi yönetmek için.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(BASE_URL . '/kategori_yonetim.php');

    $kategori_adi = turkce_upper(trim($_POST['kategori_adi'] ?? ''));

    if ($kategori_adi === '') {
        flash_set('Kategori adı boş olamaz!', 'danger');
        header('Location: ' . BASE_URL . '/kategori_yonetim.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM kategoriler WHERE kategori_adi = ?');
    $stmt->execute([$kategori_adi]);
    if ($stmt->fetch()) {
        flash_set($kategori_adi . ' zaten tanımlı!', 'warning');
        header('Location: ' . BASE_URL . '/kategori_yonetim.php');
        exit;
    }

    $pdo->prepare('INSERT INTO kategoriler (kategori_adi, created_at) VALUES (?, datetime(\'now\',\'localtime\'))')
        ->execute([$kategori_adi]);

    flash_set($kategori_adi . ' kategorisi eklendi!', 'success');
    header('Location: ' . BASE_URL . '/kategori_yonetim.php');
    exit;
}

$kategoriler = $pdo->query(
    "SELECT k.*, (SELECT COUNT(*) FROM urunler u WHERE u.kategori = k.kategori_adi) AS urun_sayisi
     FROM kategoriler k ORDER BY k.kategori_adi"
)->fetchAll();

$page_title   = 'ÜRÜN KATEGORİLERİ';
$breadcrumb   = 'Kategori Yönetimi';
$current_page = 'kategori_yonetim';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card-custom mb-3">
            <div class="card-header-custom">
                <h5><i class="fas fa-tags"></i> YENİ KATEGORİ EKLE</h5>
            </div>
            <div class="card-body-custom">
                <form method="POST" class="row g-2 align-items-end">
                    <?= csrf_field() ?>
                    <div class="col-md-9">
                        <label class="form-label">Kategori Adı</label>
                        <input type="text" name="kategori_adi" class="form-control" placeholder="ör. BİLGİSAYAR, KONSOL, AKSESUAR" required autofocus>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success-custom w-100">
                            <i class="fas fa-plus"></i> EKLE
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card-custom">
            <div class="card-header-custom">
                <h5><i class="fas fa-list"></i> TANIMLI KATEGORİLER (<?= count($kategoriler) ?>)</h5>
                <a href="<?= BASE_URL ?>/stok_ekle.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-left"></i> ÜRÜN EKLE'YE DÖN
                </a>
            </div>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Kategori Adı</th>
                            <th class="text-center">Kullanan Ürün Sayısı</th>
                            <th style="width: 80px;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($kategoriler): ?>
                            <?php foreach ($kategoriler as $k): ?>
                            <tr>
                                <td><strong><?= e($k['kategori_adi']) ?></strong></td>
                                <td class="text-center">
                                    <?php if ((int)$k['urun_sayisi'] > 0): ?>
                                        <a href="<?= BASE_URL ?>/stok_listesi.php?kategori=<?= urlencode($k['kategori_adi']) ?>" class="text-decoration-none">
                                            <?= (int)$k['urun_sayisi'] ?> ürün
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">0 ürün</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>/kategori_sil.php?id=<?= (int)$k['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>"
                                       class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('<?= e($k['kategori_adi']) ?> kategorisini silmek istediğinize emin misiniz?<?= (int)$k['urun_sayisi'] > 0 ? ' Bu kategoriyi kullanan ' . (int)$k['urun_sayisi'] . ' ürün var - silinse bile o ürünlerin kategori bilgisi olduğu gibi kalır, sadece yeni ürün eklerken bu seçenek görünmez.' : '' ?>')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    <i class="fas fa-tags fa-3x d-block mb-3" style="color:#3a3a3a;"></i>
                                    Henüz hiç kategori tanımlanmamış. Yukarıdan ilk kategoriyi ekleyin.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
