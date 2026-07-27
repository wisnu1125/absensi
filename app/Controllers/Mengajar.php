<?php

namespace App\Controllers;

use App\Libraries\AuditLogger;
use App\Models\GuruModel;
use App\Models\JadwalModel;
use App\Models\JamPelajaranModel;
use App\Models\JurnalMengajarModel;
use App\Models\PresensiDetailModel;
use App\Models\PresensiModel;
use App\Models\SemesterModel;
use App\Models\SiswaModel;
use App\Models\TukarJadwalModel;

/**
 * Alur inti aplikasi: Mulai Mengajar -> Presensi -> Jurnal -> Selesai.
 * Semua method di sini HANYA beroperasi pada jadwal milik guru yang sedang
 * login, dan HANYA untuk tanggal hari ini — guru tidak pernah diminta
 * memilih kelas/mapel/jam karena semua sudah ditentukan oleh jadwal_id.
 *
 * Kepemilikan sesi juga bisa berpindah SEMENTARA lewat fitur Tukar Jadwal:
 * kalau ada pengajuan yang DISETUJUI untuk jadwal+tanggal hari ini, guru
 * pengganti yang disetujui itu juga berhak mengisi presensi/jurnal-nya,
 * tanpa tabel jadwal itu sendiri pernah diubah.
 */
class Mengajar extends BaseController
{
    private function guruSaatIni(): ?array
    {
        return (new GuruModel())->findByUserId((int) session()->get('user_id'));
    }

    /**
     * Cari jadwal yang boleh diisi guru ini UNTUK TANGGAL TERTENTU: entah karena
     * dia pemilik asli jadwal itu, ATAU karena ada pengajuan tukar jadwal yang
     * sudah disetujui untuk jadwal+tanggal ini dengan dia sebagai pengganti.
     */
    private function ambilJadwalUntukSesi(int $jadwalId, int $guruId, string $tanggal): ?array
    {
        $jadwalModel = new JadwalModel();

        $jadwal = $jadwalModel->getJadwalMilikGuru($jadwalId, $guruId);
        if ($jadwal) {
            return $jadwal;
        }

        $swap = (new TukarJadwalModel())->getDisetujui($jadwalId, $tanggal);
        if ($swap && (int) $swap['guru_pengganti_id'] === $guruId) {
            return $jadwalModel->select('jadwal.*, mata_pelajaran.nama as nama_mapel, kelas.nama_kelas')
                ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
                ->join('kelas', 'kelas.id = jadwal.kelas_id')
                ->where('jadwal.id', $jadwalId)
                ->first();
        }

        return null;
    }

    public function presensi($jadwalId)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum dihubungkan ke data guru. Hubungi administrator.');
        }

        $tanggal = date('Y-m-d');
        $jadwal  = $this->ambilJadwalUntukSesi((int) $jadwalId, (int) $guru['id'], $tanggal);
        if (! $jadwal) {
            return redirect()->to('/dashboard')->with('error', 'Jadwal tidak ditemukan atau bukan milik Anda.');
        }

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

        $tanggal = date('Y-m-d');
        $jadwal  = $this->ambilJadwalUntukSesi((int) $jadwalId, (int) $guru['id'], $tanggal);
        if (! $jadwal) {
            return redirect()->to('/dashboard')->with('error', 'Jadwal tidak ditemukan atau bukan milik Anda.');
        }

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

        $tanggal = date('Y-m-d');
        $jadwal  = $this->ambilJadwalUntukSesi((int) $jadwalId, (int) $guru['id'], $tanggal);
        if (! $jadwal) {
            return redirect()->to('/dashboard')->with('error', 'Jadwal tidak ditemukan atau bukan milik Anda.');
        }

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

        $tanggal = date('Y-m-d');
        $jadwal  = $this->ambilJadwalUntukSesi((int) $jadwalId, (int) $guru['id'], $tanggal);
        if (! $jadwal) {
            return redirect()->to('/dashboard')->with('error', 'Jadwal tidak ditemukan atau bukan milik Anda.');
        }

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

    /**
     * Riwayat sesi mengajar milik guru yang login: semua presensi yang pernah
     * diisi (bukan cuma hari ini), lengkap dengan materi jurnal & rekap kehadiran.
     */
    public function riwayat()
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $filter = [
            'tanggal_dari'   => $this->request->getGet('tanggal_dari') ?: date('Y-m-01'),
            'tanggal_sampai' => $this->request->getGet('tanggal_sampai') ?: date('Y-m-d'),
        ];

        $rows = (new PresensiModel())
            ->select('presensi.id as presensi_id, presensi.tanggal, jadwal.hari, jadwal.jam_mulai, jadwal.jam_selesai, kelas.nama_kelas, mata_pelajaran.nama as nama_mapel, jurnal_mengajar.materi')
            ->join('jadwal', 'jadwal.id = presensi.jadwal_id')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('jurnal_mengajar', 'jurnal_mengajar.jadwal_id = presensi.jadwal_id AND jurnal_mengajar.tanggal = presensi.tanggal', 'left')
            ->where('jadwal.guru_id', (int) $guru['id'])
            ->where('presensi.tanggal >=', $filter['tanggal_dari'])
            ->where('presensi.tanggal <=', $filter['tanggal_sampai'])
            ->orderBy('presensi.tanggal', 'DESC')
            ->orderBy('presensi.id', 'DESC')
            ->findAll();

        $detailModel = new PresensiDetailModel();
        foreach ($rows as &$r) {
            $r['rekap'] = $detailModel->rekapStatus((int) $r['presensi_id']);
        }
        unset($r);

        $data = [
            'title'   => 'Riwayat Mengajar',
            'content' => view('mengajar/riwayat', ['rows' => $rows, 'filter' => $filter]),
        ];

        return view('layouts/main', $data);
    }

    /**
     * Kalender jadwal semester milik guru, ditampilkan sebagai grid Hari x Jam Ke —
     * karena jadwal berbasis hari (bukan tanggal spesifik) dan berulang tiap minggu,
     * satu grid ini sudah mewakili seluruh pola mengajar guru selama semester berjalan.
     */
    public function kalender()
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $aktif = (new SemesterModel())->getActive();
        if (! $aktif) {
            $data = ['title' => 'Kalender Jadwal', 'content' => view('mengajar/kalender_kosong')];

            return view('layouts/main', $data);
        }

        $items = (new JadwalModel())
            ->select('jadwal.*, mata_pelajaran.nama as nama_mapel, kelas.nama_kelas')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->where('jadwal.guru_id', (int) $guru['id'])
            ->where('jadwal.semester_id', (int) $aktif['id'])
            ->findAll();

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $jamList  = (new JamPelajaranModel())->getAllOrdered();

        // Susun grid[jam_ke][hari]: null = kosong, 'lanjutan' = sudah tercakup rowspan
        // sel di atasnya (untuk jadwal yang membentang lebih dari 1 jam pelajaran),
        // array = detail jadwal yang jadi sel utama (dengan rowspan-nya).
        $grid = [];
        foreach ($jamList as $jp) {
            foreach ($hariList as $h) {
                $grid[$jp['jam_ke']][$h] = null;
            }
        }

        foreach ($items as $item) {
            $mulai = (int) $item['jam_ke_mulai'];
            $akhir = (int) $item['jam_ke_selesai'];

            $grid[$mulai][$item['hari']] = $item + ['rowspan' => max(1, $akhir - $mulai + 1)];

            for ($k = $mulai + 1; $k <= $akhir; $k++) {
                $grid[$k][$item['hari']] = 'lanjutan';
            }
        }

        $data = [
            'title'   => 'Kalender Jadwal — ' . $aktif['nama_tahun_ajaran'] . ' ' . $aktif['nama'],
            'content' => view('mengajar/kalender', [
                'aktif'    => $aktif,
                'hariList' => $hariList,
                'jamList'  => $jamList,
                'grid'     => $grid,
            ]),
        ];

        return view('layouts/main', $data);
    }
}
