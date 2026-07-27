<?php

namespace App\Controllers\Master;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\GuruModel;
use App\Models\RoleModel;
use App\Models\UserModel;

class Pengguna extends BaseController
{
    public function index()
    {
        $userModel = new UserModel();
        $guruModel = new GuruModel();

        $users = $userModel->orderBy('full_name', 'ASC')->findAll();

        foreach ($users as &$u) {
            $roles          = $userModel->getRoles((int) $u['id']);
            $u['role_ids']  = array_map('intval', array_column($roles, 'id'));
            $u['role_slugs'] = array_column($roles, 'slug');

            $guruTerhubung = $guruModel->findByUserId((int) $u['id']);
            $u['guru_id']   = $guruTerhubung['id'] ?? null;
            $u['guru_nama'] = $guruTerhubung['nama'] ?? null;
        }
        unset($u);

        $data = [
            'title'   => 'Pengguna & Role',
            'content' => view('master/pengguna/index', [
                'users' => $users,
                'roles' => (new RoleModel())->orderBy('id', 'ASC')->findAll(),
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function store()
    {
        $userModel = new UserModel();
        $username  = trim((string) $this->request->getPost('username'));
        $password  = (string) $this->request->getPost('password');

        if ($username === '') {
            return redirect()->to('/master/pengguna')->with('error', 'Username wajib diisi.');
        }
        if ($userModel->findByUsername($username)) {
            return redirect()->to('/master/pengguna')->with('error', 'Username "' . $username . '" sudah dipakai.');
        }
        if (strlen($password) < 6) {
            return redirect()->to('/master/pengguna')->with('error', 'Password minimal 6 karakter.');
        }

        $userId = $userModel->insert([
            'username'  => $username,
            'email'     => $this->request->getPost('email') ?: null,
            'password'  => password_hash($password, PASSWORD_BCRYPT),
            'full_name' => $this->request->getPost('full_name'),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ]);

        if (! $userId) {
            return redirect()->to('/master/pengguna')->with('error', implode(' ', $userModel->errors()));
        }

        $roleIds = array_map('intval', $this->request->getPost('role_ids') ?? []);
        $userModel->syncRoles((int) $userId, $roleIds);

        (new AuditLogger())->log('tambah_user', 'Menambah pengguna: ' . $username);

        return redirect()->to('/master/pengguna')->with('message', 'Pengguna berhasil ditambahkan.');
    }

    public function update()
    {
        $id        = (int) $this->request->getPost('id');
        $userModel = new UserModel();
        $sessionId = (int) session()->get('user_id');

        $roleIds = array_map('intval', $this->request->getPost('role_ids') ?? []);

        // Pengaman: jangan sampai admin melepas role administrator dari akunnya sendiri -> terkunci.
        $roleModel  = new RoleModel();
        $adminRole  = $roleModel->where('slug', 'administrator')->first();
        if ($id === $sessionId && $adminRole && ! in_array((int) $adminRole['id'], $roleIds, true)) {
            return redirect()->to('/master/pengguna')->with('error', 'Tidak bisa melepas role Administrator dari akun yang sedang Anda pakai.');
        }

        $payload = [
            'email'     => $this->request->getPost('email') ?: null,
            'full_name' => $this->request->getPost('full_name'),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ];

        $password = (string) $this->request->getPost('password');
        if ($password !== '') {
            if (strlen($password) < 6) {
                return redirect()->to('/master/pengguna')->with('error', 'Password minimal 6 karakter.');
            }
            $payload['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $ok = $userModel->update($id, $payload);
        if (! $ok) {
            return redirect()->to('/master/pengguna')->with('error', implode(' ', $userModel->errors()));
        }

        $userModel->syncRoles($id, $roleIds);

        (new AuditLogger())->log('ubah_user', 'Mengubah pengguna #' . $id);

        return redirect()->to('/master/pengguna')->with('message', 'Pengguna berhasil diperbarui.');
    }

    public function delete($id)
    {
        if ((int) $id === (int) session()->get('user_id')) {
            return redirect()->to('/master/pengguna')->with('error', 'Tidak bisa menghapus akun yang sedang Anda pakai.');
        }

        (new UserModel())->delete((int) $id);

        (new AuditLogger())->log('hapus_user', 'Menghapus pengguna #' . $id);

        return redirect()->to('/master/pengguna')->with('message', 'Pengguna berhasil dihapus.');
    }
}
