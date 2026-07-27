<?php

namespace App\Models;

use CodeIgniter\Model;

class PresensiModel extends Model
{
    protected $table         = 'presensi';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['jadwal_id', 'tanggal', 'locked_at', 'created_by'];

    public function findByJadwalTanggal(int $jadwalId, string $tanggal): ?array
    {
        return $this->where('jadwal_id', $jadwalId)->where('tanggal', $tanggal)->first();
    }
}
