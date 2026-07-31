<?php

namespace App\Models;

use CodeIgniter\Model;

class KelasModel extends Model
{
    protected $table         = 'kelas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tahun_ajaran_id', 'nama_kelas', 'tingkat'];

    protected $validationRules = [
        'tahun_ajaran_id' => 'required|is_natural_no_zero',
        'nama_kelas'      => 'required|max_length[50]',
        'tingkat'         => 'required|max_length[10]',
    ];

    public function getWithTahunAjaran(): array
    {
        return $this->select('kelas.*, tahun_ajaran.nama as nama_tahun_ajaran, guru.nama as nama_wali_kelas, wali_kelas.guru_id as wali_kelas_guru_id,
                (SELECT COUNT(*) FROM siswa WHERE siswa.kelas_id = kelas.id AND siswa.deleted_at IS NULL AND siswa.status = "aktif") as jumlah_siswa')
            ->join('tahun_ajaran', 'tahun_ajaran.id = kelas.tahun_ajaran_id')
            ->join('wali_kelas', 'wali_kelas.kelas_id = kelas.id', 'left')
            ->join('guru', 'guru.id = wali_kelas.guru_id', 'left')
            ->orderBy('tahun_ajaran.nama', 'DESC')
            ->orderBy('kelas.nama_kelas', 'ASC')
            ->findAll();
    }

    /**
     * Cari kelas berdasarkan nama, dalam tahun ajaran tertentu.
     * Dipakai saat import Excel siswa (mencocokkan kolom "Kelas" ke kelas_id).
     */
    public function findByNama(string $namaKelas, int $tahunAjaranId): ?array
    {
        return $this->where('nama_kelas', trim($namaKelas))
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->first();
    }
}
