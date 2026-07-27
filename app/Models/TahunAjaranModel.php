<?php

namespace App\Models;

use CodeIgniter\Model;

class TahunAjaranModel extends Model
{
    protected $table         = 'tahun_ajaran';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['nama', 'is_active'];

    protected $validationRules = [
        'nama' => 'required|max_length[20]',
    ];

    /**
     * Hanya boleh ada 1 tahun ajaran aktif di seluruh sistem.
     * Menjadikan $id aktif otomatis menonaktifkan yang lain.
     */
    public function setActive(int $id): void
    {
        $this->where('id !=', $id)->set('is_active', 0)->update();
        $this->update($id, ['is_active' => 1]);
    }

    public function getActive(): ?array
    {
        return $this->where('is_active', 1)->first();
    }
}
