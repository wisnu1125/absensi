<?php

namespace App\Models;

use CodeIgniter\Model;

class MataPelajaranModel extends Model
{
    protected $table         = 'mata_pelajaran';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $useSoftDeletes = true;
    protected $allowedFields = ['kode', 'nama'];

    protected $validationRules = [
        'kode' => 'required|max_length[20]',
        'nama' => 'required|max_length[100]',
    ];
    protected $validationMessages = [
        'kode' => ['required' => 'Kode mata pelajaran wajib diisi.'],
        'nama' => ['required' => 'Nama mata pelajaran wajib diisi.'],
    ];
}
