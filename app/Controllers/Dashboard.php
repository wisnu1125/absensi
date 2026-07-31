<?php

namespace App\Controllers;

use App\Libraries\DashboardAdminService;
use App\Libraries\ScheduleResolverService;
use App\Models\AgendaAkademikModel;
use App\Models\GuruModel;
use App\Models\JadwalModel;
use App\Models\JurnalMengajarModel;
use App\Models\KelasModel;
use App\Models\MataPelajaranModel;
use App\Models\PresensiDetailModel;
use App\Models\PresensiModel;
use App\Models\SemesterModel;
use App\Models\SiswaModel;
use App\Models\TukarJadwalModel;

class Dashboard extends BaseController
{
    /**
     * Dashboard utama — RINGKASAN saja (bukan daftar lengkap jadwal hari ini,
     * itu sekarang di halaman terpisah /jadwal-hari-ini sesuai permintaan agar
     * dua halaman ini punya isi yang beda, bukan duplikat).
     * Isinya menyesuaikan gabungan role yang dimiliki user:
     * - guru            -> ringkasan hari ini + progres semester + pengingat
     * - administrator/operator -> statistik sekolah + rincian per kelas hari ini
     * - kepala_sekolah  -> monitoring real time + rincian per kelas hari ini
     */
    public function index()
    {
        $user  = current_user();
        $roles = $user['roles'];

        $data = [
            'title'   => 'Dashboard',
            'content' => view('dashboard/_content', [
                'user'          => $user,
                'agendaHariIni' => $this->agendaHariIni(),
                'ringkasanGuru' => in_array('guru', $roles, true) ? $this->ringkasanGuru((int) $user['id']) : null,
                'statsAdmin'    => array_intersect(['administrator', 'operator'], $roles) ? $this->statsAdministrator() : null,
                'statsKepsek'   => in_array('kepala_sekolah', $roles, true) ? $this->statsKepalaSekolah() : null,
                'railKalender'  => $this->railKalender(),
                // Pengumuman TIDAK digantung di statsAdmin (yang cuma terisi utk
                // admin/operator) — sengaja diambil di sini SUPAYA tampil ke SEMUA
                // role, termasuk guru & kepala sekolah, bukan cuma admin.
                'pengumuman'    => (new \App\Models\PengumumanModel())->getAktif(),
            ]),
        ];

        return view('layouts/main', $data);
    }

    /**
     * Data untuk widget "Kalender Akademik" di sidebar kanan Dashboard —
     * kalender mini bulan berjalan + daftar event terdekat, memakai ulang
     * AgendaAkademikModel yang sama dengan modul Kalender Akademik penuh
     * (bukan query terpisah), supaya datanya selalu konsisten satu sama lain.
     */
    private function railKalender(): array
    {
        $bulan = (int) date('n');
        $tahun = (int) date('Y');
        $awalBulan  = sprintf('%04d-%02d-01', $tahun, $bulan);
        $akhirBulan = date('Y-m-t', strtotime($awalBulan));
        $n = (int) date('N', strtotime($awalBulan)) % 7;
        $awalGrid  = date('Y-m-d', strtotime($awalBulan . " -{$n} days"));
        $n2 = (int) date('N', strtotime($akhirBulan)) % 7;
        $akhirGrid = date('Y-m-d', strtotime($akhirBulan . ' +' . (6 - $n2) . ' days'));

        $model = new AgendaAkademikModel();

        return [
            'bulan'           => $bulan,
            'tahun'           => $tahun,
            'awalBulan'       => $awalBulan,
            'akhirBulan'      => $akhirBulan,
            'awalGrid'        => $awalGrid,
            'akhirGrid'       => $akhirGrid,
            'eventPerTanggal' => $model->getEventPerTanggal($awalGrid, $akhirGrid),
            'eventTerdekat'   => $model->getEventTerdekat(4),
        ];
    }

    /**
     * Agenda Kalender Akademik HARI INI (semua kategori KECUALI yang murni
     * penanda libur, itu sudah ditangani terpisah lewat pesan "Hari ini
     * libur" di bagian guru) — dipakai baris ringkas di atas Dashboard,
     * terlihat oleh SEMUA role, memenuhi permintaan "Dashboard menampilkan
     * informasi ujian/kegiatan" dari spesifikasi Kalender Akademik.
     */
    private function agendaHariIni(): array
    {
        $today = date('Y-m-d');

        return array_values(array_filter(
            (new AgendaAkademikModel())->getEventRentang($today, $today),
            static fn ($ev) => $ev['status'] !== 'dibatalkan' && $ev['dampak_presensi'] !== 'nonaktif'
        ));
    }

    /**
     * Halaman terpisah "Jadwal Hari Ini" — CUMA memuat jadwal hari ini, tidak
     * ada statistik atau konten lain, sesuai permintaan agar beda dari Dashboard.
     */
    public function jadwalHariIniPage()
    {
        $user = current_user();

        if (! in_array('guru', $user['roles'], true)) {
            return redirect()->to('/dashboard');
        }

        $data = [
            'title'   => 'Jadwal Hari Ini',
            'content' => view('dashboard/jadwal_hari_ini', [
                'jadwalGuru' => $this->jadwalHariIni((int) $user['id']),
            ]),
        ];

        return view('layouts/main', $data);
    }

    /**
     * Ringkasan guru untuk Dashboard — SENGAJA dibatasi cuma 3 bagian sesuai
     * permintaan: (1) jadwal hari ini, (2) jadwal minggu ini dalam bentuk
     * tabel, (3) notifikasi kalau ada presensi/jurnal yang belum diisi.
     * Tidak ada statistik semester atau tombol aksi tambahan lagi di sini.
     */
    private function ringkasanGuru(int $userId): array
    {
        $guru = (new GuruModel())->findByUserId($userId);

        if (! $guru) {
            return ['guru' => null];
        }

        $aktif = (new SemesterModel())->getActive();

        if (! $aktif) {
            return ['guru' => $guru, 'aktif' => null];
        }

        $hariIni = $this->jadwalHariIni($userId);

        return [
            'guru'           => $guru,
            'aktif'          => $aktif,
            'hariIni'        => $hariIni,
            'mingguan'       => $this->jadwalMingguanGuru((int) $guru['id'], $aktif),
            'progress'       => $this->progresGuruHariIni($hariIni['items'] ?? []),
            'riwayatTerbaru' => $this->riwayatTerbaruGuru((int) $guru['id']),
            'pengingat'      => $this->pengingatGuru((int) $guru['id'], $hariIni['items'] ?? []),
        ];
    }

    /**
     * Progress hari ini KHUSUS guru yang login: dari sesi2 dia hari ini,
     * berapa presensi/jurnal yang terisi, plus jumlah entri penilaian yang
     * dia buat hari ini. Dipakai kartu "Progress Hari Ini" di Dashboard.
     */
    private function progresGuruHariIni(array $items): array
    {
        $total          = count($items);
        $presensiTerisi = count(array_filter($items, static fn ($i) => in_array($i['status'], ['berlangsung', 'selesai'], true)));
        $jurnalTerisi   = count(array_filter($items, static fn ($i) => $i['status'] === 'selesai'));

        $jadwalIds = array_column($items, 'id');
        $jurnalIds = empty($jadwalIds) ? [] : array_column(
            (new JurnalMengajarModel())->whereIn('jadwal_id', $jadwalIds)->where('tanggal', date('Y-m-d'))->findAll(),
            'id'
        );
        $penilaianHariIni = empty($jurnalIds) ? 0 : (new \App\Models\PenilaianHarianModel())->whereIn('jurnal_id', $jurnalIds)->countAllResults();

        $persen = static fn ($a, $b) => $b > 0 ? round($a / $b * 100) : 0;

        return [
            'jadwal_total'      => $total,
            'presensi_terisi'   => $presensiTerisi,
            'presensi_persen'   => $persen($presensiTerisi, $total),
            'jurnal_terisi'     => $jurnalTerisi,
            'jurnal_persen'     => $persen($jurnalTerisi, $total),
            'penilaian_hari_ini'=> $penilaianHariIni,
        ];
    }

    /**
     * Aktivitas terbaru MILIK guru yang login sendiri (bukan seluruh
     * sekolah seperti versi Admin) — gabungan jurnal &amp; presensi 7 hari
     * terakhir, terbaru dulu, dipakai kartu "Riwayat Terbaru".
     */
    private function riwayatTerbaruGuru(int $guruId, int $limit = 5): array
    {
        $db      = \Config\Database::connect();
        $sejak   = date('Y-m-d', strtotime('-7 days'));

        $jurnal = $db->table('jurnal_mengajar jm')
            ->select('jm.created_at as waktu, "jurnal" as jenis, mp.nama as nama_mapel, k.nama_kelas', false)
            ->join('jadwal j', 'j.id = jm.jadwal_id')
            ->join('mata_pelajaran mp', 'mp.id = j.mapel_id')
            ->join('kelas k', 'k.id = j.kelas_id')
            ->where('j.guru_id', $guruId)
            ->where('jm.tanggal >=', $sejak)
            ->get()->getResultArray();

        $presensi = $db->table('presensi p')
            ->select('p.created_at as waktu, "presensi" as jenis, mp.nama as nama_mapel, k.nama_kelas', false)
            ->join('jadwal j', 'j.id = p.jadwal_id')
            ->join('mata_pelajaran mp', 'mp.id = j.mapel_id')
            ->join('kelas k', 'k.id = j.kelas_id')
            ->where('j.guru_id', $guruId)
            ->where('p.tanggal >=', $sejak)
            ->get()->getResultArray();

        $semua = array_merge($jurnal, $presensi);
        usort($semua, static fn ($a, $b) => strcmp((string) $b['waktu'], (string) $a['waktu']));

        return array_slice($semua, 0, $limit);
    }

    /**
     * Pengingat KHUSUS guru: jurnal hari ini yang belum diisi, sesi hari
     * ini yang jurnalnya sudah ada tapi belum satupun siswa dinilai, dan
     * tukar jadwal yang dia ajukan yang masih menunggu.
     */
    private function pengingatGuru(int $guruId, array $items): array
    {
        $today     = date('Y-m-d');
        $jurnalBelum = count(array_filter($items, static fn ($i) => $i['status'] === 'belum' && $i['jam_selesai'] <= date('H:i:s')));

        $jadwalIds = array_column($items, 'id');
        $jurnalHariIni = empty($jadwalIds) ? [] : (new JurnalMengajarModel())->whereIn('jadwal_id', $jadwalIds)->where('tanggal', $today)->findAll();
        $jurnalIds = array_column($jurnalHariIni, 'id');
        $jurnalIdsDenganPenilaian = empty($jurnalIds) ? [] : array_unique(array_column(
            (new \App\Models\PenilaianHarianModel())->whereIn('jurnal_id', $jurnalIds)->findAll(),
            'jurnal_id'
        ));
        $penilaianBelum = count(array_filter($jurnalHariIni, static fn ($j) => ! in_array($j['id'], $jurnalIdsDenganPenilaian, true)));

        return [
            'jurnal_belum'    => $jurnalBelum,
            'penilaian_belum' => $penilaianBelum,
            'tukar_menunggu'  => (new TukarJadwalModel())->hitungMenungguUntukGuru($guruId),
        ];
    }

    /**
     * Jadwal SATU MINGGU PENUH milik guru (Senin-Sabtu, minggu berjalan),
     * sudah lewat ScheduleResolverService jadi ikut memperhitungkan Tukar
     * Jadwal yang aktif minggu ini — dipakai tabel "Jadwal minggu ini" di
     * Dashboard, diurutkan hari lalu jam.
     */
    private function jadwalMingguanGuru(int $guruId, array $aktif): array
    {
        $today       = date('Y-m-d');
        $awalMinggu  = $this->awalMinggu($today);
        $hariList    = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $tanggalHari = [];
        foreach ($hariList as $i => $h) {
            $tanggalHari[$h] = date('Y-m-d', strtotime($awalMinggu . " +{$i} days"));
        }

        $jadwalSaya = (new JadwalModel())
            ->select('jadwal.*, mata_pelajaran.nama as nama_mapel, kelas.nama_kelas')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->where('jadwal.guru_id', $guruId)
            ->where('jadwal.semester_id', (int) $aktif['id'])
            ->findAll();

        $resolver = new ScheduleResolverService();
        $mingguan = [];
        foreach ($jadwalSaya as $item) {
            $tanggalMaster = $tanggalHari[$item['hari']] ?? null;
            if (! $tanggalMaster) {
                continue;
            }
            $mingguan[] = $resolver->terapkan($item, $tanggalMaster) + ['tanggal_master' => $tanggalMaster];
        }

        $urutanHari = array_flip($hariList);
        usort($mingguan, static function ($a, $b) use ($urutanHari) {
            $bedaHari = ($urutanHari[$a['hari']] ?? 99) <=> ($urutanHari[$b['hari']] ?? 99);

            return $bedaHari !== 0 ? $bedaHari : strcmp($a['jam_mulai'], $b['jam_mulai']);
        });

        return $mingguan;
    }

    /**
     * Susun jadwal hari ini milik guru yang login, lengkap dengan status
     * yang dihitung ulang setiap kali halaman dibuka (bukan disimpan permanen),
     * karena satu baris jadwal dipakai berulang setiap minggu.
     *
     * Memperhitungkan DUA mekanisme berbeda:
     * - Pertukaran Jadwal (jadwal_swap, lewat ScheduleResolverService): sesi yang
     *   HARI EFEKTIFnya hari ini (bisa jadi karena pindah dari hari lain akibat
     *   pertukaran) ikut ditampilkan; sesi yang hari MASTER-nya hari ini tapi
     *   sudah pindah ke hari lain otomatis TIDAK ditampilkan lagi.
     * - Tukar Jadwal / guru pengganti (tukar_jadwal): sesi yang digantikan orang
     *   lain hari ini ditandai "digantikan", dan sesi orang lain yang DIA
     *   gantikan hari ini ikut muncul sebagai kartu tambahan.
     */
    private function jadwalHariIni(int $userId): array
    {
        $guru = (new GuruModel())->findByUserId($userId);

        if (! $guru) {
            return ['guru' => null];
        }

        $aktif = (new SemesterModel())->getActive();

        if (! $aktif) {
            return ['guru' => $guru, 'aktif' => null, 'items' => []];
        }

        $hariIni = $this->namaHariIni();

        if (! $hariIni) {
            $today       = date('Y-m-d');
            $liburEvents = array_filter(
                (new AgendaAkademikModel())->getEventRentang($today, $today),
                static fn ($ev) => $ev['dampak_presensi'] === 'nonaktif' && $ev['status'] !== 'dibatalkan'
            );

            return ['guru' => $guru, 'aktif' => $aktif, 'hari' => null, 'items' => [], 'liburEvents' => array_values($liburEvents)];
        }

        $today      = date('Y-m-d');
        $awalMinggu = $this->awalMinggu($today);
        $hariList   = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $tanggalHari = [];
        foreach ($hariList as $i => $h) {
            $tanggalHari[$h] = date('Y-m-d', strtotime($awalMinggu . " +{$i} days"));
        }

        $semuaJadwalSaya = (new JadwalModel())
            ->select('jadwal.*, mata_pelajaran.nama as nama_mapel, kelas.nama_kelas')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->where('jadwal.guru_id', (int) $guru['id'])
            ->where('jadwal.semester_id', (int) $aktif['id'])
            ->findAll();

        $resolver = new ScheduleResolverService();
        $items    = [];

        foreach ($semuaJadwalSaya as $item) {
            $tanggalMaster = $tanggalHari[$item['hari']] ?? null;
            if (! $tanggalMaster) {
                continue;
            }

            $efektif = $resolver->terapkan($item, $tanggalMaster);

            // Cuma yang HARI EFEKTIFnya hari ini yang tampil di "jadwal hari ini" —
            // baik yang normal (tidak ditukar) maupun yang PINDAH ke hari ini
            // akibat pertukaran jadwal.
            if ($efektif['hari'] !== $hariIni) {
                continue;
            }

            $items[] = $efektif;
        }

        $presensiModel = new PresensiModel();
        $jurnalModel   = new JurnalMengajarModel();
        $tukarModel    = new TukarJadwalModel();

        foreach ($items as &$item) {
            $swap = $tukarModel->getDisetujui((int) $item['id'], $today);

            if ($swap) {
                $item['status']         = 'digantikan';
                $item['nama_pengganti'] = $this->namaGuru((int) $swap['guru_pengganti_id']);
                continue;
            }

            $presensi = $presensiModel->findByJadwalTanggal((int) $item['id'], $today);
            $jurnal   = $jurnalModel->findByJadwalTanggal((int) $item['id'], $today);

            $item['status'] = match (true) {
                $presensi && $jurnal => 'selesai',
                (bool) $presensi     => 'berlangsung',
                default              => 'belum',
            };
        }
        unset($item);

        // Sesi milik guru LAIN yang hari ini digantikan oleh guru yang sedang login.
        $penggantian = $tukarModel->getDisetujuiUntukPengganti((int) $guru['id'], $today, $today);
        foreach ($penggantian as $p) {
            $presensi = $presensiModel->findByJadwalTanggal((int) $p['jadwal_id'], $today);
            $jurnal   = $jurnalModel->findByJadwalTanggal((int) $p['jadwal_id'], $today);

            $items[] = [
                'id'             => $p['jadwal_id'],
                'jam_mulai'      => $p['jam_mulai'],
                'jam_selesai'    => $p['jam_selesai'],
                'nama_kelas'     => $p['nama_kelas'],
                'nama_mapel'     => $p['nama_mapel'],
                'status'         => match (true) {
                    $presensi && $jurnal => 'selesai',
                    (bool) $presensi     => 'berlangsung',
                    default              => 'belum',
                },
                'menggantikan'   => $p['nama_guru_asal'],
            ];
        }

        return ['guru' => $guru, 'aktif' => $aktif, 'hari' => $hariIni, 'items' => $items];
    }

    private function awalMinggu(string $tanggal): string
    {
        $n = (int) date('N', strtotime($tanggal));

        return date('Y-m-d', strtotime($tanggal . ' -' . ($n - 1) . ' days'));
    }

    private function namaGuru(int $guruId): ?string
    {
        $g = (new GuruModel())->find($guruId);

        return $g['nama'] ?? null;
    }

    /**
     * Statistik untuk Administrator/Operator: total data master + aktivitas hari ini,
     * PLUS rincian per kelas siapa yang sudah/belum isi (bukan cuma angka agregat).
     */
    private function statsAdministrator(): array
    {
        $today  = date('Y-m-d');
        $aktif  = (new \App\Models\SemesterModel())->getActive();
        $lengkap = $aktif ? (new DashboardAdminService())->build((int) $aktif['id']) : null;

        return [
            'total_guru'          => (new GuruModel())->countAllResults(),
            'total_siswa'         => (new SiswaModel())->where('status', 'aktif')->countAllResults(),
            'total_kelas'         => (new KelasModel())->countAllResults(),
            'total_jadwal'        => (new JadwalModel())->countAllResults(),
            'presensi_hari_ini'   => (new PresensiModel())->where('tanggal', $today)->countAllResults(),
            'jurnal_hari_ini'     => (new JurnalMengajarModel())->where('tanggal', $today)->countAllResults(),
            'rekap_hari_ini'      => $this->rekapPresensiTanggal($today),
            'tukar_menunggu'      => (new TukarJadwalModel())->hitungMenunggu(),
            'tukar_hari_ini'      => (new TukarJadwalModel())->where('tanggal', $today)->countAllResults(),
            'detail_hari_ini'     => $this->detailAktivitasHariIni(),
            'lengkap'             => $lengkap,
        ];
    }

    /**
     * Rincian TIAP sesi yang dijadwalkan hari ini se-sekolah (jadwal EFEKTIF,
     * ikut memperhitungkan Tukar Jadwal yang aktif) — guru, kelas, mapel, jam,
     * dan status (Sudah/Sedang berlangsung/Belum). Dipakai Admin &amp; Kepala
     * Sekolah supaya bisa lihat LANGSUNG siapa yang sudah isi dan siapa yang
     * belum, bukan cuma jumlah agregat.
     */
    private function detailAktivitasHariIni(): array
    {
        $aktif = (new SemesterModel())->getActive();
        if (! $aktif) {
            return [];
        }

        $hariIni = $this->namaHariIni();
        if (! $hariIni) {
            return [];
        }

        $today         = date('Y-m-d');
        $jadwalHariIni = (new ScheduleResolverService())->jadwalEfektifSekolah((int) $aktif['id'], $today);

        $presensiModel = new PresensiModel();
        $jurnalModel   = new JurnalMengajarModel();
        $tukarModel    = new TukarJadwalModel();

        $detail = [];
        foreach ($jadwalHariIni as $j) {
            $swap = $tukarModel->getDisetujui((int) $j['id'], $today);

            if ($swap) {
                $detail[] = [
                    'nama_guru'  => $j['nama_guru'],
                    'nama_kelas' => $j['nama_kelas'],
                    'nama_mapel' => $j['nama_mapel'],
                    'jam_mulai'  => $j['jam_mulai'],
                    'jam_selesai'=> $j['jam_selesai'],
                    'status'     => 'digantikan',
                    'keterangan' => 'Digantikan ' . ($this->namaGuru((int) $swap['guru_pengganti_id']) ?? '-'),
                ];

                continue;
            }

            $presensi = $presensiModel->findByJadwalTanggal((int) $j['id'], $today);
            $jurnal   = $jurnalModel->findByJadwalTanggal((int) $j['id'], $today);

            $detail[] = [
                'nama_guru'  => $j['nama_guru'],
                'nama_kelas' => $j['nama_kelas'],
                'nama_mapel' => $j['nama_mapel'],
                'jam_mulai'  => $j['jam_mulai'],
                'jam_selesai'=> $j['jam_selesai'],
                'status'     => match (true) {
                    $presensi && $jurnal => 'selesai',
                    (bool) $presensi     => 'berlangsung',
                    default              => 'belum',
                },
                'keterangan' => null,
            ];
        }

        // Urutkan: yang paling butuh perhatian (belum) di atas, lalu berlangsung,
        // baru selesai/digantikan — dalam grup yang sama urutkan berdasar jam.
        $urutanStatus = ['belum' => 0, 'berlangsung' => 1, 'selesai' => 2, 'digantikan' => 3];
        usort($detail, static function ($a, $b) use ($urutanStatus) {
            $bedaStatus = ($urutanStatus[$a['status']] ?? 9) <=> ($urutanStatus[$b['status']] ?? 9);

            return $bedaStatus !== 0 ? $bedaStatus : strcmp($a['jam_mulai'], $b['jam_mulai']);
        });

        return $detail;
    }

    /**
     * Monitoring real time untuk Kepala Sekolah: progres jadwal EFEKTIF hari ini
     * se-sekolah (lewat ScheduleResolverService, jadi ikut memperhitungkan
     * Pertukaran Jadwal yang sedang aktif hari ini), plus rincian per kelas.
     */
    private function statsKepalaSekolah(): array
    {
        $aktif = (new SemesterModel())->getActive();

        if (! $aktif) {
            return ['aktif' => null];
        }

        $hariIni = $this->namaHariIni();

        if (! $hariIni) {
            return ['aktif' => $aktif, 'hariIni' => null];
        }

        $today         = date('Y-m-d');
        $jadwalHariIni = (new ScheduleResolverService())->jadwalEfektifSekolah((int) $aktif['id'], $today);

        $presensiModel = new PresensiModel();
        $jurnalModel   = new JurnalMengajarModel();

        $sedangMengajar  = 0;
        $selesaiMengajar = 0;
        $belumPresensi   = 0;
        $belumJurnal     = 0;

        foreach ($jadwalHariIni as $j) {
            $presensi = $presensiModel->findByJadwalTanggal((int) $j['id'], $today);
            $jurnal   = $jurnalModel->findByJadwalTanggal((int) $j['id'], $today);

            if ($presensi && $jurnal) {
                $selesaiMengajar++;
            } elseif ($presensi) {
                $sedangMengajar++;
                $belumJurnal++;
            } else {
                $belumPresensi++;
            }
        }

        $rekap         = $this->rekapPresensiTanggal($today);
        $totalPresensi = array_sum($rekap);
        $persenHadir   = $totalPresensi > 0 ? round($rekap['hadir'] / $totalPresensi * 100) : 0;

        return [
            'aktif'                 => $aktif,
            'hariIni'               => $hariIni,
            'total_jadwal_hari_ini' => count($jadwalHariIni),
            'sedang_mengajar'       => $sedangMengajar,
            'selesai_mengajar'      => $selesaiMengajar,
            'belum_presensi'        => $belumPresensi,
            'belum_jurnal'          => $belumJurnal,
            'persen_hadir'          => $persenHadir,
            'rekap'                 => $rekap,
            'tukar_hari_ini'        => (new TukarJadwalModel())->where('tanggal', $today)->countAllResults(),
            'detail_hari_ini'       => $this->detailAktivitasHariIni(),
        ];
    }

    /**
     * Rekap jumlah presensi per status untuk satu tanggal, se-sekolah (semua kelas/guru).
     */
    private function rekapPresensiTanggal(string $tanggal): array
    {
        $rows = (new PresensiDetailModel())
            ->select('presensi_detail.status')
            ->join('presensi', 'presensi.id = presensi_detail.presensi_id')
            ->where('presensi.tanggal', $tanggal)
            ->findAll();

        $rekap = array_fill_keys(PresensiDetailModel::STATUS_VALID, 0);
        foreach ($rows as $r) {
            $rekap[$r['status']] = ($rekap[$r['status']] ?? 0) + 1;
        }

        return $rekap;
    }

    private function namaHariIni(): ?string
    {
        $hariMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => null];

        // Hari sekolah efektif: BUKAN Minggu, DAN bukan hari libur (event
        // Kalender Akademik berdampak nonaktif) — kalau libur, dianggap sama
        // seperti akhir pekan: tidak ada "hari ini" yang aktif.
        if (is_hari_nonaktif()) {
            return null;
        }

        return $hariMap[(int) date('N')] ?? null;
    }
}
