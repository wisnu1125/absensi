# Sistem Presensi Siswa & Jurnal Mengajar — Panduan Lengkap (Fase 1–6)

Dokumen ini **menggantikan semua README_FASE*.md sebelumnya**. Kalau Anda masih punya file-file
README lama dari zip sebelumnya, boleh dihapus — semua isinya sudah dirangkum di sini, plus
perbaikan untuk file yang sempat kelewat.

## ⚠️ Kalau Anda upgrade dari zip fase sebelumnya

**Wajib jalankan `database/tambahan_tukar_jadwal.sql` lewat phpMyAdmin (tab Import)** kalau
database Anda sudah terisi data sebelumnya — ini menambahkan tabel `tukar_jadwal` yang
dibutuhkan fitur Tukar Jadwal. Kalau Anda instalasi baru dari nol pakai `schema.sql` yang ada
di paket ini, tabel itu sudah otomatis ikut, tidak perlu jalankan file tambahan ini.

Ada beberapa hal lain yang sudah pernah saya catat di sini sebelumnya:

Ada 2 perbaikan penting di paket ini yang **wajib** disalin ulang meski Anda sudah pernah copas fase 1-6:

1. **`app/Controllers/BaseController.php`** — file ini SEKARANG BARU BENAR-BENAR DISERTAKAN.
   Sebelumnya cuma disebut lewat instruksi teks ("tambahkan 'auth' ke $helpers"), makanya
   gampang kelewat. Ini penyebab error `Call to undefined function current_user()`.
2. **`app/Config/Filters.php`** — filter `csrf` sekarang diaktifkan secara global. Sebelumnya
   setiap form sudah mengirim token CSRF (`csrf_field()`) tapi token itu tidak pernah benar-benar
   diperiksa karena filternya belum dinyalakan.
3. **Jam Pelajaran** sekarang jadi Master Data tersendiri yang bisa di-CRUD (menu Data Master →
   Jam Pelajaran), bukan cuma data bawaan di `schema.sql`. Alasannya: tiap sekolah punya jam
   masuk & durasi periode yang berbeda-beda.
4. **Audit Log** sekarang punya halaman untuk melihatnya (menu Sistem → Audit Log), lengkap
   dengan filter tanggal/pengguna/jenis aktivitas/kata kunci dan pagination.
5. **Tampilan dirombak total**: tema deep blue ocean, font Roboto, ikon di seluruh menu &amp;
   tombol, dan mobile-first responsive (sidebar jadi menu geser di layar kecil, bukan cuma
   menyempit). Karena perubahan ini menyentuh hampir semua file, cara paling aman adalah
   **timpa seluruh folder `app/` dan `public/` Anda dengan isi zip ini**, bukan pilih-pilih file.
6. **Guru & akun login digabung jadi satu form.** Menu Guru sekarang langsung punya field
   username/password/email/role — tidak perlu lagi bolak-balik ke menu Pengguna untuk
   menautkan akun guru baru. Menu Pengguna & Role tetap ada, tapi sekarang khusus untuk akun
   non-guru (administrator/operator/kepala sekolah) dan pengaturan role secara umum.
7. **Import Excel Guru ikut disesuaikan** — template sekarang punya kolom tambahan opsional:
   Username, Password, Email, Role Tambahan (pisah koma, contoh: `wali_kelas` atau
   `wali_kelas,operator`). Kosongkan Username kalau baris itu belum perlu akun login; kalau
   diisi, akun &amp; role langsung dibuat sekaligus persis seperti form tambah satuan.
8. **Penunjukan wali kelas + halaman kerjanya kini ada.** Menu Kelas sekarang punya field
   "Wali kelas" (hanya menampilkan guru yang sudah punya role Wali Kelas). Guru yang ditunjuk
   otomatis punya menu baru "Rekap & data kelas" — data siswa, grafik kehadiran, dan rekap
   per-siswa (Hadir/Sakit/Izin/Alpha/Terlambat) untuk kelas yang jadi tanggung jawabnya.
9. **Semua modul yang tadinya "menyusul" sekarang lengkap** — tidak ada lagi menu nonaktif
   di sidebar:
   - **Dashboard Administrator/Operator**: statistik total guru/siswa/kelas/jadwal, jumlah
     presensi & jurnal hari ini, dan grafik kehadiran hari ini se-sekolah.
   - **Dashboard Kepala Sekolah**: monitoring real time — jadwal hari ini, berapa guru sedang
     mengajar/sudah selesai/belum presensi/belum jurnal, dan persentase kehadiran siswa.
   - **Hari Libur**: CRUD kalender akademik (menu Data Master), dipakai sebagai referensi
     tanggal libur sekolah.
   - **Riwayat Mengajar**: guru bisa melihat semua sesi presensi &amp; jurnal yang pernah
     diisi (bukan cuma hari ini), lengkap rekap kehadiran per sesi dan materi yang diajarkan.
10. **Dropdown di template Excel.** Kolom yang datanya mengacu ke Data Master (Jenis Kelamin,
    Kelas, Role Tambahan) sekarang berupa dropdown asli Excel — klik sel, pilih dari daftar,
    tidak perlu mengetik manual. Jenis Kelamin &amp; Kelas dipaksa (tidak bisa isi nilai lain);
    Role Tambahan cuma saran karena kolom itu boleh diisi lebih dari satu nilai dipisah koma.
11. **Import Excel untuk Jadwal Mengajar** (menu Jadwal → Import Excel). Kolom Guru, Mata
    Pelajaran, Kelas, Hari, dan Jam Ke semuanya dropdown. Setiap baris tetap divalidasi anti-
    bentrok guru &amp; kelas — persis seperti tambah manual — termasuk bentrok ANTAR baris di
    file yang sama, bukan cuma terhadap jadwal yang sudah ada.
12. **Kalender jadwal semester untuk guru** (menu Guru → Kalender jadwal). Karena jadwal
    berbasis hari dan berulang tiap minggu, satu grid Hari × Jam Ke ini sudah mewakili seluruh
    pola mengajar guru selama semester berjalan — termasuk menangani kelas yang jamnya
    membentang lebih dari satu periode sekaligus (rowspan otomatis).
13. **Fitur Tukar Jadwal** (menu Guru → Tukar Jadwal). Guru bisa mengajukan guru pengganti
    untuk SATU sesi tertentu (jadwal + tanggal spesifik) ke guru lain manapun, tunggu
    disetujui. Tabel `jadwal` (template mingguan) TIDAK PERNAH diubah — begitu tanggal yang
    diajukan lewat, minggu berikutnya otomatis kembali ke guru asli tanpa tindakan apa pun,
    karena pengajuan ini memang hanya berlaku untuk tanggal itu saja. Guru pengganti yang
    disetujui otomatis bisa mengisi presensi &amp; jurnal sesi itu di hari-H, dan sesi itu
    muncul di dashboard kedua belah pihak (guru asal: "Digantikan oleh...", pengganti:
    "Menggantikan..."). Tercatat lengkap di audit log, dan ada laporannya sendiri (menu
    Laporan → Tukar Jadwal) untuk Administrator, Operator, dan Kepala Sekolah, plus ringkasan
    jumlah pengajuan di dashboard masing-masing.

Kalau ragu, cara paling aman: **timpa seluruh folder `app/` dan `public/` Anda dengan isi zip ini.**

---

## 1. Import database

1. Buat database baru di phpMyAdmin (contoh nama: `db_presensi`)
2. Tab **Import** → pilih `database/schema.sql` → **Go**
3. Login default setelah import — **username:** `admin` **password:** `admin123`
   (wajib diganti setelah login pertama, lewat menu Pengguna & Role)

## 2. Salin semua file ke project CI4 Anda

Salin folder `app/`, `public/`, dan `database/` di zip ini ke root project CI4 Anda (hasil
composer install), **timpa file yang namanya sama**. Berikut daftar lengkap 55 file yang ada:

```
app/Config/Filters.php
app/Config/Routes.php
app/Controllers/Auth.php
app/Controllers/BaseController.php        <- WAJIB, lihat catatan di atas
app/Controllers/Dashboard.php
app/Controllers/Laporan.php
app/Controllers/Mengajar.php
app/Controllers/TukarJadwal.php
app/Controllers/WaliKelas.php
app/Controllers/Master/AuditLog.php
app/Controllers/Master/Guru.php
app/Controllers/Master/HariLibur.php
app/Controllers/Master/Jadwal.php
app/Controllers/Master/JamPelajaran.php
app/Controllers/Master/Kelas.php
app/Controllers/Master/MataPelajaran.php
app/Controllers/Master/Pengguna.php
app/Controllers/Master/Siswa.php
app/Controllers/Master/TahunAjaran.php
app/Filters/AuthFilter.php
app/Filters/RoleFilter.php
app/Helpers/auth_helper.php
app/Libraries/AuditLogger.php
app/Models/AuditLogModel.php
app/Models/GuruModel.php
app/Models/HariLiburModel.php
app/Models/JadwalModel.php
app/Models/JamPelajaranModel.php
app/Models/JurnalMengajarModel.php
app/Models/KelasModel.php
app/Models/MataPelajaranModel.php
app/Models/PresensiDetailModel.php
app/Models/PresensiModel.php
app/Models/RoleModel.php
app/Models/SemesterModel.php
app/Models/SiswaModel.php
app/Models/TahunAjaranModel.php
app/Models/UserModel.php
app/Models/UserRoleModel.php
app/Models/TukarJadwalModel.php
app/Models/WaliKelasModel.php
app/Views/auth/login.php
app/Views/dashboard/_content.php
app/Views/laporan/jurnal.php
app/Views/laporan/jurnal_pdf.php
app/Views/laporan/presensi.php
app/Views/laporan/presensi_pdf.php
app/Views/laporan/tukar_jadwal.php
app/Views/layouts/_icons.php
app/Views/layouts/_sidebar.php
app/Views/layouts/_topbar.php
app/Views/layouts/main.php
app/Views/master/audit_log/index.php
app/Views/master/guru/index.php
app/Views/master/hari_libur/index.php
app/Views/master/jadwal/index.php
app/Views/master/jadwal/kosong.php
app/Views/master/jam_pelajaran/index.php
app/Views/master/kelas/index.php
app/Views/master/mata_pelajaran/index.php
app/Views/master/pengguna/index.php
app/Views/master/siswa/index.php
app/Views/master/tahun_ajaran/index.php
app/Views/mengajar/jurnal.php
app/Views/mengajar/kalender.php
app/Views/mengajar/kalender_kosong.php
app/Views/mengajar/presensi.php
app/Views/mengajar/riwayat.php
app/Views/wali_kelas/index.php
app/Views/wali_kelas/kosong.php
app/Views/tukar_jadwal/index.php
database/schema.sql
database/tambahan_tukar_jadwal.sql
public/assets/css/app.css
public/assets/js/app.js
public/favicon.svg
```

Kalau Anda punya file `.env` dari permintaan sebelumnya, itu file terpisah (di root project, bukan
di dalam `app/`) — bukan bagian dari daftar di atas. Isinya panduan koneksi database, cek lagi kalau
belum ada.

## 3. Set koneksi database

Di file `.env` (root project, sejajar folder `app/`), isi bagian database sesuai yang Anda buat di
langkah 1:

```
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = db_presensi
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.charset = utf8mb4
database.default.DBCollat = utf8mb4_unicode_ci
```

## 4. Jalankan

```
php spark serve
```

Buka `http://localhost:8080`, login dengan akun admin di atas.

---

## Ringkasan fitur yang sudah jadi

| Area | Isi |
|---|---|
| Fondasi | Login, session, Multi-Role RBAC (`users`–`user_roles`–`roles`), layout clean minimalist, tema deep blue ocean, mobile-first |
| Data Master | Mata Pelajaran, Jam Pelajaran, Hari Libur, Tahun Ajaran & Semester, Kelas (+wali kelas), Guru (+akun+role terintegrasi), Siswa — pola "index saja", import Excel Guru &amp; Siswa |
| Jadwal | CRUD + validasi anti-bentrok (guru & kelas); Import Excel (dropdown Guru/Mapel/Kelas/Hari/Jam); Kalender semester (grid Hari×Jam untuk guru) |
| Alur mengajar | Dashboard guru (jadwal hari ini + status) → Mulai Mengajar → Presensi → Jurnal → terkunci; + Riwayat Mengajar |
| Tukar Jadwal | Guru ajukan pengganti untuk 1 sesi spesifik, perlu persetujuan; jadwal asli tidak berubah, otomatis normal lagi minggu berikutnya; ada laporannya untuk Admin/Kepsek |
| Wali Kelas | Penunjukan lewat menu Kelas; halaman kerja: data siswa, grafik &amp; rekap kehadiran per siswa |
| Dashboard Admin/Operator | Statistik total data master + presensi/jurnal hari ini + grafik kehadiran se-sekolah |
| Dashboard Kepala Sekolah | Monitoring real time: progres jadwal hari ini, guru belum presensi/jurnal, persentase kehadiran |
| Laporan | Filter tanggal/guru/kelas/mapel, export PDF (DomPDF) & Excel (PhpSpreadsheet) untuk Presensi & Jurnal |
| Pengguna | Kelola akun + role (multi-role lewat checkbox), guru+akun jadi satu form |
| Audit Log | Riwayat login/logout & tambah-ubah-hapus data di seluruh modul, bisa difilter dan berhalaman |

## Cara menambah guru baru (sekarang 1 langkah)

Menu **Guru** → Tambah guru → isi data profil, lalu isi juga bagian "Akun login" (username,
password, role tambahan kalau ada) di form yang sama. Profil dan akun langsung tertaut otomatis
— tidak perlu buka menu lain. Kosongkan username kalau guru itu belum perlu akses sistem; bisa
ditambahkan belakangan lewat Edit.

## Catatan jujur soal kelengkapan

Seluruh modul di SRS sudah ada dan berfungsi, termasuk seluruh dashboard per role, dan tidak
ada lagi menu "segera hadir" di sidebar. Dua hal berikut sengaja saya beri tahu apa adanya,
bukan disembunyikan:

- **Penguncian presensi/jurnal** saat ini terjadi begitu jurnal disimpan (sesi resmi "Selesai"),
  bukan berdasarkan jam tertentu di malam hari. Ini karena "batas waktu" di SRS lebih natural
  diartikan sebagai "sesi itu sendiri berakhir" untuk alur guru — implementasi jam cut-off
  tertentu (misal terkunci otomatis jam 23:59) bisa ditambahkan lewat scheduled task kalau
  sekolah Anda butuh itu secara spesifik.
- **Pencarian di Data Master** (Guru, Siswa, dst) bekerja instan di sisi browser dan mencakup
  semua baris yang dimuat — cocok untuk skala sekolah pada umumnya (puluhan-ratusan data).
  Kalau nanti jumlah siswa/guru sampai ribuan, pertimbangkan pagination sisi server; belum
  saya tambahkan sekarang karena akan mengubah cara kerja pencarian instan yang sudah ada.

---

## Troubleshooting

**`Call to undefined function App\Controllers\current_user()`**
→ Pastikan `app/Controllers/BaseController.php` dari paket ini sudah tersalin, dan isinya
punya baris `$this->helpers = ['auth'];` sebelum `parent::initController(...)`.

**Import Excel gagal / halaman Laporan PDF-Excel error**
→ Pastikan `composer require phpoffice/phpspreadsheet` dan `composer require dompdf/dompdf`
sudah dijalankan di project Anda (folder `vendor/phpoffice` dan `vendor/dompdf` harus ada).

**Field jadwal/kelas kosong padahal sudah diisi datanya**
→ Pastikan ada 1 Tahun Ajaran dan 1 Semester yang statusnya **Aktif** (menu Tahun Ajaran & Semester)
sebelum membuat Kelas atau Jadwal.

**Dashboard guru tidak menampilkan jadwal**
→ Cek 3 hal: (1) semester aktif sudah ada, (2) akun guru sudah ditautkan ke profil guru di menu
Pengguna & Role, (3) ada jadwal untuk **hari ini** (sesuai hari berjalan) di menu Jadwal Mengajar.
