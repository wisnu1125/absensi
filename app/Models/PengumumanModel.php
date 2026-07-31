<?php

namespace App\Models;

use CodeIgniter\Model;

class PengumumanModel extends Model
{
    protected $table          = 'pengumuman';
    protected $primaryKey     = 'id';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = ['judul', 'isi', 'tanggal_mulai', 'tanggal_selesai', 'dibuat_oleh'];

    /**
     * Semua pengumuman, terbaru dulu — dipakai halaman kelola (admin).
     */
    public function getSemua(): array
    {
        return $this->orderBy('tanggal_mulai', 'DESC')->orderBy('id', 'DESC')->findAll();
    }

    /**
     * Pengumuman yang SEDANG AKTIF tampil hari ini (tanggal_mulai <= hari ini,
     * dan tanggal_selesai kosong ATAU >= hari ini) — dipakai widget Dashboard.
     */
    public function getAktif(int $limit = 3): array
    {
        $today = date('Y-m-d');

        return $this->where('tanggal_mulai <=', $today)
            ->groupStart()
                ->where('tanggal_selesai IS NULL')
                ->orWhere('tanggal_selesai >=', $today)
            ->groupEnd()
            ->orderBy('tanggal_mulai', 'DESC')
            ->findAll($limit);
    }
}
