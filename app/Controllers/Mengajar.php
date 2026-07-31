<?php

namespace App\Controllers;

use App\Libraries\AuditLogger;
use App\Libraries\ScheduleResolverService;
use App\Models\AgendaAkademikModel;
use App\Models\GuruModel;
use App\Models\JadwalModel;
use App\Models\JamPelajaranModel;
use App\Models\JurnalMengajarModel;
use App\Models\PenilaianHarianModel;
use App\Models\PresensiDetailModel;
use App\Models\PresensiModel;
use App\Models\SemesterModel;
use App\Models\SiswaModel;
use App\Models\TujuanPembelajaranModel;
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

        // Penilaian Harian (opsional): daftar siswa kelas ini + penilaian yang
        // sudah ada kalau guru sempat mengisi lalu kembali sebelum jurnal terkunci.
        $daftarSiswa      = (new SiswaModel())->where('kelas_id', $jadwal['kelas_id'])->where('status', 'aktif')->orderBy('nama', 'ASC')->findAll();
        $penilaianTersimpan = $jurnal ? (new PenilaianHarianModel())->getUntukJurnal((int) $jurnal['id']) : [];

        // TP: diambil dari Master TP milik Guru Pengampu jadwal ini (kalau
        // sudah tertaut) — jadwal LAMA yang belum tertaut ke Guru Pengampu
        // tetap bisa isi TP manual (lihat penanganannya di view).
        $daftarTP = $jadwal['guru_pengampu_id']
            ? (new TujuanPembelajaranModel())->getUntukPengampu((int) $jadwal['guru_pengampu_id'])
            : [];

        $data = [
            'title'   => 'Jurnal Mengajar — ' . $jadwal['nama_mapel'],
            'content' => view('mengajar/jurnal', [
                'jadwal'    => $jadwal,
                'tanggal'   => $tanggal,
                'jurnal'    => $jurnal,
                'daftarSiswa' => $daftarSiswa,
                'penilaianTersimpan' => $penilaianTersimpan,
                'daftarTP'  => $daftarTP,
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

        // Penilaian Harian (opsional) — simpan SETELAH jurnal berhasil, karena
        // butuh jurnal_id. Baris kosong otomatis tidak dibuat record-nya
        // (lihat PenilaianHarianModel::simpanUntukJurnal()).
        $jurnalIdUntukNilai = $existing ? (int) $existing['id'] : (int) $jurnalModel->getInsertID();
        $penilaianRows      = $this->request->getPost('penilaian') ?? [];
        $jumlahDinilai      = 0;
        if (is_array($penilaianRows) && ! empty($penilaianRows)) {
            $jumlahDinilai = (new PenilaianHarianModel())->simpanUntukJurnal($jurnalIdUntukNilai, $penilaianRows);
        }

        // Kunci presensi juga, karena sesi mengajarnya sudah resmi "Selesai".
        $presensi = $presensiModel->findByJadwalTanggal((int) $jadwal['id'], $tanggal);
        if ($presensi && ! $presensi['locked_at']) {
            $presensiModel->update($presensi['id'], ['locked_at' => date('Y-m-d H:i:s')]);
        }

        (new AuditLogger())->log('isi_jurnal', "Mengisi jurnal jadwal #{$jadwal['id']} ({$jadwal['nama_mapel']}/{$jadwal['nama_kelas']}) tanggal {$tanggal}" . ($jumlahDinilai > 0 ? ", {$jumlahDinilai} siswa dinilai" : ''));

        $pesan = 'Jurnal tersimpan. Sesi mengajar hari ini selesai — terima kasih!';
        if ($jumlahDinilai > 0) {
            $pesan = "Jurnal tersimpan, {$jumlahDinilai} siswa dinilai. Sesi mengajar hari ini selesai — terima kasih!";
        }

        return redirect()->to('/dashboard')->with('message', $pesan);
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

        $aktif = (new SemesterModel())->getActive();

        $data = [
            'title'   => 'Riwayat Mengajar',
            'content' => view('mengajar/riwayat', [
                'rows'      => $rows,
                'filter'    => $filter,
                'statistik' => $aktif ? $this->statistikMengajarSemester((int) $guru['id'], $aktif) : null,
            ]),
        ];

        return view('layouts/main', $data);
    }

    /**
     * "Sudah mengajar X kali dari Y sesi yang dijadwalkan semester ini" — Y
     * dihitung dari pola jadwal mingguan guru dikalikan berapa kali hari itu
     * SUDAH LEWAT sejak semester mulai (dikurangi hari libur), X dari jumlah
     * jurnal_mengajar yang benar-benar terisi. Return null kalau semester
     * aktif belum diisi tanggal berlakunya (tidak ada dasar hitung yang adil).
     */
    private function statistikMengajarSemester(int $guruId, array $aktif): ?array
    {
        if (! $aktif['tanggal_mulai'] || ! $aktif['tanggal_selesai']) {
            return null;
        }

        $mulai   = $aktif['tanggal_mulai'];
        $kemarin = min($aktif['tanggal_selesai'], date('Y-m-d', strtotime('-1 day')));

        if ($mulai > $kemarin) {
            return ['seharusnya' => 0, 'sudah' => 0, 'terlewat' => 0];
        }

        $jadwalSaya = (new JadwalModel())->select('id, hari')
            ->where('guru_id', $guruId)->where('semester_id', (int) $aktif['id'])->findAll();

        if (empty($jadwalSaya)) {
            return ['seharusnya' => 0, 'sudah' => 0, 'terlewat' => 0];
        }

        $jadwalIds = array_column($jadwalSaya, 'id');
        $jurnalAda = (new JurnalMengajarModel())->select('jadwal_id, tanggal')
            ->whereIn('jadwal_id', $jadwalIds)->where('tanggal >=', $mulai)->where('tanggal <=', $kemarin)->findAll();
        $sudahIsi = [];
        foreach ($jurnalAda as $j) {
            $sudahIsi[$j['jadwal_id'] . '|' . $j['tanggal']] = true;
        }

        $agendaNonaktif = [];
        foreach ((new AgendaAkademikModel())->getEventRentang($mulai, $kemarin) as $ev) {
            if ($ev['dampak_presensi'] === 'nonaktif' && $ev['status'] !== 'dibatalkan') {
                $agendaNonaktif[$ev['tanggal_tampil']] = true;
            }
        }
        $hariMap   = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => null];

        $seharusnya = 0;
        $sudah      = 0;
        foreach ($jadwalSaya as $j) {
            $cursor = $mulai;
            while ($cursor <= $kemarin) {
                $namaHari = $hariMap[(int) date('N', strtotime($cursor))] ?? null;
                if ($namaHari === $j['hari'] && ! isset($agendaNonaktif[$cursor])) {
                    $seharusnya++;
                    if (isset($sudahIsi[$j['id'] . '|' . $cursor])) {
                        $sudah++;
                    }
                }
                $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
            }
        }

        return ['seharusnya' => $seharusnya, 'sudah' => $sudah, 'terlewat' => $seharusnya - $sudah];
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

        // Minggu yang sedang dilihat, ditentukan dari tanggal Senin-nya. Defaultnya
        // minggu berjalan (hari ini); navigasi sebelumnya/berikutnya tinggal geser 7 hari.
        $awalMinggu = $this->request->getGet('awal') ?: $this->awalMinggu(date('Y-m-d'));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $awalMinggu) || ! strtotime($awalMinggu)) {
            $awalMinggu = $this->awalMinggu(date('Y-m-d'));
        }
        $awalMinggu = $this->awalMinggu($awalMinggu); // jaga-jaga kalau yang dikirim bukan hari Senin

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $jamKeList = (new JamPelajaranModel())->getSemuaJamKeUnik();

        // Tanggal sebenarnya untuk tiap hari di minggu yang sedang dilihat.
        $tanggalHari = [];
        foreach ($hariList as $i => $h) {
            $tanggalHari[$h] = date('Y-m-d', strtotime($awalMinggu . " +{$i} days"));
        }
        $akhirMinggu = end($tanggalHari);

        $items = (new JadwalModel())
            ->select('jadwal.*, mata_pelajaran.nama as nama_mapel, kelas.nama_kelas')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->where('jadwal.guru_id', (int) $guru['id'])
            ->where('jadwal.semester_id', (int) $aktif['id'])
            ->findAll();

        $tukarModel = new TukarJadwalModel();
        $resolver   = new ScheduleResolverService();

        // Susun grid[jam_ke][hari]: null = kosong, 'lanjutan' = sudah tercakup rowspan
        // sel di atasnya (untuk jadwal yang membentang lebih dari 1 jam pelajaran),
        // array = detail jadwal yang jadi sel utama (dengan rowspan-nya).
        $grid = [];
        foreach ($jamKeList as $jamKe) {
            foreach ($hariList as $h) {
                $grid[$jamKe][$h] = null;
            }
        }

        foreach ($items as $item) {
            // Langkah 1: PERTUKARAN JADWAL (slot penuh, lewat ScheduleResolverService).
            // Kalau ada pertukaran disetujui yang aktif untuk tanggal hari master
            // item ini (di minggu yang sedang dilihat), posisi hari/jam EFEKTIFnya
            // pindah ke slot pasangannya — guru/mapel/kelas tetap milik guru ini.
            $tanggalMaster = $tanggalHari[$item['hari']];
            $efektif       = $resolver->terapkan($item, $tanggalMaster);
            $hariTampil    = $efektif['hari'];

            if (! isset($tanggalHari[$hariTampil])) {
                continue; // jaga-jaga kalau hasil resolve di luar Senin-Sabtu
            }

            $mulai         = (int) $efektif['jam_ke_mulai'];
            $akhir         = (int) $efektif['jam_ke_selesai'];
            $tanggalTampil = $tanggalHari[$hariTampil];

            // Langkah 2: GURU PENGGANTI (satu tanggal, lewat fitur Tukar Jadwal yang
            // sudah ada) — dicek di TANGGAL EFEKTIF (setelah pertukaran slot di atas),
            // karena "siapa yang mengajar sesi ini hari itu" tetap relevan walau
            // sesinya sendiri sudah pindah hari akibat pertukaran.
            $swap = $tukarModel->getDisetujui((int) $item['id'], $tanggalTampil);
            $isi  = $item + [
                'hari'           => $hariTampil,
                'jam_ke_mulai'   => $mulai,
                'jam_ke_selesai' => $akhir,
                'jam_mulai'      => $efektif['jam_mulai'],
                'jam_selesai'    => $efektif['jam_selesai'],
                'rowspan'        => max(1, $akhir - $mulai + 1),
                'tanggal'        => $tanggalTampil,
                'ditukar_slot'   => $efektif['ditukar'],
                'hari_asli'      => $efektif['hari_asli'],
                'digantikan'     => null,
            ];

            if ($swap) {
                $penggantiRow      = (new GuruModel())->find((int) $swap['guru_pengganti_id']);
                $isi['digantikan'] = $penggantiRow['nama'] ?? '-';
            }

            $grid[$mulai][$hariTampil] = $isi;

            for ($k = $mulai + 1; $k <= $akhir; $k++) {
                $grid[$k][$hariTampil] = 'lanjutan';
            }
        }

        // Sesi milik guru LAIN yang di minggu ini digantikan oleh guru yang sedang
        // login — ikut ditambahkan ke grid supaya kalender mencerminkan tanggung
        // jawab SEBENARNYA minggu itu, bukan cuma jadwal tetapnya saja.
        $penggantian = $tukarModel->getDisetujuiUntukPengganti((int) $guru['id'], $awalMinggu, $akhirMinggu);
        foreach ($penggantian as $p) {
            $mulai = (int) $p['jam_ke_mulai'];
            $akhir = (int) $p['jam_ke_selesai'];

            // Cari hari (Senin..Sabtu) yang tanggalnya cocok dengan tanggal pengajuan ini.
            $hariCocok = array_search($p['tanggal'], $tanggalHari, true);
            if ($hariCocok === false) {
                continue;
            }

            $grid[$mulai][$hariCocok] = [
                'id' => $p['jadwal_id'], 'hari' => $hariCocok, 'jam_mulai' => $p['jam_mulai'], 'jam_selesai' => $p['jam_selesai'],
                'jam_ke_mulai' => $mulai, 'jam_ke_selesai' => $akhir,
                'nama_mapel' => $p['nama_mapel'], 'nama_kelas' => $p['nama_kelas'],
                'rowspan' => max(1, $akhir - $mulai + 1), 'tanggal' => $p['tanggal'],
                'digantikan' => null, 'menggantikan' => $p['nama_guru_asal'],
            ];
            for ($k = $mulai + 1; $k <= $akhir; $k++) {
                $grid[$k][$hariCocok] = 'lanjutan';
            }
        }

        // Status kelengkapan tiap sel — inilah yang membuat Kalender bisa dipakai
        // untuk CEK & LENGKAPI presensi/jurnal yang terlewat, bukan cuma menampilkan
        // pola jadwal. Cuma dihitung untuk sel yang genuinely ada isinya.
        $today         = date('Y-m-d');
        $presensiModel = new PresensiModel();
        $jurnalModel   = new JurnalMengajarModel();

        foreach ($grid as $jamKe => $baris) {
            foreach ($baris as $hari => $cell) {
                if (! is_array($cell)) {
                    continue;
                }

                if (! empty($cell['digantikan'])) {
                    $grid[$jamKe][$hari]['status_sel'] = 'digantikan';
                    continue;
                }

                if ($cell['tanggal'] > $today) {
                    $grid[$jamKe][$hari]['status_sel'] = 'akan_datang';
                    continue;
                }

                $presensi = $presensiModel->findByJadwalTanggal((int) $cell['id'], $cell['tanggal']);
                $jurnal   = $jurnalModel->findByJadwalTanggal((int) $cell['id'], $cell['tanggal']);

                if ($presensi && $jurnal) {
                    $grid[$jamKe][$hari]['status_sel']    = 'selesai';
                    $grid[$jamKe][$hari]['presensi_id']   = $presensi['id'];
                } elseif ($presensi) {
                    $grid[$jamKe][$hari]['status_sel']  = $cell['tanggal'] === $today ? 'berlangsung' : 'jurnal_kosong';
                    $grid[$jamKe][$hari]['presensi_id'] = $presensi['id'];
                } else {
                    $grid[$jamKe][$hari]['status_sel'] = $cell['tanggal'] === $today ? 'belum' : 'terlewat';
                }
            }
        }

        // Jam SUNGGUHAN hari ini (kalau hari ini termasuk minggu yang sedang dilihat) —
        // dipakai garis "sekarang" di JS, karena jamnya sekarang bisa beda per hari,
        // jadi tidak bisa lagi pakai satu jamList yang sama utk semua hari.
        $todayIso  = date('Y-m-d');
        $namaHariIni = $this->namaHariDariTanggal($todayIso);
        $jamHariIni = ($namaHariIni && in_array($todayIso, $tanggalHari, true))
            ? (new JamPelajaranModel())->getByHari($namaHariIni)
            : [];

        $data = [
            'title'   => 'Kalender Jadwal — ' . $aktif['nama_tahun_ajaran'] . ' ' . $aktif['nama'],
            'content' => view('mengajar/kalender', [
                'aktif'       => $aktif,
                'hariList'    => $hariList,
                'jamKeList'   => $jamKeList,
                'jamHariIni'  => $jamHariIni,
                'grid'        => $grid,
                'tanggalHari' => $tanggalHari,
                'awalMinggu'  => $awalMinggu,
                'akhirMinggu' => $akhirMinggu,
                'iniMinggu'   => $awalMinggu === $this->awalMinggu(date('Y-m-d')),
                'mingguSebelumnyaValid' => ! $aktif['tanggal_mulai'] || date('Y-m-d', strtotime($awalMinggu . ' -7 days')) >= $this->awalMinggu($aktif['tanggal_mulai']),
                'mingguBerikutnyaValid' => ! $aktif['tanggal_selesai'] || date('Y-m-d', strtotime($awalMinggu . ' +7 days')) <= $aktif['tanggal_selesai'],
            ]),
        ];

        return view('layouts/main', $data);
    }

    private function awalMinggu(string $tanggal): string
    {
        $n = (int) date('N', strtotime($tanggal)); // 1=Senin ... 7=Minggu

        return date('Y-m-d', strtotime($tanggal . ' -' . ($n - 1) . ' days'));
    }

    /**
     * Detail satu sesi dari Riwayat Mengajar: presensi per siswa + seluruh
     * field jurnal (bukan cuma materi seperti di ringkasan daftar riwayat).
     */
    public function riwayatDetail($presensiId)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $presensi = (new PresensiModel())->find((int) $presensiId);
        if (! $presensi) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Sesi tidak ditemukan.');
        }

        $jadwal = $this->ambilJadwalUntukSesi((int) $presensi['jadwal_id'], (int) $guru['id'], $presensi['tanggal']);
        if (! $jadwal) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Sesi ini bukan milik Anda.');
        }

        $siswaPresensi = (new PresensiDetailModel())->getByPresensiId((int) $presensi['id']);
        $jurnal        = (new JurnalMengajarModel())->findByJadwalTanggal((int) $jadwal['id'], $presensi['tanggal']);

        $data = [
            'title'   => 'Detail Sesi — ' . $jadwal['nama_mapel'],
            'content' => view('mengajar/riwayat_detail', [
                'jadwal'        => $jadwal,
                'presensi'      => $presensi,
                'siswaPresensi' => $siswaPresensi,
                'jurnal'        => $jurnal,
            ]),
        ];

        return view('layouts/main', $data);
    }

    /**
     * Revisi presensi sesi LAMA dari Riwayat Mengajar — beda dengan presensi()
     * yang cuma untuk hari ini, method ini SENGAJA tidak menolak sesi yang
     * sudah terkunci, karena inilah jalur resminya untuk membetulkan kalau
     * ada yang salah input. Tetap tercatat di audit log sebagai "revisi".
     */
    public function revisiPresensi($presensiId)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $presensi = (new PresensiModel())->find((int) $presensiId);
        if (! $presensi) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Sesi tidak ditemukan.');
        }

        $jadwal = $this->ambilJadwalUntukSesi((int) $presensi['jadwal_id'], (int) $guru['id'], $presensi['tanggal']);
        if (! $jadwal) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Sesi ini bukan milik Anda.');
        }

        $existing = [];
        foreach ((new PresensiDetailModel())->getByPresensiId((int) $presensi['id']) as $d) {
            $existing[(int) $d['siswa_id']] = $d;
        }

        $data = [
            'title'   => 'Revisi Presensi — ' . $jadwal['nama_mapel'],
            'content' => view('mengajar/revisi_presensi', [
                'jadwal'   => $jadwal,
                'presensi' => $presensi,
                'siswa'    => (new SiswaModel())->getWithKelas((int) $jadwal['kelas_id']),
                'existing' => $existing,
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function simpanRevisiPresensi($presensiId)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $presensi = (new PresensiModel())->find((int) $presensiId);
        if (! $presensi) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Sesi tidak ditemukan.');
        }

        $jadwal = $this->ambilJadwalUntukSesi((int) $presensi['jadwal_id'], (int) $guru['id'], $presensi['tanggal']);
        if (! $jadwal) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Sesi ini bukan milik Anda.');
        }

        $detailModel = new PresensiDetailModel();
        $statusPost  = $this->request->getPost('status') ?? [];
        $catatanPost = $this->request->getPost('catatan') ?? [];

        foreach ($statusPost as $siswaId => $status) {
            $siswaId = (int) $siswaId;
            if (! in_array($status, PresensiDetailModel::STATUS_VALID, true)) {
                continue;
            }

            $payload = [
                'presensi_id' => (int) $presensi['id'],
                'siswa_id'    => $siswaId,
                'status'      => $status,
                'catatan'     => trim((string) ($catatanPost[$siswaId] ?? '')) ?: null,
            ];

            $existingRow = $detailModel->where('presensi_id', $presensi['id'])->where('siswa_id', $siswaId)->first();
            if ($existingRow) {
                $detailModel->update($existingRow['id'], $payload);
            } else {
                $detailModel->insert($payload);
            }
        }

        (new AuditLogger())->log('revisi_presensi', "Merevisi presensi jadwal #{$jadwal['id']} ({$jadwal['nama_mapel']}/{$jadwal['nama_kelas']}) tanggal {$presensi['tanggal']}");

        return redirect()->to('/mengajar/riwayat/detail/' . $presensi['id'])->with('message', 'Presensi berhasil direvisi.');
    }

    public function revisiJurnal($jurnalId)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $jurnal = (new JurnalMengajarModel())->find((int) $jurnalId);
        if (! $jurnal) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Jurnal tidak ditemukan.');
        }

        $jadwal = $this->ambilJadwalUntukSesi((int) $jurnal['jadwal_id'], (int) $guru['id'], $jurnal['tanggal']);
        if (! $jadwal) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Sesi ini bukan milik Anda.');
        }

        $presensi = (new PresensiModel())->findByJadwalTanggal((int) $jadwal['id'], $jurnal['tanggal']);

        $daftarSiswa        = (new SiswaModel())->where('kelas_id', $jadwal['kelas_id'])->where('status', 'aktif')->orderBy('nama', 'ASC')->findAll();
        $penilaianTersimpan = (new PenilaianHarianModel())->getUntukJurnal((int) $jurnal['id']);
        $daftarTP           = $jadwal['guru_pengampu_id']
            ? (new TujuanPembelajaranModel())->getUntukPengampu((int) $jadwal['guru_pengampu_id'])
            : [];

        $data = [
            'title'   => 'Revisi Jurnal — ' . $jadwal['nama_mapel'],
            'content' => view('mengajar/revisi_jurnal', [
                'jadwal'    => $jadwal,
                'jurnal'    => $jurnal,
                'presensi'  => $presensi,
                'daftarSiswa' => $daftarSiswa,
                'penilaianTersimpan' => $penilaianTersimpan,
                'daftarTP'  => $daftarTP,
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function simpanRevisiJurnal($jurnalId)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $jurnalModel = new JurnalMengajarModel();
        $jurnal      = $jurnalModel->find((int) $jurnalId);
        if (! $jurnal) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Jurnal tidak ditemukan.');
        }

        $jadwal = $this->ambilJadwalUntukSesi((int) $jurnal['jadwal_id'], (int) $guru['id'], $jurnal['tanggal']);
        if (! $jadwal) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Sesi ini bukan milik Anda.');
        }

        $ok = $jurnalModel->update((int) $jurnalId, [
            'materi'                => $this->request->getPost('materi'),
            'tujuan_pembelajaran'   => $this->request->getPost('tujuan_pembelajaran'),
            'metode'                => $this->request->getPost('metode'),
            'media'                 => $this->request->getPost('media'),
            'kegiatan_pembelajaran' => $this->request->getPost('kegiatan_pembelajaran'),
            'catatan'               => $this->request->getPost('catatan'),
            'kendala'               => $this->request->getPost('kendala'),
            'tindak_lanjut'         => $this->request->getPost('tindak_lanjut'),
        ]);

        if (! $ok) {
            return redirect()->back()->withInput()->with('error', implode(' ', $jurnalModel->errors()));
        }

        $penilaianRows = $this->request->getPost('penilaian') ?? [];
        $jumlahDinilai = 0;
        if (is_array($penilaianRows) && ! empty($penilaianRows)) {
            $jumlahDinilai = (new PenilaianHarianModel())->simpanUntukJurnal((int) $jurnalId, $penilaianRows);
        }

        (new AuditLogger())->log('revisi_jurnal', "Merevisi jurnal jadwal #{$jadwal['id']} ({$jadwal['nama_mapel']}/{$jadwal['nama_kelas']}) tanggal {$jurnal['tanggal']}" . ($jumlahDinilai > 0 ? ", {$jumlahDinilai} siswa dinilai" : ''));

        return redirect()->to('/mengajar/riwayat/detail/' . $this->cariPresensiId((int) $jadwal['id'], $jurnal['tanggal']))->with('message', 'Jurnal berhasil direvisi.');
    }

    private function cariPresensiId(int $jadwalId, string $tanggal): int
    {
        $presensi = (new PresensiModel())->findByJadwalTanggal($jadwalId, $tanggal);

        return (int) ($presensi['id'] ?? 0);
    }

    /**
     * "Isi Jurnal Terlewat" — untuk sesi yang presensinya SUDAH diisi tapi
     * jurnalnya TIDAK PERNAH diisi sama sekali (beda dari Revisi Jurnal yang
     * untuk MENGUBAH jurnal yang sudah ada). Bisa dipakai kapan pun, tidak
     * terbatas hari ini — supaya guru yang baru sadar lupa mengisi jurnal
     * minggu lalu tetap bisa melengkapinya, bukan sesi itu jadi hilang
     * selamanya karena Dashboard cuma menampilkan jadwal hari ini.
     */
    public function isiJurnalTerlewat($presensiId)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $presensi = (new PresensiModel())->find((int) $presensiId);
        if (! $presensi) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Sesi tidak ditemukan.');
        }

        $jadwal = $this->ambilJadwalUntukSesi((int) $presensi['jadwal_id'], (int) $guru['id'], $presensi['tanggal']);
        if (! $jadwal) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Sesi ini bukan milik Anda.');
        }

        // Kalau ternyata jurnalnya SUDAH ada (mis. dibuka dari link lama), arahkan ke
        // Detail biasa — jalur ini khusus utk yang BELUM PERNAH diisi sama sekali.
        if ((new JurnalMengajarModel())->findByJadwalTanggal((int) $jadwal['id'], $presensi['tanggal'])) {
            return redirect()->to('/mengajar/riwayat/detail/' . $presensiId)->with('message', 'Jurnal untuk sesi ini sudah ada.');
        }

        $data = [
            'title'   => 'Isi Jurnal Terlewat — ' . $jadwal['nama_mapel'],
            'content' => view('mengajar/isi_jurnal_terlewat', [
                'jadwal'   => $jadwal,
                'presensi' => $presensi,
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function simpanJurnalTerlewat($presensiId)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $presensi = (new PresensiModel())->find((int) $presensiId);
        if (! $presensi) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Sesi tidak ditemukan.');
        }

        $jadwal = $this->ambilJadwalUntukSesi((int) $presensi['jadwal_id'], (int) $guru['id'], $presensi['tanggal']);
        if (! $jadwal) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Sesi ini bukan milik Anda.');
        }

        $jurnalModel = new JurnalMengajarModel();

        // Jaga-jaga dari klik dobel / dua tab terbuka bersamaan — jangan sampai satu
        // sesi punya 2 baris jurnal.
        if ($jurnalModel->findByJadwalTanggal((int) $jadwal['id'], $presensi['tanggal'])) {
            return redirect()->to('/mengajar/riwayat/detail/' . $presensiId)->with('error', 'Jurnal untuk sesi ini sudah pernah diisi sebelumnya.');
        }

        $ok = $jurnalModel->insert([
            'jadwal_id'             => (int) $jadwal['id'],
            'tanggal'               => $presensi['tanggal'],
            'materi'                => $this->request->getPost('materi'),
            'tujuan_pembelajaran'   => $this->request->getPost('tujuan_pembelajaran'),
            'metode'                => $this->request->getPost('metode'),
            'media'                 => $this->request->getPost('media'),
            'kegiatan_pembelajaran' => $this->request->getPost('kegiatan_pembelajaran'),
            'catatan'               => $this->request->getPost('catatan'),
            'kendala'               => $this->request->getPost('kendala'),
            'tindak_lanjut'         => $this->request->getPost('tindak_lanjut'),
            'locked_at'             => date('Y-m-d H:i:s'),
        ]);

        if (! $ok) {
            return redirect()->back()->withInput()->with('error', implode(' ', $jurnalModel->errors()));
        }

        // Sekalian kunci presensinya juga kalau ternyata belum (menyamakan dengan
        // alur normal, di mana presensi & jurnal terkunci bersamaan).
        if (! $presensi['locked_at']) {
            (new PresensiModel())->update((int) $presensiId, ['locked_at' => date('Y-m-d H:i:s')]);
        }

        (new AuditLogger())->log('isi_jurnal_terlewat', "Mengisi jurnal yang terlewat untuk jadwal #{$jadwal['id']} ({$jadwal['nama_mapel']}/{$jadwal['nama_kelas']}) tanggal {$presensi['tanggal']}");

        return redirect()->to('/mengajar/riwayat/detail/' . $presensiId)->with('message', 'Jurnal berhasil diisi — sesi ini sekarang lengkap.');
    }

    /**
     * "Hari Terlewat" — beda dari Isi Jurnal Terlewat (yang presensinya SUDAH
     * ada, cuma jurnalnya kosong). Ini untuk hari yang PRESENSI-NYA SAJA belum
     * pernah diisi sama sekali — tidak ada riwayat apa pun untuk dirujuk,
     * jadi tanggalnya harus DIREKONSTRUKSI dari pola jadwal mingguan guru
     * (hari yang cocok, bukan hari libur, belum ada presensi), bukan dicari
     * dari tabel presensi yang justru kosong.
     */
    public function hariTerlewat()
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $aktif = (new SemesterModel())->getActive();
        if (! $aktif) {
            $data = ['title' => 'Hari Terlewat', 'content' => view('mengajar/hari_terlewat', ['terlewat' => []])];

            return view('layouts/main', $data);
        }

        $jadwalSaya = (new JadwalModel())
            ->select('jadwal.*, mata_pelajaran.nama as nama_mapel, kelas.nama_kelas')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->where('jadwal.guru_id', (int) $guru['id'])
            ->where('jadwal.semester_id', (int) $aktif['id'])
            ->findAll();

        $mulai  = date('Y-m-d', strtotime('-30 days'));
        $kemarin = date('Y-m-d', strtotime('-1 day')); // BUKAN hari ini — itu sudah ditangani alur normal di Dashboard

        // Ambil SEKALI SAJA semua presensi & hari libur dalam rentang ini, lalu dicek
        // di memori (bukan query berulang di dalam loop) supaya tetap ringan walau
        // guru punya banyak jadwal.
        $presensiAda = [];
        if (! empty($jadwalSaya)) {
            $jadwalIds = array_column($jadwalSaya, 'id');
            $rows = (new PresensiModel())->whereIn('jadwal_id', $jadwalIds)
                ->where('tanggal >=', $mulai)->where('tanggal <=', $kemarin)->findAll();
            foreach ($rows as $r) {
                $presensiAda[$r['jadwal_id'] . '|' . $r['tanggal']] = true;
            }
        }

        // Tanggal nonaktif dari Kalender Akademik (kategori Libur/Nasional dst
        // dengan dampak_presensi='nonaktif') — diambil SEKALI utk seluruh
        // rentang 30 hari (bukan per-tanggal di dalam loop di bawah) supaya
        // tetap ringan.
        $agendaNonaktif = [];
        foreach ((new AgendaAkademikModel())->getEventRentang($mulai, $kemarin) as $ev) {
            if ($ev['dampak_presensi'] === 'nonaktif' && $ev['status'] !== 'dibatalkan') {
                $agendaNonaktif[$ev['tanggal_tampil']] = true;
            }
        }

        $terlewat = [];
        foreach ($jadwalSaya as $j) {
            $cursor = $mulai;
            while ($cursor <= $kemarin) {
                $namaHari = $this->namaHariDariTanggal($cursor);
                $cocokHari = $namaHari === $j['hari'];
                $bukanLibur = ! isset($agendaNonaktif[$cursor]);
                $belumAda = ! isset($presensiAda[$j['id'] . '|' . $cursor]);

                if ($cocokHari && $bukanLibur && $belumAda) {
                    $terlewat[] = [
                        'jadwal_id'  => $j['id'],
                        'tanggal'    => $cursor,
                        'hari'       => $namaHari,
                        'nama_mapel' => $j['nama_mapel'],
                        'nama_kelas' => $j['nama_kelas'],
                        'jam_mulai'  => $j['jam_mulai'],
                        'jam_selesai'=> $j['jam_selesai'],
                    ];
                }

                $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
            }
        }

        usort($terlewat, static fn ($a, $b) => strcmp($b['tanggal'], $a['tanggal']));

        $data = [
            'title'   => 'Hari Terlewat',
            'content' => view('mengajar/hari_terlewat', ['terlewat' => $terlewat]),
        ];

        return view('layouts/main', $data);
    }

    private function namaHariDariTanggal(string $tanggal): ?string
    {
        $hariMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => null];

        return $hariMap[(int) date('N', strtotime($tanggal))] ?? null;
    }

    /**
     * Validasi umum dipakai di seluruh alur "hari terlewat": guru pemilik jadwal
     * (bukan lewat tukar jadwal — mengisi utk tanggal lampau tidak relevan dgn
     * pengganti), tanggalnya di masa lalu, harinya cocok dengan jadwal.hari, dan
     * belum ada presensi utk kombinasi ini. Return jadwal kalau valid, atau
     * redirect response kalau tidak (dicek pemanggil).
     */
    private function validasiHariTerlewat(int $jadwalId, string $tanggal, array $guru)
    {
        if ($tanggal >= date('Y-m-d')) {
            return redirect()->to('/mengajar/riwayat/hari-terlewat')->with('error', 'Tanggal harus tanggal yang sudah lewat.');
        }

        $jadwal = (new JadwalModel())->getJadwalMilikGuru($jadwalId, (int) $guru['id']);
        if (! $jadwal) {
            return redirect()->to('/mengajar/riwayat/hari-terlewat')->with('error', 'Jadwal tidak ditemukan atau bukan milik Anda.');
        }

        if ($this->namaHariDariTanggal($tanggal) !== $jadwal['hari']) {
            return redirect()->to('/mengajar/riwayat/hari-terlewat')->with('error', 'Tanggal yang dipilih tidak jatuh pada hari ' . $jadwal['hari'] . '.');
        }

        if ((new PresensiModel())->findByJadwalTanggal($jadwalId, $tanggal)) {
            return redirect()->to('/mengajar/riwayat/hari-terlewat')->with('error', 'Presensi untuk tanggal ini sudah pernah diisi.');
        }

        return $jadwal;
    }

    public function presensiHariTerlewat($jadwalId, $tanggal)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $jadwal = $this->validasiHariTerlewat((int) $jadwalId, $tanggal, $guru);
        if (! is_array($jadwal)) {
            return $jadwal; // redirect response dari validasi
        }

        $siswa = (new SiswaModel())->getWithKelas((int) $jadwal['kelas_id']);

        $data = [
            'title'   => 'Isi Presensi Terlewat',
            'content' => view('mengajar/presensi', [
                'jadwal'      => $jadwal,
                'tanggal'     => $tanggal,
                'siswa'       => $siswa,
                'existing'    => [],
                'formAction'  => 'mengajar/riwayat/hari-terlewat/presensi/' . $jadwalId . '/' . $tanggal,
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function simpanPresensiHariTerlewat($jadwalId, $tanggal)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $jadwal = $this->validasiHariTerlewat((int) $jadwalId, $tanggal, $guru);
        if (! is_array($jadwal)) {
            return $jadwal;
        }

        $presensiModel = new PresensiModel();
        $presensiId    = $presensiModel->insert(['jadwal_id' => (int) $jadwalId, 'tanggal' => $tanggal]);

        $detailModel = new PresensiDetailModel();
        $statusPost  = $this->request->getPost('status') ?? [];
        $catatanPost = $this->request->getPost('catatan') ?? [];

        foreach ($statusPost as $siswaId => $status) {
            if (! in_array($status, PresensiDetailModel::STATUS_VALID, true)) {
                continue;
            }
            $detailModel->insert([
                'presensi_id' => (int) $presensiId,
                'siswa_id'    => (int) $siswaId,
                'status'      => $status,
                'catatan'     => trim((string) ($catatanPost[$siswaId] ?? '')) ?: null,
            ]);
        }

        (new AuditLogger())->log('isi_presensi_terlewat', "Mengisi presensi yang terlewat untuk jadwal #{$jadwalId} tanggal {$tanggal}");

        return redirect()->to('/mengajar/riwayat/hari-terlewat/jurnal/' . $jadwalId . '/' . $tanggal);
    }

    public function jurnalHariTerlewat($jadwalId, $tanggal)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $jadwal = (new JadwalModel())->getJadwalMilikGuru((int) $jadwalId, (int) $guru['id']);
        if (! $jadwal) {
            return redirect()->to('/mengajar/riwayat/hari-terlewat')->with('error', 'Jadwal tidak ditemukan atau bukan milik Anda.');
        }

        if (! (new PresensiModel())->findByJadwalTanggal((int) $jadwalId, $tanggal)) {
            return redirect()->to('/mengajar/riwayat/hari-terlewat/presensi/' . $jadwalId . '/' . $tanggal)->with('error', 'Isi presensinya dulu sebelum lanjut ke jurnal.');
        }

        $daftarSiswa = (new SiswaModel())->where('kelas_id', $jadwal['kelas_id'])->where('status', 'aktif')->orderBy('nama', 'ASC')->findAll();

        $data = [
            'title'   => 'Isi Jurnal Terlewat',
            'content' => view('mengajar/jurnal', [
                'jadwal'     => $jadwal,
                'tanggal'    => $tanggal,
                'jurnal'     => null,
                'formAction' => 'mengajar/riwayat/hari-terlewat/jurnal/' . $jadwalId . '/' . $tanggal,
                'daftarSiswa' => $daftarSiswa,
                'penilaianTersimpan' => [],
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function simpanJurnalHariTerlewat($jadwalId, $tanggal)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Akun Anda belum dihubungkan ke data guru.');
        }

        $jadwal = (new JadwalModel())->getJadwalMilikGuru((int) $jadwalId, (int) $guru['id']);
        if (! $jadwal) {
            return redirect()->to('/mengajar/riwayat/hari-terlewat')->with('error', 'Jadwal tidak ditemukan atau bukan milik Anda.');
        }

        $presensi = (new PresensiModel())->findByJadwalTanggal((int) $jadwalId, $tanggal);
        if (! $presensi) {
            return redirect()->to('/mengajar/riwayat/hari-terlewat/presensi/' . $jadwalId . '/' . $tanggal)->with('error', 'Isi presensinya dulu sebelum lanjut ke jurnal.');
        }

        $jurnalModel = new JurnalMengajarModel();
        if ($jurnalModel->findByJadwalTanggal((int) $jadwalId, $tanggal)) {
            return redirect()->to('/mengajar/riwayat')->with('error', 'Jurnal untuk tanggal ini sudah pernah diisi.');
        }

        $ok = $jurnalModel->insert([
            'jadwal_id'             => (int) $jadwalId,
            'tanggal'               => $tanggal,
            'materi'                => $this->request->getPost('materi'),
            'tujuan_pembelajaran'   => $this->request->getPost('tujuan_pembelajaran'),
            'metode'                => $this->request->getPost('metode'),
            'media'                 => $this->request->getPost('media'),
            'kegiatan_pembelajaran' => $this->request->getPost('kegiatan_pembelajaran'),
            'catatan'               => $this->request->getPost('catatan'),
            'kendala'               => $this->request->getPost('kendala'),
            'tindak_lanjut'         => $this->request->getPost('tindak_lanjut'),
            'locked_at'             => date('Y-m-d H:i:s'),
        ]);

        if (! $ok) {
            return redirect()->back()->withInput()->with('error', implode(' ', $jurnalModel->errors()));
        }

        $penilaianRows = $this->request->getPost('penilaian') ?? [];
        if (is_array($penilaianRows) && ! empty($penilaianRows)) {
            (new PenilaianHarianModel())->simpanUntukJurnal((int) $jurnalModel->getInsertID(), $penilaianRows);
        }

        (new PresensiModel())->update((int) $presensi['id'], ['locked_at' => date('Y-m-d H:i:s')]);

        (new AuditLogger())->log('isi_jurnal_hari_terlewat', "Melengkapi hari terlewat (presensi+jurnal) untuk jadwal #{$jadwalId} ({$jadwal['nama_mapel']}) tanggal {$tanggal}");

        return redirect()->to('/mengajar/riwayat/detail/' . $presensi['id'])->with('message', 'Presensi & jurnal untuk hari yang terlewat berhasil dilengkapi.');
    }
}
