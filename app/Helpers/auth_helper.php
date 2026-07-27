<?php
/**
 * Auth & RBAC Helper
 *
 * Kumpulan fungsi global untuk membaca sesi user yang sedang login
 * dan mengecek role yang dimilikinya. Dipakai di Controller maupun View.
 *
 * CARA AKTIFKAN:
 * Tambahkan 'auth' ke $helpers di app/Config/Autoload.php, contoh:
 *   public $helpers = ['auth'];
 * atau panggil helper('auth') di awal method Controller yang butuh.
 */

if (! function_exists('current_user')) {
    /**
     * Ambil data user yang sedang login dari session.
     * Return null jika belum login.
     */
    function current_user(): ?array
    {
        $session = session();

        if (! $session->get('isLoggedIn')) {
            return null;
        }

        return [
            'id'        => $session->get('user_id'),
            'username'  => $session->get('username'),
            'full_name' => $session->get('full_name'),
            'roles'     => $session->get('roles') ?? [],
        ];
    }
}

if (! function_exists('has_role')) {
    /**
     * Cek apakah user yang login memiliki 1 role tertentu.
     */
    function has_role(string $roleSlug): bool
    {
        $roles = session()->get('roles') ?? [];

        return in_array($roleSlug, $roles, true);
    }
}

if (! function_exists('has_any_role')) {
    /**
     * Cek apakah user yang login memiliki SALAH SATU dari beberapa role.
     * Karena satu user bisa multi role, ini yang paling sering dipakai
     * untuk menampilkan menu/sidebar.
     */
    function has_any_role(array $roleSlugs): bool
    {
        $roles = session()->get('roles') ?? [];

        return count(array_intersect($roleSlugs, $roles)) > 0;
    }
}

if (! function_exists('role_label')) {
    /**
     * Ubah slug role jadi label rapi untuk ditampilkan, contoh:
     * 'wali_kelas' -> 'Wali Kelas'
     */
    function role_label(string $roleSlug): string
    {
        return ucwords(str_replace('_', ' ', $roleSlug));
    }
}
