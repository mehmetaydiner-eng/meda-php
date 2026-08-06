-- ============================================================
-- MEDA BİLGİSAYAR - Ürün Kategorileri tablosunu ekler
-- ============================================================
-- Yeni bir kurulumsan sql/schema.sql zaten bu tabloyu içeriyor, bu dosyayı
-- çalıştırmana gerek yok. Var olan bir SQLite veritabanını güncelliyorsan:
--   sqlite3 data/meda.sqlite < sql/add_kategoriler.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS kategoriler (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kategori_adi VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Ürünlerde daha önce serbest metin olarak girilmiş kategorileri, yeni
-- tabloya otomatik aktarır - böylece mevcut ürünlerin kategorileri
-- dropdown'da hazır çıkar, elle yeniden tanımlaman gerekmez.
INSERT OR IGNORE INTO kategoriler (kategori_adi)
SELECT DISTINCT kategori FROM urunler WHERE kategori IS NOT NULL AND TRIM(kategori) != '';
