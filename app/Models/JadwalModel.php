<?php

namespace App\Models;

use CodeIgniter\Model;

class JadwalModel extends Model
{
    protected $table         = 'jadwal';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'guru_id', 'mapel_id', 'kelas_id', 'tahun_ajaran_id', 'semester_id',
        'hari', 'jam_ke_mulai', 'jam_ke_selesai', 'jam_mulai', 'jam_selesai', 'is_active',
    ];

    /**
     * Cek bentrok jadwal GURU: guru yang sama, hari yang sama, semester yang sama,
     * dan rentang jamnya beririsan dengan jadwal lain yang sudah ada.
     * Dua rentang waktu [a,b] dan [c,d] beririsan kalau a < d DAN c < b.
     */
    public function cekBentrokGuru(int $guruId, string $hari, string $jamMulai, string $jamSelesai, int $semesterId, ?int $excludeId = null): ?array
    {
        $builder = $this->select('jadwal.*, kelas.nama_kelas, mata_pelajaran.nama as nama_mapel')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->where('jadwal.guru_id', $guruId)
            ->where('jadwal.hari', $hari)
            ->where('jadwal.semester_id', $semesterId)
            ->where('jadwal.jam_mulai <', $jamSelesai)
            ->where('jadwal.jam_selesai >', $jamMulai);

        if ($excludeId !== null) {
            $builder->where('jadwal.id !=', $excludeId);
        }

        return $builder->first();
    }

    /**
     * Cek bentrok jadwal KELAS: kelas yang sama tidak boleh punya 2 mapel di jam yang tumpang tindih.
     */
    public function cekBentrokKelas(int $kelasId, string $hari, string $jamMulai, string $jamSelesai, int $semesterId, ?int $excludeId = null): ?array
    {
        $builder = $this->select('jadwal.*, guru.nama as nama_guru, mata_pelajaran.nama as nama_mapel')
            ->join('guru', 'guru.id = jadwal.guru_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->where('jadwal.kelas_id', $kelasId)
            ->where('jadwal.hari', $hari)
            ->where('jadwal.semester_id', $semesterId)
            ->where('jadwal.jam_mulai <', $jamSelesai)
            ->where('jadwal.jam_selesai >', $jamMulai);

        if ($excludeId !== null) {
            $builder->where('jadwal.id !=', $excludeId);
        }

        return $builder->first();
    }

    /**
     * Jadwal milik satu guru pada satu hari tertentu (dipakai dashboard: "Jadwal hari ini").
     */
    public function getJadwalHariIniByGuru(int $guruId, string $hari, int $semesterId): array
    {
        return $this->select('jadwal.*, mata_pelajaran.nama as nama_mapel, kelas.nama_kelas')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->where('jadwal.guru_id', $guruId)
            ->where('jadwal.hari', $hari)
            ->where('jadwal.semester_id', $semesterId)
            ->orderBy('jadwal.jam_mulai', 'ASC')
            ->findAll();
    }

    /**
     * Satu jadwal spesifik, tapi WAJIB milik guru yang diberikan — dipakai sebagai
     * pengaman supaya guru A tidak bisa mengisi presensi/jurnal jadwal guru B
     * hanya dengan menebak angka jadwal_id di URL.
     */
    public function getJadwalMilikGuru(int $jadwalId, int $guruId): ?array
    {
        return $this->select('jadwal.*, mata_pelajaran.nama as nama_mapel, kelas.nama_kelas')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->where('jadwal.id', $jadwalId)
            ->where('jadwal.guru_id', $guruId)
            ->first();
    }

    /**
     * Semua jadwal 1 semester, lengkap dengan nama guru/mapel/kelas, terurut Senin -> Sabtu lalu jam.
     */
    public function getWithDetail(int $semesterId): array
    {
        $rows = $this->select('jadwal.*, guru.nama as nama_guru, mata_pelajaran.nama as nama_mapel, kelas.nama_kelas')
            ->join('guru', 'guru.id = jadwal.guru_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->where('jadwal.semester_id', $semesterId)
            ->orderBy('jadwal.jam_mulai', 'ASC')
            ->findAll();

        $urutanHari = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6];
        usort($rows, static fn ($a, $b) => ($urutanHari[$a['hari']] ?? 9) <=> ($urutanHari[$b['hari']] ?? 9));

        return $rows;
    }

    /**
     * Import banyak jadwal sekaligus dari Excel. Memakai validasi anti-bentrok
     * yang SAMA PERSIS dengan form tambah manual (cekBentrokGuru/cekBentrokKelas),
     * dan karena setiap baris valid langsung disimpan sebelum baris berikutnya
     * diperiksa, bentrok ANTAR baris di file yang sama juga otomatis tertangkap.
     */
    public function importRows(array $rows, int $tahunAjaranId, int $semesterId): array
    {
        $guruModel  = new GuruModel();
        $mapelModel = new MataPelajaranModel();
        $kelasModel = new KelasModel();
        $jamModel   = new JamPelajaranModel();

        $hariValid = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $sukses    = 0;
        $gagal     = 0;
        $errors    = [];

        foreach ($rows as $i => $row) {
            $baris        = $i + 2;
            $namaGuru     = trim((string) ($row['guru'] ?? ''));
            $namaMapel    = trim((string) ($row['mapel'] ?? ''));
            $namaKelas    = trim((string) ($row['kelas'] ?? ''));
            $hari         = trim((string) ($row['hari'] ?? ''));
            $jamKeMulai   = (int) ($row['jam_ke_mulai'] ?? 0);
            $jamKeSelesai = (int) ($row['jam_ke_selesai'] ?? 0);

            if ($namaGuru === '' || $namaMapel === '' || $namaKelas === '' || $hari === '' || ! $jamKeMulai || ! $jamKeSelesai) {
                $gagal++;
                $errors[] = "Baris {$baris}: ada kolom wajib yang kosong, dilewati.";
                continue;
            }

            if (! in_array($hari, $hariValid, true)) {
                $gagal++;
                $errors[] = "Baris {$baris}: hari '{$hari}' tidak valid (harus Senin-Sabtu).";
                continue;
            }

            $guru = $guruModel->where('nama', $namaGuru)->first();
            if (! $guru) {
                $gagal++;
                $errors[] = "Baris {$baris}: guru '{$namaGuru}' tidak ditemukan di Data Master.";
                continue;
            }

            $mapel = $mapelModel->where('nama', $namaMapel)->first();
            if (! $mapel) {
                $gagal++;
                $errors[] = "Baris {$baris}: mata pelajaran '{$namaMapel}' tidak ditemukan.";
                continue;
            }

            $kelas = $kelasModel->where('nama_kelas', $namaKelas)->where('tahun_ajaran_id', $tahunAjaranId)->first();
            if (! $kelas) {
                $gagal++;
                $errors[] = "Baris {$baris}: kelas '{$namaKelas}' tidak ditemukan di tahun ajaran aktif.";
                continue;
            }

            if ($jamKeSelesai < $jamKeMulai) {
                $gagal++;
                $errors[] = "Baris {$baris}: jam ke-selesai tidak boleh sebelum jam ke-mulai.";
                continue;
            }

            $jpMulai   = $jamModel->findByJamKe($jamKeMulai);
            $jpSelesai = $jamModel->findByJamKe($jamKeSelesai);
            if (! $jpMulai || ! $jpSelesai) {
                $gagal++;
                $errors[] = "Baris {$baris}: jam ke-{$jamKeMulai}/{$jamKeSelesai} tidak ditemukan di Data Master Jam Pelajaran.";
                continue;
            }

            $jamMulai   = $jpMulai['jam_mulai'];
            $jamSelesai = $jpSelesai['jam_selesai'];

            $bentrokGuru = $this->cekBentrokGuru((int) $guru['id'], $hari, $jamMulai, $jamSelesai, $semesterId);
            if ($bentrokGuru) {
                $gagal++;
                $errors[] = "Baris {$baris}: guru {$namaGuru} bentrok dengan jadwal {$bentrokGuru['nama_mapel']} jam " . substr($bentrokGuru['jam_mulai'], 0, 5) . '-' . substr($bentrokGuru['jam_selesai'], 0, 5) . " hari {$hari}.";
                continue;
            }

            $bentrokKelas = $this->cekBentrokKelas((int) $kelas['id'], $hari, $jamMulai, $jamSelesai, $semesterId);
            if ($bentrokKelas) {
                $gagal++;
                $errors[] = "Baris {$baris}: kelas {$namaKelas} bentrok dengan jadwal {$bentrokKelas['nama_mapel']} jam " . substr($bentrokKelas['jam_mulai'], 0, 5) . '-' . substr($bentrokKelas['jam_selesai'], 0, 5) . " hari {$hari}.";
                continue;
            }

            $this->insert([
                'guru_id'         => (int) $guru['id'],
                'mapel_id'        => (int) $mapel['id'],
                'kelas_id'        => (int) $kelas['id'],
                'tahun_ajaran_id' => $tahunAjaranId,
                'semester_id'     => $semesterId,
                'hari'            => $hari,
                'jam_ke_mulai'    => $jamKeMulai,
                'jam_ke_selesai'  => $jamKeSelesai,
                'jam_mulai'       => $jamMulai,
                'jam_selesai'     => $jamSelesai,
                'is_active'       => 1,
            ]);

            $sukses++;
        }

        return ['sukses' => $sukses, 'gagal' => $gagal, 'errors' => $errors];
    }
}
