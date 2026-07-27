<?php

namespace App\Controllers\Master;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\GuruModel;
use App\Models\KelasModel;
use App\Models\TahunAjaranModel;
use App\Models\WaliKelasModel;

class Kelas extends BaseController
{
    protected KelasModel $model;

    public function __construct()
    {
        $this->model = new KelasModel();
    }

    public function index()
    {
        $tahunAjaranModel = new TahunAjaranModel();

        $data = [
            'title'   => 'Kelas',
            'content' => view('master/kelas/index', [
                'items'       => $this->model->getWithTahunAjaran(),
                'tahunAjaran' => $tahunAjaranModel->orderBy('nama', 'DESC')->findAll(),
                'waliKelas'   => (new GuruModel())->getDenganRoleWaliKelas(),
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function store()
    {
        $tahunAjaranId = (int) $this->request->getPost('tahun_ajaran_id');

        $ok = $this->model->insert([
            'tahun_ajaran_id' => $tahunAjaranId,
            'nama_kelas'      => $this->request->getPost('nama_kelas'),
            'tingkat'         => $this->request->getPost('tingkat'),
        ]);

        if (! $ok) {
            return redirect()->to('/master/kelas')->with('error', implode(' ', $this->model->errors()));
        }

        $kelasId = $this->model->getInsertID();
        $this->simpanWaliKelas($kelasId, $tahunAjaranId);

        (new AuditLogger())->log('tambah_kelas', 'Menambah kelas: ' . $this->request->getPost('nama_kelas'));

        return redirect()->to('/master/kelas')->with('message', 'Kelas berhasil ditambahkan.');
    }

    public function update()
    {
        $id            = (int) $this->request->getPost('id');
        $tahunAjaranId = (int) $this->request->getPost('tahun_ajaran_id');

        $ok = $this->model->update($id, [
            'tahun_ajaran_id' => $tahunAjaranId,
            'nama_kelas'      => $this->request->getPost('nama_kelas'),
            'tingkat'         => $this->request->getPost('tingkat'),
        ]);

        if (! $ok) {
            return redirect()->to('/master/kelas')->with('error', implode(' ', $this->model->errors()));
        }

        $this->simpanWaliKelas($id, $tahunAjaranId);

        (new AuditLogger())->log('ubah_kelas', 'Mengubah kelas #' . $id);

        return redirect()->to('/master/kelas')->with('message', 'Kelas berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->model->delete((int) $id);

        (new AuditLogger())->log('hapus_kelas', 'Menghapus kelas #' . $id);

        return redirect()->to('/master/kelas')->with('message', 'Kelas berhasil dihapus.');
    }

    /**
     * Set/lepas wali kelas dari field "wali_kelas_id" pada form yang sama.
     * Kosong = tidak punya wali kelas.
     */
    private function simpanWaliKelas(int $kelasId, int $tahunAjaranId): void
    {
        $guruId = $this->request->getPost('wali_kelas_id');

        (new WaliKelasModel())->setWaliKelas($kelasId, $tahunAjaranId, $guruId ? (int) $guruId : null);
    }
}
