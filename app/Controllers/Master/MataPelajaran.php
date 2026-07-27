<?php

namespace App\Controllers\Master;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\MataPelajaranModel;

class MataPelajaran extends BaseController
{
    protected MataPelajaranModel $model;

    public function __construct()
    {
        $this->model = new MataPelajaranModel();
    }

    /**
     * Satu-satunya halaman untuk modul ini: tabel + modal tambah/edit.
     * Tidak ada halaman create/edit terpisah — sesuai prinsip "minim klik".
     */
    public function index()
    {
        $data = [
            'title'   => 'Mata Pelajaran',
            'content' => view('master/mata_pelajaran/index', [
                'items' => $this->model->orderBy('nama', 'ASC')->findAll(),
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function store()
    {
        // insert()/update() Model CI4 otomatis validasi ke $validationRules di Model
        // dan return false kalau tidak lolos — tidak perlu panggil $this->validate() manual.
        $ok = $this->model->insert([
            'kode' => strtoupper((string) $this->request->getPost('kode')),
            'nama' => $this->request->getPost('nama'),
        ]);

        if (! $ok) {
            return redirect()->to('/master/mata-pelajaran')->with('error', implode(' ', $this->model->errors()));
        }

        (new AuditLogger())->log('tambah_mapel', 'Menambah mata pelajaran: ' . $this->request->getPost('nama'));

        return redirect()->to('/master/mata-pelajaran')->with('message', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function update()
    {
        $id = (int) $this->request->getPost('id');

        $ok = $this->model->update($id, [
            'kode' => strtoupper((string) $this->request->getPost('kode')),
            'nama' => $this->request->getPost('nama'),
        ]);

        if (! $ok) {
            return redirect()->to('/master/mata-pelajaran')->with('error', implode(' ', $this->model->errors()));
        }

        (new AuditLogger())->log('ubah_mapel', 'Mengubah mata pelajaran #' . $id);

        return redirect()->to('/master/mata-pelajaran')->with('message', 'Mata pelajaran berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->model->delete((int) $id);

        (new AuditLogger())->log('hapus_mapel', 'Menghapus mata pelajaran #' . $id);

        return redirect()->to('/master/mata-pelajaran')->with('message', 'Mata pelajaran berhasil dihapus.');
    }
}
