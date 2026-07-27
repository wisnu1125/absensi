<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'username', 'email', 'password', 'full_name', 'is_active', 'last_login_at',
    ];

    protected $validationRules = [
        'username'  => 'required|min_length[3]|max_length[50]',
        'full_name' => 'required|max_length[150]',
    ];

    /**
     * Cari user berdasarkan username, dipakai saat login.
     */
    public function findByUsername(string $username): ?array
    {
        return $this->where('username', $username)->first();
    }

    /**
     * Ambil semua role milik satu user (bisa lebih dari satu -> Multi Role).
     * Struktur: users -> user_roles -> roles
     */
    public function getRoles(int $userId): array
    {
        return $this->db->table('user_roles ur')
            ->select('roles.id, roles.name, roles.slug')
            ->join('roles', 'roles.id = ur.role_id')
            ->where('ur.user_id', $userId)
            ->get()
            ->getResultArray();
    }

    /**
     * Ganti seluruh role user (dipakai di form kelola pengguna nanti).
     * $roleIds contoh: [1, 3] -> Administrator + Guru.
     */
    public function syncRoles(int $userId, array $roleIds): void
    {
        $userRoleModel = new UserRoleModel();
        $userRoleModel->where('user_id', $userId)->delete();

        $rows = [];
        foreach ($roleIds as $roleId) {
            $rows[] = [
                'user_id'    => $userId,
                'role_id'    => $roleId,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        if (! empty($rows)) {
            $userRoleModel->insertBatch($rows);
        }
    }

    /**
     * Set password dengan hashing yang benar (dipakai saat create/reset password).
     */
    public function setPassword(int $userId, string $plainPassword): bool
    {
        return $this->update($userId, [
            'password' => password_hash($plainPassword, PASSWORD_BCRYPT),
        ]);
    }
}
