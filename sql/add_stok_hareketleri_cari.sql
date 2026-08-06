-- ============================================================
-- ARTIK GEREKLİ DEĞİL (18 Temmuz 2026): Proje SQLite'e geçirildi ve
-- schema.sql zaten bu değişikliği içeriyor. Bu dosya sadece MySQL
-- döneminden kalan, var olan bir MySQL veritabanını güncellemek için
-- yazılmıştı - yeni bir SQLite kurulumunda ÇALIŞTIRMANA gerek yok.
-- ============================================================

-- ============================================================
-- MEDA BİLGİSAYAR - stok_hareketleri tablosuna cari_id ekler
-- ============================================================
-- Eğer stok_hareketleri tablosunu DAHA ÖNCE (sql/add_stok_hareketleri.sql'in
-- eski bir sürümüyle) oluşturduysan, bu dosya sadece eksik olan cari_id
-- kolonunu ekler - "hangi cariye sattım / kimden aldım" bilgisini stok
-- hareketleri listesinde görebilmen için.
--
-- KULLANIM:
--   mysql -u root -p meda_db < sql/add_stok_hareketleri_cari.sql
--
-- Eğer tabloyu ilk kez oluşturuyorsan (henüz hiç yoksa), bunun yerine
-- sql/add_stok_hareketleri.sql'i çalıştır - o zaten cari_id'yi içeriyor,
-- bu dosyaya ihtiyacın olmaz (ALTER TABLE zaten var olan bir tabloyu
-- değiştirmek içindir).
-- ============================================================

ALTER TABLE stok_hareketleri ADD COLUMN cari_id INT AFTER aciklama;
ALTER TABLE stok_hareketleri ADD CONSTRAINT fk_stokhareket_cari FOREIGN KEY (cari_id) REFERENCES cariler(id);
