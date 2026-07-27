<?php

namespace App\Models;

use CodeIgniter\Model;

class WaliKelasModel extends Model
{
    protected $table         = 'wali_kelas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['guru_id', 'kelas_id', 'tahun_ajaran_id'];

    /**
     * Siapa wali kelas dari satu kelas (kalau ada).
     */
    public function getByKelas(int $kelasId): ?array
    {
        return $this->select('wali_kelas.*, guru.nama as nama_guru')
            ->join('guru', 'guru.id = wali_kelas.guru_id')
            ->where('kelas_id', $kelasId)
            ->first();
    }

    /**
     * Kelas mana yang jadi tanggung jawab satu guru, pada satu tahun ajaran
     * (biasanya tahun ajaran aktif). Dipakai di dashboard & halaman kerja wali kelas.
     */
    public function getByGuruTahun(int $guruId, int $tahunAjaranId): ?array
    {
        return $this->select('wali_kelas.*, kelas.nama_kelas, kelas.tingkat')
            ->join('kelas', 'kelas.id = wali_kelas.kelas_id')
            ->where('wali_kelas.guru_id', $guruId)
            ->where('wali_kelas.tahun_ajaran_id', $tahunAjaranId)
            ->first();
    }

    /**
     * Tetapkan/ganti wali kelas untuk satu kelas pada satu tahun ajaran.
     * $guruId = null berarti melepas wali kelas (kelas jadi tanpa wali kelas).
     */
    public function setWaliKelas(int $kelasId, int $tahunAjaranId, ?int $guruId): void
    {
        $existing = $this->where('kelas_id', $kelasId)->where('tahun_ajaran_id', $tahunAjaranId)->first();

        if ($guruId === null) {
            if ($existing) {
                $this->delete($existing['id']);
            }

            return;
        }

        if ($existing) {
            $this->update($existing['id'], ['guru_id' => $guruId]);
        } else {
            $this->insert(['guru_id' => $guruId, 'kelas_id' => $kelasId, 'tahun_ajaran_id' => $tahunAjaranId]);
        }
    }
}
