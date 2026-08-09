<?php
/**
 * includes/numara_manager.php
 * Sadece numara_sayac tablosuna güvenir.
 * getNext(): sadece okur, artırmaz.
 * consumeNext(): okur, 1 artırır ve yeni değeri döndürür.
 * setNext(): manuel ayarlar.
 */

const NUMARA_AYARLARI = [
    'FAT' => ['baslangic'=>1, 'aciklama'=>'E-FATURA', 'prefix_ozel'=>'MED', 'yil'=>'2026', 'hane_sayisi'=>9, 'format'=>'sayi'],
    'EAR' => ['baslangic'=>1, 'aciklama'=>'E-ARŞİV', 'prefix_ozel'=>'GIB', 'yil'=>'2026', 'hane_sayisi'=>9, 'format'=>'sayi'],
    'STM' => ['baslangic'=>1, 'aciklama'=>'SATIŞ MAKBUZU', 'prefix_ozel'=>'STM', 'yil'=>'2026', 'format'=>'sayi_zf'],
    'ALM' => ['baslangic'=>1, 'aciklama'=>'ALIŞ MAKBUZU', 'prefix_ozel'=>'ALM', 'yil'=>'2026', 'format'=>'sayi_zf'],
    'THM' => ['baslangic'=>1, 'aciklama'=>'TAHSİLAT', 'prefix_ozel'=>'THM', 'yil'=>'2026', 'format'=>'sayi_zf'],
    'ODM' => ['baslangic'=>1, 'aciklama'=>'ÖDEME', 'prefix_ozel'=>'ODM', 'yil'=>'2026', 'format'=>'sayi_zf'],
    'VT'  => ['baslangic'=>1, 'aciklama'=>'TEKLİF (Verilen)', 'prefix_ozel'=>'VT', 'yil'=>'2026', 'format'=>'sayi_zf'],
    'SRV' => ['baslangic'=>1, 'aciklama'=>'TEKNİK SERVİS', 'prefix_ozel'=>'SRV', 'yil'=>'2026', 'format'=>'sayi_zf'],
    'SP'  => ['baslangic'=>1, 'aciklama'=>'SİPARİŞ', 'prefix_ozel'=>'SP', 'yil'=>'2026', 'format'=>'sayi_zf'],
];

class NumaraManager
{
    private static function formatla(array $ayar, int $sayi): string
    {
        $prefixOzel = $ayar['prefix_ozel'];
        $yil = $ayar['yil'];
        $haneSayisi = $ayar['hane_sayisi'] ?? 4;
        $format = $ayar['format'];

        if ($format === 'sayi') {
            return $prefixOzel . $yil . str_pad((string)$sayi, $haneSayisi, '0', STR_PAD_LEFT);
        }
        return $prefixOzel . '-' . $yil . '-' . str_pad((string)$sayi, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Sıradaki numarayı okur, artırmaz (önizleme için).
     */
    public static function getNext(PDO $pdo, string $prefix): string
    {
        $ayar = NUMARA_AYARLARI[$prefix] ?? null;
        if (!$ayar) return "{$prefix}-2026-0001";

        $stmt = $pdo->prepare("SELECT son_kullanilan_numara FROM numara_sayac WHERE prefix = ?");
        $stmt->execute([$prefix]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $current = ($row && (int)$row['son_kullanilan_numara'] > 0) ? (int)$row['son_kullanilan_numara'] : ($ayar['baslangic'] - 1);
        $next = $current + 1;
        return self::formatla($ayar, $next);
    }

    /**
     * Sıradaki numarayı okur, 1 artırır ve günceller, yeni değeri döndürür.
     * Evrak kaydedilirken kullanılır.
     */
    public static function consumeNext(PDO $pdo, string $prefix): string
    {
        $ayar = NUMARA_AYARLARI[$prefix] ?? null;
        if (!$ayar) return "{$prefix}-2026-0001";

        // Mevcut sayacı al
        $stmt = $pdo->prepare("SELECT son_kullanilan_numara FROM numara_sayac WHERE prefix = ?");
        $stmt->execute([$prefix]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $current = ($row && (int)$row['son_kullanilan_numara'] > 0) ? (int)$row['son_kullanilan_numara'] : ($ayar['baslangic'] - 1);

        // Artır
        $yeni = $current + 1;

        // Güncelle
        $stmt = $pdo->prepare("INSERT INTO numara_sayac (prefix, son_kullanilan_numara, updated_at)
                               VALUES (?, ?, datetime('now','localtime'))
                               ON CONFLICT(prefix) DO UPDATE SET
                               son_kullanilan_numara = excluded.son_kullanilan_numara,
                               updated_at = datetime('now','localtime')");
        $stmt->execute([$prefix, $yeni]);

        return self::formatla($ayar, $yeni);
    }

    /**
     * Manüel olarak sıradaki numarayı ayarlar (sayacı ayarlar, artırmaz).
     */
    public static function setNext(PDO $pdo, string $prefix, int $yeniNumara): array
    {
        $ayar = NUMARA_AYARLARI[$prefix] ?? null;
        if (!$ayar) return [false, 'Geçersiz prefix!'];

        $stmt = $pdo->prepare("INSERT INTO numara_sayac (prefix, son_kullanilan_numara, updated_at)
                               VALUES (?, ?, datetime('now','localtime'))
                               ON CONFLICT(prefix) DO UPDATE SET
                               son_kullanilan_numara = excluded.son_kullanilan_numara,
                               updated_at = datetime('now','localtime')");
        $stmt->execute([$prefix, $yeniNumara - 1]);

        return [true, "Sıradaki numara {$yeniNumara} olarak ayarlandı!"];
    }

    public static function getInfo(PDO $pdo, string $prefix): ?array
    {
        $ayar = NUMARA_AYARLARI[$prefix] ?? null;
        if (!$ayar) return null;

        $stmt = $pdo->prepare("SELECT son_kullanilan_numara FROM numara_sayac WHERE prefix = ?");
        $stmt->execute([$prefix]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $current = ($row && (int)$row['son_kullanilan_numara'] > 0) ? (int)$row['son_kullanilan_numara'] : ($ayar['baslangic'] - 1);
        $siradaki = $current + 1;

        return [
            'prefix' => $prefix,
            'aciklama' => $ayar['aciklama'],
            'yil' => $ayar['yil'],
            'mevcut_sayi' => $current,
            'siradaki' => $siradaki,
            'siradaki_format' => self::formatla($ayar, $siradaki),
            'toplam_kayit' => 0,
        ];
    }

    public static function getAllInfo(PDO $pdo): array
    {
        $result = [];
        foreach (array_keys(NUMARA_AYARLARI) as $prefix) {
            $info = self::getInfo($pdo, $prefix);
            if ($info) $result[] = $info;
        }
        return $result;
    }

    public static function resetAll(PDO $pdo): array
    {
        $pdo->exec("DELETE FROM numara_sayac");
        return ['Tüm sayaçlar sıfırlandı'];
    }
}

// ---- KULLANIM KOLAYLIĞI İÇİN FONKSİYONLAR ----
// consumeNext: evrak kaydedilirken kullanılır (sayacı artırır)
function generate_fatura_no_nm(PDO $pdo): string { return NumaraManager::consumeNext($pdo, 'FAT'); }
function generate_earsiv_no_nm(PDO $pdo): string { return NumaraManager::consumeNext($pdo, 'EAR'); }
function generate_makbuz_no_nm(PDO $pdo, string $tur): string {
    $map = ['SATIS'=>'STM', 'ALIS'=>'ALM', 'TAHSILAT'=>'THM', 'ODEME'=>'ODM', 'IADE'=>'İAM'];
    $prefix = $map[$tur] ?? 'MKB';
    return NumaraManager::consumeNext($pdo, $prefix);
}
function generate_teklif_no_nm(PDO $pdo, string $tur): string {
    $map = ['VERILEN'=>'VT', 'ALINAN'=>'AT'];
    $prefix = $map[$tur] ?? 'TK';
    return NumaraManager::consumeNext($pdo, $prefix);
}
function generate_servis_no_nm(PDO $pdo): string { return NumaraManager::consumeNext($pdo, 'SRV'); }
function generate_siparis_no(PDO $pdo): string { return NumaraManager::consumeNext($pdo, 'SP'); }

// preview: sayfa yüklenirken önizleme için (artırmaz)
function preview_fatura_no(PDO $pdo): string { return NumaraManager::getNext($pdo, 'FAT'); }
function preview_earsiv_no(PDO $pdo): string { return NumaraManager::getNext($pdo, 'EAR'); }
function preview_makbuz_no(PDO $pdo, string $tur): string {
    $map = ['SATIS'=>'STM', 'ALIS'=>'ALM', 'TAHSILAT'=>'THM', 'ODEME'=>'ODM', 'IADE'=>'İAM'];
    $prefix = $map[$tur] ?? 'MKB';
    return NumaraManager::getNext($pdo, $prefix);
}
function preview_teklif_no(PDO $pdo, string $tur): string {
    $map = ['VERILEN'=>'VT', 'ALINAN'=>'AT'];
    $prefix = $map[$tur] ?? 'TK';
    return NumaraManager::getNext($pdo, $prefix);
}
function preview_servis_no(PDO $pdo): string { return NumaraManager::getNext($pdo, 'SRV'); }
function preview_siparis_no(PDO $pdo): string { return NumaraManager::getNext($pdo, 'SP'); }
?>