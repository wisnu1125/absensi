<?php

namespace App\Controllers\Master;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\GuruModel;
use App\Models\GuruPengampuModel;
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

    /**
     * Respons JSON standar utk request AJAX Grid — SELALU menyertakan
     * token CSRF terbaru. CI4 meregenerasi token di setiap request
     * (Security.php bawaan: regenerate=true), jadi kalau tidak dikirim
     * ulang ke JS, form yg masih terbuka di halaman (Tambah/Edit/Hapus)
     * akan tetap pakai token BASI dari page-load pertama — submission
     * KEDUA dan seterusnya akan ditolak validasi CSRF secara DIAM-DIAM
     * (gagal tanpa pesan error yang jelas ke user). Ini akar dari bug
     * "klik simpan tidak menyimpan" yang dilaporkan.
     */
    private function jsonRespons(array $data)
    {
        return $this->response->setJSON($data + [
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
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

        $guruPengampuModel = new GuruPengampuModel();
        $semuaPengampu     = $guruPengampuModel->getSemuaLengkap();
        $pengampuPerTingkat = [];
        foreach ($semuaPengampu as $p) {
            $pengampuPerTingkat[$p['tingkat']][] = [
                'id'    => $p['id'],
                'label' => $p['nama_guru'] . ' — ' . $p['nama_mapel'],
            ];
        }

        $kelasModel = new KelasModel();
        $jamModel   = new JamPelajaranModel();

        $data = [
            'title'   => 'Jadwal Mengajar',
            'content' => view('master/jadwal/index', [
                'items'        => $this->model->getWithDetail((int) $aktif['id']),
                'aktif'        => $aktif,
                'kelas'        => $kelasModel->where('tahun_ajaran_id', $aktif['tahun_ajaran_id'])->orderBy('nama_kelas', 'ASC')->findAll(),
                'jamPelajaran' => $jamModel->getSemuaDikelompokkan(),
                'pengampuPerTingkat' => $pengampuPerTingkat,
                'adaPengampu'  => ! empty($semuaPengampu),
            ]),
        ];

        return view('layouts/main', $data);
    }

    /**
     * Tampilan grid (hari x jam x kelas), meniru bentuk jadwal dinding yang biasa
     * dipakai sekolah — supaya mengisi jadwal terasa seperti mengisi tabel itu
     * juga, bukan menambah satu-satu lewat form terpisah. Klik sel kosong untuk
     * menambah, klik sel terisi untuk mengedit (memakai modal yang sama dengan
     * tampilan daftar).
     */
    public function grid()
    {
        $aktif = (new SemesterModel())->getActive();

        if (! $aktif) {
            $data = ['title' => 'Jadwal Mengajar — Grid', 'content' => view('master/jadwal/kosong')];

            return view('layouts/main', $data);
        }

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $hariAktif = $this->request->getGet('hari') ?: $hariList[0];
        if (! in_array($hariAktif, $hariList, true)) {
            $hariAktif = $hariList[0];
        }

        $gridData = $this->dataGridUntukHari($aktif, $hariAktif);

        $guruPengampuModel  = new GuruPengampuModel();
        $semuaPengampu      = $guruPengampuModel->getSemuaLengkap();
        $pengampuPerTingkat = [];
        foreach ($semuaPengampu as $p) {
            $pengampuPerTingkat[$p['tingkat']][] = [
                'id'    => $p['id'],
                'label' => $p['nama_guru'] . ' — ' . $p['nama_mapel'],
            ];
        }

        $data = [
            'title'   => 'Jadwal Mengajar — Grid',
            'content' => view('master/jadwal/grid', [
                'aktif'     => $aktif,
                'hariList'  => $hariList,
                'hariAktif' => $hariAktif,
                'kelasList' => $gridData['kelasList'],
                'jamList'   => $gridData['jamList'],
                'grid'      => $gridData['grid'],
                'pengampuPerTingkat' => $pengampuPerTingkat,
                'adaPengampu' => ! empty($semuaPengampu),
            ]),
        ];

        return view('layouts/main', $data);
    }

    /**
     * Data grid (kelasList/jamList/grid) utk SATU hari — dipakai grid() saat
     * render halaman penuh, DAN store()/update()/delete() saat merender
     * ulang tabel utk respons AJAX (tanpa reload halaman).
     */
    private function dataGridUntukHari(array $aktif, string $hariAktif): array
    {
        $kelasModel = new KelasModel();
        $kelasList  = $kelasModel->where('tahun_ajaran_id', $aktif['tahun_ajaran_id'])
            ->orderBy('tingkat', 'ASC')->orderBy('nama_kelas', 'ASC')->findAll();

        $jamList = (new JamPelajaranModel())->getByHari($hariAktif);

        $items = $this->model->select('jadwal.*, guru.nama as nama_guru, mata_pelajaran.nama as nama_mapel')
            ->join('guru', 'guru.id = jadwal.guru_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->where('jadwal.hari', $hariAktif)
            ->where('jadwal.semester_id', (int) $aktif['id'])
            ->findAll();

        // grid[jam_ke][kelas_id] = null (kosong) | 'lanjutan' (tercakup rowspan di atas) | array detail
        $grid = [];
        foreach ($jamList as $jp) {
            foreach ($kelasList as $k) {
                $grid[$jp['jam_ke']][$k['id']] = null;
            }
        }
        foreach ($items as $item) {
            $mulai = (int) $item['jam_ke_mulai'];
            $akhir = (int) $item['jam_ke_selesai'];
            $grid[$mulai][$item['kelas_id']] = $item + ['rowspan' => max(1, $akhir - $mulai + 1)];
            for ($k = $mulai + 1; $k <= $akhir; $k++) {
                $grid[$k][$item['kelas_id']] = 'lanjutan';
            }
        }

        return ['kelasList' => $kelasList, 'jamList' => $jamList, 'grid' => $grid];
    }

    /**
     * Render ulang PARTIAL tabel grid utk satu hari — dipakai respons AJAX
     * store()/update()/delete() supaya JS bisa tukar HTML tabel tanpa
     * reload halaman.
     */
    private function renderGridTable(array $aktif, string $hariAktif): string
    {
        $gridData = $this->dataGridUntukHari($aktif, $hariAktif);

        return view('master/jadwal/_grid_table', $gridData);
    }

    public function store()
    {
        $aktif = (new SemesterModel())->getActive();
        $hariAktifForm = (string) $this->request->getPost('hari_aktif_grid');

        if (! $aktif) {
            if ($this->request->isAJAX()) {
                return $this->jsonRespons(['success' => false, 'message' => 'Tidak ada semester aktif. Aktifkan dulu di menu Tahun Ajaran & Semester.']);
            }

            return redirect()->to('/master/jadwal')->with('error', 'Tidak ada semester aktif. Aktifkan dulu di menu Tahun Ajaran & Semester.');
        }

        $result = $this->simpanJadwal($this->request->getPost(), (int) $aktif['id'], (int) $aktif['tahun_ajaran_id'], null);

        if ($result !== true) {
            if ($this->request->isAJAX()) {
                return $this->jsonRespons(['success' => false, 'message' => $result]);
            }

            return redirect()->to('/master/jadwal')->with('error', $result);
        }

        (new AuditLogger())->log('tambah_jadwal', 'Menambah jadwal mengajar');

        if ($this->request->isAJAX() && $hariAktifForm !== '') {
            return $this->jsonRespons([
                'success' => true,
                'message' => 'Jadwal berhasil ditambahkan.',
                'html'    => $this->renderGridTable($aktif, $hariAktifForm),
            ]);
        }

        return redirect()->to('/master/jadwal')->with('message', 'Jadwal berhasil ditambahkan.');
    }

    public function update()
    {
        $id    = (int) $this->request->getPost('id');
        $aktif = (new SemesterModel())->getActive();
        $hariAktifForm = (string) $this->request->getPost('hari_aktif_grid');

        if (! $aktif) {
            if ($this->request->isAJAX()) {
                return $this->jsonRespons(['success' => false, 'message' => 'Tidak ada semester aktif.']);
            }

            return redirect()->to('/master/jadwal')->with('error', 'Tidak ada semester aktif.');
        }

        $result = $this->simpanJadwal($this->request->getPost(), (int) $aktif['id'], (int) $aktif['tahun_ajaran_id'], $id);

        if ($result !== true) {
            if ($this->request->isAJAX()) {
                return $this->jsonRespons(['success' => false, 'message' => $result]);
            }

            return redirect()->to('/master/jadwal')->with('error', $result);
        }

        (new AuditLogger())->log('ubah_jadwal', 'Mengubah jadwal #' . $id);

        if ($this->request->isAJAX() && $hariAktifForm !== '') {
            return $this->jsonRespons([
                'success' => true,
                'message' => 'Jadwal berhasil diperbarui.',
                'html'    => $this->renderGridTable($aktif, $hariAktifForm),
            ]);
        }

        return redirect()->to('/master/jadwal')->with('message', 'Jadwal berhasil diperbarui.');
    }

    public function delete($id)
    {
        $hariAktifForm = (string) $this->request->getPost('hari_aktif_grid');

        $this->model->delete((int) $id);

        (new AuditLogger())->log('hapus_jadwal', 'Menghapus jadwal #' . $id);

        if ($this->request->isAJAX() && $hariAktifForm !== '') {
            $aktif = (new SemesterModel())->getActive();

            return $this->jsonRespons([
                'success' => true,
                'message' => 'Jadwal berhasil dihapus.',
                'html'    => $aktif ? $this->renderGridTable($aktif, $hariAktifForm) : '',
            ]);
        }

        return redirect()->to('/master/jadwal')->with('message', 'Jadwal berhasil dihapus.');
    }

    /**
     * Validasi bentrok (guru + kelas) lalu simpan/ubah satu baris jadwal.
     * Return TRUE kalau sukses, atau STRING pesan error kalau ditolak.
     */
    private function simpanJadwal(array $post, int $semesterId, int $tahunAjaranId, ?int $excludeId)
    {
        $guruPengampuId = (int) ($post['guru_pengampu_id'] ?? 0);
        $kelasId        = (int) ($post['kelas_id'] ?? 0);
        $hari           = $post['hari'] ?? '';
        $jamKeMulai     = (int) ($post['jam_ke_mulai'] ?? 0);
        $jamKeSelesai   = (int) ($post['jam_ke_selesai'] ?? 0);

        if (! $guruPengampuId || ! $kelasId || $hari === '' || ! $jamKeMulai || ! $jamKeSelesai) {
            return 'Semua field wajib diisi.';
        }

        // Guru Pengampu jadi SUMBER KEBENARAN guru_id/mapel_id — admin tidak
        // lagi memilih guru & mapel bebas terpisah, tapi lewat kombinasi yang
        // memang terdaftar berhak mengajar (menu Guru Pengampu).
        $pengampu = (new GuruPengampuModel())->find($guruPengampuId);
        if (! $pengampu) {
            return 'Guru Pengampu yang dipilih tidak ditemukan. Muat ulang halaman dan coba lagi.';
        }

        $kelas = (new KelasModel())->find($kelasId);
        if (! $kelas) {
            return 'Kelas yang dipilih tidak ditemukan.';
        }

        // Jaga-jaga kalau filter JS di form ter-lewati (mis. lewat request manual):
        // tingkat kelas HARUS cocok dengan tingkat Guru Pengampu yang dipilih.
        if ($kelas['tingkat'] !== $pengampu['tingkat']) {
            return "Guru Pengampu ini terdaftar untuk tingkat {$pengampu['tingkat']}, bukan tingkat {$kelas['tingkat']} (kelas {$kelas['nama_kelas']}).";
        }

        $guruId  = (int) $pengampu['guru_id'];
        $mapelId = (int) $pengampu['mapel_id'];

        if ($jamKeSelesai < $jamKeMulai) {
            return 'Jam ke-selesai tidak boleh sebelum jam ke-mulai.';
        }

        $jamModel  = new JamPelajaranModel();
        $jpMulai   = $jamModel->findByHariJamKe($hari, $jamKeMulai);
        $jpSelesai = $jamModel->findByHariJamKe($hari, $jamKeSelesai);

        if (! $jpMulai || ! $jpSelesai) {
            return "Jam ke-{$jamKeMulai} s/d ke-{$jamKeSelesai} belum diatur untuk hari {$hari}. Atur dulu di menu Jam Pelajaran.";
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
            'guru_id'          => $guruId,
            'mapel_id'         => $mapelId,
            'guru_pengampu_id' => $guruPengampuId,
            'kelas_id'         => $kelasId,
            'tahun_ajaran_id'  => $tahunAjaranId,
            'semester_id'      => $semesterId,
            'hari'             => $hari,
            'jam_ke_mulai'     => $jamKeMulai,
            'jam_ke_selesai'   => $jamKeSelesai,
            'jam_mulai'        => $jamMulai,
            'jam_selesai'      => $jamSelesai,
            'is_active'        => 1,
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
        $daftarJamKe = (new JamPelajaranModel())->getSemuaJamKeUnik();
        $semuaPengampu = (new GuruPengampuModel())->getSemuaLengkap();

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
        $sheet->setCellValue('H1', 'PENTING: kombinasi Guru+Mapel yang diisi HARUS sudah terdaftar');
        $sheet->setCellValue('H2', 'sebagai "Guru Pengampu" untuk tingkat kelas itu, lihat sheet');
        $sheet->setCellValue('H3', '"Referensi Guru Pengampu" di sebelah. Kalau belum ada, baris');
        $sheet->setCellValue('H4', 'itu akan DILEWATI saat import — daftarkan dulu lewat aplikasi.');
        $sheet->getStyle('H1:H4')->getFont()->setBold(true)->setItalic(true);

        // Sheet referensi: daftar Guru Pengampu yang SAH, supaya admin tahu
        // kombinasi mana saja yang valid SEBELUM mengisi, bukan baru tahu
        // setelah baris ditolak saat import.
        $sheetRef = $spreadsheet->createSheet();
        $sheetRef->setTitle('Referensi Guru Pengampu');
        $sheetRef->setCellValue('A1', 'Guru');
        $sheetRef->setCellValue('B1', 'Mata Pelajaran');
        $sheetRef->setCellValue('C1', 'Tingkat');
        $sheetRef->getStyle('A1:C1')->getFont()->setBold(true);
        $sheetRef->getColumnDimension('A')->setWidth(22);
        $sheetRef->getColumnDimension('B')->setWidth(22);
        $sheetRef->getColumnDimension('C')->setWidth(12);
        if (empty($semuaPengampu)) {
            $sheetRef->setCellValue('A2', 'Belum ada data Guru Pengampu — daftarkan dulu lewat menu Guru Pengampu di aplikasi.');
        } else {
            $baris = 2;
            foreach ($semuaPengampu as $p) {
                $sheetRef->setCellValue('A' . $baris, $p['nama_guru']);
                $sheetRef->setCellValue('B' . $baris, $p['nama_mapel']);
                $sheetRef->setCellValue('C' . $baris, $p['tingkat']);
                $baris++;
            }
        }

        // Daftar Guru/Mapel/Kelas ditaruh di sheet referensi lalu dropdown-nya
        // menunjuk ke RANGE sel itu (bukan daftar teks langsung di formula),
        // supaya tidak kena batas 255 karakter Excel yang bikin dropdown
        // "hilang sendiri" saat file dibuka kalau daftarnya panjang (banyak guru).
        $rentang = $this->buatSheetReferensi($spreadsheet, [
            'Guru'  => $daftarGuru,
            'Mapel' => $daftarMapel,
            'Kelas' => $daftarKelas,
        ]);

        $this->pasangDropdownRange($sheet, 'A', 300, $rentang['Guru'], true);
        $this->pasangDropdownRange($sheet, 'B', 300, $rentang['Mapel'], true);
        $this->pasangDropdownRange($sheet, 'C', 300, $rentang['Kelas'], true);
        $this->pasangDropdown($sheet, 'D', 300, 'Senin,Selasa,Rabu,Kamis,Jumat,Sabtu', true);
        $this->pasangDropdown($sheet, 'E', 300, implode(',', $daftarJamKe), true);
        $this->pasangDropdown($sheet, 'F', 300, implode(',', $daftarJamKe), true);

        $spreadsheet->setActiveSheetIndex(0);

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

    /**
     * Template Excel BENTUK GRID (hari & jam di baris, kelas di kolom — meniru
     * jadwal dinding yang biasa dipakai sekolah), sebagai alternatif dari
     * template daftar panjang di atas. Setiap kelas dapat 2 kolom: Guru & Mapel,
     * masing-masing dropdown sendiri.
     */
    public function downloadTemplateGrid()
    {
        $aktif = (new SemesterModel())->getActive();

        if (! $aktif) {
            return redirect()->to('/master/jadwal')->with('error', 'Tidak ada semester aktif, tidak bisa membuat template.');
        }

        $daftarGuru  = array_column((new GuruModel())->where('status', 'aktif')->findAll(), 'nama');
        $daftarMapel = array_column((new MataPelajaranModel())->findAll(), 'nama');
        $kelasList   = (new KelasModel())->where('tahun_ajaran_id', $aktif['tahun_ajaran_id'])
            ->orderBy('tingkat', 'ASC')->orderBy('nama_kelas', 'ASC')->findAll();
        $jamPerHari  = (new JamPelajaranModel())->getSemuaDikelompokkan();
        $hariList    = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        if (empty($kelasList)) {
            return redirect()->to('/master/jadwal')->with('error', 'Belum ada data kelas untuk tahun ajaran aktif.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Grid Jadwal');

        // Sheet referensi Guru Pengampu — sama seperti template daftar panjang,
        // supaya admin tahu kombinasi guru+mapel yang SAH per tingkat sebelum
        // mengisi (bukan baru tahu setelah baris ditolak saat import).
        $semuaPengampu = (new GuruPengampuModel())->getSemuaLengkap();
        $sheetRef      = $spreadsheet->createSheet();
        $sheetRef->setTitle('Referensi Guru Pengampu');
        $sheetRef->setCellValue('A1', 'Guru');
        $sheetRef->setCellValue('B1', 'Mata Pelajaran');
        $sheetRef->setCellValue('C1', 'Tingkat');
        $sheetRef->getStyle('A1:C1')->getFont()->setBold(true);
        $sheetRef->getColumnDimension('A')->setWidth(22);
        $sheetRef->getColumnDimension('B')->setWidth(22);
        $sheetRef->getColumnDimension('C')->setWidth(12);
        if (empty($semuaPengampu)) {
            $sheetRef->setCellValue('A2', 'Belum ada data Guru Pengampu — daftarkan dulu lewat menu Guru Pengampu di aplikasi.');
        } else {
            $barisRef = 2;
            foreach ($semuaPengampu as $p) {
                $sheetRef->setCellValue('A' . $barisRef, $p['nama_guru']);
                $sheetRef->setCellValue('B' . $barisRef, $p['nama_mapel']);
                $sheetRef->setCellValue('C' . $barisRef, $p['tingkat']);
                $barisRef++;
            }
        }
        $spreadsheet->setActiveSheetIndex(0);

        // Header baris 1: Hari, Jam Ke, Jam (referensi baca saja, jamnya beda
        // tiap hari jadi ditampilkan supaya admin tidak perlu bolak-balik cek
        // menu Jam Pelajaran), lalu nama tiap kelas (merge 2 kolom: Guru+Mapel)
        $sheet->setCellValue([1, 1], 'Hari');
        $sheet->setCellValue([2, 1], 'Jam Ke');
        $sheet->setCellValue([3, 1], 'Jam (referensi)');
        $sheet->mergeCells('A1:A2');
        $sheet->mergeCells('B1:B2');
        $sheet->mergeCells('C1:C2');

        // setCellValueByColumnAndRow() DAN seluruh method "ByColumnAndRow" lainnya
        // (mergeCellsByColumnAndRow, getColumnDimensionByColumn, dst) sudah dihapus
        // di versi PhpSpreadsheet terbaru, jadi semuanya dikonversi dulu ke koordinat
        // huruf (A, B, C, ...) lewat Coordinate::stringFromColumnIndex() yang aman dipakai.
        $kolom = 4;
        foreach ($kelasList as $k) {
            $hurufMulai = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($kolom);
            $hurufAkhir = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($kolom + 1);
            $sheet->setCellValue([$kolom, 1], $k['nama_kelas']);
            $sheet->mergeCells($hurufMulai . '1:' . $hurufAkhir . '1');
            $sheet->setCellValue([$kolom, 2], 'Guru');
            $sheet->setCellValue([$kolom + 1, 2], 'Mapel');
            $kolom += 2;
        }

        $totalKolom = 3 + (count($kelasList) * 2);
        $hurufTerakhir = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalKolom);
        $sheet->getStyle('A1:' . $hurufTerakhir . '2')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(9);
        $sheet->getColumnDimension('C')->setWidth(15);
        for ($c = 4; $c <= $totalKolom; $c++) {
            $hurufKolom = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $sheet->getColumnDimension($hurufKolom)->setWidth(16);
        }

        // Baris data: setiap kombinasi Hari x Jam Ke MILIK HARI ITU SENDIRI (jam
        // bisa beda tiap hari) — Hari, Jam Ke, dan Jam referensi sudah diisikan,
        // sisanya (Guru/Mapel per kelas) dikosongkan untuk diisi admin.
        $baris = 3;
        foreach ($hariList as $hari) {
            foreach ($jamPerHari[$hari] as $jp) {
                $sheet->setCellValue([1, $baris], $hari);
                $sheet->setCellValue([2, $baris], $jp['jam_ke']);
                $sheet->setCellValue([3, $baris], substr($jp['jam_mulai'], 0, 5) . '-' . substr($jp['jam_selesai'], 0, 5));
                $baris++;
            }
        }
        $barisTerakhir = $baris - 1;

        // Daftar Guru/Mapel ditaruh di sheet referensi, dropdown menunjuk ke RANGE
        // sel itu (bukan daftar teks langsung di formula) — kalau dipakai teks
        // langsung, daftar guru yang panjang gampang lewat batas 255 karakter
        // formula Excel, dan dropdown-nya jadi "hilang sendiri" saat file dibuka.
        $rentang = $this->buatSheetReferensi($spreadsheet, [
            'Guru'  => $daftarGuru,
            'Mapel' => $daftarMapel,
        ]);

        // Dropdown Hari (kolom A, daftarnya cuma 6 item jadi aman dipakai langsung)
        $this->pasangDropdown($sheet, 'A', $barisTerakhir, implode(',', $hariList), true, 3);

        // Dropdown Guru/Mapel per kelas (kolom D dst, berselang-seling)
        $kolom = 4;
        foreach ($kelasList as $k) {
            $hurufGuru  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($kolom);
            $hurufMapel = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($kolom + 1);
            $this->pasangDropdownRange($sheet, $hurufGuru, $barisTerakhir, $rentang['Guru'], false, 3);
            $this->pasangDropdownRange($sheet, $hurufMapel, $barisTerakhir, $rentang['Mapel'], false, 3);
            $kolom += 2;
        }

        $sheet->freezePane('D3');
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        return $this->streamXlsx($writer, 'template_grid_jadwal.xlsx');
    }

    /**
     * Import dari template BENTUK GRID. Kelas yang sama dengan guru+mapel yang
     * sama di jam-jam yang berurutan (mis. jam ke-1 dan ke-2) otomatis DIGABUNG
     * jadi satu jadwal yang membentang 2 jam, bukan dianggap 2 sesi terpisah —
     * supaya di dashboard guru tetap muncul sebagai SATU sesi mengajar, sesuai
     * bagaimana presensi & jurnal memang dirancang (satu per sesi, bukan per jam).
     * Setelah digabung, baris-barisnya diproses lewat JadwalModel::importRows()
     * yang SAMA PERSIS dipakai template daftar panjang — jadi validasi anti-
     * bentroknya juga identik.
     */
    public function importGrid()
    {
        $aktif = (new SemesterModel())->getActive();

        if (! $aktif) {
            return redirect()->to('/master/jadwal')->with('error', 'Tidak ada semester aktif.');
        }

        $file = $this->request->getFile('file_excel_grid');

        if (! $file || ! $file->isValid()) {
            return redirect()->to('/master/jadwal')->with('error', 'File Excel tidak valid atau belum dipilih.');
        }

        if (! in_array(strtolower($file->getClientExtension()), ['xlsx', 'xls', 'csv'], true)) {
            return redirect()->to('/master/jadwal')->with('error', 'Format file harus xlsx, xls, atau csv.');
        }

        $kelasList = (new KelasModel())->where('tahun_ajaran_id', $aktif['tahun_ajaran_id'])
            ->orderBy('tingkat', 'ASC')->orderBy('nama_kelas', 'ASC')->findAll();

        $spreadsheet = IOFactory::load($file->getTempName());
        $rawRows     = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        array_splice($rawRows, 0, 2); // buang 2 baris header (nama kelas + Guru/Mapel)

        // Ratakan grid jadi baris per (hari, jam_ke, kelas), lalu gabungkan
        // jam yang berurutan dengan guru+mapel sama jadi satu rentang.
        $flat = [];
        foreach ($rawRows as $r) {
            $hari  = trim((string) ($r[0] ?? ''));
            $jamKe = (int) ($r[1] ?? 0);
            if ($hari === '' || ! $jamKe) {
                continue;
            }

            $kolom = 3; // index 0-based: kolom D = index 3 (kolom C/index 2 = Jam referensi, dilewati — bukan data)
            foreach ($kelasList as $k) {
                $guru  = trim((string) ($r[$kolom] ?? ''));
                $mapel = trim((string) ($r[$kolom + 1] ?? ''));
                $kolom += 2;

                if ($guru === '' || $mapel === '') {
                    continue; // slot kosong utk kelas ini di jam ini
                }

                $flat[] = ['hari' => $hari, 'jam_ke' => $jamKe, 'kelas' => $k['nama_kelas'], 'guru' => $guru, 'mapel' => $mapel];
            }
        }

        // Kelompokkan per (hari, kelas), urutkan per jam_ke, gabung yang berurutan & sama.
        $kelompok = [];
        foreach ($flat as $r) {
            $kelompok[$r['hari'] . '|' . $r['kelas']][] = $r;
        }

        $rows = [];
        foreach ($kelompok as $daftar) {
            usort($daftar, static fn ($a, $b) => $a['jam_ke'] <=> $b['jam_ke']);

            $sesi = null;
            foreach ($daftar as $r) {
                if ($sesi && $sesi['guru'] === $r['guru'] && $sesi['mapel'] === $r['mapel'] && $r['jam_ke'] === $sesi['jam_ke_selesai'] + 1) {
                    $sesi['jam_ke_selesai'] = $r['jam_ke'];
                    continue;
                }

                if ($sesi) {
                    $rows[] = $sesi;
                }
                $sesi = [
                    'hari' => $r['hari'], 'kelas' => $r['kelas'], 'guru' => $r['guru'], 'mapel' => $r['mapel'],
                    'jam_ke_mulai' => $r['jam_ke'], 'jam_ke_selesai' => $r['jam_ke'],
                ];
            }
            if ($sesi) {
                $rows[] = $sesi;
            }
        }

        $hasil = $this->model->importRows($rows, (int) $aktif['tahun_ajaran_id'], (int) $aktif['id']);

        (new AuditLogger())->log('import_jadwal_grid', "Import grid jadwal: {$hasil['sukses']} sukses, {$hasil['gagal']} gagal");

        $pesan = "Import selesai: {$hasil['sukses']} sesi mengajar berhasil ditambahkan (jam berurutan otomatis digabung), {$hasil['gagal']} dilewati.";
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
     * Buat sheet tersembunyi berisi daftar-daftar panjang (Guru, Mapel, dst),
     * masing-masing di satu kolom. Dipakai sebagai SUMBER dropdown lewat
     * referensi RANGE sel (bukan daftar teks langsung di formula validasi),
     * supaya tidak kena batas 255 karakter Excel yang bikin dropdown daftar
     * panjang (banyak guru/mapel) "hilang sendiri" begitu file dibuka.
     * Return array nama => string range, mis. "Referensi!$A$2:$A$15".
     */
    private function buatSheetReferensi(Spreadsheet $spreadsheet, array $daftarPerNama): array
    {
        $ref = $spreadsheet->createSheet();
        $ref->setTitle('Referensi');

        $rentang = [];
        $kolom   = 1;

        foreach ($daftarPerNama as $nama => $daftar) {
            $huruf = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($kolom);
            $ref->setCellValue([$kolom, 1], $nama);

            $baris = 2;
            foreach ($daftar as $nilai) {
                $ref->setCellValue([$kolom, $baris], $nilai);
                $baris++;
            }

            // Minimal 1 baris data (baris 2) supaya formula range tetap valid walau daftarnya kosong.
            $barisAkhir     = max(2, $baris - 1);
            $rentang[$nama] = "Referensi!\${$huruf}\$2:\${$huruf}\${$barisAkhir}";
            $kolom++;
        }

        try {
            $ref->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
        } catch (\Throwable $e) {
            // Kalau method ini bermasalah di versi tertentu, biarkan sheet-nya tetap
            // terlihat (cuma kurang rapi) daripada ikut menggagalkan pembuatan template.
        }

        return $rentang;
    }

    /**
     * Sama seperti pasangDropdown(), tapi Formula1-nya berupa REFERENSI RANGE sel
     * (mis. "Referensi!$A$2:$A$15") alih-alih daftar teks langsung — dipakai
     * untuk daftar yang berpotensi panjang (Guru, Mapel, Kelas) supaya aman dari
     * batas 255 karakter Excel.
     */
    private function pasangDropdownRange($sheet, string $kolom, int $sampaiBaris, string $formulaRange, bool $wajib, int $mulaiBaris = 2): void
    {
        for ($baris = $mulaiBaris; $baris <= $sampaiBaris; $baris++) {
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
            $validation->setFormula1($formulaRange);
        }
    }

    /**
     * Pasang dropdown pilihan pada satu kolom, dari baris $mulaiBaris sampai $sampaiBaris
     * (default mulai baris 2, dipakai template daftar-panjang yang headernya cuma 1 baris;
     * template grid pakai header 2 baris jadi datanya baru mulai baris 3).
     * Diterapkan satu sel satu sel supaya kompatibel di semua versi PhpSpreadsheet.
     */
    private function pasangDropdown($sheet, string $kolom, int $sampaiBaris, string $daftarKoma, bool $wajib, int $mulaiBaris = 2): void
    {
        for ($baris = $mulaiBaris; $baris <= $sampaiBaris; $baris++) {
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
