-- ============================================================================
-- TAMBAHAN: Jam Pelajaran per hari — sebelumnya satu set jam berlaku untuk
-- SEMUA hari (Senin s/d Sabtu), sekarang tiap hari bisa punya jam sendiri
-- (jam ke-1 Senin bisa beda dari jam ke-1 Selasa, dst).
--
-- CARA PAKAI: jalankan file INI SAJA lewat phpMyAdmin (tab Import) kalau
-- database Anda sudah terisi data. Instalasi baru dari nol otomatis sudah
-- termasuk lewat schema.sql, tidak perlu jalankan file ini lagi.
--
-- APA YANG TERJADI: jam pelajaran yang SUDAH ADA sekarang akan DIGANDAKAN ke
-- SEMUA 6 hari (Senin-Sabtu) sebagai titik awal yang sama seperti jam Anda
-- sebelumnya — supaya jadwal yang sudah ada tetap valid tanpa data hilang.
-- SETELAH migrasi ini, buka menu Jam Pelajaran dan EDIT jam untuk hari-hari
-- yang memang berbeda (mis. Selasa mulai lebih pagi) sesuai kondisi sekolah
-- Anda yang sebenarnya.
-- ============================================================================

ALTER TABLE `jam_pelajaran` ADD COLUMN `hari` ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NULL AFTER `id`;

-- WAJIB hapus constraint LAMA (jam_ke saja) DULU, sebelum menggandakan baris —
-- kalau tidak, MySQL menolak jam_ke yang sama muncul lagi walau untuk hari berbeda.
ALTER TABLE `jam_pelajaran` DROP INDEX `uq_jam_ke`;

-- Gandakan tiap baris yang ada ke 5 hari lainnya (baris asli akan dipakai utk Senin di bawah).
INSERT INTO `jam_pelajaran` (`hari`, `jam_ke`, `jam_mulai`, `jam_selesai`, `deleted_at`)
SELECT 'Selasa', jam_ke, jam_mulai, jam_selesai, deleted_at FROM `jam_pelajaran` WHERE `hari` IS NULL;
INSERT INTO `jam_pelajaran` (`hari`, `jam_ke`, `jam_mulai`, `jam_selesai`, `deleted_at`)
SELECT 'Rabu', jam_ke, jam_mulai, jam_selesai, deleted_at FROM `jam_pelajaran` WHERE `hari` IS NULL;
INSERT INTO `jam_pelajaran` (`hari`, `jam_ke`, `jam_mulai`, `jam_selesai`, `deleted_at`)
SELECT 'Kamis', jam_ke, jam_mulai, jam_selesai, deleted_at FROM `jam_pelajaran` WHERE `hari` IS NULL;
INSERT INTO `jam_pelajaran` (`hari`, `jam_ke`, `jam_mulai`, `jam_selesai`, `deleted_at`)
SELECT 'Jumat', jam_ke, jam_mulai, jam_selesai, deleted_at FROM `jam_pelajaran` WHERE `hari` IS NULL;
INSERT INTO `jam_pelajaran` (`hari`, `jam_ke`, `jam_mulai`, `jam_selesai`, `deleted_at`)
SELECT 'Sabtu', jam_ke, jam_mulai, jam_selesai, deleted_at FROM `jam_pelajaran` WHERE `hari` IS NULL;

-- Baris asli (yang tadinya hari IS NULL) jadi jam untuk hari Senin.
UPDATE `jam_pelajaran` SET `hari` = 'Senin' WHERE `hari` IS NULL;

ALTER TABLE `jam_pelajaran` MODIFY COLUMN `hari` ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL;
ALTER TABLE `jam_pelajaran` ADD UNIQUE KEY `uq_hari_jam_ke` (`hari`, `jam_ke`);
