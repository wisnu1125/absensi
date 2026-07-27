<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Membatasi akses route berdasarkan role.
 *
 * Cara pakai di Routes.php:
 *   $routes->group('master', ['filter' => ['auth', 'role:administrator,operator']], function ($routes) { ... });
 *
 * Karena satu user bisa multi role, filter ini meloloskan request selama
 * user punya SALAH SATU role yang diminta (bukan harus semua).
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Tidak ada role spesifik yang diminta -> loloskan (cukup filter 'auth' saja yang berlaku).
        if (empty($arguments)) {
            return;
        }

        $session   = session();
        $userRoles = $session->get('roles') ?? [];

        $allowed = array_intersect($arguments, $userRoles);

        if (empty($allowed)) {
            log_message('notice', 'Akses ditolak untuk user_id={user}, role dibutuhkan: {req}', [
                'user' => $session->get('user_id'),
                'req'  => implode(',', $arguments),
            ]);

            return redirect()->to('/dashboard')
                ->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada yang perlu dilakukan setelah request.
    }
}
