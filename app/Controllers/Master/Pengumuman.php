<?php

namespace App\Controllers\Master;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\PengumumanModel;

class Pengumuman extends BaseController
{
    protected PengumumanModel $model;

    public function __construct()
    {
        $this->model = new PengumumanModel();
    }

    public function index()
    {
        $data = [
            'title'   => 'Pengumuman',
            'content' => view('master/pengumuman/index', [
                'items' => $this->model->getSemua(),
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function store()
    {
        $user = current_user();

        $ok = $this->model->insert([
            'judul'           => $this->request->getPost('judul'),
            'isi'             => $this->request->getPost('isi'),
            'tanggal_mulai'   => $this->request->getPost('tanggal_mulai'),
            'tanggal_selesai' => $this->request->getPost('tanggal_selesai') ?: null,
            'dibuat_oleh'     => $user['id'],
        ]);

        if (! $ok) {
            return redirect()->to('/master/pengumuman')->with('error', implode(' ', $this->model->errors()));
        }

        (new AuditLogger())->log('tambah_pengumuman', 'Menambah pengumuman: ' . $this->request->getPost('judul'));

        return redirect()->to('/master/pengumuman')->with('message', 'Pengumuman berhasil ditambahkan.');
    }

    public function update()
    {
        $id = (int) $this->request->getPost('id');

        $ok = $this->model->update($id, [
            'judul'           => $this->request->getPost('judul'),
            'isi'             => $this->request->getPost('isi'),
            'tanggal_mulai'   => $this->request->getPost('tanggal_mulai'),
            'tanggal_selesai' => $this->request->getPost('tanggal_selesai') ?: null,
        ]);

        if (! $ok) {
            return redirect()->to('/master/pengumuman')->with('error', implode(' ', $this->model->errors()));
        }

        (new AuditLogger())->log('ubah_pengumuman', 'Mengubah pengumuman #' . $id);

        return redirect()->to('/master/pengumuman')->with('message', 'Pengumuman berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->model->delete((int) $id);

        (new AuditLogger())->log('hapus_pengumuman', 'Menghapus pengumuman #' . $id);

        return redirect()->to('/master/pengumuman')->with('message', 'Pengumuman berhasil dihapus.');
    }
}
