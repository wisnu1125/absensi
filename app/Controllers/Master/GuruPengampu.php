<?php

namespace App\Controllers\Master;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\GuruModel;
use App\Models\GuruPengampuModel;
use App\Models\KelasModel;
use App\Models\MataPelajaranModel;

/**
 * Guru Pengampu — relasi Guru x Mata Pelajaran x Tingkat. Fondasi baru yang
 * menentukan siapa berhak mengajar apa di tingkat mana; dipakai form Tambah
 * Jadwal supaya penjadwalan tidak bisa asal pilih guru+mapel bebas lagi.
 */
class GuruPengampu extends BaseController
{
    protected GuruPengampuModel $model;

    public function __construct()
    {
        $this->model = new GuruPengampuModel();
    }

    public function index()
    {
        $data = [
            'title'   => 'Guru Pengampu',
            'content' => view('master/guru_pengampu/index', [
                'items'         => $this->model->getSemuaLengkap(),
                'daftarGuru'    => (new GuruModel())->where('status', 'aktif')->orderBy('nama', 'ASC')->findAll(),
                'daftarMapel'   => (new MataPelajaranModel())->orderBy('nama', 'ASC')->findAll(),
                'daftarTingkat' => $this->daftarTingkat(),
            ]),
        ];

        return view('layouts/main', $data);
    }

    /**
     * Tingkat diambil DINAMIS dari data kelas yang sudah ada (bukan di-hardcode
     * VII/VIII/IX) — supaya cocok utk jenjang apa pun (SD, SMA, dst), sesuai
     * apa yang sekolah ini sudah pakai di menu Kelas.
     */
    private function daftarTingkat(): array
    {
        $tingkat = array_column((new KelasModel())->select('tingkat')->distinct()->orderBy('tingkat', 'ASC')->findAll(), 'tingkat');

        return array_values(array_filter($tingkat));
    }

    public function store()
    {
        $guruId  = (int) $this->request->getPost('guru_id');
        $mapelId = (int) $this->request->getPost('mapel_id');
        $tingkat = $this->request->getPost('tingkat');

        if ($this->model->sudahAda($guruId, $mapelId, $tingkat)) {
            return redirect()->to('/master/guru-pengampu')->with('error', 'Kombinasi guru, mata pelajaran, dan tingkat ini sudah terdaftar.');
        }

        $ok = $this->model->insert(['guru_id' => $guruId, 'mapel_id' => $mapelId, 'tingkat' => $tingkat]);

        if (! $ok) {
            return redirect()->to('/master/guru-pengampu')->with('error', implode(' ', $this->model->errors()));
        }

        $guru  = (new GuruModel())->find($guruId);
        $mapel = (new MataPelajaranModel())->find($mapelId);
        (new AuditLogger())->log('tambah_guru_pengampu', "Menambah pengampu: {$guru['nama']} - {$mapel['nama']} - Tingkat {$tingkat}");

        return redirect()->to('/master/guru-pengampu')->with('message', 'Guru Pengampu berhasil ditambahkan.');
    }

    public function update()
    {
        $id      = (int) $this->request->getPost('id');
        $guruId  = (int) $this->request->getPost('guru_id');
        $mapelId = (int) $this->request->getPost('mapel_id');
        $tingkat = $this->request->getPost('tingkat');

        if ($this->model->sudahAda($guruId, $mapelId, $tingkat, $id)) {
            return redirect()->to('/master/guru-pengampu')->with('error', 'Kombinasi guru, mata pelajaran, dan tingkat ini sudah dipakai baris lain.');
        }

        $ok = $this->model->update($id, ['id' => $id, 'guru_id' => $guruId, 'mapel_id' => $mapelId, 'tingkat' => $tingkat]);

        if (! $ok) {
            return redirect()->to('/master/guru-pengampu')->with('error', implode(' ', $this->model->errors()));
        }

        (new AuditLogger())->log('ubah_guru_pengampu', 'Mengubah Guru Pengampu #' . $id);

        return redirect()->to('/master/guru-pengampu')->with('message', 'Guru Pengampu berhasil diperbarui.');
    }

    public function delete($id)
    {
        if ($this->model->dipakaiJadwal((int) $id)) {
            return redirect()->to('/master/guru-pengampu')->with('error', 'Guru Pengampu ini tidak bisa dihapus karena masih dipakai jadwal yang ada.');
        }

        $this->model->delete((int) $id);

        (new AuditLogger())->log('hapus_guru_pengampu', 'Menghapus Guru Pengampu #' . $id);

        return redirect()->to('/master/guru-pengampu')->with('message', 'Guru Pengampu berhasil dihapus.');
    }
}
