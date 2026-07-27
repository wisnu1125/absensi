<?php

namespace App\Controllers\Master;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\KelasModel;
use App\Models\SiswaModel;
use App\Models\TahunAjaranModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Siswa extends BaseController
{
    protected SiswaModel $model;

    public function __construct()
    {
        $this->model = new SiswaModel();
    }

    public function index()
    {
        $kelasModel = new KelasModel();

        $data = [
            'title'   => 'Siswa',
            'content' => view('master/siswa/index', [
                'items' => $this->model->getWithKelas(),
                'kelas' => $kelasModel->getWithTahunAjaran(),
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function store()
    {
        $ok = $this->model->insert([
            'nis'           => $this->request->getPost('nis'),
            'nama'          => $this->request->getPost('nama'),
            'jenis_kelamin' => $this->request->getPost('jenis_kelamin'),
            'kelas_id'      => $this->request->getPost('kelas_id') ?: null,
            'tanggal_lahir' => $this->request->getPost('tanggal_lahir') ?: null,
            'alamat'        => $this->request->getPost('alamat') ?: null,
            'nama_ortu'     => $this->request->getPost('nama_ortu') ?: null,
            'no_hp_ortu'    => $this->request->getPost('no_hp_ortu') ?: null,
            'status'        => 'aktif',
        ]);

        if (! $ok) {
            return redirect()->to('/master/siswa')->with('error', implode(' ', $this->model->errors()));
        }

        (new AuditLogger())->log('tambah_siswa', 'Menambah siswa: ' . $this->request->getPost('nama'));

        return redirect()->to('/master/siswa')->with('message', 'Data siswa berhasil ditambahkan.');
    }

    public function update()
    {
        $id = (int) $this->request->getPost('id');

        $ok = $this->model->update($id, [
            'nis'           => $this->request->getPost('nis'),
            'nama'          => $this->request->getPost('nama'),
            'jenis_kelamin' => $this->request->getPost('jenis_kelamin'),
            'kelas_id'      => $this->request->getPost('kelas_id') ?: null,
            'tanggal_lahir' => $this->request->getPost('tanggal_lahir') ?: null,
            'alamat'        => $this->request->getPost('alamat') ?: null,
            'nama_ortu'     => $this->request->getPost('nama_ortu') ?: null,
            'no_hp_ortu'    => $this->request->getPost('no_hp_ortu') ?: null,
            'status'        => $this->request->getPost('status'),
        ]);

        if (! $ok) {
            return redirect()->to('/master/siswa')->with('error', implode(' ', $this->model->errors()));
        }

        (new AuditLogger())->log('ubah_siswa', 'Mengubah data siswa #' . $id);

        return redirect()->to('/master/siswa')->with('message', 'Data siswa berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->model->delete((int) $id);

        (new AuditLogger())->log('hapus_siswa', 'Menghapus siswa #' . $id);

        return redirect()->to('/master/siswa')->with('message', 'Data siswa berhasil dihapus.');
    }

    /**
     * Pasang dropdown pilihan pada satu kolom, dari baris 2 sampai $sampaiBaris.
     * Diterapkan satu sel satu sel supaya kompatibel di semua versi PhpSpreadsheet.
     * $wajib=true -> Excel menolak nilai di luar daftar (dipaksa).
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

    public function downloadTemplate()
    {
        $tahunAktif   = (new TahunAjaranModel())->getActive();
        $daftarKelas  = $tahunAktif
            ? array_column((new KelasModel())->where('tahun_ajaran_id', $tahunAktif['id'])->findAll(), 'nama_kelas')
            : [];

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Siswa');

        // setCellValueByColumnAndRow() sudah dihapus di PhpSpreadsheet 2.x,
        // jadi dipakai sintaks koordinat [kolom, baris] yang berlaku di versi saat ini.
        $headers = ['NIS', 'Nama', 'Jenis Kelamin (L/P)', 'Kelas', 'Tanggal Lahir (YYYY-MM-DD)', 'Alamat', 'Nama Orang Tua', 'No HP Orang Tua'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValue([$i + 1, 1], $header);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setWidth(20);
        }

        // Dropdown Jenis Kelamin (kolom C) — dipaksa, cuma boleh L atau P.
        $this->pasangDropdown($sheet, 'C', 500, 'L,P', true);

        // Dropdown Kelas (kolom D) — daftar dari kelas pada tahun ajaran AKTIF saat ini.
        // Tidak dipaksa (siswa tetap tersimpan tanpa kelas kalau kosong/tidak cocok),
        // supaya import tidak gagal total hanya gara-gara satu ketikan nama kelas meleset.
        if (! empty($daftarKelas)) {
            $this->pasangDropdown($sheet, 'D', 500, implode(',', $daftarKelas), false);
        }

        $writer = new Xlsx($spreadsheet);

        return $this->streamXlsx($writer, 'template_import_siswa.xlsx');
    }

    public function import()
    {
        $file = $this->request->getFile('file_excel');

        if (! $file || ! $file->isValid()) {
            return redirect()->to('/master/siswa')->with('error', 'File Excel tidak valid atau belum dipilih.');
        }

        $allowed = ['xlsx', 'xls', 'csv'];
        if (! in_array(strtolower($file->getClientExtension()), $allowed, true)) {
            return redirect()->to('/master/siswa')->with('error', 'Format file harus xlsx, xls, atau csv.');
        }

        $tahunAjaranModel = new TahunAjaranModel();
        $tahunAktif       = $tahunAjaranModel->getActive();

        if (! $tahunAktif) {
            return redirect()->to('/master/siswa')->with('error', 'Tidak ada tahun ajaran aktif. Aktifkan tahun ajaran terlebih dahulu di menu Tahun Ajaran.');
        }

        $spreadsheet = IOFactory::load($file->getTempName());
        $sheet       = $spreadsheet->getActiveSheet();
        $rawRows     = $sheet->toArray(null, true, true, false);

        array_shift($rawRows); // buang baris header

        $rows = [];
        foreach ($rawRows as $r) {
            if (empty(array_filter($r))) {
                continue;
            }
            // urutan kolom template: NIS, Nama, JK, Kelas, Tgl Lahir, Alamat, Nama Ortu, No HP Ortu
            $rows[] = [
                'nis'           => $r[0] ?? '',
                'nama'          => $r[1] ?? '',
                'jenis_kelamin' => $r[2] ?? 'L',
                'kelas'         => $r[3] ?? '',
                'tanggal_lahir' => $r[4] ?? '',
                'alamat'        => $r[5] ?? '',
                'nama_ortu'     => $r[6] ?? '',
                'no_hp_ortu'    => $r[7] ?? '',
            ];
        }

        $hasil = $this->model->importRows($rows, (int) $tahunAktif['id']);

        (new AuditLogger())->log('import_siswa', "Import Excel siswa: {$hasil['sukses']} sukses, {$hasil['gagal']} gagal");

        $pesan = "Import selesai: {$hasil['sukses']} data berhasil ditambahkan, {$hasil['gagal']} dilewati.";
        if (! empty($hasil['errors'])) {
            $pesan .= ' Detail: ' . implode(' | ', array_slice($hasil['errors'], 0, 5));
        }

        return redirect()->to('/master/siswa')->with('message', $pesan);
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
}
