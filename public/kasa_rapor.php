<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$stmt = $pdo->prepare("SELECT * FROM hesaplar WHERE hesap_turu = ?");
$stmt->execute(['KASA']);
$kasa_hesaplari = $stmt->fetchAll();
$kasaIds = array_column($kasa_hesaplari, 'id');

$hareketler = [];
$gelen_toplam = 0.0;
$giden_toplam = 0.0;
$net_toplam = 0.0;
$baslangic = '';
$bitis = '';
$secili_kasa = '';
$secili_islem = 'TUMU';
$secili_hareket = 'TUMU';
$grafik_verisi = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $kasaIds) {
    $baslangic = trim($_POST['baslangic'] ?? '');
    $bitis = trim($_POST['bitis'] ?? '');
    $secili_kasa = trim($_POST['kasa_id'] ?? '');
    $secili_islem = trim($_POST['islem_turu'] ?? 'TUMU');
    $secili_hareket = trim($_POST['hareket_turu'] ?? 'TUMU');

    $where = [];
    $params = [];

    if ($secili_kasa !== '') {
        $where[] = 'hesap_id = ?';
        $params[] = $secili_kasa;
    } else {
        $placeholders = implode(',', array_fill(0, count($kasaIds), '?'));
        $where[] = "hesap_id IN ({$placeholders})";
        $params = array_merge($params, $kasaIds);
    }

    if ($baslangic !== '') {
        $where[] = 'DATE(hareket_tarihi) >= ?';
        $params[] = $baslangic;
    }
    if ($bitis !== '') {
        $where[] = 'DATE(hareket_tarihi) <= ?';
        $params[] = $bitis;
    }
    if ($secili_islem !== '' && $secili_islem !== 'TUMU') {
        $where[] = 'islem_turu = ?';
        $params[] = $secili_islem;
    }
    if ($secili_hareket !== '' && $secili_hareket !== 'TUMU') {
        $where[] = 'hareket_turu = ?';
        $params[] = $secili_hareket;
    }

    $whereSql = implode(' AND ', $where);

    $stmt = $pdo->prepare("SELECT h.*, hs.hesap_adi, c.unvan AS cari_unvan FROM hesap_hareketleri h
                            LEFT JOIN hesaplar hs ON hs.id = h.hesap_id
                            LEFT JOIN cariler c ON c.id = h.cari_id
                            WHERE {$whereSql} ORDER BY h.hareket_tarihi");
    $stmt->execute($params);
    $hareketler = $stmt->fetchAll();

    foreach ($hareketler as $h) {
        if (in_array($h['islem_turu'], ['GELEN', 'GİRİŞ'], true)) {
            $gelen_toplam += (float)$h['tutar'];
        } else {
            $giden_toplam += (float)$h['tutar'];
        }
    }
    $net_toplam = $gelen_toplam - $giden_toplam;

    // Günlük grafik verisi
    foreach ($hareketler as $h) {
        $tarihKaynagi = $h['hareket_tarihi'] ?: $h['tarih'];
        if (!$tarihKaynagi) continue;
        $tarihStr = date('d.m.Y', strtotime($tarihKaynagi));
        if (!isset($grafik_verisi[$tarihStr])) {
            $grafik_verisi[$tarihStr] = ['gelen' => 0.0, 'giden' => 0.0];
        }
        if (in_array($h['islem_turu'], ['GELEN', 'GİRİŞ'], true)) {
            $grafik_verisi[$tarihStr]['gelen'] += (float)$h['tutar'];
        } else {
            $grafik_verisi[$tarihStr]['giden'] += (float)$h['tutar'];
        }
    }
    ksort($grafik_verisi);
}

$page_title   = 'KASA RAPORU';
$breadcrumb   = 'Kasa Raporu';
$current_page = 'kasa_ana';
$extra_css    = '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/kasa_rapor.css">';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="rapor-container">

    <!-- ===== FİLTRE FORMU ===== -->
    <div class="filtre-kutusu no-print">
        <form method="POST" id="raporForm">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">BAŞLANGIÇ</label>
                    <input type="date" name="baslangic" class="form-control" value="<?= e($baslangic) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">BİTİŞ</label>
                    <input type="date" name="bitis" class="form-control" value="<?= e($bitis) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">KASA HESABI</label>
                    <select name="kasa_id" class="form-select">
                        <option value="">TÜM KASALAR</option>
                        <?php foreach ($kasa_hesaplari as $kasa): ?>
                        <option value="<?= (int)$kasa['id'] ?>" <?= (string)$secili_kasa === (string)$kasa['id'] ? 'selected' : '' ?>>
                            <?= e($kasa['hesap_adi']) ?> (<?= number_format((float)$kasa['bakiye'], 2, '.', '') ?> ₺)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">İŞLEM TÜRÜ</label>
                    <select name="islem_turu" class="form-select">
                        <option value="TUMU">TÜMÜ</option>
                        <option value="GİRİŞ" <?= $secili_islem === 'GİRİŞ' ? 'selected' : '' ?>>GİRİŞ</option>
                        <option value="ÇIKIŞ" <?= $secili_islem === 'ÇIKIŞ' ? 'selected' : '' ?>>ÇIKIŞ</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">HAREKET TÜRÜ</label>
                    <select name="hareket_turu" class="form-select">
                        <option value="TUMU">TÜMÜ</option>
                        <option value="TAHSİLAT" <?= $secili_hareket === 'TAHSİLAT' ? 'selected' : '' ?>>TAHSİLAT</option>
                        <option value="ÖDEME" <?= $secili_hareket === 'ÖDEME' ? 'selected' : '' ?>>ÖDEME</option>
                        <option value="KOMİSYON" <?= $secili_hareket === 'KOMİSYON' ? 'selected' : '' ?>>KOMİSYON</option>
                        <option value="VERESİYE" <?= $secili_hareket === 'VERESİYE' ? 'selected' : '' ?>>VERESİYE</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn-rapor w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ===== ÖZET KARTLARI ===== -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="ozet-kart">
                <div class="ozet-label">TOPLAM GELEN</div>
                <div class="ozet-deger text-success"><?= number_format($gelen_toplam, 2, '.', '') ?> ₺</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ozet-kart">
                <div class="ozet-label">TOPLAM GİDEN</div>
                <div class="ozet-deger text-danger"><?= number_format($giden_toplam, 2, '.', '') ?> ₺</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ozet-kart">
                <div class="ozet-label">NET DURUM</div>
                <div class="ozet-deger <?= $net_toplam >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= number_format($net_toplam, 2, '.', '') ?> ₺
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ozet-kart">
                <div class="ozet-label">TOPLAM HAREKET</div>
                <div class="ozet-deger text-info"><?= count($hareketler) ?></div>
            </div>
        </div>
    </div>

    <!-- ===== GRAFİK ===== -->
    <div class="grafik-container">
        <h6 style="color: #8a8a8a; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 15px;">
            <i class="fas fa-chart-bar"></i> GÜNLÜK HAREKET GRAFİĞİ
        </h6>

        <?php if ($grafik_verisi): ?>
        <div class="grafik-barlar">
            <?php foreach ($grafik_verisi as $tarih => $veri): ?>
            <?php
                $gelenYukseklik = $gelen_toplam > 0 ? round($veri['gelen'] / ($gelen_toplam / 100)) : 4;
                $gidenYukseklik = $giden_toplam > 0 ? round($veri['giden'] / ($giden_toplam / 100)) : 4;
            ?>
            <div class="grafik-bar-item">
                <div class="bar-grup">
                    <div class="bar bar-gelen" style="height: <?= $gelenYukseklik ?>px;" title="Gelen: <?= number_format($veri['gelen'], 2, '.', '') ?> ₺"></div>
                    <div class="bar bar-giden" style="height: <?= $gidenYukseklik ?>px;" title="Giden: <?= number_format($veri['giden'], 2, '.', '') ?> ₺"></div>
                </div>
                <div class="bar-label"><?= e($tarih) ?></div>
                <div class="bar-deger">+<?= number_format($veri['gelen'], 0, '.', '') ?>/-<?= number_format($veri['giden'], 0, '.', '') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="grafik-legend">
            <div class="legend-item">
                <div class="legend-color gelen"></div>
                <span>Gelen</span>
                <span class="text-muted" style="font-size: 11px;">(<?= number_format($gelen_toplam, 2, '.', '') ?> ₺)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color giden"></div>
                <span>Giden</span>
                <span class="text-muted" style="font-size: 11px;">(<?= number_format($giden_toplam, 2, '.', '') ?> ₺)</span>
            </div>
            <div class="legend-item">
                <span style="color: #8a8a8a; font-size: 11px;">
                    Net: <span class="<?= $net_toplam >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= number_format($net_toplam, 2, '.', '') ?> ₺
                    </span>
                </span>
            </div>
        </div>
        <?php else: ?>
        <div class="grafik-bos">
            <div class="text-center">
                <i class="fas fa-chart-bar fa-3x d-block mb-3" style="color: #3a3a3a;"></i>
                <span>Veri bulunamadı. Lütfen filtreleme yapın.</span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== HAREKET LİSTESİ ===== -->
    <div class="card-custom mt-4">
        <div class="card-header-custom">
            <h5><i class="fas fa-list"></i> HAREKET LİSTESİ</h5>
            <div class="d-flex gap-2 no-print">
                <button type="button" class="btn-excel" onclick="excelIndir()">
                    <i class="fas fa-file-excel"></i> EXCEL
                </button>
                <button type="button" class="btn-pdf" onclick="window.print()">
                    <i class="fas fa-print"></i> YAZDIR
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-custom" id="raporTable">
                <thead>
                    <tr>
                        <th>Kasa</th>
                        <th>Tarih</th>
                        <th>İşlem Türü</th>
                        <th>Hareket Türü</th>
                        <th class="text-end">Tutar</th>
                        <th>Cari</th>
                        <th>Açıklama</th>
                        <th>Referans</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($hareketler): ?>
                        <?php foreach ($hareketler as $hareket): ?>
                        <?php
                            $isGiris = in_array($hareket['islem_turu'], ['GELEN', 'GİRİŞ'], true);
                            $hareketTurClass = match(true) {
                                $hareket['hareket_turu'] === 'TAHSİLAT' => 'bg-success',
                                $hareket['hareket_turu'] === 'ÖDEME'    => 'bg-danger',
                                $hareket['hareket_turu'] === 'KOMİSYON' => 'bg-warning',
                                $hareket['hareket_turu'] === 'VERESİYE' => 'bg-info',
                                str_starts_with($hareket['hareket_turu'] ?? '', 'HIZLI_TAHSİLAT') => 'bg-success',
                                str_starts_with($hareket['hareket_turu'] ?? '', 'HIZLI_ÖDEME')    => 'bg-info',
                                str_starts_with($hareket['hareket_turu'] ?? '', 'MAKBUZ_')       => 'bg-primary',
                                    str_starts_with($hareket['hareket_turu'] ?? '', 'FATURA_TAHSİLAT') => 'bg-success',
                                    str_starts_with($hareket['hareket_turu'] ?? '', 'FATURA_ÖDEME')    => 'bg-info',
                                $hareket['hareket_turu'] === 'PRİM_ÖDEME'      => 'bg-danger',
                                default    => 'bg-secondary',
                            };
                        ?>
                        <tr>
                            <td><?= e($hareket['hesap_adi'] ?: '-') ?></td>
                            <td><?= $hareket['hareket_tarihi'] ? format_tarih($hareket['hareket_tarihi'], 'd.m.Y H:i') : ($hareket['tarih'] ? format_tarih($hareket['tarih']) : '-') ?></td>
                            <td><span class="badge-status <?= $isGiris ? 'bg-success' : 'bg-danger' ?>"><?= e($hareket['islem_turu']) ?></span></td>
                            <td><span class="badge-status <?= $hareketTurClass ?>"><?= e($hareket['hareket_turu']) ?></span></td>
                            <td class="text-end <?= $isGiris ? 'text-success' : 'text-danger' ?>"><?= number_format((float)$hareket['tutar'], 2, '.', '') ?> ₺</td>
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
                                <?php if ($baslangic || $bitis): ?>
                                    Belirtilen tarih aralığında hareket bulunamadı.
                                <?php else: ?>
                                    Lütfen filtreleme yaparak rapor oluşturun.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if ($hareketler): ?>
                <tfoot>
                    <tr style="border-top: 2px solid var(--border-color);">
                        <td colspan="4" class="text-end"><strong>TOPLAM GELEN</strong></td>
                        <td class="text-end text-success"><strong><?= number_format($gelen_toplam, 2, '.', '') ?> ₺</strong></td>
                        <td colspan="3"></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end"><strong>TOPLAM GİDEN</strong></td>
                        <td class="text-end text-danger"><strong><?= number_format($giden_toplam, 2, '.', '') ?> ₺</strong></td>
                        <td colspan="3"></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end" style="font-weight: 700; font-size: 15px;"><strong>NET DURUM</strong></td>
                        <td class="text-end" style="font-weight: 700; font-size: 15px; <?= $net_toplam >= 0 ? 'color: #4ad46a;' : 'color: #d44a4a;' ?>">
                            <strong><?= number_format($net_toplam, 2, '.', '') ?> ₺</strong>
                        </td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<?php
$extra_js = <<<'JS'
<script>
function excelIndir() {
    // NOT: Önceki sürüm bir HTML tablosunu ".xls" uzantısıyla kaydediyordu -
    // bu, Excel'de her açılışta "dosya biçimi ve uzantısı eşleşmiyor" uyarısı
    // veriyordu çünkü gerçek bir Excel dosyası değildi. Burada onun yerine
    // gerçek, standart bir CSV dosyası üretiliyor - Excel bunu hiçbir uyarı
    // vermeden doğrudan açar. Türkçe karakterlerin doğru görünmesi için
    // UTF-8 BOM ekleniyor, ondalık ayracımız virgül olduğu için CSV
    // ayracı olarak noktalı virgül (;) kullanılıyor (Türkçe Excel'in
    // varsayılan beklentisi).
    var satirlar = [];

    satirlar.push(['KASA RAPORU']);

    var baslangicVal = document.querySelector('input[name="baslangic"]').value;
    var bitisVal = document.querySelector('input[name="bitis"]').value;
    if (baslangicVal || bitisVal) {
        satirlar.push(['Tarih Aralığı', (baslangicVal || 'Başlangıç') + ' - ' + (bitisVal || 'Bitiş')]);
    }

    var gelenEl = document.querySelector('.ozet-deger.text-success');
    var gidenEl = document.querySelector('.ozet-deger.text-danger');
    satirlar.push(['Toplam Gelen', gelenEl ? gelenEl.textContent.trim() : '0 ₺']);
    satirlar.push(['Toplam Giden', gidenEl ? gidenEl.textContent.trim() : '0 ₺']);
    satirlar.push([]);

    var table = document.getElementById('raporTable');
    tabloyuCsvSatirlarinaEkle(table, satirlar);

    csvIndir(satirlar, 'kasa_raporu_' + new Date().toISOString().slice(0, 10) + '.csv');
}

/** Bir <table> elementindeki başlık ve veri satırlarını CSV satır dizisine ekler */
function tabloyuCsvSatirlarinaEkle(table, satirlar) {
    table.querySelectorAll('thead tr').forEach(function(tr) {
        var hucreler = [];
        tr.querySelectorAll('th').forEach(function(th) { hucreler.push(th.textContent.trim()); });
        satirlar.push(hucreler);
    });
    table.querySelectorAll('tbody tr').forEach(function(tr) {
        if (tr.style.display === 'none') return; // filtrelenmiş/gizli satırları atla
        var hucreler = [];
        tr.querySelectorAll('td').forEach(function(td) { hucreler.push(td.textContent.trim().replace(/\s+/g, ' ')); });
        if (hucreler.length) satirlar.push(hucreler);
    });
}

/** Satır dizisini gerçek bir CSV dosyası olarak indirir (UTF-8 BOM'lu, ; ayraçlı) */
function csvIndir(satirlar, dosyaAdi) {
    var csv = satirlar.map(function(satir) {
        return satir.map(function(hucre) {
            var deger = String(hucre == null ? '' : hucre);
            if (deger.indexOf(';') !== -1 || deger.indexOf('"') !== -1 || deger.indexOf('\n') !== -1) {
                deger = '"' + deger.replace(/"/g, '""') + '"';
            }
            return deger;
        }).join(';');
    }).join('\r\n');

    var blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = dosyaAdi;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>
