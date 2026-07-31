-- ============================================================================
-- TAMBAHAN: Konsep "Guru Pengampu" (Guru x Mata Pelajaran x Tingkat) sebagai
-- fondasi baru — jadwal ke depannya memilih Guru Pengampu (bukan guru+mapel
-- bebas terpisah), plus fondasi Master Tujuan Pembelajaran (TP).
--
-- PENTING — PENDEKATAN ADITIF, BUKAN MENGGANTI: kolom jadwal.guru_id dan
-- jadwal.mapel_id TETAP ADA dan tetap terisi seperti biasa. Migrasi ini CUMA
-- MENAMBAH kolom & tabel baru, tidak menghapus apa pun, supaya seluruh fitur
-- yang sudah ada (Presensi, Jurnal, Tukar Jadwal, Laporan, Kalender, dst)
-- tetap berjalan tanpa perubahan sama sekali.
--
-- CARA PAKAI: jalankan file INI SAJA lewat phpMyAdmin (tab Import) kalau
-- database Anda sudah terisi data. Instalasi baru dari nol otomatis sudah
-- termasuk lewat schema.sql, tidak perlu jalankan file ini lagi.
--
-- APA YANG TERJADI: kombinasi guru+mapel+tingkat yang SUDAH ADA di jadwal
-- Anda saat ini akan OTOMATIS dijadikan data Guru Pengampu (backfill), dan
-- jadwal yang sudah ada langsung ditautkan ke Guru Pengampu yang sesuai —
-- jadwal lama Anda TIDAK PERLU diedit ulang satu-satu.
-- ============================================================================

CREATE TABLE `guru_pengampu` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `guru_id` INT UNSIGNED NOT NULL,
  `mapel_id` INT UNSIGNED NOT NULL,
  `tingkat` VARCHAR(20) NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pengampu` (`guru_id`, `mapel_id`, `tingkat`),
  KEY `fk_pengampu_mapel` (`mapel_id`),
  CONSTRAINT `fk_pengampu_guru` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pengampu_mapel` FOREIGN KEY (`mapel_id`) REFERENCES `mata_pelajaran` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tujuan_pembelajaran` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `guru_pengampu_id` INT UNSIGNED NOT NULL,
  `teks` VARCHAR(500) NOT NULL,
  `urutan` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_tp_pengampu` (`guru_pengampu_id`),
  CONSTRAINT `fk_tp_pengampu` FOREIGN KEY (`guru_pengampu_id`) REFERENCES `guru_pengampu` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `jadwal` ADD COLUMN `guru_pengampu_id` INT UNSIGNED DEFAULT NULL AFTER `mapel_id`;
ALTER TABLE `jadwal` ADD KEY `fk_jadwal_pengampu` (`guru_pengampu_id`);
ALTER TABLE `jadwal` ADD CONSTRAINT `fk_jadwal_pengampu` FOREIGN KEY (`guru_pengampu_id`) REFERENCES `guru_pengampu` (`id`) ON DELETE SET NULL;

-- Backfill: buat data Guru Pengampu dari kombinasi guru+mapel+tingkat yang
-- SUDAH ADA di jadwal (lewat kelas.tingkat), lalu tautkan jadwal ke situ.
INSERT INTO `guru_pengampu` (`guru_id`, `mapel_id`, `tingkat`, `created_at`, `updated_at`)
SELECT DISTINCT jadwal.guru_id, jadwal.mapel_id, kelas.tingkat, NOW(), NOW()
FROM `jadwal`
JOIN `kelas` ON `kelas`.`id` = `jadwal`.`kelas_id`
WHERE `jadwal`.`deleted_at` IS NULL;

UPDATE `jadwal`
JOIN `kelas` ON `kelas`.`id` = `jadwal`.`kelas_id`
JOIN `guru_pengampu` ON `guru_pengampu`.`guru_id` = `jadwal`.`guru_id`
  AND `guru_pengampu`.`mapel_id` = `jadwal`.`mapel_id`
  AND `guru_pengampu`.`tingkat` = `kelas`.`tingkat`
SET `jadwal`.`guru_pengampu_id` = `guru_pengampu`.`id`
WHERE `jadwal`.`deleted_at` IS NULL;
