<?php

namespace App\Libraries;

use App\Models\AuditLogModel;
use Config\Services;

/**
 * Pencatat audit log terpusat.
 *
 * Dipakai di semua modul yang butuh mencatat aktivitas penting
 * (login, logout, ubah presensi, ubah jurnal, tambah jadwal, hapus data, dst).
 *
 * Contoh pemakaian di controller lain nanti:
 *   (new AuditLogger())->log('ubah_presensi', 'Mengubah presensi jadwal #12 tanggal 2026-07-27');
 */
class AuditLogger
{
    protected AuditLogModel $model;

    public function __construct()
    {
        $this->model = new AuditLogModel();
    }

    public function log(string $aktivitas, ?string $keterangan = null): void
    {
        $this->model->insert([
            'user_id'    => session()->get('user_id'),
            'aktivitas'  => $aktivitas,
            'keterangan' => $keterangan,
            'ip_address' => Services::request()->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
