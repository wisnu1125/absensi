-- ============================================================================
-- TAMBAHAN: Fitur Tukar Jadwal (guru pengganti untuk 1 sesi tertentu)
--
-- CARA PAKAI: kalau database Anda SUDAH ada isinya (bukan instalasi baru),
-- cukup jalankan file INI SAJA lewat phpMyAdmin (tab Import) — tidak perlu
-- mengimpor ulang schema.sql yang lama. Kalau Anda instalasi baru dari nol,
-- ini sudah otomatis ikut di schema.sql, tidak perlu jalankan file ini lagi.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `tukar_jadwal` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jadwal_id` INT UNSIGNED NOT NULL,
  `tanggal` DATE NOT NULL COMMENT 'tanggal spesifik sesi yang ditukar, bukan tanggal pengajuan',
  `guru_asal_id` INT UNSIGNED NOT NULL COMMENT 'guru pemilik jadwal, yang mengajukan',
  `guru_pengganti_id` INT UNSIGNED NOT NULL COMMENT 'guru yang diminta menggantikan',
  `alasan` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('menunggu','disetujui','ditolak','dibatalkan') NOT NULL DEFAULT 'menunggu',
  `catatan_respon` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tukar_jadwal_tanggal` (`jadwal_id`, `tanggal`),
  KEY `fk_tukar_guru_asal` (`guru_asal_id`),
  KEY `fk_tukar_guru_pengganti` (`guru_pengganti_id`),
  CONSTRAINT `fk_tukar_jadwal` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tukar_guru_asal` FOREIGN KEY (`guru_asal_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tukar_guru_pengganti` FOREIGN KEY (`guru_pengganti_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
