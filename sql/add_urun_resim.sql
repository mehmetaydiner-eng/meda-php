-- ============================================================
-- ARTIK GEREKLİ DEĞİL (18 Temmuz 2026): Proje SQLite'e geçirildi ve
-- schema.sql zaten bu değişikliği içeriyor. Bu dosya sadece MySQL
-- döneminden kalan, var olan bir MySQL veritabanını güncellemek için
-- yazılmıştı - yeni bir SQLite kurulumunda ÇALIŞTIRMANA gerek yok.
-- ============================================================

-- ============================================================
-- MEDA BİLGİSAYAR - Ürün Resmi (urunler.resim) ekler
-- ============================================================
-- Yeni bir kurulumsan sql/schema.sql zaten bu kolonu içeriyor, bu dosyayı
-- çalıştırmana gerek yok. Var olan bir veritabanını güncelliyorsan:
--   mysql -u root -p meda_db < sql/add_urun_resim.sql
-- ============================================================

ALTER TABLE urunler ADD COLUMN resim VARCHAR(255) AFTER birim;
