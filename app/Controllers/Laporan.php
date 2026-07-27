<?php

namespace App\Controllers;

use App\Models\GuruModel;
use App\Models\JurnalMengajarModel;
use App\Models\KelasModel;
use App\Models\MataPelajaranModel;
use App\Models\PresensiDetailModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Satu halaman filter (tanggal, guru, kelas, mapel) menghasilkan semua variasi
 * laporan yang diminta SRS (harian/bulanan/semester/per guru/per kelas/per
 * mapel/per siswa) tinggal lewat kombinasi filter — tidak perlu 8 halaman terpisah.
 */
class Laporan extends BaseController
{
    // ============================================================ PRESENSI

    public function presensi()
    {
        $filter = $this->ambilFilter();
        $rows   = $this->queryPresensi($filter);

        $data = [
            'title'   => 'Laporan Presensi',
            'content' => view('laporan/presensi', [
                'rows'   => $rows,
                'rekap'  => $this->rekapPresensi($rows),
                'filter' => $filter,
                'guru'   => (new GuruModel())->orderBy('nama', 'ASC')->findAll(),
                'kelas'  => (new KelasModel())->orderBy('nama_kelas', 'ASC')->findAll(),
                'mapel'  => (new MataPelajaranModel())->orderBy('nama', 'ASC')->findAll(),
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function presensiExportExcel()
    {
        $rows = $this->queryPresensi($this->ambilFilter());

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Presensi');

        $headers = ['Tanggal', 'Hari', 'Kelas', 'Mata Pelajaran', 'Guru', 'NIS', 'Nama Siswa', 'Status', 'Catatan'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue([$i + 1, 1], $h);
        }
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        $r = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue([1, $r], date('d-m-Y', strtotime($row['tanggal'])));
            $sheet->setCellValue([2, $r], $row['hari']);
            $sheet->setCellValue([3, $r], $row['nama_kelas']);
            $sheet->setCellValue([4, $r], $row['nama_mapel']);
            $sheet->setCellValue([5, $r], $row['nama_guru']);
            $sheet->setCellValue([6, $r], $row['nis']);
            $sheet->setCellValue([7, $r], $row['nama']);
            $sheet->setCellValue([8, $r], ucfirst($row['status']));
            $sheet->setCellValue([9, $r], $row['catatan'] ?? '');
            $r++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setWidth(18);
        }

        return $this->streamXlsx(new Xlsx($spreadsheet), 'laporan-presensi.xlsx');
    }

    public function presensiExportPdf()
    {
        $filter = $this->ambilFilter();
        $rows   = $this->queryPresensi($filter);

        $html = view('laporan/presensi_pdf', [
            'rows'   => $rows,
            'rekap'  => $this->rekapPresensi($rows),
            'filter' => $filter,
        ]);

        return $this->streamPdf($html, 'laporan-presensi.pdf');
    }

    // ============================================================== JURNAL

    public function jurnal()
    {
        $filter = $this->ambilFilterJurnal();

        $data = [
            'title'   => 'Rekap Jurnal Mengajar',
            'content' => view('laporan/jurnal', [
                'rows'   => $this->queryJurnal($filter),
                'filter' => $filter,
                'guru'   => (new GuruModel())->orderBy('nama', 'ASC')->findAll(),
                'kelas'  => (new KelasModel())->orderBy('nama_kelas', 'ASC')->findAll(),
            ]),
        ];

        return view('layouts/main', $data);
    }

    public function jurnalExportExcel()
    {
        $rows = $this->queryJurnal($this->ambilFilterJurnal());

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Jurnal');

        $headers = ['Tanggal', 'Hari', 'Kelas', 'Mata Pelajaran', 'Guru', 'Materi', 'Metode', 'Kendala'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue([$i + 1, 1], $h);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        $r = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue([1, $r], date('d-m-Y', strtotime($row['tanggal'])));
            $sheet->setCellValue([2, $r], $row['hari']);
            $sheet->setCellValue([3, $r], $row['nama_kelas']);
            $sheet->setCellValue([4, $r], $row['nama_mapel']);
            $sheet->setCellValue([5, $r], $row['nama_guru']);
            $sheet->setCellValue([6, $r], $row['materi']);
            $sheet->setCellValue([7, $r], $row['metode'] ?? '');
            $sheet->setCellValue([8, $r], $row['kendala'] ?? '');
            $r++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setWidth(22);
        }

        return $this->streamXlsx(new Xlsx($spreadsheet), 'rekap-jurnal.xlsx');
    }

    public function jurnalExportPdf()
    {
        $filter = $this->ambilFilterJurnal();

        $html = view('laporan/jurnal_pdf', [
            'rows'   => $this->queryJurnal($filter),
            'filter' => $filter,
        ]);

        return $this->streamPdf($html, 'rekap-jurnal.pdf');
    }

    // ========================================================== PRIVATE

    private function ambilFilter(): array
    {
        return [
            'tanggal_dari'   => $this->request->getGet('tanggal_dari') ?: date('Y-m-01'),
            'tanggal_sampai' => $this->request->getGet('tanggal_sampai') ?: date('Y-m-d'),
            'guru_id'        => $this->request->getGet('guru_id') ?: '',
            'kelas_id'       => $this->request->getGet('kelas_id') ?: '',
            'mapel_id'       => $this->request->getGet('mapel_id') ?: '',
        ];
    }

    private function ambilFilterJurnal(): array
    {
        return [
            'tanggal_dari'   => $this->request->getGet('tanggal_dari') ?: date('Y-m-01'),
            'tanggal_sampai' => $this->request->getGet('tanggal_sampai') ?: date('Y-m-d'),
            'guru_id'        => $this->request->getGet('guru_id') ?: '',
            'kelas_id'       => $this->request->getGet('kelas_id') ?: '',
        ];
    }

    private function queryPresensi(array $filter): array
    {
        $builder = (new PresensiDetailModel())
            ->select('presensi_detail.status, presensi_detail.catatan, presensi.tanggal, jadwal.hari, siswa.nis, siswa.nama, kelas.nama_kelas, mata_pelajaran.nama as nama_mapel, guru.nama as nama_guru')
            ->join('presensi', 'presensi.id = presensi_detail.presensi_id')
            ->join('siswa', 'siswa.id = presensi_detail.siswa_id')
            ->join('jadwal', 'jadwal.id = presensi.jadwal_id')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('guru', 'guru.id = jadwal.guru_id')
            ->where('presensi.tanggal >=', $filter['tanggal_dari'])
            ->where('presensi.tanggal <=', $filter['tanggal_sampai']);

        if ($filter['guru_id'] !== '') {
            $builder->where('jadwal.guru_id', $filter['guru_id']);
        }
        if ($filter['kelas_id'] !== '') {
            $builder->where('jadwal.kelas_id', $filter['kelas_id']);
        }
        if ($filter['mapel_id'] !== '') {
            $builder->where('jadwal.mapel_id', $filter['mapel_id']);
        }

        return $builder->orderBy('presensi.tanggal', 'DESC')->orderBy('siswa.nama', 'ASC')->findAll();
    }

    private function rekapPresensi(array $rows): array
    {
        $rekap = array_fill_keys(PresensiDetailModel::STATUS_VALID, 0);
        foreach ($rows as $r) {
            $rekap[$r['status']] = ($rekap[$r['status']] ?? 0) + 1;
        }

        return $rekap;
    }

    private function queryJurnal(array $filter): array
    {
        $builder = (new JurnalMengajarModel())
            ->select('jurnal_mengajar.*, jadwal.hari, kelas.nama_kelas, mata_pelajaran.nama as nama_mapel, guru.nama as nama_guru')
            ->join('jadwal', 'jadwal.id = jurnal_mengajar.jadwal_id')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('guru', 'guru.id = jadwal.guru_id')
            ->where('jurnal_mengajar.tanggal >=', $filter['tanggal_dari'])
            ->where('jurnal_mengajar.tanggal <=', $filter['tanggal_sampai']);

        if ($filter['guru_id'] !== '') {
            $builder->where('jadwal.guru_id', $filter['guru_id']);
        }
        if ($filter['kelas_id'] !== '') {
            $builder->where('jadwal.kelas_id', $filter['kelas_id']);
        }

        return $builder->orderBy('jurnal_mengajar.tanggal', 'DESC')->findAll();
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

    private function streamPdf(string $html, string $filename)
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return service('response')
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }
}
