-- ============================================================================
-- TAMBAHAN: Modul Kalender Akademik — pusat informasi agenda sekolah (KBM,
-- Ujian, Libur, Rapat, Kegiatan, PPDB, Pesantren, Nasional), termasuk
-- dukungan event berulang mingguan (mis. Upacara tiap Senin).
--
-- CARA PAKAI: jalankan file INI SAJA lewat phpMyAdmin (tab Import) kalau
-- database Anda sudah terisi data. Instalasi baru dari nol otomatis sudah
-- termasuk lewat schema.sql, tidak perlu jalankan file ini lagi.
-- ============================================================================

CREATE TABLE `agenda_akademik` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `judul` VARCHAR(200) NOT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `kategori` ENUM('kbm','ujian','libur','rapat','kegiatan','ppdb','pesantren','nasional') NOT NULL,
  `tanggal_mulai` DATE NOT NULL,
  `tanggal_selesai` DATE NOT NULL,
  `jam_mulai` TIME DEFAULT NULL,
  `jam_selesai` TIME DEFAULT NULL,
  `all_day` TINYINT(1) NOT NULL DEFAULT 1,
  `status` ENUM('terjadwal','selesai','dibatalkan') NOT NULL DEFAULT 'terjadwal',
  `dampak_presensi` ENUM('normal','nonaktif') NOT NULL DEFAULT 'normal',
  `recurring_hari` VARCHAR(50) DEFAULT NULL,
  `dibuat_oleh` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_agenda_tanggal` (`tanggal_mulai`, `tanggal_selesai`),
  KEY `idx_agenda_kategori` (`kategori`),
  KEY `fk_agenda_user` (`dibuat_oleh`),
  CONSTRAINT `fk_agenda_user` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
