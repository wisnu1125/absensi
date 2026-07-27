<?php

namespace App\Models;

use CodeIgniter\Model;

class HariLiburModel extends Model
{
    protected $table         = 'hari_libur';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['tanggal', 'keterangan'];

    protected $validationRules = [
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
