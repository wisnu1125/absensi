<?php

namespace App\Libraries;

use App\Models\JurnalMengajarModel;
use App\Models\KelasModel;
use App\Models\PenilaianHarianModel;
use App\Models\PresensiDetailModel;
use App\Models\PresensiModel;
use App\Models\SiswaModel;
use App\Models\TukarJadwalModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Seluruh agregasi utk Dashboard Admin "lengkap" (stat card, progress,
 * aktivitas terbaru, monitoring guru/kelas, TP hari ini, analisis
 * penilaian, grafik mingguan, siswa perlu perhatian, deadline). SEMUA
 * angka di sini dihitung dari tabel yang SUDAH ADA (jadwal, presensi,
 * jurnal_mengajar, penilaian_harian, tukar_jadwal) — tidak ada tabel baru,
 * murni query agregasi, sesuai arahan: guru sudah ada jadwal, tinggal
 * dicek pengisian presensi/jurnal/penilaiannya hari itu.
 *
 * Jadwal efektif hari ini (via ScheduleResolverService, yang SUDAH
 * memperhitungkan tukar jadwal yang disetujui) di-fetch SEKALI di build(),
 * lalu dipakai ulang ke semua widget turunan -- supaya tidak query jadwal
 * berkali-kali utk hal yang sama.
 */
class DashboardAdminService
{
    private ScheduleResolverService $resolver;
    private BaseConnection $db;

    public function __construct()
    {
        $this->resolver = new ScheduleResolverService();
        $this->db       = \Config\Database::connect();
    }

    public function build(int $semesterId): array
    {
        $today = date('Y-m-d');
        $jam   = date('H:i:s');

        $jadwalHariIni = $this->resolver->jadwalEfektifSekolah($semesterId, $today);
        $jadwalIds     = array_column($jadwalHariIni, 'id');

        $presensiMap = $this->bulkPresensiHariIni($jadwalIds, $today);
        $jurnalMap   = $this->bulkJurnalHariIni($jadwalIds, $today);
        $jurnalIds   = array_column($jurnalMap, 'id');
        $penilaian   = $this->bulkPenilaianUntukJurnal($jurnalIds);

        return [
            'jadwalHariIni'       => $jadwalHariIni,
            'statCards'           => $this->hitungStatCards($jadwalHariIni, $presensiMap, $jurnalMap, $penilaian, $jam),
            'progress'            => $this->hitungProgress($jadwalHariIni, $presensiMap, $jurnalMap, $penilaian),
            'aktivitasTerbaru'    => $this->aktivitasTerbaru($today),
            'monitoringGuru'      => $this->monitoringGuru($jadwalHariIni, $presensiMap, $jurnalMap, $penilaian),
            'monitoringKelas'     => $this->monitoringKelas($jadwalHariIni),
            'tpHariIni'           => $this->tpHariIni($jadwalHariIni, $jurnalMap),
            'analisisPenilaian'   => $this->analisisPenilaian($penilaian),
            'grafikMingguan'      => $this->grafikMingguan(),
            'siswaPerluPerhatian' => $this->siswaPerluPerhatian(),
            'deadline'            => $this->deadlinePengingat($jadwalHariIni, $jurnalMap, $jam),
        ];
    }

    /** @return array<int,array> keyed by jadwal_id */
    private function bulkPresensiHariIni(array $jadwalIds, string $tanggal): array
    {
        if (empty($jadwalIds)) {
            return [];
        }
        $rows = (new PresensiModel())->whereIn('jadwal_id', $jadwalIds)->where('tanggal', $tanggal)->findAll();

        return array_column($rows, null, 'jadwal_id');
    }

    /** @return array<int,array> keyed by jadwal_id */
    private function bulkJurnalHariIni(array $jadwalIds, string $tanggal): array
    {
        if (empty($jadwalIds)) {
            return [];
        }
        $rows = (new JurnalMengajarModel())->whereIn('jadwal_id', $jadwalIds)->where('tanggal', $tanggal)->findAll();

        return array_column($rows, null, 'jadwal_id');
    }

    /** @return array flat list semua baris penilaian_harian utk jurnal2 yang diberikan */
    private function bulkPenilaianUntukJurnal(array $jurnalIds): array
    {
        if (empty($jurnalIds)) {
            return [];
        }

        return (new PenilaianHarianModel())->whereIn('jurnal_id', $jurnalIds)->findAll();
    }

    private function hitungStatCards(array $jadwal, array $presensiMap, array $jurnalMap, array $penilaian, string $jamSekarang): array
    {
        $guruIds       = array_unique(array_column($jadwal, 'guru_id'));
        $guruIdsHadir  = array_unique(array_map(
            static fn ($j) => $j['guru_id'],
            array_filter($jadwal, static fn ($j) => isset($presensiMap[$j['id']]))
        ));

        $sedangBerlangsung = count(array_filter(
            $jadwal,
            static fn ($j) => $j['jam_mulai'] <= $jamSekarang && $jamSekarang <= $j['jam_selesai']
        ));

        $jurnalTerisi = count(array_filter($jadwal, static fn ($j) => isset($jurnalMap[$j['id']])));

        $tpDilaksanakan = count(array_filter(
            $jurnalMap,
            static fn ($j) => trim((string) ($j['tujuan_pembelajaran'] ?? '')) !== ''
        ));

        return [
            'guru_hadir'          => count($guruIdsHadir),
            'guru_total'          => count($guruIds),
            'jadwal_hari_ini'     => count($jadwal),
            'sedang_berlangsung'  => $sedangBerlangsung,
            'jurnal_terisi'       => $jurnalTerisi,
            'jurnal_total'        => count($jadwal),
            'penilaian_hari_ini'  => count($penilaian),
            'tp_dilaksanakan'     => $tpDilaksanakan,
            'tukar_menunggu'      => (new TukarJadwalModel())->hitungMenunggu(),
        ];
    }

    private function hitungProgress(array $jadwal, array $presensiMap, array $jurnalMap, array $penilaian): array
    {
        $guruIds      = array_unique(array_column($jadwal, 'guru_id'));
        $guruIdsHadir = array_unique(array_map(
            static fn ($j) => $j['guru_id'],
            array_filter($jadwal, static fn ($j) => isset($presensiMap[$j['id']]))
        ));
        $jurnalTerisi = count(array_filter($jadwal, static fn ($j) => isset($jurnalMap[$j['id']])));

        $kelasIds = array_unique(array_column($jadwal, 'kelas_id'));
        $jurnalIdsDenganPenilaian = array_unique(array_column($penilaian, 'jurnal_id'));
        $kelasIdsDenganPenilaian  = array_unique(array_map(
            static function ($j) use ($jurnalMap, $jurnalIdsDenganPenilaian) {
                $jr = $jurnalMap[$j['id']] ?? null;

                return ($jr && in_array($jr['id'], $jurnalIdsDenganPenilaian, true)) ? $j['kelas_id'] : null;
            },
            $jadwal
        ));
        $kelasIdsDenganPenilaian = array_filter($kelasIdsDenganPenilaian);

        $persen = static fn ($a, $b) => $b > 0 ? round($a / $b * 100) : 0;

        return [
            'presensi' => ['terisi' => count($guruIdsHadir), 'total' => count($guruIds), 'persen' => $persen(count($guruIdsHadir), count($guruIds))],
            'jurnal'   => ['terisi' => $jurnalTerisi, 'total' => count($jadwal), 'persen' => $persen($jurnalTerisi, count($jadwal))],
            'penilaian'=> ['terisi' => count($kelasIdsDenganPenilaian), 'total' => count($kelasIds), 'persen' => $persen(count($kelasIdsDenganPenilaian), count($kelasIds))],
        ];
    }

    /**
     * Gabungan 3 sumber aktivitas hari ini (presensi mulai, jurnal diisi,
     * tukar jadwal diajukan), diurutkan waktu terbaru dulu. Sengaja TIDAK
     * memakai penilaian_harian sebagai aktivitas TERPISAH dari jurnal --
     * di aplikasi ini keduanya diisi dalam SATU form yang sama (submit
     * jurnal), jadi "mengisi jurnal" sudah mencakup penilaiannya.
     */
    private function aktivitasTerbaru(string $today, int $limit = 8): array
    {
        $jurnal = $this->db->table('jurnal_mengajar jm')
            ->select('jm.created_at as waktu, "jurnal" as jenis, g.nama as nama_guru, mp.nama as nama_mapel, k.nama_kelas', false)
            ->join('jadwal j', 'j.id = jm.jadwal_id')
            ->join('guru g', 'g.id = j.guru_id')
            ->join('mata_pelajaran mp', 'mp.id = j.mapel_id')
            ->join('kelas k', 'k.id = j.kelas_id')
            ->where('jm.tanggal', $today)
            ->get()->getResultArray();

        $tukar = $this->db->table('tukar_jadwal tj')
            ->select('tj.created_at as waktu, "tukar" as jenis, g.nama as nama_guru, mp.nama as nama_mapel, k.nama_kelas, tj.tanggal as tanggal_sesi', false)
            ->join('jadwal j', 'j.id = tj.jadwal_id')
            ->join('guru g', 'g.id = tj.guru_asal_id')
            ->join('mata_pelajaran mp', 'mp.id = j.mapel_id')
            ->join('kelas k', 'k.id = j.kelas_id')
            ->where('tj.created_at >=', $today . ' 00:00:00')
            ->where('tj.created_at <', date('Y-m-d 00:00:00', strtotime($today . ' +1 day')))
            ->get()->getResultArray();

        $presensi = $this->db->table('presensi p')
            ->select('p.created_at as waktu, "presensi" as jenis, g.nama as nama_guru, mp.nama as nama_mapel, k.nama_kelas', false)
            ->join('jadwal j', 'j.id = p.jadwal_id')
            ->join('guru g', 'g.id = j.guru_id')
            ->join('mata_pelajaran mp', 'mp.id = j.mapel_id')
            ->join('kelas k', 'k.id = j.kelas_id')
            ->where('p.tanggal', $today)
            ->get()->getResultArray();

        $semua = array_merge($jurnal, $tukar, $presensi);
        usort($semua, static fn ($a, $b) => strcmp((string) $b['waktu'], (string) $a['waktu']));

        return array_slice($semua, 0, $limit);
    }

    /**
     * Per-guru yang ADA jadwal hari ini: dari sekian sesi dia hari ini,
     * berapa yang presensi/jurnal-nya sudah terisi. 'sudah' = semua sesi
     * terisi, 'sebagian' = ada yg terisi ada yg belum, 'belum' = belum
     * satupun.
     */
    private function monitoringGuru(array $jadwal, array $presensiMap, array $jurnalMap, array $penilaian): array
    {
        $jurnalIdsDenganPenilaian = array_unique(array_column($penilaian, 'jurnal_id'));

        $perGuru = [];
        foreach ($jadwal as $j) {
            $gid = $j['guru_id'];
            if (! isset($perGuru[$gid])) {
                $perGuru[$gid] = ['nama_guru' => $j['nama_guru'], 'sesi' => 0, 'presensi' => 0, 'jurnal' => 0, 'penilaian' => 0];
            }
            $perGuru[$gid]['sesi']++;
            if (isset($presensiMap[$j['id']])) {
                $perGuru[$gid]['presensi']++;
            }
            $jr = $jurnalMap[$j['id']] ?? null;
            if ($jr) {
                $perGuru[$gid]['jurnal']++;
                if (in_array($jr['id'], $jurnalIdsDenganPenilaian, true)) {
                    $perGuru[$gid]['penilaian']++;
                }
            }
        }

        $status = static function (int $terisi, int $total): string {
            if ($total === 0) {
                return 'tidak_ada';
            }

            return $terisi === 0 ? 'belum' : ($terisi === $total ? 'sudah' : 'sebagian');
        };

        $hasil = [];
        foreach ($perGuru as $gid => $r) {
            $hasil[] = [
                'guru_id'          => $gid,
                'nama_guru'        => $r['nama_guru'],
                'sesi'             => $r['sesi'],
                'status_presensi'  => $status($r['presensi'], $r['sesi']),
                'status_jurnal'    => $status($r['jurnal'], $r['sesi']),
                'status_penilaian' => $status($r['penilaian'], $r['sesi']),
            ];
        }
        usort($hasil, static fn ($a, $b) => strcmp((string) $a['nama_guru'], (string) $b['nama_guru']));

        return $hasil;
    }

    /**
     * Per-kelas yang ADA jadwal hari ini: dari siswa AKTIF di kelas itu,
     * berapa % yang punya MINIMAL SATU entri penilaian_harian dalam 30
     * hari terakhir (bukan cuma "hari ini" -- kalau discope ke hari ini
     * saja, hampir semua kelas akan 0% karena penilaian tidak diisi
     * setiap hari utk setiap siswa).
     */
    private function monitoringKelas(array $jadwal): array
    {
        $kelasIds = array_values(array_unique(array_column($jadwal, 'kelas_id')));
        if (empty($kelasIds)) {
            return [];
        }
        $namaKelas = array_column($jadwal, 'nama_kelas', 'kelas_id');

        $sejakTanggal = date('Y-m-d', strtotime('-30 days'));

        $hasil = [];
        foreach ($kelasIds as $kid) {
            $totalSiswa = (new SiswaModel())->where('kelas_id', $kid)->where('status', 'aktif')->countAllResults();

            if ($totalSiswa === 0) {
                $hasil[] = ['kelas_id' => $kid, 'nama_kelas' => $namaKelas[$kid], 'persen' => 0, 'total_siswa' => 0];
                continue;
            }

            $siswaDinilai = $this->db->table('penilaian_harian ph')
                ->select('ph.siswa_id')
                ->join('jurnal_mengajar jm', 'jm.id = ph.jurnal_id')
                ->join('jadwal j', 'j.id = jm.jadwal_id')
                ->where('j.kelas_id', $kid)
                ->where('jm.tanggal >=', $sejakTanggal)
                ->groupBy('ph.siswa_id')
                ->countAllResults();

            $hasil[] = [
                'kelas_id'    => $kid,
                'nama_kelas'  => $namaKelas[$kid],
                'persen'      => round($siswaDinilai / $totalSiswa * 100),
                'total_siswa' => $totalSiswa,
            ];
        }
        usort($hasil, static fn ($a, $b) => strcmp((string) $a['nama_kelas'], (string) $b['nama_kelas']));

        return $hasil;
    }

    /**
     * TP yang dipakai hari ini, dikelompokkan per mapel -- diambil dari
     * teks tujuan_pembelajaran yang TERSIMPAN di jurnal (bukan join balik
     * ke tabel master TP), supaya tetap akurat walau guru mengetik manual
     * lewat "Lainnya" alih-alih pilih dari dropdown.
     */
    private function tpHariIni(array $jadwal, array $jurnalMap): array
    {
        $perMapelTp = [];
        foreach ($jadwal as $j) {
            $jr = $jurnalMap[$j['id']] ?? null;
            $tp = trim((string) ($jr['tujuan_pembelajaran'] ?? ''));
            if ($tp === '') {
                continue;
            }
            $key = $j['mapel_id'] . '|' . $tp;
            if (! isset($perMapelTp[$key])) {
                $perMapelTp[$key] = ['nama_mapel' => $j['nama_mapel'], 'tp' => $tp, 'kelas' => []];
            }
            $perMapelTp[$key]['kelas'][$j['kelas_id']] = $j['nama_kelas'];
        }

        $hasil = [];
        foreach ($perMapelTp as $r) {
            $hasil[] = ['nama_mapel' => $r['nama_mapel'], 'tp' => $r['tp'], 'jumlah_kelas' => count($r['kelas']), 'daftar_kelas' => implode(', ', $r['kelas'])];
        }
        usort($hasil, static fn ($a, $b) => $b['jumlah_kelas'] <=> $a['jumlah_kelas']);

        return $hasil;
    }

    private function analisisPenilaian(array $penilaian): array
    {
        $perJenis = [];
        foreach ($penilaian as $p) {
            $jenis = $p['jenis_penilaian'];
            $perJenis[$jenis] = ($perJenis[$jenis] ?? 0) + 1;
        }
        arsort($perJenis);
        $total = array_sum($perJenis);

        $hasil = [];
        foreach ($perJenis as $jenis => $jumlah) {
            $hasil[] = ['jenis' => $jenis, 'jumlah' => $jumlah, 'persen' => $total > 0 ? round($jumlah / $total * 100) : 0];
        }

        return ['total' => $total, 'rincian' => $hasil];
    }

    /** 7 hari terakhir (termasuk hari ini): jumlah jurnal & penilaian per hari. */
    private function grafikMingguan(): array
    {
        $mulai = date('Y-m-d', strtotime('-6 days'));
        $akhir = date('Y-m-d');

        $jurnalPerHari = $this->db->table('jurnal_mengajar')
            ->select('tanggal, COUNT(*) as jumlah', false)
            ->where('tanggal >=', $mulai)->where('tanggal <=', $akhir)
            ->groupBy('tanggal')->get()->getResultArray();
        $jurnalMap = array_column($jurnalPerHari, 'jumlah', 'tanggal');

        $penilaianPerHari = $this->db->table('penilaian_harian ph')
            ->select('jm.tanggal, COUNT(*) as jumlah', false)
            ->join('jurnal_mengajar jm', 'jm.id = ph.jurnal_id')
            ->where('jm.tanggal >=', $mulai)->where('jm.tanggal <=', $akhir)
            ->groupBy('jm.tanggal')->get()->getResultArray();
        $penilaianMap = array_column($penilaianPerHari, 'jumlah', 'tanggal');

        $namaHari = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        $hasil    = [];
        $cursor   = $mulai;
        while ($cursor <= $akhir) {
            $hasil[] = [
                'tanggal'   => $cursor,
                'label'     => $namaHari[(int) date('N', strtotime($cursor)) - 1] . ' ' . date('d/n', strtotime($cursor)),
                'jurnal'    => (int) ($jurnalMap[$cursor] ?? 0),
                'penilaian' => (int) ($penilaianMap[$cursor] ?? 0),
            ];
            $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }

        return $hasil;
    }

    /**
     * Kriteria disederhanakan jadi 2 yang bisa dihitung akurat dari data
     * yang ada (bukan 4 seperti mockup -- "nilai menurun" & "belum
     * mengumpulkan tugas" tidak punya data pendukung yang jelas di skema
     * saat ini, jadi TIDAK dipaksakan supaya tidak mengarang):
     * 1) belum ada penilaian sama sekali 14 hari terakhir
     * 2) kehadiran bulan ini di bawah 75%
     */
    private function siswaPerluPerhatian(int $limit = 6): array
    {
        $sejak14Hari = date('Y-m-d', strtotime('-14 days'));
        $awalBulan   = date('Y-m-01');

        $siswaAktif = (new SiswaModel())->select('siswa.id, siswa.nama, siswa.nis, kelas.nama_kelas')
            ->join('kelas', 'kelas.id = siswa.kelas_id', 'left')
            ->where('siswa.status', 'aktif')->findAll();

        $siswaDinilai14Hari = array_column(
            $this->db->table('penilaian_harian ph')
                ->select('ph.siswa_id')
                ->distinct()
                ->join('jurnal_mengajar jm', 'jm.id = ph.jurnal_id')
                ->where('jm.tanggal >=', $sejak14Hari)
                ->get()->getResultArray(),
            'siswa_id'
        );

        $rekapHadirBulanIni = $this->db->table('presensi_detail pd')
            ->select('pd.siswa_id, pd.status, COUNT(*) as jumlah', false)
            ->join('presensi p', 'p.id = pd.presensi_id')
            ->where('p.tanggal >=', $awalBulan)
            ->groupBy('pd.siswa_id, pd.status')->get()->getResultArray();
        $perSiswaRekap = [];
        foreach ($rekapHadirBulanIni as $r) {
            $perSiswaRekap[$r['siswa_id']][$r['status']] = (int) $r['jumlah'];
        }

        $hasil = [];
        foreach ($siswaAktif as $s) {
            if (! in_array($s['id'], $siswaDinilai14Hari, true)) {
                $hasil[] = ['nama' => $s['nama'], 'nis' => $s['nis'], 'nama_kelas' => $s['nama_kelas'] ?? '-', 'alasan' => 'Belum ada penilaian 14 hari terakhir', 'prioritas' => 1];
                continue;
            }

            $rekap  = $perSiswaRekap[$s['id']] ?? [];
            $total  = array_sum($rekap);
            $hadir  = $rekap['hadir'] ?? 0;
            $persen = $total > 0 ? round($hadir / $total * 100) : null;

            if ($persen !== null && $total >= 3 && $persen < 75) {
                $hasil[] = ['nama' => $s['nama'], 'nis' => $s['nis'], 'nama_kelas' => $s['nama_kelas'] ?? '-', 'alasan' => "Kehadiran {$persen}% bulan ini", 'prioritas' => 2];
            }
        }
        usort($hasil, static fn ($a, $b) => $a['prioritas'] <=> $b['prioritas']);

        return array_slice($hasil, 0, $limit);
    }

    private function deadlinePengingat(array $jadwal, array $jurnalMap, string $jamSekarang): array
    {
        $jurnalBelum = count(array_filter(
            $jadwal,
            static fn ($j) => ! isset($jurnalMap[$j['id']]) && $j['jam_selesai'] <= $jamSekarang
        ));

        $kelasIds        = array_unique(array_column($jadwal, 'kelas_id'));
        $kelasAdaJurnal  = array_unique(array_map(
            static fn ($j) => isset($jurnalMap[$j['id']]) ? $j['kelas_id'] : null,
            $jadwal
        ));
        $kelasBelumJurnal = count(array_diff($kelasIds, array_filter($kelasAdaJurnal)));

        return [
            'jurnal_belum_diisi'    => $jurnalBelum,
            'tukar_menunggu'        => (new TukarJadwalModel())->hitungMenunggu(),
            'kelas_belum_penilaian' => $kelasBelumJurnal,
        ];
    }
}
