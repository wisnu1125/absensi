# Panduan Instalasi — Fase 1 (Fondasi: Database, Auth, RBAC, Layout)

## 1. Import database
1. Buka phpMyAdmin, buat database baru (contoh nama: `db_presensi`).
2. Klik database tersebut → tab **Import** → pilih file `database/schema.sql` → **Go**.
3. Kalau sukses akan muncul 16 tabel, dan sudah ada 1 akun admin siap pakai:
   - **Username:** `admin`
   - **Password:** `admin123`  ⚠️ **wajib diganti setelah login pertama**

## 2. Salin file ke project CI4 Anda
Salin folder/file berikut ke lokasi yang sama persis di project CI4 Anda (folder `app/` dan `public/` yang sudah ada dari hasil composer install):

```
app/Config/Filters.php          → timpa (atau merge, lihat catatan di dalam file)
app/Config/Routes.php           → timpa
app/Filters/AuthFilter.php      → baru
app/Filters/RoleFilter.php      → baru
app/Models/UserModel.php        → baru
app/Models/RoleModel.php        → baru
app/Models/UserRoleModel.php    → baru
app/Models/AuditLogModel.php    → baru
app/Libraries/AuditLogger.php   → baru
app/Helpers/auth_helper.php     → baru
app/Controllers/Auth.php        → baru
app/Controllers/Dashboard.php   → baru
app/Views/layouts/main.php      → baru
app/Views/layouts/_sidebar.php  → baru
app/Views/layouts/_topbar.php   → baru
app/Views/auth/login.php        → baru
app/Views/dashboard/_content.php→ baru
public/assets/css/app.css       → baru
```

## 3. Aktifkan helper `auth`
Buka `app/Controllers/BaseController.php` bawaan Anda, tambahkan `'auth'` ke `$helpers` **sebelum** baris `parent::initController(...)`:

```php
public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
{
    $this->helpers = ['form', 'url', 'auth'];   // <-- tambahkan baris ini

    parent::initController($request, $response, $logger);
}
```

## 4. Set koneksi database
Buka file `.env` di root project (kalau belum ada, copy dari `env` lalu rename jadi `.env`), isi bagian database:

```
database.default.hostname = localhost
database.default.database = db_presensi
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
```

Juga pastikan `CI_ENVIRONMENT = development` selama masa pengembangan.

## 5. Jalankan
```
php spark serve
```
Buka `http://localhost:8080`, akan otomatis diarahkan ke halaman login. Masuk dengan akun admin di atas.

---

## Yang sudah bisa dicoba di Fase 1 ini
- Login / logout dengan session
- Multi-role: satu akun bisa punya beberapa role sekaligus (lihat tabel `user_roles`), sidebar & dashboard otomatis menyesuaikan gabungan role tersebut
- Audit log otomatis mencatat setiap login & logout (cek tabel `audit_logs`)
- Desain clean minimalist dengan CSS custom (tanpa Bootstrap/Tailwind)

## Yang BELUM ada di Fase 1 (menyusul di fase berikutnya)
- CRUD Data Master (Guru, Siswa, Kelas, dst)
- Jadwal mengajar + validasi bentrok
- Alur Presensi & Jurnal
- Laporan (Excel/PDF dengan PhpSpreadsheet & DomPDF)

Menu untuk bagian-bagian ini sudah tampil di sidebar (menyesuaikan role), tapi masih ditandai nonaktif sampai modulnya dibangun.
