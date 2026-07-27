<?php

namespace App\Controllers;

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
     * Dashboard utama, isinya menyesuaikan gabungan role yang dimiliki user:
     * - guru            -> jadwal hari ini + status (Schedule Driven System)
     * - administrator/operator -> statistik keseluruhan sekolah
     * - kepala_sekolah  -> monitoring real time jadwal hari ini
     * Kalau user punya lebih dari satu role, semua bagian yang relevan tampil sekaligus.
     */
    public function index()
    {
        $user  = current_user();
        $roles = $user['roles'];

        $data = [
            'title'   => 'Dashboard',
            'content' => view('dashboard/_content', [
                'user'        => $user,
                'jadwalGuru'  => in_array('guru', $roles, true) ? $this->jadwalHariIni((int) $user['id']) : null,
                'statsAdmin'  => array_intersect(['administrator', 'operator'], $roles) ? $this->statsAdministrator() : null,
                'statsKepsek' => in_array('kepala_sekolah', $roles, true) ? $this->statsKepalaSekolah() : null,
            ]),
        ];

        return view('layouts/main', $data);
    }

    /**
     * Susun jadwal hari ini milik guru yang login, lengkap dengan status
     * yang dihitung ulang setiap kali halaman dibuka (bukan disimpan permanen),
     * karena satu baris jadwal dipakai berulang setiap minggu.
     *
     * Juga memperhitungkan Tukar Jadwal: sesi yang jadwalnya digantikan orang
     * lain hari ini ditandai "digantikan" (tidak bisa diklik), dan sesi orang
     * lain yang DIA gantikan hari ini ikut muncul sebagai kartu tambahan.
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
        $items   = $hariIni
            ? (new JadwalModel())->getJadwalHariIniByGuru((int) $guru['id'], $hariIni, (int) $aktif['id'])
            : [];

        $presensiModel = new PresensiModel();
        $jurnalModel   = new JurnalMengajarModel();
        $tukarModel    = new TukarJadwalModel();
        $today         = date('Y-m-d');

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

    private function namaGuru(int $guruId): ?string
    {
        $g = (new GuruModel())->find($guruId);

        return $g['nama'] ?? null;
    }

    /**
     * Statistik untuk Administrator/Operator: total data master + aktivitas hari ini.
     */
    private function statsAdministrator(): array
    {
        $today = date('Y-m-d');

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
        ];
    }

    /**
     * Monitoring real time untuk Kepala Sekolah: progres jadwal hari ini se-sekolah.
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

        $jadwalHariIni = (new JadwalModel())
            ->select('jadwal.id')
            ->where('hari', $hariIni)
            ->where('semester_id', (int) $aktif['id'])
            ->findAll();

        $today         = date('Y-m-d');
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

        return $hariMap[(int) date('N')] ?? null;
    }
}
