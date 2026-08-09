<?php
/**
 * public/primler.php
 * Tüm prim (SATIŞ_PRİMİ türündeki komisyon_hareketleri) kayıtlarının
 * listelendiği ve ödenebildiği sayfa - Toplu ödeme desteği ile.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pagination.php';
require_login();

$sayfa = get_current_page();
$perPage = 30;

$toplam_prim = (int)$pdo->query("SELECT COUNT(*) FROM komisyon_hareketleri WHERE komisyon_turu = 'SATIŞ_PRİMİ'")->fetchColumn();
$toplam_sayfa = (int)ceil($toplam_prim / $perPage);

$bekleyen_tutar = (float)($pdo->query("SELECT COALESCE(SUM(tutar),0) FROM komisyon_hareketleri WHERE komisyon_turu = 'SATIŞ_PRİMİ' AND odeme_durumu = 'BEKLEMEDE'")->fetchColumn() ?: 0);
$bekleyen_sayi = (int)$pdo->query("SELECT COUNT(*) FROM komisyon_hareketleri WHERE komisyon_turu = 'SATIŞ_PRİMİ' AND odeme_durumu = 'BEKLEMEDE'")->fetchColumn();
$odenen_tutar = (float)($pdo->query("SELECT COALESCE(SUM(tutar),0) FROM komisyon_hareketleri WHERE komisyon_turu = 'SATIŞ_PRİMİ' AND odeme_durumu = 'ÖDENDİ'")->fetchColumn() ?: 0);

$stmt = $pdo->prepare(
    "SELECT k.*, c.unvan AS kisi_adi
     FROM komisyon_hareketleri k
     LEFT JOIN cariler c ON c.id = k.cari_id
     WHERE k.komisyon_turu = 'SATIŞ_PRİMİ'
     ORDER BY k.created_at DESC, k.id DESC
     LIMIT :limit OFFSET :offset"
);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', pagination_offset($sayfa, $perPage), PDO::PARAM_INT);
$stmt->execute();
$primler = $stmt->fetchAll();

$hesaplar = $pdo->query('SELECT * FROM hesaplar WHERE is_active = 1 ORDER BY hesap_adi')->fetchAll();

$page_title   = 'PRİMLER';
$breadcrumb   = 'Prim Yönetimi';
$current_page = 'primler';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card-custom text-center" style="border-color: var(--badge-warning-text, #d4a84a);">
            <h5 class="text-muted">BEKLEYEN PRİM</h5>
            <h2 style="color: var(--badge-warning-text, #d4a84a);"><?= number_format($bekleyen_tutar, 2, '.', '') ?> ₺</h2>
            <small class="text-muted"><?= $bekleyen_sayi ?> kayıt</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom text-center" style="border-color: var(--badge-success-text, #4ad46a);">
            <h5 class="text-muted">ÖDENEN PRİM</h5>
            <h2 style="color: var(--badge-success-text, #4ad46a);"><?= number_format($odenen_tutar, 2, '.', '') ?> ₺</h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom text-center">
            <h5 class="text-muted">TOPLAM KAYIT</h5>
            <h2 class="text-white"><?= $toplam_prim ?></h2>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Prim kayıtları, Hızlı İşlem / Fatura Oluştur / Makbuz Oluştur ekranlarında bir satış
    tamamlandığında çıkan "Prim İşlemi" penceresinden veya manuel olarak
    <a href="<?= BASE_URL ?>/prim_ekle_form.php">Yeni Prim Ekle</a> sayfasından oluşturulabilir.
    Yeni bir prim kaydı önce <strong>BEKLEMEDE</strong> durumunda oluşur - hiçbir kasa bakiyesi
    etkilenmez. "ÖDE" butonuna basıp bir kasa seçtiğinde, o kasadan gerçekten para çıkar ve
    bir Hesap Hareketi oluşur.
</div>

<div class="card-custom">
    <div class="card-header-custom">
        <h5><i class="fas fa-hand-holding-usd"></i> PRİM LİSTESİ</h5>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>/prim_ekle_form.php" class="btn btn-success-custom btn-sm">
                <i class="fas fa-plus"></i> YENİ PRİM EKLE
            </a>
            <button type="button" class="btn btn-primary-custom btn-sm" onclick="topluOde()">
                <i class="fas fa-money-bill-wave"></i> SEÇİLİLERİ ÖDE
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="tumunuSec()">TÜMÜNÜ SEÇ</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="bekleyenleriSec()">BEKLEYENLERİ SEÇ</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table-custom" id="primTablo">
            <thead>
                <tr>
                    <th style="width:30px;"><input type="checkbox" id="selectAll" onchange="toggleAll(this)"></th>
                    <th>Tarih</th>
                    <th>Kişi</th>
                    <th class="text-end">Matrah</th>
                    <th class="text-end">Oran</th>
                    <th class="text-end">Tutar</th>
                    <th>Referans</th>
                    <th>Açıklama</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($primler): ?>
                    <?php foreach ($primler as $prim): ?>
                    <tr data-id="<?= (int)$prim['id'] ?>" data-durum="<?= e($prim['odeme_durumu']) ?>">
                        <td>
                            <?php if ($prim['odeme_durumu'] !== 'ÖDENDİ'): ?>
                                <input type="checkbox" class="prim-check" value="<?= (int)$prim['id'] ?>">
                            <?php else: ?>
                                <span class="text-muted" style="font-size:10px;">ÖDENDİ</span>
                            <?php endif; ?>
                        </td>
                        <td><?= format_tarih($prim['tarih'], 'd.m.Y H:i') ?></td>
                        <td><strong><?php if ($prim['kisi_adi']): ?><a href="<?= BASE_URL ?>/cari_detay.php?id=<?= (int)$prim['cari_id'] ?>" class="text-decoration-none"><?= e($prim['kisi_adi']) ?></a><?php else: ?>-<?php endif; ?></strong></td>
                        <td class="text-end"><?= number_format((float)$prim['matrah'], 2, '.', '') ?> ₺</td>
                        <td class="text-end"><?= $prim['oran'] !== null ? number_format((float)$prim['oran'], 1, '.', '') . '%' : '-' ?></td>
                        <td class="text-end"><strong><?= number_format((float)$prim['tutar'], 2, '.', '') ?> ₺</strong></td>
                        <td><?= e($prim['referans_no'] ?: '-') ?></td>
                        <td><?= e($prim['aciklama'] ?: '-') ?></td>
                        <td>
                            <?php if ($prim['odeme_durumu'] === 'ÖDENDİ'): ?>
                                <span class="badge-status bg-success">ÖDENDİ</span>
                            <?php else: ?>
                                <span class="badge-status bg-warning">BEKLEMEDE</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($prim['odeme_durumu'] !== 'ÖDENDİ'): ?>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="primOdeAc(<?= (int)$prim['id'] ?>, '<?= e(addslashes($prim['kisi_adi'] ?: '')) ?>', <?= (float)$prim['tutar'] ?>)">
                                    <i class="fas fa-money-bill-wave"></i> ÖDE
                                </button>
                            <?php else: ?>
                                <small class="text-muted"><?= $prim['odeme_tarihi'] ? format_tarih($prim['odeme_tarihi'], 'd.m.Y') : '-' ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="fas fa-hand-holding-usd fa-3x d-block mb-3" style="color:#3a3a3a;"></i>
                            Henüz hiç prim kaydı yok.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?= render_pagination_ozet($sayfa, $perPage, $toplam_prim) ?>
        <?= render_pagination($sayfa, $toplam_sayfa, BASE_URL . '/primler.php') ?>
    </div>
</div>

<!-- ===== TOPLU ÖDEME MODAL ===== -->
<div class="modal fade" id="topluOdeModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-money-bill-wave"></i> TOPLU PRİM ÖDEMESİ</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p style="font-size: 13px;">
                    <strong id="topluOdeAdet"></strong> prim seçildi. Toplam tutar:
                    <strong id="topluOdeTutar"></strong>
                </p>
                <label class="form-label">Hangi Kasadan Ödenecek? <span class="text-danger">*</span></label>
                <select id="topluOdeHesap" class="form-select">
                    <option value="">-- Seçin --</option>
                    <?php foreach ($hesaplar as $h): ?>
                        <option value="<?= (int)$h['id'] ?>"><?= e($h['hesap_adi']) ?> (<?= number_format((float)$h['bakiye'], 2, '.', '') ?> ₺)</option>
                    <?php endforeach; ?>
                </select>
                <?php if (!$hesaplar): ?>
                    <small class="text-warning">
                        Aktif bir hesap/kasa yok. <a href="<?= BASE_URL ?>/hesap_ekle.php" target="_blank">Buradan ekleyebilirsiniz</a>.
                    </small>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İPTAL</button>
                <button type="button" class="btn btn-success btn-sm" onclick="topluOdeGonder()">
                    <i class="fas fa-check"></i> ÖDEMEYİ ONAYLA
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== TEK ÖDEME MODAL ===== -->
<div class="modal fade" id="primOdeModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-money-bill-wave"></i> PRİM ÖDE</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="primOdePrimId" value="">
                <p style="font-size: 13px;">
                    <strong id="primOdeKisi"></strong> kişisine
                    <strong id="primOdeTutar"></strong> ödenecek.
                </p>
                <label class="form-label">Hangi Kasadan Ödenecek? <span class="text-danger">*</span></label>
                <select id="primOdeHesap" class="form-select">
                    <option value="">-- Seçin --</option>
                    <?php foreach ($hesaplar as $h): ?>
                        <option value="<?= (int)$h['id'] ?>"><?= e($h['hesap_adi']) ?> (<?= number_format((float)$h['bakiye'], 2, '.', '') ?> ₺)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İPTAL</button>
                <button type="button" class="btn btn-success btn-sm" onclick="primOdeGonder()">
                    <i class="fas fa-check"></i> ÖDEMEYİ ONAYLA
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
var seciliPrimler = [];

function toggleAll(master) {
    document.querySelectorAll('.prim-check:not(:disabled)').forEach(chk => chk.checked = master.checked);
}

function tumunuSec() {
    document.querySelectorAll('.prim-check:not(:disabled)').forEach(chk => chk.checked = true);
}

function bekleyenleriSec() {
    document.querySelectorAll('.prim-check:not(:disabled)').forEach(chk => chk.checked = true);
}

function topluOde() {
    var secili = [];
    var toplamTutar = 0;
    document.querySelectorAll('.prim-check:checked').forEach(chk => {
        var row = chk.closest('tr');
        var tutarText = row.querySelector('td:nth-child(6)').textContent.replace(/[^0-9,.]/g, '').replace(',', '.');
        var tutar = parseFloat(tutarText) || 0;
        secili.push(chk.value);
        toplamTutar += tutar;
    });

    if (secili.length === 0) {
        alert('Lütfen en az bir prim seçin!');
        return;
    }

    if (!confirm('Seçili ' + secili.length + ' prim toplam ' + toplamTutar.toFixed(2) + ' ₺ olarak ödenecek. Devam edilsin mi?')) {
        return;
    }

    document.getElementById('topluOdeAdet').textContent = secili.length;
    document.getElementById('topluOdeTutar').textContent = toplamTutar.toFixed(2) + ' ₺';
    document.getElementById('topluOdeHesap').value = '';

    var modal = new bootstrap.Modal(document.getElementById('topluOdeModal'));
    modal.show();

    window.topluPrimIds = secili;
}

function topluOdeGonder() {
    var hesapId = document.getElementById('topluOdeHesap').value;
    if (!hesapId) {
        alert('Lütfen ödemenin yapılacağı kasayı seçin!');
        return;
    }

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = API_BASE + '/prim_toplu_ode.php';
    
    var csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = CSRF_TOKEN;
    form.appendChild(csrfInput);

    var hesapInput = document.createElement('input');
    hesapInput.type = 'hidden';
    hesapInput.name = 'hesap_id';
    hesapInput.value = hesapId;
    form.appendChild(hesapInput);

    window.topluPrimIds.forEach(function(id) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'prim_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}

function primOdeAc(primId, kisiAdi, tutar) {
    document.getElementById('primOdePrimId').value = primId;
    document.getElementById('primOdeKisi').textContent = kisiAdi;
    document.getElementById('primOdeTutar').textContent = tutar.toFixed(2) + ' ₺';
    document.getElementById('primOdeHesap').value = '';
    var modal = new bootstrap.Modal(document.getElementById('primOdeModal'));
    modal.show();
}

function primOdeGonder() {
    var primId = document.getElementById('primOdePrimId').value;
    var hesapId = document.getElementById('primOdeHesap').value;

    if (!hesapId) {
        alert('Lütfen ödemenin yapılacağı kasayı seçin!');
        return;
    }

    window.location.href = API_BASE + '/prim_ode.php?id=' + primId + '&hesap_id=' + hesapId + '&csrf_token=' + encodeURIComponent(CSRF_TOKEN);
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>