<?php

namespace App\Models;

use CodeIgniter\Model;

class PresensiDetailModel extends Model
{
    protected $table         = 'presensi_detail';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['presensi_id', 'siswa_id', 'status', 'catatan'];

    public const STATUS_VALID = ['hadir', 'sakit', 'izin', 'alpha', 'terlambat'];

    public function getByPresensiId(int $presensiId): array
    {
        return $this->select('presensi_detail.*, siswa.nama, siswa.nis')
            ->join('siswa', 'siswa.id = presensi_detail.siswa_id')
            ->where('presensi_id', $presensiId)
            ->orderBy('siswa.nama', 'ASC')
            ->findAll();
    }

    /**
     * Ringkasan jumlah siswa per status, dipakai di halaman jurnal & riwayat.
     */
    public function rekapStatus(int $presensiId): array
    {
        $rows = $this->select('status, COUNT(*) as jumlah')
            ->where('presensi_id', $presensiId)
            ->groupBy('status')
            ->findAll();

        $rekap = array_fill_keys(self::STATUS_VALID, 0);
        foreach ($rows as $r) {
            $rekap[$r['status']] = (int) $r['jumlah'];
        }

        return $rekap;
    }
}
