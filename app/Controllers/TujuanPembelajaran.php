<?php

namespace App\Controllers;

use App\Libraries\AuditLogger;
use App\Models\GuruModel;
use App\Models\GuruPengampuModel;
use App\Models\TujuanPembelajaranModel;

/**
 * Master Tujuan Pembelajaran (TP) — dikelola guru SENDIRI, per Guru Pengampu
 * (mapel+tingkat) yang mereka ampu. Ini "pembayaran" dari fondasi Guru
 * Pengampu yang sudah dibangun — jadi fitur nyata yang guru pakai, bukan
 * cuma struktur data di belakang layar.
 *
 * SETIAP aksi CRUD memvalidasi ULANG bahwa Guru Pengampu/TP yang disentuh
 * benar milik guru yang sedang login — guru A TIDAK BOLEH bisa mengubah/
 * menghapus TP milik guru B walau tahu ID-nya.
 */
class TujuanPembelajaran extends BaseController
{
    private function guruSaatIni(): ?array
    {
        return (new GuruModel())->findByUserId((int) current_user()['id']);
    }

    public function index()
    {
        $guru = $this->guruSaatIni();

        if (! $guru) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum terhubung ke data guru.');
        }

        $pengampuModel = new GuruPengampuModel();
        $tpModel       = new TujuanPembelajaranModel();

        $pengampuList = $pengampuModel->getUntukGuru((int) $guru['id']);
        foreach ($pengampuList as &$p) {
            $p['tp_list'] = $tpModel->getUntukPengampu((int) $p['id']);
        }
        unset($p);

        $data = [
            'title'   => 'Master Tujuan Pembelajaran',
            'content' => view('tujuan_pembelajaran/index', [
                'guru'         => $guru,
                'pengampuList' => $pengampuList,
            ]),
        ];

        return view('layouts/main', $data);
    }

    /**
     * Pastikan Guru Pengampu yang dirujuk BENAR milik guru yang sedang login
     * — dipakai sebelum store/update/delete supaya guru tidak bisa
     * menyentuh data guru lain walau tahu ID-nya.
     */
    private function pengampuMilikSaya(int $guruPengampuId, array $guru): ?array
    {
        $pengampu = (new GuruPengampuModel())->find($guruPengampuId);

        if (! $pengampu || (int) $pengampu['guru_id'] !== (int) $guru['id']) {
            return null;
        }

        return $pengampu;
    }

    public function store()
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum terhubung ke data guru.');
        }

        $guruPengampuId = (int) $this->request->getPost('guru_pengampu_id');
        $pengampu       = $this->pengampuMilikSaya($guruPengampuId, $guru);

        if (! $pengampu) {
            return redirect()->to('/tujuan-pembelajaran')->with('error', 'Data pengampuan tidak valid atau bukan milik Anda.');
        }

        $tpModel = new TujuanPembelajaranModel();
        $ok      = $tpModel->insert([
            'guru_pengampu_id' => $guruPengampuId,
            'teks'             => $this->request->getPost('teks'),
            'urutan'           => $tpModel->urutanBerikutnya($guruPengampuId),
        ]);

        if (! $ok) {
            return redirect()->to('/tujuan-pembelajaran')->with('error', implode(' ', $tpModel->errors()));
        }

        (new AuditLogger())->log('tambah_tp', 'Menambah TP untuk pengampuan #' . $guruPengampuId);

        return redirect()->to('/tujuan-pembelajaran')->with('message', 'Tujuan Pembelajaran berhasil ditambahkan.');
    }

    public function update()
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum terhubung ke data guru.');
        }

        $id      = (int) $this->request->getPost('id');
        $tpModel = new TujuanPembelajaranModel();
        $tp      = $tpModel->find($id);

        if (! $tp || ! $this->pengampuMilikSaya((int) $tp['guru_pengampu_id'], $guru)) {
            return redirect()->to('/tujuan-pembelajaran')->with('error', 'TP tidak ditemukan atau bukan milik Anda.');
        }

        $ok = $tpModel->update($id, ['id' => $id, 'teks' => $this->request->getPost('teks')]);

        if (! $ok) {
            return redirect()->to('/tujuan-pembelajaran')->with('error', implode(' ', $tpModel->errors()));
        }

        (new AuditLogger())->log('ubah_tp', 'Mengubah TP #' . $id);

        return redirect()->to('/tujuan-pembelajaran')->with('message', 'Tujuan Pembelajaran berhasil diperbarui.');
    }

    public function delete($id)
    {
        $guru = $this->guruSaatIni();
        if (! $guru) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum terhubung ke data guru.');
        }

        $tpModel = new TujuanPembelajaranModel();
        $tp      = $tpModel->find((int) $id);

        if (! $tp || ! $this->pengampuMilikSaya((int) $tp['guru_pengampu_id'], $guru)) {
            return redirect()->to('/tujuan-pembelajaran')->with('error', 'TP tidak ditemukan atau bukan milik Anda.');
        }

        $tpModel->delete((int) $id);

        (new AuditLogger())->log('hapus_tp', 'Menghapus TP #' . $id);

        return redirect()->to('/tujuan-pembelajaran')->with('message', 'Tujuan Pembelajaran berhasil dihapus.');
    }
}
