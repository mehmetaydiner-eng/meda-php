<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/numara_manager.php';
require_login();

$tur = mb_strtoupper(trim($_GET['tur'] ?? $_POST['tur'] ?? ''), 'UTF-8');
if (!in_array($tur, ['ALIS', 'SATIS', 'TAHSILAT', 'ODEME'], true)) {
    flash_set('Geçersiz makbuz türü!', 'danger');
    header('Location: ' . BASE_URL . '/makbuzlar.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(BASE_URL . '/makbuz_olustur.php?tur=' . $tur);

    try {
        $cari_id = safe_int($_POST['cari_id'] ?? null, 0) ?: null;
        $makbuz_tarihi_str = trim($_POST['makbuz_tarihi'] ?? '');
        $para_birimi = turkce_upper(trim($_POST['para_birimi'] ?? 'TRY'));
        $aciklama = turkce_upper(trim($_POST['aciklama'] ?? ''));

        // ===== ÖDEME DAĞILIMI (çoklu kanal/kasa) =====
        // NOT: Önceden tek bir "Ödeme Türü" + tek bir "Hesap/Kasa" seçimi
        // vardı. Efe'nin isteği üzerine (15 Temmuz 2026) artık bir makbuz
        // birden fazla kanaldan (nakit + havale + kredi kartı gibi), birden
        // fazla kasaya bölünerek ödenebiliyor - bkz. hizli_islem_yap.php'deki
        // aynı desen. Ödenen toplam genel toplamdan az olabilir; ödenmeyen
        // kısım için kasa hareketi oluşmaz (otomatik veresiye/borç sayılır).
        $odeme_turleri_raw     = $_POST['odeme_turu'] ?? [];
        $odeme_hesap_idler_raw = $_POST['odeme_hesap_id'] ?? [];
        $odeme_tutarlar_raw    = $_POST['odeme_tutar'] ?? [];

        $odemeSatirlari = [];
        foreach ($odeme_tutarlar_raw as $i => $tutarRaw) {
            $tutarBu = safe_float($tutarRaw);
            if ($tutarBu <= 0) continue;
            $odemeSatirlari[] = [
                'turu'     => trim($odeme_turleri_raw[$i] ?? 'NAKİT'),
                'hesap_id' => safe_int($odeme_hesap_idler_raw[$i] ?? null, 0) ?: null,
                'tutar'    => $tutarBu,
            ];
        }
        $odemeOzetTurleri = array_values(array_unique(array_column($odemeSatirlari, 'turu')));
        $odeme_turu = $odemeOzetTurleri ? implode(' + ', $odemeOzetTurleri) : 'VERESİYE';
        $hesap_id = null;
        foreach ($odemeSatirlari as $os) {
            if ($os['hesap_id']) { $hesap_id = $os['hesap_id']; break; }
        }

        if (!$cari_id) {
            flash_set('Lütfen bir cari seçin!', 'danger');
            header('Location: ' . BASE_URL . '/makbuz_olustur.php?tur=' . $tur);
            exit;
        }

        $makbuz_tarihi = $makbuz_tarihi_str !== '' ? $makbuz_tarihi_str . ' ' . date('H:i:s') : date('Y-m-d H:i:s');
        $makbuz_no = generate_makbuz_no_nm($pdo, $tur);

        $pdo->beginTransaction();

        $insert = $pdo->prepare(
            'INSERT INTO makbuzlar
                (makbuz_no, makbuz_tarihi, makbuz_turu, cari_id, hesap_id, para_birimi,
                 odeme_turu, aciklama, durum, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'), datetime(\'now\',\'localtime\'))'
        );
        $insert->execute([
            $makbuz_no, $makbuz_tarihi, $tur, $cari_id, $hesap_id, $para_birimi,
            $odeme_turu, $aciklama, 'OLUŞTURULDU', current_user()['username'] ?? '',
        ]);
        $makbuz_id = (int)$pdo->lastInsertId();

        $urun_ids   = $_POST['urun_ids'] ?? [];
        $miktarlar  = $_POST['miktarlar'] ?? [];
        $fiyatlar   = $_POST['fiyatlar'] ?? [];
        $iskontolar = $_POST['iskontolar'] ?? [];

        $ara_toplam = 0.0;
        $toplam_iskonto = 0.0;

        for ($i = 0; $i < count($urun_ids); $i++) {
            if (empty($urun_ids[$i])) continue;

            $stmt = $pdo->prepare('SELECT * FROM urunler WHERE id = ?');
            $stmt->execute([$urun_ids[$i]]);
            $urun = $stmt->fetch();
            if (!$urun) continue;

            $miktar = safe_float($miktarlar[$i] ?? 1, 1);
            $birim_fiyat = safe_float($fiyatlar[$i] ?? $urun['satis_fiyati'], (float)$urun['satis_fiyati']);
            $iskonto_orani = safe_float($iskontolar[$i] ?? 0, 0);

            $satir_toplam = $miktar * $birim_fiyat;
            $iskonto_tutari = $satir_toplam * ($iskonto_orani / 100);
            $iskonto_sonrasi = $satir_toplam - $iskonto_tutari;
            $genel_satir_toplam = $iskonto_sonrasi;

            $ara_toplam += $satir_toplam;
            $toplam_iskonto += $iskonto_tutari;

            $insertDetay = $pdo->prepare(
                'INSERT INTO makbuz_detaylari
                    (makbuz_id, urun_id, urun_adi, urun_kodu, barkod, birim, miktar, birim_fiyati,
                     iskonto, iskonto_tutari, ara_toplam, toplam_tutar, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
            );
            $insertDetay->execute([
                $makbuz_id, $urun['id'], $urun['urun_adi'], $urun['urun_kodu'], $urun['barkod'],
                $urun['birim'] ?: 'ADET', $miktar, $birim_fiyat, $iskonto_orani, $iskonto_tutari,
                $satir_toplam, $genel_satir_toplam,
            ]);

            $stokOncesi = (float)$urun['stok_miktari'];
            if ($tur === 'SATIS') {
                $yeniStok = $stokOncesi - $miktar;
                $pdo->prepare('UPDATE urunler SET stok_miktari = ? WHERE id = ?')->execute([$yeniStok, $urun['id']]);
                stok_hareketi_ekle($pdo, (int)$urun['id'], 'SATIŞ', -$miktar, $stokOncesi, $yeniStok, $makbuz_no, "Satış Makbuzu - {$makbuz_no}", $cari_id);
            } elseif ($tur === 'ALIS') {
                $yeniStok = $stokOncesi + $miktar;
                $pdo->prepare('UPDATE urunler SET stok_miktari = ? WHERE id = ?')->execute([$yeniStok, $urun['id']]);
                stok_hareketi_ekle($pdo, (int)$urun['id'], 'ALIŞ', $miktar, $stokOncesi, $yeniStok, $makbuz_no, "Alış Makbuzu - {$makbuz_no}", $cari_id);
            }
        }

        $iskonto_orani_toplam = $ara_toplam > 0 ? ($toplam_iskonto / $ara_toplam * 100) : 0;
        $genel_toplam = $ara_toplam - $toplam_iskonto;

        $pdo->prepare(
            'UPDATE makbuzlar SET ara_toplam=?, iskonto=?, iskonto_tutari=?, vergi_orani=0, vergi_tutari=0, genel_toplam=?, updated_at=datetime(\'now\',\'localtime\') WHERE id=?'
        )->execute([$ara_toplam, $iskonto_orani_toplam, $toplam_iskonto, $genel_toplam, $makbuz_id]);

        // Cari bakiye güncelle
        // NOT: Önceden cari bakiyesi HER ZAMAN tam makbuz tutarı kadar
        // etkileniyordu - ödeme dağılımında gerçekte ne kadar tahsil/ödeme
        // yapıldığına bakılmaksızın. Artık sadece ÖDENMEYEN (kalan) tutar
        // cari bakiyesini etkiliyor - tam ödenen/tahsil edilen bir makbuzda
        // cari bakiyesi hiç değişmez.
        $stmt = $pdo->prepare('SELECT * FROM cariler WHERE id = ?');
        $stmt->execute([$cari_id]);
        $cari = $stmt->fetch();

        // NOT (19 Temmuz 2026): "VERESİYE" ödeme türü satırları gerçek bir
        // ödeme değildir - ödenen toplama dahil edilmiyor (bkz.
        // hizli_islem_yap.php'deki aynı not).
        $toplamOdenenTutar = array_sum(array_column(
            array_filter($odemeSatirlari, fn($os) => $os['turu'] !== 'VERESİYE'),
            'tutar'
        ));
        $kalanTutar = $genel_toplam - $toplamOdenenTutar;

        if ($cari) {
            if (in_array($tur, ['SATIS', 'TAHSILAT'], true)) {
                $yeniCariBakiye = (float)$cari['bakiye'] - $kalanTutar;
            } else { // ALIS, ODEME
                $yeniCariBakiye = (float)$cari['bakiye'] + $kalanTutar;
            }
            $pdo->prepare('UPDATE cariler SET bakiye = ? WHERE id = ?')->execute([$yeniCariBakiye, $cari_id]);
        }

        // Ödeme dağılımı: her ödeme satırı için ayrı bir hesap bakiyesi
        // güncellemesi + hesap hareketi (defter) satırı oluşturuluyor.
        $islemTuru = in_array($tur, ['SATIS', 'TAHSILAT'], true) ? 'GELEN' : 'GIDEN';
        $insertHareket = $pdo->prepare(
            'INSERT INTO hesap_hareketleri
                (hesap_id, cari_id, hareket_no, hareket_tarihi, islem_turu, hareket_turu, tutar,
                 para_birimi, aciklama, referans_no, odeme_turu, hesap_bakiye_oncesi, hesap_bakiye_sonrasi, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
        );

        foreach ($odemeSatirlari as $odemeSatiri) {
            if ($odemeSatiri['turu'] === 'VERESİYE') continue; // veresiye satırları hiçbir kasaya dokunmaz
            if (!$odemeSatiri['hesap_id']) continue; // kasa seçilmediyse sadece "ödendi" bilgisi kalır

            $stmt = $pdo->prepare('SELECT * FROM hesaplar WHERE id = ?');
            $stmt->execute([$odemeSatiri['hesap_id']]);
            $buHesap = $stmt->fetch();
            if (!$buHesap) continue;

            $buOncesi = (float)$buHesap['bakiye'];
            $buSonrasi = in_array($tur, ['SATIS', 'TAHSILAT'], true)
                ? ($buOncesi + $odemeSatiri['tutar'])
                : ($buOncesi - $odemeSatiri['tutar']);
            $pdo->prepare('UPDATE hesaplar SET bakiye = ? WHERE id = ?')->execute([$buSonrasi, $odemeSatiri['hesap_id']]);

            $insertHareket->execute([
                $odemeSatiri['hesap_id'], $cari_id, generate_hareket_no(), date('Y-m-d H:i:s'), $islemTuru,
                'MAKBUZ_' . $tur, $odemeSatiri['tutar'], $para_birimi,
                "{$tur} Makbuzu - {$makbuz_no} - {$odemeSatiri['turu']}", $makbuz_no, $odemeSatiri['turu'],
                $buOncesi, $buSonrasi,
            ]);
        }

        $pdo->commit();

        flash_set("{$tur} makbuzu başarıyla oluşturuldu! No: {$makbuz_no}", 'success');

        // SATIS makbuzundan sonra Prim popup'ının açılabilmesi için (bu sayfa
        // da geleneksel form POST + redirect kullanıyor - bkz. hizli_islem_yap.php'deki
        // aynı not) satış tutarı/referans bilgilerini yönlendirme URL'sine ekliyoruz.
        if ($tur === 'SATIS') {
            $primParams = http_build_query([
                'prim_sor'  => 1,
                'tutar'     => $genel_toplam,
                'ref'       => $makbuz_no,
            ]);
            header('Location: ' . BASE_URL . '/makbuz_olustur.php?tur=SATIS&' . $primParams);
        } else {
            header('Location: ' . BASE_URL . '/makbuzlar.php');
        }
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash_set('Hata oluştu: ' . $e->getMessage(), 'danger');
        header('Location: ' . BASE_URL . '/makbuz_olustur.php?tur=' . $tur);
        exit;
    }
}

$cariler = $pdo->query('SELECT * FROM cariler ORDER BY unvan')->fetchAll();
$hesaplar = $pdo->query("SELECT * FROM hesaplar WHERE is_active = 1 AND hesap_turu != 'VERESİYE' ORDER BY hesap_adi")->fetchAll();
$personeller = $pdo->query("SELECT * FROM cariler WHERE cari_turu = 'PERSONEL' ORDER BY unvan")->fetchAll();
$onSeciliCariId = safe_int($_GET['cari_id'] ?? null, 0);

$page_title   = $tur . ' MAKBUZU OLUŞTUR';
$breadcrumb   = $tur . ' Makbuzu';
$current_page = 'makbuzlar';
$extra_css    = '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/makbuz_olustur.css">';
require_once __DIR__ . '/../includes/header.php';

$now = new DateTime('now');
?>

<div class="makbuz-container">
    <div class="card-custom">
        <div class="makbuz-header">
            <div class="makbuz-bilgi">
                <div class="makbuz-no"><?= e($tur) ?> MAKBUZU</div>
                <div>Tarih: <?= $now->format('d.m.Y H:i') ?></div>
                <div>No: OTOMATİK OLUŞACAK</div>
            </div>
            <div class="makbuz-bilgi text-end">
                <div><strong>Makbuz Türü:</strong> <?= e($tur) ?></div>
                <div><strong>Durum:</strong> <span class="badge bg-info">OLUŞTURULUYOR</span></div>
            </div>
        </div>

        <div class="makbuz-body">
            <form method="POST" id="makbuzForm">
                <?= csrf_field() ?>                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" style="color: #8a8a8a; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                            CARİ / MÜŞTERİ <span class="text-danger">*</span>
                        </label>
                        <select name="cari_id" class="form-select" required>
                            <option value="">Seçin...</option>
                            <?php foreach ($cariler as $cari): ?>
                            <option value="<?= (int)$cari['id'] ?>" <?= $onSeciliCariId === (int)$cari['id'] ? 'selected' : '' ?>>
                                <?= e($cari['unvan']) ?> - <?= e($cari['vergi_no'] ?: 'VERGİ NO YOK') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="color: #8a8a8a; font-size: 11px; font-weight: 600; text-transform: uppercase;">Makbuz Tarihi</label>
                        <input type="date" name="makbuz_tarihi" class="form-control" value="<?= $now->format('Y-m-d') ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" style="color: #8a8a8a; font-size: 11px; font-weight: 600; text-transform: uppercase;">Para Birimi</label>
                        <select name="para_birimi" class="form-select">
                            <option value="TRY">TL</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                </div>

                <div class="urun-ekle-row" style="margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div style="font-size: 11px; font-weight: 700; color: #8a8a8a; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-money-bill-wave"></i> ÖDEME DAĞILIMI
                        </div>
                        <button type="button" class="btn-yeni-urun-makbuz" onclick="odemeSatiriEkle()">
                            <i class="fas fa-plus"></i> ÖDEME EKLE
                        </button>
                    </div>
                    <div id="odemeSatirlari"></div>
                    <div class="d-flex justify-content-end gap-4 mt-2" style="font-size: 12px;">
                        <span>Genel Toplam: <strong id="odemeGenelToplamGoster">0.00</strong></span>
                        <span>Ödenen: <strong id="odemeOdenenGoster" class="text-success">0.00</strong></span>
                        <span>Kalan: <strong id="odemeKalanGoster">0.00</strong></span>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Ödemeyi birden fazla kanaldan (nakit + havale + kredi kartı gibi) bölebilirsin.
                        Kalan tutar veresiye/borç olarak izlenir.
                    </small>
                </div>

                <div class="urun-ekle-row">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div style="font-size: 11px; font-weight: 700; color: #8a8a8a; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-plus-circle"></i> ÜRÜN EKLE
                        </div>
                        <button type="button" class="btn-yeni-urun-makbuz" data-bs-toggle="modal" data-bs-target="#yeniUrunModal">
                            <i class="fas fa-plus-circle"></i> YENİ ÜRÜN
                        </button>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">ÜRÜN ARA</label>
                            <input type="text" id="urun-ara" class="form-control" placeholder="Ürün adı veya barkod...">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn-makbuz-urun-ekle w-100" onclick="urunAra()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ÜRÜN SEÇ</label>
                            <select id="urun-listesi" class="form-select">
                                <option value="">Seçin...</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">MİKTAR</label>
                            <input type="number" id="urun-miktar" class="form-control" value="1" min="0.01" step="0.01">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">FİYAT</label>
                            <input type="number" id="urun-fiyat" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn-makbuz-urun-ekle w-100" onclick="kalemEkle()">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="makbuz-table" id="makbuz-kalemleri">
                        <thead>
                            <tr>
                                <th style="width:30px;">#</th>
                                <th style="width:25%;">ÜRÜN ADI</th>
                                <th style="width:15%;">BARKOD</th>
                                <th style="width:10%;" class="text-center">MİKTAR</th>
                                <th style="width:15%;" class="text-end">BİRİM FİYAT</th>
                                <th style="width:10%;" class="text-center">İSKONTO %</th>
                                <th style="width:15%;" class="text-end">TOPLAM</th>
                                <th style="width:35px;" class="text-center">İŞLEM</th>
                            </tr>
                        </thead>
                        <tbody id="makbuz-kalem-tbody">
                            <tr id="bos-kalem">
                                <td colspan="8" class="text-center text-muted py-3">
                                    <i class="fas fa-plus-circle fa-2x d-block mb-2"></i>
                                    Ürün eklemek için yukarıdaki formu kullanın
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" class="text-end"><strong>ARA TOPLAM</strong></td>
                                <td class="text-end" id="ara-toplam">0.00</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="6" class="text-end"><strong>İSKONTO</strong></td>
                                <td class="text-end" id="iskonto-tutari">0.00</td>
                                <td></td>
                            </tr>
                            <tr style="border-top: 2px solid #2a2a2a;">
                                <td colspan="6" class="text-end" style="font-size: 15px; font-weight: 700;">GENEL TOPLAM</td>
                                <td class="text-end" style="font-size: 15px; font-weight: 700; color: #4ad46a;" id="genel-toplam">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-3">
                    <label class="form-label" style="color: #8a8a8a; font-size: 11px; font-weight: 600; text-transform: uppercase;">AÇIKLAMA / NOT</label>
                    <textarea name="aciklama" class="form-control" rows="2" placeholder="Makbuz açıklaması..."></textarea>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn-makbuz-kaydet">
                        <i class="fas fa-save"></i> MAKBUZU KAYDET
                    </button>
                    <a href="<?= BASE_URL ?>/makbuzlar.php" class="btn btn-outline-primary">İPTAL</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== YENİ ÜRÜN EKLE MODAL ===== -->
<div class="modal fade" id="yeniUrunModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-box"></i> YENİ ÜRÜN EKLE</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="yeniUrunForm" onsubmit="return false;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ÜRÜN KODU <span class="text-danger">*</span></label>
                            <input type="text" id="modal_urun_kodu" class="form-control" placeholder="PR-001" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ÜRÜN ADI <span class="text-danger">*</span></label>
                            <input type="text" id="modal_urun_adi" class="form-control" placeholder="LAPTOP" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">BARKOD</label>
                            <div class="input-group">
                                <input type="text" id="modal_barkod" class="form-control" placeholder="OTOMATİK">
                                <button type="button" class="btn btn-outline-primary" onclick="modalBarkodOlustur()">
                                    <i class="fas fa-qrcode"></i> OLUŞTUR
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SERİ NUMARASI</label>
                            <input type="text" id="modal_seri_no" class="form-control" placeholder="SN-2024-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">KATEGORİ</label>
                            <input type="text" id="modal_kategori" class="form-control" placeholder="BİLGİSAYAR">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">BİRİM</label>
                            <select id="modal_birim" class="form-select">
                                <option value="ADET">ADET</option>
                                <option value="KG">KG</option>
                                <option value="METRE">METRE</option>
                                <option value="LİTRE">LİTRE</option>
                                <option value="SAAT">SAAT</option>
                                <option value="PAKET">PAKET</option>
                                <option value="KUTU">KUTU</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ÜRÜN TİPİ</label>
                            <select id="modal_urun_tipi" class="form-select">
                                <option value="SIFIR">SIFIR</option>
                                <option value="2.EL">2.EL</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ALIŞ FİYATI</label>
                            <div class="input-group">
                                <input type="number" id="modal_alis_fiyati" class="form-control" step="0.01" value="0">
                                <select id="modal_alis_doviz" class="form-select" style="max-width: 80px;">
                                    <option value="TL">TL</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SATIŞ FİYATI</label>
                            <div class="input-group">
                                <input type="number" id="modal_satis_fiyati" class="form-control" step="0.01" value="0">
                                <select id="modal_satis_doviz" class="form-select" style="max-width: 80px;">
                                    <option value="TL">TL</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">STOK MİKTARI</label>
                            <input type="number" id="modal_stok_miktari" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">MIN. STOK</label>
                            <input type="number" id="modal_min_stok" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">MAX. STOK</label>
                            <input type="number" id="modal_max_stok" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">AÇIKLAMA</label>
                            <textarea id="modal_aciklama" class="form-control" rows="2" placeholder="ÜRÜN AÇIKLAMASI..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-iptal" data-bs-dismiss="modal">İPTAL</button>
                <button type="button" class="btn-urun-kaydet" onclick="modalUrunKaydet()">
                    <i class="fas fa-save"></i> KAYDET VE EKLE
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== PRİM POPUP ===== -->
<div class="modal fade" id="primModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-hand-holding-usd"></i> PRİM İŞLEMİ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="primSoruAlani" class="text-center py-3">
                    <p style="font-size: 15px;">Bu satış için <strong>prim işlemi</strong> yapılacak mı?</p>
                    <p class="text-muted" style="font-size: 12px;">Satış tutarı: <strong id="primSatisTutariGoster">-</strong></p>
                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <button type="button" class="btn btn-success" onclick="primEvet()">
                            <i class="fas fa-check"></i> EVET
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> HAYIR
                        </button>
                    </div>
                </div>

                <div id="primDetayAlani" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label">Prim Verilecek Kişi <span class="text-danger">*</span></label>
                        <select id="primKisi" class="form-select">
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
                    <div class="mb-3">
                        <label class="form-label">Hesaplama Yöntemi</label>
                        <div class="d-flex gap-3">
                            <label class="d-flex align-items-center gap-1">
                                <input type="radio" name="primYontem" value="SABIT" checked onchange="primYontemDegisti()"> Sabit Tutar
                            </label>
                            <label class="d-flex align-items-center gap-1">
                                <input type="radio" name="primYontem" value="ORAN" onchange="primYontemDegisti()"> Satıştan Oranla
                            </label>
                        </div>
                    </div>
                    <div id="primSabitAlani" class="mb-3">
                        <label class="form-label">Prim Tutarı (₺)</label>
                        <input type="number" id="primTutarSabit" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div id="primOranAlani" class="mb-3" style="display: none;">
                        <label class="form-label">Oran (%)</label>
                        <input type="number" id="primOranYuzde" class="form-control" step="0.1" min="0" max="100" value="0" oninput="primOranHesapla()">
                        <small class="text-muted">Hesaplanan tutar: <strong id="primHesaplananTutar">0.00</strong> ₺</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama (opsiyonel)</label>
                        <input type="text" id="primAciklama" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="primModalFooter" style="display: none;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İPTAL</button>
                <button type="button" class="btn btn-success btn-sm" onclick="primKaydet()">
                    <i class="fas fa-save"></i> KAYDET
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$api_base_json = json_encode(BASE_URL);
$odeme_hesaplari_json = json_encode(array_map(fn($h) => ['id' => (int)$h['id'], 'ad' => $h['hesap_adi']], $hesaplar));
$odeme_kanallari_json = json_encode(['NAKİT', 'KREDİ KARTI', 'BANKA HAVALESİ', 'EFT', 'ÇEK', 'SENET', 'VERESİYE']);
$extra_js = <<<JS
<script>
var API_BASE = {$api_base_json};
var ODEME_HESAPLARI = {$odeme_hesaplari_json};
var ODEME_KANALLARI = {$odeme_kanallari_json};
var odemeSatirSayaci = 0;
var primSatisTutari = 0;
var primReferansNo = '';

function urunAra() {
    var q = document.getElementById('urun-ara').value.trim();
    if (q.length < 2) return;

    fetch(API_BASE + '/api/stok_ara.php?q=' + encodeURIComponent(q))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var select = document.getElementById('urun-listesi');
            select.innerHTML = '<option value="">Ürün seçin...</option>';
            data.forEach(function(urun) {
                select.innerHTML += '<option value="' + urun.id + '" data-fiyat="' + urun.satis_fiyati + '" data-ad="' + urun.urun_adi + '" data-barkod="' + (urun.barkod || '') + '">' + urun.urun_adi + ' - ' + (urun.barkod || 'BARKOD YOK') + ' (' + urun.satis_fiyati + ' TL)</option>';
            });
        });
}

document.getElementById('urun-listesi').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    if (selected.value) {
        document.getElementById('urun-fiyat').value = selected.dataset.fiyat || 0;
    }
});

function kalemEkle() {
    var select = document.getElementById('urun-listesi');
    var selected = select.options[select.selectedIndex];
    if (!selected.value) { alert('Lütfen bir ürün seçin!'); return; }

    var miktar = parseFloat(document.getElementById('urun-miktar').value) || 1;
    var fiyat = parseFloat(document.getElementById('urun-fiyat').value) || 0;
    var iskonto = 0;
    var toplam = miktar * fiyat * (1 - iskonto / 100);

    var tbody = document.getElementById('makbuz-kalem-tbody');
    var bos = document.getElementById('bos-kalem');
    if (bos) bos.remove();

    var index = tbody.children.length + 1;
    var row = document.createElement('tr');
    row.innerHTML =
        '<td>' + index + '</td>' +
        '<td><input type="hidden" name="urun_ids[]" value="' + selected.value + '">' + selected.dataset.ad + '</td>' +
        '<td>' + (selected.dataset.barkod || '-') + '</td>' +
        '<td class="text-center"><input type="number" class="miktar-input" name="miktarlar[]" value="' + miktar.toFixed(2) + '" min="0.01" step="0.01" onchange="satirGuncelle(this)"></td>' +
        '<td class="text-end"><input type="number" class="fiyat-input" name="fiyatlar[]" value="' + fiyat.toFixed(2) + '" min="0" step="0.01" onchange="satirGuncelle(this)"></td>' +
        '<td class="text-center"><input type="number" class="iskonto-input" name="iskontolar[]" value="' + iskonto.toFixed(0) + '" min="0" max="100" step="0.5" onchange="satirGuncelle(this)"></td>' +
        '<td class="text-end toplam-tutar">' + toplam.toFixed(2) + '</td>' +
        '<td class="text-center"><button type="button" class="btn-makbuz-urun-sil" onclick="satirSil(this)"><i class="fas fa-times"></i></button></td>';
    tbody.appendChild(row);
    hesaplaToplam();
}

function satirGuncelle(input) {
    var row = input.closest('tr');
    var miktar = parseFloat(row.querySelector('.miktar-input').value) || 0;
    var fiyat = parseFloat(row.querySelector('.fiyat-input').value) || 0;
    var iskonto = parseFloat(row.querySelector('.iskonto-input').value) || 0;
    var toplam = miktar * fiyat * (1 - iskonto / 100);
    row.querySelector('.toplam-tutar').textContent = toplam.toFixed(2);
    hesaplaToplam();
}

function satirSil(btn) {
    var row = btn.closest('tr');
    row.remove();
    document.querySelectorAll('#makbuz-kalem-tbody tr').forEach(function(tr, i) { tr.cells[0].textContent = i + 1; });
    hesaplaToplam();
}

function hesaplaToplam() {
    var araToplam = 0;
    var toplamIskonto = 0;
    document.querySelectorAll('#makbuz-kalem-tbody tr').forEach(function(row) {
        if (row.id === 'bos-kalem') return;
        var miktar = parseFloat(row.querySelector('.miktar-input').value) || 0;
        var fiyat = parseFloat(row.querySelector('.fiyat-input').value) || 0;
        var iskonto = parseFloat(row.querySelector('.iskonto-input').value) || 0;
        araToplam += miktar * fiyat;
        toplamIskonto += (miktar * fiyat) * (iskonto / 100);
    });
    var genelToplam = araToplam - toplamIskonto;
    document.getElementById('ara-toplam').textContent = araToplam.toFixed(2);
    document.getElementById('iskonto-tutari').textContent = toplamIskonto.toFixed(2);
    document.getElementById('genel-toplam').textContent = genelToplam.toFixed(2);
    odemeOzetGuncelle();
}

// ============================================================
// ÖDEME DAĞILIMI (çoklu kanal/kasa ile bölünmüş ödeme)
// ============================================================
// NOT: Bu form NATIVE (JS'siz) submit ediyor - bu yüzden her ödeme
// satırındaki select/input'lara doğrudan name="odeme_turu[]" gibi
// öznitelikler veriliyor; form gönderilince tarayıcı bunları otomatik
// olarak diziye çeviriyor, ekstra bir JS submit-hook'a gerek yok.
// Efe'nin isteği üzerine (19 Temmuz 2026): "VERESİYE" seçilirse Hesap/Kasa
// seçicisi devre dışı bırakılıyor. NOT: Bu form native (isimli input)
// gönderim kullandığı için select'i GERÇEKTEN disabled yapmıyoruz -
// disabled elemanlar tarayıcı tarafından hiç gönderilmez, bu da
// odeme_turu[]/odeme_hesap_id[]/odeme_tutar[] dizilerinin hizasını
// bozardı. Bunun yerine değeri temizleyip görsel olarak "pasif" gösteren
// bir "yumuşak devre dışı bırakma" yapıyoruz - alan yine boş değerle
// gönderiliyor, dizi hizası korunuyor.
function odemeTuruDegisti(selectEl) {
    var satir = selectEl.closest('.odeme-satiri');
    if (!satir) return;
    var hesapSelect = satir.querySelector('.odeme-hesap-select');
    if (!hesapSelect) return;

    if (selectEl.value === 'VERESİYE') {
        hesapSelect.value = '';
        hesapSelect.style.pointerEvents = 'none';
        hesapSelect.style.opacity = '0.4';
        hesapSelect.tabIndex = -1;
    } else {
        hesapSelect.style.pointerEvents = '';
        hesapSelect.style.opacity = '';
        hesapSelect.tabIndex = 0;
    }
}

function odemeSatiriEkle() {
    odemeSatirSayaci++;
    var satirId = odemeSatirSayaci;

    var kanalOptions = ODEME_KANALLARI.map(function(k) {
        return '<option value="' + k + '"' + (k === 'NAKİT' ? ' selected' : '') + '>' + k + '</option>';
    }).join('');

    var hesapOptions = '<option value="">-- Kasa Seçin --</option>' + ODEME_HESAPLARI.map(function(h) {
        return '<option value="' + h.id + '">' + h.ad + '</option>';
    }).join('');

    var satir = document.createElement('div');
    satir.className = 'row g-1 mb-1 odeme-satiri';
    satir.dataset.satirId = satirId;
    satir.innerHTML =
        '<div class="col-4"><select class="form-select odeme-turu-select" name="odeme_turu[]" onchange="odemeTuruDegisti(this)">' + kanalOptions + '</select></div>' +
        '<div class="col-4"><select class="form-select odeme-hesap-select" name="odeme_hesap_id[]">' + hesapOptions + '</select></div>' +
        '<div class="col-3"><input type="number" class="form-control odeme-tutar-input" name="odeme_tutar[]" step="0.01" min="0" value="0" oninput="odemeOzetGuncelle()"></div>' +
        '<div class="col-1"><button type="button" class="btn btn-outline-danger btn-sm" onclick="odemeSatiriSil(' + satirId + ')"><i class="fas fa-times"></i></button></div>';

    document.getElementById('odemeSatirlari').appendChild(satir);
    odemeOzetGuncelle();
}

function odemeSatiriSil(satirId) {
    var satir = document.querySelector('.odeme-satiri[data-satir-id="' + satirId + '"]');
    if (satir) satir.remove();
    odemeOzetGuncelle();
}

function odemeOzetGuncelle() {
    var genelToplamEl = document.getElementById('genel-toplam');
    var genelToplam = genelToplamEl ? (parseFloat(genelToplamEl.textContent) || 0) : 0;
    var odenenToplam = 0;

    document.querySelectorAll('.odeme-tutar-input').forEach(function(input) {
        odenenToplam += parseFloat(input.value) || 0;
    });

    var kalan = genelToplam - odenenToplam;

    document.getElementById('odemeGenelToplamGoster').textContent = genelToplam.toFixed(2);
    document.getElementById('odemeOdenenGoster').textContent = odenenToplam.toFixed(2);

    var kalanEl = document.getElementById('odemeKalanGoster');
    kalanEl.textContent = kalan.toFixed(2);
    kalanEl.className = kalan > 0.01 ? 'text-warning' : (kalan < -0.01 ? 'text-danger' : 'text-success');
}

document.addEventListener('DOMContentLoaded', function() {
    odemeSatiriEkle(); // varsayılan olarak bir NAKİT ödeme satırı ile başla
});

function modalBarkodOlustur() {
    var prefix = '869';
    var random = '';
    for (var i = 0; i < 9; i++) random += Math.floor(Math.random() * 10);
    document.getElementById('modal_barkod').value = prefix + random + Math.floor(Math.random() * 10);
}

function modalUrunKaydet() {
    var urun_kodu = document.getElementById('modal_urun_kodu').value.trim().toUpperCase();
    var urun_adi = document.getElementById('modal_urun_adi').value.trim().toUpperCase();
    if (!urun_kodu) { alert('Ürün kodu zorunludur!'); return; }
    if (!urun_adi) { alert('Ürün adı zorunludur!'); return; }

    var satis_fiyati = parseFloat(document.getElementById('modal_satis_fiyati').value) || 0;
    var barkod = document.getElementById('modal_barkod').value.trim();

    var formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('urun_kodu', urun_kodu);
    formData.append('urun_adi', urun_adi);
    formData.append('barkod', barkod);
    formData.append('seri_no', document.getElementById('modal_seri_no').value.trim().toUpperCase());
    formData.append('kategori', document.getElementById('modal_kategori').value.trim().toUpperCase());
    formData.append('birim', document.getElementById('modal_birim').value);
    formData.append('urun_tipi', document.getElementById('modal_urun_tipi').value);
    formData.append('alis_fiyati', document.getElementById('modal_alis_fiyati').value);
    formData.append('alis_fiyati_doviz', document.getElementById('modal_alis_doviz').value);
    formData.append('satis_fiyati', satis_fiyati);
    formData.append('satis_fiyati_doviz', document.getElementById('modal_satis_doviz').value);
    formData.append('stok_miktari', document.getElementById('modal_stok_miktari').value);
    formData.append('min_stok', document.getElementById('modal_min_stok').value);
    formData.append('max_stok', document.getElementById('modal_max_stok').value);
    formData.append('aciklama', document.getElementById('modal_aciklama').value.trim().toUpperCase());

    fetch(API_BASE + '/api/stok_ekle_ajax.php', { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('yeniUrunModal')).hide();
                alert('Ürün başarıyla eklendi! Şimdi listeye ekleyebilirsiniz.');

                var select = document.getElementById('urun-listesi');
                var option = document.createElement('option');
                option.value = data.urun_id;
                option.dataset.fiyat = satis_fiyati;
                option.dataset.ad = urun_adi;
                option.dataset.barkod = barkod || '';
                option.text = urun_adi + ' - ' + (barkod || 'BARKOD YOK') + ' (' + satis_fiyati + ' TL)';
                select.appendChild(option);
                select.value = data.urun_id;
                document.getElementById('urun-fiyat').value = satis_fiyati;
            } else {
                alert('Hata: ' + (data.message || 'Ürün eklenemedi!'));
            }
        })
        .catch(function(error) { alert('Hata oluştu: ' + error); });
}

document.getElementById('urun-ara').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') urunAra();
});

document.getElementById('yeniUrunModal').addEventListener('hidden.bs.modal', function() {
    ['modal_urun_kodu','modal_urun_adi','modal_barkod','modal_seri_no','modal_kategori','modal_aciklama'].forEach(function(id) {
        document.getElementById(id).value = '';
    });
    ['modal_stok_miktari','modal_min_stok','modal_max_stok','modal_alis_fiyati','modal_satis_fiyati'].forEach(function(id) {
        document.getElementById(id).value = '0';
    });
});

// ============================================================
// PRİM POPUP
// ============================================================
// NOT: Bu sayfa geleneksel form POST + redirect kullanıyor - SATIS
// makbuzu başarıyla oluşunca kendi üzerine (?prim_sor=1&...) geri
// yönlendiriliyor, açılışta bu parametreler kontrol ediliyor.
document.addEventListener('DOMContentLoaded', function() {
    var params = new URLSearchParams(window.location.search);
    if (params.get('prim_sor') !== '1') return;

    primSatisTutari = parseFloat(params.get('tutar')) || 0;
    primReferansNo = params.get('ref') || '';

    document.getElementById('primSatisTutariGoster').textContent = primSatisTutari.toFixed(2) + ' ₺ (' + primReferansNo + ')';
    document.getElementById('primSoruAlani').style.display = 'block';
    document.getElementById('primDetayAlani').style.display = 'none';
    document.getElementById('primModalFooter').style.display = 'none';

    var modal = new bootstrap.Modal(document.getElementById('primModal'));
    modal.show();

    window.history.replaceState({}, document.title, window.location.pathname + '?tur=SATIS');
});

function primEvet() {
    document.getElementById('primSoruAlani').style.display = 'none';
    document.getElementById('primDetayAlani').style.display = 'block';
    document.getElementById('primModalFooter').style.display = 'flex';
}

function primYontemDegisti() {
    var yontem = document.querySelector('input[name="primYontem"]:checked').value;
    document.getElementById('primSabitAlani').style.display = yontem === 'SABIT' ? 'block' : 'none';
    document.getElementById('primOranAlani').style.display = yontem === 'ORAN' ? 'block' : 'none';
    if (yontem === 'ORAN') primOranHesapla();
}

function primOranHesapla() {
    var oran = parseFloat(document.getElementById('primOranYuzde').value) || 0;
    document.getElementById('primHesaplananTutar').textContent = (primSatisTutari * oran / 100).toFixed(2);
}

function primKaydet() {
    var kisiId = document.getElementById('primKisi').value;
    if (!kisiId) { alert('Lütfen prim verilecek kişiyi seçin!'); return; }

    var yontem = document.querySelector('input[name="primYontem"]:checked').value;
    var tutar, oran;
    if (yontem === 'SABIT') {
        tutar = parseFloat(document.getElementById('primTutarSabit').value) || 0;
        oran = null;
    } else {
        oran = parseFloat(document.getElementById('primOranYuzde').value) || 0;
        tutar = primSatisTutari * oran / 100;
    }
    if (tutar <= 0) { alert('Geçerli bir prim tutarı girin!'); return; }

    fetch(API_BASE + '/api/prim_ekle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            cari_id: kisiId, tutar: tutar, oran: oran, matrah: primSatisTutari,
            referans_no: primReferansNo, aciklama: document.getElementById('primAciklama').value,
            csrf_token: CSRF_TOKEN,
        }),
    })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var modal = bootstrap.Modal.getInstance(document.getElementById('primModal'));
            if (modal) modal.hide();
            if (data.success) {
                alert('Prim kaydı oluşturuldu! Primler sayfasından ödeyebilirsiniz.');
            } else {
                alert('Prim kaydedilemedi: ' + data.message);
            }
        })
        .catch(function(error) { alert('Hata: ' + error); });
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>
