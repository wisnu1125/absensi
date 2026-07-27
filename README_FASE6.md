# Fase 6 — Pengguna & Role (menutup celah "guru baru tidak bisa login")

## File baru / berubah

```
app/Config/Routes.php                  → TIMPA (menambahkan grup route "pengguna", khusus administrator)
app/Views/layouts/_sidebar.php         → TIMPA ("Pengguna & role" sekarang aktif)

app/Controllers/Master/Pengguna.php    → BARU
app/Views/master/pengguna/index.php    → BARU
```

## Kenapa fase ini penting
Sejak Fase 2, menu Guru hanya menambah **data profil** guru (nama, NIP, dst) — bukan akun login.
Tanpa fase ini, satu-satunya guru yang bisa login adalah akun `admin` bawaan. Sekarang admin bisa:

1. Buat akun baru (username, password, nama, email opsional)
2. Centang role apa saja yang dimiliki (bisa lebih dari satu — ini UI untuk RBAC multi-role-nya)
3. **Tautkan akun itu ke salah satu profil guru** yang sudah ada di menu Guru — begitu tertaut,
   pemilik akun otomatis bisa memakai dashboard "Jadwal hari ini" dan alur Presensi/Jurnal

## Alur yang disarankan untuk menambah guru baru
1. Menu **Guru** → tambah profil guru (nama, NIP, dst) seperti biasa
2. Menu **Pengguna & role** → tambah pengguna baru, centang role **Guru**, lalu di dropdown
   "Tautkan ke profil guru" pilih nama guru yang baru saja dibuat di langkah 1

## Pengaman yang sudah dibangun
- Password minimal 6 karakter, di-hash dengan `password_hash()` (bukan disimpan polos)
- Username tidak bisa diubah lewat form edit (mencegah salah ganti identitas login)
- Admin **tidak bisa melepas role Administrator dari akunnya sendiri** maupun **menghapus akun
  yang sedang dipakai login** — mencegah terkunci dari sistem sendiri
- Kalau admin menautkan guru yang ternyata sudah terhubung ke akun lain, sistem menolak menautkan
  ulang (tidak diam-diam mencuri tautan orang lain) dan memberi tahu lewat pesan

## Sudah diuji dengan data nyata
- Assign & ganti kombinasi role (multi-role) lewat pola delete-lalu-insert `syncRoles()`
- Penautan guru ⇄ user, termasuk pengecekan "sudah terhubung ke akun lain"
- `GuruModel::findByUserId()` (dipakai di seluruh alur Presensi/Jurnal sejak Fase 4) berhasil
  menemukan guru dari akun yang baru ditautkan lewat fase ini

## Belum termasuk (menyusul)
- Audit Log viewer (datanya sudah tercatat sejak Fase 1, tinggal UI untuk melihatnya)
- Dashboard Wali Kelas & Kepala Sekolah, Riwayat Mengajar
- Jam Pelajaran & Hari Libur (CRUD-nya)
