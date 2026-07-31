<?php

namespace App\Controllers\Master;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\SemesterModel;
use App\Models\TahunAjaranModel;

class TahunAjaran extends BaseController
{
    protected TahunAjaranModel $model;
    protected SemesterModel $semesterModel;

    public function __construct()
    {
        $this->model         = new TahunAjaranModel();
        $this->semesterModel = new SemesterModel();
    }

    /**
     * Satu halaman menampilkan tahun ajaran DAN semester sekaligus,
     * karena keduanya selalu dikelola bersamaan (semester adalah anak dari tahun ajaran).
     */
    public function index()
    {
        $data = [
            'title'   => 'Tahun Ajaran & Semester',
            'content' => view('master/tahun_ajaran/index', [
                'tahunAjaran' => $this->model->orderBy('nama', 'DESC')->findAll(),
                'semester'    => $this->semesterModel->getWithTahunAjaran(),
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function store()
    {
        $ok = $this->model->insert(['nama' => $this->request->getPost('nama'), 'is_active' => 0]);

        if (! $ok) {
            return redirect()->to('/master/tahun-ajaran')->with('error', implode(' ', $this->model->errors()));
        }

        (new AuditLogger())->log('tambah_tahun_ajaran', 'Menambah tahun ajaran: ' . $this->request->getPost('nama'));

        return redirect()->to('/master/tahun-ajaran')->with('message', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function setActive($id)
    {
        $this->model->setActive((int) $id);

        (new AuditLogger())->log('aktifkan_tahun_ajaran', 'Mengaktifkan tahun ajaran #' . $id);

        return redirect()->to('/master/tahun-ajaran')->with('message', 'Tahun ajaran aktif berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->model->delete((int) $id);

        (new AuditLogger())->log('hapus_tahun_ajaran', 'Menghapus tahun ajaran #' . $id);

        return redirect()->to('/master/tahun-ajaran')->with('message', 'Tahun ajaran berhasil dihapus.');
    }

    // ---------------- Semester ----------------

    public function storeSemester()
    {
        $ok = $this->semesterModel->insert([
            'tahun_ajaran_id' => (int) $this->request->getPost('tahun_ajaran_id'),
            'nama'            => $this->request->getPost('nama'),
            'tanggal_mulai'   => $this->request->getPost('tanggal_mulai') ?: null,
            'tanggal_selesai' => $this->request->getPost('tanggal_selesai') ?: null,
            'is_active'       => 0,
        ]);

        if (! $ok) {
            return redirect()->to('/master/tahun-ajaran')->with('error', implode(' ', $this->semesterModel->errors()));
        }

        (new AuditLogger())->log('tambah_semester', 'Menambah semester');

        return redirect()->to('/master/tahun-ajaran')->with('message', 'Semester berhasil ditambahkan.');
    }

    /**
     * Semester sudah ada sebelum fitur tanggal berlaku ini dibuat tidak punya
     * cara diedit — method ini mengisinya (atau mengubahnya kalau perlu).
     */
    public function updateSemester()
    {
        $id = (int) $this->request->getPost('id');

        $tanggalMulai   = $this->request->getPost('tanggal_mulai') ?: null;
        $tanggalSelesai = $this->request->getPost('tanggal_selesai') ?: null;

        if ($tanggalMulai && $tanggalSelesai && $tanggalSelesai < $tanggalMulai) {
            return redirect()->to('/master/tahun-ajaran')->with('error', 'Tanggal selesai tidak boleh sebelum tanggal mulai.');
        }

        $ok = $this->semesterModel->update($id, [
            'nama'            => $this->request->getPost('nama'),
            'tanggal_mulai'   => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
        ]);

        if (! $ok) {
            return redirect()->to('/master/tahun-ajaran')->with('error', implode(' ', $this->semesterModel->errors()));
        }

        (new AuditLogger())->log('ubah_semester', 'Mengubah semester #' . $id . ' (tanggal berlaku ' . ($tanggalMulai ?? '-') . ' s/d ' . ($tanggalSelesai ?? '-') . ')');

        return redirect()->to('/master/tahun-ajaran')->with('message', 'Semester berhasil diperbarui.');
    }

    public function setActiveSemester($id)
    {
        $this->semesterModel->setActive((int) $id);

        (new AuditLogger())->log('aktifkan_semester', 'Mengaktifkan semester #' . $id);

        return redirect()->to('/master/tahun-ajaran')->with('message', 'Semester aktif berhasil diperbarui.');
    }

    public function deleteSemester($id)
    {
        $this->semesterModel->delete((int) $id);

        (new AuditLogger())->log('hapus_semester', 'Menghapus semester #' . $id);

        return redirect()->to('/master/tahun-ajaran')->with('message', 'Semester berhasil dihapus.');
    }
}
