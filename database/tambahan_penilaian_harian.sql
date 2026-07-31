-- ============================================================================
-- TAMBAHAN: Penilaian Harian — bagian dari Jurnal Mengajar, bukan menu sendiri.
-- Mencatat penilaian siswa SELAMA proses belajar (keaktifan, bertanya,
-- presentasi, dst) — TIDAK memaksa semua siswa dinilai, hanya siswa yang
-- memang dinilai hari itu yang punya baris data.
--
-- CARA PAKAI: jalankan file INI SAJA lewat phpMyAdmin (tab Import) kalau
-- database Anda sudah terisi data. Instalasi baru dari nol otomatis sudah
-- termasuk lewat schema.sql, tidak perlu jalankan file ini lagi.
-- ============================================================================

CREATE TABLE `penilaian_harian` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jurnal_id` INT UNSIGNED NOT NULL,
  `siswa_id` INT UNSIGNED NOT NULL,
  `jenis_penilaian` VARCHAR(100) NOT NULL,
  `nilai` VARCHAR(10) NOT NULL,
  `catatan` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jurnal_siswa` (`jurnal_id`, `siswa_id`),
  KEY `fk_penilaian_siswa` (`siswa_id`),
  CONSTRAINT `fk_penilaian_jurnal` FOREIGN KEY (`jurnal_id`) REFERENCES `jurnal_mengajar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_penilaian_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
