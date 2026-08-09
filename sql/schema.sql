-- ============================================================
-- MEDA BİLGİSAYAR - MySQL Veritabanı Şeması
-- Flask/SQLAlchemy (models.py) + gerçek meda.db (SQLite) yapısından
-- birebir aktarılmıştır.
--
-- NOT: SQLite'daki "personel", "personel_izin", "personel_maas",
-- "personel_prim" tabloları models.py içinde tanımlı değildi ve
-- app.py içinde de kullanılmıyordu (muhtemelen yarım kalmış/kullanılmayan
-- bir modül). İleride "Personel" modülü PHP'ye taşınacaksa bunlar da
-- eklenecek; şimdilik referans olması için en altta yorum satırı
-- halinde bırakıldı.
-- ============================================================


-- ------------------------------------------------------------
-- 1. KULLANICILAR
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(80) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(200) NOT NULL,
    full_name VARCHAR(100),
    role VARCHAR(20) DEFAULT 'user',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- 2. CARİLER (Müşteri / Tedarikçi)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cariler (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unvan VARCHAR(200) NOT NULL,
    vergi_no VARCHAR(20),
    vergi_dairesi VARCHAR(100),
    adres TEXT,
    telefon VARCHAR(20),
    email VARCHAR(100),
    yetkili VARCHAR(100),
    cari_turu VARCHAR(20),
    bakiye DOUBLE DEFAULT 0,
    aciklama TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- 3. ÜRÜNLER
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS urunler (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    urun_kodu VARCHAR(50) NOT NULL UNIQUE,
    urun_adi VARCHAR(200) NOT NULL,
    barkod VARCHAR(50) UNIQUE,
    seri_no VARCHAR(50),
    urun_tipi VARCHAR(20) DEFAULT 'SIFIR',
    alis_fiyati DOUBLE DEFAULT 0,
    alis_fiyati_doviz VARCHAR(3) DEFAULT 'TL',
    satis_fiyati DOUBLE DEFAULT 0,
    satis_fiyati_doviz VARCHAR(3) DEFAULT 'TL',
    stok_miktari DOUBLE DEFAULT 0,
    min_stok DOUBLE DEFAULT 0,
    max_stok DOUBLE DEFAULT 0,
    kategori VARCHAR(100),
    birim VARCHAR(20) DEFAULT 'ADET',
    resim VARCHAR(255),
    aciklama TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- 3b. ÜRÜN KATEGORİLERİ (tanımlı kategori listesi - Stok Ekle'de
-- serbest metin yerine dropdown olarak kullanılır)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kategoriler (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kategori_adi VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- 4. TEKNİK SERVİS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS teknik_servis (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    servis_no VARCHAR(50) NOT NULL UNIQUE,
    barkod VARCHAR(50) UNIQUE,
    cari_id INT,
    urun_adi VARCHAR(200) NOT NULL,
    marka VARCHAR(100),
    model VARCHAR(100),
    seri_no VARCHAR(50),
    urun_tipi VARCHAR(50),
    gelis_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    teslim_tarihi DATETIME,
    durum VARCHAR(20) DEFAULT 'BEKLEMEDE',
    ariza_tanimi TEXT,
    yapilan_islem TEXT,
    notlar TEXT,
    aksesuarlar TEXT,
    kusurlar TEXT,
    iscilik_ucreti DOUBLE DEFAULT 0,
    malzeme_ucreti DOUBLE DEFAULT 0,
    toplam_ucret DOUBLE DEFAULT 0,
    odeme_durumu VARCHAR(20) DEFAULT 'BEKLEMEDE',
    garanti_durumu VARCHAR(20) DEFAULT 'GARANTİSİZ',
    garanti_bitis_tarihi DATETIME,
    fatura_no VARCHAR(50),
    fatura_tarihi DATETIME,
    teknik_personel VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_teknikservis_cari FOREIGN KEY (cari_id) REFERENCES cariler(id)
);

-- ------------------------------------------------------------
-- 5. SERVİS MALZEMELERİ
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS servis_malzemeler (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    teknik_servis_id INT,
    urun_id INT,
    urun_adi VARCHAR(200) NOT NULL,
    urun_kodu VARCHAR(50),
    barkod VARCHAR(50),
    miktar DOUBLE DEFAULT 1,
    birim VARCHAR(20) DEFAULT 'ADET',
    birim_fiyati DOUBLE DEFAULT 0,
    toplam_tutar DOUBLE DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_servismalzeme_servis FOREIGN KEY (teknik_servis_id) REFERENCES teknik_servis(id),
    CONSTRAINT fk_servismalzeme_urun FOREIGN KEY (urun_id) REFERENCES urunler(id)
);

-- ------------------------------------------------------------
-- 6. HESAPLAR (Banka / Kasa / Komisyon / POS / Virman)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hesaplar (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hesap_kodu VARCHAR(50) NOT NULL UNIQUE,
    hesap_adi VARCHAR(200) NOT NULL,
    hesap_turu VARCHAR(30) NOT NULL,
    para_birimi VARCHAR(3) DEFAULT 'TRY',
    bakiye DOUBLE DEFAULT 0,
    banka_adi VARCHAR(100),
    sube_adi VARCHAR(100),
    iban VARCHAR(50),
    hesap_no VARCHAR(50),
    komisyon_orani DOUBLE DEFAULT 0,
    komisyon_turu VARCHAR(20),
    aciklama TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- 7. FATURALAR
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS faturalar (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    fatura_no VARCHAR(50) NOT NULL UNIQUE,
    fatura_tarihi DATETIME NOT NULL,
    cari_id INT,
    fatura_turu VARCHAR(20) DEFAULT 'SATIŞ',
    fatura_tipi VARCHAR(20) DEFAULT 'E-FATURA',
    fatura_senaryosu VARCHAR(20) DEFAULT 'TEMEL',
    durum VARCHAR(20) DEFAULT 'TASLAK',
    odeme_turu VARCHAR(20) DEFAULT 'NAKİT',
    odeme_tarihi DATETIME,
    vade_tarihi DATETIME,
    para_birimi VARCHAR(3) DEFAULT 'TRY',
    doviz_kuru DOUBLE DEFAULT 1.0,
    ara_toplam DOUBLE DEFAULT 0,
    iskonto DOUBLE DEFAULT 0,
    iskonto_tutari DOUBLE DEFAULT 0,
    vergi_orani DOUBLE DEFAULT 20,
    vergi_tutari DOUBLE DEFAULT 0,
    genel_toplam DOUBLE DEFAULT 0,
    siparis_no VARCHAR(50),
    irsaliye_no VARCHAR(50),
    teslim_tarihi DATETIME,
    teslim_yeri VARCHAR(200),
    gib_uuid VARCHAR(50),
    gib_durum VARCHAR(20) DEFAULT 'OLUŞTURULDU',
    gib_hata TEXT,
    xml_content TEXT,
    html_content TEXT,
    aciklama TEXT,
    notlar TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_fatura_cari FOREIGN KEY (cari_id) REFERENCES cariler(id)
);

-- ------------------------------------------------------------
-- 8. FATURA DETAYLARI
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS fatura_detaylari (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    fatura_id INT,
    urun_id INT,
    urun_adi VARCHAR(200) NOT NULL,
    urun_kodu VARCHAR(50),
    barkod VARCHAR(50),
    birim VARCHAR(20) DEFAULT 'ADET',
    miktar DOUBLE DEFAULT 1,
    birim_fiyati DOUBLE DEFAULT 0,
    iskonto DOUBLE DEFAULT 0,
    iskonto_tutari DOUBLE DEFAULT 0,
    vergi_orani DOUBLE DEFAULT 20,
    vergi_tutari DOUBLE DEFAULT 0,
    ara_toplam DOUBLE DEFAULT 0,
    toplam_tutar DOUBLE DEFAULT 0,
    aciklama TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_faturadetay_fatura FOREIGN KEY (fatura_id) REFERENCES faturalar(id),
    CONSTRAINT fk_faturadetay_urun FOREIGN KEY (urun_id) REFERENCES urunler(id)
);

-- ------------------------------------------------------------
-- 9. TODO (YAPILACAKLAR)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS todos (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    priority VARCHAR(20) DEFAULT 'Orta',
    status VARCHAR(20) DEFAULT 'Bekliyor',
    due_date DATETIME,
    user_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    CONSTRAINT fk_todo_user FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ------------------------------------------------------------
-- 10. HESAP HAREKETLERİ
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hesap_hareketleri (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cari_id INT,
    hareket_turu VARCHAR(30) NOT NULL,
    islem_turu VARCHAR(10) NOT NULL,
    tutar DOUBLE DEFAULT 0,
    aciklama TEXT,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    ilgili_kisi VARCHAR(100),
    referans_no VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    hesap_id INT,
    hareket_no VARCHAR(50) UNIQUE,
    hareket_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    para_birimi VARCHAR(3) DEFAULT 'TRY',
    doviz_kuru DOUBLE DEFAULT 1.0,
    tutar_tl DOUBLE,
    odeme_turu VARCHAR(30),
    odeme_tarihi DATETIME,
    vade_tarihi DATETIME,
    cari_bakiye_oncesi DOUBLE,
    cari_bakiye_sonrasi DOUBLE,
    hesap_bakiye_oncesi DOUBLE,
    hesap_bakiye_sonrasi DOUBLE,
    created_by VARCHAR(100),
    CONSTRAINT fk_hesaphareket_cari FOREIGN KEY (cari_id) REFERENCES cariler(id),
    CONSTRAINT fk_hesaphareket_hesap FOREIGN KEY (hesap_id) REFERENCES hesaplar(id)
);

-- ------------------------------------------------------------
-- 11. KOMİSYON HAREKETLERİ
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS komisyon_hareketleri (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hesap_id INT,
    cari_id INT NOT NULL,
    fatura_id INT,
    komisyon_no VARCHAR(50) NOT NULL UNIQUE,
    tarih DATETIME NOT NULL,
    komisyon_turu VARCHAR(30) NOT NULL,
    matrah DOUBLE NOT NULL,
    oran DOUBLE NOT NULL,
    tutar DOUBLE NOT NULL,
    para_birimi VARCHAR(3) DEFAULT 'TRY',
    aciklama TEXT,
    referans_no VARCHAR(50),
    odeme_durumu VARCHAR(20) DEFAULT 'BEKLEMEDE',
    odeme_tarihi DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_komisyon_hesap FOREIGN KEY (hesap_id) REFERENCES hesaplar(id),
    CONSTRAINT fk_komisyon_cari FOREIGN KEY (cari_id) REFERENCES cariler(id),
    CONSTRAINT fk_komisyon_fatura FOREIGN KEY (fatura_id) REFERENCES faturalar(id)
);

-- ------------------------------------------------------------
-- 12. TAHSİLAT PLANLARI
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tahsilat_planlari (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cari_id INT,
    fatura_id INT,
    plan_no VARCHAR(50) NOT NULL UNIQUE,
    baslik VARCHAR(200) NOT NULL,
    toplam_tutar DOUBLE NOT NULL,
    para_birimi VARCHAR(3) DEFAULT 'TRY',
    taksit_sayisi INT DEFAULT 1,
    taksit_tutari DOUBLE,
    ilk_taksit_tarihi DATETIME,
    durum VARCHAR(20) DEFAULT 'AKTIF',
    aciklama TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tahsilatplan_cari FOREIGN KEY (cari_id) REFERENCES cariler(id),
    CONSTRAINT fk_tahsilatplan_fatura FOREIGN KEY (fatura_id) REFERENCES faturalar(id)
);

-- ------------------------------------------------------------
-- 13. TAHSİLAT TAKSİTLERİ
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tahsilat_taksitleri (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    plan_id INT,
    taksit_no INT NOT NULL,
    vade_tarihi DATETIME NOT NULL,
    tutar DOUBLE NOT NULL,
    para_birimi VARCHAR(3) DEFAULT 'TRY',
    durum VARCHAR(20) DEFAULT 'BEKLIYOR',
    odeme_tarihi DATETIME,
    odeme_tutari DOUBLE,
    gecikme_faizi DOUBLE DEFAULT 0,
    aciklama TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_taksit_plan FOREIGN KEY (plan_id) REFERENCES tahsilat_planlari(id)
);

-- ------------------------------------------------------------
-- 14. MAKBUZLAR
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS makbuzlar (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    makbuz_no VARCHAR(50) NOT NULL UNIQUE,
    makbuz_tarihi DATETIME NOT NULL,
    makbuz_turu VARCHAR(20) NOT NULL,
    cari_id INT,
    hesap_id INT,
    ara_toplam DOUBLE DEFAULT 0,
    iskonto DOUBLE DEFAULT 0,
    iskonto_tutari DOUBLE DEFAULT 0,
    vergi_orani DOUBLE DEFAULT 0,
    vergi_tutari DOUBLE DEFAULT 0,
    genel_toplam DOUBLE DEFAULT 0,
    para_birimi VARCHAR(3) DEFAULT 'TRY',
    odeme_turu VARCHAR(30) DEFAULT 'NAKİT',
    odeme_tarihi DATETIME,
    aciklama TEXT,
    notlar TEXT,
    durum VARCHAR(20) DEFAULT 'OLUŞTURULDU',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(100),
    CONSTRAINT fk_makbuz_cari FOREIGN KEY (cari_id) REFERENCES cariler(id),
    CONSTRAINT fk_makbuz_hesap FOREIGN KEY (hesap_id) REFERENCES hesaplar(id)
);

-- ------------------------------------------------------------
-- 15. MAKBUZ DETAYLARI
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS makbuz_detaylari (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    makbuz_id INT,
    urun_id INT,
    urun_adi VARCHAR(200) NOT NULL,
    urun_kodu VARCHAR(50),
    barkod VARCHAR(50),
    birim VARCHAR(20) DEFAULT 'ADET',
    miktar DOUBLE DEFAULT 1,
    birim_fiyati DOUBLE DEFAULT 0,
    iskonto DOUBLE DEFAULT 0,
    iskonto_tutari DOUBLE DEFAULT 0,
    ara_toplam DOUBLE DEFAULT 0,
    toplam_tutar DOUBLE DEFAULT 0,
    aciklama TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_makbuzdetay_makbuz FOREIGN KEY (makbuz_id) REFERENCES makbuzlar(id),
    CONSTRAINT fk_makbuzdetay_urun FOREIGN KEY (urun_id) REFERENCES urunler(id)
);

-- ------------------------------------------------------------
-- 16. TEKLİFLER
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS teklifler (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    teklif_no VARCHAR(50) NOT NULL UNIQUE,
    teklif_tarihi DATETIME NOT NULL,
    teklif_turu VARCHAR(20) NOT NULL,
    teklif_tipi VARCHAR(30) NOT NULL,
    cari_id INT,
    teknik_servis_id INT,
    konu VARCHAR(200) NOT NULL,
    aciklama TEXT,
    para_birimi VARCHAR(3) DEFAULT 'TRY',
    ara_toplam DOUBLE DEFAULT 0,
    iskonto DOUBLE DEFAULT 0,
    iskonto_tutari DOUBLE DEFAULT 0,
    vergi_orani DOUBLE DEFAULT 18,
    vergi_tutari DOUBLE DEFAULT 0,
    genel_toplam DOUBLE DEFAULT 0,
    gecerlilik_tarihi DATETIME,
    teslim_tarihi DATETIME,
    durum VARCHAR(20) DEFAULT 'TASLAK',
    onay_tarihi DATETIME,
    onaylayan VARCHAR(100),
    notlar TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(100),
    CONSTRAINT fk_teklif_cari FOREIGN KEY (cari_id) REFERENCES cariler(id),
    CONSTRAINT fk_teklif_servis FOREIGN KEY (teknik_servis_id) REFERENCES teknik_servis(id)
);

-- ------------------------------------------------------------
-- 17. TEKLİF DETAYLARI
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS teklif_detaylari (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    teklif_id INT,
    urun_id INT,
    urun_adi VARCHAR(200) NOT NULL,
    urun_kodu VARCHAR(50),
    barkod VARCHAR(50),
    birim VARCHAR(20) DEFAULT 'ADET',
    miktar DOUBLE DEFAULT 1,
    birim_fiyati DOUBLE DEFAULT 0,
    iskonto DOUBLE DEFAULT 0,
    iskonto_tutari DOUBLE DEFAULT 0,
    vergi_orani DOUBLE DEFAULT 18,
    vergi_tutari DOUBLE DEFAULT 0,
    ara_toplam DOUBLE DEFAULT 0,
    toplam_tutar DOUBLE DEFAULT 0,
    servis_turu VARCHAR(50),
    servis_suresi VARCHAR(50),
    servis_sure_miktari DOUBLE,
    aciklama TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_teklifdetay_teklif FOREIGN KEY (teklif_id) REFERENCES teklifler(id),
    CONSTRAINT fk_teklifdetay_urun FOREIGN KEY (urun_id) REFERENCES urunler(id)
);


-- ------------------------------------------------------------
-- 18. STOK HAREKETLERİ (defter - hangi ürün ne zaman/nereden/ne kadar
-- girdi-çıktı yaptı, hesap_hareketleri'nin stok karşılığı)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stok_hareketleri (

    id INTEGER PRIMARY KEY AUTOINCREMENT,
    urun_id INT NOT NULL,
    hareket_turu VARCHAR(20) NOT NULL, -- SATIŞ, ALIŞ, İADE, SERVİS, MANUEL, İPTAL
    miktar DOUBLE NOT NULL,            -- işaretli: pozitif = stok girişi, negatif = stok çıkışı
    stok_oncesi DOUBLE NOT NULL,
    stok_sonrasi DOUBLE NOT NULL,
    referans_no VARCHAR(50),
    aciklama VARCHAR(255),
    cari_id INT,                       -- satışta müşteri, alışta tedarikçi (manuel düzeltmede NULL)
    created_by VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stokhareket_urun FOREIGN KEY (urun_id) REFERENCES urunler(id),
    CONSTRAINT fk_stokhareket_cari FOREIGN KEY (cari_id) REFERENCES cariler(id)
);

-- ------------------------------------------------------------
-- SİPARİŞLER (Ön sipariş / veresiye notu)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS siparisler (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    siparis_no VARCHAR(50) NOT NULL UNIQUE,
    cari_id INT NOT NULL,
    siparis_tarihi DATETIME NOT NULL,
    durum VARCHAR(20) DEFAULT 'BEKLEMEDE',  -- BEKLEMEDE, FATURALANDI, İPTAL
    aciklama TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_siparis_cari FOREIGN KEY (cari_id) REFERENCES cariler(id)
);

-- ------------------------------------------------------------
-- SİPARİŞ DETAYLARI
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS siparis_detaylari (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    siparis_id INT NOT NULL,
    urun_id INT,
    urun_adi VARCHAR(200) NOT NULL,
    urun_kodu VARCHAR(50),
    barkod VARCHAR(50),
    birim VARCHAR(20) DEFAULT 'ADET',
    miktar DOUBLE DEFAULT 1,
    birim_fiyati DOUBLE DEFAULT 0,
    iskonto DOUBLE DEFAULT 0,
    iskonto_tutari DOUBLE DEFAULT 0,
    vergi_orani DOUBLE DEFAULT 18,
    vergi_tutari DOUBLE DEFAULT 0,
    ara_toplam DOUBLE DEFAULT 0,
    toplam_tutar DOUBLE DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_siparisdetay_siparis FOREIGN KEY (siparis_id) REFERENCES siparisler(id),
    CONSTRAINT fk_siparisdetay_urun FOREIGN KEY (urun_id) REFERENCES urunler(id)
);

-- ============================================================
-- SQLite'da bulunan ama models.py'de tanımlı olmayan/kullanılmayan
-- tablolar (personel, personel_izin, personel_maas, personel_prim):
-- İleride "Personel" modülü ele alınacaksa bu şemaya eklenecektir.
-- ============================================================

-- ------------------------------------------------------------
-- INDEKSLER (SQLite'da CREATE TABLE icinde tanimlanamadigi icin ayri)
-- ------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_cariler_unvan ON cariler(unvan);
CREATE INDEX IF NOT EXISTS idx_cariler_vergi_no ON cariler(vergi_no);
CREATE INDEX IF NOT EXISTS idx_cariler_telefon ON cariler(telefon);
CREATE INDEX IF NOT EXISTS idx_cariler_cari_turu ON cariler(cari_turu);
CREATE INDEX IF NOT EXISTS idx_urunler_urun_adi ON urunler(urun_adi);
CREATE INDEX IF NOT EXISTS idx_teknik_servis_created ON teknik_servis(created_at);
CREATE INDEX IF NOT EXISTS idx_teknik_servis_durum ON teknik_servis(durum);
CREATE INDEX IF NOT EXISTS idx_faturalar_tarihi ON faturalar(fatura_tarihi);
CREATE INDEX IF NOT EXISTS idx_faturalar_durum ON faturalar(durum);
CREATE INDEX IF NOT EXISTS idx_todos_user_status ON todos(user_id, status);
CREATE INDEX IF NOT EXISTS idx_hesap_hareketleri_tarihi ON hesap_hareketleri(hareket_tarihi);
CREATE INDEX IF NOT EXISTS idx_makbuzlar_tarihi ON makbuzlar(makbuz_tarihi);
CREATE INDEX IF NOT EXISTS idx_teklifler_tarihi ON teklifler(teklif_tarihi);
CREATE INDEX IF NOT EXISTS idx_teklifler_durum ON teklifler(durum);
CREATE INDEX IF NOT EXISTS idx_stok_hareketleri_urun ON stok_hareketleri(urun_id);
CREATE INDEX IF NOT EXISTS idx_stok_hareketleri_tarih ON stok_hareketleri(created_at);

