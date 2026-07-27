<?php

namespace App\Controllers;

use App\Libraries\AuditLogger;
use App\Models\GuruModel;
use App\Models\JadwalModel;
use App\Models\SemesterModel;
use App\Models\TukarJadwalModel;

/**
 * Tukar jadwal = pengajuan guru pengganti untuk SATU sesi tertentu saja
 * (satu jadwal_id + satu tanggal spesifik). Tabel `jadwal` (template mingguan)
 * TIDAK PERNAH diubah oleh fitur ini — begitu minggu berganti, jadwal otomatis
 * kembali ke guru aslinya karena pengajuan ini memang cuma berlaku untuk
 * tanggal yang diajukan.
 */
class TukarJadwal extends BaseController
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

        $tukarModel = new TukarJadwalModel();

        $data = [
            'title'   => 'Tukar Jadwal',
            'content' => view('tukar_jadwal/index', [
                'guru'          => $guru,
                'aktif'         => $aktif,
                'jadwalSaya'    => $jadwalSaya,
                'guruLain'      => (new GuruModel())->where('status', 'aktif')->where('id !=', $guru['id'])->orderBy('nama', 'ASC')->findAll(),
                'menunggu'      => $tukarModel->getMenungguUntukGuru((int) $guru['id']),
                'riwayat'       => $tukarModel->getRiwayatUntukGuru((int) $guru['id']),
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

        $jadwalId         = (int) $this->request->getPost('jadwal_id');
        $tanggal          = (string) $this->request->getPost('tanggal');
        $guruPenggantiId  = (int) $this->request->getPost('guru_pengganti_id');
        $alasan           = $this->request->getPost('alasan');

        $jadwal = (new JadwalModel())->getJadwalMilikGuru($jadwalId, (int) $guru['id']);
        if (! $jadwal) {
            return redirect()->to('/tukar-jadwal')->with('error', 'Jadwal tidak ditemukan atau bukan milik Anda.');
        }

        if ($guruPenggantiId === (int) $guru['id']) {
            return redirect()->to('/tukar-jadwal')->with('error', 'Tidak bisa mengajukan tukar jadwal ke diri sendiri.');
        }

        $guruPengganti = (new GuruModel())->find($guruPenggantiId);
        if (! $guruPengganti) {
            return redirect()->to('/tukar-jadwal')->with('error', 'Guru pengganti tidak ditemukan.');
        }

        // Tanggal yang diajukan wajib jatuh pada hari yang sama dengan jadwal aslinya
        // (jadwal Senin ya cuma bisa ditukar untuk tanggal yang jatuhnya hari Senin).
        $hariMap  = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        $hariTgl  = $hariMap[(int) date('N', strtotime($tanggal))] ?? '';
        if ($hariTgl !== $jadwal['hari']) {
            return redirect()->to('/tukar-jadwal')->with('error', "Tanggal yang dipilih jatuh pada hari {$hariTgl}, padahal jadwal ini adalah hari {$jadwal['hari']}. Pilih tanggal yang sesuai.");
        }

        if (strtotime($tanggal) < strtotime(date('Y-m-d'))) {
            return redirect()->to('/tukar-jadwal')->with('error', 'Tidak bisa mengajukan untuk tanggal yang sudah lewat.');
        }

        $tukarModel = new TukarJadwalModel();
        if ($tukarModel->adaPengajuanAktif($jadwalId, $tanggal)) {
            return redirect()->to('/tukar-jadwal')->with('error', 'Sudah ada pengajuan aktif (menunggu/disetujui) untuk sesi ini. Batalkan dulu kalau ingin mengajukan ulang.');
        }

        $ok = $tukarModel->insert([
            'jadwal_id'         => $jadwalId,
            'tanggal'           => $tanggal,
            'guru_asal_id'      => (int) $guru['id'],
            'guru_pengganti_id' => $guruPenggantiId,
            'alasan'            => $alasan ?: null,
            'status'            => 'menunggu',
        ]);

        if (! $ok) {
            return redirect()->to('/tukar-jadwal')->with('error', implode(' ', $tukarModel->errors()));
        }

        (new AuditLogger())->log('ajukan_tukar_jadwal', "Mengajukan tukar jadwal #{$jadwalId} tanggal {$tanggal} ke {$guruPengganti['nama']}");

        return redirect()->to('/tukar-jadwal')->with('message', 'Pengajuan tukar jadwal berhasil dikirim, menunggu persetujuan ' . $guruPengganti['nama'] . '.');
    }

    public function setuju($id)
    {
        return $this->respon((int) $id, 'disetujui');
    }

    public function tolak($id)
    {
        return $this->respon((int) $id, 'ditolak');
    }

    private function respon(int $id, string $status)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $tukarModel = new TukarJadwalModel();
        $pengajuan  = $tukarModel->find($id);

        if (! $pengajuan || (int) $pengajuan['guru_pengganti_id'] !== (int) $guru['id']) {
            return redirect()->to('/tukar-jadwal')->with('error', 'Pengajuan tidak ditemukan atau bukan untuk Anda.');
        }

        if ($pengajuan['status'] !== 'menunggu') {
            return redirect()->to('/tukar-jadwal')->with('error', 'Pengajuan ini sudah direspon sebelumnya.');
        }

        $tukarModel->update($id, [
            'status'         => $status,
            'catatan_respon' => $this->request->getPost('catatan_respon') ?: null,
        ]);

        (new AuditLogger())->log('respon_tukar_jadwal', "Merespon ({$status}) pengajuan tukar jadwal #{$id}");

        return redirect()->to('/tukar-jadwal')->with('message', $status === 'disetujui' ? 'Pengajuan disetujui.' : 'Pengajuan ditolak.');
    }

    public function batal($id)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $tukarModel = new TukarJadwalModel();
        $pengajuan  = $tukarModel->find((int) $id);

        if (! $pengajuan || (int) $pengajuan['guru_asal_id'] !== (int) $guru['id']) {
            return redirect()->to('/tukar-jadwal')->with('error', 'Pengajuan tidak ditemukan atau bukan milik Anda.');
        }

        if ($pengajuan['status'] !== 'menunggu') {
            return redirect()->to('/tukar-jadwal')->with('error', 'Hanya pengajuan yang masih menunggu yang bisa dibatalkan.');
        }

        $tukarModel->update((int) $id, ['status' => 'dibatalkan']);

        (new AuditLogger())->log('batal_tukar_jadwal', 'Membatalkan pengajuan tukar jadwal #' . $id);

        return redirect()->to('/tukar-jadwal')->with('message', 'Pengajuan dibatalkan.');
    }
}
