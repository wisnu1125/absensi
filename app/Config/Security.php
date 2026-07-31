<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Security extends BaseConfig
{
    /**
     * CSRF Protection Method — 'cookie' (bawaan CI4).
     */
    public string $csrfProtection = 'cookie';

    /**
     * Randomisasi token tambahan — dibiarkan bawaan (nonaktif).
     */
    public bool $tokenRandomize = false;

    public string $tokenName = 'csrf_test_name';
    public string $headerName = 'X-CSRF-TOKEN';
    public string $cookieName = 'csrf_cookie_name';
    public int $expires = 7200;

    /**
     * CSRF Regenerate — SENGAJA dimatikan (bawaan CI4: true).
     *
     * Bawaannya, token CSRF diregenerasi di SETIAP request yang lolos
     * validasi. Ini pengamanan ekstra thd serangan BREACH, tapi utk
     * aplikasi internal sekolah ber-login ini (bukan aplikasi publik
     * berisiko tinggi), efeknya lebih banyak merugikan daripada
     * membantu: SETIAP form yang masih terbuka di halaman (mis. modal
     * Tambah/Edit di Grid Jadwal) langsung punya token BASI begitu ada
     * SATU request lain berhasil, dan submission berikutnya ditolak
     * validasi CSRF secara diam-diam — inilah akar bug "klik simpan
     * tidak menyimpan" / "modal tidak mau hilang" yang dilaporkan.
     *
     * Dengan token tetap sama selama SATU SESI LOGIN (bukan per-request),
     * seluruh kelas bug token-basi ini hilang — jauh lebih andal utk
     * alur AJAX (Grid Jadwal, dan fitur AJAX serupa di masa depan)
     * dibanding terus-menerus menyinkronkan token lewat JavaScript
     * setiap kali ada respons dari server.
     */
    public bool $regenerate = false;

    /**
     * CSRF Redirect — bawaan (redirect hanya di production).
     */
    public bool $redirect = (ENVIRONMENT === 'production');
}
