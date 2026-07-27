# Fase 4 — Alur Inti: Mulai Mengajar → Presensi → Jurnal

## File baru / berubah

```
app/Config/Routes.php                → TIMPA (menambahkan grup route "mengajar")
app/Controllers/Dashboard.php        → TIMPA (sekarang menampilkan jadwal hari ini yang sungguhan)
app/Views/dashboard/_content.php     → TIMPA
app/Views/layouts/_sidebar.php       → TIMPA ("Jadwal hari ini" sekarang aktif)
app/Models/GuruModel.php             → TIMPA (tambah findByUserId())
app/Models/JadwalModel.php           → TIMPA (tambah getJadwalHariIniByGuru(), getJadwalMilikGuru())
app/Models/SemesterModel.php         → TIMPA (getActive() sekarang ikut tahun ajaran)

app/Models/PresensiModel.php         → BARU
app/Models/PresensiDetailModel.php   → BARU
app/Models/JurnalMengajarModel.php   → BARU
app/Controllers/Mengajar.php         → BARU
app/Views/mengajar/presensi.php      → BARU
app/Views/mengajar/jurnal.php        → BARU
```

## Prasyarat sebelum dicoba
Supaya "Jadwal hari ini" muncul di dashboard guru, pastikan sudah ada:
1. Tahun ajaran & semester aktif (menu Tahun Ajaran & Semester)
2. Guru yang **akun user-nya sudah terhubung** — kolom `user_id` di tabel `guru` harus diisi
   dengan `id` akun login guru tersebut (menghubungkan ini lewat UI menyusul di fase berikutnya;
   untuk sekarang bisa di-set manual lewat phpMyAdmin: `UPDATE guru SET user_id = X WHERE id = Y`)
3. Jadwal untuk **hari ini** (sesuai hari berjalan di kalender)

## Cara kerja alur
1. Guru login → dashboard otomatis menampilkan jadwal hari ini dengan status:
   - **Belum dimulai** → tombol "Mulai mengajar" membuka halaman Presensi
   - **Sedang berlangsung** → presensi sudah diisi, tombol "Lanjut ke jurnal"
   - **Selesai** → presensi & jurnal sudah lengkap, terkunci
2. Halaman Presensi: satu halaman, semua siswa langsung terdaftar dengan status default "Hadir".
   Guru tinggal klik pill status per siswa (atau klik "Hadir semua"), lalu Simpan.
3. Setelah presensi disimpan, otomatis diarahkan ke halaman Jurnal (persis alur di SRS).
4. Setelah jurnal disimpan, presensi & jurnal hari itu otomatis terkunci (tidak bisa diubah lagi
   lewat alur normal) — sesi dianggap resmi selesai.

## Keamanan
Setiap request presensi/jurnal divalidasi ulang bahwa jadwal_id yang diakses benar-benar milik
guru yang sedang login (`JadwalModel::getJadwalMilikGuru()`), jadi guru A tidak bisa mengisi
presensi kelas milik guru B hanya dengan mengubah angka di URL.

## Belum termasuk (menyusul)
- Riwayat mengajar (lihat presensi/jurnal hari-hari sebelumnya)
- Rekap Wali Kelas & Monitoring Kepala Sekolah
- Laporan dengan export PDF (DomPDF) & Excel (PhpSpreadsheet)
- Jam Pelajaran & Hari Libur (CRUD-nya, datanya sudah ada default)
