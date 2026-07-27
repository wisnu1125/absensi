<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table         = 'audit_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false; // created_at diisi manual oleh AuditLogger
    protected $allowedFields = ['user_id', 'aktivitas', 'keterangan', 'ip_address', 'created_at'];

    /**
     * Daftar jenis aktivitas yang PERNAH tercatat, diambil langsung dari data
     * (bukan daftar statis) supaya otomatis ikut bertambah kalau ada modul baru
     * yang mencatat audit log dengan nama aktivitas baru.
     */
    public function getDistinctAktivitas(): array
    {
        return $this->distinct()->select('aktivitas')->orderBy('aktivitas', 'ASC')->findColumn('aktivitas') ?? [];
    }
}
