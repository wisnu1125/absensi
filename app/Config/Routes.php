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

    // ------------------------------------------------------------------------
    // FASE 2 — Data Master (khusus administrator + operator)
    // ------------------------------------------------------------------------
    $routes->group('master', ['namespace' => 'App\Controllers\Master', 'filter' => 'role:administrator,operator'], static function (RouteCollection $routes) {

        // Mata pelajaran
        $routes->get('mata-pelajaran', 'MataPelajaran::index');
        $routes->post('mata-pelajaran/store', 'MataPelajaran::store');
        $routes->post('mata-pelajaran/update', 'MataPelajaran::update');
        $routes->post('mata-pelajaran/delete/(:num)', 'MataPelajaran::delete/$1');

        // Tahun ajaran & semester (satu controller, dua sub-resource)
        $routes->get('tahun-ajaran', 'TahunAjaran::index');
        $routes->post('tahun-ajaran/store', 'TahunAjaran::store');
        $routes->post('tahun-ajaran/aktifkan/(:num)', 'TahunAjaran::setActive/$1');
        $routes->post('tahun-ajaran/delete/(:num)', 'TahunAjaran::delete/$1');
        $routes->post('tahun-ajaran/semester/store', 'TahunAjaran::storeSemester');
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
        $routes->get('siswa/template', 'Siswa::downloadTemplate');
        $routes->post('siswa/import', 'Siswa::import');

        // Jadwal mengajar (+ validasi bentrok guru & kelas, + import Excel)
        $routes->get('jadwal', 'Jadwal::index');
        $routes->post('jadwal/store', 'Jadwal::store');
        $routes->post('jadwal/update', 'Jadwal::update');
        $routes->post('jadwal/delete/(:num)', 'Jadwal::delete/$1');
        $routes->get('jadwal/template', 'Jadwal::downloadTemplate');
        $routes->post('jadwal/import', 'Jadwal::import');

        // Jam pelajaran (beda-beda tiap sekolah, jadi wajib bisa diatur sendiri)
        $routes->get('jam-pelajaran', 'JamPelajaran::index');
        $routes->post('jam-pelajaran/store', 'JamPelajaran::store');
        $routes->post('jam-pelajaran/update', 'JamPelajaran::update');
        $routes->post('jam-pelajaran/delete/(:num)', 'JamPelajaran::delete/$1');

        // Hari libur (kalender akademik)
        $routes->get('hari-libur', 'HariLibur::index');
        $routes->post('hari-libur/store', 'HariLibur::store');
        $routes->post('hari-libur/update', 'HariLibur::update');
        $routes->post('hari-libur/delete/(:num)', 'HariLibur::delete/$1');
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
        $routes->get('kalender', 'Mengajar::kalender');
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
});
