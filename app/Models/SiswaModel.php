<?php

namespace App\Models;

use CodeIgniter\Model;

class SiswaModel extends Model
{
    protected $table         = 'siswa';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nis', 'nama', 'jenis_kelamin', 'kelas_id', 'tanggal_lahir',
        'alamat', 'nama_ortu', 'no_hp_ortu', 'status',
    ];

    protected $validationRules = [
        'nis'           => 'required|max_length[30]|is_unique[siswa.nis,id,{id}]',
        'nama'          => 'required|max_length[150]',
        'jenis_kelamin' => 'required|in_list[L,P]',
    ];
    protected $validationMessages = [
        'nis' => [
            'required'  => 'NIS wajib diisi.',
            'is_unique' => 'NIS ini sudah dipakai siswa lain.',
        ],
        'nama' => ['required' => 'Nama siswa wajib diisi.'],
    ];

    public function getWithKelas(?int $kelasId = null): array
    {
        $builder = $this->select('siswa.*, kelas.nama_kelas')
            ->join('kelas', 'kelas.id = siswa.kelas_id', 'left');

        if ($kelasId) {
            $builder->where('siswa.kelas_id', $kelasId);
        }

        return $builder->orderBy('siswa.nama', 'ASC')->findAll();
    }

    /**
     * Insert banyak siswa sekaligus dari hasil parsing Excel.
     * Kolom "kelas" berupa NAMA kelas (mis. "VII A") dicocokkan ke kelas_id
     * lewat KelasModel::findByNama() dalam tahun ajaran yang aktif.
     * Baris dengan NIS kosong/dobel atau nama kelas yang tidak ditemukan dilewati.
     */
    public function importRows(array $rows, int $tahunAjaranId): array
    {
        $kelasModel = new KelasModel();
        $kelasCache = [];

        $sukses = 0;
        $gagal  = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $baris = $i + 2; // baris ke-berapa di file Excel (setelah header)
            $nis   = trim((string) ($row['nis'] ?? ''));
            $nama  = trim((string) ($row['nama'] ?? ''));

            if ($nis === '' || $nama === '') {
                $gagal++;
                $errors[] = "Baris {$baris}: NIS atau nama kosong.";
                continue;
            }

            if ($this->where('nis', $nis)->first()) {
                $gagal++;
                $errors[] = "Baris {$baris}: NIS {$nis} sudah terdaftar.";
                continue;
            }

            $namaKelas = trim((string) ($row['kelas'] ?? ''));
            $kelasId   = null;

            if ($namaKelas !== '') {
                if (! array_key_exists($namaKelas, $kelasCache)) {
                    $found = $kelasModel->findByNama($namaKelas, $tahunAjaranId);
                    $kelasCache[$namaKelas] = $found['id'] ?? null;
                }
                $kelasId = $kelasCache[$namaKelas];

                if ($kelasId === null) {
                    $errors[] = "Baris {$baris}: kelas '{$namaKelas}' tidak ditemukan, siswa tetap disimpan tanpa kelas.";
                }
            }

            $tglLahir = trim((string) ($row['tanggal_lahir'] ?? ''));

            $this->insert([
                'nis'           => $nis,
                'nama'          => $nama,
                'jenis_kelamin' => strtoupper(trim((string) ($row['jenis_kelamin'] ?? 'L'))) === 'P' ? 'P' : 'L',
                'kelas_id'      => $kelasId,
                'tanggal_lahir' => $tglLahir !== '' ? date('Y-m-d', strtotime($tglLahir)) : null,
                'alamat'        => trim((string) ($row['alamat'] ?? '')) ?: null,
                'nama_ortu'     => trim((string) ($row['nama_ortu'] ?? '')) ?: null,
                'no_hp_ortu'    => trim((string) ($row['no_hp_ortu'] ?? '')) ?: null,
                'status'        => 'aktif',
            ]);

            $sukses++;
        }

        return ['sukses' => $sukses, 'gagal' => $gagal, 'errors' => $errors];
    }
}
