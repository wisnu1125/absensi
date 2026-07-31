<?php

namespace App\Controllers\Master;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\GuruModel;
use App\Models\JadwalModel;
use App\Models\JamPelajaranModel;
use App\Models\KelasModel;
use App\Models\MataPelajaranModel;
use App\Models\SiswaModel;
use App\Models\UserModel;

/**
 * Sampah = tempat admin melihat & memulihkan SEMUA data yang pernah dihapus
 * siapa pun di aplikasi ini (Guru, Siswa, Kelas, Mata Pelajaran, Jam
 * Pelajaran, Jadwal, Pengguna). Data yang "dihapus" sebenarnya
 * cuma ditandai (soft delete, lewat useSoftDeletes bawaan CodeIgniter),
 * jadi tidak pernah benar-benar hilang dari database sampai admin memilih
 * memulihkannya di sini — atau membiarkannya (tidak ada penghapusan
 * permanen otomatis).
 */
class Sampah extends BaseController
{
    /**
     * Peta jenis data -> [label, Model class, kolom nama utama untuk ditampilkan,
     * rute restore]. Satu tempat referensi supaya index() & restore() konsisten.
     */
    private function petaJenis(): array
    {
        return [
            'guru'           => ['label' => 'Guru', 'model' => GuruModel::class, 'kolom' => 'nama'],
            'siswa'          => ['label' => 'Siswa', 'model' => SiswaModel::class, 'kolom' => 'nama'],
            'kelas'          => ['label' => 'Kelas', 'model' => KelasModel::class, 'kolom' => 'nama_kelas'],
            'mata_pelajaran' => ['label' => 'Mata Pelajaran', 'model' => MataPelajaranModel::class, 'kolom' => 'nama'],
            'jam_pelajaran'  => ['label' => 'Jam Pelajaran', 'model' => JamPelajaranModel::class, 'kolom' => 'jam_ke'],
            'jadwal'         => ['label' => 'Jadwal Mengajar', 'model' => JadwalModel::class, 'kolom' => 'id'],
            'pengguna'       => ['label' => 'Pengguna', 'model' => UserModel::class, 'kolom' => 'username'],
        ];
    }

    public function index()
    {
        $filterJenis = $this->request->getGet('jenis') ?: '';
        $peta        = $this->petaJenis();

        $items = [];
        foreach ($peta as $kunci => $info) {
            if ($filterJenis !== '' && $filterJenis !== $kunci) {
                continue;
            }

            $modelClass = $info['model'];
            $rows       = (new $modelClass())->onlyDeleted()->orderBy('deleted_at', 'DESC')->findAll();

            foreach ($rows as $row) {
                $label = $row[$info['kolom']] ?? '#' . $row['id'];
                if ($kunci === 'jadwal') {
                    // Jadwal tidak punya satu kolom nama tunggal — susun label dari relasinya.
                    $label = $this->labelJadwal((int) $row['id']);
                }

                $items[] = [
                    'jenis'      => $kunci,
                    'label_jenis'=> $info['label'],
                    'id'         => $row['id'],
                    'label'      => (string) $label,
                    'deleted_at' => $row['deleted_at'],
                ];
            }
        }

        // Urutkan gabungan semua jenis berdasarkan waktu hapus terbaru.
        usort($items, static fn ($a, $b) => strcmp($b['deleted_at'], $a['deleted_at']));

        $data = [
            'title'   => 'Sampah',
            'content' => view('master/sampah/index', [
                'items'       => $items,
                'peta'        => $peta,
                'filterJenis' => $filterJenis,
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function restore($jenis, $id)
    {
        $peta = $this->petaJenis();

        if (! isset($peta[$jenis])) {
            return redirect()->to('/master/sampah')->with('error', 'Jenis data tidak dikenali.');
        }

        $modelClass = $peta[$jenis]['model'];
        $model      = new $modelClass();

        // Pastikan baris ini memang ada DAN memang sedang berstatus terhapus,
        // supaya tidak bisa "restore" sembarang id lewat URL.
        $row = $model->onlyDeleted()->find((int) $id);
        if (! $row) {
            return redirect()->to('/master/sampah')->with('error', 'Data tidak ditemukan di Sampah (mungkin sudah dipulihkan sebelumnya).');
        }

        $model->update((int) $id, ['deleted_at' => null]);

        $label = $row[$peta[$jenis]['kolom']] ?? ('#' . $id);
        (new AuditLogger())->log('restore_data', "Memulihkan {$peta[$jenis]['label']}: {$label} (#{$id}) dari Sampah");

        return redirect()->to('/master/sampah')->with('message', $peta[$jenis]['label'] . ' berhasil dipulihkan.');
    }

    private function labelJadwal(int $jadwalId): string
    {
        $row = (new JadwalModel())->onlyDeleted()
            ->select('jadwal.hari, jadwal.jam_ke_mulai, jadwal.jam_ke_selesai, guru.nama as nama_guru, kelas.nama_kelas, mata_pelajaran.nama as nama_mapel')
            ->join('guru', 'guru.id = jadwal.guru_id', 'left')
            ->join('kelas', 'kelas.id = jadwal.kelas_id', 'left')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id', 'left')
            ->find($jadwalId);

        if (! $row) {
            return '#' . $jadwalId;
        }

        return sprintf(
            '%s, jam ke-%d–%d — %s (%s), guru %s',
            $row['hari'] ?? '-',
            (int) ($row['jam_ke_mulai'] ?? 0),
            (int) ($row['jam_ke_selesai'] ?? 0),
            $row['nama_kelas'] ?? '-',
            $row['nama_mapel'] ?? '-',
            $row['nama_guru'] ?? '-'
        );
    }
}
