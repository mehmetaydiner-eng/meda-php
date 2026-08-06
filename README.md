# MEDA BİLGİSAYAR — ERP Sistemi

Bilgisayar/teknoloji satışı yapan bir işletme için; **fatura, stok, cari
(müşteri/tedarikçi), teknik servis, kasa/hesap, teklif ve prim** takibini
tek bir yerden yöneten, tamamen Türkçe bir ERP sistemi.

Bu proje, orijinal bir Flask (Python) uygulamasının **saf PHP + PDO +
SQLite** ile yeniden yazılmış hâlidir. Framework kullanılmıyor - sade,
okunabilir PHP dosyaları ve doğrudan SQL sorguları tercih edildi.

## İçindekiler

- [Özellikler](#özellikler)
- [Teknoloji](#teknoloji)
- [Kurulum](#kurulum)
- [Klasör Yapısı](#klasör-yapısı)
- [Önemli Kavramlar](#önemli-kavramlar)
- [Bilinen Sınırlamalar](#bilinen-sınırlamalar--açık-konular)
- [Değişiklik Geçmişi](#değişiklik-geçmişi)

## Özellikler

### 📇 Cariler (Müşteri / Tedarikçi / Personel)
- Müşteri, tedarikçi ve personel (prim alacak kişi) olarak ayrı türlerde
  cari tanımlama
- Her carinin borç/alacak bakiyesi, hesap hareketi geçmişi, fatura/makbuz
  geçmişi tek sayfada
- Cari adı sistemde nerede görünürse görünsün (fatura, makbuz, teklif,
  stok hareketi, prim listesi...) tıklanabilir - direkt o carinin detayına
  götürür

### 📦 Stok (Ürünler)
- Ürün kartı: kod, barkod, seri no, kategori, alış/satış fiyatı (TL/USD/EUR),
  min/max stok, **ürün resmi**
- **Kategoriler artık tanımlı bir listeden (dropdown) seçiliyor** - serbest
  metin değil. `Kategori Yönetimi` sayfasından (`kategori_yonetim.php`)
  yeni kategori eklenip yönetilebiliyor
- Barkod etiketi oluşturma ve yazdırma
- Detaylı stok hareket geçmişi (hangi işlem, ne zaman, kaç adet, hangi
  cariyle ilişkili)

### ⚡ Hızlı İşlem (Satış / Alış / İade)
- Ürün arayıp sepete ekleme, miktar/fiyat/iskonto/KDV satır bazında
  düzenlenebilir
- **Çoklu kanal ödeme dağılımı**: bir satışı nakit + havale + kredi kartı
  gibi birden fazla kasaya bölerek ödeme, ödenmeyen kısım otomatik
  veresiye/borç olarak izlenir
- Satış sonrası **prim popup'ı**: personele satış primi tanımlama
- İşlem tamamlandığında ilgili carinin detay sayfasına yönlendirme

### 🧾 Fatura / Makbuz / Teklif
- E-Fatura/E-Arşiv tipinde fatura oluşturma, GİB uyumlu UBL-TR 2.1 XML
  üretimi
- **GİB e-Fatura XML'inden ALIŞ faturası içe aktarma**: tedarikçiden gelen
  e-Fatura XML'i yüklenip ayrıştırılır, cari (VKN'ye göre) ve ürünler
  (stok koduna göre) otomatik eşleştirilir/oluşturulur, satış fiyatı
  alış fiyatının %30 fazlası olarak önerilir - **kaydetmeden önce
  düzenlenebilir bir önizleme ekranı** gösterilir
- Satış ve alış faturaları, çoklu ödeme dağılımı ile
- Makbuz (satış/alış/tahsilat/ödeme türlerinde), teklif oluşturma ve
  yazdırılabilir çıktılar

### 🔧 Teknik Servis
- Arıza kaydı, malzeme/işçilik takibi, garanti durumu, servis çıktısı
  (barkod dahil)

### 🏦 Hesaplar / Kasa
- Banka, kasa, POS, komisyon, veresiye türünde hesap tanımlama
- Hesaplar **silinmez, sadece pasife alınır** - geçmiş hareketler ve
  raporlar hiçbir zaman bozulmaz
- Kasa/hesap hareket geçmişi, günlük/aylık kasa raporu

### 💰 Primler
- Personele satıştan sabit tutar veya oran üzerinden prim tanımlama
- Prim ödemesi gerçek bir kasa hareketi oluşturur (kasa bakiyesini
  gerçekten etkiler)

### 📋 Diğer
- Yapılacaklar listesi, evrak numarası yönetimi, tüm belge türlerini
  (fatura/makbuz/teklif/servis) tek listede gösteren Evraklar sayfası

## Teknoloji

- **PHP 8+** (PDO, framework yok)
- **SQLite** (dosya tabanlı, ayrı bir veritabanı sunucusu gerektirmez)
- **Bootstrap** + özel CSS/JS (tema: koyu/açık/gri, tamamen özelleştirilmiş)
- Apache/Nginx (ya da PHP'nin kendi geliştirme sunucusu)

## Kurulum

### XAMPP ile (önerilen)

1. Bu klasörü `htdocs/meda-php/` olarak kopyala.
2. XAMPP Control Panel'den sadece **Apache**'yi başlat (MySQL'e gerek yok
   - proje SQLite kullanıyor).
3. XAMPP'in PHP kurulumunda `pdo_sqlite` eklentisi genelde zaten aktiftir;
   emin değilsen `php.ini`'de `extension=pdo_sqlite` satırının başındaki
   `;` işaretini kaldırıp Apache'yi yeniden başlat.
4. Tarayıcıda `http://localhost/meda-php/public/register.php` aç, bir
   hesap oluştur. Bu ilk istekte `data/meda.sqlite` **otomatik oluşturulup
   şema otomatik yüklenir** - ayrıca bir kurulum komutu çalıştırmana gerek
   yok.
5. `login.php` ile giriş yap.

### Genel (XAMPP dışı) kurulum

1. `data/` klasörünün web sunucusu tarafından yazılabilir olduğundan emin
   ol (veritabanı dosyası buraya otomatik oluşturulacak).
2. Web sunucunun **DocumentRoot'unu `public/` klasörüne** yönlendir.
   `config/`, `includes/`, `sql/`, `data/` klasörleri web'den erişilemez
   olmalı (zaten `public/` dışında tutuldu, bu yüzden güvenli).
3. `config/config.php` içindeki `BASE_URL` otomatik algılanır. Sadece
   klasör adın `meda-php` değilse `APP_BASE_PATH` sabitini güncelle.
4. İlk sayfa isteğinde veritabanı otomatik kurulur.

### Yönetici kullanıcı yapma

Yeni kayıtlar varsayılan olarak `user` rolüyle açılır. Bir kullanıcıyı
admin yapmak için (bazı geri alınamaz işlemler - örn. evrak numarasını
tamamen sıfırlama - sadece admin'e açıktır):

**Web üzerinden (önerilen):** `kullanici_yonetim.php` sayfasını aç -
navbar'daki 🛡️ ikonundan ulaşabilirsin. Sistemde henüz hiç admin yoksa bu
sayfa herkese açıktır (ilk admin'i atayabilesin diye); en az bir admin
oluştuktan sonra sadece admin'ler erişebilir. Tek admin kendi yetkisini
kaldıramaz - böylece kimse admin işlemlerine erişemez hale gelmiyor.

**Komut satırından (eski yöntem, hâlâ çalışır):**
```bash
php sql/make_admin.php KULLANICI_ADI
```

## Klasör Yapısı

```
php-project/
├── config/
│   ├── config.php       # Site ayarları, BASE_URL, oturum ayarları
│   └── database.php     # PDO/SQLite bağlantısı (veritabanını otomatik kurar)
├── includes/
│   ├── functions.php    # Türkçe karakter işleme, ortak yardımcı fonksiyonlar
│   ├── auth.php         # Oturum/giriş yönetimi, CSRF koruması
│   ├── header.php       # Ortak sayfa üstü (navbar, tema)
│   └── footer.php       # Ortak sayfa altı (script yüklemeleri)
├── public/              # Web sunucusunun DocumentRoot'u BURASI olmalı
│   ├── *.php            # Her sayfa kendi dosyasında
│   ├── api/             # AJAX uç noktaları
│   └── assets/          # CSS, JS, fontlar, yüklenen ürün resimleri
├── sql/
│   ├── schema.sql        # SQLite tablo şeması (otomatik yüklenir)
│   ├── make_admin.php    # Bir kullanıcıyı admin yapan CLI betiği
│   └── *.sql, *.php      # Eski MySQL dönemine ait, artık gerekli olmayan
│                         # dosyalar (üstlerinde bunu belirten not var)
└── data/
    └── meda.sqlite       # Veritabanı dosyası (ilk çalıştırmada otomatik oluşur)
```

## Önemli Kavramlar

### Borç / Alacak mantığı

Her carinin tek bir `bakiye` alanı vardır ve işareti şunu ifade eder:

- **Negatif bakiye → BORÇ**: cari (müşteri) bize borçlu, ondan tahsil
  edeceğiz. Kırmızı ve "BORÇ" etiketiyle gösterilir.
- **Pozitif bakiye → ALACAK**: biz cariye (genelde tedarikçiye) borçluyuz,
  ona ödeyeceğiz. Yeşil ve "ALACAK" etiketiyle gösterilir.

**Bakiye sadece ödenmeyen (kalan) tutarı yansıtır** - bir satışta tam ödeme
alınırsa cari bakiyesi hiç değişmez (ne borç ne alacak oluşur). Kısmi
ödemede sadece kalan kısım bakiyeye yansır.

### Çoklu kanal ödeme dağılımı

Hızlı İşlem, Fatura Oluştur ve Makbuz Oluştur'un üçünde de bir işlem
birden fazla ödeme kanalına/kasasına bölünebilir (örn. 1500 TL'lik bir
satışta 500 TL nakit + 500 TL havale + 500 TL kredi kartı). Her ödeme
satırı kendi kasasının bakiyesini günceller ve ayrı bir hesap hareketi
(TAHSİLAT/ÖDEME) oluşturur. Ödenmeyen kalan otomatik olarak cari
bakiyesine borç/alacak olarak yansır.

**VERESİYE** de bir ödeme türü seçeneğidir - bir satırda VERESİYE
seçilince o satırın Hesap/Kasa seçimi devre dışı kalır (çünkü gerçek bir
kasa hareketi yok), tutarı hiçbir kasayı etkilemeden doğrudan kalan/borca
eklenir. Bu tutarın gerçek tahsilatı/ödemesi daha sonra Cari Detay'daki
"Yeni Hareket" ekranından (kasa seçimi zorunlu) yapılır. `VERESİYE` **türü
hesaplar** (Hesap Ekle'de tanımlanan) ise bilinçli olarak ödeme
seçicilerinde görünmez - karışıklık olmasın diye.

### Hesap/Kasa silinmez, pasife alınır

Bir kasa/hesap asla silinemez - sadece "pasife alınabilir". Pasif bir
hesap yeni işlem ekranlarının seçim listelerinden kaybolur ama geçmiş
hareketleri ve raporları hiçbir zaman bozulmaz.

### Prim sistemi

"Prim Verilecek Kişi" kavramı ayrı bir tablo yerine Cariler alt yapısı
üzerinden çalışır - bir cariye "PERSONEL" türü verilerek prim alabilecek
biri olarak işaretlenir. Satış sonrası çıkan popup'tan sabit tutar ya da
satış tutarının bir oranı olarak prim tanımlanır; prim ödemesi gerçek bir
kasa hareketi oluşturur.

## Bilinen Sınırlamalar / Açık Konular

- **Fatura İptal**, stok/bakiyeyi otomatik geri almaz (bilinçli tasarım -
  bir faturanın hangi yoldan oluştuğunu kesin ayırt eden bir alan yok,
  otomatik geri alma yanlış yöne stok değiştirme riski taşırdı). İptal
  sadece durumu değiştirir, gerekirse manuel düzeltme yapılmalı.
- **Evrak No modalı**: gerçek bir "sıradaki numara" sayaç tablosu yok -
  `NumaraManager` mevcut kayıtlar arasında en yükseği bulup bir sonrakini
  hesaplıyor. İleride ayrı bir sayaç tablosu eklenebilir.
- **Web sitesi / e-ticaret entegrasyonu**: gelecekte bu ERP'nin ürün/stok
  verisinin bir web sitesinin satış vitrinine bağlanması planlanıyor
  (API/senkronizasyon köprüsü ile) - henüz detaylar netleşmedi. Ürün
  resmi ekleme özelliği bunun ilk adımı olarak düşünüldü.
- **Döviz konusu**: kısmen çözüldü (Hızlı İşlem sepetinde USD/EUR fiyatlı
  ürünler canlı kur üzerinden TL'ye çevriliyor) ama başka bir eksiklik
  daha var, detayı henüz netleşmedi.
- **GİB e-Fatura XML'i (`includes/fatura_xml.php`) - kullanım amacı henüz
  netleşmedi.** Bu özelliğin (a) sadece dahili görüntü/arşiv için mi,
  yoksa (b) gerçekten bir Özel Entegratör'e bağlanıp GİB'e gönderilecek
  mi kullanılacağı henüz netleşmedi (Efe'ye soruldu, "not edilsin" dendi -
  sıradaki konuşmada netleşince ele alınacak). Bu ayrım önemli çünkü:
  gerçek gönderim için XML'in ayrıca **mali mühür/XAdES-BES ile
  imzalanması** ve bir Özel Entegratör (Uyumsoft, Foriba, Logo, Mikro vb.)
  ya da GİB Portalı üzerinden iletilmesi gerekiyor - şu anki kod sadece
  XML'i üretiyor, imzalama/iletim yapmıyor. Ayrıca **ALIŞ faturaları için
  bu akış kavramsal olarak uygun değil** (e-Fatura SATICI tarafından
  üretilir, alıcı taraf üretmez) - `InvoiceTypeCode=ALIS` GİB'in kod
  listesinde de geçerli bir değer değil. `ProfileID` alanındaki
  "TEMEL"/"TİCARİ" gibi kısa Türkçe etiketlerin GİB'in gerçek kodlarına
  ("TEMELFATURA"/"TICARIFATURA") çevrilmesi bu oturumda düzeltildi.
  Araştırma sonucunda: GİB Portalı'nda manuel fatura kesme, kendi web
  formuna elle veri girip orada imzalamayı gerektiriyor - dışarıdan
  hazır bir XML yükleyip imzalatma (en azından tekil/düşük hacimli
  kullanım için) belgelenmiş bir yol değil. Efe bunu GİB Portalı'nda
  bizzat deneyip netleştirecek.

## Değişiklik Geçmişi

Bu projenin geliştirme sürecindeki her adımın (hangi hatanın bulunup nasıl
düzeltildiği, hangi kararın neden alındığı dahil) detaylı teknik günlüğü
için **[CHANGELOG.md](CHANGELOG.md)** dosyasına bakın.
