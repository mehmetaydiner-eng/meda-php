<?php
/**
 * includes/numara_manager.php
 * Python tarafındaki numara_manager.py dosyasının PHP karşılığı.
 * Fatura/Makbuz/Teklif/Teknik Servis belge numaralarını yönetir.
 *
 * ÖNEMLİ (orijinal davranışla birebir uyum için not):
 * - get_next(): İlgili tabloda o prefix'e ait EN YÜKSEK otomatik numarayı
 *   bulur ve bir sonrakini döndürür. Hiçbir şeyi veritabanına YAZMAZ -
 *   sadece "şu an sıradaki numara bu olurdu" bilgisini hesaplar.
 * - set_next(): Sadece verilen numaranın halihazırda kullanılıp
 *   kullanılmadığını KONTROL EDER ve bir başarı/hata mesajı döndürür.
 *   Orijinal Flask kodunda da bu fonksiyon kalıcı bir "sıradaki numara"
 *   ayarı SAKLAMIYORDU (öyle bir tablo/alan yoktu) - sadece çakışma
 *   kontrolü yapıp mesaj döndürüyordu. Bu davranış burada da aynen
 *   korundu (kullanıcıyı yanıltmamak için arayüzde bunu belirtiyoruz).
 * - Manuel girilen numaralar (SEF, EFS, INV, KEK, MMT vb.) hiçbir zaman
 *   sorguları bozmaz çünkü _get_max_number() sadece kendi prefix+yıl
 *   desenine uyan kayıtları dikkate alır.
 */

const NUMARA_AYARLARI = [
    'FAT' => [
        'baslangic'   => 1,
        'aciklama'    => 'E-FATURA',
        'prefix_ozel' => 'MED',
        'yil'         => '2026',
        'hane_sayisi' => 9,
        'format'      => 'sayi', // {prefix_ozel}{yil}{sayi} -> MED2026000000052
    ],
    'EAR' => [
        'baslangic'   => 1,
        'aciklama'    => 'E-ARŞİV',
        'prefix_ozel' => 'GIB',
        'yil'         => '2026',
        'hane_sayisi' => 9,
        'format'      => 'sayi',
    ],
    'STM' => [
        'baslangic'   => 1,
        'aciklama'    => 'SATIŞ MAKBUZU',
        'prefix_ozel' => 'STM',
        'yil'         => '2026',
        'format'      => 'sayi_zf', // {prefix_ozel}-{yil}-{sayi_zf} -> STM-2026-0001
    ],
    'ALM' => [
        'baslangic'   => 1,
        'aciklama'    => 'ALIŞ MAKBUZU',
        'prefix_ozel' => 'ALM',
        'yil'         => '2026',
        'format'      => 'sayi_zf',
    ],
    'THM' => [
        'baslangic'   => 1,
        'aciklama'    => 'TAHSİLAT',
        'prefix_ozel' => 'THM',
        'yil'         => '2026',
        'format'      => 'sayi_zf',
    ],
    'ODM' => [
        'baslangic'   => 1,
        'aciklama'    => 'ÖDEME',
        'prefix_ozel' => 'ODM',
        'yil'         => '2026',
        'format'      => 'sayi_zf',
    ],
    'VT' => [
        'baslangic'   => 1,
        'aciklama'    => 'TEKLİF (Verilen)',
        'prefix_ozel' => 'VT',
        'yil'         => '2026',
        'format'      => 'sayi_zf',
    ],
    'SRV' => [
        'baslangic'   => 1,
        'aciklama'    => 'TEKNİK SERVİS',
        'prefix_ozel' => 'SRV',
        'yil'         => '2026',
        'format'      => 'sayi_zf',
    ],
];

class NumaraManager
{
    /** Prefix -> [tablo adı, kolon adı] eşlemesi */
    private static function modelVeAlan(string $prefix): ?array
    {
        $map = [
            'FAT' => ['faturalar', 'fatura_no'],
            'EAR' => ['faturalar', 'fatura_no'],
            'STM' => ['makbuzlar', 'makbuz_no'],
            'ALM' => ['makbuzlar', 'makbuz_no'],
            'THM' => ['makbuzlar', 'makbuz_no'],
            'ODM' => ['makbuzlar', 'makbuz_no'],
            'VT'  => ['teklifler', 'teklif_no'],
            'SRV' => ['teknik_servis', 'servis_no'],
        ];
        return $map[$prefix] ?? null;
    }

    private static function formatla(string $prefixOzel, string $yil, int $sayi, string $format, int $haneSayisi = 4): string
    {
        if ($format === 'sayi') {
            return $prefixOzel . $yil . str_pad((string)$sayi, $haneSayisi, '0', STR_PAD_LEFT);
        }
        return $prefixOzel . '-' . $yil . '-' . str_pad((string)$sayi, 4, '0', STR_PAD_LEFT);
    }

    /** O prefix+yıl desenine uyan kayıtlar içindeki en yüksek numarayı bulur */
    private static function enYuksekNumara(PDO $pdo, string $tablo, string $kolon, string $prefixOzel, string $yil): int
    {
        if (str_contains($prefixOzel, 'MED') || str_contains($prefixOzel, 'GIB')) {
            $pattern = $prefixOzel . $yil;
        } else {
            $pattern = $prefixOzel . '-' . $yil . '-';
        }
        $patternLen = mb_strlen($pattern);

        $stmt = $pdo->prepare("SELECT {$kolon} FROM {$tablo} WHERE {$kolon} LIKE ?");
        $stmt->execute([$pattern . '%']);

        $maxNum = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $deger) {
            $suffix = mb_substr((string)$deger, $patternLen);
            if ($suffix !== '' && ctype_digit($suffix)) {
                $val = (int)$suffix;
                if ($val > $maxNum) $maxNum = $val;
            }
        }
        return $maxNum;
    }

    /** Sıradaki numarayı hesaplar (veritabanına yazmaz) */
    public static function getNext(PDO $pdo, string $prefix): string
    {
        $ayar = NUMARA_AYARLARI[$prefix] ?? null;
        if (!$ayar) {
            return "{$prefix}-2026-0001";
        }

        $yil = $ayar['yil'];
        $prefixOzel = $ayar['prefix_ozel'];
        $format = $ayar['format'];
        $haneSayisi = $ayar['hane_sayisi'] ?? 4;

        $modelVeAlan = self::modelVeAlan($prefix);
        if (!$modelVeAlan) {
            return self::formatla($prefixOzel, $yil, 1, $format, $haneSayisi);
        }
        [$tablo, $kolon] = $modelVeAlan;

        $maxNum = self::enYuksekNumara($pdo, $tablo, $kolon, $prefixOzel, $yil);
        $baslangic = $ayar['baslangic'] ?? 1;
        if ($maxNum < $baslangic - 1) {
            $maxNum = $baslangic - 1;
        }

        return self::formatla($prefixOzel, $yil, $maxNum + 1, $format, $haneSayisi);
    }

    /**
     * Verilen numaranın kullanılabilir olup olmadığını kontrol eder.
     * NOT: Orijinal davranışla aynı şekilde HİÇBİR ŞEYİ KALICI OLARAK
     * DEĞİŞTİRMEZ - sadece çakışma kontrolü yapar.
     * @return array{0: bool, 1: string} [başarı, mesaj]
     */
    public static function setNext(PDO $pdo, string $prefix, int $yeniNumara): array
    {
        $ayar = NUMARA_AYARLARI[$prefix] ?? null;
        if (!$ayar) {
            return [false, 'Geçersiz prefix!'];
        }

        $yil = $ayar['yil'];
        $prefixOzel = $ayar['prefix_ozel'];
        $format = $ayar['format'];
        $haneSayisi = $ayar['hane_sayisi'] ?? 4;

        $modelVeAlan = self::modelVeAlan($prefix);
        if (!$modelVeAlan) {
            return [false, 'Geçersiz prefix!'];
        }
        [$tablo, $kolon] = $modelVeAlan;

        $fullNumber = self::formatla($prefixOzel, $yil, $yeniNumara, $format, $haneSayisi);

        $stmt = $pdo->prepare("SELECT id FROM {$tablo} WHERE {$kolon} = ?");
        $stmt->execute([$fullNumber]);
        if ($stmt->fetch()) {
            return [false, "Bu numara zaten kullanılıyor: {$fullNumber}"];
        }

        return [true, "Numara {$yeniNumara} olarak ayarlandı!"];
    }

    /** Tek bir prefix hakkında bilgi döndürür (Numara Yönetimi ekranı için) */
    public static function getInfo(PDO $pdo, string $prefix): ?array
    {
        $ayar = NUMARA_AYARLARI[$prefix] ?? null;
        if (!$ayar) return null;

        $yil = $ayar['yil'];
        $prefixOzel = $ayar['prefix_ozel'];
        $format = $ayar['format'];
        $haneSayisi = $ayar['hane_sayisi'] ?? 4;

        $modelVeAlan = self::modelVeAlan($prefix);
        if (!$modelVeAlan) return null;
        [$tablo, $kolon] = $modelVeAlan;

        $maxNum = self::enYuksekNumara($pdo, $tablo, $kolon, $prefixOzel, $yil);
        $baslangic = $ayar['baslangic'] ?? 1;
        if ($maxNum < $baslangic - 1) $maxNum = $baslangic - 1;

        $siradaki = $maxNum + 1;
        $toplamKayit = (int)$pdo->query("SELECT COUNT(*) FROM {$tablo}")->fetchColumn();

        return [
            'prefix'          => $prefix,
            'aciklama'        => $ayar['aciklama'] ?? $prefix,
            'yil'             => $yil,
            'mevcut_sayi'     => $maxNum,
            'siradaki'        => $siradaki,
            'baslangic'       => $baslangic,
            'hane_sayisi'     => $haneSayisi,
            'siradaki_format' => self::formatla($prefixOzel, $yil, $siradaki, $format, $haneSayisi),
            'toplam_kayit'    => $toplamKayit,
        ];
    }

    /** Tüm prefix'lerin bilgilerini döndürür */
    public static function getAllInfo(PDO $pdo): array
    {
        $result = [];
        foreach (array_keys(NUMARA_AYARLARI) as $prefix) {
            $info = self::getInfo($pdo, $prefix);
            if ($info) $result[] = $info;
        }
        return $result;
    }

    /**
     * DİKKAT - GERİ ALINAMAZ: Bir prefix'e ait TÜM kayıtları id sırasına göre
     * yeniden numaralandırır (Flask: NumaraManager.reset_all()). Bu, mevcut
     * fatura/makbuz/teklif/servis numaralarının üzerine yazar. Sadece
     * Numara Yönetimi ekranındaki açık bir onay sonrası çağrılmalıdır.
     *
     * @return array<string> Her prefix için kaç kaydın güncellendiğini özetleyen mesajlar
     */
    public static function resetAll(PDO $pdo): array
    {
        $results = [];

        foreach (NUMARA_AYARLARI as $prefix => $ayar) {
            $baslangic = $ayar['baslangic'] ?? 1;
            $prefixOzel = $ayar['prefix_ozel'];
            $yil = $ayar['yil'];
            $format = $ayar['format'];
            $haneSayisi = $ayar['hane_sayisi'] ?? 4;

            $modelVeAlan = self::modelVeAlan($prefix);
            if (!$modelVeAlan) continue;
            [$tablo, $kolon] = $modelVeAlan;

            $stmt = $pdo->prepare("SELECT id FROM {$tablo} ORDER BY id");
            $stmt->execute();
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $update = $pdo->prepare("UPDATE {$tablo} SET {$kolon} = ? WHERE id = ?");
            foreach ($ids as $i => $id) {
                $yeniNo = self::formatla($prefixOzel, $yil, $baslangic + $i, $format, $haneSayisi);
                $update->execute([$yeniNo, $id]);
            }

            $results[] = "{$prefix}: " . count($ids) . ' kayıt güncellendi';
        }

        return $results;
    }
}

/** Flask: generate_fatura_no() */
function generate_fatura_no_nm(PDO $pdo): string
{
    return NumaraManager::getNext($pdo, 'FAT');
}

/** Flask: generate_earsiv_no() */
function generate_earsiv_no_nm(PDO $pdo): string
{
    return NumaraManager::getNext($pdo, 'EAR');
}

/** Flask: generate_makbuz_no(tur) */
function generate_makbuz_no_nm(PDO $pdo, string $tur): string
{
    $prefixMap = [
        'SATIS'    => 'STM',
        'ALIS'     => 'ALM',
        'TAHSILAT' => 'THM',
        'ODEME'    => 'ODM',
        'IADE'     => 'İAM',
    ];
    $prefix = $prefixMap[$tur] ?? 'MKB';
    return NumaraManager::getNext($pdo, $prefix);
}

/** Flask: generate_teklif_no(tur) */
function generate_teklif_no_nm(PDO $pdo, string $tur): string
{
    $prefixMap = [
        'VERILEN' => 'VT',
        'ALINAN'  => 'AT',
    ];
    $prefix = $prefixMap[$tur] ?? 'TK';
    return NumaraManager::getNext($pdo, $prefix);
}

/** Flask: generate_servis_no() (numara_manager.py'deki, utils.py'deki basit olandan farklı) */
function generate_servis_no_nm(PDO $pdo): string
{
    return NumaraManager::getNext($pdo, 'SRV');
}
