# Fase 5 — Laporan (DomPDF + PhpSpreadsheet)

## File baru / berubah

```
app/Config/Routes.php              → TIMPA (menambahkan grup route "laporan")
app/Views/layouts/_sidebar.php     → TIMPA ("Laporan presensi" & "Rekap jurnal" sekarang aktif)

app/Controllers/Laporan.php        → BARU
app/Views/laporan/presensi.php     → BARU (halaman filter + tabel, tampil di dalam aplikasi)
app/Views/laporan/presensi_pdf.php → BARU (HTML khusus untuk dirender DomPDF)
app/Views/laporan/jurnal.php       → BARU
app/Views/laporan/jurnal_pdf.php   → BARU
```

## Pastikan dompdf ada di composer.json Anda
```
composer require dompdf/dompdf
```

## Cara kerja
Satu halaman filter (tanggal dari–sampai, guru, kelas, mata pelajaran — semua opsional dan bisa
dikombinasikan) menghasilkan semua variasi laporan yang diminta SRS:
- **Harian**: isi tanggal dari = tanggal sampai
- **Bulanan/Semester**: perlebar rentang tanggal
- **Per Guru / Per Kelas / Per Mapel**: pilih salah satu filter, kosongkan sisanya
- **Per Siswa**: filter kelas lalu cari nama siswa di tabel hasil

Ringkasan jumlah Hadir/Sakit/Izin/Alpha/Terlambat tampil sebagai kartu di atas tabel.
Tombol **Export PDF** dan **Export Excel** memakai filter yang sama persis dengan yang sedang
ditampilkan di layar (dikirim lewat query string).

Rekap Jurnal punya halaman terpisah dengan pola yang sama (tanpa breakdown status karena jurnal
tidak berkaitan dengan status kehadiran).

## Yang sudah diverifikasi
- Query gabungan (presensi → jadwal → kelas/mapel/guru, dan jurnal → jadwal → ...) sudah diuji
  dengan data sungguhan di database — hasil dan filter-nya benar.
- Sintaks PHP semua file baru lolos `php -l`.
- Pemanggilan API PhpSpreadsheet (`setCellValue`, `toArray`) dan DomPDF (`Dompdf`, `Options`,
  `loadHtml`, `setPaper`, `render`, `output`) dicek ulang ke dokumentasi resminya masing-masing
  supaya sesuai versi yang berlaku saat ini.

## Belum termasuk (menyusul)
- Riwayat Mengajar (guru), Rekap Wali Kelas, Monitoring Kepala Sekolah
- Jam Pelajaran & Hari Libur (CRUD-nya — datanya sudah ada default)
- Kelola Pengguna & Role lewat UI (saat ini lewat SQL/phpMyAdmin), Audit Log viewer
