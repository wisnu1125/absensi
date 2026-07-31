<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Tujuan Pembelajaran (TP) — FONDASI untuk fitur Master TP yang akan
 * dikelola guru sendiri per Guru Pengampu (mapel+tingkat yang diampu).
 * Model ini sudah lengkap & siap dipakai; UI Master TP di Dashboard Guru
 * dan dropdown TP di Jurnal Mengajar menyusul di tahap berikutnya.
 */
class TujuanPembelajaranModel extends Model
{
    protected $table          = 'tujuan_pembelajaran';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = ['guru_pengampu_id', 'teks', 'urutan'];

    protected $validationRules = [
        'id'               => 'permit_empty|is_natural_no_zero',
        'guru_pengampu_id' => 'required|is_natural_no_zero',
        'teks'             => 'required|max_length[500]',
    ];
    protected $validationMessages = [
        'teks' => ['required' => 'Teks Tujuan Pembelajaran wajib diisi.'],
    ];

    /**
     * Daftar TP milik satu Guru Pengampu, diurutkan sesuai `urutan` lalu id
     * — dipakai baik utk halaman kelola Master TP maupun dropdown di Jurnal.
     */
    public function getUntukPengampu(int $guruPengampuId): array
    {
        return $this->where('guru_pengampu_id', $guruPengampuId)
            ->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Nomor urut berikutnya utk TP baru pada Guru Pengampu tsb (TP baru
     * otomatis ditaruh di akhir daftar).
     */
    public function urutanBerikutnya(int $guruPengampuId): int
    {
        $terakhir = $this->where('guru_pengampu_id', $guruPengampuId)->orderBy('urutan', 'DESC')->first();

        return $terakhir ? ((int) $terakhir['urutan'] + 1) : 1;
    }
}
