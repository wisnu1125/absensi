<?php

namespace App\Controllers\Master;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\GuruModel;
use App\Models\JadwalModel;
use App\Models\JamPelajaranModel;
use App\Models\KelasModel;
use App\Models\MataPelajaranModel;
use App\Models\SemesterModel;

class Jadwal extends BaseController
{
    protected JadwalModel $model;

    public function __construct()
    {
        $this->model = new JadwalModel();
    }

    public function index()
    {
        $aktif = (new SemesterModel())->getActive();

        if (! $aktif) {
            $data = [
                'title'   => 'Jadwal Mengajar',
                'content' => view('master/jadwal/kosong'),
            ];

            return view('layouts/main', $data);
        }

        $guruModel  = new GuruModel();
        $mapelModel = new MataPelajaranModel();
        $kelasModel = new KelasModel();
        $jamModel   = new JamPelajaranModel();

        $data = [
            'title'   => 'Jadwal Mengajar',
            'content' => view('master/jadwal/index', [
                'items'        => $this->model->getWithDetail((int) $aktif['id']),
                'aktif'        => $aktif,
                'guru'         => $guruModel->where('status', 'aktif')->orderBy('nama', 'ASC')->findAll(),
                'mapel'        => $mapelModel->orderBy('nama', 'ASC')->findAll(),
                'kelas'        => $kelasModel->where('tahun_ajaran_id', $aktif['tahun_ajaran_id'])->orderBy('nama_kelas', 'ASC')->findAll(),
                'jamPelajaran' => $jamModel->getAllOrdered(),
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function store()
    {
        $aktif = (new SemesterModel())->getActive();

        if (! $aktif) {
            return redirect()->to('/master/jadwal')->with('error', 'Tidak ada semester aktif. Aktifkan dulu di menu Tahun Ajaran & Semester.');
        }

        $result = $this->simpanJadwal($this->request->getPost(), (int) $aktif['id'], (int) $aktif['tahun_ajaran_id'], null);

        if ($result !== true) {
            return redirect()->to('/master/jadwal')->with('error', $result);
        }

        (new AuditLogger())->log('tambah_jadwal', 'Menambah jadwal mengajar');

        return redirect()->to('/master/jadwal')->with('message', 'Jadwal berhasil ditambahkan.');
    }

    public function update()
    {
        $id    = (int) $this->request->getPost('id');
        $aktif = (new SemesterModel())->getActive();

        if (! $aktif) {
            return redirect()->to('/master/jadwal')->with('error', 'Tidak ada semester aktif.');
        }

        $result = $this->simpanJadwal($this->request->getPost(), (int) $aktif['id'], (int) $aktif['tahun_ajaran_id'], $id);

        if ($result !== true) {
            return redirect()->to('/master/jadwal')->with('error', $result);
        }

        (new AuditLogger())->log('ubah_jadwal', 'Mengubah jadwal #' . $id);

        return redirect()->to('/master/jadwal')->with('message', 'Jadwal berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->model->delete((int) $id);

        (new AuditLogger())->log('hapus_jadwal', 'Menghapus jadwal #' . $id);

        return redirect()->to('/master/jadwal')->with('message', 'Jadwal berhasil dihapus.');
    }

    /**
     * Validasi bentrok (guru + kelas) lalu simpan/ubah satu baris jadwal.
     * Return TRUE kalau sukses, atau STRING pesan error kalau ditolak.
     */
    private function simpanJadwal(array $post, int $semesterId, int $tahunAjaranId, ?int $excludeId)
    {
        $guruId       = (int) ($post['guru_id'] ?? 0);
        $mapelId      = (int) ($post['mapel_id'] ?? 0);
        $kelasId      = (int) ($post['kelas_id'] ?? 0);
        $hari         = $post['hari'] ?? '';
        $jamKeMulai   = (int) ($post['jam_ke_mulai'] ?? 0);
        $jamKeSelesai = (int) ($post['jam_ke_selesai'] ?? 0);

        if (! $guruId || ! $mapelId || ! $kelasId || $hari === '' || ! $jamKeMulai || ! $jamKeSelesai) {
            return 'Semua field wajib diisi.';
        }

        if ($jamKeSelesai < $jamKeMulai) {
            return 'Jam ke-selesai tidak boleh sebelum jam ke-mulai.';
        }

        $jamModel  = new JamPelajaranModel();
        $jpMulai   = $jamModel->findByJamKe($jamKeMulai);
        $jpSelesai = $jamModel->findByJamKe($jamKeSelesai);

        if (! $jpMulai || ! $jpSelesai) {
            return 'Jam pelajaran yang dipilih tidak ditemukan.';
        }

        $jamMulai   = $jpMulai['jam_mulai'];
        $jamSelesai = $jpSelesai['jam_selesai'];

        // ---- Validasi #1: tidak boleh bentrok jadwal GURU ----
        $bentrokGuru = $this->model->cekBentrokGuru($guruId, $hari, $jamMulai, $jamSelesai, $semesterId, $excludeId);
        if ($bentrokGuru) {
            return sprintf(
                'Guru sudah mengajar %s di kelas %s jam %s–%s pada hari %s.',
                $bentrokGuru['nama_mapel'],
                $bentrokGuru['nama_kelas'],
                substr($bentrokGuru['jam_mulai'], 0, 5),
                substr($bentrokGuru['jam_selesai'], 0, 5),
                $hari
            );
        }

        // ---- Validasi #2: tidak boleh bentrok jadwal KELAS ----
        $bentrokKelas = $this->model->cekBentrokKelas($kelasId, $hari, $jamMulai, $jamSelesai, $semesterId, $excludeId);
        if ($bentrokKelas) {
            return sprintf(
                'Kelas sudah ada jadwal %s bersama %s jam %s–%s pada hari %s.',
                $bentrokKelas['nama_mapel'],
                $bentrokKelas['nama_guru'],
                substr($bentrokKelas['jam_mulai'], 0, 5),
                substr($bentrokKelas['jam_selesai'], 0, 5),
                $hari
            );
        }

        $data = [
            'guru_id'         => $guruId,
            'mapel_id'        => $mapelId,
            'kelas_id'        => $kelasId,
            'tahun_ajaran_id' => $tahunAjaranId,
            'semester_id'     => $semesterId,
            'hari'            => $hari,
            'jam_ke_mulai'    => $jamKeMulai,
            'jam_ke_selesai'  => $jamKeSelesai,
            'jam_mulai'       => $jamMulai,
            'jam_selesai'     => $jamSelesai,
            'is_active'       => 1,
        ];

        $ok = $excludeId ? $this->model->update($excludeId, $data) : $this->model->insert($data);

        if (! $ok) {
            return implode(' ', $this->model->errors());
        }

        return true;
    }
}
