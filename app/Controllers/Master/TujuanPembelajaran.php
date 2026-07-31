<?php

namespace App\Controllers\Master;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\GuruModel;
use App\Models\GuruPengampuModel;
use App\Models\TujuanPembelajaranModel;

/**
 * Master TP — versi Administrator. Guru cuma bisa kelola TP miliknya sendiri
 * (lihat App\Controllers\TujuanPembelajaran), tapi admin/operator punya
 * akses penuh ke SEMUA guru — untuk keperluan controlling/quality-control,
 * bukan cuma menunggu tiap guru mengisi sendiri.
 *
 * TIDAK ADA pengecekan kepemilikan di sini (beda dari versi guru) karena
 * memang itu tujuannya — tapi tetap dibatasi role admin/operator lewat
 * filter rute, dan tiap aksi tetap tercatat di Audit Log.
 */
class TujuanPembelajaran extends BaseController
{
    public function index()
    {
        $guruModel     = new GuruModel();
        $pengampuModel = new GuruPengampuModel();
        $tpModel       = new TujuanPembelajaranModel();

        $guruFilter = (int) ($this->request->getGet('guru_id') ?? 0);

        $semuaPengampu = $pengampuModel->getSemuaLengkap();
        if ($guruFilter) {
            $semuaPengampu = array_values(array_filter($semuaPengampu, static fn ($p) => (int) $p['guru_id'] === $guruFilter));
        }

        // Kelompokkan per guru, supaya admin bisa telusuri satu guru dulu
        // baru ke guru berikutnya — bukan satu daftar panjang tercampur.
        $perGuru = [];
        foreach ($semuaPengampu as $p) {
            $p['tp_list'] = $tpModel->getUntukPengampu((int) $p['id']);
            $perGuru[$p['guru_id']]['nama_guru'] = $p['nama_guru'];
            $perGuru[$p['guru_id']]['pengampu'][] = $p;
        }

        $data = [
            'title'   => 'Master TP — Semua Guru',
            'content' => view('master/tujuan_pembelajaran/index', [
                'perGuru'    => $perGuru,
                'daftarGuru' => $guruModel->where('status', 'aktif')->orderBy('nama', 'ASC')->findAll(),
                'guruFilter' => $guruFilter,
                'totalTP'    => array_sum(array_map(static fn ($g) => array_sum(array_map(static fn ($p) => count($p['tp_list']), $g['pengampu'])), $perGuru)),
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function store()
    {
        $guruPengampuId = (int) $this->request->getPost('guru_pengampu_id');
        $pengampu       = (new GuruPengampuModel())->find($guruPengampuId);

        if (! $pengampu) {
            return redirect()->to('/master/tujuan-pembelajaran')->with('error', 'Data pengampuan tidak ditemukan.');
        }

        $tpModel = new TujuanPembelajaranModel();
        $ok      = $tpModel->insert([
            'guru_pengampu_id' => $guruPengampuId,
            'teks'             => $this->request->getPost('teks'),
            'urutan'           => $tpModel->urutanBerikutnya($guruPengampuId),
        ]);

        if (! $ok) {
            return redirect()->to('/master/tujuan-pembelajaran')->with('error', implode(' ', $tpModel->errors()));
        }

        (new AuditLogger())->log('admin_tambah_tp', 'Admin menambah TP untuk pengampuan #' . $guruPengampuId);

        return redirect()->to('/master/tujuan-pembelajaran')->with('message', 'Tujuan Pembelajaran berhasil ditambahkan.');
    }

    public function update()
    {
        $id      = (int) $this->request->getPost('id');
        $tpModel = new TujuanPembelajaranModel();

        $ok = $tpModel->update($id, ['id' => $id, 'teks' => $this->request->getPost('teks')]);

        if (! $ok) {
            return redirect()->to('/master/tujuan-pembelajaran')->with('error', implode(' ', $tpModel->errors()));
        }

        (new AuditLogger())->log('admin_ubah_tp', 'Admin mengubah TP #' . $id);

        return redirect()->to('/master/tujuan-pembelajaran')->with('message', 'Tujuan Pembelajaran berhasil diperbarui.');
    }

    public function delete($id)
    {
        (new TujuanPembelajaranModel())->delete((int) $id);

        (new AuditLogger())->log('admin_hapus_tp', 'Admin menghapus TP #' . $id);

        return redirect()->to('/master/tujuan-pembelajaran')->with('message', 'Tujuan Pembelajaran berhasil dihapus.');
    }
}
