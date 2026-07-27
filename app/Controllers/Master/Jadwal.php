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
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

    /**
     * Unduh template Excel — Guru/Mapel/Kelas/Hari/Jam Ke berupa dropdown supaya
     * admin tinggal pilih, tidak mengetik manual dan salah ketik nama.
     */
    public function downloadTemplate()
    {
        $aktif = (new SemesterModel())->getActive();

        if (! $aktif) {
            return redirect()->to('/master/jadwal')->with('error', 'Tidak ada semester aktif, tidak bisa membuat template.');
        }

        $daftarGuru  = array_column((new GuruModel())->where('status', 'aktif')->findAll(), 'nama');
        $daftarMapel = array_column((new MataPelajaranModel())->findAll(), 'nama');
        $daftarKelas = array_column((new KelasModel())->where('tahun_ajaran_id', $aktif['tahun_ajaran_id'])->findAll(), 'nama_kelas');
        $daftarJamKe = array_column((new JamPelajaranModel())->getAllOrdered(), 'jam_ke');

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Jadwal');

        $headers = ['Guru', 'Mata Pelajaran', 'Kelas', 'Hari', 'Jam Ke Mulai', 'Jam Ke Selesai'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValue([$i + 1, 1], $header);
        }
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setWidth(22);
        }

        $this->pasangDropdown($sheet, 'A', 300, implode(',', $daftarGuru), true);
        $this->pasangDropdown($sheet, 'B', 300, implode(',', $daftarMapel), true);
        $this->pasangDropdown($sheet, 'C', 300, implode(',', $daftarKelas), true);
        $this->pasangDropdown($sheet, 'D', 300, 'Senin,Selasa,Rabu,Kamis,Jumat,Sabtu', true);
        $this->pasangDropdown($sheet, 'E', 300, implode(',', $daftarJamKe), true);
        $this->pasangDropdown($sheet, 'F', 300, implode(',', $daftarJamKe), true);

        $writer = new Xlsx($spreadsheet);

        return $this->streamXlsx($writer, 'template_import_jadwal.xlsx');
    }

    public function import()
    {
        $aktif = (new SemesterModel())->getActive();

        if (! $aktif) {
            return redirect()->to('/master/jadwal')->with('error', 'Tidak ada semester aktif.');
        }

        $file = $this->request->getFile('file_excel');

        if (! $file || ! $file->isValid()) {
            return redirect()->to('/master/jadwal')->with('error', 'File Excel tidak valid atau belum dipilih.');
        }

        if (! in_array(strtolower($file->getClientExtension()), ['xlsx', 'xls', 'csv'], true)) {
            return redirect()->to('/master/jadwal')->with('error', 'Format file harus xlsx, xls, atau csv.');
        }

        $spreadsheet = IOFactory::load($file->getTempName());
        $rawRows     = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        array_shift($rawRows); // buang baris header

        $rows = [];
        foreach ($rawRows as $r) {
            if (empty(array_filter($r))) {
                continue;
            }
            $rows[] = [
                'guru'          => $r[0] ?? '',
                'mapel'         => $r[1] ?? '',
                'kelas'         => $r[2] ?? '',
                'hari'          => $r[3] ?? '',
                'jam_ke_mulai'  => $r[4] ?? '',
                'jam_ke_selesai' => $r[5] ?? '',
            ];
        }

        $hasil = $this->model->importRows($rows, (int) $aktif['tahun_ajaran_id'], (int) $aktif['id']);

        (new AuditLogger())->log('import_jadwal', "Import Excel jadwal: {$hasil['sukses']} sukses, {$hasil['gagal']} gagal");

        $pesan = "Import selesai: {$hasil['sukses']} jadwal berhasil ditambahkan, {$hasil['gagal']} dilewati.";
        if (! empty($hasil['errors'])) {
            $pesan .= ' Detail: ' . implode(' | ', array_slice($hasil['errors'], 0, 5));
        }

        return redirect()->to('/master/jadwal')->with('message', $pesan);
    }

    private function streamXlsx(Xlsx $writer, string $filename)
    {
        $response = service('response');
        $response->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setHeader('Cache-Control', 'max-age=0');

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $response->setBody($content);
    }

    /**
     * Pasang dropdown pilihan pada satu kolom, dari baris 2 sampai $sampaiBaris.
     * Diterapkan satu sel satu sel supaya kompatibel di semua versi PhpSpreadsheet.
     */
    private function pasangDropdown($sheet, string $kolom, int $sampaiBaris, string $daftarKoma, bool $wajib): void
    {
        for ($baris = 2; $baris <= $sampaiBaris; $baris++) {
            $validation = $sheet->getCell($kolom . $baris)->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage($wajib);
            $validation->setPromptTitle('Pilih dari daftar');
            $validation->setPrompt('Klik sel ini lalu pilih dari daftar yang muncul.');
            $validation->setErrorTitle('Nilai tidak valid');
            $validation->setError('Silakan pilih salah satu dari daftar yang tersedia.');
            $validation->setFormula1('"' . $daftarKoma . '"');
        }
    }
}
