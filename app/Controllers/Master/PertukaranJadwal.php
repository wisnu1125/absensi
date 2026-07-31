<?php

namespace App\Controllers\Master;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\JadwalSwapModel;

/**
 * Persetujuan TAHAP 2 (akhir) untuk Pertukaran Jadwal, oleh Admin/Waka
 * Kurikulum — sesuai aturan bisnis "Harus mendapatkan persetujuan Waka
 * Kurikulum/Admin" selain persetujuan guru yang dituju (tahap 1, lihat
 * JadwalSwap::setujuGuru()). Status baru jadi "disetujui" (dan pertukaran
 * aktif berlaku) setelah KEDUA tahap ini selesai.
 */
class PertukaranJadwal extends BaseController
{
    public function index()
    {
        $swapModel = new JadwalSwapModel();

        $filter = ['status' => $this->request->getGet('status') ?: ''];

        $data = [
            'title'   => 'Tukar Jadwal',
            'content' => view('master/pertukaran_jadwal/index', [
                'menunggu' => $swapModel->getMenungguAdmin(),
                'semua'    => $swapModel->getSemua($filter),
                'filter'   => $filter,
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function setuju($id)
    {
        $swapModel = new JadwalSwapModel();
        $pengajuan = $swapModel->find((int) $id);

        if (! $pengajuan) {
            return redirect()->to('/master/pertukaran-jadwal')->with('error', 'Pengajuan tidak ditemukan.');
        }

        if ($pengajuan['status'] !== 'pending' || ! $pengajuan['guru_setuju']) {
            return redirect()->to('/master/pertukaran-jadwal')->with('error', 'Pengajuan ini belum disetujui guru tujuan, atau sudah diproses sebelumnya.');
        }

        $swapModel->update((int) $id, [
            'status'        => 'disetujui',
            'approved_by'   => (int) session()->get('user_id'),
            'approved_at'   => date('Y-m-d H:i:s'),
            'catatan_admin' => $this->request->getPost('catatan_admin') ?: null,
        ]);

        (new AuditLogger())->log('setuju_admin_pertukaran_jadwal', "Menyetujui (final) tukar jadwal #{$id}");

        return redirect()->to('/master/pertukaran-jadwal')->with('message', 'Tukar jadwal disetujui dan sekarang aktif untuk rentang tanggal yang diajukan.');
    }

    public function tolak($id)
    {
        $swapModel = new JadwalSwapModel();
        $pengajuan = $swapModel->find((int) $id);

        if (! $pengajuan) {
            return redirect()->to('/master/pertukaran-jadwal')->with('error', 'Pengajuan tidak ditemukan.');
        }

        if ($pengajuan['status'] !== 'pending') {
            return redirect()->to('/master/pertukaran-jadwal')->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $swapModel->update((int) $id, [
            'status'        => 'ditolak',
            'approved_by'   => (int) session()->get('user_id'),
            'approved_at'   => date('Y-m-d H:i:s'),
            'catatan_admin' => $this->request->getPost('catatan_admin') ?: null,
        ]);

        (new AuditLogger())->log('tolak_admin_pertukaran_jadwal', "Menolak (final) tukar jadwal #{$id}");

        return redirect()->to('/master/pertukaran-jadwal')->with('message', 'Pengajuan ditolak.');
    }

    /**
     * Admin/Waka Kurikulum membatalkan pengajuan tukar jadwal MILIK GURU
     * MANAPUN — override, tidak terikat siapa pengajunya atau sudah sampai
     * tahap mana. Bisa dipakai untuk status 'pending' (batalkan sebelum
     * diproses) MAUPUN 'disetujui' (batalkan pertukaran yang sudah aktif —
     * begitu statusnya berubah, ScheduleResolverService otomatis berhenti
     * menerapkannya karena resolvernya hanya mencari status 'disetujui').
     */
    public function batalkan($id)
    {
        $swapModel = new JadwalSwapModel();
        $pengajuan = $swapModel->find((int) $id);

        if (! $pengajuan) {
            return redirect()->to('/master/pertukaran-jadwal')->with('error', 'Pengajuan tidak ditemukan.');
        }

        if (! in_array($pengajuan['status'], ['pending', 'disetujui'], true)) {
            return redirect()->to('/master/pertukaran-jadwal')->with('error', 'Pengajuan ini sudah tidak aktif (status: ' . $pengajuan['status'] . ').');
        }

        $admin = current_user();
        $swapModel->update((int) $id, [
            'status'        => 'dibatalkan',
            'catatan_admin' => trim(($pengajuan['catatan_admin'] ?? '') . ' | Dibatalkan Admin/Operator (' . ($admin['full_name'] ?? '-') . ')', ' |'),
        ]);

        (new AuditLogger())->log('admin_batalkan_pertukaran_jadwal', "Admin membatalkan tukar jadwal #{$id} (sebelumnya berstatus {$pengajuan['status']})");

        return redirect()->to('/master/pertukaran-jadwal')->with('message', 'Pertukaran jadwal berhasil dibatalkan.');
    }
}
