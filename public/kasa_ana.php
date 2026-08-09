<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$stmt = $pdo->prepare("SELECT * FROM hesaplar WHERE hesap_turu = ? AND is_active = 1");
$stmt->execute(['KASA']);
$kasa_hesaplari = $stmt->fetchAll();

$toplam_kasa = (float)($pdo->query("SELECT SUM(bakiye) FROM hesaplar WHERE hesap_turu = 'KASA' AND is_active = 1")->fetchColumn() ?: 0);

$kasaIds = array_column($kasa_hesaplari, 'id');
$bugun_gelen = 0.0;
$bugun_giden = 0.0;
$son_hareketler = [];

if ($kasaIds) {
    $placeholders = implode(',', array_fill(0, count($kasaIds), '?'));

    $stmt = $pdo->prepare(
        "SELECT SUM(tutar) FROM hesap_hareketleri
         WHERE hesap_id IN ({$placeholders}) AND islem_turu IN ('GELEN','GİRİŞ')
         AND DATE(hareket_tarihi) = DATE('now','localtime')"
    );
    $stmt->execute($kasaIds);
    $bugun_gelen = (float)($stmt->fetchColumn() ?: 0);

    $stmt = $pdo->prepare(
        "SELECT SUM(tutar) FROM hesap_hareketleri
         WHERE hesap_id IN ({$placeholders}) AND islem_turu IN ('GIDEN','ÇIKIŞ')
         AND DATE(hareket_tarihi) = DATE('now','localtime')"
    );
    $stmt->execute($kasaIds);
    $bugun_giden = (float)($stmt->fetchColumn() ?: 0);

    $stmt = $pdo->prepare(
        "SELECT h.*, hs.hesap_adi, c.unvan AS cari_unvan
         FROM hesap_hareketleri h
         LEFT JOIN hesaplar hs ON hs.id = h.hesap_id
         LEFT JOIN cariler c ON c.id = h.cari_id
         WHERE h.hesap_id IN ({$placeholders})
         ORDER BY h.hareket_tarihi DESC LIMIT 10"
    );
    $stmt->execute($kasaIds);
    $son_hareketler = $stmt->fetchAll();
}

$bugun_net = $bugun_gelen - $bugun_giden;

$cariler = $pdo->query('SELECT * FROM cariler ORDER BY unvan')->fetchAll();

$page_title   = 'KASA YÖNETİMİ';
$breadcrumb   = 'Kasa Ana Sayfa';
$current_page = 'kasa_ana';
$extra_css    = '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/kasa_ana.css">';
require_once __DIR__ . '/../includes/header.php';

$now = new DateTime('now');
?>

<div class="kasa-container">

    <!-- ===== ÖZET KARTLARI ===== -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="kasa-ozet-kart">
                <div class="ozet-label">TOPLAM KASA BAKİYESİ</div>
                <div class="ozet-deger positive"><?= number_format($toplam_kasa, 2, '.', '') ?> ₺</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kasa-ozet-kart">
                <div class="ozet-label">BUGÜN GELEN</div>
                <div class="ozet-deger positive"><?= number_format($bugun_gelen, 2, '.', '') ?> ₺</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kasa-ozet-kart">
                <div class="ozet-label">BUGÜN GİDEN</div>
                <div class="ozet-deger negative"><?= number_format($bugun_giden, 2, '.', '') ?> ₺</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kasa-ozet-kart">
                <div class="ozet-label">BUGÜN NET</div>
                <div class="ozet-deger <?= $bugun_net >= 0 ? 'positive' : 'negative' ?>">
                    <?= number_format($bugun_net, 2, '.', '') ?> ₺
                </div>
            </div>
        </div>
    </div>

    <!-- ===== HIZLI İŞLEM BUTONLARI ===== -->
    <div class="row g-2 mb-4">
        <div class="col-md-3">
            <button type="button" class="kasa-islem-btn success w-100" data-bs-toggle="modal" data-bs-target="#kasaHareketModal" onclick="kasaHareketTuru('GİRİŞ')">
                <i class="fas fa-arrow-down"></i> NAKİT GİRİŞİ
            </button>
        </div>
        <div class="col-md-3">
            <button type="button" class="kasa-islem-btn danger w-100" data-bs-toggle="modal" data-bs-target="#kasaHareketModal" onclick="kasaHareketTuru('ÇIKIŞ')">
                <i class="fas fa-arrow-up"></i> NAKİT ÇIKIŞI
            </button>
        </div>
        <div class="col-md-3">
            <a href="<?= BASE_URL ?>/tahsilat_makbuzu.php" class="kasa-islem-btn warning w-100" style="text-align: center;">
                <i class="fas fa-hand-holding-usd"></i> TAHSİLAT MAKBUZU
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?= BASE_URL ?>/kasa_rapor.php" class="kasa-islem-btn w-100" style="text-align: center;">
                <i class="fas fa-chart-bar"></i> RAPOR
            </a>
        </div>
    </div>

    <!-- ===== KASA HESAPLARI ===== -->
    <div class="card-custom mb-4">
        <div class="card-header-custom">
            <h5><i class="fas fa-cash-register"></i> KASA HESAPLARI</h5>
            <button type="button" class="btn btn-success-custom btn-sm" data-bs-toggle="modal" data-bs-target="#yeniKasaModal">
                <i class="fas fa-plus"></i> YENİ KASA
            </button>
        </div>
        <div class="table-responsive">
            <table class="kasa-hareket-tablosu">
                <thead>
                    <tr>
                        <th>Kasa Kodu</th>
                        <th>Kasa Adı</th>
                        <th>Para Birimi</th>
                        <th class="text-end">Bakiye</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($kasa_hesaplari): ?>
                        <?php foreach ($kasa_hesaplari as $kasa): ?>
                        <tr>
                            <td><code><?= e($kasa['hesap_kodu']) ?></code></td>
                            <td><strong><?= e($kasa['hesap_adi']) ?></strong></td>
                            <td><?= e($kasa['para_birimi']) ?></td>
                            <td class="text-end <?= $kasa['bakiye'] > 0 ? 'positive' : ($kasa['bakiye'] < 0 ? 'negative' : '') ?>">
                                <?= number_format((float)$kasa['bakiye'], 2, '.', '') ?> ₺
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-success" onclick="kasaHareketEkle(<?= (int)$kasa['id'] ?>, 'GİRİŞ')" title="Nakit Giriş">
                                        <i class="fas fa-arrow-down"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" onclick="kasaHareketEkle(<?= (int)$kasa['id'] ?>, 'ÇIKIŞ')" title="Nakit Çıkış">
                                        <i class="fas fa-arrow-up"></i>
                                    </button>
                                    <a href="<?= BASE_URL ?>/hesap_hareketleri.php?id=<?= (int)$kasa['id'] ?>" class="btn btn-outline-info" title="Hareketler">
                                        <i class="fas fa-list"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/hesap_duzenle.php?id=<?= (int)$kasa['id'] ?>" class="btn btn-outline-primary" title="Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-cash-register fa-3x d-block mb-3"></i>
                                Henüz kasa hesabı eklenmemiş.<br>
                                <button type="button" class="btn btn-success-custom btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#yeniKasaModal">
                                    <i class="fas fa-plus"></i> İLK KASAYI EKLE
                                </button>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===== SON 10 HAREKET ===== -->
    <div class="card-custom">
        <div class="card-header-custom">
            <h5><i class="fas fa-clock"></i> SON 10 HAREKET</h5>
            <a href="<?= BASE_URL ?>/kasa_rapor.php" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-chart-bar"></i> TÜMÜNÜ GÖR
            </a>
        </div>
        <div class="table-responsive">
            <table class="kasa-hareket-tablosu">
                <thead>
                    <tr>
                        <th>Kasa</th>
                        <th>Tarih</th>
                        <th>Tür</th>
                        <th>İşlem</th>
                        <th class="text-end">Tutar</th>
                        <th>Cari</th>
                        <th>Açıklama</th>
                        <th>Referans</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($son_hareketler): ?>
                        <?php foreach ($son_hareketler as $hareket): ?>
                        <?php
                            $turBadge = match($hareket['hareket_turu']) {
                                'TAHSİLAT' => 'tahsilat',
                                'ÖDEME'    => 'odeme',
                                'KOMİSYON' => 'komisyon',
                                'VIRMAN'   => 'virman',
                                default    => 'giris',
                            };
                            $isGiris = in_array($hareket['islem_turu'], ['GELEN', 'GİRİŞ'], true);
                        ?>
                        <tr>
                            <td><?= e($hareket['hesap_adi'] ?: '-') ?></td>
                            <td><?= $hareket['hareket_tarihi'] ? format_tarih($hareket['hareket_tarihi'], 'd.m.Y H:i') : ($hareket['tarih'] ? format_tarih($hareket['tarih']) : '-') ?></td>
                            <td><span class="badge-tur <?= $turBadge ?>"><?= e($hareket['hareket_turu']) ?></span></td>
                            <td><span class="badge-tur <?= $isGiris ? 'giris' : 'cikis' ?>"><?= e($hareket['islem_turu']) ?></span></td>
                            <td class="text-end <?= $isGiris ? 'positive' : 'negative' ?>"><?= number_format((float)$hareket['tutar'], 2, '.', '') ?> ₺</td>
                            <td>
                                <?php if ($hareket['cari_unvan']): ?>
                                    <a href="<?= BASE_URL ?>/cari_detay.php?id=<?= (int)$hareket['cari_id'] ?>" class="text-decoration-none"><?= e($hareket['cari_unvan']) ?></a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($hareket['aciklama'] ?: '-') ?></td>
                            <td><?= e($hareket['referans_no'] ?: '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-exchange-alt fa-3x d-block mb-3"></i>
                                Henüz hareket yok
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== YENİ KASA EKLE MODAL ===== -->
<div class="modal fade" id="yeniKasaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> YENİ KASA HESABI</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="yeniKasaForm" action="<?= BASE_URL ?>/hesap_ekle.php" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="hesap_turu" value="KASA">

                    <div class="mb-3">
                        <label class="form-label">KASA ADI <span class="text-danger">*</span></label>
                        <input type="text" name="hesap_adi" class="form-control" placeholder="ANA KASA" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Para Birimi</label>
                        <select name="para_birimi" class="form-select">
                            <option value="TRY">TL</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Açılış Bakiyesi</label>
                        <input type="number" name="bakiye" class="form-control" step="0.01" value="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea name="aciklama" class="form-control" rows="2" placeholder="Kasa açıklaması..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İPTAL</button>
                <button type="button" class="btn btn-success" onclick="kasaKaydet()">
                    <i class="fas fa-save"></i> KAYDET
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== KASA HAREKET EKLE MODAL ===== -->
<div class="modal fade" id="kasaHareketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exchange-alt"></i> <span id="kasaHareketBaslik">NAKİT GİRİŞİ</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="kasaHareketForm">
                    <input type="hidden" name="kasa_id" id="kasa_hareket_kasa_id">
                    <input type="hidden" name="islem_turu" id="kasa_hareket_islem_turu">

                    <div class="row g-3">
                        <?php if (count($kasa_hesaplari) > 1): ?>
                        <!-- NOT: Orijinal Flask şablonunda görünür bir "hangi kasa" seçici
                             yoktu (sadece hidden input) - üstteki genel butonlarla açıldığında
                             kasa seçilmemiş oluyordu. Burada, birden fazla kasa varsa görünür
                             bir seçici eklendi (küçük bir iyileştirme, davranışı bozmuyor). -->
                        <div class="col-md-6">
                            <label class="form-label">KASA <span class="text-danger">*</span></label>
                            <select id="kasa_hareket_kasa_select" class="form-select" onchange="document.getElementById('kasa_hareket_kasa_id').value = this.value;" required>
                                <option value="">Seçin...</option>
                                <?php foreach ($kasa_hesaplari as $kasa): ?>
                                <option value="<?= (int)$kasa['id'] ?>"><?= e($kasa['hesap_adi']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <label class="form-label">HAREKET TÜRÜ <span class="text-danger">*</span></label>
                            <select name="hareket_turu" class="form-select" required>
                                <option value="TAHSİLAT">TAHSİLAT</option>
                                <option value="ÖDEME">ÖDEME</option>
                                <option value="KOMİSYON">KOMİSYON</option>
                                <option value="VIRMAN">VİRMAN</option>
                                <option value="DİĞER">DİĞER</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">ÖDEME TÜRÜ</label>
                            <select name="odeme_turu" class="form-select">
                                <option value="">Seçin...</option>
                                <option value="NAKİT">NAKİT</option>
                                <option value="KREDİ KARTI">KREDİ KARTI</option>
                                <option value="BANKA HAVALESİ">BANKA HAVALESİ</option>
                                <option value="EFT">EFT</option>
                                <option value="ÇEK">ÇEK</option>
                                <option value="SENET">SENET</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">CARİ / MÜŞTERİ</label>
                            <select name="cari_id" class="form-select">
                                <option value="">Seçin...</option>
                                <?php foreach ($cariler as $cari): ?>
                                <option value="<?= (int)$cari['id'] ?>"><?= e($cari['unvan']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">TUTAR <span class="text-danger">*</span></label>
                            <input type="number" name="tutar" class="form-control" step="0.01" placeholder="0.00" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">REFERANS NO</label>
                            <input type="text" name="referans_no" class="form-control" placeholder="FAT-2024-001">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">TARİH</label>
                            <input type="date" name="tarih" class="form-control" value="<?= $now->format('Y-m-d') ?>">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">AÇIKLAMA</label>
                            <textarea name="aciklama" class="form-control" rows="2" placeholder="İşlem açıklaması..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İPTAL</button>
                <button type="button" class="btn btn-success" onclick="kasaHareketKaydet()">
                    <i class="fas fa-save"></i> KAYDET
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$api_base_json = json_encode(BASE_URL);
$tekKasaId = count($kasa_hesaplari) === 1 ? (int)$kasa_hesaplari[0]['id'] : 0;
$extra_js = <<<JS
<script>
var API_BASE = {$api_base_json};
var TEK_KASA_ID = {$tekKasaId};

function kasaKaydet() {
    var form = document.getElementById('yeniKasaForm');
    form.submit();
}

function kasaHareketTuru(tur) {
    document.getElementById('kasa_hareket_islem_turu').value = tur;
    // Tek kasa varsa otomatik seç (birden fazla kasa varsa kullanıcı seçmeli)
    if (TEK_KASA_ID) {
        document.getElementById('kasa_hareket_kasa_id').value = TEK_KASA_ID;
    }
    var baslik = document.getElementById('kasaHareketBaslik');
    if (tur === 'GİRİŞ') {
        baslik.textContent = 'NAKİT GİRİŞİ';
        baslik.style.color = 'var(--badge-success-text, #4ad46a)';
    } else if (tur === 'ÇIKIŞ') {
        baslik.textContent = 'NAKİT ÇIKIŞI';
        baslik.style.color = 'var(--badge-danger-text, #d44a4a)';
    } else {
        baslik.textContent = 'KASA HAREKETİ';
        baslik.style.color = 'var(--text-primary, #e0e0e0)';
    }
}

function kasaHareketEkle(kasaId, tur) {
    document.getElementById('kasa_hareket_kasa_id').value = kasaId;
    document.getElementById('kasa_hareket_islem_turu').value = tur;
    var kasaSelect = document.getElementById('kasa_hareket_kasa_select');
    if (kasaSelect) kasaSelect.value = kasaId;

    var baslik = document.getElementById('kasaHareketBaslik');
    if (tur === 'GİRİŞ') {
        baslik.textContent = 'NAKİT GİRİŞİ';
        baslik.style.color = 'var(--badge-success-text, #4ad46a)';
    } else if (tur === 'ÇIKIŞ') {
        baslik.textContent = 'NAKİT ÇIKIŞI';
        baslik.style.color = 'var(--badge-danger-text, #d44a4a)';
    }

    var modal = new bootstrap.Modal(document.getElementById('kasaHareketModal'));
    modal.show();
}

function kasaHareketKaydet() {
    var form = document.getElementById('kasaHareketForm');
    var formData = new FormData(form);
    formData.append('csrf_token', CSRF_TOKEN);

    if (!formData.get('kasa_id')) {
        alert('Lütfen bir kasa seçin!');
        return;
    }

    fetch(API_BASE + '/api/kasa_hareket_ekle.php', { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                alert('Kasa hareketi başarıyla kaydedildi!');
                location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(function(error) { alert('Hata oluştu: ' + error); });
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>
