<?php

namespace App\Controllers;

use App\Libraries\AuditLogger;
use App\Models\UserModel;

class Auth extends BaseController
{
    /**
     * Tampilkan form login.
     * Kalau sudah login, langsung lempar ke dashboard (tidak perlu login ulang).
     */
    public function index()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login', ['title' => 'Masuk']);
    }

    /**
     * Proses login.
     */
    public function login()
    {
        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Username dan password wajib diisi.');
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $userModel = new UserModel();
        $user      = $userModel->findByUsername($username);

        if (! $user || ! password_verify($password, $user['password'])) {
            return redirect()->back()->withInput()
                ->with('error', 'Username atau password salah.');
        }

        if ((int) $user['is_active'] !== 1) {
            return redirect()->back()->withInput()
                ->with('error', 'Akun Anda nonaktif. Hubungi administrator sekolah.');
        }

        $roles     = $userModel->getRoles((int) $user['id']);
        $roleSlugs = array_column($roles, 'slug');

        if (empty($roleSlugs)) {
            return redirect()->back()->withInput()
                ->with('error', 'Akun Anda belum memiliki role. Hubungi administrator.');
        }

        session()->set([
            'user_id'    => (int) $user['id'],
            'username'   => $user['username'],
            'full_name'  => $user['full_name'],
            'roles'      => $roleSlugs,
            'isLoggedIn' => true,
        ]);

        $userModel->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        (new AuditLogger())->log('login', 'Login ke sistem sebagai ' . implode(', ', $roleSlugs));

        return redirect()->to('/dashboard');
    }

    /**
     * Logout dan hancurkan session.
     */
    public function logout()
    {
        (new AuditLogger())->log('logout', 'Logout dari sistem');

        session()->destroy();

        return redirect()->to('/login')->with('message', 'Anda telah keluar dari sistem.');
    }
}
