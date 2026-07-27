<?php

namespace App\Models;

use CodeIgniter\Model;

class SemesterModel extends Model
{
    protected $table         = 'semester';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['tahun_ajaran_id', 'nama', 'is_active'];

    protected $validationRules = [
        'tahun_ajaran_id' => 'required|is_natural_no_zero',
        'nama'            => 'required|in_list[Ganjil,Genap]',
    ];

    public function getWithTahunAjaran(): array
    {
        return $this->select('semester.*, tahun_ajaran.nama as nama_tahun_ajaran')
            ->join('tahun_ajaran', 'tahun_ajaran.id = semester.tahun_ajaran_id')
            ->orderBy('tahun_ajaran.nama', 'DESC')
            ->orderBy('semester.nama', 'ASC')
            ->findAll();
    }

    /**
     * Hanya boleh ada 1 semester aktif di seluruh sistem.
     */
    public function setActive(int $id): void
    {
        $this->where('id !=', $id)->set('is_active', 0)->update();
        $this->update($id, ['is_active' => 1]);
    }

    public function getActive(): ?array
    {
        return $this->select('semester.*, tahun_ajaran.nama as nama_tahun_ajaran')
            ->join('tahun_ajaran', 'tahun_ajaran.id = semester.tahun_ajaran_id')
            ->where('semester.is_active', 1)
            ->first();
    }
}
