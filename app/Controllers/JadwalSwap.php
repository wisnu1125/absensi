<?php

namespace App\Controllers;

use App\Libraries\AuditLogger;
use App\Models\GuruModel;
use App\Models\JadwalModel;
use App\Models\JadwalSwapModel;
use App\Models\SemesterModel;

/**
 * Pertukaran Jadwal = pertukaran SLOT PENUH (hari+jam) antara 2 guru untuk
 * satu rentang tanggal — BUKAN guru pengganti (lihat TukarJadwal.php untuk
 * itu). Jadwal master tidak pernah diubah; lihat ScheduleResolverService
 * untuk cara "jadwal efektif" dihitung.
 *
 * Alur: guru A mengajukan -> guru B (pemilik jadwal tujuan) meng-ACC secara
 * personal -> Admin/Waka Kurikulum memberi persetujuan akhir -> baru status
 * jadi "disetujui" dan pertukaran aktif berlaku.
 */
class JadwalSwap extends BaseController
{
    private function guruSaatIni(): ?array
    {
        return (new GuruModel())->findByUserId((int) session()->get('user_id'));
    }

    public function index()
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $aktif = (new SemesterModel())->getActive();

        $jadwalSaya = $aktif
            ? (new JadwalModel())->select('jadwal.*, mata_pelajaran.nama as nama_mapel, kelas.nama_kelas')
                ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
                ->join('kelas', 'kelas.id = jadwal.kelas_id')
                ->where('jadwal.guru_id', (int) $guru['id'])
                ->where('jadwal.semester_id', (int) $aktif['id'])
                ->findAll()
            : [];

        // Jadwal SEMUA guru lain (calon pasangan tukar) — dikelompokkan per JP
        // (jumlah jam pelajaran) supaya di form gampang cari yang JP-nya sama.
        $jadwalLain = $aktif
            ? (new JadwalModel())->select('jadwal.*, mata_pelajaran.nama as nama_mapel, kelas.nama_kelas, guru.nama as nama_guru')
                ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
                ->join('kelas', 'kelas.id = jadwal.kelas_id')
                ->join('guru', 'guru.id = jadwal.guru_id')
                ->where('jadwal.guru_id !=', (int) $guru['id'])
                ->where('jadwal.semester_id', (int) $aktif['id'])
                ->orderBy('guru.nama', 'ASC')
                ->findAll()
            : [];

        $swapModel = new JadwalSwapModel();

        $data = [
            'title'   => 'Tukar Jadwal',
            'content' => view('jadwal_swap/index', [
                'guru'        => $guru,
                'aktif'       => $aktif,
                'jadwalSaya'  => $jadwalSaya,
                'jadwalLain'  => $jadwalLain,
                'menunggu'    => $swapModel->getMenungguGuru((int) $guru['id']),
                'riwayat'     => $swapModel->getRiwayatGuru((int) $guru['id']),
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function ajukan()
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $jadwalAsalId   = (int) $this->request->getPost('jadwal_asal_id');
        $jadwalTujuanId = (int) $this->request->getPost('jadwal_tujuan_id');
        $tanggalMulai   = (string) $this->request->getPost('tanggal_mulai');
        $tanggalSelesai = (string) $this->request->getPost('tanggal_selesai');
        $alasan         = $this->request->getPost('alasan');

        $jadwalModel = new JadwalModel();
        $jadwalAsal  = $jadwalModel->getJadwalMilikGuru($jadwalAsalId, (int) $guru['id']);

        if (! $jadwalAsal) {
            return redirect()->to('/jadwal-swap')->with('error', 'Jadwal asal tidak ditemukan atau bukan milik Anda.');
        }

        $jadwalTujuan = $jadwalModel->find($jadwalTujuanId);
        if (! $jadwalTujuan) {
            return redirect()->to('/jadwal-swap')->with('error', 'Jadwal tujuan tidak ditemukan.');
        }

        if ((int) $jadwalTujuan['guru_id'] === (int) $guru['id']) {
            return redirect()->to('/jadwal-swap')->with('error', 'Tidak bisa menukar jadwal dengan diri sendiri.');
        }

        // Aturan bisnis: jumlah JP kedua slot harus sama, supaya beban mengajar tetap seimbang.
        $jpAsal   = (int) $jadwalAsal['jam_ke_selesai'] - (int) $jadwalAsal['jam_ke_mulai'] + 1;
        $jpTujuan = (int) $jadwalTujuan['jam_ke_selesai'] - (int) $jadwalTujuan['jam_ke_mulai'] + 1;

        if ($jpAsal !== $jpTujuan) {
            return redirect()->to('/jadwal-swap')->with('error', "Jumlah JP tidak sama ({$jpAsal} JP vs {$jpTujuan} JP). Pertukaran hanya boleh antar slot dengan JP yang sama.");
        }

        if (! $tanggalMulai || ! $tanggalSelesai || strtotime($tanggalSelesai) < strtotime($tanggalMulai)) {
            return redirect()->to('/jadwal-swap')->with('error', 'Rentang tanggal tidak valid.');
        }

        $swapModel = new JadwalSwapModel();
        if ($swapModel->adaTumpangTindih($jadwalAsalId, $jadwalTujuanId, $tanggalMulai, $tanggalSelesai)) {
            return redirect()->to('/jadwal-swap')->with('error', 'Salah satu jadwal ini sudah punya pengajuan pertukaran aktif yang rentang tanggalnya tumpang tindih.');
        }

        $ok = $swapModel->insert([
            'jadwal_asal_id'    => $jadwalAsalId,
            'jadwal_tujuan_id'  => $jadwalTujuanId,
            'guru_pengaju_id'   => (int) $guru['id'],
            'guru_penyetuju_id' => (int) $jadwalTujuan['guru_id'],
            'tanggal_mulai'     => $tanggalMulai,
            'tanggal_selesai'   => $tanggalSelesai,
            'alasan'            => $alasan ?: null,
            'status'            => 'pending',
            'guru_setuju'       => 0,
        ]);

        if (! $ok) {
            return redirect()->to('/jadwal-swap')->with('error', implode(' ', $swapModel->errors()));
        }

        (new AuditLogger())->log('ajukan_pertukaran_jadwal', "Mengajukan tukar jadwal #{$jadwalAsalId} <-> #{$jadwalTujuanId}, {$tanggalMulai} s/d {$tanggalSelesai}");

        return redirect()->to('/jadwal-swap')->with('message', 'Pengajuan tukar jadwal terkirim, menunggu persetujuan guru tujuan.');
    }

    /**
     * Respon TAHAP 1 dari guru_penyetuju_id (pemilik jadwal tujuan) — ACC di
     * sini belum langsung membuat pertukaran aktif, masih perlu persetujuan
     * admin/Waka Kurikulum (tahap 2, lihat JadwalSwapAdmin::setuju()).
     */
    public function setujuGuru($id)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $swapModel = new JadwalSwapModel();
        $pengajuan = $swapModel->find((int) $id);

        if (! $pengajuan || (int) $pengajuan['guru_penyetuju_id'] !== (int) $guru['id']) {
            return redirect()->to('/jadwal-swap')->with('error', 'Pengajuan tidak ditemukan atau bukan untuk Anda.');
        }

        if ($pengajuan['status'] !== 'pending' || $pengajuan['guru_setuju']) {
            return redirect()->to('/jadwal-swap')->with('error', 'Pengajuan ini sudah direspon sebelumnya.');
        }

        $swapModel->update((int) $id, [
            'guru_setuju'  => 1,
            'catatan_guru' => $this->request->getPost('catatan_guru') ?: null,
        ]);

        (new AuditLogger())->log('setuju_guru_pertukaran_jadwal', "Menyetujui (tahap guru) tukar jadwal #{$id}");

        return redirect()->to('/jadwal-swap')->with('message', 'Persetujuan Anda tercatat. Menunggu persetujuan akhir dari Admin/Waka Kurikulum.');
    }

    public function tolakGuru($id)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $swapModel = new JadwalSwapModel();
        $pengajuan = $swapModel->find((int) $id);

        if (! $pengajuan || (int) $pengajuan['guru_penyetuju_id'] !== (int) $guru['id']) {
            return redirect()->to('/jadwal-swap')->with('error', 'Pengajuan tidak ditemukan atau bukan untuk Anda.');
        }

        if ($pengajuan['status'] !== 'pending') {
            return redirect()->to('/jadwal-swap')->with('error', 'Pengajuan ini sudah direspon sebelumnya.');
        }

        $swapModel->update((int) $id, [
            'status'       => 'ditolak',
            'catatan_guru' => $this->request->getPost('catatan_guru') ?: null,
        ]);

        (new AuditLogger())->log('tolak_guru_pertukaran_jadwal', "Menolak tukar jadwal #{$id}");

        return redirect()->to('/jadwal-swap')->with('message', 'Pengajuan ditolak.');
    }

    public function batal($id)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $swapModel = new JadwalSwapModel();
        $pengajuan = $swapModel->find((int) $id);

        if (! $pengajuan || (int) $pengajuan['guru_pengaju_id'] !== (int) $guru['id']) {
            return redirect()->to('/jadwal-swap')->with('error', 'Pengajuan tidak ditemukan atau bukan milik Anda.');
        }

        if ($pengajuan['status'] !== 'pending') {
            return redirect()->to('/jadwal-swap')->with('error', 'Hanya pengajuan yang masih pending yang bisa dibatalkan.');
        }

        $swapModel->update((int) $id, ['status' => 'dibatalkan']);

        (new AuditLogger())->log('batal_pertukaran_jadwal', 'Membatalkan tukar jadwal #' . $id);

        return redirect()->to('/jadwal-swap')->with('message', 'Pengajuan dibatalkan.');
    }
}
