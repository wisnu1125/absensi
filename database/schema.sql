-- ============================================================================
-- Sistem Presensi Siswa & Jurnal Mengajar
-- Database Schema — CodeIgniter 4 (MySQL / MariaDB)
--
-- CARA PAKAI:
-- 1. Buat database baru di phpMyAdmin, contoh nama: db_presensi
-- 2. Buka database tersebut, klik tab "Import"
-- 3. Pilih file ini, klik "Go"
-- 4. Sesuaikan app/Config/Database.php atau file .env dengan nama database ini
--
-- Login default setelah import:
--   username: admin
--   password: admin123   (WAJIB diganti setelah login pertama kali)
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- ----------------------------------------------------------------------------
-- 1. USERS & RBAC (Multi Role, Many-to-Many)
-- ----------------------------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE `user_roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_role` (`user_id`,`role_id`),
  KEY `fk_user_roles_role` (`role_id`),
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. MASTER DATA
-- ----------------------------------------------------------------------------

DROP TABLE IF EXISTS `tahun_ajaran`;
CREATE TABLE `tahun_ajaran` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(20) NOT NULL COMMENT 'contoh: 2025/2026',
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `semester`;
CREATE TABLE `semester` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tahun_ajaran_id` INT UNSIGNED NOT NULL,
  `nama` ENUM('Ganjil','Genap') NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_semester_ta` (`tahun_ajaran_id`),
  CONSTRAINT `fk_semester_ta` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `mata_pelajaran`;
CREATE TABLE `mata_pelajaran` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode` VARCHAR(20) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mapel_kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `kelas`;
CREATE TABLE `kelas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tahun_ajaran_id` INT UNSIGNED NOT NULL,
  `nama_kelas` VARCHAR(50) NOT NULL COMMENT 'contoh: VII A',
  `tingkat` VARCHAR(10) NOT NULL COMMENT 'contoh: VII',
  PRIMARY KEY (`id`),
  KEY `fk_kelas_ta` (`tahun_ajaran_id`),
  CONSTRAINT `fk_kelas_ta` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `guru`;
CREATE TABLE `guru` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL COMMENT 'akun login guru, boleh kosong dulu',
  `nip` VARCHAR(30) DEFAULT NULL,
  `nama` VARCHAR(150) NOT NULL,
  `jenis_kelamin` ENUM('L','P') NOT NULL DEFAULT 'L',
  `no_hp` VARCHAR(20) DEFAULT NULL,
  `alamat` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_guru_user` (`user_id`),
  CONSTRAINT `fk_guru_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `siswa`;
CREATE TABLE `siswa` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nis` VARCHAR(30) NOT NULL,
  `nama` VARCHAR(150) NOT NULL,
  `jenis_kelamin` ENUM('L','P') NOT NULL DEFAULT 'L',
  `kelas_id` INT UNSIGNED DEFAULT NULL,
  `tanggal_lahir` DATE DEFAULT NULL,
  `alamat` VARCHAR(255) DEFAULT NULL,
  `nama_ortu` VARCHAR(150) DEFAULT NULL,
  `no_hp_ortu` VARCHAR(20) DEFAULT NULL,
  `status` ENUM('aktif','nonaktif','lulus','pindah') NOT NULL DEFAULT 'aktif',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_siswa_nis` (`nis`),
  KEY `fk_siswa_kelas` (`kelas_id`),
  CONSTRAINT `fk_siswa_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `wali_kelas`;
CREATE TABLE `wali_kelas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `guru_id` INT UNSIGNED NOT NULL,
  `kelas_id` INT UNSIGNED NOT NULL,
  `tahun_ajaran_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wali_kelas` (`kelas_id`,`tahun_ajaran_id`),
  KEY `fk_wk_guru` (`guru_id`),
  KEY `fk_wk_ta` (`tahun_ajaran_id`),
  CONSTRAINT `fk_wk_guru` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wk_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wk_ta` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `jam_pelajaran`;
CREATE TABLE `jam_pelajaran` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jam_ke` INT UNSIGNED NOT NULL,
  `jam_mulai` TIME NOT NULL,
  `jam_selesai` TIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jam_ke` (`jam_ke`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `hari_libur`;
CREATE TABLE `hari_libur` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tanggal` DATE NOT NULL,
  `keterangan` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hari_libur_tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. JADWAL (template mingguan — berulang tiap minggu selama semester berjalan)
-- ----------------------------------------------------------------------------

DROP TABLE IF EXISTS `jadwal`;
CREATE TABLE `jadwal` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `guru_id` INT UNSIGNED NOT NULL,
  `mapel_id` INT UNSIGNED NOT NULL,
  `kelas_id` INT UNSIGNED NOT NULL,
  `tahun_ajaran_id` INT UNSIGNED NOT NULL,
  `semester_id` INT UNSIGNED NOT NULL,
  `hari` ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
  `jam_ke_mulai` INT UNSIGNED NOT NULL,
  `jam_ke_selesai` INT UNSIGNED NOT NULL,
  `jam_mulai` TIME NOT NULL,
  `jam_selesai` TIME NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_jadwal_guru_hari` (`guru_id`,`hari`),
  KEY `idx_jadwal_kelas_hari` (`kelas_id`,`hari`),
  KEY `fk_jadwal_mapel` (`mapel_id`),
  KEY `fk_jadwal_ta` (`tahun_ajaran_id`),
  KEY `fk_jadwal_smt` (`semester_id`),
  CONSTRAINT `fk_jadwal_guru` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jadwal_mapel` FOREIGN KEY (`mapel_id`) REFERENCES `mata_pelajaran` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jadwal_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jadwal_ta` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jadwal_smt` FOREIGN KEY (`semester_id`) REFERENCES `semester` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3b. TUKAR JADWAL (guru pengganti untuk 1 sesi spesifik, bukan perubahan permanen)
-- ----------------------------------------------------------------------------

DROP TABLE IF EXISTS `tukar_jadwal`;
CREATE TABLE `tukar_jadwal` (
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

-- ----------------------------------------------------------------------------
-- 4. PRESENSI & JURNAL — jadwal_id sebagai relasi utama (sesuai SRS §15)
--    tanggal membedakan setiap kemunculan jadwal yang sama tiap minggunya
-- ----------------------------------------------------------------------------

DROP TABLE IF EXISTS `presensi`;
CREATE TABLE `presensi` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jadwal_id` INT UNSIGNED NOT NULL,
  `tanggal` DATE NOT NULL,
  `locked_at` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_presensi_jadwal_tanggal` (`jadwal_id`,`tanggal`),
  KEY `fk_presensi_user` (`created_by`),
  CONSTRAINT `fk_presensi_jadwal` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_presensi_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `presensi_detail`;
CREATE TABLE `presensi_detail` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `presensi_id` INT UNSIGNED NOT NULL,
  `siswa_id` INT UNSIGNED NOT NULL,
  `status` ENUM('hadir','sakit','izin','alpha','terlambat') NOT NULL DEFAULT 'hadir',
  `catatan` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_presensi_siswa` (`presensi_id`,`siswa_id`),
  KEY `fk_pd_siswa` (`siswa_id`),
  CONSTRAINT `fk_pd_presensi` FOREIGN KEY (`presensi_id`) REFERENCES `presensi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pd_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `jurnal_mengajar`;
CREATE TABLE `jurnal_mengajar` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jadwal_id` INT UNSIGNED NOT NULL,
  `tanggal` DATE NOT NULL,
  `materi` VARCHAR(255) NOT NULL,
  `tujuan_pembelajaran` TEXT,
  `metode` VARCHAR(150) DEFAULT NULL,
  `media` VARCHAR(150) DEFAULT NULL,
  `kegiatan_pembelajaran` TEXT,
  `catatan` TEXT,
  `kendala` TEXT,
  `tindak_lanjut` TEXT,
  `locked_at` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jurnal_jadwal_tanggal` (`jadwal_id`,`tanggal`),
  KEY `fk_jurnal_user` (`created_by`),
  CONSTRAINT `fk_jurnal_jadwal` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jurnal_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. AUDIT LOG
-- ----------------------------------------------------------------------------

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `aktivitas` VARCHAR(100) NOT NULL,
  `keterangan` VARCHAR(255) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_audit_user` (`user_id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SEED DATA
-- ============================================================================

INSERT INTO `roles` (`id`,`name`,`slug`) VALUES
(1,'Administrator','administrator'),
(2,'Operator','operator'),
(3,'Guru','guru'),
(4,'Wali Kelas','wali_kelas'),
(5,'Kepala Sekolah','kepala_sekolah');

-- Password: admin123  (hash bcrypt asli, sudah diverifikasi dengan password_verify)
INSERT INTO `users` (`id`,`username`,`email`,`password`,`full_name`,`is_active`,`created_at`,`updated_at`) VALUES
(1,'admin','admin@sekolah.sch.id','$2y$10$cUFn7b4rl29Fq0Y6c1Xic.ZKWaXC0On/6dC6GZKogLuT5EE31suQ6','Administrator Sekolah',1,NOW(),NOW());

INSERT INTO `user_roles` (`user_id`,`role_id`,`created_at`) VALUES (1,1,NOW());

INSERT INTO `tahun_ajaran` (`id`,`nama`,`is_active`,`created_at`) VALUES (1,'2025/2026',1,NOW());
INSERT INTO `semester` (`id`,`tahun_ajaran_id`,`nama`,`is_active`) VALUES (1,1,'Ganjil',1);

INSERT INTO `jam_pelajaran` (`jam_ke`,`jam_mulai`,`jam_selesai`) VALUES
(1,'07:15:00','07:55:00'),
(2,'07:55:00','08:35:00'),
(3,'08:35:00','09:15:00'),
(4,'09:15:00','09:55:00'),
(5,'10:15:00','10:55:00'),
(6,'10:55:00','11:35:00'),
(7,'11:35:00','12:15:00'),
(8,'12:45:00','13:25:00'),
(9,'13:25:00','14:05:00'),
(10,'14:05:00','14:45:00');

-- Contoh data mata pelajaran (silakan sesuaikan/tambah lewat menu Data Master)
INSERT INTO `mata_pelajaran` (`kode`,`nama`) VALUES
('MTK','Matematika'),
('BIN','Bahasa Indonesia'),
('BIG','Bahasa Inggris'),
('IPA','Ilmu Pengetahuan Alam'),
('IPS','Ilmu Pengetahuan Sosial'),
('PKN','Pendidikan Kewarganegaraan'),
('PAI','Pendidikan Agama Islam'),
('PJK','Pendidikan Jasmani, Olahraga dan Kesehatan');

-- Contoh kelas untuk tahun ajaran aktif (silakan sesuaikan lewat menu Data Master)
INSERT INTO `kelas` (`tahun_ajaran_id`,`nama_kelas`,`tingkat`) VALUES
(1,'VII A','VII'),
(1,'VII B','VII'),
(1,'VIII A','VIII'),
(1,'VIII B','VIII'),
(1,'IX A','IX'),
(1,'IX B','IX');
