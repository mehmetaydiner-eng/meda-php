-- ============================================================
-- ARTIK GEREKLİ DEĞİL (18 Temmuz 2026): Proje SQLite'e geçirildi ve
-- schema.sql zaten bu değişikliği içeriyor. Bu dosya sadece MySQL
-- döneminden kalan, var olan bir MySQL veritabanını güncellemek için
-- yazılmıştı - yeni bir SQLite kurulumunda ÇALIŞTIRMANA gerek yok.
-- ============================================================

-- ============================================================
-- MEDA BİLGİSAYAR - Stok Hareketleri Tablosu (migrasyon)
-- ============================================================
-- Yeni bir kurulumsan sql/schema.sql zaten bu tabloyu içeriyor, bu
-- dosyayı çalıştırmana gerek yok. Var olan bir veritabanını
-- güncelliyorsan:
--   mysql -u root -p meda_db < sql/add_stok_hareketleri.sql
--
-- NOT: Bu tabloyu DAHA ÖNCE bu dosyanın eski bir sürümüyle oluşturduysan
-- (cari_id kolonu olmadan), bunun yerine sql/add_stok_hareketleri_cari.sql
-- dosyasını çalıştır - o sadece eksik kolonu ekler.
-- ============================================================

CREATE TABLE IF NOT EXISTS stok_hareketleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    urun_id INT NOT NULL,
    hareket_turu VARCHAR(20) NOT NULL,
    miktar DOUBLE NOT NULL,
    stok_oncesi DOUBLE NOT NULL,
    stok_sonrasi DOUBLE NOT NULL,
    referans_no VARCHAR(50),
    aciklama VARCHAR(255),
    cari_id INT,
    created_by VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stokhareket_urun FOREIGN KEY (urun_id) REFERENCES urunler(id),
    CONSTRAINT fk_stokhareket_cari FOREIGN KEY (cari_id) REFERENCES cariler(id),
    INDEX idx_stok_hareketleri_urun (urun_id),
    INDEX idx_stok_hareketleri_tarih (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
