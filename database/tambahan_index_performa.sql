-- ============================================================================
-- TAMBAHAN: Index performa untuk query rentang tanggal di Laporan (Rekap
-- Presensi & Rekap Jurnal) yang tidak selalu tahu jadwal_id spesifik — index
-- gabungan (jadwal_id, tanggal) yang sudah ada sebelumnya kurang optimal
-- untuk pola query ini karena jadwal_id bukan bagian dari filternya.
--
-- CARA PAKAI: jalankan file INI SAJA lewat phpMyAdmin (tab Import) kalau
-- database Anda sudah terisi data. Instalasi baru dari nol otomatis sudah
-- termasuk lewat schema.sql, tidak perlu jalankan file ini lagi.
-- Aman dijalankan kapan saja — cuma menambah index, tidak mengubah data.
-- ============================================================================

ALTER TABLE `presensi` ADD INDEX `idx_presensi_tanggal` (`tanggal`);
ALTER TABLE `jurnal_mengajar` ADD INDEX `idx_jurnal_tanggal` (`tanggal`);
