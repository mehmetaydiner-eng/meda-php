-- ============================================================
-- ARTIK GEREKLİ DEĞİL (18 Temmuz 2026): Proje SQLite'e geçirildi ve
-- schema.sql zaten bu değişikliği içeriyor. Bu dosya sadece MySQL
-- döneminden kalan, var olan bir MySQL veritabanını güncellemek için
-- yazılmıştı - yeni bir SQLite kurulumunda ÇALIŞTIRMANA gerek yok.
-- ============================================================

-- ============================================================
-- MEDA BİLGİSAYAR - Performans İndeksleri
-- ============================================================
-- Bu dosya, arama/sıralama yapılan ama şu ana kadar indekslenmemiş
-- kolonlara indeks ekler. UNIQUE alanlar (fatura_no, urun_kodu, barkod
-- vb.) ve foreign key'ler zaten otomatik indeksli - burada onları
-- TEKRAR eklemiyoruz.
--
-- KULLANIM:
--   - Yeni bir kurulumsan: sql/schema.sql zaten bu indeksleri içeriyor,
--     bu dosyayı ayrıca çalıştırmana gerek yok.
--   - Var olan bir veritabanını güncelliyorsan (schema.sql'i daha önce
--     içe aktardıysan): bu dosyayı bir kere çalıştırman yeterli:
--       mysql -u root -p meda_db < sql/add_indexes.sql
--
-- Not: Bir indeks zaten varsa hata vermemesi için önce DROP INDEX IF
-- EXISTS deneniyor (MySQL 8 / MariaDB 10.6+ destekler). Daha eski bir
-- sürüm kullanıyorsan ve hata alırsan, ilgili DROP satırını atlayıp
-- sadece CREATE INDEX satırlarını çalıştırabilirsin.
-- ============================================================

-- ------------------------------------------------------------
-- CARİLER - unvan/vergi_no/telefon arama, cari_turu filtreleme
-- ------------------------------------------------------------
ALTER TABLE cariler ADD INDEX idx_cariler_unvan (unvan);
ALTER TABLE cariler ADD INDEX idx_cariler_vergi_no (vergi_no);
ALTER TABLE cariler ADD INDEX idx_cariler_telefon (telefon);
ALTER TABLE cariler ADD INDEX idx_cariler_cari_turu (cari_turu);

-- ------------------------------------------------------------
-- ÜRÜNLER - urun_adi arama (urun_kodu ve barkod zaten UNIQUE=indeksli)
-- ------------------------------------------------------------
ALTER TABLE urunler ADD INDEX idx_urunler_urun_adi (urun_adi);

-- ------------------------------------------------------------
-- FATURALAR - tarihe göre sıralama, duruma göre filtreleme
-- ------------------------------------------------------------
ALTER TABLE faturalar ADD INDEX idx_faturalar_tarihi (fatura_tarihi);
ALTER TABLE faturalar ADD INDEX idx_faturalar_durum (durum);

-- ------------------------------------------------------------
-- MAKBUZLAR - tarihe göre sıralama
-- ------------------------------------------------------------
ALTER TABLE makbuzlar ADD INDEX idx_makbuzlar_tarihi (makbuz_tarihi);

-- ------------------------------------------------------------
-- TEKLİFLER - tarihe göre sıralama, duruma göre filtreleme
-- ------------------------------------------------------------
ALTER TABLE teklifler ADD INDEX idx_teklifler_tarihi (teklif_tarihi);
ALTER TABLE teklifler ADD INDEX idx_teklifler_durum (durum);

-- ------------------------------------------------------------
-- TEKNİK SERVİS - kayıt tarihine göre sıralama, durum filtreleme
-- ------------------------------------------------------------
ALTER TABLE teknik_servis ADD INDEX idx_teknik_servis_created (created_at);
ALTER TABLE teknik_servis ADD INDEX idx_teknik_servis_durum (durum);

-- ------------------------------------------------------------
-- HESAP HAREKETLERİ - tarihe göre sıralama/filtreleme (kasa_rapor.php)
-- ------------------------------------------------------------
ALTER TABLE hesap_hareketleri ADD INDEX idx_hesap_hareketleri_tarihi (hareket_tarihi);

-- ------------------------------------------------------------
-- TODOS - "kullanıcının bekleyen işleri" sorgusu (api/todo_count.php,
-- yapilacaklar.php) her ikisini birlikte kullanıyor - bileşik indeks
-- ------------------------------------------------------------
ALTER TABLE todos ADD INDEX idx_todos_user_status (user_id, status);
