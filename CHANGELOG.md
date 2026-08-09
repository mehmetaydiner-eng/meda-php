# Değişiklik Geçmişi / Geliştirme Günlüğü

Bu dosya, MEDA BİLGİSAYAR projesinin Flask'tan PHP'ye dönüştürülmesi ve
sonrasındaki geliştirme sürecinin **detaylı teknik günlüğüdür** - hangi
hatanın ne zaman bulunup nasıl düzeltildiği, hangi tasarım kararının neden
alındığı gibi bilgiler burada kronolojik sırayla (en eskiden en yeniye)
tutuluyor.

Projenin **güncel durumu, özellikleri ve kurulumu için** [README.md](README.md)
dosyasına bakın - bu dosya sadece geçmişe dönük referans ve "neden böyle
yapıldı" sorularının cevabı için tutuluyor.

---

# MEDA BİLGİSAYAR — PHP İskeleti (Aşama 1/N)

> Bu bölüm, projenin PHP'ye dönüştürüldüğü ilk günden kalan orijinal
> README içeriğidir - Flask'tan PHP'ye modül modül aktarım sürecinin
> dökümü olarak buradan itibaren başlıyor.

Bu, orijinal Flask (Python) uygulamasının **saf PHP + PDO + MySQL** karşılığının
temel iskeletidir. Tema (Bootstrap + özel CSS/JS + fontlar) **birebir aynı**
şekilde korunmuştur; sadece Jinja2 template mantığı PHP'ye çevrilmiştir.

## Bu aşamada tamamlananlar

| Flask (orijinal) | PHP karşılığı | Durum |
|---|---|---|
| `config.py` | `config/config.php`, `config/database.php` | ✅ |
| `models.py` (17 tablo) | `sql/schema.sql` | ✅ |
| `templates/base.html` | `includes/header.php` + `includes/footer.php` | ✅ |
| `templates/login.html` + `/login` route | `public/login.php` | ✅ |
| `templates/register.html` + `/register` route | `public/register.php` | ✅ |
| `/logout` route | `public/logout.php` | ✅ |
| `templates/index.html` + `/` route | `public/index.php` | ✅ |
| `/api/dashboard_data` | `public/api/dashboard_data.php` | ✅ |
| `/api/todo_count` | `public/api/todo_count.php` | ✅ |
| `utils.py` | `includes/functions.php` | ✅ |
| Flask-Login + werkzeug.security | `includes/auth.php` | ✅ |
| `static/css`, `static/js`, `static/fonts` | `public/assets/*` (aynen kopyalandı) | ✅ |
| **Cariler modülü (tamamı)** | | ✅ |
| `templates/cariler.html` + `/cariler` | `public/cariler.php` | ✅ |
| `templates/cari_ekle.html` + `/cari/ekle` | `public/cari_ekle.php` | ✅ |
| `templates/cari_duzenle.html` + `/cari/duzenle/<id>` | `public/cari_duzenle.php` | ✅ |
| `templates/cari_detay.html` + `/cari/detay/<id>` | `public/cari_detay.php` | ✅ |
| `/cari/sil/<id>` | `public/cari_sil.php` | ✅ |
| `/api/cari-ara` | `public/api/cari_ara.php` | ✅ |
| `/api/cari-detay/<id>` | `public/api/cari_detay.php` | ✅ |
| `/cari/ekle/ajax` | `public/api/cari_ekle_ajax.php` | ✅ |
| `/api/hesap-hareketi/ekle` | `public/api/hesap_hareketi_ekle.php` | ✅ |
| `/api/hesap-hareketi/sil/<id>` | `public/api/hesap_hareketi_sil.php` | ✅ |
| **Stok modülü (tamamı)** | | ✅ |
| `templates/stok_listesi.html` + `/stok` | `public/stok_listesi.php` | ✅ |
| `templates/stok_ekle.html` + `/stok/ekle` | `public/stok_ekle.php` | ✅ |
| `templates/stok_duzenle.html` + `/stok/duzenle/<id>` | `public/stok_duzenle.php` | ✅ |
| `/stok/sil/<id>` | `public/stok_sil.php` | ✅ |
| `/stok/barkod-olustur/<id>` | `public/stok_barkod_olustur.php` | ✅ |
| `/stok/barkod-bas/<id>` (orijinalde şablonu eksikti) | `public/stok_barkod_bas.php` (yeni, basit) | ✅ |
| `/api/stok-ara` | `public/api/stok_ara.php` | ✅ |
| `/stok/ekle/ajax` | `public/api/stok_ekle_ajax.php` | ✅ |
| **Numara Yönetimi (paylaşılan alt yapı)** | | ✅ |
| `numara_manager.py` (`NumaraManager` sınıfı) | `includes/numara_manager.php` | ✅ |
| `/api/numara-getir` | `public/api/numara_getir.php` | ✅ |
| `/api/numara-guncelle` | `public/api/numara_guncelle.php` | ✅ |
| **Hızlı İşlem modülü (tamamı)** | | ✅ |
| `templates/hizli_islem.html` + `/hizli-islem` | `public/hizli_islem.php` + `hizli_islem_script.php` | ✅ |
| `/hizli-islem/urun-ara` | `public/api/hizli_islem_urun_ara.php` | ✅ |
| `/hizli-islem/islem-yap` | `public/hizli_islem_yap.php` | ✅ |
| `/api/urun-detay/<id>` | `public/api/urun_detay.php` | ✅ |
| **Fatura modülü (tamamı)** | | ✅ |
| `templates/fatura_listesi.html` + `/faturalar` | `public/faturalar.php` | ✅ |
| `templates/fatura_olustur.html` + `/fatura/olustur`, `/fatura/duzenle/<id>` | `public/fatura_olustur.php` + `fatura_duzenle.php` | ✅ |
| `/fatura/kaydet` | `public/fatura_kaydet.php` | ✅ |
| `templates/alis_fatura_olustur.html` + `/alis-fatura/olustur` | `public/alis_fatura_olustur.php` | ✅ |
| `/fatura/alis/kaydet` | `public/fatura_alis_kaydet.php` | ✅ |
| `templates/fatura_cikti.html` + `/fatura/cikti/<id>` | `public/fatura_cikti.php` | ✅ |
| `utils/fatura_xml.py` (orijinalde ölü kod) | `includes/fatura_xml.php` (`FaturaXML` sınıfı) | ✅ |
| `fatura_xml_olustur` / `fatura_xml_indir` (orijinalde yoktu, link kırıktı) | `public/fatura_xml_olustur.php` + `fatura_xml_indir.php` | ✅ (yeni) |
| **Teknik Servis modülü (tamamı)** | | ✅ |
| `templates/teknik_servis_listesi.html` + `/teknik-servis` | `public/teknik_servis_listesi.php` | ✅ |
| `templates/teknik_servis_ekle.html` + `/teknik-servis/ekle` | `public/teknik_servis_ekle.php` | ✅ |
| `templates/teknik_servis_duzenle.html` + `/teknik-servis/duzenle/<id>` | `public/teknik_servis_duzenle.php` | ✅ |
| `/teknik-servis/sil/<id>` | `public/teknik_servis_sil.php` | ✅ |
| `templates/teknik_servis_cikti.html` + `/teknik-servis/cikti/<id>` | `public/teknik_servis_cikti.php` | ✅ |
| **Hesaplar / Kasa modülü (tamamı)** | | ✅ |
| `templates/hesaplar_listesi.html` + `/hesaplar` | `public/hesaplar_listesi.php` | ✅ |
| `templates/hesap_ekle.html` + `/hesap/ekle` | `public/hesap_ekle.php` | ✅ |
| `templates/hesap_duzenle.html` + `/hesap/duzenle/<id>` | `public/hesap_duzenle.php` | ✅ |
| `templates/hesap_hareketleri.html` + `/hesap/hareket/<id>` | `public/hesap_hareketleri.php` | ✅ |
| `templates/kasa_ana.html` + `/kasa` | `public/kasa_ana.php` | ✅ |
| `templates/kasa_rapor.html` + `/kasa/rapor` | `public/kasa_rapor.php` | ✅ |
| `/api/kasa/hareket/ekle` | `public/api/kasa_hareket_ekle.php` | ✅ |
| **Makbuzlar modülü (tamamı)** | | ✅ |
| `templates/makbuz_listesi.html` + `/makbuzlar` | `public/makbuzlar.php` | ✅ |
| `templates/makbuz_olustur.html` + `/makbuz/olustur/<tur>` | `public/makbuz_olustur.php?tur=` | ✅ |
| `templates/tahsilat_makbuzu.html` + `/tahsilat-makbuzu` | `public/tahsilat_makbuzu.php` | ✅ |
| `/tahsilat-makbuzu/kaydet` | `public/tahsilat_makbuzu_kaydet.php` | ✅ |
| `templates/makbuz_detay.html` + `/makbuz/detay/<id>` | `public/makbuz_detay.php` | ✅ |
| `/makbuz/iptal/<id>` | `public/makbuz_iptal.php` | ✅ |
| `makbuz_cikti.html` (orijinalde yoktu, link kırıktı) | `public/makbuz_cikti.php` | ✅ (yeni) |
| **Teklifler modülü (tamamı)** | | ✅ |
| `templates/teklif_listesi.html` + `/teklifler` | `public/teklifler.php` | ✅ |
| `templates/teklif_olustur.html` + `/teklif/olustur/<tur>/<tip>` | `public/teklif_olustur.php?id=` | ✅ |
| `/teklif/duzenle/<id>` | `public/teklif_duzenle.php` | ✅ |
| `/teklif/kaydet` (yeni AJAX handler) | `public/teklif_kaydet.php` | ✅ |
| `/teklif/durum/<id>/<durum>` | `public/teklif_durum.php` | ✅ |
| `/teklif/sil/<id>` | `public/teklif_sil.php` | ✅ |
| `templates/teklif_cikti.html`(yok, orijinalinden esinlenerek) + `/teklif/cikti/<id>` | `public/teklif_cikti.php` | ✅ |
| **Yapılacaklar modülü (tamamı)** | | ✅ |
| `templates/yapilacaklar.html` + `/yapilacaklar` | `public/yapilacaklar.php` | ✅ |
| `/yapilacaklar/ekle`, `/duzenle`, `/sil`, `/durum` (tek dosyada birleştirildi) | `public/todo_islem.php` | ✅ |
| **Numara Yönetimi / Evraklar / Finans (tamamı)** | | ✅ |
| `numara_yonetim.html` (orijinalde yoktu, link kırıktı) + `/numara-yonetim` | `public/numara_yonetim.php` | ✅ (yeni) |
| `/api/numara-ayarla` | `public/api/numara_ayarla.php` | ✅ |
| `/api/numara-sifirla` (geri alınamaz - tüm kayıtları yeniden numaralandırır) | `public/api/numara_sifirla.php` | ✅ |
| `templates/evraklar.html` + `/evraklar` | `public/evraklar.php` | ✅ |
| `vadeli_takip.html` (orijinalde yoktu) + `/vadeli-takip` | `public/vadeli_takip.php` | ✅ (yeni, bkz. not aşağıda) |
| `/api/komisyon/ekle` (orijinalde hiçbir arayüzden çağrılmıyordu) | `public/api/komisyon_ekle.php` | ✅ (sadakatle taşındı) |

**Dönüştürme tamamlandı** — orijinal Flask uygulamasının tüm route'ları ve
şablonları artık PHP'de karşılığını buldu. Sıradaki adım, modülleri tek tek
test edip bulunacak eksik/hataları gidermek (bkz. üstteki "AÇIK KONULAR").

### Numara Yönetimi / Evraklar / Vadeli Takip / Komisyon notları

- **Yine bir çökme hatası bulundu ve düzeltildi:** `/numara-yonetim` route'u
  var olan `NumaraManager.get_all_info()` alt yapısını kullanıyordu ama
  render ettiği `numara_yonetim.html` şablonu **arşivde hiç yoktu**. Zaten
  hazır olan `includes/numara_manager.php` kullanılarak gerçek, çalışan bir
  yönetim ekranı kuruldu (her belge türü için sıradaki numarayı gösterir,
  manuel ayarlama ve **geri alınamaz** "tümünü sıfırla" özelliği dahil).
- **Vadeli Takip modülü hakkında önemli bir dürüstlük notu:** `/vadeli-takip`
  route'unun render ettiği şablon da arşivde yoktu, AMA daha da önemlisi:
  `TahsilatPlanı`/`TahsilatTaksit` kayıtlarını **oluşturan hiçbir route/ekran
  da yoktu** - yani bu özellik orijinal uygulamada uçtan uca hiç
  bağlanmamıştı, tamamen ölü bir özellikti. Sayfanın kendisi (route mantığı +
  gerçek şablon) birebir kuruldu, ama "Taksitli Tahsilat Planı Oluştur" gibi
  yeni bir özellik icat edilmedi - bu, dönüştürme işinin kapsamı dışında
  kalan yeni bir geliştirme olurdu. Sayfa şu an dürüstçe boş görünüyor ve
  bunu kullanıcıya açıkça belirtiyor.
- **Komisyon API'si de benzer şekilde ölüydü:** `/api/komisyon/ekle` hiçbir
  şablon/butondan çağrılmıyordu. Birebir aynı mantıkla `api/komisyon_ekle.php`
  olarak taşındı - ileride bir "Komisyon Ekle" arayüzü yapılırsa hazır
  bekliyor, ama şu an onu tetikleyen bir buton yok.
- `evraklar.php` sayfası, sırasıyla Fatura/Makbuz/Teklif/Teknik Servis
  modüllerini birleştirip tek bir listede gösteriyor - bu modüllerin
  hepsi tamamlanmış olduğu için sorunsuz çalışıyor.

### Teklifler / Yapılacaklar modülü notları

**Not: Bu iki modülün ilk sürümü Efe tarafından yazıldı**, Claude sadece
gözden geçirip aşağıdaki tek noktayı düzeltti - geri kalan kod olduğu gibi
kabul edildi çünkü zaten proje standartlarına uygundu (BASE_URL kullanımı,
header/footer deseni, prepared statement'lar, todo'da user_id sahiplik
kontrolü vb.).

- **Bulunan ve düzeltilen tek risk - yine bir İ/I hatası ailesi:** İlk
  yazılan `teklif_olustur.php`/`teklif_kaydet.php`/`teklif_durum.php`/`teklifler.php`
  dosyaları `teklif_turu` için `'VERİLEN'` (noktalı Türkçe İ) ve `durum` için
  `'REDDEDİLDİ'` (noktalı İ) değerlerini kullanıyordu. Ancak orijinal Flask
  uygulaması (ve `sql/migrate_from_sqlite.php` ile aktarılacak eski veriler)
  bu alanlarda **noktasız ASCII karakterler** kullanıyordu: `'VERILEN'`,
  `'ALINAN'`, `'REDDEDILDI'`. Bu, projenin başından beri avladığımız tam o
  hata sınıfıydı (`turkce_upper()`'ın İ→I dönüşümü değil, doğrudan farklı
  karakter kullanımı) - eğer düzeltilmeseydi, eski SQLite'dan aktarılan
  teklifler ile PHP tarafında yeni oluşturulan tekliflerin durum/tür rozetleri
  ve filtreleri birbirini tutmayacaktı. Tüm dosyalarda bu iki alan ASCII'ye
  çevrildi (`VERILEN`/`REDDEDILDI`), `includes/numara_manager.php`'deki
  `generate_teklif_no_nm()` fonksiyonunun zaten beklediği anahtarlarla
  (`VERILEN`/`ALINAN`) tam uyumlu hale getirildi.
- `teklif_tipi` alanındaki `'HİZMET'`/`'STANDART'` değerleri kasıtlı olarak
  dokunulmadan bırakıldı - bu, orijinal Flask'ın tamamen farklı bir şema
  kullandığı (`SATIS_TEKLIFI`/`SERVIS_TEKLIFI`/`ALIS_TEKLIFI`) bir alan
  olduğu için eski veriyle zaten hiçbir örtüşmesi yok; dolayısıyla bir
  migrasyon çakışma riski taşımıyor.
- `todo_islem.php`'de başlık/açıklama alanlarına, uygulamanın geri kalanıyla
  (Cariler, Stok, vb.) ve orijinal Flask davranışıyla tutarlı olması için
  `turkce_upper()` eklendi (öncesinde sadece boşluk temizliği yapılıyordu).
- Orijinal Flask'ta teklif düzenleme/silme sadece `TASLAK`/`IPTAL` durumundaki
  tekliflerde izinliydi; bu PHP sürümünde o kısıtlama yok (herhangi bir
  durumdaki teklif düzenlenebilir/silinebilir). Bilinçli bir sadeleştirme
  olarak bırakıldı, istenirse kolayca eklenebilir.

### Makbuzlar modülü notları

- **Bir çökme hatası daha bulundu ve düzeltildi:** `makbuz_listesi.html`
  şablonu `url_for('makbuz_cikti', ...)` çağırıyordu ve bu route var olsa da
  (`app.py`'de tanımlıydı), render ettiği `makbuz_cikti.html` şablonu
  **arşivde hiç yoktu**. Yani orijinal uygulamada bir makbuzun "ÇIKTI"
  butonuna tıklamak `TemplateNotFound` (500) hatasına yol açıyordu - `fatura_xml`
  ve `stok_barkod` ile aynı türden bir eksiklik. `fatura_cikti.php` ile aynı
  desende gerçek, yazdırılabilir bir `makbuz_cikti.php` sayfası kuruldu.
- **Ayrıca düzeltilen bir küçük hata:** `tahsilat_makbuzu.html`'deki "Cari
  Bakiyesi" alanı, `/api/cari-detay` orijinalde `bakiye` alanını hiç
  döndürmediği için her zaman "0.00 ₺" gösteriyordu. Zaten var olan
  `api/cari_detay.php`'ye `bakiye` alanı eklendi (diğer hiçbir sayfayı
  bozmayan, geriye dönük uyumlu bir ekleme) - artık gerçek bakiye görünüyor.
- Makbuz oluşturma (ALIŞ/SATIŞ/TAHSİLAT/ÖDEME), Hızlı İşlem'dekiyle aynı
  mantıkla cari bakiyesi + hesap bakiyesi + hesap hareketi kaydını birlikte
  günceller (tek transaction içinde).

### Hesaplar / Kasa modülü notları

- `hesap_hareketleri.php` ve kasa'nın "Hesap Hareketi Ekle" özelliği,
  Cariler modülünde zaten kurulan `api/hesap_hareketi_ekle.php` /
  `hesap_hareketi_sil.php` API'lerini **aynen tekrar kullanıyor** - ayrı bir
  API yazmaya gerek kalmadı.
- **Bulunan ve iyileştirilen küçük bir orijinal eksiklik:** Flask tarafında
  `kasa_ana()` route'u, "Kasa Hareketi Ekle" modaline `cariler` listesini
  hiç göndermiyordu (route'un `render_template()` çağrısında `cariler`
  parametresi yoktu) - yani modal açıldığında cari dropdown'u her zaman
  boştu ("Seçin..." dışında hiçbir seçenek yoktu). Burada cariler listesi
  düzgün şekilde gönderildi, dropdown artık çalışıyor.
- **Bilinen bir orijinal UX sınırlaması (kısmen iyileştirildi):** Kasa ana
  sayfasındaki üstteki genel "NAKİT GİRİŞİ / NAKİT ÇIKIŞI" butonları, hangi
  kasaya işlem yapılacağını hiç sormuyordu (sadece işlem türünü ayarlıyordu,
  kasa seçimi boş kalıyordu - sadece tablodaki kasaya özel butonlar doğru
  çalışıyordu). Birden fazla kasa varsa modalde görünür bir kasa seçici
  eklendi; tek kasa varsa otomatik seçiliyor - böylece üstteki genel
  butonlar da artık kullanılabilir.

### Teknik Servis modülü notları

- Python tarafında `app.py`, hem `utils.py` hem `numara_manager.py`'den
  aynı isimde (`generate_servis_no`) bir fonksiyon import ediyordu; Python'da
  sonraki import bir öncekini ezdiği için gerçekte kullanılan **her zaman
  `numara_manager.py`'deki** (NumaraManager tabanlı, `SRV-2026-0001` formatlı)
  versiyondu - `utils.py`'deki basit versiyon hiç çalışmıyordu. PHP tarafında
  doğru olanı (`generate_servis_no_nm()`) kullandık.
- **Bilinen bir orijinal davranış (aynen korundu):** Servis kaydı
  **düzenlenirken** kullanılan malzemeler siline-yeniden eklenir ama stok
  miktarı bu sırada TEKRAR düşülmez (sadece ilk oluşturmada düşülür).
  Yani düzenleme ekranından malzeme ekleyip çıkarmak stok üzerinde etkisiz
  kalır - orijinal Flask kodunda da böyleydi.
- `teknik_servis_cikti.php` sayfasındaki barkod görseli harici bir servisten
  (tec-it.com) çekiliyor - bu, orijinal şablondaki davranışın aynısı
  (internet bağlantısı gerektirir, tıpkı Bootstrap/Font Awesome CDN'leri gibi).

### Fatura modülü notları

- **Kritik bir çökme hatası bulundu ve düzeltildi:** Orijinal Flask'ta
  `fatura_listesi.html` şablonu, hiç var olmayan iki route'a
  (`fatura_xml_olustur`, `fatura_xml_indir`) `url_for()` ile link veriyordu.
  Jinja2'de tanımsız bir endpoint için `url_for()` çağrısı `BuildError`
  fırlatır - yani **en az bir fatura varken orijinal `/faturalar` sayfası
  her zaman 500 hatasıyla çöküyordu.** Ayrıca `utils/fatura_xml.py`
  (292 satır, GİB uyumlu UBL-TR 2.1 XML üretici) ve `templates/fatura_xml.html`
  hiçbir yerden çağrılmıyordu - tamamen ölü/bağlanmamış kodtu.
  Bunları temel alarak `includes/fatura_xml.php` (`FaturaXML` sınıfı, PHP
  `DOMDocument` ile) ve gerçek çalışan `fatura_xml_olustur.php` /
  `fatura_xml_indir.php` sayfaları eklendi. **Gereksinim:** PHP'nin `dom`
  eklentisinin etkin olması gerekir - XAMPP'te bu varsayılan olarak açıktır,
  ekstra bir şey yapmanıza gerek yok.
- **Bilinen bir orijinal sınırlama (aynen korundu):** `fatura_kaydet.php`
  (`/fatura/duzenle/<id>` üzerinden "düzenle"meye çalışsanız bile) her zaman
  **yeni bir fatura kaydı** oluşturur, mevcut kaydı güncellemez. Orijinal
  Flask kodu da böyleydi (`fatura_id` gönderilse bile hep yeni `Fatura()`
  nesnesi oluşturuluyordu). Gerçek bir "güncelleme" özelliği yoktu.
- **Bilinen bir tutarsızlık (aynen korundu):** Hızlı İşlem'in aksine,
  `fatura_alis_kaydet.php` (bağımsız "Alış Faturası Oluştur" sayfası) cari
  bakiyesini hiç güncellemiyor - sadece stok ve fatura tutarlarını
  günceller. Orijinal Flask kodunda da böyleydi.
- `alis_fatura_olustur.php`'de küçük bir sağlamlaştırma yaptım: orijinalde
  ürün satırının hangi ürüne ait olduğu, tabloya yazılan ismi `<select>`
  listesindeki metinlerle karşılaştırarak (kırılgan bir yöntemle) buluyordu.
  Burada her satıra `data-urun-id` özniteliği eklendi - aynı görsel sonuç,
  daha sağlam eşleştirme.

### Numara Yönetimi / Hızlı İşlem modülü notları

- `NumaraManager::getNext()` **hiçbir şeyi veritabanına yazmaz** - sadece o
  prefix+yıl desenine uyan kayıtlar arasında en yüksek numarayı bulup bir
  sonrakini hesaplar. Bu, orijinal Python koduyla birebir aynı davranış.
- **Bilinen bir orijinal uygulama sınırlaması (aynen korundu):**
  "Evrak No" modalındaki KAYDET butonu, kutuda hazır duran tam formatlı
  numarayı (örn. `MED2026000000053`) olduğu gibi gönderirse, arka uç bunu
  saf sayıya çevirmeye çalışıp başarısız olur (orijinal Python'da
  `int("MED2026000000053")` hata fırlatırdı). Sadece SAF SAYI (örn. `53`)
  girilirse anlamlı bir kontrol yapılır. Bu, `NumaraManager::setNext()`'in
  zaten kalıcı bir değişiklik yapmadığı gerçeğiyle birleşince, bu özelliğin
  orijinalde de sınırlı/yanıltıcı olduğu anlamına geliyor - ileride
  `/numara-yonetim` ekranını taşırken daha sağlam bir çözümle
  (gerçek bir "sıra sayacı" tablosu ekleyerek) iyileştirebiliriz.
- Hızlı İşlem sayfasının kendine özel ~930 satırlık CSS'i (bu sayfaya özgü
  butonlar, ürün kartları, evrak kutusu vb.) **aynen** `public/assets/css/hizli_islem.css`
  dosyasına taşındı ve sadece bu sayfada linklendi (global style.css'e
  karıştırılmadı - Stok tablosunda yaşadığımız çakışma sorununu tekrar
  yaşamamak için).
Bu modüller için navbar'daki bağlantılar şimdilik **404/boş sayfa** verecektir
— her modülü ayrı ayrı taşıyacağız.

### Cariler modülü notları

- `cariler.html` şablonunun sağ menüsündeki dev "Faturalar / Makbuzlar / Teklifler /
  Evraklar" alt menüleri kasıtlı olarak **eklenmedi** — o modüller henüz PHP'ye
  taşınmadığı için kırık bağlantı oluşturmamak adına bekletildi. İlgili modülü
  taşıdığımızda `public/cariler.php` içindeki sol menüye ekleyeceğiz.
- Her Flask şablonunda tekrar eden dev `<style>` bloğu (sol menü, rozetler,
  butonlar, tablo stilleri) **tek seferlik** `public/assets/css/style.css`
  dosyasının sonuna taşındı — görsel sonuç birebir aynı, sadece kod tekrarı
  önlendi.
- `cari_detay.php` sayfasındaki "Hesap Hareketleri" bölümü çalışması için
  `hesap_hareketleri` ve `hesaplar` tablolarını güncelleyen 2 API de bu adımda
  tamamlandı (orijinal Flask app'te de bu modül cari sayfasına gömülüydü).
- **Bilinen ve düzeltilen bir hata:** Orijinal Flask kodundaki `turkce_upper()`
  fonksiyonu Türkçe "İ" harfini büyük harfe çevirirken önce noktasız "I"ya
  çeviriyordu (`GİRİŞ` → `GIRIŞ`), bu da koddaki diğer karşılaştırmalarla
  (`== 'GİRİŞ'`) eşleşmiyordu — gerçek üretim verisinde bile bu hatayı
  doğruladık. PHP tarafında `hesap_hareketi_ekle.php` içinde bu tür sabit
  `<select>` değerlerine (`GİRİŞ`/`ÇIKIŞ`, `TAHSİLAT`/`ÖDEME` vb.)
  `turkce_upper()` uygulanmıyor artık.

### Stok modülü notları

- `templates/stok_listesi.html`, `stok_ekle.html`, `stok_duzenle.html` birebir
  taşındı (`stok_listesi.php`, `stok_ekle.php`, `stok_duzenle.php`).
- `/api/stok-ara` → `api/stok_ara.php`, `/stok/ekle/ajax` → `api/stok_ekle_ajax.php`.
- **Önemli fark:** Orijinal Flask uygulamasında `/stok/barkod-bas/<id>` route'u
  `stok_barkod.html` adlı bir şablonu render etmeye çalışıyordu ama bu şablon
  **arşivde hiç yoktu** — yani orijinal uygulamada o butona tıklamak sunucu
  hatasına (500) yol açıyordu. Bunun yerine `stok_barkod_bas.php` içinde basit,
  yazdırılabilir bir barkod etiketi sayfası eklendi (gerçek bir barkod grafiği
  değil, büyük punto ile ürün adı + barkod numarası).

## Klasör yapısı

```
php-project/
├── config/
│   ├── config.php        # Site ayarları, BASE_URL, SECRET, session ayarları
│   └── database.php      # PDO/MySQL bağlantısı
├── includes/
│   ├── functions.php      # utils.py karşılığı (Türkçe upper/lower, format_para, vb.)
│   ├── auth.php           # Flask-Login karşılığı (session tabanlı)
│   ├── header.php         # base.html üst kısmı (head + navbar + page-header)
│   └── footer.php         # base.html script kısmı
├── public/                # WEB SUNUCUSUNUN DocumentRoot'u BURASI OLMALI
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── api/
│   │   ├── dashboard_data.php
│   │   └── todo_count.php
│   └── assets/
│       ├── css/style.css   # aynen kopyalandı
│       ├── js/main.js      # aynen kopyalandı
│       └── fonts/*.ttf     # aynen kopyalandı
└── sql/
    ├── schema.sql                  # MySQL tablo şeması
    ├── migrate_from_sqlite.php     # eski meda.db verisini MySQL'e aktarır
    └── reset_legacy_passwords.php  # eski kullanıcı parolalarını PHP'ye uyumlu hale getirir
```

## ⚠️ AÇIK KONULAR / SONRA ELE ALINACAK

1. ~~**Hızlı İşlem'den oluşan Fatura/Makbuz, "Hesap Hareketleri" tablosuna
   otomatik satır düşmüyor.**~~ ✅ **ÇÖZÜLDÜ** (13 Temmuz 2026 - bkz. aşağıdaki
   "Veri Tutarlılığı İyileştirmeleri" bölümü, madde 6). Hızlı İşlem artık
   her satış/alış/iade sonrası gerçek bir hesap hareketi satırı oluşturuyor.

2. **Evrak No modalındaki "KAYDET" özelliği orijinalde de yarım tasarlanmış.**
   Kutuda hazır duran tam formatlı numarayı (örn. `MED2026000000053`)
   değiştirmeden kaydetmeye çalışırsan hata alırsın (sayıya çevrilemiyor).
   Ayrıca `NumaraManager::setNext()` zaten hiçbir yere kalıcı yazmıyor,
   sadece çakışma kontrolü yapıyor - gerçek bir "sıradaki numara" sayacı
   yok. Numara Yönetimi ekranı artık var (`/numara_yonetim.php`) ama bu
   temel sınırlama (gerçek bir sayaç tablosu olmaması) hâlâ duruyor -
   istenirse ayrı bir işte ele alınabilir.

3. **Döviz konusu - Efe'nin 15 Temmuz 2026'da not düşüp sonraya bıraktığı
   bir eksiklik var, detayı henüz netleşmedi.** Şimdiye kadar netleşen ve
   ÇÖZÜLEN döviz konuları: (a) Hızlı İşlem sepetine USD/EUR fiyatlı bir
   ürün eklerken artık canlı kur üzerinden TL'ye çevriliyor (bkz. "Hızlı
   İşlem Sepetinde Döviz Dönüşümü" bölümü), (b) ürün detay popup'ında
   TL fiyatının yanında döviz karşılığı gösteriliyor. Efe başka bir döviz
   eksikliğinden bahsetti ama henüz ne olduğunu açıklamadı - sıradaki
   konuşmada bunu sorup netleştirmek gerekiyor.

4. **Web sitesi / e-ticaret entegrasyonu - Efe'nin 15 Temmuz 2026'da
   belirttiği bir gelecek planı.** "Devamında bir web sitesine satış
   tarafına yönlendirileceğiz" dedi - yani bu ERP'nin ürün/stok verisi
   ileride bir web sitesinin satış vitrinine bağlanacak (muhtemelen bir
   API veya senkronizasyon köprüsü ile). Bunun ilk adımı olarak ürün
   resmi ekleme özelliği kuruldu (bkz. "Ürün Resmi Ekleme" bölümü) -
   ama entegrasyonun kendisi (API, senkronizasyon, hangi platform vb.)
   henüz konuşulmadı, detaylar netleşince ele alınacak.

## ✅ Kapsamlı Check-Up (13 Temmuz 2026)

Tüm proje üzerinde uçtan uca bir kontrol yapıldı: sözdizimi taraması (84
dosya), kırık link taraması (BASE_URL/API/CSS referansları), gerçek bir
MySQL/MariaDB'ye şema aktarımı, ve **gerçek bir PHP sunucusu + veritabanı
üzerinden** tüm sayfaların ve kritik iş akışlarının (Hızlı İşlem satışı,
Fatura oluşturma + XML üretimi, Makbuz oluşturma, Teklif kaydetme, Cari
düzenleme) canlı olarak test edilmesiyle doğrulandı.

**Bulunan ve düzeltilen 2 gerçek hata (bu ikisi Claude'un kendi yazdığı
koddaki hatalardı, orijinal Flask'tan miras değil):**

1. **`cari_duzenle.php` - kritik İ/I hatası:** `cari_turu` alanına (sabit
   `MÜŞTERİ`/`TEDARİKÇİ` seçenekleri) yanlışlıkla `turkce_upper()`
   uygulanıyordu. Bu, "MÜŞTERİ"yi "MÜŞTERI"ye (noktasız I) çeviriyor ve
   `cariler.php`'deki rozet/filtre karşılaştırmaları (`=== 'MÜŞTERİ'`) bir
   daha hiç eşleşmiyordu - yani **bir cariyi düzenleyip kaydettiğinizde
   rozeti "belirsiz"e düşüyordu.** Canlı testte doğrulandı ve düzeltildi.
2. **3 dosyada `odeme_turu` kozmetik hatası:** `tahsilat_makbuzu_kaydet.php`,
   `makbuz_olustur.php`, `api/hesap_hareketi_ekle.php` içinde `odeme_turu`
   (sabit seçenek: NAKİT/KREDİ KARTI vb.) `turkce_upper()`'dan geçiyordu,
   "NAKİT"i "NAKIT"a (noktasız I) çeviriyordu. Bu alan hiçbir yerde
   karşılaştırılmadığı için fonksiyonel bir kırılma yaratmıyordu (sadece
   görüntüleme farkı) ama düzeltildi.

**Doğrulanan diğer her şey temiz çıktı:**
- 84 PHP dosyasının tamamı sözdizim hatasız
- Tüm `BASE_URL`/API/CSS referansları gerçekten var olan dosyalara gidiyor
- `schema.sql` gerçek bir MariaDB'ye hatasız aktarıldı (17 tablo)
- 32 sayfa, oturum açmış bir kullanıcıyla canlı olarak tarandı - hiçbirinde
  PHP fatal error/warning/notice yok
- Hızlı İşlem satışı: stok ve cari bakiyesi doğru güncelleniyor
- Fatura oluşturma: toplam hesaplama doğru, GİB uyumlu XML doğru üretiliyor
- Makbuz oluşturma: toplam hesaplama doğru
- Teklif kaydetme: numara üretimi (`VT-2026-0001`) ve ASCII `teklif_turu`
  düzeltmesi doğru çalışıyor
- Gerçek form gönderimlerinde Türkçe karakterler (İ, Ş, Ü, Ç, ı) uçtan uca
  (form → PDO → veritabanı → API JSON çıktısı) bozulmadan korunuyor

## 🔒 Güvenlik İyileştirmeleri (13 Temmuz 2026)

Orijinal Flask uygulamasında da bulunmayan, bu PHP sürümüne **yeni eklenen**
üç güvenlik katmanı:

### 1. CSRF Koruması
Tüm veri değiştiren form ve AJAX isteklerine CSRF (Cross-Site Request
Forgery) koruması eklendi - `includes/auth.php` içindeki `csrf_token()`,
`csrf_field()`, `require_csrf()` (klasik formlar için) ve `require_csrf_json()`
(AJAX/JSON uç noktaları için) fonksiyonları ile. Token, oturum başına bir kez
üretilir, `includes/header.php` üzerinden `CSRF_TOKEN` adlı bir JS
değişkenine aktarılır, ve tüm `<form>` etiketlerine, `fetch()` çağrılarına
(FormData/JSON gövdesi veya `X-CSRF-Token` header'ı ile) eklenir. Sunucu
tarafında her POST/DELETE işlemi bu token'ı doğrular; eşleşmezse işlem
sessizce reddedilir. **Canlı testle doğrulandı:** geçerli token'la işlemler
başarıyla tamamlanıyor, geçersiz/eksik token'la aynı istek hiçbir veri
değişikliği yapmadan reddediliyor.

### 2. Rol Bazlı Yetkilendirme
`users` tablosundaki `role` alanı (varsayılan `'user'`) artık gerçekten
kullanılıyor. `includes/auth.php`'deki `is_admin()`, `require_admin()`,
`require_admin_json()` fonksiyonları ile **Numara Yönetimi ekranındaki
"Tümünü Sıfırla"** (geri alınamaz, tüm fatura/makbuz/teklif/servis
numaralarını yeniden numaralandıran) işlemi artık sadece `admin` rolündeki
kullanıcılara açık - hem arayüzde buton gizleniyor hem de API tarafında
ikinci bir kontrol var (birini atlatmaya çalışan biri diğerine takılır).

Yeni kayıt olan herkes varsayılan olarak `'user'` rolünde oluşturuluyor -
kimse otomatik admin olmuyor. Kendini admin yapmak için:
```bash
php sql/make_admin.php KULLANICI_ADIN
```

### 3. Ortam Bazlı Hata Gösterimi
`config/config.php`'ye `APP_ENV` sabiti eklendi (`'development'` |
`'production'`). Geliştirme modunda hatalar ekranda görünür (debug için
faydalı); canlıya taşırken bunu `'production'` yapman yeterli - hatalar
artık kullanıcıya gösterilmez (veritabanı bilgileri, dosya yolları gibi
hassas detaylar sızmaz), sadece loglanır. **XAMPP'te yerel geliştirme
yaparken bunu `'development'` bırak.**

### Bilinen sınırlamalar / sonraki adımlar
- Legacy parola sorunu (bkz. yukarıdaki "AÇIK KONULAR") hâlâ ayrı bir konu.
- ~~Sayfalama (pagination) henüz eklenmedi~~ ✅ **ÇÖZÜLDÜ** (bkz. aşağıdaki
  "Performans İyileştirmeleri" bölümü, madde 8).
- E-posta doğrulama yok - kayıt olan hesap direkt aktif oluyor.

## 🧩 Veri Tutarlılığı İyileştirmeleri (13 Temmuz 2026 - devam)

README'nin en üstündeki "AÇIK KONULAR" bölümünde bekleyen 3 madde ele alındı:

### 5. Fatura "düzenle" artık gerçek bir UPDATE yapıyor
`fatura_kaydet.php` daha önce `fatura_id` gönderilse bile HER ZAMAN yeni bir
fatura kaydı oluşturuyordu (orijinal Flask'tan miras bir davranıştı) - yani
"düzenle"den kaydetmek aslında eskiyi veritabanında yetim bir kayıt olarak
bırakıp yeni bir tane daha ekliyordu. Şimdi: `fatura_id` gönderilmiş VE o
fatura gerçekten mevcutsa, gerçek bir `UPDATE` yapılıyor; detay kalemleri
silinip yeniden ekleniyor (teknik_servis_duzenle.php'de kullanılan aynı
"sil ve yeniden ekle" deseni). **Canlı testle doğrulandı:** aynı faturayı
iki kez kaydettikten sonra veritabanında hâlâ tek bir kayıt var, ikinci
kaydetme öncekini güncelliyor.

**Bu arada bulunan bir yan hata da düzeltildi:** Fatura/Teklif kaydederken
listede olmayan bir ürün adı girilirse otomatik ürün kodu üretiliyordu
(`PR-{tarih-saat}{sıra}`) - ama bu kod sadece saniye hassasiyetindeydi,
yani aynı saniye içinde iki kayıt işlemi (örn. bir faturayı art arda iki
kez kaydetmek) aynı ürün kodunu üretip "duplicate key" hatası veriyordu.
Koda rastgele bir bileşen eklenerek çakışma riski ortadan kaldırıldı.

### 6. Hızlı İşlem artık Hesap Hareketleri'ne (deftere) satır düşürüyor
`hizli_islem_yap.php`, cari ve hesap bakiyesini doğru güncelliyordu ama
`hesap_hareketleri` tablosuna (Cari Detay ve Hesap Hareketleri sayfalarındaki
"defter" görünümü) hiç satır eklemiyordu - bakiye doğruydu ama işlem geçmişi
görünmüyordu. Artık her Hızlı İşlem sonrasında gerçek bir defter satırı da
oluşuyor (`hareket_turu` = `HIZLI_SATIŞ`/`HIZLI_ALIŞ`/`HIZLI_İADE`,
`referans_no` = oluşan fatura/makbuz numarası) - hem cari hem hesap
bakiyesinin öncesi/sonrası ayrı ayrı kaydediliyor. Yeni rozet renkleri de
eklendi (`cari_detay.php`, `hesap_hareketleri.php`, `kasa_rapor.php`).

**Bulunan ve düzeltilen bir yan tutarsızlık:** `hesap_hareketleri.php` ve
`hesap_duzenle.php`'deki üst özet kartları (TOPLAM GELEN/GİDEN) sadece
`'GELEN'`/`'GIDEN'` değerlerini sayıyordu, ama manuel "Yeni Hareket"
formu `'GİRİŞ'`/`'ÇIKIŞ'` yazıyordu - yani bu sayfalardaki üst özet
kartları, aynı sayfadaki formla eklenen hareketleri hiç saymıyordu.
Artık her iki sorgu da her iki terimi de kabul ediyor (`kasa_ana.php`'de
bu zaten doğru yapılmıştı).

**Canlı testle doğrulandı:** Hızlı İşlem SATIŞ sonrası cari 500→380,
hesap 1000→1120, ve `hesap_hareketleri` tablosunda bu değerleri birebir
yansıtan bir satır (`referans_no` fatura numarasıyla eşleşen) oluştu.

### 7. Fatura için gerçek bir "İptal" özelliği eklendi
Orijinal uygulamada fatura silme/iptal özelliği hiç yoktu (buton tamamen
pasifti). Yeni `fatura_iptal.php` ile fatura artık gerçekten **İPTAL**
durumuna alınabiliyor (Makbuz'daki gibi kalıcı silme değil, durum
değişikliği - evrak numarası ve kayıt geçmişi korunur).

**Bilinçli bir tasarım kararı:** Bu özellik stok/bakiyeyi OTOMATİK OLARAK
geri almıyor. Sebebi: bir fatura üç farklı yoldan gelebiliyor (Fatura
Oluştur → stok/bakiye hiç değişmez, Alış Faturası → sadece stok değişir,
Hızlı İşlem → stok + cari + hesap bakiyesi değişir) ve veritabanında
faturanın hangi yoldan geldiğini kesin ayırt eden bir alan yok. Otomatik
geri alma denemek, bazı faturalarda stoku yanlış yöne değiştirme riski
taşırdı - bu yüzden güvenli tarafta kalınıp sadece durum değiştiriliyor;
gerekirse manuel düzeltme için sayfada açık bir bilgi notu var.

> **16 Temmuz 2026 güncellemesi:** Aşağıdaki "Çoklu Kanal Ödeme Dağılımı"
> bölümünde açıklandığı üzere, Fatura Oluştur artık cari/kasa bakiyesini
> DE etkiliyor (yukarıdaki "stok/bakiye hiç değişmez" artık sadece stok
> için doğru, ödeme/bakiye tarafı için değil). Yine de üç farklı yoldan
> gelen faturaları kesin ayırt eden bir alan olmadığı için, `fatura_iptal.php`
> hâlâ bilinçli olarak otomatik geri alma yapmıyor - bu konudaki temel
> belirsizlik (hangi faturanın hangi yoldan geldiği) değişmedi.

## ⚡ Performans İyileştirmeleri (13 Temmuz 2026 - devam)

### 8. Sayfalama (Pagination)
`cariler.php`, `stok_listesi.php`, `faturalar.php`, `makbuzlar.php`,
`teklifler.php`, `teknik_servis_listesi.php` artık tüm kayıtları tek
seferde çekmiyor - sayfa başına 30 kayıt gösteriyor (`?sayfa=2` gibi bir
URL parametresiyle). Tekrar kullanılabilir bir `includes/pagination.php`
yardımcı dosyası eklendi:
- `get_current_page()` - URL'den sayfa numarasını okur
- `pagination_offset()` - SQL OFFSET hesaplar
- `render_pagination()` - « Önceki 1 2 [3] 4 5 Sonraki » kontrollerini
  temanın karanlık/açık/gri tema değişkenleriyle uyumlu HTML olarak üretir
- `render_pagination_ozet()` - "1-30 arası gösteriliyor, toplam 75 kayıt" özeti

İstatistik kartları (toplam/müşteri/tedarikçi sayısı gibi) sayfalamadan
etkilenmiyor - bunlar ayrı `COUNT(*)` sorgularıyla TÜM kayıtlar üzerinden
hesaplanıyor, sadece o anki sayfadan değil. AJAX arama (Cariler/Stok'taki
canlı arama kutusu) aktifken sayfalama kontrolleri otomatik gizleniyor
(arama sonuçlarıyla karışmasın diye).

**Canlı testle doğrulandı:** 75 kayıtlık bir veri setinde sayfa 1 ve
sayfa 2 farklı kayıtlar gösteriyor, son sayfa (3) doğru şekilde kalan 15
kaydı gösteriyor, aşırı büyük bir sayfa numarası (`?sayfa=999`) hata
vermeden boş liste gösteriyor.

### 9. Veritabanı İndeksleri
Foreign key'ler ve UNIQUE alanlar (fatura_no, urun_kodu, barkod vb.) zaten
otomatik indeksliydi. Buna ek olarak, arama/sıralama yapılan ama
indekslenmemiş kolonlara indeks eklendi: `cariler` (unvan, vergi_no,
telefon, cari_turu), `urunler` (urun_adi), `faturalar` (fatura_tarihi,
durum), `makbuzlar` (makbuz_tarihi), `teklifler` (teklif_tarihi, durum),
`teknik_servis` (created_at, durum), `hesap_hareketleri` (hareket_tarihi),
`todos` (user_id + status bileşik indeksi).

`sql/schema.sql` **yeni kurulumlar için** güncellendi (indeksler baştan
gelir). **Var olan bir veritabanını** güncellemek istiyorsan:
```bash
mysql -u root -p meda_db < sql/add_indexes.sql
```
Bu iki dosya da gerçek bir MariaDB'ye aktarılıp hatasız çalıştığı
doğrulandı.

## 📄 Kullanıcı Deneyimi İyileştirmeleri (13 Temmuz 2026 - devam)

### 10. Excel indirme artık gerçek bir dosya formatı üretiyor
`teknik_servis_cikti.php` ve `kasa_rapor.php`'deki "EXCEL İNDİR" butonları
önceden bir HTML tablosunu `.xls` uzantısıyla kaydediyordu - Excel bunu her
açtığında "dosya biçimi ve uzantısı eşleşmiyor" uyarısı veriyordu çünkü
gerçek bir Excel dosyası değildi. Artık gerçek, standart bir **CSV** dosyası
üretiliyor - Excel'de hiçbir uyarı olmadan doğrudan açılıyor. Türkçe
karakterlerin doğru görünmesi için UTF-8 BOM ekleniyor, ondalık ayracımız
virgül olduğu için CSV ayracı olarak noktalı virgül (`;`) kullanıldı
(Türkçe Excel'in varsayılan beklentisi). Noktalı virgül/tırnak/satır sonu
içeren hücreler doğru şekilde tırnak içine alınıp kaçırılıyor.

Bu değişiklik bilinçli olarak PhpSpreadsheet gibi bir kütüphane eklemeden
yapıldı - projenin "sıfır bağımlılık, saf PHP" felsefesiyle tutarlı kalması
için gerçek bir `.xlsx` yerine evrensel uyumlu, kütüphane gerektirmeyen
CSV formatı tercih edildi. **Node.js ile izole test edildi:** özel
karakter kaçırma mantığı (noktalı virgül, tırnak) doğru çalışıyor;
**canlı sunucu testiyle de doğrulandı:** her iki sayfa da hatasız render
ediliyor.

## 🐛 Kullanıcı Testlerinde Bulunan Hatalar (15 Temmuz 2026)

Efe'nin gerçek kullanım testleri sırasında bulduğu ve düzeltilen hatalar:

### Hızlı İşlem - ALIŞ'ta "Evrak No" penceresi hata veriyordu
ALIŞ işlemine geçilince açılan "Evrak No" penceresine tedarikçinin kendi
fatura numarasını (örn. `EFS20260000045` - harf+rakam karışık, gerçek dünya
formatı) yazıp "KAYDET"e basınca `invalid literal for int()...` hatası
alınıyordu. Sebep: bu alan yanlışlıkla bizim kendi otomatik sıra
numaralandırma sistemimize (`api/numara_guncelle.php` - sadece SAF SAYI
kabul eder) gönderiliyordu. Oysa ALIŞ'ta bu alan tedarikçinin kendi serbest
metin fatura numarasıdır, bizim sıra sistemimizle hiçbir ilgisi yok.
`hizli_islem_script.php`'deki `evrakNoKaydet()` fonksiyonu düzeltildi:
ALIŞ işleminde artık bu numara doğrudan kabul ediliyor, API'ye sorulmuyor.
**Canlı testle doğrulandı:** `EFS20260000045` gibi harfli bir numarayla
tam ekran görüntüsündeki senaryo (Sektor Bilgisayar, Logitech Fare, 14.400₺)
sorunsuz tamamlandı.

### 🔴 Kritik: Hesap Hareketi numarası çakışması sessiz veri kaybına yol açıyordu
Bu, projede bulduğumuz önceki "ürün kodu çakışması" hatasıyla (bkz. Fatura
UPDATE bölümü) **aynı hata ailesindendi** ama çok daha ciddiydi:
`generate_hareket_no()` fonksiyonu sadece `'HRK' . date('YmdHis')` (saniye
hassasiyetinde) üretiyordu. `hareket_no` alanı UNIQUE olduğu için, **aynı
saniye içinde art arda yapılan İKİNCİ bir işlem** (örn. önce bir SATIŞ,
hemen ardından bir ALIŞ yapmak - tam da test senaryonda olduğu gibi) aynı
numarayı üretip veritabanı hatası veriyordu. Bu hata PDO transaction'ını
SESSİZCE geri alıyordu (rollback) - kullanıcı sayfa yönlendirmesi (302)
gördüğü için işlemin başarılı olduğunu sanıyordu, ama **fatura/makbuz hiç
oluşmuyordu ve stok hareketi kaydedilmiyordu**. Bu fonksiyon 5 farklı yerden
çağrılıyordu (`hizli_islem_yap.php`, `makbuz_olustur.php`,
`tahsilat_makbuzu_kaydet.php`, `api/hesap_hareketi_ekle.php`,
`api/kasa_hareket_ekle.php`) - hepsi etkileniyordu. Rastgele bir bileşen
eklenerek düzeltildi (`generate_komisyon_no()`'daki aynı örüntü de bulunup
düzeltildi). **Canlı testle doğrulandı:** art arda SATIŞ + ALIŞ artık her
ikisi de doğru şekilde kaydediliyor, stok doğru güncelleniyor (10→9→12).

### Stok Hareketleri sayfası zaten vardı ama hiçbir yerden bağlı değildi
`stok_detay.php` (ürün bazlı stok giriş/çıkış geçmişi, sayfalamalı) daha
önce tam donanımlı şekilde kurulmuştu ama `stok_listesi.php`'den ya da
`stok_duzenle.php`'den ona giden hiçbir link yoktu - tam da bu projede sık
gördüğümüz "inşa edilmiş ama bağlanmamış özellik" durumu. Artık:
- `stok_listesi.php`'de ürün adına tıklayınca (hem normal listede hem canlı
  arama sonuçlarında) doğrudan bu sayfaya gidiyor
- `stok_duzenle.php`'ye "STOK HAREKETLERİ" butonu eklendi

**Canlı testle doğrulandı:** SATIŞ ve ALIŞ hareketleri artık doğru
referans numarasıyla (fatura/makbuz no) birlikte listede görünüyor.

## 👤 Stok Hareketlerinde Cari Bilgisi (15 Temmuz 2026 - devam)

Efe'nin isteği üzerine: Stok Hareketleri artık **"kimden almışım / kime
satmışım"** bilgisini de gösteriyor. Tek bir "CARİ" sütunu eklendi (ayrı
"müşteri" ve "tedarikçi" sütunlarına bölünmedi, çünkü zaten "TÜR"
sütunundaki ALIŞ/SATIŞ rozeti hangisi olduğunu söylüyor - iki sütuna
bölmek her satırda birini hep boş bırakırdı). Cari adının yanında küçük
bir MÜŞTERİ/TEDARİKÇİ etiketi de gösteriliyor.

**Teknik detay:** `stok_hareketleri` tablosuna `cari_id` kolonu eklendi
(nullable - manuel stok düzeltmelerinde cari olmadığı için NULL kalır).
`includes/functions.php`'deki `stok_hareketi_ekle()` fonksiyonu ve onu
çağıran 5 dosyanın hepsi (Hızlı İşlem, Alış Faturası, Makbuz Oluştur,
Makbuz İptal, Teknik Servis) güncellenerek cari bilgisini de kaydediyor.

**Var olan bir veritabanını güncellemek için** (daha önce
`add_stok_hareketleri.sql` ile bu tabloyu oluşturduysan):
```bash
mysql -u root -p meda_db < sql/add_stok_hareketleri_cari.sql
```
Yeni bir kurulumsan `schema.sql`/`add_stok_hareketleri.sql` zaten
`cari_id` içeriyor, ekstra bir şey yapmana gerek yok.

**Canlı testle doğrulandı:** Gerçek formlar üzerinden eklenen bir müşteri
ve bir tedarikçiyle SATIŞ/ALIŞ işlemi yapıldı, Stok Hareketleri
listesinde her ikisinin de doğru isim ve doğru MÜŞTERİ/TEDARİKÇİ
etiketiyle göründüğü doğrulandı.

## 💱 Hızlı İşlem - Ürün Fiyatlarında Döviz Karşılığı (15 Temmuz 2026 - devam)

Ürün detay popup'ında (Hızlı İşlem'de bir ürünün yanındaki 🔍 butonu)
Satış Fiyatı ve (gösterildiğinde) Alış Fiyatı artık TL tutarının yanında
**güncel kurdan $ ve € karşılığını** da gösteriyor - örn.
`2000.00 TL (≈ $42.53 / €37.25)`. Navbar'daki canlı kur widget'ı zaten
sayfa yüklenirken bir kere kur çekiyordu - o veriyi tekrar kullanıyoruz,
ekstra bir API isteği yapılmıyor. Ürün USD/EUR üzerinden fiyatlandırılmışsa
karşılık hesaplaması buna göre (TL + diğer döviz) uyarlanıyor. Kur henüz
yüklenmediyse (sayfa yeni açıldıysa) karşılık gösterilmiyor, hata vermiyor.

**Node.js ile izole test edildi:** dönüşüm formülü (2000 TL → $42.53/€37.25
gibi) doğru hesaplanıyor. **Canlı sunucu testiyle de doğrulandı:** sayfa
hatasız render ediliyor.

## 🔴 Hızlı İşlem Sepetinde Döviz Dönüşümü Eksikti (15 Temmuz 2026 - devam)

Efe'nin bulduğu gerçek bir hata: Bir ürünü USD/EUR üzerinden fiyatlandırıp
(Stok Ekle'de döviz seçerek) kaydetmek doğru çalışıyordu (veritabanında ve
düzenleme sayfasında doğru görünüyordu), AMA o ürünü **Hızlı İşlem
sepetine eklerken** sistem sadece ham sayıyı alıp para birimini hiç
kontrol etmeden **doğrudan TL olarak** sepete koyuyordu - yani ürün
"70 EUR" olarak kayıtlıysa, sepette "70 TL" gibi işlem görüyordu (~54 kat
düşük bir tutar!).

`hizli_islem_script.php`'ye `paraBirimindenTLyeCevir()` fonksiyonu eklendi
- navbar'daki canlı kur widget'ını kullanarak (döviz karşılığı özelliğiyle
aynı kaynak) USD/EUR fiyatlı ürünleri sepete eklerken otomatik olarak TL'ye
çeviriyor. Arama sonuçlarında hem orijinal döviz tutarı hem TL karşılığı
gösteriliyor (örn. `70.00 EUR (≈3758.30 ₺)`), ama sepete TL karşılığı
ekleniyor - Hızlı İşlem'in tüm toplam hesaplamaları (ara toplam, KDV,
genel toplam, cari/hesap bakiyesi) zaten tek bir para birimi (TL) üzerinden
yapıldığı için bu tutarlılığı korumak gerekiyordu.

**Node.js ile izole test edildi:** 70 EUR → 3758.30 TL, 50 USD → 2351.50 TL
gibi dönüşümler doğru hesaplanıyor. **Canlı sunucu testiyle de
doğrulandı:** EUR fiyatlı bir ürün API'den doğru döviz koduyla dönüyor,
sayfa hatasız render ediliyor.

## 🔴 Hızlı İşlem'de "FİYAT" Kutusu Hiç Bağlı Değildi (15 Temmuz 2026 - devam)

Efe'nin fark ettiği bir hata: arama panelinin üstündeki Adet/İsk%/KDV%/
**Fiyat** + EKLE mini-formunda, Fiyat kutusuna bir değer yazıp EKLE'ye
basınca yazılan değer tamamen görmezden geliniyordu - ürünün kendi kayıtlı
fiyatı sepete ekleniyordu. Kontrol edilince: bu kutu (`id="eklenecekFiyat"`)
**hiçbir JS fonksiyonu tarafından hiç okunmuyormuş** - Adet/İsk%/KDV%
kutularının hepsi ilgili yerlerde kullanılıyordu ama Fiyat kutusu sadece
görsel olarak duruyordu, hiçbir işleve bağlı değildi (bu projede sık
gördüğümüz "inşa edilmiş ama bağlanmamış" hata ailesinden).

Düzeltildi: artık bir arama sonucu geldiğinde bu kutu otomatik olarak o
ürünün (TL'ye çevrilmiş) fiyatıyla dolduruluyor - kullanıcı isterse bu
değeri değiştirip (örn. pazarlıklı bir fiyatla) öyle ekleyebiliyor,
`sepeteEkleFromSelected()` artık gerçekten bu kutudaki değeri kullanıyor.

**Not: Hızlı İşlem'de ayrı bir "satış para birimi" seçimi yok** - tüm
sepet hesaplamaları (ara toplam, KDV, genel toplam, cari/hesap bakiyesi)
tek bir para birimi (TL) üzerinden yapılıyor. Dövizle bir fiyat üzerinden
satmak istiyorsan, bu düzeltilen Fiyat kutusuna doğrudan TL karşılığını
yazabilirsin. Tamamen döviz cinsinden bir FATURA/MAKBUZ oluşturmak
istiyorsan (Hızlı İşlem değil), Fatura Oluştur / Makbuz Oluştur
ekranlarındaki "Para Birimi" alanı TL/USD/EUR seçimi sunuyor.

## ✅ Hızlı İşlem - Ürün Seçimi İyileştirmeleri (15 Temmuz 2026 - devam)

Efe'nin önerisi üzerine, birden fazla arama sonucu olduğunda ürün seçimi
netleştirildi:

1. **Satıra tıkla, seçili yap:** Arama sonuçlarında bir ürüne tıklayınca o
   satır görsel olarak vurgulanıyor ("seçili" hale geliyor) ve üstteki
   Fiyat kutusu otomatik olarak o ürünün fiyatıyla güncelleniyor. Üstteki
   Adet/İsk%/KDV%/Fiyat panelindeki EKLE butonu artık her zaman ilk sonucu
   değil, **son tıklanan (seçili) satırı** kullanıyor.
2. **Çoklu seçim + toplu ekleme:** Her arama sonucu satırının başına bir
   onay kutusu eklendi. Birden fazla ürünü işaretleyip listenin üstündeki
   **"SEÇİLİLERİ SEPETE EKLE"** butonuyla hepsini tek seferde (üstteki
   İsk%/KDV% değerleriyle, miktar 1 olarak) sepete ekleyebilirsin.

**Canlı testle doğrulandı:** Yeni araç çubuğu, onay kutuları ve ilgili JS
fonksiyonları sayfada doğru şekilde render ediliyor, genel regresyon testi
(32 sayfa) sorunsuz.

## 🏦 Hesap/Kasa - VERESİYE Türü ve Pasife Alma (15 Temmuz 2026 - devam)

Efe'nin isteği üzerine iki iyileştirme:

### 1. Yeni hesap türü: VERESİYE
`Hesap Ekle`/`Hesap Düzenle`'deki tür seçeneklerine BANKA/KASA/KOMİSYON/
POS/VİRMAN'ın yanına **VERESİYE** eklendi - nakit kasası, banka kasası ve
veresiye (açık hesap) kasasını artık ayrı ayrı tanımlayabilirsin.
`hesap_turu` serbest bir metin alanı (ENUM değil) olduğu için **şema
değişikliği/migrasyon gerekmedi** - kodu güncellemek yeterli.

**Bu arada bulunan ve düzeltilen bir İ/I hatası:** `hesap_turu` alanına
uygulanan `turkce_upper()`, "VERESİYE" (gerçek noktalı İ içeren) değerini
"VERESIYE"ye (noktasız I) çevirip her yerdeki karşılaştırmaları
bozacaktı - projede defalarca bulduğumuz aynı hata sınıfı. `hesap_ekle.php`
ve `hesap_duzenle.php`'de bu alana artık `turkce_upper()` uygulanmıyor
(diğer sabit-seçim alanlarında yaptığımız düzeltmeyle aynı desen).

### 2. Hesap/Kasa artık silinemiyor, sadece pasife alınabiliyor
Zaten bir "hesap silme" özelliği yoktu (iyi haber), ama `is_active` alanı
da hiçbir arayüzden kullanılmıyordu. Yeni `hesap_durum_degistir.php` ile:
- Hesaplar Listesi'nde her hesabın yanına **"Pasife Al" / "Aktif Et"**
  butonu eklendi
- Pasife alınan bir hesap **silinmiyor** - kaydı ve TÜM geçmiş hareketleri
  (`hesap_hareketleri`) veritabanında olduğu gibi kalıyor
- Pasif hesaplar, Hesaplar Listesi'nde **PASİF etiketiyle hâlâ görünüyor**
  (soluk renkte) - ama Hızlı İşlem, Kasa Ana, Makbuz Oluştur, Tahsilat
  Makbuzu gibi **yeni işlem oluşturma** ekranlarındaki seçim listelerinden
  kayboluyor, böylece yanlışlıkla artık kullanılmayan bir kasaya yeni
  işlem düşmesi engelleniyor
- Hesap Hareketleri / Kasa Raporu gibi **geçmişe dönük rapor sayfaları**
  bilerek filtrelenmedi - pasif bir hesabın geçmiş verilerine hâlâ tam
  erişebiliyorsun, raporlar bozulmuyor

**Canlı testle doğrulandı:** VERESİYE türünde bir hesap oluşturuldu (doğru
noktalı İ ile kaydedildiği HEX ile doğrulandı), Hızlı İşlem'de göründü,
pasife alındı (gerçek seçim listesinden kayboldu ama Hesaplar Listesi'nde
PASİF etiketiyle kalmaya devam etti), tekrar aktif edildi - hepsi beklendiği
gibi çalıştı.

## 💰 Prim (Satış Komisyonu) Özelliği (15 Temmuz 2026 - devam)

Efe'nin isteği üzerine tamamen yeni bir özellik: satış yapıldığında personele
prim/komisyon tanımlayabilme ve bunu takip edip ödeyebilme.

### Nasıl çalışıyor
1. **Hızlı İşlem / Fatura Oluştur / Makbuz Oluştur** ekranlarından herhangi
   birinde bir **SATIŞ** tamamlandığında, "PRİM İŞLEMİ YAPILACAK MI?" popup'ı
   çıkıyor (ALIŞ/İADE işlemlerinde çıkmıyor - sadece satışlarda).
2. "EVET" denirse: **Prim Verilecek Kişi** (aşağıya bakın) seçiliyor, ve
   hesaplama yöntemi **Sabit Tutar** ya da **Satıştan Oranla (%)** olarak
   seçiliyor. Oranla seçilirse tutar satış tutarından otomatik hesaplanıyor.
3. Kaydedilen prim, **Cariler → Primler** sayfasında **BEKLEMEDE** durumunda
   listeleniyor - bu noktada hiçbir kasa bakiyesi etkilenmiyor.
4. Primler sayfasından **"ÖDE"** butonuna basılıp bir kasa seçildiğinde,
   prim gerçekten o kasadan düşülüyor (`hesaplar.bakiye` güncelleniyor) ve
   gerçek bir **Hesap Hareketi** (`PRİM_ÖDEME` türünde, "ÇIKIŞ") oluşuyor -
   Hesap Hareketleri sayfasında ve kasa raporlarında görünüyor.

### Teknik detaylar
- **"Prim Verilecek Kişi" nereden tanımlanıyor?** Ayrı bir "personel yönetimi"
  sistemi kurmak yerine, zaten sağlam olan **Cariler** alt yapısı yeniden
  kullanıldı: Cari Ekle/Düzenle'ye yeni bir tür eklendi - **PERSONEL**.
  Cari Türü olarak PERSONEL seçilen bir cari, otomatik olarak Prim popup'ının
  "Prim Verilecek Kişi" listesinde görünür.
- **Prim bakiyeye karışmıyor:** Bilinçli bir tasarım kararı olarak, prim
  tutarı personelin cari `bakiye` alanına hiç dokunmuyor (o alan müşteri/
  tedarikçi borç-alacak takibi için) - tamamen ayrı, `komisyon_hareketleri`
  tablosu üzerinden `odeme_durumu` (BEKLEMEDE/ÖDENDİ) ile takip ediliyor.
- **Şans eseri hazır bir alt yapı vardı:** `komisyon_hareketleri` tablosu ve
  `api/komisyon_ekle.php` daha önce (Finans modülü sırasında) kurulmuş ama
  hiçbir arayüzden hiç kullanılmamıştı - "Prim" kavramı bu tablonun
  karşıladığı şeyle (cari bazlı, oranlı/sabit tutarlı, ödeme durumu takipli)
  birebir örtüştüğü için sıfırdan yeni bir tablo kurmak yerine bu yeniden
  kullanıldı.
- Yeni dosyalar: `public/primler.php` (liste + ödeme akışı), `public/prim_ode.php`
  (gerçek ödeme işlemi), `public/api/prim_ekle.php` (popup'tan çağrılan
  kayıt oluşturma API'si).
- Fatura Oluştur / Makbuz Oluştur geleneksel form POST + redirect
  kullandığı için (AJAX değil), popup'ın "satış başarılı olduktan sonra"
  gösterilebilmesi için yönlendirme URL'sine `?prim_sor=1&tutar=...&ref=...`
  parametreleri eklendi - sayfa açılışında bu parametreler kontrol edilip
  popup otomatik açılıyor.

**Bulunan ve düzeltilen bir İ/I hatası:** "PERSONEL" cari türü eklerken,
komisyon_turu için kullanılan "SATIŞ_PRİMİ" ve hareket_turu için kullanılan
"PRİM_ÖDEME" gibi noktalı İ içeren sabit değerlerin `turkce_upper()`'dan
geçmediğinden emin olundu (bu fonksiyonlar zaten bu alanlara hiç
uygulanmıyordu, ama kontrol edildi).

**Canlı testle uçtan uca doğrulandı:** PERSONEL cari oluşturuldu, gerçek
API üzerinden bir prim kaydı (%10 oranla, 1000 ₺ matrahtan 100 ₺ prim)
oluşturuldu, Primler sayfasında doğru göründü, "ÖDE" ile bir kasadan
ödendi - kasa bakiyesi 5000→4900 düştü, komisyon kaydı ÖDENDİ olarak
işaretlendi, gerçek bir Hesap Hareketi satırı oluştu. Ayrıca tüm yeni/
değişen sayfalar tamamen boş bir veritabanıyla (ilk kullanım senaryosu)
hatasız test edildi.

## 🎨 Modal Tema Hatası Düzeltildi (15 Temmuz 2026 - devam)

Efe'nin fark ettiği hata: `primler.php`'deki "PRİM ÖDE" popup'ı beyaz
(varsayılan Bootstrap) görünümle açılıyordu, temayla uyumsuz duruyordu.
Kontrol edilince: modal'ların koyu/açık/gri tema stili hiç **global**
değildi - her sayfa bunu kendi CSS dosyasında (`hizli_islem.css`,
`kasa_ana.css`, `makbuz_olustur.css` vb.) ayrı ayrı tanımlıyordu. Bu
dosyalardan hiçbirini yüklemeyen yeni bir sayfa (primler.php gibi)
modal açtığında, tema uygulanmadan varsayılan beyaz Bootstrap görünümü
kalıyordu.

Bunu tek sayfada yamamak yerine **`style.css`'e (tüm sayfalarda yüklenen
global stylesheet) taşıdım** - artık hangi sayfada olursa olsun, herhangi
bir modal açıldığında otomatik olarak doğru temayı (koyu/açık/gri) alıyor.
Bu, ileride eklenecek yeni sayfalardaki modaller için de aynı hatanın bir
daha çıkmayacağı anlamına geliyor.

## 🖼️ Ürün Resmi Yükleme (15 Temmuz 2026 - devam)

Efe'nin isteği üzerine: ürünlere resim ekleyebilme özelliği eklendi
(ileride bir web sitesinin satış tarafına yönlendirileceği not edildi -
bu resimler o zaman kullanılabilir).

### Neler eklendi
- `urunler` tablosuna `resim` kolonu (dosya adını tutar)
- **Stok Ekle** ve **Stok Düzenle** formlarına resim yükleme alanı
  (JPG/PNG/WEBP/GIF, maksimum 3 MB) - Düzenle'de ayrıca mevcut resmi
  gösterme ve "Resmi Kaldır" seçeneği var
- **Stok Listesi**'nde her ürünün yanında küçük bir önizleme (resim yoksa
  boş bir ikon gösteriliyor)
- **Stok Detay** sayfasında büyük bir önizleme
- Bir ürün silindiğinde diskteki resmi de otomatik temizleniyor (yetim
  dosya kalmaması için)
- `includes/functions.php`'ye `urun_resim_yukle()` (doğrulama + kayıt) ve
  `urun_resim_sil()` (temizlik) yardımcı fonksiyonları eklendi - MIME
  türü gerçek dosya içeriğinden kontrol ediliyor (`finfo`), sadece
  uzantıya güvenilmiyor

Resimler `public/assets/uploads/urunler/` klasöründe saklanıyor.

**Var olan bir veritabanını güncellemek için:**
```bash
mysql -u root -p meda_db < sql/add_urun_resim.sql
```
Yeni bir kurulumsan `schema.sql` zaten bu kolonu içeriyor.

**Not:** XAMPP'in `php.ini`'sindeki `upload_max_filesize` ayarı 3 MB'dan
küçükse (bazı eski kurulumlarda varsayılan 2 MB olabilir), büyük resimler
sessizce reddedilebilir - böyle bir sorunla karşılaşırsan bu ayarı
kontrol et.

**Canlı testle uçtan uca doğrulandı:** Gerçek bir PNG dosyası
`stok_ekle.php`'ye yüklendi, veritabanına doğru dosya adı kaydedildiği,
dosyanın diskte gerçekten oluştuğu, hem Stok Listesi'nde hem Stok
Detay'da göründüğü doğrulandı.

## 🖼️ Ürün Resmi Ekleme (15 Temmuz 2026 - devam)

Efe'nin isteği üzerine ürünlere resim ekleme özelliği kuruldu.

### Nasıl çalışıyor
- **Stok Ekle / Stok Düzenle**: "ÜRÜN RESMİ" alanından JPG/PNG/WEBP/GIF
  yüklenebiliyor (maksimum 3 MB). Düzenleme ekranında mevcut resim
  gösteriliyor, "Resmi kaldır" onay kutusuyla silinebiliyor, yeni bir
  dosya seçilirse eskisinin yerine geçiyor.
- **Stok Listesi**: her ürün satırında küçük bir thumbnail (resim yoksa
  gri bir resim ikonu) gösteriliyor - hem normal listede hem canlı
  arama sonuçlarında.
- **Ürün Detay** (`stok_detay.php`): sayfanın üstünde daha büyük bir
  önizleme gösteriyor.
- Bir ürün silindiğinde (`stok_sil.php`), diskteki resim dosyası da
  otomatik temizleniyor - yetim dosya birikmiyor.

### Teknik detaylar
- Yeni kolon: `urunler.resim` (dosya adını tutar, tam yolu değil - tam
  yol her zaman `assets/uploads/urunler/` + dosya adı olarak kuruluyor)
- Yüklenen dosyalar `public/assets/uploads/urunler/` klasörüne, çakışmayı
  önleyen benzersiz bir isimle kaydediliyor (`urun_{id}_{zaman}_{rastgele}.uzanti`)
- `includes/functions.php`'e `urun_resim_yukle()` (doğrulama + kaydetme)
  ve `urun_resim_sil()` (temizleme) yardımcı fonksiyonları eklendi -
  dosya türü gerçek MIME kontrolüyle (`finfo`) doğrulanıyor, sadece
  uzantıya bakılmıyor (biri `.jpg` uzantılı sahte bir dosya yükleyemez)
- **Var olan bir veritabanını güncellemek için:**
  ```bash
  mysql -u root -p meda_db < sql/add_urun_resim.sql
  ```
  Yeni bir kurulumsan `schema.sql` zaten bu kolonu içeriyor.

### Not: Web sitesi entegrasyonu için
Efe'nin belirttiği üzere, ilerleyen bir aşamada bu ürünler bir web
sitesinin satış tarafına (muhtemelen bir e-ticaret vitrini) bağlanacak.
Bu resim yükleme özelliği o entegrasyonun ilk adımı olarak düşünüldü -
ürün görselleri artık merkezi olarak burada tutuluyor, ileride bir API/
senkronizasyon köprüsü kurulduğunda doğrudan kullanılabilir.

**Canlı testle uçtan uca doğrulandı:** Gerçek bir PNG dosyası yüklendi,
diske kaydedildiği, veritabanına doğru dosya adının yazıldığı, hem
Stok Listesi'nde hem Ürün Detay'da göründüğü doğrulandı.

## 💳 Çoklu Kanal Ödeme Dağılımı (16 Temmuz 2026)

Efe'nin isteği üzerine: bir satış artık tek bir ödeme kanalından değil,
**birden fazla parçaya bölünerek farklı kanallardan/kasalardan**
ödenebiliyor - örn. 1500 TL'lik bir satışta 500 TL nakit + 500 TL havale +
500 TL kredi kartı gibi. Bu, **Hızlı İşlem, Fatura Oluştur ve Makbuz
Oluştur** ekranlarının üçünde de çalışıyor.

### Nasıl çalışıyor
- Eskiden tek bir "Ödeme Türü" + tek bir "Hesap/Kasa" seçimi vardı. Artık
  dinamik bir **"ÖDEME DAĞILIMI"** paneli var: "+ ÖDEME EKLE" ile istediğin
  kadar satır ekleyebilirsin, her satırda kendi **Ödeme Türü** (Nakit/Kredi
  Kartı/Havale/EFT/Çek/Senet), kendi **Hesap/Kasa**'sı ve kendi **Tutar**'ı
  var.
- Panelin altında canlı bir özet var: Genel Toplam / Ödenen / Kalan.
- **Ödenen toplamın satış tutarından az olmasına bilinçli olarak izin
  veriliyor** (Efe'nin isteği) - ödenmeyen kısım otomatik olarak veresiye/
  borç sayılır. Hiç ödeme satırı eklemeden de (tamamen veresiye) satış
  tamamlanabilir.
- Her ödeme satırı için **ayrı bir Hesap Hareketi (defter satırı)**
  oluşuyor ve o kasanın bakiyesi kendi tutarı kadar güncelleniyor -
  ödenmeyen kısım için hiçbir kasa hareketi oluşmuyor.
- Belgenin (fatura/makbuz) kendi `odeme_turu` alanına, kullanılan tüm
  kanalların özet bir metni yazılıyor (örn. `"NAKİT + HAVALE + KREDİ
  KARTI"`, tek kanal kullanıldıysa sadece o kanalın adı, hiç ödeme
  yapılmadıysa `"VERESİYE"`).
- Cari bakiyesi hâlâ **tam satış tutarı** kadar etkileniyor (ödenen kısımdan
  bağımsız) - bu, müşterinin toplam borç/alacak durumunun her zaman doğru
  yansıması için kasıtlı bir tasarım: ödenmeyen kısım zaten cari bakiyesi
  üzerinden "borç" olarak görünüyor.

### Önemli bir değişiklik: Fatura Oluştur artık gerçek bir mali hareket oluşturuyor
`fatura_kaydet.php` (Fatura Oluştur'un arkasındaki dosya) daha önce **hiçbir
mali harekete yol açmıyordu** - sadece bir belge kaydediyordu, ne cari
bakiyesi ne de bir kasa bakiyesi hiç etkilenmiyordu (bu, README'de daha
önce "bilinen bir sınırlama" olarak not edilmişti). Çoklu ödeme özelliği
eklenirken bu da düzeltildi - artık Fatura Oluştur da Hızlı İşlem ve Makbuz
Oluştur ile tutarlı şekilde cari ve kasa bakiyelerini güncelliyor. Fatura
**düzenlendiğinde** (var olan bir faturayı güncellerken) bu bakiye
etkisi **tekrar uygulanmıyor** - sadece ilk oluşturmada bir kez uygulanıyor.

### Canlı testlerle doğrulanan senaryolar
- **3 kanallı tam ödeme:** 1500 TL'lik satış → 500 NAKİT + 500 HAVALE + 500
  KREDİ KARTI, üç farklı kasaya doğru dağıtıldı, üç ayrı hesap hareketi
  oluştu, cari bakiyesi tam 1500 TL etkilendi.
- **Kısmi ödeme (veresiye):** 1500 TL'lik satışta sadece 1000 TL NAKİT
  ödendi - sadece o kasa güncellendi (tek hareket satırı), cari bakiyesi
  yine tam 1500 TL etkilendi (kalan 500 TL borç olarak cari bakiyesinde
  görünüyor).
- **Makbuz Oluştur:** aynı çoklu kanal senaryosu makbuz üzerinden de
  doğrulandı.
- **Fatura Oluştur:** ilk kez cari/kasa bakiyesini etkilediği doğrulandı;
  aynı faturayı düzenlemenin bakiyeyi TEKRAR etkilemediği ayrıca test
  edildi (kasa/cari bakiyesi ve fatura sayısı değişmeden kaldı).
- Tüm 3 ekran + 4 makbuz türü (ALIŞ/SATIŞ/TAHSİLAT/ÖDEME) tamamen boş bir
  veritabanıyla (hiç hesap/cari yokken) hatasız render edildi.

## 🐛 4 Hata Bulundu ve Düzeltildi (18 Temmuz 2026)

Efe'nin gerçek kullanım testleri sırasında bulduğu hatalar:

### 1. Ürün resmi yüklenmiyor
Muhtemel sebep: `public/assets/uploads/urunler/` klasörü sunucuda hiç
oluşmamış olabilir (özellikle proje dosyaları tek tek kopyalandıysa, tüm
zip yeniden açılmadıysa, bu boş klasör hiç gelmemiş olabilir).
`includes/functions.php`'deki `urun_resim_yukle()` fonksiyonu artık bu
klasörü **otomatik oluşturuyor** (yoksa `mkdir` ile), ve klasör yazılabilir
değilse açık bir hata mesajı döndürüyor (önceden sessizce başarısız
olabiliyordu).

### 2. KDV oranını %0 yapınca otomatik %20'ye dönüyordu
Klasik bir JavaScript hatası: `parseFloat(deger) || 20` ifadesinde, `0`
JavaScript'te "falsy" sayıldığı için `0 || 20` ifadesi `20`'ye
değerlendiriliyordu - kullanıcı KDV'yi bilerek %0 yapsa bile otomatik
%20'ye dönüyordu. `hizli_islem_script.php`'de 4 yerde bu desen düzeltildi
(artık `isNaN()` kontrolü kullanılıyor, `0` geçerli bir değer olarak kabul
ediliyor).

### 3. Sepetteki ürünlerin KDV/İskonto/Fiyat düzenlemeleri "işlemiyordu"
Kök neden: her tek alan düzenlemesinde (örn. İSK% değiştirmek) **tüm sepet
tablosu** (tüm input elemanları dahil) DOM'dan silinip sıfırdan yeniden
oluşturuluyordu. Kullanıcı bir satırda birden fazla alanı art arda hızlıca
düzenlediğinde (örn. önce İSK%'yi değiştirip hemen KDV%'ye tıklamak),
tıkladığı an o input DOM'dan silinip yenisiyle değiştirilmiş oluyordu -
bu da odağın kaybolmasına ve düzenlemenin "işlememiş" gibi görünmesine yol
açıyordu (jsdom ile bu davranış izole olarak doğrulandı). Artık sadece
hesaplanan tutarlar (satır toplamı + genel toplamlar) güncelleniyor,
input elemanlarının kendisine hiç dokunulmuyor - `sepetToplamlariGuncelle()`
adında yeni, "cerrahi" bir güncelleme fonksiyonu eklendi.

### 4. En önemlisi: Ödeme yapılsa bile cari bakiyesi tam tutar kadar borçlandırıyordu
Efe'nin bulduğu en kritik hata: bir ürün satılıp **anında tam ödeme**
alınsa bile (örn. 500 TL'lik satışta 500 TL nakit tahsil edilse), cari
hesabı sanki hiç ödeme alınmamış gibi tam tutar kadar borçlandırılıyordu.
Sebep: cari bakiyesi güncellemesi ödeme dağılımından tamamen bağımsız
çalışıyordu - her zaman **tam satış/alış tutarı** kadar uygulanıyordu.

Artık cari bakiyesi sadece **ödenmeyen (kalan) tutar** kadar etkileniyor:
```
kalan = genel_toplam - toplam_ödenen
```
Tam ödenen bir satışta `kalan = 0`, yani cari bakiyesi hiç değişmiyor (ne
borç ne alacak oluşuyor) - tam beklenen davranış. Kısmi ödemede sadece
gerçek kalan borç/alacak cari bakiyesine yansıyor. Bu düzeltme **Hızlı
İşlem, Makbuz Oluştur ve Fatura Oluştur**'un üçünde de yapıldı.

**Terminoloji düzeltmesi:** Efe'nin belirttiği gibi, ödeme hareketlerinin
etiketi artık "SATIŞ/ALIŞ" değil, gerçek para akışı yönünü yansıtan
**"TAHSİLAT/ÖDEME"** oluyor (SATIŞ/İADE → TAHSİLAT, ALIŞ → ÖDEME) - Hızlı
İşlem ve Fatura Oluştur'da. (Makbuz Oluştur'da bu ayrım zaten var olan
kendi makbuz türü sistemi - SATIŞ/ALIŞ/TAHSİLAT/ÖDEME - üzerinden
sağlandığı için orada değiştirilmedi.)

**Canlı testlerle doğrulandı:**
- Tam ödeme (500 TL satış, 500 TL nakit tahsil): cari bakiyesi **0** kaldı
  (önceden -500 olurdu), kasa doğru arttı, hareket türü "HIZLI_TAHSİLAT"
- Kısmi ödeme (500 TL satış, sadece 300 TL nakit): cari bakiyesi **-200**
  (sadece kalan borç), kasa sadece 300 arttı
- ALIŞ senaryosu (500 TL alış, tam 500 TL nakit ödeme): cari bakiyesi
  **0** kaldı, kasa doğru azaldı, hareket türü "HIZLI_ÖDEME", işlem yönü
  "ÇIKIŞ"

## 🔗 Her Yerde Cari Adına Tıklayınca Cari Detayına Gitme (18 Temmuz 2026)

Efe'nin isteği üzerine: sistemde bir cari (müşteri/tedarikçi/personel) adı
nerede görünüyorsa, artık tıklanabilir - `cari_detay.php` sayfasına
götürüyor. 10 dosyada düzeltildi:

- `cariler.php` (hem normal listede hem canlı arama sonuçlarında)
- `faturalar.php`, `makbuzlar.php`, `teklifler.php`,
  `teknik_servis_listesi.php` - liste sayfalarındaki cari sütunu
- `evraklar.php` - fatura/makbuz/teklif/servis birleşik listesindeki 4 satır
- `makbuz_detay.php` - detay sayfasındaki "CARİ BİLGİLERİ" kartı
- `stok_detay.php` - stok hareketleri tablosundaki cari sütunu
- `primler.php` - prim alacak kişi (personel) sütunu
- `vadeli_takip.php` - taksit listesindeki cari sütunu

`hesap_hareketleri.php`, `kasa_ana.php`, `kasa_rapor.php` zaten önceden
tıklanabilirdi, değişiklik gerekmedi.

**Bilinçli olarak dokunulmayan yerler:** Cari seçim `<select>` dropdown'ları
(Fatura/Makbuz/Teklif Oluştur, Hızlı İşlem gibi - HTML `<option>` içine link
konulamaz, zaten seçim amaçlı), cari'nin kendi düzenleme sayfası (`cari_duzenle.php`
- kendine link vermenin anlamı yok), ve yazdırma çıktılarındaki (`fatura_cikti.php`
vb.) "SATICI" bilgisi (bu bizim kendi firma bilgimiz, bir cari değil).

**Canlı testle doğrulandı:** Cariler, Faturalar, Makbuzlar ve Evraklar
sayfalarının hepsinde cari adına tıklanan link doğru `cari_detay.php?id=X`
adresine gidiyor ve doğru carinin bilgilerini gösteriyor.

## 🗄️ MySQL/MariaDB'den SQLite'a Komple Geçiş (18 Temmuz 2026)

Efe'nin isteği üzerine tüm veritabanı katmanı SQLite'a taşındı. Artık
**ayrı bir veritabanı sunucusu (XAMPP'te MySQL servisi) hiç çalıştırmaya
gerek yok** - PHP'nin kendi içindeki `pdo_sqlite` eklentisi yeterli.

### Neden ve ne değişti
- **Veritabanı dosyası:** `data/meda.sqlite` (proje kökünde, `public/`
  klasörünün DIŞINDA - böylece tarayıcıdan doğrudan indirilemez/erişilemez)
- **Otomatik kurulum:** `config/database.php` artık veritabanı dosyası
  yoksa onu otomatik oluşturuyor VE `sql/schema.sql`'i içine otomatik
  yüklüyor - `mysql -u root ... < schema.sql` gibi ayrı bir kurulum
  komutu çalıştırmaya hiç gerek yok, sayfayı ilk açtığın an her şey
  hazır oluyor.
- **`sql/schema.sql`** MySQL'den SQLite'a çevrildi: `AUTO_INCREMENT` →
  `INTEGER PRIMARY KEY AUTOINCREMENT`, `ENGINE=InnoDB DEFAULT
  CHARSET=utf8mb4` kaldırıldı, `ON UPDATE CURRENT_TIMESTAMP` kaldırıldı
  (SQLite'da kolon seviyesinde desteklenmiyor - zaten uygulama her
  UPDATE'te `updated_at`'i açıkça set ediyor), `CREATE TABLE` içindeki
  `INDEX` tanımları ayrı `CREATE INDEX` ifadelerine çevrildi (SQLite'da
  satır içi index tanımlanamıyor).
- **Tüm `NOW()` çağrıları** (26 dosyada, 69 yer) `datetime('now','localtime')`
  ile değiştirildi - SQLite'ın `NOW()` fonksiyonu yok, ve **bilinçli
  olarak** `'localtime'` kullanıldı (sade `CURRENT_TIMESTAMP` UTC
  döndürür, Türkiye saatinden 3 saat geri kalırdı).
- **`kasa_ana.php`'deki `CURDATE()`** (MySQL'e özel, canlı testte
  yakalanan gerçek bir hataydı) `DATE('now','localtime')` ile değiştirildi.
- Eski MySQL'e özel `sql/add_*.sql` migrasyon dosyaları (add_indexes,
  add_stok_hareketleri, add_stok_hareketleri_cari, add_urun_resim) ve
  `sql/migrate_from_sqlite.php` artık **gerekli değil** (schema.sql zaten
  hepsini içeriyor) - üstlerine bunu belirten bir not eklendi, silinmedi
  (tarihsel referans için duruyor).

### Neyin değişmediğine dikkat
- Uygulama kodundaki (PHP) tüm iş mantığı, doğrulama, hesaplama vb.
  **hiç değişmedi** - sadece veritabanı bağlantı katmanı ve birkaç
  MySQL'e özel SQL fonksiyonu değişti.
- `includes/functions.php`'deki `turkce_upper()` ve benzeri Türkçe
  karakter işleme fonksiyonları PHP tarafında çalıştığı için SQLite
  geçişinden hiç etkilenmedi.
- Türkçe karakterler (İ/ı, Ş/ş, Ç/ç vb.) SQLite'ta MySQL'den DAHA
  SORUNSUZ saklanıyor - MySQL'in charset/collation ayarlarıyla ilgili
  hiçbir konfigürasyon derdi yok, SQLite ne PHP'den UTF-8 olarak
  gönderilirse onu olduğu gibi saklıyor.

### Canlı testlerle kapsamlı doğrulama
Gerçek bir SQLite veritabanı sıfırdan oluşturuldu ve **33 sayfanın
tamamı** (index, cariler, stok, hızlı işlem, fatura, makbuz, teklif,
teknik servis, hesaplar, kasa, primler, evraklar, vadeli takip vb.)
hatasız render edildiği doğrulandı. Ayrıca:
- **Hızlı İşlem tam satış akışı:** ürün ekleme, çoklu ödeme dağılımı,
  kasa/cari bakiye güncellemesi, stok hareketi - hepsi doğru sonuç verdi
- **Fatura Oluştur (AJAX akışı):** çoklu ödeme ile fatura oluşturma
  doğru çalıştı
- **Prim oluşturma + ödeme akışı:** uçtan uca doğrulandı
- **Saat dilimi doğruluğu:** UTC 14:22 sunucu saati, kayıtlarda doğru
  şekilde yerel saat (17:22, +3 Türkiye farkı) olarak saklandığı
  doğrulandı
- **Türkçe karakter kodlaması:** HEX karşılaştırmasıyla PHP'nin ürettiği
  UTF-8 baytlarıyla veritabanına kaydedilen baytların birebir aynı
  olduğu doğrulandı

### Kurulum artık nasıl (XAMPP'te)
1. Proje dosyalarını `htdocs/` altına koy (öncekiyle aynı)
2. **MySQL servisini çalıştırmana hiç gerek yok**
3. XAMPP'in PHP kurulumunda `pdo_sqlite` eklentisi genelde zaten aktiftir;
   değilse `php.ini`'de `extension=pdo_sqlite` satırının başındaki `;`
   işaretini kaldırıp Apache'yi yeniden başlat
4. Tarayıcıdan `http://localhost/.../public/login.php` (ya da register)
   açtığın an `data/meda.sqlite` otomatik oluşuyor, şema otomatik yükleniyor
5. `sql/make_admin.php KULLANICI_ADI` komutu hâlâ aynı şekilde çalışıyor

## 🐛 3 Gerçek Hata Bulundu ve Düzeltildi (18 Temmuz 2026 - devam)

Efe'nin SQLite geçişi sonrası yaptığı detaylı testte bulduğu hatalar:

### 1. Hesap/Kasa hareketlerinde saat hep "00:00" görünüyordu
Kök neden: "Evrak Tarihi" gibi sadece TARİH seçtiren alanlar (saat yok)
kullanıldığında, kod saat kısmını kasıtlı olarak "00:00:00"a sabitliyordu
- yani günün hangi saatinde işlem yapılırsa yapılsın, kayıtlarda hep gece
yarısı olmuş gibi görünüyordu. Bu, **7 farklı dosyada** aynı desende
tekrarlanan sistematik bir hataydı: `hizli_islem_yap.php`,
`api/hesap_hareketi_ekle.php`, `fatura_kaydet.php`, `makbuz_olustur.php`,
`tahsilat_makbuzu_kaydet.php`, `teklif_kaydet.php`,
`api/kasa_hareket_ekle.php`. Artık kullanıcının seçtiği TARİH korunuyor
ama SAAT kısmı gerçek işlem anının saatini alıyor.

### 2. Cari Detay'daki "Yeni Hareket" özelliği kasaya hiç yansımıyordu
En kritik bulgulardan biri: Cari Detay sayfasındaki "Yeni Hareket" ekle
penceresinde **kasa/hesap seçme alanı hiç yoktu** - sadece cari bakiyesini
güncelliyordu, hiçbir kasa/hesap bakiyesini etkilemiyordu. İlginç olan:
arka uçtaki `api/hesap_hareketi_ekle.php` API'si zaten `hesap_id`
parametresini tam olarak destekliyordu (hem cari hem hesap bakiyesini
doğru güncelliyordu) - sadece FORM'da bu alan unutulmuştu. `cari_detay.php`
formuna "HANGİ KASADAN/HESAPTAN?" seçim alanı eklendi - artık bu ekrandan
girilen bir ödeme/tahsilat da gerçek bir kasa hareketi oluşturuyor.

### 3. Satış sonrası ilgili carinin sayfasına yönlendirme (özellik isteği)
Efe'nin isteği üzerine: bir işlem (Hızlı İşlem) tamamlandıktan sonra artık
otomatik olarak o carinin **Cari Detay** sayfasına yönlendiriliyor -
böylece güncel bakiye/hareket hemen görülebiliyor.
- **ALIŞ/İADE**: işlem biter bitmez direkt Cari Detay'a gidiyor.
- **SATIŞ**: önce Prim popup'ı çıkıyor (var olan davranış korundu) -
  popup "Hayır" ile kapatılsın ya da bir prim kaydedilip kapansın, HANGİ
  yoldan kapanırsa kapansın, kapandığı anda Cari Detay'a yönlendiriliyor.

### Canlı testle doğrulandı
- Bir satış yapılıp kaydedilen `hareket_tarihi`'nin gerçek saati
  (`19:35:27` gibi, `00:00:00` değil) yansıttığı doğrulandı.
- Cari Detay'dan "Yeni Hareket" ile bir kasa seçilip 50 TL'lik bir
  tahsilat girildi - hem cari bakiyesi hem seçilen kasanın bakiyesi
  (1000 → 1200 [satış] → 1100 [alış] → 1150 [yeni hareket]) doğru
  şekilde güncellendiği zincirleme olarak doğrulandı.
- ALIŞ işlemi sonrası yönlendirmenin doğrudan `cari_detay.php?id=X`'e
  gittiği, SATIŞ işleminde ise önce Prim popup'ının URL parametresiyle
  birlikte doğru cari_id'yi taşıdığı doğrulandı.

## Kurulum adımları

> **NOT (18 Temmuz 2026):** Proje artık SQLite kullanıyor, aşağıdaki
> MySQL adımları güncelliğini yitirdi - detaylar için yukarıdaki
> "MySQL/MariaDB'den SQLite'a Komple Geçiş" bölümüne bakın. Özet: MySQL
> servisi çalıştırmana gerek yok, veritabanı dosyası ilk sayfa
> yüklemesinde otomatik oluşuyor.

### XAMPP kullanıyorsan (hızlı özet - GÜNCEL)

1. `php-project` klasörünün tamamını `htdocs/meda-php/` olarak kopyala
   (Windows: `C:\xampp\htdocs\meda-php\`).
2. XAMPP Control Panel'den sadece **Apache**'yi başlat (MySQL'e gerek yok).
3. XAMPP'in PHP kurulumunda `pdo_sqlite` eklentisi genelde zaten aktiftir;
   emin değilsen `php.ini`'de `extension=pdo_sqlite` satırının başındaki
   `;` işaretini kaldırıp Apache'yi yeniden başlat.
4. Tarayıcıda `http://localhost:PORT/meda-php/public/register.php` aç
   (PORT'u kendi XAMPP portunla değiştir), bir hesap oluştur - bu ilk
   istekte `data/meda.sqlite` otomatik oluşturulup şema otomatik yüklenir.
5. `login.php` ile giriş yap.

### Genel (XAMPP dışı) kurulum

1. `data/` klasörünün yazılabilir olduğundan emin ol (veritabanı dosyası
   buraya otomatik oluşturulacak).
2. `config/config.php` içindeki `BASE_URL` otomatik algılanıyor. Sadece
   klasör adın `meda-php` değilse `APP_BASE_PATH` sabitini güncelle.
3. Web sunucunuzun **DocumentRoot'unu `public/` klasörüne** yönlendirin
   (Apache/Nginx). `config/`, `includes/`, `sql/`, `data/` klasörleri
   **web'den erişilemez** olmalı (public'in dışında tutuldu, bu yüzden
   zaten güvenli).
4. İlk sayfa isteğinde veritabanı otomatik kurulur - ayrıca bir "şemayı
   içe aktar" adımına gerek yok.

### (Artık ilgisiz) Eski MySQL adımları - sadece tarihsel referans

<details>
<summary>Genişletmek için tıkla</summary>

1. MySQL/MariaDB'de veritabanı oluşturun ve `sql/schema.sql`'i içe
   aktarın (bu dosya artık SQLite formatında, MySQL'de çalışmaz).
2. `config/database.php` MySQL bağlantı bilgilerini içeriyordu - artık
   SQLite dosya yoluna bakıyor.
3. Eski `instance/meda.db` (Flask) verisini aktarmak için
   `sql/migrate_from_sqlite.php` kullanılıyordu - bu betik artık geçerli
   değil (bkz. dosyanın kendi üstündeki not).
4. Kullanıcı parolalarını düzeltmek için `sql/reset_legacy_passwords.php`
   kullanılıyordu - hâlâ çalışır ama yeni bir SQLite kurulumunda muhtemelen
   hiç ilgili kullanıcı bulunamayacak (zararsız, sadece uyarı verir).

</details>


---

## 🧾 GİB e-Fatura XML Uyumluluk Kontrolü (18 Temmuz 2026)

Efe'nin isteği üzerine GİB'in güncel UBL-TR standardı araştırıldı ve
`includes/fatura_xml.php` ile karşılaştırıldı. İki gerçek uyumsuzluk
bulundu ve düzeltildi:

1. **`ProfileID` yanlış değer kullanıyordu**: Kod, uygulamanın kendi
   içindeki kısa Türkçe senaryo etiketlerini (`"TEMEL"`, `"TİCARİ"`)
   doğrudan XML'e yazıyordu. GİB'in UBL-TR standardında geçerli değerler
   tam isimlerdir: `TEMELFATURA`, `TICARIFATURA`, `YOLCUBERABERFATURA`,
   `IHRACAT`, `EARSIVFATURA` vb. Bu haliyle GİB şematronu XML'i reddederdi.
   Bir eşleme fonksiyonu eklendi (`TEMEL→TEMELFATURA`,
   `TİCARİ→TICARIFATURA`, `İADE→TEMELFATURA` + ayrıca `InvoiceTypeCode=IADE`).

2. **`InvoiceTypeCode=ALIS` GİB'in kod listesinde geçerli bir değer değil**
   (geçerli değerler: `SATIS`, `IADE`, `TEVKIFAT`, `ISTISNA`,
   `OZELMATRAH`, `IHRACKAYITLI` vb.). Ayrıca kavramsal olarak da bir
   sorun var: e-Fatura SATICI tarafından üretilir - bir "alış faturası"
   için e-Fatura XML'i üretmek, GİB akışına uymuyor (o XML zaten
   tedarikçiden gelir). Bu, koddan kaldırılmadı (davranış değiştirilmedi)
   ama net bir kod yorumuyla işaretlendi - Efe'nin bu özelliği (a) sadece
   dahili görüntü için mi yoksa (b) gerçekten bir Özel Entegratör'e
   bağlayıp GİB'e göndermek için mi kullanacağı henüz netleşmedi (bkz.
   README "Bilinen Sınırlamalar").

**Önemli genel not**: gerçek bir e-Fatura'nın GİB'e iletilebilmesi için
sadece doğru XML üretmek yetmiyor - ayrıca mali mühür/XAdES-BES ile
imzalanması ve bir Özel Entegratör ya da GİB Portalı üzerinden iletilmesi
gerekiyor. Şu anki kod sadece XML üretiyor, imzalama/iletim yapmıyor.
GİB'in kılavuzları ve kod listeleri sık güncelleniyor (2026'da bile
Şubat/Mart/Nisan aylarında güncellemeler olmuş) - gerçek üretim kullanımı
için kendi XML üretecinizi doğrudan GİB'e bağlamak yerine bilinen bir Özel
Entegratör kullanmak çok daha güvenli ve güncel kalır.

**Canlı testle doğrulandı**: gerçek bir fatura oluşturulup XML çıktısı
alındı, `<cbc:ProfileID>TEMELFATURA</cbc:ProfileID>` doğru şekilde
üretildiği onaylandı (önceden `TEMEL` yazıyordu).

---

## 📊 Ana Sayfa (Dashboard) - Gerçek Veri Bağlantısı Eksikti (18 Temmuz 2026)

Efe'nin test sırasında bulduğu önemli bir hata: `public/index.php`'deki
**"Son Faturalar", "Son Servisler" ve "Bugünün Özeti"** bölümleri hiçbir
gerçek veriye bağlı değildi - tamamen statik (sabit) HTML olarak
duruyordu ("Henüz fatura yok" / "Henüz servis kaydı yok" metinleri ve
sabit "0" değerleri hep aynı kalıyordu). Sayfanın üstündeki 8 istatistik
kartı `api/dashboard_data.php`'den AJAX ile gerçek veri çekiyordu ama bu
üç bölüm hiç bu API'ye bağlanmamıştı.

Ayrıca `api/dashboard_data.php`'nin kendisinde de 2 hata bulundu:
- **`hizli_satis` sabit "3" değeri döndürüyordu** - kodun kendi yorum
  satırı bunun orijinal Flask uygulamasından miras kaldığını
  doğruluyordu ("Flask tarafında da sabit değer olarak dönüyordu").
  Artık gerçek bir toplam satış sayısı (makbuz + fatura, SATIŞ türünde)
  hesaplanıyor.
- **`toplam_personel` hiç sorgulanmıyordu** - JS tarafı `data.toplam_personel`
  okumaya çalışıyordu ama API bu alanı hiç döndürmüyordu, bu yüzden hep
  "0" görünüyordu. Artık PERSONEL türündeki carilerin gerçek sayısı
  dönüyor.

`api/dashboard_data.php`'ye eklenenler: son 5 fatura (cari adı, tarih,
tutar ile), son 5 teknik servis kaydı (cari, ürün, durum ile), ve
"Bugünün Özeti" için bugüne ait gelen fatura/giden fatura/servis
kaydı/kasa işlem sayıları. `index.php`'nin JS tarafı bu verilerle
tabloları/kartları dolduracak şekilde güncellendi.

**Canlı testle doğrulandı**: gerçek bir fatura, teknik servis kaydı ve
kasa işlemi oluşturulup API'nin doğru döndürdüğü, `index.php`'nin bu
verileri doğru render ettiği (HTTP 200, hatasız) onaylandı.

---

## 🔗 Her Yerde Belge Numarasına Tıklayınca Belgeye Gitme (18 Temmuz 2026)

Efe'nin isteği üzerine, cari adı tıklanabilirliğinin devamı olarak: fatura
no, makbuz no, servis no ve teklif no nerede görünürse görünsün artık
tıklanabilir - o belgenin kendi sayfasına götürüyor. 7 dosyada düzeltildi:

- `faturalar.php` → `fatura_olustur.php?id=X` (bu sayfa hem oluşturma hem
  düzenleme/görüntüleme için kullanılıyor)
- `makbuzlar.php` → `makbuz_detay.php?id=X`
- `teklifler.php` → `teklif_olustur.php?id=X`
- `teknik_servis_listesi.php` → `teknik_servis_duzenle.php?id=X`
- `evraklar.php` - fatura/makbuz/teklif/servis birleşik listesindeki 4
  belge numarası sütunu da düzeltildi
- `cari_detay.php` - o carinin fatura ve servis geçmişindeki numaralar
- `index.php` (Ana Sayfa/Dashboard) - "Son Faturalar" ve "Son Servisler"
  bölümlerindeki numaralar da tıklanabilir yapıldı (bunun için
  `api/dashboard_data.php`'nin sorgularına `id` alanı eklendi)

**Bilinçli olarak dokunulmayan yerler:** `fatura_cikti.php`,
`makbuz_cikti.php`, `teklif_cikti.php`, `teknik_servis_cikti.php` gibi
yazdırma çıktıları ve `makbuz_detay.php`, `teknik_servis_duzenle.php` gibi
belgenin KENDİ detay sayfaları - bunlar zaten o belgenin numarasını kendi
başlığında gösteriyor, kendine link vermenin bir anlamı yok.

**Canlı testle doğrulandı:** gerçek bir fatura, makbuz, teklif ve teknik
servis kaydı oluşturulup her birinin ilgili liste sayfalarında doğru
`?id=X` linkine sahip olduğu doğrulandı. Genel regresyon testi (33 sayfa)
sorunsuz.

---

## 💳 Ödeme Dağılımına "VERESİYE" Kanalı Eklendi (19 Temmuz 2026)

Efe ile Hesap/Kasa mantığı konuşulurken bulunan bir tutarsızlık ele alındı:
"VERESİYE" türündeki hesaplar (kasalar), Ödeme Dağılımı panelindeki
Hesap/Kasa seçicisinde diğer gerçek kasalarla (KASA/BANKA/POS) aynı
listede çıkıyordu. Biri yanlışlıkla oraya tutar girip "Veresiye Kasası"
seçseydi, sistem bunu **gerçek bir ödeme gibi** işleyip cari borcunu
yanlışlıkla azaltacaktı - halbuki veresiye kavramsal olarak "ödeme
yapılmadı" demek.

### Çözüm (Efe'nin onayladığı tasarım)
1. **"Ödeme Türü" listesine VERESİYE eklendi** (Hızlı İşlem, Fatura
   Oluştur, Makbuz Oluştur'un üçünde de) - artık NAKİT/KREDİ KARTI/
   HAVALE/EFT/ÇEK/SENET'in yanında bir seçenek daha var.
2. **Bir satırda VERESİYE seçilince o satırın Hesap/Kasa seçicisi devre
   dışı kalıyor** - çünkü veresiyede gerçek bir kasa hareketi yok.
   (Not: Makbuz Oluştur native form gönderimi kullandığı için - `name`
   öznitelikli input'lar - orada gerçek `disabled` kullanılmadı, çünkü
   disabled alanlar tarayıcı tarafından hiç gönderilmez ve bu
   `odeme_turu[]`/`odeme_hesap_id[]`/`odeme_tutar[]` dizilerinin
   hizasını bozardı. Onun yerine görsel bir "yumuşak devre dışı
   bırakma" - `pointer-events:none` + soluk görünüm - kullanıldı, alan
   yine de boş değerle gönderiliyor.)
3. **VERESİYE satırının tutarı "ödenen toplam"a hiç dahil edilmiyor** -
   otomatik olarak kalan/borç sayılıyor, hiçbir kasa bakiyesini
   etkilemiyor, hiçbir hesap hareketi oluşturmuyor.
4. **VERESİYE türü hesaplar artık Ödeme Dağılımı'nın Hesap/Kasa
   seçicisinden tamamen çıkarıldı** (Hızlı İşlem, Fatura Oluştur, Makbuz
   Oluştur VE Cari Detay'daki "Yeni Hareket" ekranında) - artık
   karışıklık olmasın diye. `Hesaplar` sayfasında VERESİYE türü hesap
   tanımlamak hâlâ mümkün, sadece ödeme seçicilerinde görünmüyor.
5. **Bu veresiye tutarının gerçek muhasebeleşmesi (tahsilat/ödeme) daha
   sonra Cari Detay'daki "Yeni Hareket" ekranından yapılıyor** - orası
   zaten kasa seçimini zorunlu tutuyor, gerçek para hareketi orada oluşur.

### Canlı testle doğrulandı
- **Karma ödeme** (1000 TL satış, 600 NAKİT + 400 VERESİYE): kasa sadece
  600 arttı, cari bakiyesi sadece 400 borçlandı (1000 değil), makbuzun
  özet ödeme türü "NAKİT + VERESİYE" oldu, hesap_hareketleri'nde sadece
  NAKİT için 1 satır oluştu (VERESİYE için hiç oluşmadı).
- **Tamamen VERESİYE** (Fatura: 800 TL tamamı veresiye + Makbuz: 400 TL
  tamamı veresiye): her ikisinde de kasa hiç değişmedi, cari doğru
  tutarlarla borçlandı, hiçbir hesap hareketi oluşmadı.
- VERESİYE türü hesapların artık hiçbir ödeme dropdown'ında görünmediği
  doğrulandı. Genel regresyon testi (33 sayfa) sorunsuz.

---

## 📊 İstatistik Kartlarına Tıklayınca Detay Popup'ı (19 Temmuz 2026)

Efe'nin isteği üzerine, `cariler.php`, `stok_listesi.php`,
`teknik_servis_listesi.php` ve `yapilacaklar.php` sayfalarının üstündeki
istatistik kartları (TOPLAM CARİ, DÜŞÜK STOK, BEKLEYEN gibi) artık
tıklanabilir - her birine tıklayınca o kategorideki kayıtları listeleyen
bir popup açılıyor.

### Neler eklendi
- **`yapilacaklar.php`**: Bekleyen İşler / Yüksek Öncelikli / Tamamlananlar
  - her biri kendi listesini gösteriyor (başlık, öncelik/tamamlanma tarihi ile)
- **`cariler.php`**: Toplam Cari / Müşteriler / Tedarikçiler / Personel -
  her biri o türdeki carileri (cari detayına linkli) listeliyor
- **`stok_listesi.php`**: Toplam Ürün / Toplam Stok (en yüksek stoklu
  ürünler) / **Düşük Stok** (min. stok altındaki ürünler - gerçek
  anlamda aksiyona dönüştürülebilir bir rapor) / Kategoriler (kategori
  başına ürün sayısı dağılımı)
- **`teknik_servis_listesi.php`**: Toplam Servis / Bekleyen / İşlemde /
  Tamamlanan - her biri o durumdaki servisleri listeliyor

### Teknik detaylar
- Her sayfadaki asıl liste **sayfalanmış** (30 kayıt/sayfa) olduğu için,
  popup'ların doğru/tam veri göstermesi adına **ayrı, sayfalanmamış
  sorgular** eklendi (performans için her biri en fazla 100 kayıtla
  sınırlı - "hızlı bakış" raporu olduğu için tam bir sayfalama
  gerekmiyor).
- Veriler AJAX ile değil, sayfa yüklenirken **doğrudan JSON olarak
  gömülüyor** - ekstra bir sunucu isteği gerekmiyor, popup anında açılıyor.
- Popup içindeki her kayıt, ilgili detay sayfasına (`cari_detay.php`,
  `stok_detay.php`, `teknik_servis_duzenle.php`) tıklanabilir link olarak
  gösteriliyor.

**Canlı testle doğrulandı**: 4 sayfa da gerçek test verisiyle (müşteri,
tedarikçi, düşük stoklu ürün, servis kaydı) test edildi - her popup'ın
doğru veriyi içerdiği hem sunucu tarafında (gömülü JSON içeriği) hem
tarayıcı tarafında (jsdom ile gerçek JS fonksiyonu çalıştırılarak, modal
içeriğinin doğru dolduğu) doğrulandı. Genel regresyon testi (33 sayfa)
sorunsuz.

---

## 🏷️ Ürün Kategorileri - Serbest Metin Yerine Tanımlı Liste (19 Temmuz 2026)

Efe'nin isteği üzerine: ürün eklerken kategori artık elle yazılmıyor -
önceden tanımlanan bir listeden dropdown ile seçiliyor.

### Neler eklendi
- **Yeni tablo**: `kategoriler` (id, kategori_adi UNIQUE, created_at)
- **Yeni sayfa**: `kategori_yonetim.php` - kategori ekleme formu + tanımlı
  kategorilerin listesi (her birinin kaç üründe kullanıldığı gösteriliyor,
  o sayıya tıklayınca `stok_listesi.php`'ye filtre ile gidiyor)
- **Yeni sayfa**: `kategori_sil.php` - bir kategoriyi listeden siler.
  `urunler.kategori` foreign key değil, serbest metin olarak saklandığı
  için bu silme işlemi **mevcut ürünlerin kategori bilgisini hiç
  etkilemiyor** - sadece bundan sonra yeni ürün eklerken o seçenek
  dropdown'da görünmüyor.
- `stok_ekle.php` ve `stok_duzenle.php`'deki kategori `<input type="text">`
  alanları `<select>` dropdown'a çevrildi. Düzenleme sayfasında, ürünün
  MEVCUT kategorisi tanımlı listede yoksa (örn. bu özellikten önce serbest
  metin girilmişse) dropdown'da kaybolmaması için otomatik olarak listeye
  ekleniyor.
- `stok_listesi.php`'ye "KATEGORİLER" butonu eklendi, direkt
  `kategori_yonetim.php`'ye gidiyor.

### Mevcut kurulumlar için otomatik migrasyon
`kategoriler` tablosu `schema.sql`'e sonradan eklendiği için, zaten
çalışan bir veritabanında bu tablo hiç olmayabilir. Kullanıcının ayrıca
bir migrasyon komutu çalıştırmasına gerek kalmasın diye,
`config/database.php`'ye bir kontrol eklendi: veritabanı dosyası zaten
varsa ama `kategoriler` tablosu yoksa, tablo otomatik oluşturuluyor VE
**ürünlerde daha önce serbest metin olarak girilmiş kategoriler otomatik
olarak içine aktarılıyor** - kullanıcı hiçbir şey yapmadan mevcut
kategorileri dropdown'da hazır buluyor. (Elle çalıştırmak isteyenler için
aynı mantığı yapan `sql/add_kategoriler.sql` da eklendi.)

### Kapsam notu
Bu değişiklik öncelik olarak **Stok Ekle/Düzenle** sayfalarına
uygulandı. Fatura/Makbuz/Teklif/Teknik Servis sayfalarındaki "hızlı ürün
ekle" modallarında kategori hâlâ serbest metin - bu bilinçli bir kapsam
sınırlaması, istenirse ayrı bir işte genişletilebilir.

**Canlı testle doğrulandı**: (1) Sıfırdan kurulumda `kategoriler`
tablosunun şema ile geldiği, (2) mevcut bir veritabanında tablo silinip
gerçek bir ürünün serbest-metin kategorisiyle birlikte tekrar sunucuya
istek atıldığında tablonun otomatik oluşup o kategorinin otomatik
aktarıldığı, (3) yeni kategori ekleme, (4) dropdown'da doğru seçeneklerin
çıkması, (5) bir ürünü seçili kategoriyle kaydetme, (6) düzenleme
sayfasında doğru kategorinin "selected" gelmesi, (7) kategori silmenin
mevcut ürün verisini bozmadığı doğrulandı. Genel regresyon testi
(33 sayfa) sorunsuz.

---

## 🛡️ Web Üzerinden Kullanıcı/Admin Yönetimi (19 Temmuz 2026)

Efe'nin isteği üzerine: bir kullanıcıyı admin yapmak için artık komut
satırından `php sql/make_admin.php KULLANICI_ADI` çalıştırmaya gerek yok
- yeni `kullanici_yonetim.php` sayfasından tek tıkla yapılabiliyor.

### Neler eklendi
- **`kullanici_yonetim.php`**: tüm kullanıcıları (adı, e-postası, mevcut
  yetkisi ile) listeler, her biri için "ADMİN YAP" / "ADMİNLİKTEN AL"
  butonu var.
- **"Tavuk-yumurta" sorunu çözüldü**: sistemde HİÇ admin yoksa (örn. ilk
  kurulum sonrası), bu sayfaya giriş yapmış HERHANGİ bir kullanıcı
  erişip ilk admin'i atayabiliyor. En az bir admin oluştuktan sonra
  sayfa otomatik olarak sadece admin'lere kapanıyor.
- **Güvenlik**: sistemdeki TEK admin kendi (ya da son admin'in) yetkisini
  kaldıramıyor - aksi halde kimse admin gerektiren işlemlere
  (Numara Yönetimi'nin "Tümünü Sıfırla"sı gibi) erişemez hale gelirdi.
- Navbar'a 🛡️ ikonlu bir link eklendi - sadece admin'lere (ya da hiç
  admin yokken herkese) görünüyor.
- Rol değişikliği **anında etkili** oluyor - `current_user()` zaten her
  sayfa yüklemesinde rolü veritabanından taze çektiği için, admin
  yapılan kullanıcının tekrar giriş yapmasına gerek yok.

**Canlı testle doğrulandı**: (1) hiç admin yokken normal bir kullanıcının
sayfaya erişip kendini admin yapabildiği, (2) bir admin oluştuktan sonra
YENİ bir normal kullanıcının sayfaya erişememesi (index.php'ye
yönlendirilmesi) ve navbar'da ikonun görünmemesi, (3) admin kullanıcı
için ikonun doğru göründüğü, (4) tek admin'in kendi yetkisini kaldırma
girişiminin engellendiği doğrulandı. Genel regresyon testi (33 sayfa)
sorunsuz.

---

## 🎨 Kategoriler Popup'ında CSS Renk Hatası (19 Temmuz 2026)

Efe'nin bulduğu bir hata: `stok_listesi.php`'deki "KATEGORİLER" istatistik
kartı popup'ı, içerik neredeyse görünmez ("soluk"/okunaksız) şekilde
açılıyordu. Kök neden: kategori satırları için kullanılan `<div
class="list-group-item">` elemanına `background:transparent` verilmişti
ama **`color` (yazı rengi) hiç ayarlanmamıştı** - Bootstrap'ın varsayılan
koyu yazı rengi, sitenin koyu temasında neredeyse hiç kontrast
oluşturmuyordu. Aynı sayfadaki diğer popup satırları (`<a>` etiketli
olanlar) doğru şekilde `color:var(--text-primary)` içeriyordu, sadece bu
`<div>` etiketli (link olmayan) satır atlanmıştı.

`yapilacaklar.php`'nin popup'ında da (Bekleyen/Yüksek Öncelikli/
Tamamlanan listesi, o da `<div>` kullanıyor) aynı eksik bulunup
düzeltildi. Tüm projede `list-group-item` kullanılan yerler taranıp
başka eksik kalmadığı doğrulandı.

**Canlı testle doğrulandı**: gerçek bir ürün oluşturulup Stok Listesi
sayfasının render ettiği JS kodunda `color:var(--text-primary)`'nin artık
doğru yerinde olduğu onaylandı. Genel regresyon testi (33 sayfa) sorunsuz.

---

## 📜 İstatistik Popup'larındaki 100 Kayıt Sınırı Kaldırıldı (19 Temmuz 2026)

Efe'nin sorusu üzerine: `cariler.php`, `stok_listesi.php`,
`teknik_servis_listesi.php`'deki istatistik kartı popup'ları önceden en
fazla 100 kayıt gösteriyordu (performans amaçlı bilinçli bir sınırdı).
Efe 100'den fazla ürün/cari/servis olabileceğini belirtip sınırın
kaldırılmasını istedi - zaten popup'ta scroll (`modal-dialog-scrollable`)
olduğu için bunun sorun olmayacağını söyledi.

Tüm `LIMIT 100` ifadeleri ve "İlk 100 kayıt gösteriliyor" notları
kaldırıldı - popup'lar artık kaç kayıt olursa olsun tamamını gösteriyor,
scroll ile geziliyor.

**Canlı testle doğrulandı**: 200 test ürünü oluşturulup popup verisinin
gerçekten tüm 200 kaydı içerdiği (önceden 100'de kesiliyordu)
doğrulandı. Genel regresyon testi (33 sayfa) sorunsuz.

---

## 📥 GİB e-Fatura XML'inden ALIŞ Faturası İçe Aktarma (19 Temmuz 2026)

Efe'nin isteği üzerine: `alis_fatura_olustur.php`'ye, tedarikçiden gelen
gerçek bir GİB e-Fatura (UBL-TR) XML dosyasını yükleyip otomatik olarak
fatura oluşturma özelliği eklendi.

### Akış
1. **Yükle**: `alis_fatura_olustur.php`'nin üstündeki yeni bölümden XML
   dosyası seçilip yüklenir (`fatura_xml_yukle.php`).
2. **Ayrıştır**: `includes/fatura_xml_parser.php` (DOMDocument/DOMXPath,
   UBL namespace'leri kayıtlı) XML'i okuyup tedarikçi bilgilerini, fatura
   bilgilerini ve ürün kalemlerini çıkarır. Bu adımda **hiçbir veritabanı
   yazması yok** - sadece okuma. Sonuç session'a konur.
3. **Önizle**: `fatura_xml_onizleme.php` ayrıştırılan veriyi düzenlenebilir
   bir form olarak gösterir - cari VKN'ye göre eşleştirilip "YENİ CARİ
   AÇILACAK" / "MEVCUT CARİ GÜNCELLENECEK" / "MEVCUT CARİ KULLANILACAK"
   olarak işaretlenir; her ürün kalemi stok koduna göre eşleştirilip "YENİ
   ÜRÜN" / "EŞLEŞTİ - GÜNCELLENECEK" olarak işaretlenir. Satış fiyatı
   otomatik olarak alış fiyatının %30 fazlası önerilir ama bu alan
   dahil her şey düzenlenebilir.
4. **Kaydet**: Kullanıcı onaylayınca `fatura_xml_kaydet.php` gerçek
   veritabanı yazmalarını yapar (cari oluştur/güncelle, ürün
   oluştur/güncelle, fatura + fatura_detaylari + stok_hareketi).

### Efe'nin onayladığı tasarım kararları
- **Ürün eşleşirse**: alış/satış fiyatı ve stok miktarı yeni XML
  değerleriyle güncellenir (eski fiyat geçmişi zaten `stok_hareketleri`
  tablosunda korunuyor, kaybolmuyor).
- **Yeni ürün açılırsa**: kategorisiz açılır - kullanıcı sonra elle
  Kategori Yönetimi'nden atayabilir.
- **Cari VKN eşleşir ama unvan farklıysa**: cari, XML'deki yeni bilgilerle
  (unvan, adres, telefon, e-posta) güncellenir.
- **Fatura tarihi**: XML'deki gerçek `IssueDate` kullanılır (bugünün
  tarihi DEĞİL) - ürün/stok hareketlerine de bu tarih işlenir. Saat kısmı
  ise gerçek işlem anının saati (Efe'nin daha önce bulduğu "00:00 hatası"
  sınıfına düşülmedi).
- **Para birimi**: XML'de ne yazıyorsa (TRY/USD/EUR) o şekilde
  korunuyor, zorla TL'ye çevrilmiyor.
- **Ödeme**: Bu fatura tam veresiye (henüz ödeme girilmedi) olarak
  kaydediliyor - cari bakiyesi genel toplam kadar borçlanıyor. Gerçek
  ödeme daha sonra Cari Detay'daki "Yeni Hareket" ekranından (kasa
  seçimi zorunlu) yapılabilir.

### Canlı testle doğrulandı (gerçek bir GİB faturasıyla)
- **Yeni cari + yeni ürün senaryosu**: XML'den doğru VKN, unvan, adres,
  telefon, e-posta ile yeni cari açıldığı; doğru stok kodu, ürün adı,
  miktar, alış fiyatı ile yeni ürün açıldığı; satış fiyatının alış
  fiyatının tam %30 fazlası (4787.50 → 6223.75) olarak hesaplandığı;
  fatura tarihinin XML'deki gerçek tarih (2026-07-03, bugünün tarihi
  değil) olarak kaydedildiği; cari bakiyesinin genel toplam kadar
  (5745 TL) borçlandığı doğrulandı.
- **Eşleşen cari + eşleşen ürün senaryosu**: aynı VKN ile önceden
  farklı unvanlı bir cari, aynı stok koduyla farklı fiyatlı bir ürün
  oluşturulup XML tekrar yüklendi - önizlemede doğru şekilde "MEVCUT
  CARİ GÜNCELLENECEK" ve "EŞLEŞTİ - GÜNCELLENECEK" etiketlerinin
  çıktığı; kaydedince **hiçbir kopya kayıt oluşmadığı** (cari sayısı ve
  ürün sayısı aynı kaldı), cari unvanının ve ürün fiyatlarının doğru
  güncellendiği, stok miktarının doğru toplandığı (5+1=6) doğrulandı.

Genel regresyon testi sorunsuz.

---

## 💵 Hızlı İşlem Ürün Aramasında Döviz Bilgisi Kayboluyordu (19 Temmuz 2026)

Efe'nin bulduğu bir hata: Hızlı İşlem'de bir ürünü aratıp listede görünce
fiyatı hep "₺" ile gösteriyordu (örn. "11.57 ₺"), ama aynı ürüne "GÖSTER"
ile detayına bakınca doğru şekilde "11.57 USD" gösteriyordu.

**Kök neden**: `api/hizli_islem_urun_ara.php`, SQL sorgusunda `SELECT *`
ile tüm kolonları çekiyordu, ama JSON yanıtını oluştururken
(`array_map`) **`satis_fiyati_doviz` alanını tamamen atlıyordu**. JS
tarafındaki `var fiyatDoviz = urun.satis_fiyati_doviz || 'TL';` satırı bu
yüzden alanı hiç bulamayıp her zaman varsayılan 'TL'ye düşüyordu - kodun
kendisi (döviz≠TL ise orijinal para birimi + TL karşılığını gösterme
mantığı) zaten doğruydu, sadece veri hiç gelmiyordu.

Aynı desenle diğer ürün arama API'leri (`api/stok_ara.php`) kontrol
edildi - o zaten doğru şekilde hem `alis_fiyati_doviz` hem
`satis_fiyati_doviz`'i döndürüyordu, sorun sadece
`hizli_islem_urun_ara.php`'deydi.

**Canlı testle doğrulandı**: 11.57 USD fiyatlı bir test ürünü oluşturulup
API'nin artık doğru şekilde `"satis_fiyati_doviz": "USD"` döndürdüğü
onaylandı. Genel regresyon sorunsuz.

---

## 🔁 XML'den Mükerrer Fatura Kontrolü (19 Temmuz 2026)

Efe'nin bulduğu bir hata: aynı GİB e-Fatura XML dosyası tekrar tekrar
yüklenip her seferinde ayrı bir fatura olarak kaydedilebiliyordu - hiçbir
mükerrer (aynı faturanın tekrar) kontrolü yoktu.

### Çözüm
İki farklı, birbirini tamamlayan kontrol eklendi:

1. **GİB UUID'ye göre** (`cbc:UUID`) - GİB standardına göre her e-Fatura
   için benzersizdir, en güvenilir kontrol. XML ayrıştırıcıya
   (`fatura_xml_parser.php`) UUID okuma eklendi, `faturalar` tablosundaki
   zaten var olan `gib_uuid` kolonuna kaydediliyor.
2. **Tedarikçinin kendi fatura no'suna göre** (`cbc:ID`, örn.
   "EFS2026000003854") - UUID bir sebeple boş gelirse yedek kontrol,
   `aciklama` alanına "(Kaynak Fatura No: ...)" olarak işleniyor ve
   aranıyor.

Kontrol **iki ayrı yerde** yapılıyor (savunma derinliği):
- `fatura_xml_yukle.php`: XML yüklenir yüklenmez, önizlemeye gitmeden
  ÖNCE - mükerrerse önizlemeye hiç gidilmiyor, direkt mevcut faturanın
  sayfasına yönlendirilip net bir uyarı gösteriliyor.
- `fatura_xml_kaydet.php`: kaydet anında tekrar kontrol ediliyor (biri
  eski bir önizleme sayfasını tarayıcı geri tuşuyla tekrar gönderirse
  diye).

**Canlı testle doğrulandı**: gerçek bir XML dosyası ilk kez yüklenip
başarıyla kaydedildi (`gib_uuid` doğru şekilde saklandığı doğrulandı).
Aynı dosya İKİNCİ kez yüklenince önizlemeye hiç gidilmediği, direkt
mevcut faturanın sayfasına yönlendirildiği ve "Bu fatura zaten daha önce
içe aktarılmış!" uyarısının doğru göründüğü doğrulandı - veritabanında
hiçbir mükerrer kayıt oluşmadığı (aynı UUID'li fatura sayısı hep 1 kaldı)
onaylandı. Genel regresyon testi sorunsuz.

---

## 🏢 XML İçe Aktarımında Aynı VKN İçin Kopya Cari Oluşuyordu (19 Temmuz 2026)

Efe'nin bulduğu gerçek bir hata: aynı VKN'ye sahip bir tedarikçiden birden
fazla fatura XML'i içe aktarıldığında, her seferinde AYNI cari yerine
YENİ bir kopya cari oluşuyordu (Cariler listesinde aynı ünvan/VKN/telefon
ile 3 ayrı satır görünüyordu).

**Kök neden**: `fatura_xml_kaydet.php`, önizleme sayfasından gelen gizli
`mevcut_cari_id` alanına **körü körüne güveniyordu** - kendi başına bir
VKN kontrolü yapmıyordu. Bu alan bir sebeple boş/eski gelirse (örn.
tarayıcı geri tuşuyla eski bir önizleme sayfasına dönülüp tekrar
gönderilmesi) sistem "eşleşen cari yok" sanıp yeni bir tane oluşturuyordu
- halbuki önizleme sayfası (`fatura_xml_onizleme.php`) VKN'ye göre doğru
şekilde eşleştirme yapıyordu, sorun sadece kaydetme adımının buna
güvenmemesindeydi.

**Çözüm**: Fatura mükerrer kontrolünde kullanılan "savunma derinliği"
prensibi burada da uygulandı - `fatura_xml_kaydet.php` artık kaydetme
anında **kendi başına, formdan bağımsız olarak** VKN'ye göre cari arıyor.
Form'dan gelen `mevcut_cari_id` sadece VKN boşsa (nadir durum) yedek
olarak kullanılıyor.

**Canlı testle doğrulandı**: Önce aynı VKN ile bir cari oluşturulup,
sonra `fatura_xml_kaydet.php`'ye **bilerek boş `mevcut_cari_id`**
gönderilerek (Efe'nin karşılaştığı senaryonun birebir simülasyonu) test
edildi - sistem VKN'ye göre mevcut cariyi doğru buldu, **kopya
oluşturmadı**, mevcut carinin bilgilerini güncelledi. Genel regresyon
sorunsuz.

**Not**: Bu düzeltme sadece BUNDAN SONRAKİ içe aktarımlar için geçerli -
Efe'nin ekran görüntüsündeki zaten oluşmuş 3 kopya cari (2, 3, 4 numaralı
satırlar) otomatik silinmiyor/birleştirilmiyor, elle temizlenmesi
gerekiyor (Cariler sayfasından fazla olanları silip, varsa üzerlerindeki
fatura/hareket kayıtlarını doğru cariye taşımak gerekebilir).

---

## ❓ İptal Edilmiş Faturayla Eşleşme - Her Seferinde Onay İsteniyor (19 Temmuz 2026)

Efe'nin sorusu üzerine: mükerrer fatura kontrolü, XML'in eşleştiği kayıt
**İPTAL edilmiş** olsa bile onu engelliyordu - halbuki tedarikçi iptal
edilmiş bir faturayı düzeltip yeniden gönderebilir, bu durumda
engellemek doğru olmaz.

### Efe'nin onayladığı çözüm: her seferinde sor
Sessizce geçmek yerine, kullanıcıya HER SEFERİNDE açıkça soruluyor:

1. **`fatura_xml_yukle.php`**: eşleşen kayıt bulunursa artık `durum`una
   bakılıyor. **İPTAL değilse** eskisi gibi engelleniyor (gerçek
   mükerrer). **İPTAL ise** engellenmeden önizlemeye gidiliyor, ama bu
   bilgi session'a not düşülüyor.
2. **`fatura_xml_onizleme.php`**: iptal edilmiş bir kayıtla eşleşme
   varsa, sarı bir uyarı kutusu ve **zorunlu bir onay kutusu**
   ("Evet, iptal edilmiş kayda rağmen bunu yeni bir fatura olarak
   kaydet.") gösteriliyor - kutu işaretlenmeden form gönderilemiyor
   (HTML `required`).
3. **`fatura_xml_kaydet.php`**: sunucu tarafında da bağımsız olarak
   tekrar kontrol ediliyor - `durum != 'İPTAL'` ise yine engelleniyor;
   `durum = 'İPTAL'` ise `iptal_onay` alanının gerçekten "1" gönderilip
   gönderilmediği doğrulanıyor (sadece tarayıcının HTML `required`
   özelliğine güvenilmiyor - biri bunu atlatırsa diye).

### Canlı testle doğrulandı
Gerçek bir fatura kaydedilip iptal edildi, aynı XML tekrar yüklendi -
**engellenmeden** önizlemeye gittiği, uyarı kutusu ve onay kutusunun
doğru göründüğü doğrulandı. Onay kutusu **işaretlenmeden** kaydetmeye
çalışınca engellendiği (yeni fatura oluşmadığı), onay kutusu
**işaretlenince** başarıyla yeni bir fatura oluşturduğu (eski iptal
edilmiş kayıt yerinde kalırken) doğrulandı. Genel regresyon sorunsuz.
