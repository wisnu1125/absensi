<?php

namespace App\Models;

use CodeIgniter\Model;

class JamPelajaranModel extends Model
{
    protected $table         = 'jam_pelajaran';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $useSoftDeletes = true;
    protected $allowedFields = ['hari', 'jam_ke', 'jam_mulai', 'jam_selesai'];

    protected $validationRules = [
        'hari'        => 'required|in_list[Senin,Selasa,Rabu,Kamis,Jumat,Sabtu]',
        'jam_ke'      => 'required|is_natural_no_zero',
        'jam_mulai'   => 'required',
        'jam_selesai' => 'required',
    ];
    protected $validationMessages = [
        'jam_ke' => ['required' => 'Jam ke- wajib diisi.'],
        'hari'   => ['required' => 'Hari wajib dipilih.'],
    ];

    /**
     * Jam pelajaran SATU hari tertentu saja, diurutkan jam_ke — dipakai di
     * mana pun jadwal untuk satu hari spesifik perlu ditampilkan/dipilih
     * (form Tambah Jadwal, grid, Kalender).
     */
    /**
     * Daftar NOMOR jam ke- unik lintas semua hari (union), diurutkan — dipakai
     * sebagai baris Y-axis grid (Jadwal admin, Kalender guru) karena satu grid
     * dilihat lintas hari sekaligus, jadi barisnya harus mencakup jam ke- dari
     * SEMUA hari (kalau Jumat cuma sampai jam ke-6 tapi hari lain sampai ke-9,
     * barisnya tetap sampai ke-9, sel Jumat jam ke-9 kosong).
     */
    public function getSemuaJamKeUnik(): array
    {
        $rows = $this->select('jam_ke')->distinct()->orderBy('jam_ke', 'ASC')->findAll();

        return array_column($rows, 'jam_ke');
    }

    public function getByHari(string $hari): array
    {
        return $this->where('hari', $hari)->orderBy('jam_ke', 'ASC')->findAll();
    }

    /**
     * SEMUA jam pelajaran, semua hari, dikelompokkan per hari — dipakai
     * halaman kelola Jam Pelajaran. Urutan hari selalu Senin..Sabtu meskipun
     * data disimpan/dibaca tidak berurutan.
     */
    public function getSemuaDikelompokkan(): array
    {
        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $semua    = $this->orderBy('jam_ke', 'ASC')->findAll();

        $dikelompokkan = array_fill_keys($hariList, []);
        foreach ($semua as $row) {
            $dikelompokkan[$row['hari']][] = $row;
        }

        return $dikelompokkan;
    }

    public function findByHariJamKe(string $hari, int $jamKe): ?array
    {
        return $this->where('hari', $hari)->where('jam_ke', $jamKe)->first();
    }

    /**
     * Dipakai form Tambah/Edit: cek apakah kombinasi hari+jam_ke ini sudah
     * dipakai baris LAIN — pengganti is_unique bawaan karena constraint-nya
     * sekarang gabungan 2 kolom, bukan cuma jam_ke sendiri.
     */
    public function sudahAda(string $hari, int $jamKe, ?int $excludeId = null): bool
    {
        $builder = $this->where('hari', $hari)->where('jam_ke', $jamKe);
        if ($excludeId !== null) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->first() !== null;
    }

    /**
     * Cek apakah rentang jam yang diisi tumpang tindih dengan periode LAIN
     * di HARI YANG SAMA — periode di hari berbeda tidak mungkin beririsan
     * karena memang bukan waktu yang sama.
     */
    public function cekBentrok(string $hari, string $jamMulai, string $jamSelesai, ?int $excludeId = null): ?array
    {
        $builder = $this->where('hari', $hari)->where('jam_mulai <', $jamSelesai)->where('jam_selesai >', $jamMulai);

        if ($excludeId !== null) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->first();
    }

    /**
     * Dipakai sebelum menghapus: cegah hapus periode yang masih dirujuk oleh
     * jadwal yang sudah ada PADA HARI YANG SAMA (jadwal.hari + jam_ke_mulai/
     * jam_ke_selesai), supaya jadwal itu tidak jadi tidak bisa diedit karena
     * periodenya hilang.
     */
    public function dipakaiJadwal(string $hari, int $jamKe): bool
    {
        $jadwalModel = new JadwalModel();

        return $jadwalModel->where('hari', $hari)
                ->groupStart()
                    ->where('jam_ke_mulai', $jamKe)
                    ->orWhere('jam_ke_selesai', $jamKe)
                ->groupEnd()
            ->countAllResults() > 0;
    }
}
