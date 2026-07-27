<?php

namespace App\Controllers;

use App\Models\GuruModel;
use App\Models\PresensiDetailModel;
use App\Models\SiswaModel;
use App\Models\TahunAjaranModel;
use App\Models\WaliKelasModel;

/**
 * Halaman kerja wali kelas: rekap kehadiran & data siswa untuk SATU kelas yang
 * jadi tanggung jawabnya (ditentukan admin lewat menu Kelas -> field Wali Kelas).
 */
class WaliKelas extends BaseController
{
    public function index()
    {
        $guru = (new GuruModel())->findByUserId((int) session()->get('user_id'));

        if (! $guru) {
            return $this->tampilKosong('akun');
        }

        $tahunAktif = (new TahunAjaranModel())->getActive();

        if (! $tahunAktif) {
            return $this->tampilKosong('tahun_ajaran');
        }

        $penugasan = (new WaliKelasModel())->getByGuruTahun((int) $guru['id'], (int) $tahunAktif['id']);

        if (! $penugasan) {
            return $this->tampilKosong('belum_ditugaskan');
        }

        $kelasId = (int) $penugasan['kelas_id'];
        $filter  = $this->ambilFilter();
        $siswa   = (new SiswaModel())->getWithKelas($kelasId);
        $baris   = $this->queryRekap($kelasId, $filter);

        $data = [
            'title'   => 'Wali Kelas — ' . $penugasan['nama_kelas'],
            'content' => view('wali_kelas/index', [
                'kelas'         => $penugasan,
                'siswa'         => $siswa,
                'filter'        => $filter,
                'rekap'         => $this->rekapStatus($baris),
                'rekapPerSiswa' => $this->rekapPerSiswa($baris, $siswa),
            ]),
        ];

        return view('layouts/main', $data);
    }

    private function tampilKosong(string $alasan)
    {
        $data = [
            'title'   => 'Wali Kelas',
            'content' => view('wali_kelas/kosong', ['alasan' => $alasan]),
        ];

        return view('layouts/main', $data);
    }

    private function ambilFilter(): array
    {
        return [
            'tanggal_dari'   => $this->request->getGet('tanggal_dari') ?: date('Y-m-01'),
            'tanggal_sampai' => $this->request->getGet('tanggal_sampai') ?: date('Y-m-d'),
        ];
    }

    private function queryRekap(int $kelasId, array $filter): array
    {
        return (new PresensiDetailModel())
            ->select('presensi_detail.status, presensi_detail.siswa_id, presensi.tanggal')
            ->join('presensi', 'presensi.id = presensi_detail.presensi_id')
            ->join('siswa', 'siswa.id = presensi_detail.siswa_id')
            ->where('siswa.kelas_id', $kelasId)
            ->where('presensi.tanggal >=', $filter['tanggal_dari'])
            ->where('presensi.tanggal <=', $filter['tanggal_sampai'])
            ->findAll();
    }

    private function rekapStatus(array $rows): array
    {
        $rekap = array_fill_keys(PresensiDetailModel::STATUS_VALID, 0);
        foreach ($rows as $r) {
            $rekap[$r['status']] = ($rekap[$r['status']] ?? 0) + 1;
        }

        return $rekap;
    }

    /**
     * Rekap per siswa (jumlah tiap status), dipakai untuk tabel "Data siswa per kelas"
     * plus rekapnya sekaligus — jadi wali kelas langsung lihat siapa yang sering alpha dsb.
     */
    private function rekapPerSiswa(array $rows, array $siswa): array
    {
        $perSiswa = [];

        foreach ($siswa as $s) {
            $perSiswa[$s['id']] = [
                'nama'      => $s['nama'],
                'nis'       => $s['nis'],
                'hadir'     => 0,
                'sakit'     => 0,
                'izin'      => 0,
                'alpha'     => 0,
                'terlambat' => 0,
            ];
        }

        foreach ($rows as $r) {
            if (isset($perSiswa[$r['siswa_id']][$r['status']])) {
                $perSiswa[$r['siswa_id']][$r['status']]++;
            }
        }

        return $perSiswa;
    }
}
