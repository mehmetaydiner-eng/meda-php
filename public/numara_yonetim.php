<?php
/**
 * public/numara_yonetim.php
 *
 * NOT: Orijinal Flask uygulamasında `/numara-yonetim` route'u vardı ve
 * `render_template('numara_yonetim.html', ...)` çağırıyordu ama bu şablon
 * dosyası ARŞİVDE HİÇ YOKTU - makbuz_cikti, fatura_xml ve stok_barkod ile
 * aynı türden bir eksiklik. Burada, zaten kurulu olan includes/numara_manager.php
 * alt yapısı kullanılarak gerçek, çalışan bir yönetim ekranı kuruldu.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/numara_manager.php';
require_login();

$ayarlar = NumaraManager::getAllInfo($pdo);

$page_title   = 'NUMARA YÖNETİMİ';
$breadcrumb   = 'Evrak Numaralandırma';
$current_page = 'numara_yonetim';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card-custom mb-3">
    <div class="card-header-custom">
        <h5><i class="fas fa-hashtag"></i> EVRAK NUMARALANDIRMA AYARLARI</h5>
        <?php if (is_admin()): ?>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="tumunuSifirla()">
            <i class="fas fa-undo"></i> TÜMÜNÜ SIFIRLA
        </button>
        <?php endif; ?>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        Her belge türü için "sıradaki numara" otomatik olarak mevcut kayıtlar arasındaki
        en yüksek numaraya göre hesaplanır. "Numara Ayarla" ile bir sonraki numaranın
        hangi değerden başlayacağını (çakışma yoksa) kontrol edebilirsiniz.
    </div>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Kod</th>
                    <th>Açıklama</th>
                    <th>Yıl</th>
                    <th class="text-end">Mevcut Kayıt</th>
                    <th class="text-end">Mevcut Sayı</th>
                    <th>Sıradaki Numara</th>
                    <th style="width: 220px;">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ayarlar as $ayar): ?>
                <tr>
                    <td><code><?= e($ayar['prefix']) ?></code></td>
                    <td><?= e($ayar['aciklama']) ?></td>
                    <td><?= e($ayar['yil']) ?></td>
                    <td class="text-end"><?= (int)$ayar['toplam_kayit'] ?></td>
                    <td class="text-end"><?= (int)$ayar['mevcut_sayi'] ?></td>
                    <td><strong class="text-success"><?= e($ayar['siradaki_format']) ?></strong></td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" id="sira-<?= e($ayar['prefix']) ?>"
                                   value="<?= (int)$ayar['siradaki'] ?>" min="1" style="max-width: 100px;">
                            <button class="btn btn-primary-custom btn-sm" onclick="numaraAyarla('<?= e($ayar['prefix']) ?>')">
                                <i class="fas fa-save"></i> AYARLA
                            </button>
                        </div>
                        <small class="text-muted d-block">Yıl (<?= e($ayar['yil']) ?>) otomatik eklenir - sadece sıra numarasını yazın (ör. 6)</small>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card-custom">
    <div class="card-header-custom">
        <h5><i class="fas fa-exclamation-triangle text-warning"></i> DİKKAT</h5>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="border rounded p-3">
                <h6><i class="fas fa-save"></i> NUMARA AYARLA</h6>
                <p class="text-muted small mb-0">
                    Girdiğiniz sayının, o belge türü için başka bir kayıtta zaten kullanılmadığını
                    kontrol eder. Kullanılmıyorsa işlemi onaylar - ama gerçek bir "sıra sayacı"
                    tablosu olmadığı için sıradaki numara yine de mevcut kayıtlara göre otomatik
                    hesaplanmaya devam eder. Yani bu, kalıcı bir sayaç ayarından çok bir
                    "çakışma kontrolü" gibi çalışır.
                </p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="border rounded p-3" style="border-color: #5a2a2a !important;">
                <h6 class="text-danger"><i class="fas fa-undo"></i> TÜMÜNÜ SIFIRLA</h6>
                <p class="text-muted small mb-0">
                    <strong>GERİ ALINAMAZ:</strong> Her belge türündeki TÜM mevcut kayıtları
                    (id sırasına göre) 1'den başlayarak yeniden numaralandırır. Bu işlem
                    mevcut fatura/makbuz/teklif/servis numaralarının üzerine yazar. Sadece
                    emin olduğunuzda kullanın.
                    <?php if (!is_admin()): ?>
                        <br><strong class="text-warning">Not:</strong> Bu işlem sadece yönetici (admin) rolündeki kullanıcılar tarafından yapılabilir.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php
// ============================================================
// SİPARİŞ NUMARASI ÜRETME FONKSİYONU (DOĞRU YERDE)
// ============================================================
/**
 * Sipariş numarası üretir (SP-2024-0001 formatında)
 */
if (!function_exists('generate_siparis_no')) {
    function generate_siparis_no($pdo) {
        $stmt = $pdo->query("SELECT siparis_no FROM siparisler ORDER BY id DESC LIMIT 1");
        $last = $stmt->fetchColumn();
        
        if ($last && preg_match('/SP-(\d{4})-(\d{4})/', $last, $matches)) {
            $yil = $matches[1];
            $no = (int)$matches[2] + 1;
            if ($yil != date('Y')) {
                return 'SP-' . date('Y') . '-0001';
            }
            return 'SP-' . $yil . '-' . str_pad($no, 4, '0', STR_PAD_LEFT);
        }
        return 'SP-' . date('Y') . '-0001';
    }
}

$api_base_json = json_encode(BASE_URL);
$extra_js = <<<JS
<script>
var API_BASE = {$api_base_json};

function numaraAyarla(prefix) {
    var input = document.getElementById('sira-' + prefix);
    var yeniNumara = parseInt(input.value, 10);

    if (!yeniNumara || yeniNumara < 1) {
        alert("Numara 1'den küçük olamaz!");
        return;
    }

    fetch(API_BASE + '/api/numara_ayarla.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ prefix: prefix, yeni_numara: yeniNumara, csrf_token: CSRF_TOKEN })
    })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(function(err) { alert('Hata oluştu: ' + err); });
}

function tumunuSifirla() {
    if (!confirm('TÜM belge numaraları yeniden sıralanacak. Bu işlem GERİ ALINAMAZ!\\n\\nDevam etmek istediğinize emin misiniz?')) {
        return;
    }
    if (!confirm('Son kez soruyoruz: Gerçekten devam etmek istiyor musunuz?')) {
        return;
    }

    fetch(API_BASE + '/api/numara_sifirla.php', { method: 'POST', headers: { 'X-CSRF-Token': CSRF_TOKEN } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                alert('Tüm numaralar sıfırlandı!\\n\\n' + (data.results || []).join('\\n'));
                location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(function(err) { alert('Hata oluştu: ' + err); });
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>