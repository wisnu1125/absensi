-- ============================================================================
-- TAMBAHAN: Tanggal berlaku semester (tanggal_mulai, tanggal_selesai) — dasar
-- hitung jadwal efektif, rekap "hari terlewat", dan statistik jumlah mengajar
-- yang akurat (bukan lagi tebakan 30 hari terakhir).
--
-- CARA PAKAI: jalankan file INI SAJA lewat phpMyAdmin (tab Import) kalau
-- database Anda sudah terisi data. Instalasi baru dari nol otomatis sudah
-- termasuk lewat schema.sql, tidak perlu jalankan file ini lagi.
--
-- SETELAH menjalankan file ini, buka menu Tahun Ajaran & Semester dan ISI
-- tanggal mulai/selesai untuk semester yang aktif — sebelum diisi, fitur
-- yang bergantung padanya (Kalender status, Hari Terlewat) akan menganggap
-- semester belum punya rentang tanggal dan menampilkan pesan pengingat.
-- ============================================================================

ALTER TABLE `semester`
  ADD COLUMN `tanggal_mulai` DATE DEFAULT NULL COMMENT 'awal semester berlaku' AFTER `nama`,
  ADD COLUMN `tanggal_selesai` DATE DEFAULT NULL AFTER `tanggal_mulai`;
