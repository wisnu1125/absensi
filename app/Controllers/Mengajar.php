<?php

namespace App\Controllers;

use App\Libraries\AuditLogger;
use App\Models\GuruModel;
use App\Models\JadwalModel;
use App\Models\JurnalMengajarModel;
use App\Models\PresensiDetailModel;
use App\Models\PresensiModel;
use App\Models\SiswaModel;

/**
 * Alur inti aplikasi: Mulai Mengajar -> Presensi -> Jurnal -> Selesai.
 * Semua method di sini HANYA beroperasi pada jadwal milik guru yang sedang
 * login, dan HANYA untuk tanggal hari ini — guru tidak pernah diminta
 * memilih kelas/mapel/jam karena semua sudah ditentukan oleh jadwal_id.
 */
class Mengajar extends BaseController
{
    private function guruSaatIni(): ?array
    {
        return (new GuruModel())->findByUserId((int) session()->get('user_id'));
    }

    public function presensi($jadwalId)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum dihubungkan ke data guru. Hubungi administrator.');
        }

        $jadwal = (new JadwalModel())->getJadwalMilikGuru((int) $jadwalId, (int) $guru['id']);
        if (! $jadwal) {
            return redirect()->to('/dashboard')->with('error', 'Jadwal tidak ditemukan atau bukan milik Anda.');
        }

        $tanggal       = date('Y-m-d');
        $presensiModel = new PresensiModel();
        $detailModel   = new PresensiDetailModel();

        $presensi = $presensiModel->findByJadwalTanggal((int) $jadwal['id'], $tanggal);

        if ($presensi && $presensi['locked_at']) {
            return redirect()->to('/dashboard')->with('error', 'Presensi sesi ini sudah terkunci (sudah selesai).');
        }

        $existing = [];
        if ($presensi) {
            foreach ($detailModel->getByPresensiId((int) $presensi['id']) as $d) {
                $existing[(int) $d['siswa_id']] = $d;
            }
        }

        $data = [
            'title'   => 'Presensi — ' . $jadwal['nama_mapel'],
            'content' => view('mengajar/presensi', [
                'jadwal'   => $jadwal,
                'tanggal'  => $tanggal,
                'siswa'    => (new SiswaModel())->getWithKelas((int) $jadwal['kelas_id']),
                'existing' => $existing,
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function simpanPresensi($jadwalId)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $jadwal = (new JadwalModel())->getJadwalMilikGuru((int) $jadwalId, (int) $guru['id']);
        if (! $jadwal) {
            return redirect()->to('/dashboard')->with('error', 'Jadwal tidak ditemukan atau bukan milik Anda.');
        }

        $tanggal       = date('Y-m-d');
        $presensiModel = new PresensiModel();
        $detailModel   = new PresensiDetailModel();

        $presensi = $presensiModel->findByJadwalTanggal((int) $jadwal['id'], $tanggal);

        if ($presensi && $presensi['locked_at']) {
            return redirect()->to('/dashboard')->with('error', 'Presensi sesi ini sudah terkunci.');
        }

        if ($presensi) {
            $presensiId = (int) $presensi['id'];
        } else {
            $presensiId = $presensiModel->insert([
                'jadwal_id'  => (int) $jadwal['id'],
                'tanggal'    => $tanggal,
                'created_by' => session()->get('user_id'),
            ]);

            if (! $presensiId) {
                return redirect()->back()->with('error', 'Gagal menyimpan presensi. Coba lagi.');
            }
        }

        $statusPost  = $this->request->getPost('status') ?? [];
        $catatanPost = $this->request->getPost('catatan') ?? [];

        foreach ($statusPost as $siswaId => $status) {
            $siswaId = (int) $siswaId;

            if (! in_array($status, PresensiDetailModel::STATUS_VALID, true)) {
                continue;
            }

            $payload = [
                'presensi_id' => $presensiId,
                'siswa_id'    => $siswaId,
                'status'      => $status,
                'catatan'     => trim((string) ($catatanPost[$siswaId] ?? '')) ?: null,
            ];

            $existingRow = $detailModel->where('presensi_id', $presensiId)->where('siswa_id', $siswaId)->first();

            if ($existingRow) {
                $detailModel->update($existingRow['id'], $payload);
            } else {
                $detailModel->insert($payload);
            }
        }

        (new AuditLogger())->log('isi_presensi', "Mengisi presensi jadwal #{$jadwal['id']} ({$jadwal['nama_mapel']}/{$jadwal['nama_kelas']}) tanggal {$tanggal}");

        return redirect()->to('/mengajar/jurnal/' . $jadwal['id'])
            ->with('message', 'Presensi tersimpan. Lanjutkan mengisi jurnal mengajar.');
    }

    public function jurnal($jadwalId)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $jadwal = (new JadwalModel())->getJadwalMilikGuru((int) $jadwalId, (int) $guru['id']);
        if (! $jadwal) {
            return redirect()->to('/dashboard')->with('error', 'Jadwal tidak ditemukan atau bukan milik Anda.');
        }

        $tanggal = date('Y-m-d');

        if (! (new PresensiModel())->findByJadwalTanggal((int) $jadwal['id'], $tanggal)) {
            return redirect()->to('/mengajar/presensi/' . $jadwal['id'])->with('error', 'Isi presensi terlebih dahulu sebelum mengisi jurnal.');
        }

        $jurnalModel = new JurnalMengajarModel();
        $jurnal      = $jurnalModel->findByJadwalTanggal((int) $jadwal['id'], $tanggal);

        if ($jurnal && $jurnal['locked_at']) {
            return redirect()->to('/dashboard')->with('error', 'Jurnal sesi ini sudah terkunci (sudah selesai).');
        }

        $data = [
            'title'   => 'Jurnal Mengajar — ' . $jadwal['nama_mapel'],
            'content' => view('mengajar/jurnal', [
                'jadwal'  => $jadwal,
                'tanggal' => $tanggal,
                'jurnal'  => $jurnal,
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function simpanJurnal($jadwalId)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $jadwal = (new JadwalModel())->getJadwalMilikGuru((int) $jadwalId, (int) $guru['id']);
        if (! $jadwal) {
            return redirect()->to('/dashboard')->with('error', 'Jadwal tidak ditemukan atau bukan milik Anda.');
        }

        $tanggal       = date('Y-m-d');
        $jurnalModel   = new JurnalMengajarModel();
        $presensiModel = new PresensiModel();

        $existing = $jurnalModel->findByJadwalTanggal((int) $jadwal['id'], $tanggal);
        if ($existing && $existing['locked_at']) {
            return redirect()->to('/dashboard')->with('error', 'Jurnal sesi ini sudah terkunci.');
        }

        $payload = [
            'jadwal_id'             => (int) $jadwal['id'],
            'tanggal'               => $tanggal,
            'materi'                => $this->request->getPost('materi'),
            'tujuan_pembelajaran'   => $this->request->getPost('tujuan_pembelajaran'),
            'metode'                => $this->request->getPost('metode'),
            'media'                 => $this->request->getPost('media'),
            'kegiatan_pembelajaran' => $this->request->getPost('kegiatan_pembelajaran'),
            'catatan'               => $this->request->getPost('catatan'),
            'kendala'               => $this->request->getPost('kendala'),
            'tindak_lanjut'         => $this->request->getPost('tindak_lanjut'),
            'created_by'            => session()->get('user_id'),
            // Sesi dianggap selesai begitu jurnal disimpan -> langsung dikunci,
            // sesuai "Penguncian jurnal setelah batas waktu" (batasnya: sesi itu sendiri berakhir).
            'locked_at'             => date('Y-m-d H:i:s'),
        ];

        $ok = $existing ? $jurnalModel->update($existing['id'], $payload) : $jurnalModel->insert($payload);

        if (! $ok) {
            return redirect()->back()->withInput()->with('error', implode(' ', $jurnalModel->errors()));
        }

        // Kunci presensi juga, karena sesi mengajarnya sudah resmi "Selesai".
        $presensi = $presensiModel->findByJadwalTanggal((int) $jadwal['id'], $tanggal);
        if ($presensi && ! $presensi['locked_at']) {
            $presensiModel->update($presensi['id'], ['locked_at' => date('Y-m-d H:i:s')]);
        }

        (new AuditLogger())->log('isi_jurnal', "Mengisi jurnal jadwal #{$jadwal['id']} ({$jadwal['nama_mapel']}/{$jadwal['nama_kelas']}) tanggal {$tanggal}");

        return redirect()->to('/dashboard')->with('message', 'Jurnal tersimpan. Sesi mengajar hari ini selesai — terima kasih!');
    }
}
