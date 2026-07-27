<?php

namespace App\Models;

use CodeIgniter\Model;

class JurnalMengajarModel extends Model
{
    protected $table         = 'jurnal_mengajar';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'jadwal_id', 'tanggal', 'materi', 'tujuan_pembelajaran', 'metode', 'media',
        'kegiatan_pembelajaran', 'catatan', 'kendala', 'tindak_lanjut', 'locked_at', 'created_by',
    ];

    protected $validationRules = [
        'materi' => 'required',
    ];
    protected $validationMessages = [
        'materi' => ['required' => 'Materi pembelajaran wajib diisi.'],
    ];

    public function findByJadwalTanggal(int $jadwalId, string $tanggal): ?array
    {
        return $this->where('jadwal_id', $jadwalId)->where('tanggal', $tanggal)->first();
    }
}
