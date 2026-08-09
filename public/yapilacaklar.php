<?php
/**
 * public/yapilacaklar.php
 * Yapılacaklar (todos) modülünün listeleme ve yönetim ekranı.
 */

require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();

// Bekleyen işleri getir (Öncelik sırasına göre ve sonra teslim tarihine göre sıralı)
$stmt = $pdo->prepare("
    SELECT * FROM todos 
    WHERE user_id = ? AND status = 'Bekliyor' 
    ORDER BY CASE priority WHEN 'Yüksek' THEN 1 WHEN 'Orta' THEN 2 WHEN 'Düşük' THEN 3 ELSE 4 END, due_date ASC, id DESC
");
$stmt->execute([$user['id']]);
$bekleyenler = $stmt->fetchAll();

// Tamamlanan işleri getir (En son tamamlanana göre sıralı)
$stmt = $pdo->prepare("
    SELECT * FROM todos 
    WHERE user_id = ? AND status = 'Tamamlandı' 
    ORDER BY completed_at DESC, id DESC
");
$stmt->execute([$user['id']]);
$tamamlananlar = $stmt->fetchAll();

// İstatistikler
$toplam_bekleyen = count($bekleyenler);
$toplam_tamamlanan = count($tamamlananlar);
$yuksek_oncelikli = 0;
foreach ($bekleyenler as $t) {
    if ($t['priority'] === 'Yüksek') {
        $yuksek_oncelikli++;
    }
}

$page_title   = 'YAPILACAKLAR';
$breadcrumb   = 'İşlerim';
$current_page = 'yapilacaklar';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- İstatistik Kartları -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card-custom text-center" style="cursor:pointer;" onclick="statPopupGoster('bekleyen')" title="Detayları görmek için tıkla">
            <h5 class="text-muted">BEKLEYEN İŞLER</h5>
            <h2 class="text-warning"><?= $toplam_bekleyen ?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom text-center" style="cursor:pointer;" onclick="statPopupGoster('yuksek')" title="Detayları görmek için tıkla">
            <h5 class="text-muted">YÜKSEK ÖNCELİKLİ</h5>
            <h2 class="text-danger"><?= $yuksek_oncelikli ?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom text-center" style="cursor:pointer;" onclick="statPopupGoster('tamamlanan')" title="Detayları görmek için tıkla">
            <h5 class="text-muted">TAMAMLANANLAR</h5>
            <h2 class="text-success"><?= $toplam_tamamlanan ?></h2>
        </div>
    </div>
</div>

<!-- ===== İSTATİSTİK DETAY POPUP ===== -->
<div class="modal fade" id="statDetayModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statDetayBaslik">Detay</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="statDetayIcerik"></div>
            </div>
        </div>
    </div>
</div>

<?php
$yuksek_oncelikliler = array_values(array_filter($bekleyenler, fn($t) => $t['priority'] === 'Yüksek'));
?>
<script>
var STAT_VERILERI = {
    bekleyen: <?= json_encode($bekleyenler, JSON_UNESCAPED_UNICODE) ?>,
    yuksek: <?= json_encode($yuksek_oncelikliler, JSON_UNESCAPED_UNICODE) ?>,
    tamamlanan: <?= json_encode($tamamlananlar, JSON_UNESCAPED_UNICODE) ?>
};

var STAT_BASLIKLAR = {
    bekleyen: 'Bekleyen İşler (' + STAT_VERILERI.bekleyen.length + ')',
    yuksek: 'Yüksek Öncelikli İşler (' + STAT_VERILERI.yuksek.length + ')',
    tamamlanan: 'Tamamlanan İşler (' + STAT_VERILERI.tamamlanan.length + ')'
};

function statPopupGoster(anahtar) {
    var veri = STAT_VERILERI[anahtar];
    document.getElementById('statDetayBaslik').textContent = STAT_BASLIKLAR[anahtar];

    var icerikEl = document.getElementById('statDetayIcerik');
    if (!veri || veri.length === 0) {
        icerikEl.innerHTML = '<p class="text-muted text-center py-3">Bu kategoride kayıt yok.</p>';
    } else {
        var oncelikRenk = { 'Yüksek': 'text-danger', 'Orta': 'text-warning', 'Düşük': 'text-success' };
        icerikEl.innerHTML = '<div class="list-group">' + veri.map(function(t) {
            var altSatir = '';
            if (anahtar === 'tamamlanan') {
                altSatir = t.completed_at ? 'Tamamlandı: ' + t.completed_at.split(' ')[0].split('-').reverse().join('.') : '';
            } else {
                var oncelikSpan = '<span class="' + (oncelikRenk[t.priority] || '') + '">' + (t.priority || '-') + '</span>';
                altSatir = 'Öncelik: ' + oncelikSpan + (t.due_date ? ' • Teslim: ' + t.due_date.split('-').reverse().join('.') : '');
            }
            return '<div class="list-group-item" style="background:transparent;border-color:var(--border-color);color:var(--text-primary);">' +
                '<div style="font-weight:600;">' + (t.title || '-') + '</div>' +
                '<small class="text-muted">' + altSatir + '</small>' +
                '</div>';
        }).join('') + '</div>';
    }

    var modal = new bootstrap.Modal(document.getElementById('statDetayModal'));
    modal.show();
}
</script>

<div class="row g-4">
    <!-- Sol Sütun: Yeni İş Ekle -->
    <div class="col-lg-4">
        <div class="card-custom">
            <div class="card-header-custom">
                <h5><i class="fas fa-plus-circle"></i> YENİ İŞ EKLE</h5>
            </div>
            <form action="<?= BASE_URL ?>/todo_islem.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3">
                    <label class="form-label">Başlık <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="Yapılacak işin başlığı..." required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Açıklama</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="İşin detayları, açıklaması..."></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Öncelik Derecesi</label>
                    <select name="priority" class="form-select">
                        <option value="Düşük">Düşük</option>
                        <option value="Orta" selected>Orta</option>
                        <option value="Yüksek">Yüksek</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Son Teslim Tarihi</label>
                    <input type="date" name="due_date" class="form-control">
                </div>
                
                <button type="submit" class="btn btn-success-custom w-100 mt-2">
                    <i class="fas fa-save"></i> KAYDET
                </button>
            </form>
        </div>
    </div>

    <!-- Sağ Sütun: İş Listeleri (Sekmeli) -->
    <div class="col-lg-8">
        <div class="card-custom">
            <div class="card-header-custom p-0" style="border-bottom: 1px solid #2a2a2a;">
                <ul class="nav nav-tabs border-0" id="todoTabs" role="tablist" style="margin-bottom: -1px;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active text-uppercase px-4 py-3 border-0" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-todos" type="button" role="tab" aria-controls="active-todos" aria-selected="true" style="background: transparent; color: var(--text-primary); font-weight: 600;">
                            <i class="fas fa-clock text-warning me-2"></i> Bekleyenler (<?= $toplam_bekleyen ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-uppercase px-4 py-3 border-0" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed-todos" type="button" role="tab" aria-controls="completed-todos" aria-selected="false" style="background: transparent; color: var(--text-muted); font-weight: 600;">
                            <i class="fas fa-check-circle text-success me-2"></i> Tamamlananlar (<?= $toplam_tamamlanan ?>)
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content mt-3" id="todoTabsContent">
                <!-- BEKLEYENLER SEKMESİ -->
                <div class="tab-pane fade show active" id="active-todos" role="tabpanel" aria-labelledby="active-tab">
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Yapılacak İş</th>
                                    <th style="width: 100px;">Öncelik</th>
                                    <th style="width: 130px;">Son Tarih</th>
                                    <th style="width: 120px;" class="text-end">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($bekleyenler): ?>
                                    <?php foreach ($bekleyenler as $index => $todo): ?>
                                        <?php
                                        // Öncelik sınıfları
                                        $priorityClass = 'bg-secondary';
                                        if ($todo['priority'] === 'Yüksek') $priorityClass = 'bg-danger';
                                        elseif ($todo['priority'] === 'Orta') $priorityClass = 'bg-warning text-dark';
                                        elseif ($todo['priority'] === 'Düşük') $priorityClass = 'bg-info text-dark';

                                        // Tarih aşım kontrolü
                                        $dueText = '-';
                                        $dateClass = '';
                                        if ($todo['due_date']) {
                                            $dueText = format_tarih($todo['due_date']);
                                            $dueDate = new DateTime($todo['due_date']);
                                            $today = new DateTime('today');
                                            if ($dueDate < $today) {
                                                $dateClass = 'text-danger fw-bold';
                                                $dueText .= ' (Gecikti)';
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <strong class="text-white"><?= e($todo['title']) ?></strong>
                                                <?php if (!empty($todo['description'])): ?>
                                                    <div class="text-muted small mt-1"><?= nl2br(e($todo['description'])) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $priorityClass ?> px-2 py-1" style="font-size: 11px;"><?= e($todo['priority']) ?></span>
                                            </td>
                                            <td>
                                                <span class="<?= $dateClass ?>"><?= $dueText ?></span>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= BASE_URL ?>/todo_islem.php?action=complete&id=<?= $todo['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" class="btn btn-outline-success" title="Tamamla">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <a href="<?= BASE_URL ?>/todo_islem.php?action=delete&id=<?= $todo['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" class="btn btn-outline-danger" title="Sil" onclick="return confirm('Bu yapılacak işi silmek istediğinize emin misiniz?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fas fa-tasks fa-3x d-block mb-3 text-secondary"></i>
                                            Bekleyen yapılacak bir işiniz bulunmuyor.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAMAMLANANLAR SEKMESİ -->
                <div class="tab-pane fade" id="completed-todos" role="tabpanel" aria-labelledby="completed-tab">
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Yapılan İş</th>
                                    <th style="width: 150px;">Tamamlanma Tarihi</th>
                                    <th style="width: 120px;" class="text-end">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($tamamlananlar): ?>
                                    <?php foreach ($tamamlananlar as $index => $todo): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <span class="text-decoration-line-through text-muted"><?= e($todo['title']) ?></span>
                                                <?php if (!empty($todo['description'])): ?>
                                                    <div class="text-muted small mt-1 text-decoration-line-through"><?= nl2br(e($todo['description'])) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?= format_tarih($todo['completed_at'], 'd.m.Y H:i') ?></span>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= BASE_URL ?>/todo_islem.php?action=reopen&id=<?= $todo['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" class="btn btn-outline-warning" title="Geri Al">
                                                        <i class="fas fa-undo"></i>
                                                    </a>
                                                    <a href="<?= BASE_URL ?>/todo_islem.php?action=delete&id=<?= $todo['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" class="btn btn-outline-danger" title="Sil" onclick="return confirm('Bu tamamlanmış işi tamamen silmek istiyor musunuz?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="fas fa-check-double fa-3x d-block mb-3 text-secondary"></i>
                                            Henüz tamamlanmış bir iş bulunmuyor.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = <<<'JS'
<script>
// Aktif sekme tıklandığında rengini güncelle
var triggerTabList = [].slice.call(document.querySelectorAll('#todoTabs button'))
triggerTabList.forEach(function (triggerEl) {
  var tabTrigger = new bootstrap.Tab(triggerEl)
  triggerEl.addEventListener('click', function (event) {
    event.preventDefault()
    
    // Aktif renklendirmeyi ayarla
    document.querySelectorAll('#todoTabs button').forEach(function(btn) {
        btn.style.color = 'var(--text-muted)';
    });
    triggerEl.style.color = 'var(--text-primary)';
    
    tabTrigger.show()
  })
})
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>
