-- ============================================================================
-- TAMBAHAN: Fitur Pertukaran Jadwal (jadwal_swap) — BUKAN guru pengganti.
-- Ini pertukaran SLOT penuh (hari+jam) antar 2 guru untuk rentang tanggal
-- tertentu. Jadwal master (tabel `jadwal`) TIDAK PERNAH diubah oleh fitur ini.
--
-- CARA PAKAI: jalankan file INI SAJA lewat phpMyAdmin (tab Import) kalau
-- database Anda sudah terisi data. Instalasi baru dari nol otomatis sudah
-- termasuk lewat schema.sql, tidak perlu jalankan file ini lagi.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `jadwal_swap` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jadwal_asal_id` INT UNSIGNED NOT NULL COMMENT 'jadwal milik guru pengaju',
  `jadwal_tujuan_id` INT UNSIGNED NOT NULL COMMENT 'jadwal milik guru yang dituju',
  `guru_pengaju_id` INT UNSIGNED NOT NULL,
  `guru_penyetuju_id` INT UNSIGNED NOT NULL COMMENT 'pemilik jadwal_tujuan, guru yang harus menyetujui',
  `tanggal_mulai` DATE NOT NULL,
  `tanggal_selesai` DATE NOT NULL,
  `alasan` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending','disetujui','ditolak','dibatalkan') NOT NULL DEFAULT 'pending',
  `guru_setuju` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'persetujuan guru_penyetuju_id (tahap 1); status baru jadi disetujui setelah tahap 2 (admin) juga acc',
  `catatan_guru` VARCHAR(255) DEFAULT NULL COMMENT 'catatan guru_penyetuju saat merespon',
  `approved_by` INT UNSIGNED DEFAULT NULL COMMENT 'user_id admin/Waka Kurikulum yang memberi persetujuan akhir',
  `approved_at` DATETIME DEFAULT NULL,
  `catatan_admin` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_jadwal_swap_rentang` (`tanggal_mulai`, `tanggal_selesai`, `status`),
  KEY `fk_swap_jadwal_asal` (`jadwal_asal_id`),
  KEY `fk_swap_jadwal_tujuan` (`jadwal_tujuan_id`),
  KEY `fk_swap_guru_pengaju` (`guru_pengaju_id`),
  KEY `fk_swap_guru_penyetuju` (`guru_penyetuju_id`),
  KEY `fk_swap_approved_by` (`approved_by`),
  CONSTRAINT `fk_swap_jadwal_asal` FOREIGN KEY (`jadwal_asal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_swap_jadwal_tujuan` FOREIGN KEY (`jadwal_tujuan_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_swap_guru_pengaju` FOREIGN KEY (`guru_pengaju_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_swap_guru_penyetuju` FOREIGN KEY (`guru_penyetuju_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_swap_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
