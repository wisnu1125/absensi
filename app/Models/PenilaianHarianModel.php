<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Penilaian Harian — BAGIAN DARI Jurnal Mengajar (bukan menu sendiri). Aturan
 * bisnis paling penting: "Belum Dinilai ≠ Nilai Nol" — siswa yang barisnya
 * dikosongkan guru TIDAK dibuatkan record sama sekali, bukan disimpan
 * sebagai nilai kosong/nol. Guru dan Kelas/Mapel/Tanggal/TP/Materi TIDAK
 * disimpan di sini — semuanya diambil dari jurnal_id (jurnal_mengajar).
 */
class PenilaianHarianModel extends Model
{
    protected $table          = 'penilaian_harian';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = ['jurnal_id', 'siswa_id', 'jenis_penilaian', 'nilai', 'catatan'];

    protected $validationRules = [
        'jurnal_id'       => 'required|is_natural_no_zero',
        'siswa_id'        => 'required|is_natural_no_zero',
        'jenis_penilaian' => 'required|max_length[100]',
        'nilai'           => 'required|max_length[10]',
    ];

    /**
     * Simpan penilaian utk satu jurnal dari data form — INTI aturan bisnisnya
     * di sini: baris yang jenis_penilaian ATAU nilai-nya kosong TIDAK dibuat
     * record-nya sama sekali (bukan disimpan sbg 0/kosong). Kalau sedang
     * REVISI dan baris yang SEBELUMNYA terisi kini dikosongkan, record
     * lamanya ikut dihapus supaya datanya konsisten dgn form saat ini.
     *
     * @param array<int, array{jenis_penilaian?: string, nilai?: string, catatan?: string}> $rows keyed by siswa_id
     * @return int jumlah baris yang benar-benar tersimpan (utk pesan konfirmasi ke guru)
     */
    public function simpanUntukJurnal(int $jurnalId, array $rows): int
    {
        $existingSemua = $this->where('jurnal_id', $jurnalId)->findAll();
        $existingPerSiswa = [];
        foreach ($existingSemua as $e) {
            $existingPerSiswa[(int) $e['siswa_id']] = $e;
        }

        $tersimpan = 0;

        foreach ($rows as $siswaId => $row) {
            $siswaId = (int) $siswaId;
            $jenis   = trim((string) ($row['jenis_penilaian'] ?? ''));
            $nilai   = trim((string) ($row['nilai'] ?? ''));
            $catatan = trim((string) ($row['catatan'] ?? ''));
            $catatan = $catatan === '' ? null : $catatan;

            $existing = $existingPerSiswa[$siswaId] ?? null;

            // ATURAN UTAMA: baris kosong (jenis ATAU nilai belum diisi) tidak
            // pernah dibuatkan record. Kalau sebelumnya ADA (revisi lalu
            // dikosongkan), hapus yang lama supaya tetap konsisten.
            if ($jenis === '' || $nilai === '') {
                if ($existing) {
                    $this->delete((int) $existing['id']);
                }
                continue;
            }

            $payload = [
                'jurnal_id'       => $jurnalId,
                'siswa_id'        => $siswaId,
                'jenis_penilaian' => $jenis,
                'nilai'           => $nilai,
                'catatan'         => $catatan,
            ];

            if ($existing) {
                $this->update((int) $existing['id'], $payload);
            } else {
                $this->insert($payload);
            }
            $tersimpan++;
        }

        return $tersimpan;
    }

    /**
     * Penilaian yang SUDAH ADA untuk satu jurnal, dikelompokkan per siswa_id
     * — dipakai buat pre-fill form saat merevisi jurnal yang sudah pernah
     * diisi penilaiannya.
     */
    public function getUntukJurnal(int $jurnalId): array
    {
        $rows = $this->where('jurnal_id', $jurnalId)->findAll();

        $perSiswa = [];
        foreach ($rows as $r) {
            $perSiswa[(int) $r['siswa_id']] = $r;
        }

        return $perSiswa;
    }

    /**
     * Riwayat lengkap penilaian SATU siswa lintas semua jurnal — dipakai
     * halaman profil/riwayat siswa. Diurutkan dari yang PALING BARU.
     */
    public function getRiwayatSiswa(int $siswaId): array
    {
        return $this->select('penilaian_harian.*, jurnal_mengajar.tanggal, jurnal_mengajar.tujuan_pembelajaran,
                jurnal_mengajar.materi, mata_pelajaran.nama as nama_mapel, guru.nama as nama_guru')
            ->join('jurnal_mengajar', 'jurnal_mengajar.id = penilaian_harian.jurnal_id')
            ->join('jadwal', 'jadwal.id = jurnal_mengajar.jadwal_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('guru', 'guru.id = jadwal.guru_id')
            ->where('penilaian_harian.siswa_id', $siswaId)
            ->orderBy('jurnal_mengajar.tanggal', 'DESC')
            ->findAll();
    }
}
