# Sistem Presensi Siswa & Jurnal Mengajar — Panduan Lengkap (Fase 1–6)

Dokumen ini **menggantikan semua README_FASE*.md sebelumnya**. Kalau Anda masih punya file-file
README lama dari zip sebelumnya, boleh dihapus — semua isinya sudah dirangkum di sini, plus
perbaikan untuk file yang sempat kelewat.

## ⚠️ Kalau Anda upgrade dari zip fase sebelumnya

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
app/Controllers/WaliKelas.php
app/Controllers/Master/AuditLog.php
app/Controllers/Master/Guru.php
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
app/Models/WaliKelasModel.php
app/Views/auth/login.php
app/Views/dashboard/_content.php
app/Views/laporan/jurnal.php
app/Views/laporan/jurnal_pdf.php
app/Views/laporan/presensi.php
app/Views/laporan/presensi_pdf.php
app/Views/layouts/_icons.php
app/Views/layouts/_sidebar.php
app/Views/layouts/_topbar.php
app/Views/layouts/main.php
app/Views/master/audit_log/index.php
app/Views/master/guru/index.php
app/Views/master/jadwal/index.php
app/Views/master/jadwal/kosong.php
app/Views/master/jam_pelajaran/index.php
app/Views/master/kelas/index.php
app/Views/master/mata_pelajaran/index.php
app/Views/master/pengguna/index.php
app/Views/master/siswa/index.php
app/Views/master/tahun_ajaran/index.php
app/Views/mengajar/jurnal.php
app/Views/mengajar/presensi.php
app/Views/wali_kelas/index.php
app/Views/wali_kelas/kosong.php
database/schema.sql
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
| Fondasi | Login, session, Multi-Role RBAC (`users`–`user_roles`–`roles`), layout clean minimalist |
| Data Master | Mata Pelajaran, Jam Pelajaran (bisa diatur bebas sesuai ketentuan sekolah), Tahun Ajaran & Semester, Kelas, Guru, Siswa — pola "index saja" (tabel + modal, tanpa halaman form terpisah), import Excel untuk Guru & Siswa |
| Jadwal | CRUD jadwal dengan validasi otomatis anti-bentrok (guru & kelas) |
| Alur mengajar | Dashboard guru (jadwal hari ini + status) → Mulai Mengajar → Presensi (1 klik per siswa + "Hadir semua") → Jurnal → otomatis terkunci |
| Laporan | Filter tanggal/guru/kelas/mapel, export PDF (DomPDF) & Excel (PhpSpreadsheet) untuk Presensi & Jurnal |
| Pengguna | Kelola akun + role (multi-role lewat checkbox), guru+akun jadi satu form |
| Wali Kelas | Penunjukan lewat menu Kelas; halaman kerja: data siswa, grafik &amp; rekap kehadiran per siswa |
| Audit Log | Riwayat login/logout & tambah-ubah-hapus data di seluruh modul, bisa difilter (tanggal, pengguna, jenis aktivitas, kata kunci) dan berhalaman |

## Cara menambah guru baru (sekarang 1 langkah)

Menu **Guru** → Tambah guru → isi data profil, lalu isi juga bagian "Akun login" (username,
password, role tambahan kalau ada) di form yang sama. Profil dan akun langsung tertaut otomatis
— tidak perlu buka menu lain. Kosongkan username kalau guru itu belum perlu akses sistem; bisa
ditambahkan belakangan lewat Edit.

## Belum termasuk (menyusul)

- Monitoring Kepala Sekolah (dashboard real-time)
- Riwayat Mengajar (guru melihat presensi/jurnal hari-hari sebelumnya)
- Hari Libur (CRUD-nya — datanya belum ada tabel isi default, kalender akademik)

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
