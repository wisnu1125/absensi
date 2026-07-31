-- ============================================================================
-- TAMBAHAN: Migrasi Hari Libur → Kalender Akademik, lalu hapus tabel lama.
--
-- Fitur "Hari Libur" terpisah sudah DIHAPUS dari aplikasi (menu, halaman,
-- model) karena fungsinya 100% tumpang tindih dengan kategori "Libur" di
-- Kalender Akademik (yang sudah punya field dampak_presensi='nonaktif' —
-- sama persis efeknya). SATU sumber kebenaran lebih aman daripada dua
-- tempat yang bisa saling tidak sinkron.
--
-- Skrip ini WAJIB dijalankan SEKALI di phpMyAdmin SEBELUM Anda pakai versi
-- baru aplikasi ini, supaya tanggal-tanggal libur yang SUDAH Anda input
-- sebelumnya di menu "Hari Libur" lama TIDAK HILANG — dipindah dulu ke
-- Kalender Akademik, baru tabel lamanya dihapus.
--
-- AMAN dijalankan berkali-kali (baris ke-2 SKIP tanggal yang sudah ada
-- event kategori Libur di Kalender Akademik, tidak akan dobel).
-- Kalau tabel `hari_libur` sudah tidak ada (instalasi baru / sudah pernah
-- menjalankan skrip ini), skrip ini otomatis tidak melakukan apa-apa yang
-- merusak — baris migrasi cukup diabaikan manual kalau tabelnya sudah tidak
-- ada, dan DROP TABLE di baris terakhir aman dijalankan berkali-kali.
-- ============================================================================

-- 1) Pindahkan tanggal libur yang MASIH AKTIF (belum di-soft-delete) dan
--    BELUM punya event kategori Libur di tanggal yang sama.
INSERT INTO `agenda_akademik`
  (`judul`, `deskripsi`, `kategori`, `tanggal_mulai`, `tanggal_selesai`, `all_day`, `status`, `dampak_presensi`, `created_at`, `updated_at`)
SELECT
  hl.`keterangan`, NULL, 'libur', hl.`tanggal`, hl.`tanggal`, 1, 'terjadwal', 'nonaktif', NOW(), NOW()
FROM `hari_libur` hl
WHERE hl.`deleted_at` IS NULL
  AND NOT EXISTS (
    SELECT 1 FROM `agenda_akademik` aa
    WHERE aa.`kategori` = 'libur'
      AND hl.`tanggal` BETWEEN aa.`tanggal_mulai` AND aa.`tanggal_selesai`
  );

-- 2) Setelah dipastikan datanya sudah pindah (cek dulu di halaman Kalender
--    Akademik kalau mau), baru hapus tabel lama. Kalau Anda ingin
--    VERIFIKASI dulu sebelum drop, JALANKAN cuma bagian (1) di atas dulu,
--    baru jalankan baris di bawah ini terpisah setelah yakin.
DROP TABLE IF EXISTS `hari_libur`;
