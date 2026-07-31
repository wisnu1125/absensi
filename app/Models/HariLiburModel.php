<?php

namespace App\Models;

use CodeIgniter\Model;

class HariLiburModel extends Model
{
    protected $table         = 'hari_libur';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tanggal', 'keterangan'];

    protected $validationRules = [
        // 'id' WAJIB ada di sini — sejak CI4 4.3.5, placeholder {id} di is_unique
        // tidak diganti kalau field id tidak punya aturan validasi sendiri (lihat
        // penjelasan lengkap di SiswaModel, bug yang sama persis terjadi di sini).
        'id'         => 'permit_empty|is_natural_no_zero',
        'tanggal'    => 'required|valid_date|is_unique[hari_libur.tanggal,id,{id}]',
        'keterangan' => 'required|max_length[255]',
    ];
    protected $validationMessages = [
        'tanggal' => [
            'required'  => 'Tanggal wajib diisi.',
            'is_unique' => 'Tanggal ini sudah terdaftar sebagai hari libur.',
        ],
        'keterangan' => ['required' => 'Keterangan wajib diisi.'],
    ];

    public function getAkanDatang(int $limit = 10): array
    {
        return $this->where('tanggal >=', date('Y-m-d'))
            ->orderBy('tanggal', 'ASC')
            ->findAll($limit);
    }

    public function isHariLibur(string $tanggal): bool
    {
        return (bool) $this->where('tanggal', $tanggal)->first();
    }
}
