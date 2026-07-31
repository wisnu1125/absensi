<?php

namespace App\Models;

use CodeIgniter\Model;

class TukarJadwalModel extends Model
{
    protected $table         = 'tukar_jadwal';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'jadwal_id', 'tanggal', 'guru_asal_id', 'guru_pengganti_id', 'alasan', 'status', 'catatan_respon',
    ];

    /**
     * True kalau sudah ada pengajuan yang masih aktif (menunggu/disetujui) untuk
     * jadwal+tanggal ini — dipakai supaya tidak dobel pengajuan untuk sesi yang sama.
     */
    public function adaPengajuanAktif(int $jadwalId, string $tanggal): bool
    {
        return (bool) $this->where('jadwal_id', $jadwalId)
            ->where('tanggal', $tanggal)
            ->whereIn('status', ['menunggu', 'disetujui'])
            ->first();
    }

    /**
     * Pengajuan yang SUDAH DISETUJUI untuk satu jadwal+tanggal (kalau ada) —
     * inilah yang dipakai untuk "mengalihkan" kepemilikan sesi presensi/jurnal
     * hari itu ke guru pengganti, tanpa mengubah tabel jadwal sama sekali.
     */
    public function getDisetujui(int $jadwalId, string $tanggal): ?array
    {
        return $this->where('jadwal_id', $jadwalId)
            ->where('tanggal', $tanggal)
            ->where('status', 'disetujui')
            ->first();
    }

    /**
     * Semua pengajuan DISETUJUI di mana $guruId berperan sebagai pengganti,
     * dalam rentang tanggal tertentu — dipakai dashboard guru pengganti.
     */
    public function getDisetujuiUntukPengganti(int $guruId, string $tanggalDari, string $tanggalSampai): array
    {
        return $this->select('tukar_jadwal.*, jadwal.hari, jadwal.jam_mulai, jadwal.jam_selesai, jadwal.jam_ke_mulai, jadwal.jam_ke_selesai, jadwal.kelas_id, jadwal.mapel_id, kelas.nama_kelas, mata_pelajaran.nama as nama_mapel, guru.nama as nama_guru_asal')
            ->join('jadwal', 'jadwal.id = tukar_jadwal.jadwal_id')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('guru', 'guru.id = tukar_jadwal.guru_asal_id')
            ->where('tukar_jadwal.guru_pengganti_id', $guruId)
            ->where('tukar_jadwal.status', 'disetujui')
            ->where('tukar_jadwal.tanggal >=', $tanggalDari)
            ->where('tukar_jadwal.tanggal <=', $tanggalSampai)
            ->findAll();
    }

    /**
     * Pengajuan MASUK yang masih menunggu respon dari $guruId (sebagai pengganti).
     */
    public function getMenungguUntukGuru(int $guruId): array
    {
        return $this->select('tukar_jadwal.*, jadwal.hari, jadwal.jam_mulai, jadwal.jam_selesai, kelas.nama_kelas, mata_pelajaran.nama as nama_mapel, guru.nama as nama_guru_asal')
            ->join('jadwal', 'jadwal.id = tukar_jadwal.jadwal_id')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('guru', 'guru.id = tukar_jadwal.guru_asal_id')
            ->where('tukar_jadwal.guru_pengganti_id', $guruId)
            ->where('tukar_jadwal.status', 'menunggu')
            ->orderBy('tukar_jadwal.tanggal', 'ASC')
            ->findAll();
    }

    /**
     * Riwayat lengkap (dikirim maupun diterima) untuk satu guru.
     */
    public function getRiwayatUntukGuru(int $guruId): array
    {
        return $this->select('tukar_jadwal.*, jadwal.hari, jadwal.jam_mulai, jadwal.jam_selesai, kelas.nama_kelas, mata_pelajaran.nama as nama_mapel, ga.nama as nama_guru_asal, gp.nama as nama_guru_pengganti')
            ->join('jadwal', 'jadwal.id = tukar_jadwal.jadwal_id')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('guru as ga', 'ga.id = tukar_jadwal.guru_asal_id')
            ->join('guru as gp', 'gp.id = tukar_jadwal.guru_pengganti_id')
            ->groupStart()
                ->where('tukar_jadwal.guru_asal_id', $guruId)
                ->orWhere('tukar_jadwal.guru_pengganti_id', $guruId)
            ->groupEnd()
            ->orderBy('tukar_jadwal.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Semua pengajuan se-sekolah, dengan filter — dipakai laporan admin & kepala sekolah.
     */
    public function getSemua(array $filter): array
    {
        $builder = $this->select('tukar_jadwal.*, jadwal.hari, kelas.nama_kelas, mata_pelajaran.nama as nama_mapel, ga.nama as nama_guru_asal, gp.nama as nama_guru_pengganti')
            ->join('jadwal', 'jadwal.id = tukar_jadwal.jadwal_id')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('guru as ga', 'ga.id = tukar_jadwal.guru_asal_id')
            ->join('guru as gp', 'gp.id = tukar_jadwal.guru_pengganti_id')
            ->where('tukar_jadwal.tanggal >=', $filter['tanggal_dari'])
            ->where('tukar_jadwal.tanggal <=', $filter['tanggal_sampai']);

        if (! empty($filter['status'])) {
            $builder->where('tukar_jadwal.status', $filter['status']);
        }

        return $builder->orderBy('tukar_jadwal.tanggal', 'DESC')->findAll();
    }

    /**
     * Jumlah pengajuan yang MASIH MENUNGGU RESPON $guruId (buat badge sidebar) —
     * versi ringan dari getMenungguUntukGuru(), cuma COUNT tanpa JOIN detail.
     */
    public function hitungMenungguUntukGuru(int $guruId): int
    {
        return $this->where('guru_pengganti_id', $guruId)->where('status', 'menunggu')->countAllResults();
    }

    public function hitungMenunggu(): int
    {
        return $this->where('status', 'menunggu')->countAllResults();
    }
}
