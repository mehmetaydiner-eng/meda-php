<?php
/**
 * includes/functions.php
 * Python tarafındaki utils.py dosyasının birebir PHP karşılığı.
 */

/**
 * Türkçe karakterleri doğru şekilde BÜYÜK harfe çevirir (I/İ sorunu düzeltilmiş).
 * PHP'nin mb_strtoupper('tr_TR') fonksiyonu bunu zaten doğru yapar,
 * ama Flask tarafındaki mantığı birebir korumak için manuel eşleme kullanıyoruz.
 */
function turkce_upper(?string $text): string
{
    if ($text === null || $text === '') {
        return (string)$text;
    }

    // Önce İ -> I, i -> ı
    $text = str_replace('İ', 'I', $text);
    $text = str_replace('i', 'ı', $text);

    $map = [
        'ı' => 'I', 'ğ' => 'Ğ', 'ş' => 'Ş',
        'ö' => 'Ö', 'ç' => 'Ç', 'ü' => 'Ü',
    ];
    $text = strtr($text, $map);

    // Geri kalan (Türkçe olmayan) harfleri normal şekilde büyült
    return mb_strtoupper($text, 'UTF-8');
}

/**
 * Türkçe karakterleri doğru şekilde küçük harfe çevirir (I/İ sorunu düzeltilmiş).
 */
function turkce_lower(?string $text): string
{
    if ($text === null || $text === '') {
        return (string)$text;
    }

    $text = str_replace('I', 'ı', $text);
    $text = str_replace('İ', 'i', $text);

    $map = [
        'Ğ' => 'ğ', 'Ş' => 'ş',
        'Ö' => 'ö', 'Ç' => 'ç', 'Ü' => 'ü',
    ];
    $text = strtr($text, $map);

    return mb_strtolower($text, 'UTF-8');
}

/**
 * Her kelimenin ilk harfini büyük yapar (Türkçe kurallarına göre).
 */
function turkce_title(?string $text): string
{
    if ($text === null || $text === '') {
        return (string)$text;
    }

    $words = preg_split('/\s+/u', trim($text));
    $result = [];
    foreach ($words as $word) {
        if ($word === '') continue;
        $first = turkce_upper(mb_substr($word, 0, 1, 'UTF-8'));
        $rest  = mb_strlen($word, 'UTF-8') > 1 ? turkce_lower(mb_substr($word, 1, null, 'UTF-8')) : '';
        $result[] = $first . $rest;
    }
    return implode(' ', $result);
}

/**
 * Sadece İ -> I, i -> ı dönüşümü (kullanıcı adı normalizasyonu için).
 */
function normalize_turkish(?string $text): string
{
    if ($text === null || $text === '') {
        return (string)$text;
    }
    $text = str_replace('İ', 'I', $text);
    $text = str_replace('i', 'ı', $text);
    return $text;
}

/** Güvenli float dönüşümü (boş/geçersiz değerlerde varsayılan döner) */
function safe_float($value, float $default = 0.0): float
{
    if ($value === null || $value === '') return $default;
    return is_numeric($value) ? (float)$value : $default;
}

/** Güvenli int dönüşümü */
function safe_int($value, int $default = 0): int
{
    if ($value === null || $value === '') return $default;
    return is_numeric($value) ? (int)(float)$value : $default;
}

/** 13 haneli EAN-13 tarzı barkod üretir (Türkiye 869 prefix'i ile) */
function generate_barkod(): string
{
    $prefix = '869';
    $random = '';
    for ($i = 0; $i < 9; $i++) $random .= random_int(0, 9);
    $check = (string)random_int(0, 9);
    return $prefix . $random . $check;
}

/** Basit teknik servis numarası (SRV-YYYYMM-XXXX) */
function generate_servis_no(): string
{
    $tarih = date('Ym');
    $rastgele = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    return "SRV-{$tarih}-{$rastgele}";
}

/** Basit fatura numarası (FAT-YYYYMM-XXXX) - asıl numaralandırma numara_manager.php içinde */
function generate_fatura_no_basit(): string
{
    $tarih = date('Ym');
    $rastgele = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    return "FAT-{$tarih}-{$rastgele}";
}

/** Vergi tutarını hesaplar */
function hesapla_vergi(float $ana_tutar, float $vergi_orani = 18): float
{
    return $ana_tutar * ($vergi_orani / 100);
}

/** Vergi dahil toplamı hesaplar */
function hesapla_toplam(float $ana_tutar, float $vergi_orani = 18): float
{
    return $ana_tutar + hesapla_vergi($ana_tutar, $vergi_orani);
}

/** Tutarı para birimine göre formatlar (1.234,56 ₺ / $1,234.56 / €1,234.56) */
function format_para(float $tutar, string $doviz = 'TL'): string
{
    switch ($doviz) {
        case 'TL':
            return number_format($tutar, 2, ',', '.') . ' ₺';
        case 'USD':
            return '$' . number_format($tutar, 2, '.', ',');
        case 'EUR':
            return '€' . number_format($tutar, 2, '.', ',');
        default:
            return number_format($tutar, 2, ',', '.') . ' ' . $doviz;
    }
}

/** DateTime nesnesini / string'i dd.mm.YYYY formatına çevirir */
function format_tarih($tarih, string $format = 'd.m.Y'): string
{
    if (!$tarih) return '-';
    if (is_string($tarih)) {
        try {
            $tarih = new DateTime($tarih);
        } catch (Exception $e) {
            return '-';
        }
    }
    return $tarih->format($format);
}

/** Baştaki/sondaki boşlukları siler, çoklu boşlukları teke indirir */
function temizle_text(?string $text): string
{
    if (!$text) return '';
    $text = trim($text);
    return preg_replace('/\s+/', ' ', $text);
}

/** Türkçe karakterleri İngilizce karşılıklarına çevirip URL-uyumlu slug üretir */
function slugify(?string $text): string
{
    if (!$text) return '';

    $map = [
        'ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g',
        'ı' => 'i', 'I' => 'i', 'i' => 'i', 'İ' => 'i',
        'ö' => 'o', 'Ö' => 'o', 'ş' => 's', 'Ş' => 's',
        'ü' => 'u', 'Ü' => 'u',
    ];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-zA-Z0-9\s-]/', '', $text);
    $text = strtolower($text);
    $text = str_replace(' ', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

/** E-posta doğrulama */
function is_valid_email(?string $email): bool
{
    if (!$email) return false;
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** Telefon doğrulama (en az 10 haneli rakam) */
function is_valid_phone(?string $phone): bool
{
    if (!$phone) return false;
    $digits = preg_replace('/[^0-9]/', '', $phone);
    return strlen($digits) >= 10;
}

/** Çeşitli formatlardaki tarih string'ini DateTime nesnesine çevirir */
function parse_date(?string $date_str): ?DateTime
{
    if (!$date_str) return null;

    $formats = ['Y-m-d', 'd.m.Y', 'd/m/Y', 'd-m-Y', 'Ymd'];
    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $date_str);
        if ($dt !== false) return $dt;
    }
    return null;
}

/** Tarihi Türkçe ay/gün isimleriyle birlikte döndürür (base.html'deki üst menü tarihi) */
function turkce_tarih_uzun(?DateTime $tarih = null): string
{
    if ($tarih === null) $tarih = new DateTime('now');

    $aylar = [
        'January' => 'Ocak', 'February' => 'Şubat', 'March' => 'Mart',
        'April' => 'Nisan', 'May' => 'Mayıs', 'June' => 'Haziran',
        'July' => 'Temmuz', 'August' => 'Ağustos', 'September' => 'Eylül',
        'October' => 'Ekim', 'November' => 'Kasım', 'December' => 'Aralık',
    ];
    $gunler = [
        'Monday' => 'Pazartesi', 'Tuesday' => 'Salı', 'Wednesday' => 'Çarşamba',
        'Thursday' => 'Perşembe', 'Friday' => 'Cuma', 'Saturday' => 'Cumartesi',
        'Sunday' => 'Pazar',
    ];

    $sonuc = $tarih->format('d F Y, l');
    $sonuc = strtr($sonuc, $aylar);
    $sonuc = strtr($sonuc, $gunler);
    return $sonuc;
}

/** HTML çıktısı için güvenli kaçış (XSS koruması) - Jinja2'nin otomatik escape'i yerine */
function e(?string $text): string
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

/** Hesap hareketi numarası üretir (app.py: generate_hareket_no) */
function generate_hareket_no(): string
{
    // NOT: Önceki sürüm sadece 'HRK' . date('YmdHis') döndürüyordu - saniye
    // hassasiyetinde. hareket_no alanı UNIQUE olduğu için, aynı saniye
    // içinde oluşan İKİNCİ bir hesap hareketi (örn. art arda yapılan iki
    // Hızlı İşlem) aynı hareket_no'yu üretip "duplicate key" hatası
    // veriyordu - bu da PDO transaction'ının SESSİZCE geri alınmasına
    // (rollback) yol açıyordu: fatura/makbuz hiç oluşmuyordu ama kullanıcı
    // sayfa yönlendirmesi (302) gördüğü için işlemin başarılı olduğunu
    // sanıyordu. Rastgele bir bileşen eklenerek çakışma riski ortadan
    // kaldırıldı (fatura_kaydet.php/teklif_kaydet.php'deki ürün kodu
    // üretiminde daha önce bulunup düzeltilen aynı hata sınıfı).
    return 'HRK' . date('YmdHis') . '-' . random_int(1000, 9999);
}

/**
 * Stok hareketi (defter) satırı ekler. Ürünün stok_miktari'nı GÜNCELLEMEZ -
 * sadece o değişikliği kayıt altına alır. Çağıran kod hem UPDATE'i hem bu
 * fonksiyonu çağırmalı (tek transaction içinde).
 *
 * @param float $miktar İşaretli: pozitif = stok girişi, negatif = stok çıkışı
 * @param int|null $cariId Bu hareketin ilişkili olduğu cari (satışta müşteri,
 *        alışta tedarikçi). Manuel stok düzeltmelerinde (stok_ekle/duzenle)
 *        cari yoktur, null geçilir.
 */
function stok_hareketi_ekle(
    PDO $pdo,
    int $urunId,
    string $hareketTuru,
    float $miktar,
    float $stokOncesi,
    float $stokSonrasi,
    ?string $referansNo = null,
    ?string $aciklama = null,
    ?int $cariId = null
): void {
    $stmt = $pdo->prepare(
        'INSERT INTO stok_hareketleri
            (urun_id, hareket_turu, miktar, stok_oncesi, stok_sonrasi, referans_no, aciklama, cari_id, created_by, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\',\'localtime\'))'
    );
    $username = is_logged_in() ? (current_user()['username'] ?? '') : '';
    $stmt->execute([$urunId, $hareketTuru, $miktar, $stokOncesi, $stokSonrasi, $referansNo, $aciklama, $cariId, $username]);
}

/**
 * Yüklenen bir ürün resmini doğrular, benzersiz bir isimle
 * public/assets/uploads/urunler/ klasörüne kaydeder.
 *
 * @param array $file $_FILES['urun_resmi'] gibi tek bir dosya girdisi
 * @param int $urunId Dosya adına eklenecek ürün id'si (benzersizlik için)
 * @return array{success: bool, filename: ?string, message: ?string}
 */
function urun_resim_yukle(array $file, int $urunId): array
{
    // Kullanıcı dosya seçmediyse sessizce "işlem yok" döndür (hata değil)
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => null, 'message' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'filename' => null, 'message' => 'Dosya yükleme hatası (kod: ' . $file['error'] . ')'];
    }

    $maxBoyut = 3 * 1024 * 1024; // 3 MB
    if ($file['size'] > $maxBoyut) {
        return ['success' => false, 'filename' => null, 'message' => "Dosya boyutu 3 MB'ı geçemez!"];
    }

    $izinliMimeler = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($izinliMimeler[$mime])) {
        return ['success' => false, 'filename' => null, 'message' => 'Sadece JPG, PNG, WEBP ya da GIF resim dosyaları yüklenebilir!'];
    }

    $uzanti = $izinliMimeler[$mime];
    $dosyaAdi = 'urun_' . $urunId . '_' . time() . '_' . random_int(1000, 9999) . '.' . $uzanti;
    $hedefKlasor = __DIR__ . '/../public/assets/uploads/urunler';
    $hedefYol = $hedefKlasor . '/' . $dosyaAdi;

    // NOT: Bu klasör daha sonradan eklendi - eğer proje dosyaları tek tek
    // kopyalandıysa (tüm zip yeniden açılmadıysa) bu boş klasör hiç
    // oluşmamış olabilir, bu da yüklemenin sessizce başarısız olmasına yol
    // açardı. Burada yoksa otomatik oluşturuluyor.
    if (!is_dir($hedefKlasor)) {
        @mkdir($hedefKlasor, 0755, true);
    }

    if (!is_dir($hedefKlasor) || !is_writable($hedefKlasor)) {
        return ['success' => false, 'filename' => null, 'message' => "Yükleme klasörü ({$hedefKlasor}) yok ya da yazılabilir değil. Klasörün var olduğundan ve yazma izni olduğundan emin olun."];
    }

    if (!move_uploaded_file($file['tmp_name'], $hedefYol)) {
        return ['success' => false, 'filename' => null, 'message' => 'Dosya kaydedilemedi (klasör izinlerini kontrol edin).'];
    }

    return ['success' => true, 'filename' => $dosyaAdi, 'message' => null];
}

/** Bir ürün resmini diskten siler (varsa) - hata sessizce yutulur (dosya zaten yoksa sorun değil) */
function urun_resim_sil(?string $dosyaAdi): void
{
    if (!$dosyaAdi) return;
    $yol = __DIR__ . '/../public/assets/uploads/urunler/' . basename($dosyaAdi);
    if (is_file($yol)) {
        @unlink($yol);
    }
}
