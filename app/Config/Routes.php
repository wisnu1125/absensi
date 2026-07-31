<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ----------------------------------------------------------------------------
// AUTH (publik, tanpa login)
// ----------------------------------------------------------------------------
$routes->get('/', 'Auth::index');
$routes->get('login', 'Auth::index');
$routes->post('login', 'Auth::login');
$routes->get('logout', 'Auth::logout');

// ----------------------------------------------------------------------------
// AREA WAJIB LOGIN (semua role)
// ----------------------------------------------------------------------------
$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes) {

    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('jadwal-hari-ini', 'Dashboard::jadwalHariIniPage');
    $routes->get('kalender-akademik', 'KalenderAkademik::index');

    // ------------------------------------------------------------------------
    // FASE 2 — Data Master (khusus administrator + operator)
    // ------------------------------------------------------------------------
    $routes->group('master', ['namespace' => 'App\Controllers\Master', 'filter' => 'role:administrator,operator'], static function (RouteCollection $routes) {

        // Mata pelajaran
        $routes->get('mata-pelajaran', 'MataPelajaran::index');
        $routes->post('mata-pelajaran/store', 'MataPelajaran::store');
        $routes->post('mata-pelajaran/update', 'MataPelajaran::update');
        $routes->post('mata-pelajaran/delete/(:num)', 'MataPelajaran::delete/$1');

        $routes->get('guru-pengampu', 'GuruPengampu::index');
        $routes->post('guru-pengampu/store', 'GuruPengampu::store');
        $routes->post('guru-pengampu/update', 'GuruPengampu::update');
        $routes->post('guru-pengampu/delete/(:num)', 'GuruPengampu::delete/$1');

        $routes->get('tujuan-pembelajaran', 'TujuanPembelajaran::index');
        $routes->post('tujuan-pembelajaran/store', 'TujuanPembelajaran::store');
        $routes->post('tujuan-pembelajaran/update', 'TujuanPembelajaran::update');
        $routes->post('tujuan-pembelajaran/delete/(:num)', 'TujuanPembelajaran::delete/$1');

        // Tahun ajaran & semester (satu controller, dua sub-resource)
        $routes->get('tahun-ajaran', 'TahunAjaran::index');
        $routes->post('tahun-ajaran/store', 'TahunAjaran::store');
        $routes->post('tahun-ajaran/aktifkan/(:num)', 'TahunAjaran::setActive/$1');
        $routes->post('tahun-ajaran/delete/(:num)', 'TahunAjaran::delete/$1');
        $routes->post('tahun-ajaran/semester/store', 'TahunAjaran::storeSemester');
        $routes->post('tahun-ajaran/semester/update', 'TahunAjaran::updateSemester');
        $routes->post('tahun-ajaran/semester/aktifkan/(:num)', 'TahunAjaran::setActiveSemester/$1');
        $routes->post('tahun-ajaran/semester/delete/(:num)', 'TahunAjaran::deleteSemester/$1');

        // Kelas
        $routes->get('kelas', 'Kelas::index');
        $routes->post('kelas/store', 'Kelas::store');
        $routes->post('kelas/update', 'Kelas::update');
        $routes->post('kelas/delete/(:num)', 'Kelas::delete/$1');

        // Guru (+ import Excel)
        $routes->get('guru', 'Guru::index');
        $routes->post('guru/store', 'Guru::store');
        $routes->post('guru/update', 'Guru::update');
        $routes->post('guru/delete/(:num)', 'Guru::delete/$1');
        $routes->get('guru/template', 'Guru::downloadTemplate');
        $routes->post('guru/import', 'Guru::import');

        // Siswa (+ import Excel)
        $routes->get('siswa', 'Siswa::index');
        $routes->post('siswa/store', 'Siswa::store');
        $routes->post('siswa/update', 'Siswa::update');
        $routes->post('siswa/delete/(:num)', 'Siswa::delete/$1');
        $routes->post('siswa/bulk-delete', 'Siswa::bulkDelete');
        $routes->get('siswa/template', 'Siswa::downloadTemplate');
        $routes->post('siswa/import', 'Siswa::import');

        // Jadwal mengajar (+ validasi bentrok guru & kelas, + import Excel, + tampilan grid)
        $routes->get('jadwal', 'Jadwal::index');
        $routes->get('jadwal/grid', 'Jadwal::grid');
        $routes->post('jadwal/store', 'Jadwal::store');
        $routes->post('jadwal/update', 'Jadwal::update');
        $routes->post('jadwal/delete/(:num)', 'Jadwal::delete/$1');
        $routes->get('jadwal/template', 'Jadwal::downloadTemplate');
        $routes->post('jadwal/import', 'Jadwal::import');
        $routes->get('jadwal/template-grid', 'Jadwal::downloadTemplateGrid');
        $routes->post('jadwal/import-grid', 'Jadwal::importGrid');

        // Jam pelajaran (beda-beda tiap sekolah, jadi wajib bisa diatur sendiri)
        $routes->get('jam-pelajaran', 'JamPelajaran::index');
        $routes->post('jam-pelajaran/store', 'JamPelajaran::store');
        $routes->post('jam-pelajaran/update', 'JamPelajaran::update');
        $routes->post('jam-pelajaran/delete/(:num)', 'JamPelajaran::delete/$1');

        // Pengumuman (widget Dashboard Admin)
        $routes->get('pengumuman', 'Pengumuman::index');
        $routes->post('pengumuman/store', 'Pengumuman::store');
        $routes->post('pengumuman/update', 'Pengumuman::update');
        $routes->post('pengumuman/delete/(:num)', 'Pengumuman::delete/$1');

        // Pertukaran jadwal (tahap 2 - persetujuan akhir Admin/Waka Kurikulum)
        $routes->get('pertukaran-jadwal', 'PertukaranJadwal::index');
        $routes->post('pertukaran-jadwal/setuju/(:num)', 'PertukaranJadwal::setuju/$1');
        $routes->post('pertukaran-jadwal/tolak/(:num)', 'PertukaranJadwal::tolak/$1');
        $routes->post('pertukaran-jadwal/batalkan/(:num)', 'PertukaranJadwal::batalkan/$1');
    });

    // ------------------------------------------------------------------------
    // FASE 6 — Pengguna & Role (khusus administrator, lebih sensitif dari data master lain)
    // ------------------------------------------------------------------------
    $routes->group('master', ['namespace' => 'App\Controllers\Master', 'filter' => 'role:administrator'], static function (RouteCollection $routes) {
        $routes->get('pengguna', 'Pengguna::index');
        $routes->post('pengguna/store', 'Pengguna::store');
        $routes->post('pengguna/update', 'Pengguna::update');
        $routes->post('pengguna/delete/(:num)', 'Pengguna::delete/$1');

        $routes->get('audit-log', 'AuditLog::index');

        $routes->get('sampah', 'Sampah::index');
        $routes->post('sampah/restore/(:segment)/(:num)', 'Sampah::restore/$1/$2');
    });

    // ------------------------------------------------------------------------
    // FASE 4 — Alur inti: Mulai Mengajar -> Presensi -> Jurnal (khusus guru)
    // ------------------------------------------------------------------------
    $routes->group('mengajar', ['filter' => 'role:guru'], static function (RouteCollection $routes) {
        $routes->get('presensi/(:num)', 'Mengajar::presensi/$1');
        $routes->post('presensi/(:num)', 'Mengajar::simpanPresensi/$1');
        $routes->get('jurnal/(:num)', 'Mengajar::jurnal/$1');
        $routes->post('jurnal/(:num)', 'Mengajar::simpanJurnal/$1');
        $routes->get('riwayat', 'Mengajar::riwayat');
        $routes->get('riwayat/detail/(:num)', 'Mengajar::riwayatDetail/$1');
        $routes->get('riwayat/revisi-presensi/(:num)', 'Mengajar::revisiPresensi/$1');
        $routes->post('riwayat/revisi-presensi/(:num)', 'Mengajar::simpanRevisiPresensi/$1');
        $routes->get('riwayat/revisi-jurnal/(:num)', 'Mengajar::revisiJurnal/$1');
        $routes->post('riwayat/revisi-jurnal/(:num)', 'Mengajar::simpanRevisiJurnal/$1');
        $routes->get('riwayat/isi-jurnal/(:num)', 'Mengajar::isiJurnalTerlewat/$1');
        $routes->post('riwayat/isi-jurnal/(:num)', 'Mengajar::simpanJurnalTerlewat/$1');
        $routes->get('riwayat/hari-terlewat', 'Mengajar::hariTerlewat');
        $routes->get('riwayat/hari-terlewat/presensi/(:num)/(:segment)', 'Mengajar::presensiHariTerlewat/$1/$2');
        $routes->post('riwayat/hari-terlewat/presensi/(:num)/(:segment)', 'Mengajar::simpanPresensiHariTerlewat/$1/$2');
        $routes->get('riwayat/hari-terlewat/jurnal/(:num)/(:segment)', 'Mengajar::jurnalHariTerlewat/$1/$2');
        $routes->post('riwayat/hari-terlewat/jurnal/(:num)/(:segment)', 'Mengajar::simpanJurnalHariTerlewat/$1/$2');
        $routes->get('kalender', 'Mengajar::kalender');
    });

    // Master Tujuan Pembelajaran (TP): dikelola guru sendiri per Guru Pengampu
    $routes->group('tujuan-pembelajaran', ['filter' => 'role:guru'], static function (RouteCollection $routes) {
        $routes->get('/', 'TujuanPembelajaran::index');
        $routes->post('store', 'TujuanPembelajaran::store');
        $routes->post('update', 'TujuanPembelajaran::update');
        $routes->post('delete/(:num)', 'TujuanPembelajaran::delete/$1');
    });

    // Wali kelas: rekap kehadiran & data siswa untuk kelas yang ditugaskan
    $routes->get('wali-kelas', 'WaliKelas::index', ['filter' => 'role:wali_kelas']);

    // Tukar jadwal: pengajuan guru pengganti untuk satu sesi tertentu
    $routes->group('tukar-jadwal', ['filter' => 'role:guru'], static function (RouteCollection $routes) {
        $routes->get('/', 'TukarJadwal::index');
        $routes->post('ajukan', 'TukarJadwal::ajukan');
        $routes->post('setuju/(:num)', 'TukarJadwal::setuju/$1');
        $routes->post('tolak/(:num)', 'TukarJadwal::tolak/$1');
        $routes->post('batal/(:num)', 'TukarJadwal::batal/$1');
    });

    // Pertukaran jadwal: tukar SLOT PENUH (hari+jam) antar 2 guru, bukan guru pengganti
    $routes->group('jadwal-swap', ['filter' => 'role:guru'], static function (RouteCollection $routes) {
        $routes->get('/', 'JadwalSwap::index');
        $routes->post('ajukan', 'JadwalSwap::ajukan');
        $routes->post('setuju-guru/(:num)', 'JadwalSwap::setujuGuru/$1');
        $routes->post('tolak-guru/(:num)', 'JadwalSwap::tolakGuru/$1');
        $routes->post('batal/(:num)', 'JadwalSwap::batal/$1');
    });

    // ------------------------------------------------------------------------
    // FASE 5 — Laporan: DomPDF (PDF) & PhpSpreadsheet (Excel)
    // ------------------------------------------------------------------------
    $routes->group('laporan', ['filter' => 'role:administrator,operator,kepala_sekolah'], static function (RouteCollection $routes) {
        $routes->get('presensi', 'Laporan::presensi');
        $routes->get('presensi/export-pdf', 'Laporan::presensiExportPdf');
        $routes->get('presensi/export-excel', 'Laporan::presensiExportExcel');
        $routes->get('jurnal', 'Laporan::jurnal');
        $routes->get('jurnal/export-pdf', 'Laporan::jurnalExportPdf');
        $routes->get('jurnal/export-excel', 'Laporan::jurnalExportExcel');
        $routes->get('tukar-jadwal', 'Laporan::tukarJadwal');
    });

    // Override admin: batalkan pengajuan Cari Guru Pengganti milik guru manapun
    $routes->post('laporan/tukar-jadwal/batalkan/(:num)', 'Laporan::tukarJadwalBatalkan/$1', ['filter' => 'role:administrator,operator']);

    // Kalender Akademik: SEMUA role bisa lihat (rute GET di grup 'auth' biasa di atas),
    // tapi CRUD (tambah/ubah/hapus event) KHUSUS Administrator.
    $routes->post('kalender-akademik/store', 'KalenderAkademik::store', ['filter' => 'role:administrator']);
    $routes->post('kalender-akademik/update', 'KalenderAkademik::update', ['filter' => 'role:administrator']);
    $routes->post('kalender-akademik/delete/(:num)', 'KalenderAkademik::delete/$1', ['filter' => 'role:administrator']);
});
