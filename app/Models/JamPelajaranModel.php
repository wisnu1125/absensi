<?php

namespace App\Models;

use CodeIgniter\Model;

class JamPelajaranModel extends Model
{
    protected $table         = 'jam_pelajaran';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['jam_ke', 'jam_mulai', 'jam_selesai'];

    protected $validationRules = [
        'jam_ke'      => 'required|is_natural_no_zero|is_unique[jam_pelajaran.jam_ke,id,{id}]',
        'jam_mulai'   => 'required',
        'jam_selesai' => 'required',
    ];
    protected $validationMessages = [
        'jam_ke' => [
            'required'  => 'Jam ke- wajib diisi.',
            'is_unique' => 'Jam ke- ini sudah dipakai, pilih nomor lain.',
        ],
    ];

    public function getAllOrdered(): array
    {
        return $this->orderBy('jam_ke', 'ASC')->findAll();
    }

    public function findByJamKe(int $jamKe): ?array
    {
        return $this->where('jam_ke', $jamKe)->first();
    }

    /**
     * Cek apakah rentang jam yang diisi tumpang tindih dengan periode lain yang
     * sudah ada — dua periode tidak boleh saling beririsan waktunya.
     */
    public function cekBentrok(string $jamMulai, string $jamSelesai, ?int $excludeId = null): ?array
    {
        $builder = $this->where('jam_mulai <', $jamSelesai)->where('jam_selesai >', $jamMulai);

        if ($excludeId !== null) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->first();
    }

    /**
     * Dipakai sebelum menghapus: cegah hapus periode yang masih dirujuk oleh
     * jadwal yang sudah ada (jadwal.jam_ke_mulai / jam_ke_selesai), supaya
     * jadwal itu tidak jadi tidak bisa diedit karena periodenya hilang.
     */
    public function dipakaiJadwal(int $jamKe): bool
    {
        $jadwalModel = new JadwalModel();

        return $jadwalModel->groupStart()
                ->where('jam_ke_mulai', $jamKe)
                ->orWhere('jam_ke_selesai', $jamKe)
            ->groupEnd()
            ->countAllResults() > 0;
    }
}
