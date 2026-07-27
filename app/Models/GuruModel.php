<?php

namespace App\Models;

use CodeIgniter\Model;

class GuruModel extends Model
{
    protected $table         = 'guru';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['user_id', 'nip', 'nama', 'jenis_kelamin', 'no_hp', 'alamat', 'status'];

    protected $validationRules = [
        'nama'          => 'required|max_length[150]',
        'jenis_kelamin' => 'required|in_list[L,P]',
    ];
    protected $validationMessages = [
        'nama' => ['required' => 'Nama guru wajib diisi.'],
    ];

    /**
     * Cari data guru berdasarkan user_id akun yang sedang login.
     * Dipakai di alur Mulai Mengajar untuk tahu guru_id dari sesi login.
     */
    public function findByUserId(int $userId): ?array
    {
        return $this->where('user_id', $userId)->first();
    }

    /**
     * Guru yang akunnya punya role wali_kelas — inilah yang boleh dipilih sebagai
     * wali kelas saat admin membuat/mengubah data Kelas.
     */
    public function getDenganRoleWaliKelas(): array
    {
        return $this->select('guru.*')
            ->join('users', 'users.id = guru.user_id')
            ->join('user_roles', 'user_roles.user_id = users.id')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('roles.slug', 'wali_kelas')
            ->where('guru.status', 'aktif')
            ->groupBy('guru.id')
            ->orderBy('guru.nama', 'ASC')
            ->findAll();
    }

    /**
     * Insert banyak guru sekaligus dari hasil parsing Excel.
     * Kolom akun (username/password/email/role_tambahan) bersifat OPSIONAL per baris —
     * kalau username diisi, akun login dibuat sekaligus (persis logika di form tambah
     * satuan); kalau kosong, hanya profil guru yang dibuat seperti sebelumnya.
     */
    public function importRows(array $rows): array
    {
        $userModel = new UserModel();
        $roleModel = new RoleModel();

        $guruRole = $roleModel->where('slug', 'guru')->first();

        // Peta pencarian role yang fleksibel: slug ATAU nama tampilan (dengan spasi/underscore),
        // huruf besar/kecil diabaikan, supaya admin sekolah bisa tulis "Wali Kelas" apa adanya.
        $petaRole = [];
        foreach ($roleModel->findAll() as $r) {
            $petaRole[strtolower($r['slug'])] = (int) $r['id'];
            $petaRole[strtolower(str_replace(' ', '_', $r['name']))] = (int) $r['id'];
        }

        $sukses            = 0;
        $gagal              = 0;
        $errors             = [];
        $usernameDalamFile = [];

        foreach ($rows as $i => $row) {
            $baris = $i + 2; // baris ke berapa di file Excel (setelah header)
            $nama  = trim((string) ($row['nama'] ?? ''));

            if ($nama === '') {
                $gagal++;
                $errors[] = "Baris {$baris}: nama kosong, dilewati.";
                continue;
            }

            $userId   = null;
            $username = trim((string) ($row['username'] ?? ''));

            if ($username !== '') {
                if (isset($usernameDalamFile[$username]) || $userModel->findByUsername($username)) {
                    $gagal++;
                    $errors[] = "Baris {$baris}: username '{$username}' sudah dipakai, baris dilewati (profil TIDAK dibuat).";
                    continue;
                }

                $password = trim((string) ($row['password'] ?? ''));
                if (strlen($password) < 6) {
                    $gagal++;
                    $errors[] = "Baris {$baris}: password untuk '{$username}' kosong/kurang dari 6 karakter, baris dilewati.";
                    continue;
                }

                $userId = $userModel->insert([
                    'username'  => $username,
                    'email'     => trim((string) ($row['email'] ?? '')) ?: null,
                    'password'  => password_hash($password, PASSWORD_BCRYPT),
                    'full_name' => $nama,
                    'is_active' => 1,
                ]);

                if (! $userId) {
                    $gagal++;
                    $errors[] = "Baris {$baris}: gagal membuat akun '{$username}'.";
                    continue;
                }

                $usernameDalamFile[$username] = true;

                $roleIds = [];
                foreach (explode(',', (string) ($row['role_tambahan'] ?? '')) as $bagian) {
                    $key = strtolower(str_replace(' ', '_', trim($bagian)));
                    if ($key !== '' && isset($petaRole[$key])) {
                        $roleIds[] = $petaRole[$key];
                    }
                }
                if ($guruRole && ! in_array((int) $guruRole['id'], $roleIds, true)) {
                    $roleIds[] = (int) $guruRole['id'];
                }
                $userModel->syncRoles((int) $userId, array_unique($roleIds));
            }

            $this->insert([
                'user_id'       => $userId,
                'nip'           => trim((string) ($row['nip'] ?? '')) ?: null,
                'nama'          => $nama,
                'jenis_kelamin' => strtoupper(trim((string) ($row['jenis_kelamin'] ?? 'L'))) === 'P' ? 'P' : 'L',
                'no_hp'         => trim((string) ($row['no_hp'] ?? '')) ?: null,
                'alamat'        => trim((string) ($row['alamat'] ?? '')) ?: null,
                'status'        => 'aktif',
            ]);

            $sukses++;
        }

        return ['sukses' => $sukses, 'gagal' => $gagal, 'errors' => $errors];
    }
}
