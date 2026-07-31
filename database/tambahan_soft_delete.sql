-- ============================================================================
-- TAMBAHAN: Soft Delete — data yang dihapus TIDAK langsung hilang dari
-- database, cuma ditandai (kolom deleted_at diisi tanggal-waktu hapus).
-- Bisa dipulihkan lewat menu Sampah (Admin). Berlaku untuk 8 tabel Data
-- Master yang punya tombol Hapus.
--
-- CARA PAKAI: jalankan file INI SAJA lewat phpMyAdmin (tab Import) kalau
-- database Anda sudah terisi data. Instalasi baru dari nol otomatis sudah
-- termasuk lewat schema.sql, tidak perlu jalankan file ini lagi.
-- ============================================================================

ALTER TABLE `guru` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL AFTER `updated_at`;
ALTER TABLE `siswa` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL AFTER `updated_at`;
ALTER TABLE `kelas` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL;
ALTER TABLE `mata_pelajaran` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL;
ALTER TABLE `jam_pelajaran` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL;
ALTER TABLE `hari_libur` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL;
ALTER TABLE `jadwal` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL;

-- Index supaya query "WHERE deleted_at IS NULL" (dipakai otomatis oleh CI4
-- di SETIAP query normal) tetap cepat walau tabelnya sudah besar.
ALTER TABLE `guru` ADD INDEX `idx_guru_deleted_at` (`deleted_at`);
ALTER TABLE `siswa` ADD INDEX `idx_siswa_deleted_at` (`deleted_at`);
ALTER TABLE `kelas` ADD INDEX `idx_kelas_deleted_at` (`deleted_at`);
ALTER TABLE `mata_pelajaran` ADD INDEX `idx_mapel_deleted_at` (`deleted_at`);
ALTER TABLE `jam_pelajaran` ADD INDEX `idx_jampel_deleted_at` (`deleted_at`);
ALTER TABLE `hari_libur` ADD INDEX `idx_harilibur_deleted_at` (`deleted_at`);
ALTER TABLE `jadwal` ADD INDEX `idx_jadwal_deleted_at` (`deleted_at`);
ALTER TABLE `users` ADD INDEX `idx_users_deleted_at` (`deleted_at`);
