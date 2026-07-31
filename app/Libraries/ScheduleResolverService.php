<?php

namespace App\Libraries;

use App\Models\JadwalModel;
use App\Models\JadwalSwapModel;

/**
 * Satu-satunya pintu masuk resmi untuk mengetahui "jadwal efektif" — jadwal
 * master DITIMPA oleh pertukaran (jadwal_swap) yang berlaku untuk tanggal
 * tertentu, kalau ada. Semua modul (Kalender, Presensi, Jurnal, Monitoring,
 * Laporan) SEHARUSNYA memakai service ini alih-alih membaca tabel `jadwal`
 * langsung, supaya konsisten begitu pertukaran diterapkan.
 *
 * Konsep pertukaran (BUKAN guru pengganti): yang ditukar adalah HARI & JAM
 * dua baris jadwal, sedangkan guru/mapel/kelas TETAP melekat pada baris
 * aslinya masing-masing. Tabel `jadwal` sendiri tidak pernah diubah.
 */
class ScheduleResolverService
{
    private JadwalModel $jadwalModel;
    private JadwalSwapModel $swapModel;

    public function __construct()
    {
        $this->jadwalModel = new JadwalModel();
        $this->swapModel   = new JadwalSwapModel();
    }

    /**
     * Jadwal efektif satu guru, untuk SATU tanggal spesifik. Dipakai Kalender
     * Guru, Presensi, dan Jurnal — modul-modul yang selalu beroperasi pada
     * satu tanggal tertentu.
     */
    public function jadwalEfektifGuru(int $guruId, int $semesterId, string $tanggal): array
    {
        $master = $this->jadwalModel
            ->select('jadwal.*, mata_pelajaran.nama as nama_mapel, kelas.nama_kelas')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->where('jadwal.guru_id', $guruId)
            ->where('jadwal.semester_id', $semesterId)
            ->findAll();

        return array_map(fn ($j) => $this->terapkan($j, $tanggal), $master);
    }

    /**
     * Jadwal efektif SATU BARIS jadwal spesifik (dikenali dari jadwal_id),
     * untuk satu tanggal. Dipakai ketika modul lain (mis. Presensi/Jurnal)
     * sudah tahu jadwal_id-nya dan cuma perlu tahu apakah hari/jam efektifnya
     * berubah untuk tanggal itu.
     */
    public function jadwalEfektifSatuBaris(int $jadwalId, string $tanggal): ?array
    {
        $master = $this->jadwalModel
            ->select('jadwal.*, mata_pelajaran.nama as nama_mapel, kelas.nama_kelas, guru.nama as nama_guru')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->join('guru', 'guru.id = jadwal.guru_id')
            ->where('jadwal.id', $jadwalId)
            ->first();

        return $master ? $this->terapkan($master, $tanggal) : null;
    }

    /**
     * Jadwal efektif SELURUH SEKOLAH untuk satu tanggal spesifik (semua guru,
     * semua kelas) — dipakai grid jadwal induk & Monitoring. Menyusuri SEMUA
     * baris jadwal semester ini (bukan cuma yang hari master-nya cocok),
     * karena baris yang aslinya hari LAIN bisa saja efektifnya pindah ke
     * tanggal ini akibat pertukaran — lalu disaring ke yang hari efektifnya
     * benar-benar cocok dengan tanggal yang diminta.
     */
    public function jadwalEfektifSekolah(int $semesterId, string $tanggal): array
    {
        $hariMap  = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => null];
        $harinya  = $hariMap[(int) date('N', strtotime($tanggal))] ?? null;

        if ($harinya === null) {
            return [];
        }

        $master = $this->jadwalModel
            ->select('jadwal.*, guru.nama as nama_guru, mata_pelajaran.nama as nama_mapel, kelas.nama_kelas')
            ->join('guru', 'guru.id = jadwal.guru_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id = jadwal.mapel_id')
            ->join('kelas', 'kelas.id = jadwal.kelas_id')
            ->where('jadwal.semester_id', $semesterId)
            ->findAll();

        $hasil = [];
        foreach ($master as $j) {
            $efektif = $this->terapkan($j, $tanggal);
            if ($efektif['hari'] === $harinya) {
                $hasil[] = $efektif;
            }
        }

        return $hasil;
    }

    /**
     * Inti resolver: terapkan override pertukaran (kalau ada yang DISETUJUI
     * dan aktif untuk tanggal ini) pada satu baris jadwal. Yang berubah cuma
     * hari & jam; guru/mapel/kelas tetap melekat ke baris aslinya.
     */
    public function terapkan(array $jadwal, string $tanggal): array
    {
        $swap = $this->swapModel->getAktifUntukJadwal((int) $jadwal['id'], $tanggal);

        if (! $swap) {
            return $jadwal + ['ditukar' => false, 'hari_asli' => $jadwal['hari']];
        }

        $idPasangan = (int) $swap['jadwal_asal_id'] === (int) $jadwal['id']
            ? (int) $swap['jadwal_tujuan_id']
            : (int) $swap['jadwal_asal_id'];

        $pasangan = $this->jadwalModel->find($idPasangan);

        if (! $pasangan) {
            return $jadwal + ['ditukar' => false, 'hari_asli' => $jadwal['hari']];
        }

        return array_merge($jadwal, [
            'hari'           => $pasangan['hari'],
            'jam_ke_mulai'   => $pasangan['jam_ke_mulai'],
            'jam_ke_selesai' => $pasangan['jam_ke_selesai'],
            'jam_mulai'      => $pasangan['jam_mulai'],
            'jam_selesai'    => $pasangan['jam_selesai'],
            'ditukar'        => true,
            'hari_asli'      => $jadwal['hari'],
        ]);
    }
}
