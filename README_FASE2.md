# Fase 2 — Data Master (+ Import Excel Guru & Siswa)

## File baru / berubah di fase ini

```
app/Config/Routes.php                        → TIMPA (sudah termasuk semua route Fase 1 + Fase 2)
app/Views/layouts/main.php                   → TIMPA (menambahkan <script> app.js)
app/Views/layouts/_sidebar.php               → TIMPA (menu Data Master sekarang aktif/bisa diklik)
public/assets/css/app.css                    → TIMPA (menambahkan style modal, toolbar, form)
public/assets/js/app.js                      → BARU

app/Models/MataPelajaranModel.php            → BARU
app/Models/TahunAjaranModel.php              → BARU
app/Models/SemesterModel.php                 → BARU
app/Models/KelasModel.php                    → BARU
app/Models/GuruModel.php                     → BARU
app/Models/SiswaModel.php                    → BARU

app/Controllers/Master/MataPelajaran.php     → BARU
app/Controllers/Master/TahunAjaran.php       → BARU
app/Controllers/Master/Kelas.php             → BARU
app/Controllers/Master/Guru.php              → BARU
app/Controllers/Master/Siswa.php             → BARU

app/Views/master/mata_pelajaran/index.php    → BARU
app/Views/master/tahun_ajaran/index.php      → BARU
app/Views/master/kelas/index.php             → BARU
app/Views/master/guru/index.php              → BARU
app/Views/master/siswa/index.php             → BARU
```

Salin semua ke lokasi yang sama di project Anda (menimpa file Fase 1 yang namanya sama).

## Pastikan PhpSpreadsheet ada di composer.json Anda
```
composer require phpoffice/phpspreadsheet
```
(Anda sudah menyebut sudah install ini — cukup pastikan `vendor/phpoffice/phpspreadsheet` ada.)

## Cara kerja pola "index saja, minim klik"
Setiap modul (Mata Pelajaran, Kelas, Guru, Siswa, Tahun Ajaran/Semester) hanya py **satu halaman**:
tabel data + tombol "Tambah" yang membuka modal di halaman yang sama. Tombol "Edit" pada tiap baris
membuka modal yang sama, sudah terisi otomatis lewat JavaScript — tidak ada perpindahan ke halaman
form terpisah sama sekali. Submit form modal mengirim POST ke controller, lalu kembali ke halaman
index yang sama dengan pesan sukses/gagal.

## Import Excel (Guru & Siswa)
1. Buka menu Guru atau Siswa → klik "Import Excel"
2. Klik "Unduh template Excel" agar format kolomnya pas
3. Isi template, simpan, lalu unggah lewat form yang sama
4. Sistem akan melapor berapa baris berhasil & berapa yang dilewati (beserta alasannya untuk siswa)

Khusus siswa: kolom "Kelas" diisi nama kelas apa adanya (contoh: `VII A`), dicocokkan otomatis ke
`kelas_id` pada **tahun ajaran yang sedang aktif**. Kalau nama kelasnya tidak ketemu, siswa tetap
disimpan tapi tanpa kelas, dan akan dilaporkan di pesan hasil import.

## Catatan penting
- Aktifkan dulu 1 Tahun Ajaran & 1 Semester (menu "Tahun ajaran & semester") sebelum menambah Kelas,
  karena Kelas wajib terhubung ke tahun ajaran.
- Import siswa akan gagal dengan pesan jelas kalau belum ada tahun ajaran yang aktif.

## Belum termasuk di fase ini (menyusul)
- Jam Pelajaran & Hari Libur (sudah ada data default di `schema.sql`, CRUD-nya menyusul)
- Jadwal Mengajar + validasi bentrok jadwal
- Alur Presensi & Jurnal (fitur utama, "index saja" juga)
- Laporan dengan export PDF (DomPDF) & Excel (PhpSpreadsheet)
