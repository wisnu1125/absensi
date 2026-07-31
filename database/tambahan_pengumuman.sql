-- ============================================================================
-- TAMBAHAN: Pengumuman — satu-satunya widget di Dashboard Admin "lengkap"
-- yang butuh tabel baru (yang lain murni agregasi dari data yang sudah ada).
-- Jalankan SEKALI di phpMyAdmin. Aman dijalankan berkali-kali (pakai
-- IF NOT EXISTS).
-- ============================================================================

CREATE TABLE IF NOT EXISTS `pengumuman` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `judul` VARCHAR(200) NOT NULL,
  `isi` TEXT DEFAULT NULL,
  `tanggal_mulai` DATE NOT NULL COMMENT 'mulai tampil di Dashboard sejak tanggal ini',
  `tanggal_selesai` DATE DEFAULT NULL COMMENT 'kosong = tampil terus sampai dihapus manual',
  `dibuat_oleh` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pengumuman_tanggal` (`tanggal_mulai`, `tanggal_selesai`),
  KEY `fk_pengumuman_user` (`dibuat_oleh`),
  CONSTRAINT `fk_pengumuman_user` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
