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
}
