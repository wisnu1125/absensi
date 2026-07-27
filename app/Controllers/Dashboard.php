<?php

namespace App\Controllers;

use App\Models\GuruModel;
use App\Models\JadwalModel;
use App\Models\JurnalMengajarModel;
use App\Models\PresensiModel;
use App\Models\SemesterModel;

class Dashboard extends BaseController
{
    /**
     * Dashboard utama. Kalau user punya role guru, bagian paling atas
     * menampilkan jadwal hari ini + status masing-masing (Belum Dimulai /
     * Sedang Berlangsung / Selesai) — inilah pusat dari Schedule Driven System.
     * Role lain (wali_kelas, kepala_sekolah, dst) masih placeholder, menyusul di fase berikutnya.
     */
    public function index()
    {
        $user = current_user();

        $data = [
            'title'   => 'Dashboard',
            'content' => view('dashboard/_content', [
                'user'       => $user,
                'jadwalGuru' => in_array('guru', $user['roles'], true) ? $this->jadwalHariIni((int) $user['id']) : null,
            ]),
        ];

        return view('layouts/main', $data);
    }

    /**
     * Susun jadwal hari ini milik guru yang login, lengkap dengan status
     * yang dihitung ulang setiap kali halaman dibuka (bukan disimpan permanen),
     * karena satu baris jadwal dipakai berulang setiap minggu.
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

        $hariMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => null];
        $hariIni = $hariMap[(int) date('N')] ?? null;

        $items = $hariIni
            ? (new JadwalModel())->getJadwalHariIniByGuru((int) $guru['id'], $hariIni, (int) $aktif['id'])
            : [];

        $presensiModel = new PresensiModel();
        $jurnalModel   = new JurnalMengajarModel();
        $today         = date('Y-m-d');

        foreach ($items as &$item) {
            $presensi = $presensiModel->findByJadwalTanggal((int) $item['id'], $today);
            $jurnal   = $jurnalModel->findByJadwalTanggal((int) $item['id'], $today);

            $item['status'] = match (true) {
                $presensi && $jurnal => 'selesai',
                (bool) $presensi     => 'berlangsung',
                default              => 'belum',
            };
        }
        unset($item);

        return ['guru' => $guru, 'aktif' => $aktif, 'hari' => $hariIni, 'items' => $items];
    }
}
