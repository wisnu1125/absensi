<?php

namespace App\Controllers\Master;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\JamPelajaranModel;

/**
 * Setiap sekolah punya ketentuan jam masuk & durasi jam pelajaran yang berbeda,
 * jadi ini WAJIB bisa diatur sendiri lewat UI, bukan sekadar data bawaan di
 * schema.sql. Data seed di schema.sql hanya contoh awal supaya aplikasi bisa
 * langsung dicoba — sekolah tetap perlu menyesuaikannya di sini.
 */
class JamPelajaran extends BaseController
{
    protected JamPelajaranModel $model;

    public function __construct()
    {
        $this->model = new JamPelajaranModel();
    }

    public function index()
    {
        $data = [
            'title'   => 'Jam Pelajaran',
            'content' => view('master/jam_pelajaran/index', [
                'dikelompokkan' => $this->model->getSemuaDikelompokkan(),
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function store()
    {
        $hari       = $this->request->getPost('hari');
        $jamKe      = (int) $this->request->getPost('jam_ke');
        $jamMulai   = $this->request->getPost('jam_mulai');
        $jamSelesai = $this->request->getPost('jam_selesai');

        if ($jamSelesai <= $jamMulai) {
            return redirect()->to('/master/jam-pelajaran')->with('error', 'Jam selesai harus lebih besar dari jam mulai.');
        }

        if ($this->model->sudahAda($hari, $jamKe)) {
            return redirect()->to('/master/jam-pelajaran')->with('error', "Jam ke-{$jamKe} untuk hari {$hari} sudah ada, pilih nomor lain atau edit yang sudah ada.");
        }

        $bentrok = $this->model->cekBentrok($hari, $jamMulai, $jamSelesai);
        if ($bentrok) {
            return redirect()->to('/master/jam-pelajaran')->with('error', sprintf(
                'Jam %s–%s bentrok dengan jam ke-%d (%s–%s) yang sudah ada di hari %s.',
                substr($jamMulai, 0, 5),
                substr($jamSelesai, 0, 5),
                $bentrok['jam_ke'],
                substr($bentrok['jam_mulai'], 0, 5),
                substr($bentrok['jam_selesai'], 0, 5),
                $hari
            ));
        }

        $ok = $this->model->insert([
            'hari'        => $hari,
            'jam_ke'      => $jamKe,
            'jam_mulai'   => $jamMulai,
            'jam_selesai' => $jamSelesai,
        ]);

        if (! $ok) {
            return redirect()->to('/master/jam-pelajaran')->with('error', implode(' ', $this->model->errors()));
        }

        (new AuditLogger())->log('tambah_jam_pelajaran', "Menambah jam pelajaran ke-{$jamKe} hari {$hari}");

        return redirect()->to('/master/jam-pelajaran')->with('message', 'Jam pelajaran berhasil ditambahkan.');
    }

    public function update()
    {
        $id         = (int) $this->request->getPost('id');
        $hari       = $this->request->getPost('hari');
        $jamKe      = (int) $this->request->getPost('jam_ke');
        $jamMulai   = $this->request->getPost('jam_mulai');
        $jamSelesai = $this->request->getPost('jam_selesai');

        if ($jamSelesai <= $jamMulai) {
            return redirect()->to('/master/jam-pelajaran')->with('error', 'Jam selesai harus lebih besar dari jam mulai.');
        }

        if ($this->model->sudahAda($hari, $jamKe, $id)) {
            return redirect()->to('/master/jam-pelajaran')->with('error', "Jam ke-{$jamKe} untuk hari {$hari} sudah dipakai baris lain.");
        }

        $bentrok = $this->model->cekBentrok($hari, $jamMulai, $jamSelesai, $id);
        if ($bentrok) {
            return redirect()->to('/master/jam-pelajaran')->with('error', sprintf(
                'Jam %s–%s bentrok dengan jam ke-%d (%s–%s) yang sudah ada di hari %s.',
                substr($jamMulai, 0, 5),
                substr($jamSelesai, 0, 5),
                $bentrok['jam_ke'],
                substr($bentrok['jam_mulai'], 0, 5),
                substr($bentrok['jam_selesai'], 0, 5),
                $hari
            ));
        }

        $ok = $this->model->update($id, [
            'hari'        => $hari,
            'jam_ke'      => $jamKe,
            'jam_mulai'   => $jamMulai,
            'jam_selesai' => $jamSelesai,
        ]);

        if (! $ok) {
            return redirect()->to('/master/jam-pelajaran')->with('error', implode(' ', $this->model->errors()));
        }

        (new AuditLogger())->log('ubah_jam_pelajaran', 'Mengubah jam pelajaran #' . $id);

        return redirect()->to('/master/jam-pelajaran')->with('message', 'Jam pelajaran berhasil diperbarui.');
    }

    public function delete($id)
    {
        $jam = $this->model->find((int) $id);

        if ($jam && $this->model->dipakaiJadwal($jam['hari'], (int) $jam['jam_ke'])) {
            return redirect()->to('/master/jam-pelajaran')->with('error', "Jam ke-{$jam['jam_ke']} hari {$jam['hari']} tidak bisa dihapus karena masih dipakai di jadwal mengajar.");
        }

        $this->model->delete((int) $id);

        (new AuditLogger())->log('hapus_jam_pelajaran', 'Menghapus jam pelajaran #' . $id);

        return redirect()->to('/master/jam-pelajaran')->with('message', 'Jam pelajaran berhasil dihapus.');
    }
}
