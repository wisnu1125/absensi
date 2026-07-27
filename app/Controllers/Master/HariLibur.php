<?php

namespace App\Controllers\Master;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\HariLiburModel;

class HariLibur extends BaseController
{
    protected HariLiburModel $model;

    public function __construct()
    {
        $this->model = new HariLiburModel();
    }

    public function index()
    {
        $data = [
            'title'   => 'Hari Libur',
            'content' => view('master/hari_libur/index', [
                'items' => $this->model->orderBy('tanggal', 'DESC')->findAll(),
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function store()
    {
        $ok = $this->model->insert([
            'tanggal'    => $this->request->getPost('tanggal'),
            'keterangan' => $this->request->getPost('keterangan'),
        ]);

        if (! $ok) {
            return redirect()->to('/master/hari-libur')->with('error', implode(' ', $this->model->errors()));
        }

        (new AuditLogger())->log('tambah_hari_libur', 'Menambah hari libur: ' . $this->request->getPost('tanggal'));

        return redirect()->to('/master/hari-libur')->with('message', 'Hari libur berhasil ditambahkan.');
    }

    public function update()
    {
        $id = (int) $this->request->getPost('id');

        $ok = $this->model->update($id, [
            'tanggal'    => $this->request->getPost('tanggal'),
            'keterangan' => $this->request->getPost('keterangan'),
        ]);

        if (! $ok) {
            return redirect()->to('/master/hari-libur')->with('error', implode(' ', $this->model->errors()));
        }

        (new AuditLogger())->log('ubah_hari_libur', 'Mengubah hari libur #' . $id);

        return redirect()->to('/master/hari-libur')->with('message', 'Hari libur berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->model->delete((int) $id);

        (new AuditLogger())->log('hapus_hari_libur', 'Menghapus hari libur #' . $id);

        return redirect()->to('/master/hari-libur')->with('message', 'Hari libur berhasil dihapus.');
    }
}
