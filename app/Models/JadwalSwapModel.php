<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Pertukaran jadwal = pertukaran SLOT PENUH (hari+jam) antara 2 guru untuk
 * satu rentang tanggal, BUKAN guru pengganti. Jadwal master tidak pernah
 * diubah — dipakai bersama ScheduleResolverService untuk menghitung
 * "jadwal efektif".
 *
 * Alur persetujuan 2 tahap:
 * 1) guru_penyetuju_id (pemilik jadwal_tujuan) meng-ACC -> guru_setuju = 1
 * 2) Admin/Waka Kurikulum memberi persetujuan akhir -> status = disetujui
 * Ditolak di tahap manapun langsung mengubah status jadi 'ditolak'.
 */
class JadwalSwapModel extends Model
{
    protected $table         = 'jadwal_swap';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'jadwal_asal_id', 'jadwal_tujuan_id', 'guru_pengaju_id', 'guru_penyetuju_id',
        'tanggal_mulai', 'tanggal_selesai', 'alasan', 'status', 'guru_setuju',
        'catatan_guru', 'approved_by', 'approved_at', 'catatan_admin',
    ];

    /**
     * Pertukaran yang SUDAH DISETUJUI PENUH (status=disetujui) yang melibatkan
     * jadwal_id ini (sebagai asal ATAU tujuan), dan tanggal yang diminta jatuh
     * di dalam rentang tanggal_mulai..tanggal_selesai. Inilah jantung resolver:
     * kalau ada baris ini, jadwal_id yang dicek harus "meminjam" hari/jam dari
     * pasangannya untuk tanggal tersebut.
     */
    public function getAktifUntukJadwal(int $jadwalId, string $tanggal): ?array
    {
        return $this->groupStart()
                ->where('jadwal_asal_id', $jadwalId)
                ->orWhere('jadwal_tujuan_id', $jadwalId)
            ->groupEnd()
            ->where('status', 'disetujui')
            ->where('tanggal_mulai <=', $tanggal)
            ->where('tanggal_selesai >=', $tanggal)
            ->first();
    }

    /**
     * True kalau salah satu dari kedua jadwal ini sudah punya pertukaran AKTIF
     * (pending/disetujui) yang rentang tanggalnya TUMPANG TINDIH dengan rentang
     * yang baru diajukan — supaya tidak ada 2 pertukaran nyasar/tabrakan di
     * jadwal yang sama pada waktu bersamaan.
     */
    public function adaTumpangTindih(int $jadwalAsalId, int $jadwalTujuanId, string $tanggalMulai, string $tanggalSelesai): bool
    {
        return (bool) $this->groupStart()
                ->groupStart()
                    ->where('jadwal_asal_id', $jadwalAsalId)
                    ->orWhere('jadwal_tujuan_id', $jadwalAsalId)
                    ->orWhere('jadwal_asal_id', $jadwalTujuanId)
                    ->orWhere('jadwal_tujuan_id', $jadwalTujuanId)
                ->groupEnd()
            ->groupEnd()
            ->whereIn('status', ['pending', 'disetujui'])
            ->where('tanggal_mulai <=', $tanggalSelesai)
            ->where('tanggal_selesai >=', $tanggalMulai)
            ->first() !== null;
    }

    /**
     * Pengajuan yang MASIH MENUNGGU RESPON PERSONAL dari $guruId (tahap 1).
     */
    public function getMenungguGuru(int $guruId): array
    {
        return $this->select($this->kolomLengkap())
            ->join('jadwal as ja', 'ja.id = jadwal_swap.jadwal_asal_id')
            ->join('jadwal as jt', 'jt.id = jadwal_swap.jadwal_tujuan_id')
            ->join('kelas as ka', 'ka.id = ja.kelas_id')
            ->join('kelas as kt', 'kt.id = jt.kelas_id')
            ->join('mata_pelajaran as ma', 'ma.id = ja.mapel_id')
            ->join('mata_pelajaran as mt', 'mt.id = jt.mapel_id')
            ->join('guru as ga', 'ga.id = jadwal_swap.guru_pengaju_id')
            ->where('jadwal_swap.guru_penyetuju_id', $guruId)
            ->where('jadwal_swap.status', 'pending')
            ->where('jadwal_swap.guru_setuju', 0)
            ->orderBy('jadwal_swap.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Pengajuan yang sudah di-ACC guru (tahap 1 selesai), MENUNGGU ADMIN (tahap 2).
     */
    public function getMenungguAdmin(): array
    {
        return $this->select($this->kolomLengkap())
            ->join('jadwal as ja', 'ja.id = jadwal_swap.jadwal_asal_id')
            ->join('jadwal as jt', 'jt.id = jadwal_swap.jadwal_tujuan_id')
            ->join('kelas as ka', 'ka.id = ja.kelas_id')
            ->join('kelas as kt', 'kt.id = jt.kelas_id')
            ->join('mata_pelajaran as ma', 'ma.id = ja.mapel_id')
            ->join('mata_pelajaran as mt', 'mt.id = jt.mapel_id')
            ->join('guru as ga', 'ga.id = jadwal_swap.guru_pengaju_id')
            ->where('jadwal_swap.status', 'pending')
            ->where('jadwal_swap.guru_setuju', 1)
            ->orderBy('jadwal_swap.created_at', 'ASC')
            ->findAll();
    }

    /**
     * Riwayat lengkap (diajukan maupun dituju) untuk satu guru.
     */
    public function getRiwayatGuru(int $guruId): array
    {
        return $this->select($this->kolomLengkap() . ', gp.nama as nama_guru_penyetuju')
            ->join('jadwal as ja', 'ja.id = jadwal_swap.jadwal_asal_id')
            ->join('jadwal as jt', 'jt.id = jadwal_swap.jadwal_tujuan_id')
            ->join('kelas as ka', 'ka.id = ja.kelas_id')
            ->join('kelas as kt', 'kt.id = jt.kelas_id')
            ->join('mata_pelajaran as ma', 'ma.id = ja.mapel_id')
            ->join('mata_pelajaran as mt', 'mt.id = jt.mapel_id')
            ->join('guru as ga', 'ga.id = jadwal_swap.guru_pengaju_id')
            ->join('guru as gp', 'gp.id = jadwal_swap.guru_penyetuju_id')
            ->groupStart()
                ->where('jadwal_swap.guru_pengaju_id', $guruId)
                ->orWhere('jadwal_swap.guru_penyetuju_id', $guruId)
            ->groupEnd()
            ->orderBy('jadwal_swap.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Semua pengajuan se-sekolah dengan filter — dipakai laporan & Waka Kurikulum.
     */
    public function getSemua(array $filter): array
    {
        $builder = $this->select($this->kolomLengkap() . ', gp.nama as nama_guru_penyetuju')
            ->join('jadwal as ja', 'ja.id = jadwal_swap.jadwal_asal_id')
            ->join('jadwal as jt', 'jt.id = jadwal_swap.jadwal_tujuan_id')
            ->join('kelas as ka', 'ka.id = ja.kelas_id')
            ->join('kelas as kt', 'kt.id = jt.kelas_id')
            ->join('mata_pelajaran as ma', 'ma.id = ja.mapel_id')
            ->join('mata_pelajaran as mt', 'mt.id = jt.mapel_id')
            ->join('guru as ga', 'ga.id = jadwal_swap.guru_pengaju_id')
            ->join('guru as gp', 'gp.id = jadwal_swap.guru_penyetuju_id');

        if (! empty($filter['status'])) {
            $builder->where('jadwal_swap.status', $filter['status']);
        }

        return $builder->orderBy('jadwal_swap.created_at', 'DESC')->findAll();
    }

    /**
     * Jumlah pengajuan yang masih menunggu respon PERSONAL $guruId (buat badge
     * sidebar) — versi ringan dari getMenungguGuru(), cuma COUNT tanpa JOIN detail.
     */
    public function hitungMenungguGuru(int $guruId): int
    {
        return $this->where('guru_penyetuju_id', $guruId)->where('status', 'pending')->where('guru_setuju', 0)->countAllResults();
    }

    public function hitungMenunggu(): int
    {
        return $this->where('status', 'pending')->countAllResults();
    }

    /**
     * Kolom SELECT yang dipakai berulang di atas: info lengkap kedua jadwal
     * (asal & tujuan) plus nama guru pengaju, supaya tidak diulang 5x.
     */
    private function kolomLengkap(): string
    {
        return implode(', ', [
            'jadwal_swap.*',
            'ja.hari as hari_asal', 'ja.jam_mulai as jam_mulai_asal', 'ja.jam_selesai as jam_selesai_asal',
            'ja.jam_ke_mulai as jam_ke_mulai_asal', 'ja.jam_ke_selesai as jam_ke_selesai_asal',
            'ka.nama_kelas as kelas_asal', 'ma.nama as mapel_asal',
            'jt.hari as hari_tujuan', 'jt.jam_mulai as jam_mulai_tujuan', 'jt.jam_selesai as jam_selesai_tujuan',
            'jt.jam_ke_mulai as jam_ke_mulai_tujuan', 'jt.jam_ke_selesai as jam_ke_selesai_tujuan',
            'kt.nama_kelas as kelas_tujuan', 'mt.nama as mapel_tujuan',
            'ga.nama as nama_guru_pengaju',
        ]);
    }
}
