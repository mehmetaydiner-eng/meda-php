<?php
/**
 * public/prim_ekle_form.php
 * Manuel prim ekleme sayfası
 */

require_once __DIR__ . '/../includes/auth.php';
require_login();

$personeller = $pdo->query("SELECT * FROM cariler WHERE cari_turu = 'PERSONEL' ORDER BY unvan")->fetchAll();

$page_title   = 'PRİM EKLE';
$breadcrumb   = 'Yeni Prim';
$current_page = 'primler';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="card-custom">
        <div class="card-header-custom">
            <h5><i class="fas fa-hand-holding-usd"></i> YENİ PRİM EKLE</h5>
            <a href="<?= BASE_URL ?>/primler.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> GERİ
            </a>
        </div>
        <div class="p-3">
            <form method="POST" action="<?= BASE_URL ?>/api/prim_ekle.php" id="primForm">
                <?= csrf_field() ?>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="row g-3">
                    <!-- Personel -->
                    <div class="col-md-6">
                        <label class="form-label small">PRİM VERİLECEK KİŞİ <span class="text-danger">*</span></label>
                        <select name="cari_id" class="form-select form-select-sm" required>
                            <option value="">-- Seçin --</option>
                            <?php foreach ($personeller as $p): ?>
                                <option value="<?= (int)$p['id'] ?>"><?= e($p['unvan']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!$personeller): ?>
                            <small class="text-warning">
                                Henüz PERSONEL türünde bir cari yok.
                                <a href="<?= BASE_URL ?>/cari_ekle.php" target="_blank">Buradan ekleyebilirsiniz</a>.
                            </small>
                        <?php endif; ?>
                    </div>

                    <!-- Referans No -->
                    <div class="col-md-6">
                        <label class="form-label small">REFERANS NO (Fatura/Makbuz No)</label>
                        <input type="text" name="referans_no" class="form-control form-control-sm" placeholder="FAT-2026-0001">
                    </div>

                    <!-- Hesaplama Yöntemi -->
                    <div class="col-md-12">
                        <label class="form-label small">HESAPLAMA YÖNTEMİ</label>
                        <div class="d-flex gap-3">
                            <label class="d-flex align-items-center gap-1">
                                <input type="radio" name="hesaplama_yontemi" value="sabit" checked onchange="hesaplamaYontemDegisti()"> Sabit Tutar
                            </label>
                            <label class="d-flex align-items-center gap-1">
                                <input type="radio" name="hesaplama_yontemi" value="oran" onchange="hesaplamaYontemDegisti()"> Satıştan Oranla
                            </label>
                        </div>
                    </div>

                    <!-- Matrah (Oranlı için) -->
                    <div class="col-md-4" id="matrahGrubu" style="display: none;">
                        <label class="form-label small">MATRAH (Satış Tutarı) <span class="text-danger">*</span></label>
                        <input type="number" name="matrah" id="matrah" class="form-control form-control-sm" step="0.01" min="0" placeholder="0.00">
                    </div>

                    <!-- Oran (Oranlı için) -->
                    <div class="col-md-4" id="oranGrubu" style="display: none;">
                        <label class="form-label small">ORAN (%) <span class="text-danger">*</span></label>
                        <input type="number" name="oran" id="oran" class="form-control form-control-sm" step="0.1" min="0" max="100" placeholder="10" oninput="hesaplaTutarOran()">
                    </div>

                    <!-- Tutar -->
                    <div class="col-md-4" id="tutarGrubu">
                        <label class="form-label small">PRİM TUTARI (₺) <span class="text-danger">*</span></label>
                        <input type="number" name="tutar" id="tutar" class="form-control form-control-sm" step="0.01" min="0" placeholder="0.00" required>
                    </div>

                    <!-- Açıklama -->
                    <div class="col-md-12">
                        <label class="form-label small">AÇIKLAMA</label>
                        <input type="text" name="aciklama" class="form-control form-control-sm" placeholder="Opsiyonel açıklama...">
                    </div>

                    <!-- Buton -->
                    <div class="col-md-12 mt-3">
                        <button type="submit" class="btn btn-success-custom">
                            <i class="fas fa-save"></i> PRİMİ KAYDET
                        </button>
                        <a href="<?= BASE_URL ?>/primler.php" class="btn btn-outline-secondary">İPTAL</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function hesaplamaYontemDegisti() {
    var yontem = document.querySelector('input[name="hesaplama_yontemi"]:checked').value;
    if (yontem === 'sabit') {
        document.getElementById('matrahGrubu').style.display = 'none';
        document.getElementById('oranGrubu').style.display = 'none';
        document.getElementById('tutarGrubu').style.display = 'block';
        document.getElementById('tutar').required = true;
    } else {
        document.getElementById('matrahGrubu').style.display = 'block';
        document.getElementById('oranGrubu').style.display = 'block';
        document.getElementById('tutarGrubu').style.display = 'block';
        document.getElementById('tutar').required = false;
        document.getElementById('tutar').readOnly = true;
        document.getElementById('tutar').value = '';
        hesaplaTutarOran();
    }
}

function hesaplaTutarOran() {
    var matrah = parseFloat(document.getElementById('matrah').value) || 0;
    var oran = parseFloat(document.getElementById('oran').value) || 0;
    var tutar = matrah * oran / 100;
    document.getElementById('tutar').value = tutar.toFixed(2);
}

document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    var jsonData = {};
    formData.forEach(function(value, key) {
        jsonData[key] = value;
    });
    // CSRF token'ı ekle (zaten formda var)
    // AJAX ile gönder
    fetch(this.action, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(jsonData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Prim başarıyla eklendi!');
            window.location.href = '<?= BASE_URL ?>/primler.php';
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        alert('Hata oluştu: ' + error);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>