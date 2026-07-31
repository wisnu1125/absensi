<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Guru Pengampu — relasi Guru x Mata Pelajaran x Tingkat kelas. Ini FONDASI
 * baru: menentukan siapa BERHAK mengajar mapel apa di tingkat mana, dipakai
 * sebagai sumber pilihan saat membuat Jadwal (bukan lagi guru+mapel bebas
 * terpisah), dan jadi induk bagi Tujuan Pembelajaran (TP) per guru+mapel+
 * tingkat.
 */
class GuruPengampuModel extends Model
{
    protected $table          = 'guru_pengampu';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = ['guru_id', 'mapel_id', 'tingkat'];

    protected $validationRules = [
        'id'       => 'permit_empty|is_natural_no_zero',
        'guru_id'  => 'required|is_natural_no_zero',
        'mapel_id' => 'required|is_natural_no_zero',
        'tingkat'  => 'required|max_length[20]',
    ];
    protected $validationMessages = [
        'guru_id'  => ['required' => 'Guru wajib dipilih.'],
        'mapel_id' => ['required' => 'Mata pelajaran wajib dipilih.'],
        'tingkat'  => ['required' => 'Tingkat kelas wajib dipilih.'],
    ];

    /**
     * Semua Guru Pengampu lengkap dengan nama guru & mapel — dipakai halaman
     * kelola Guru Pengampu dan dropdown-dropdown yang butuh label lengkap.
     */
    public function getSemuaLengkap(): array
    {
        return $this->select('guru_pengampu.*, guru.nama as nama_guru, mata_pelajaran.nama as nama_mapel')
            ->join('guru', 'guru.id = guru_pengampu.guru_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id = guru_pengampu.mapel_id')
            ->orderBy('guru.nama', 'ASC')->orderBy('mata_pelajaran.nama', 'ASC')->orderBy('guru_pengampu.tingkat', 'ASC')
            ->findAll();
    }

    /**
     * Guru Pengampu utk SATU tingkat tertentu — dipakai form Tambah/Edit
     * Jadwal: begitu admin pilih Tingkat, dropdown Guru Pengampu difilter
     * cuma yang berhak mengajar di tingkat itu.
     */
    public function getUntukTingkat(string $tingkat): array
    {
        return $this->select('guru_pengampu.*, guru.nama as nama_guru, mata_pelajaran.nama as nama_mapel')
            ->join('guru', 'guru.id = guru_pengampu.guru_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id = guru_pengampu.mapel_id')
            ->where('guru_pengampu.tingkat', $tingkat)
            ->orderBy('guru.nama', 'ASC')
            ->findAll();
    }

    /**
     * Semua Guru Pengampu milik SATU guru — dipakai Dashboard Guru (menu
     * Master TP nantinya perlu tahu "mapel+tingkat apa saja yang saya ampu").
     */
    public function getUntukGuru(int $guruId): array
    {
        return $this->select('guru_pengampu.*, mata_pelajaran.nama as nama_mapel')
            ->join('mata_pelajaran', 'mata_pelajaran.id = guru_pengampu.mapel_id')
            ->where('guru_pengampu.guru_id', $guruId)
            ->orderBy('mata_pelajaran.nama', 'ASC')->orderBy('guru_pengampu.tingkat', 'ASC')
            ->findAll();
    }

    public function sudahAda(int $guruId, int $mapelId, string $tingkat, ?int $excludeId = null): bool
    {
        $builder = $this->where('guru_id', $guruId)->where('mapel_id', $mapelId)->where('tingkat', $tingkat);
        if ($excludeId !== null) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->first() !== null;
    }

    /**
     * Dipakai sebelum menghapus: cegah hapus pengampu yang masih dipakai
     * jadwal aktif, supaya jadwal itu tidak kehilangan rujukannya.
     */
    public function dipakaiJadwal(int $id): bool
    {
        return (new JadwalModel())->where('guru_pengampu_id', $id)->countAllResults() > 0;
    }
}
