<?php

namespace App\Controllers\Master;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\GuruModel;
use App\Models\RoleModel;
use App\Models\UserModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Guru extends BaseController
{
    protected GuruModel $model;

    public function __construct()
    {
        $this->model = new GuruModel();
    }

    public function index()
    {
        $userModel = new UserModel();
        $items     = $this->model->orderBy('nama', 'ASC')->findAll();

        foreach ($items as &$g) {
            if ($g['user_id']) {
                $u              = $userModel->find((int) $g['user_id']);
                $g['username']  = $u['username'] ?? null;
                $g['is_active_akun'] = $u['is_active'] ?? null;
                $roles          = $userModel->getRoles((int) $g['user_id']);
                $g['role_ids']  = array_map('intval', array_column($roles, 'id'));
                $g['role_slugs']= array_column($roles, 'slug');
            } else {
                $g['username']  = null;
                $g['is_active_akun'] = null;
                $g['role_ids']  = [];
                $g['role_slugs']= [];
            }
        }
        unset($g);

        $data = [
            'title'   => 'Guru',
            'content' => view('master/guru/index', [
                'items' => $items,
                'roles' => (new RoleModel())->orderBy('id', 'ASC')->findAll(),
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function store()
    {
        $nama = $this->request->getPost('nama');

        // ---- Bagian akun login (opsional): kalau username diisi, buat akun sekaligus ----
        $userId   = null;
        $username = trim((string) $this->request->getPost('username'));

        if ($username !== '') {
            $userModel = new UserModel();

            if ($userModel->findByUsername($username)) {
                return redirect()->to('/master/guru')->with('error', 'Username "' . $username . '" sudah dipakai.');
            }

            $password = (string) $this->request->getPost('password');
            if (strlen($password) < 6) {
                return redirect()->to('/master/guru')->with('error', 'Password akun minimal 6 karakter.');
            }

            $userId = $userModel->insert([
                'username'  => $username,
                'email'     => $this->request->getPost('email') ?: null,
                'password'  => password_hash($password, PASSWORD_BCRYPT),
                'full_name' => $nama,
                'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            ]);

            if (! $userId) {
                return redirect()->to('/master/guru')->with('error', implode(' ', $userModel->errors()));
            }

            $userModel->syncRoles((int) $userId, $this->roleIdsDenganGuru());
        }

        // ---- Profil guru ----
        $ok = $this->model->insert([
            'user_id'       => $userId,
            'nip'           => $this->request->getPost('nip') ?: null,
            'nama'          => $nama,
            'jenis_kelamin' => $this->request->getPost('jenis_kelamin'),
            'no_hp'         => $this->request->getPost('no_hp') ?: null,
            'alamat'        => $this->request->getPost('alamat') ?: null,
            'status'        => 'aktif',
        ]);

        if (! $ok) {
            return redirect()->to('/master/guru')->with('error', implode(' ', $this->model->errors()));
        }

        (new AuditLogger())->log('tambah_guru', 'Menambah guru: ' . $nama . ($userId ? ' (dengan akun login)' : ''));

        return redirect()->to('/master/guru')->with('message', 'Data guru' . ($userId ? ' beserta akun login' : '') . ' berhasil ditambahkan.');
    }

    public function update()
    {
        $id   = (int) $this->request->getPost('id');
        $guru = $this->model->find($id);
        $nama = $this->request->getPost('nama');

        if (! $guru) {
            return redirect()->to('/master/guru')->with('error', 'Data guru tidak ditemukan.');
        }

        $userModel = new UserModel();
        $userId    = $guru['user_id'];
        $username  = trim((string) $this->request->getPost('username'));

        if ($userId) {
            // ---- Sudah punya akun: update (username tidak bisa diganti) ----
            $sessionId = (int) session()->get('user_id');
            $roleIds   = $this->roleIdsDenganGuru();

            $adminRole = (new RoleModel())->where('slug', 'administrator')->first();
            if ((int) $userId === $sessionId && $adminRole && ! in_array((int) $adminRole['id'], $roleIds, true)) {
                return redirect()->to('/master/guru')->with('error', 'Tidak bisa melepas role Administrator dari akun yang sedang Anda pakai.');
            }

            $payload = [
                'email'     => $this->request->getPost('email') ?: null,
                'full_name' => $nama,
                'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            ];

            $password = (string) $this->request->getPost('password');
            if ($password !== '') {
                if (strlen($password) < 6) {
                    return redirect()->to('/master/guru')->with('error', 'Password akun minimal 6 karakter.');
                }
                $payload['password'] = password_hash($password, PASSWORD_BCRYPT);
            }

            $userModel->update((int) $userId, $payload);
            $userModel->syncRoles((int) $userId, $roleIds);
        } elseif ($username !== '') {
            // ---- Belum punya akun, dan admin mengisi username sekarang -> buat baru ----
            if ($userModel->findByUsername($username)) {
                return redirect()->to('/master/guru')->with('error', 'Username "' . $username . '" sudah dipakai.');
            }

            $password = (string) $this->request->getPost('password');
            if (strlen($password) < 6) {
                return redirect()->to('/master/guru')->with('error', 'Password akun minimal 6 karakter.');
            }

            $userId = $userModel->insert([
                'username'  => $username,
                'email'     => $this->request->getPost('email') ?: null,
                'password'  => password_hash($password, PASSWORD_BCRYPT),
                'full_name' => $nama,
                'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            ]);

            if (! $userId) {
                return redirect()->to('/master/guru')->with('error', implode(' ', $userModel->errors()));
            }

            $userModel->syncRoles((int) $userId, $this->roleIdsDenganGuru());
        }

        $ok = $this->model->update($id, [
            'user_id'       => $userId,
            'nip'           => $this->request->getPost('nip') ?: null,
            'nama'          => $nama,
            'jenis_kelamin' => $this->request->getPost('jenis_kelamin'),
            'no_hp'         => $this->request->getPost('no_hp') ?: null,
            'alamat'        => $this->request->getPost('alamat') ?: null,
            'status'        => $this->request->getPost('status'),
        ]);

        if (! $ok) {
            return redirect()->to('/master/guru')->with('error', implode(' ', $this->model->errors()));
        }

        (new AuditLogger())->log('ubah_guru', 'Mengubah data guru #' . $id);

        return redirect()->to('/master/guru')->with('message', 'Data guru berhasil diperbarui.');
    }

    /**
     * Ambil role_ids dari form, lalu pastikan role 'guru' selalu ikut —
     * supaya kalaupun admin lupa centang, orangnya tetap punya akses dashboard guru.
     */
    private function roleIdsDenganGuru(): array
    {
        $roleIds  = array_map('intval', $this->request->getPost('role_ids') ?? []);
        $guruRole = (new RoleModel())->where('slug', 'guru')->first();

        if ($guruRole && ! in_array((int) $guruRole['id'], $roleIds, true)) {
            $roleIds[] = (int) $guruRole['id'];
        }

        return $roleIds;
    }

    public function delete($id)
    {
        $this->model->delete((int) $id);

        (new AuditLogger())->log('hapus_guru', 'Menghapus guru #' . $id);

        return redirect()->to('/master/guru')->with('message', 'Data guru berhasil dihapus.');
    }

    /**
     * Unduh template Excel kosong untuk diisi lalu diimpor kembali.
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Guru');

        // setCellValueByColumnAndRow() sudah dihapus di PhpSpreadsheet 2.x,
        // jadi dipakai sintaks koordinat [kolom, baris] yang berlaku di versi saat ini.
        $headers = [
            'NIP', 'Nama', 'Jenis Kelamin (L/P)', 'No HP', 'Alamat',
            'Username (opsional)', 'Password (wajib jika Username diisi)', 'Email (opsional)',
            'Role Tambahan (opsional, pisah koma)',
        ];
        foreach ($headers as $i => $header) {
            $sheet->setCellValue([$i + 1, 1], $header);
        }
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setWidth(22);
        }
        $writer = new Xlsx($spreadsheet);

        return $this->streamXlsx($writer, 'template_import_guru.xlsx');
    }

    /**
     * Proses import dari file Excel yang diunggah.
     */
    public function import()
    {
        $file = $this->request->getFile('file_excel');

        if (! $file || ! $file->isValid()) {
            return redirect()->to('/master/guru')->with('error', 'File Excel tidak valid atau belum dipilih.');
        }

        $allowed = ['xlsx', 'xls', 'csv'];
        if (! in_array(strtolower($file->getClientExtension()), $allowed, true)) {
            return redirect()->to('/master/guru')->with('error', 'Format file harus xlsx, xls, atau csv.');
        }

        $spreadsheet = IOFactory::load($file->getTempName());
        $sheet       = $spreadsheet->getActiveSheet();
        $rawRows     = $sheet->toArray(null, true, true, false);

        // baris pertama = header, dilewati
        array_shift($rawRows);

        $rows = [];
        foreach ($rawRows as $r) {
            // urutan kolom sesuai template: NIP, Nama, JK, No HP, Alamat, Username, Password, Email, Role Tambahan
            if (empty(array_filter($r))) {
                continue; // lewati baris kosong
            }
            $rows[] = [
                'nip'           => $r[0] ?? '',
                'nama'          => $r[1] ?? '',
                'jenis_kelamin' => $r[2] ?? 'L',
                'no_hp'         => $r[3] ?? '',
                'alamat'        => $r[4] ?? '',
                'username'      => $r[5] ?? '',
                'password'      => $r[6] ?? '',
                'email'         => $r[7] ?? '',
                'role_tambahan' => $r[8] ?? '',
            ];
        }

        $hasil = $this->model->importRows($rows);

        (new AuditLogger())->log('import_guru', "Import Excel guru: {$hasil['sukses']} sukses, {$hasil['gagal']} gagal");

        $pesan = "Import selesai: {$hasil['sukses']} data berhasil ditambahkan, {$hasil['gagal']} dilewati.";
        if (! empty($hasil['errors'])) {
            $pesan .= ' Detail: ' . implode(' | ', array_slice($hasil['errors'], 0, 5));
        }

        return redirect()->to('/master/guru')->with('message', $pesan);
    }

    private function streamXlsx(Xlsx $writer, string $filename)
    {
        $response = service('response');
        $response->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setHeader('Cache-Control', 'max-age=0');

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $response->setBody($content);
    }
}
