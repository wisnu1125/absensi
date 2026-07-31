<?php
/**
 * Auth & RBAC Helper
 *
 * Kumpulan fungsi global untuk membaca sesi user yang sedang login
 * dan mengecek role yang dimilikinya. Dipakai di Controller maupun View.
 *
 * CARA AKTIFKAN:
 * Tambahkan 'auth' ke $helpers di app/Config/Autoload.php, contoh:
 *   public $helpers = ['auth'];
 * atau panggil helper('auth') di awal method Controller yang butuh.
 */

if (! function_exists('current_user')) {
    /**
     * Ambil data user yang sedang login dari session.
     * Return null jika belum login.
     */
    function current_user(): ?array
    {
        $session = session();

        if (! $session->get('isLoggedIn')) {
            return null;
        }

        return [
            'id'        => $session->get('user_id'),
            'username'  => $session->get('username'),
            'full_name' => $session->get('full_name'),
            'roles'     => $session->get('roles') ?? [],
        ];
    }
}

if (! function_exists('has_role')) {
    /**
     * Cek apakah user yang login memiliki 1 role tertentu.
     */
    function has_role(string $roleSlug): bool
    {
        $roles = session()->get('roles') ?? [];

        return in_array($roleSlug, $roles, true);
    }
}

if (! function_exists('has_any_role')) {
    /**
     * Cek apakah user yang login memiliki SALAH SATU dari beberapa role.
     * Karena satu user bisa multi role, ini yang paling sering dipakai
     * untuk menampilkan menu/sidebar.
     */
    function has_any_role(array $roleSlugs): bool
    {
        $roles = session()->get('roles') ?? [];

        return count(array_intersect($roleSlugs, $roles)) > 0;
    }
}

if (! function_exists('role_label')) {
    /**
     * Ubah slug role jadi label rapi untuk ditampilkan, contoh:
     * 'wali_kelas' -> 'Wali Kelas'
     */
    function role_label(string $roleSlug): string
    {
        return ucwords(str_replace('_', ' ', $roleSlug));
    }
}

if (! function_exists('badge_jadwal_hari_ini')) {
    /**
     * Jumlah jadwal hari ini milik guru yang login yang masih butuh tindakan
     * (belum ada presensi terkunci, dan bukan yang sedang digantikan orang lain).
     * Sengaja pakai query ringan (bukan ScheduleResolverService penuh) karena
     * ini dihitung di SETIAP halaman untuk sidebar — cukup sebagai indikator
     * cepat, detail akurat lengkap tetap ada di halaman Dashboard itu sendiri.
     */
    function badge_jadwal_hari_ini(): int
    {
        if (! has_role('guru')) {
            return 0;
        }

        $guru = (new \App\Models\GuruModel())->findByUserId((int) session()->get('user_id'));
        if (! $guru) {
            return 0;
        }

        $aktif = (new \App\Models\SemesterModel())->getActive();
        if (! $aktif) {
            return 0;
        }

        $hariMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => null];
        $hariIni = $hariMap[(int) date('N')] ?? null;
        if (! $hariIni) {
            return 0;
        }

        $today = date('Y-m-d');
        $db    = db_connect();

        $sql = "SELECT COUNT(*) as jumlah FROM jadwal j
                WHERE j.guru_id = ? AND j.hari = ? AND j.semester_id = ?
                AND NOT EXISTS (SELECT 1 FROM presensi p WHERE p.jadwal_id = j.id AND p.tanggal = ? AND p.locked_at IS NOT NULL)
                AND NOT EXISTS (SELECT 1 FROM tukar_jadwal tj WHERE tj.jadwal_id = j.id AND tj.tanggal = ? AND tj.status = 'disetujui')";

        $row = $db->query($sql, [$guru['id'], $hariIni, $aktif['id'], $today, $today])->getRow();

        return (int) ($row->jumlah ?? 0);
    }
}

if (! function_exists('badge_cari_guru_pengganti')) {
    /**
     * Jumlah pengajuan "Cari Guru Pengganti" yang menunggu respon guru yang login.
     */
    function badge_cari_guru_pengganti(): int
    {
        if (! has_role('guru')) {
            return 0;
        }

        $guru = (new \App\Models\GuruModel())->findByUserId((int) session()->get('user_id'));
        if (! $guru) {
            return 0;
        }

        return (new \App\Models\TukarJadwalModel())->hitungMenungguUntukGuru((int) $guru['id']);
    }
}

if (! function_exists('badge_jurnal_terlewat')) {
    /**
     * Jumlah sesi yang butuh perhatian di Kalender Jadwal — gabungan dua
     * kondisi: (1) presensi ada tapi jurnal belum diisi, dan (2) presensi-nya
     * SENDIRI belum pernah diisi sama sekali ("hari terlewat" — direkonstruksi
     * dari pola jadwal mingguan, bukan dari tabel presensi yang justru kosong).
     *
     * Jendela pengecekan dibatasi MAKSIMAL 30 hari ke belakang, TAPI tidak
     * pernah melewati tanggal_mulai/tanggal_selesai semester aktif (kalau
     * sudah diisi) — supaya tidak salah menandai hari SEBELUM semester
     * dimulai (yang jadwalnya memang belum berlaku) sebagai "terlewat".
     */
    function badge_jurnal_terlewat(): int
    {
        if (! has_role('guru')) {
            return 0;
        }

        $guru = (new \App\Models\GuruModel())->findByUserId((int) session()->get('user_id'));
        if (! $guru) {
            return 0;
        }

        $aktif = (new \App\Models\SemesterModel())->getActive();
        if (! $aktif) {
            return 0;
        }

        [$mulai, $kemarin] = jendela_terlewat($aktif);
        if ($mulai === null) {
            return 0; // di luar rentang semester (mis. semester belum mulai sama sekali)
        }

        $db = db_connect();

        // (1) presensi ada, jurnal belum.
        $sqlJurnal = "SELECT COUNT(*) as jumlah FROM presensi p
                JOIN jadwal j ON j.id = p.jadwal_id
                WHERE j.guru_id = ?
                AND p.tanggal >= ? AND p.tanggal <= ?
                AND NOT EXISTS (SELECT 1 FROM jurnal_mengajar jm WHERE jm.jadwal_id = p.jadwal_id AND jm.tanggal = p.tanggal)";
        $jumlahJurnal = (int) ($db->query($sqlJurnal, [$guru['id'], $mulai, $kemarin])->getRow()->jumlah ?? 0);

        // (2) hari yang presensinya sendiri belum ada sama sekali — direkonstruksi
        // dari pola hari jadwal, dicek ringan (tanpa bikin daftar detail lengkap).
        $jadwalSaya = (new \App\Models\JadwalModel())->select('id, hari')
            ->where('guru_id', (int) $guru['id'])->where('semester_id', (int) $aktif['id'])->findAll();

        $jumlahHari = 0;
        if (! empty($jadwalSaya)) {
            $jadwalIds = array_column($jadwalSaya, 'id');
            $rows = (new \App\Models\PresensiModel())->select('jadwal_id, tanggal')
                ->whereIn('jadwal_id', $jadwalIds)->where('tanggal >=', $mulai)->where('tanggal <=', $kemarin)->findAll();
            $presensiAda = [];
            foreach ($rows as $r) {
                $presensiAda[$r['jadwal_id'] . '|' . $r['tanggal']] = true;
            }

            $agendaNonaktif = [];
            foreach ((new \App\Models\AgendaAkademikModel())->getEventRentang($mulai, $kemarin) as $ev) {
                if ($ev['dampak_presensi'] === 'nonaktif' && $ev['status'] !== 'dibatalkan') {
                    $agendaNonaktif[$ev['tanggal_tampil']] = true;
                }
            }
            $hariMap   = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => null];

            foreach ($jadwalSaya as $j) {
                $cursor = $mulai;
                while ($cursor <= $kemarin) {
                    $namaHari = $hariMap[(int) date('N', strtotime($cursor))] ?? null;
                    if ($namaHari === $j['hari'] && ! isset($agendaNonaktif[$cursor]) && ! isset($presensiAda[$j['id'] . '|' . $cursor])) {
                        $jumlahHari++;
                    }
                    $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
                }
            }
        }

        return $jumlahJurnal + $jumlahHari;
    }
}

if (! function_exists('jendela_terlewat')) {
    /**
     * Hitung rentang [mulai, kemarin] yang aman dipakai untuk deteksi "hari
     * terlewat": maksimal 30 hari ke belakang, TAPI dijepit supaya tidak
     * pernah keluar dari tanggal_mulai/tanggal_selesai semester aktif (kalau
     * sudah diisi admin). Return [null, null] kalau rentangnya jadi kosong/
     * tidak valid (mis. semester belum mulai sama sekali sampai hari ini).
     * Dipakai bersama oleh badge_jurnal_terlewat() dan Kalender Jadwal supaya
     * dua-duanya konsisten memakai jendela yang sama.
     */
    function jendela_terlewat(array $semesterAktif): array
    {
        $tigapuluhHariLalu = date('Y-m-d', strtotime('-30 days'));
        $kemarinKalender   = date('Y-m-d', strtotime('-1 day'));

        $mulai   = $semesterAktif['tanggal_mulai'] ? max($semesterAktif['tanggal_mulai'], $tigapuluhHariLalu) : $tigapuluhHariLalu;
        $kemarin = $semesterAktif['tanggal_selesai'] ? min($semesterAktif['tanggal_selesai'], $kemarinKalender) : $kemarinKalender;

        if ($mulai > $kemarin) {
            return [null, null];
        }

        return [$mulai, $kemarin];
    }
}

if (! function_exists('badge_tukar_jadwal')) {
    /**
     * Jumlah pengajuan "Tukar Jadwal" (pertukaran slot) yang menunggu respon
     * personal guru yang login (tahap 1, sebelum masuk antrean admin).
     */
    function badge_tukar_jadwal(): int
    {
        if (! has_role('guru')) {
            return 0;
        }

        $guru = (new \App\Models\GuruModel())->findByUserId((int) session()->get('user_id'));
        if (! $guru) {
            return 0;
        }

        return (new \App\Models\JadwalSwapModel())->hitungMenungguGuru((int) $guru['id']);
    }
}

if (! function_exists('badge_admin_tukar_jadwal')) {
    /**
     * Jumlah pengajuan "Tukar Jadwal" yang sudah di-ACC guru tujuan (tahap 1
     * selesai) dan sedang MENUNGGU PERSETUJUAN AKHIR Admin/Waka Kurikulum
     * (tahap 2) — badge ini untuk menu admin di /master/pertukaran-jadwal,
     * beda dari badge_tukar_jadwal() di atas yang untuk sisi guru.
     */
    function badge_admin_tukar_jadwal(): int
    {
        if (! has_any_role(['administrator', 'operator'])) {
            return 0;
        }

        return (new \App\Models\JadwalSwapModel())->where('status', 'pending')->where('guru_setuju', 1)->countAllResults();
    }
}

if (! function_exists('is_hari_nonaktif')) {
    /**
     * SATU-SATUNYA sumber kebenaran untuk "apakah tanggal ini hari libur
     * sekolah" — dicek dari Kalender Akademik (event kategori apa pun
     * dengan dampak_presensi='nonaktif', termasuk yang berulang). Modul
     * mana pun yang perlu tahu "apakah presensi seharusnya nonaktif hari
     * ini" (Dashboard, Jadwal Hari Ini, dst) sebaiknya panggil fungsi ini,
     * bukan cek agenda_akademik secara langsung, supaya hasilnya selalu
     * konsisten di seluruh aplikasi.
     *
     * Sebelumnya juga cek tabel hari_libur terpisah — dihapus karena
     * fungsinya sudah sepenuhnya digantikan kategori "Libur" di Kalender
     * Akademik (dua sumber kebenaran utk hal yang sama itu sendiri adalah
     * potensi bug, bukan cuma redundan).
     */
    function is_hari_nonaktif(?string $tanggal = null): bool
    {
        $tanggal ??= date('Y-m-d');

        return (new \App\Models\AgendaAkademikModel())->adalahHariNonaktif($tanggal);
    }
}
