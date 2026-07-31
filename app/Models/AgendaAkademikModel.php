<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Kalender Akademik — pusat informasi agenda sekolah. Event BIASA disimpan
 * apa adanya (tanggal_mulai s/d tanggal_selesai). Event BERULANG (mis.
 * "Upacara tiap Senin") disimpan SATU BARIS SAJA (tanggal_mulai/selesai jadi
 * batas periode aktifnya, recurring_hari jadi pola harinya) dan DIEKSPANSI
 * jadi kemunculan per-tanggal SAAT DIBACA (query untuk rentang tanggal
 * tertentu) — pola yang sama dengan bagaimana `jadwal` bekerja di modul
 * Jadwal Mengajar, bukan menyimpan ratusan baris duplikat di database.
 */
class AgendaAkademikModel extends Model
{
    protected $table          = 'agenda_akademik';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = [
        'judul', 'deskripsi', 'kategori', 'tanggal_mulai', 'tanggal_selesai',
        'jam_mulai', 'jam_selesai', 'all_day', 'status', 'dampak_presensi',
        'recurring_hari', 'dibuat_oleh',
    ];

    protected $validationRules = [
        'id'              => 'permit_empty|is_natural_no_zero',
        'judul'           => 'required|max_length[200]',
        'kategori'        => 'required|in_list[kbm,ujian,libur,rapat,kegiatan,ppdb,pesantren,nasional]',
        'tanggal_mulai'   => 'required|valid_date',
        'tanggal_selesai' => 'required|valid_date',
    ];
    protected $validationMessages = [
        'judul'    => ['required' => 'Judul event wajib diisi.'],
        'kategori' => ['required' => 'Kategori wajib dipilih.'],
    ];

    /**
     * Satu-satunya sumber kebenaran untuk kategori & warnanya — dipakai
     * Controller, View kalender, legenda, filter, DAN modul lain yang nanti
     * perlu tahu warna/label kategori (mis. Dashboard), supaya warna selalu
     * konsisten di seluruh aplikasi (tidak diketik ulang di banyak tempat).
     */
    public const KATEGORI = [
        'kbm'       => ['label' => 'KBM', 'warna' => 'var(--color-primary)', 'soft' => 'var(--color-primary-soft)', 'deskripsi' => 'Kegiatan Belajar Mengajar'],
        'ujian'     => ['label' => 'Ujian', 'warna' => 'var(--color-danger)', 'soft' => 'var(--color-danger-soft)', 'deskripsi' => 'Pelaksanaan Ujian'],
        'libur'     => ['label' => 'Libur', 'warna' => 'var(--color-success)', 'soft' => 'var(--color-success-soft)', 'deskripsi' => 'Libur Sekolah'],
        'rapat'     => ['label' => 'Rapat', 'warna' => 'var(--color-substitute)', 'soft' => 'var(--color-substitute-soft)', 'deskripsi' => 'Rapat / Meeting'],
        'kegiatan'  => ['label' => 'Kegiatan', 'warna' => 'var(--color-warning)', 'soft' => 'var(--color-warning-soft)', 'deskripsi' => 'Kegiatan Sekolah'],
        'ppdb'      => ['label' => 'PPDB', 'warna' => 'var(--teal-500)', 'soft' => '#e3f7f3', 'deskripsi' => 'Penerimaan Siswa Baru'],
        'pesantren' => ['label' => 'Pesantren', 'warna' => '#a8631f', 'soft' => '#faf1e6', 'deskripsi' => 'Kegiatan Pesantren'],
        'nasional'  => ['label' => 'Nasional', 'warna' => 'var(--color-neutral)', 'soft' => 'var(--color-neutral-soft)', 'deskripsi' => 'Hari Nasional'],
    ];

    private const HARI_MAP = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    /**
     * Event dalam satu RENTANG TANGGAL (dipakai tampilan bulan/minggu/agenda),
     * SUDAH DIEKSPANSI — event berulang muncul di setiap tanggal yang cocok
     * dalam rentang ini, event biasa muncul di tanggal aslinya. Tiap hasil
     * dapat 'tanggal_tampil' (tanggal spesifik kemunculan ini) supaya
     * pemanggil tidak perlu tahu bedanya berulang/tidak.
     *
     * @param list<string>|null $kategoriFilter kalau diisi, cuma kategori ini yang disertakan
     */
    public function getEventRentang(string $dari, string $sampai, ?array $kategoriFilter = null): array
    {
        $builder = $this->where('tanggal_mulai <=', $sampai)->where('tanggal_selesai >=', $dari);

        if ($kategoriFilter !== null && $kategoriFilter !== []) {
            $builder->whereIn('kategori', $kategoriFilter);
        }

        $semua = $builder->orderBy('all_day', 'DESC')->orderBy('jam_mulai', 'ASC')->findAll();

        $hasil = [];
        foreach ($semua as $ev) {
            if ($ev['recurring_hari']) {
                // Event berulang: cek TIAP TANGGAL dalam irisan [dari,sampai] x
                // [tanggal_mulai,tanggal_selesai] event ini, cocokkan hari-nya.
                $hariAktif = array_map('trim', explode(',', $ev['recurring_hari']));
                $cursor    = max($dari, $ev['tanggal_mulai']);
                $batas     = min($sampai, $ev['tanggal_selesai']);

                while ($cursor <= $batas) {
                    $namaHari = self::HARI_MAP[(int) date('N', strtotime($cursor))] ?? '';
                    if (in_array($namaHari, $hariAktif, true)) {
                        $hasil[] = $ev + ['tanggal_tampil' => $cursor, 'is_recurring' => true];
                    }
                    $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
                }
            } else {
                // Event biasa: SATU kemunculan per tanggal dalam rentangnya sendiri
                // yang beririsan dengan [dari,sampai] — kalau event 3 hari (PTS),
                // dia muncul di ke-3 tanggal itu (masing2 "kejadian" hari yg sama).
                $cursor = max($dari, $ev['tanggal_mulai']);
                $batas  = min($sampai, $ev['tanggal_selesai']);
                while ($cursor <= $batas) {
                    $hasil[] = $ev + ['tanggal_tampil' => $cursor, 'is_recurring' => false];
                    $cursor  = date('Y-m-d', strtotime($cursor . ' +1 day'));
                }
            }
        }

        usort($hasil, static fn ($a, $b) => $a['tanggal_tampil'] <=> $b['tanggal_tampil'] ?: strcmp($a['jam_mulai'] ?? '', $b['jam_mulai'] ?? ''));

        return $hasil;
    }

    /**
     * Dikelompokkan per tanggal (key = 'Y-m-d') — bentuk yang paling praktis
     * dipakai tampilan kalender bulan (satu sel per tanggal).
     */
    public function getEventPerTanggal(string $dari, string $sampai, ?array $kategoriFilter = null): array
    {
        $flat = $this->getEventRentang($dari, $sampai, $kategoriFilter);

        $perTanggal = [];
        foreach ($flat as $ev) {
            $perTanggal[$ev['tanggal_tampil']][] = $ev;
        }

        return $perTanggal;
    }

    /**
     * Apakah TANGGAL tertentu punya event dengan dampak_presensi='nonaktif'
     * (mis. kategori Libur) — dipakai modul lain (Presensi/Jurnal/Dashboard)
     * utk tahu "apakah hari ini sekolah libur menurut Kalender Akademik".
     * Ikut memeriksa event berulang, bukan cuma event biasa.
     */
    public function adalahHariNonaktif(string $tanggal): bool
    {
        $events = $this->getEventRentang($tanggal, $tanggal);

        foreach ($events as $ev) {
            if ($ev['dampak_presensi'] === 'nonaktif' && $ev['status'] !== 'dibatalkan') {
                return true;
            }
        }

        return false;
    }

    /**
     * N event ke depan dari HARI INI (dipakai card "Event Terdekat") — event
     * berulang diikutkan juga (kemunculan berikutnya, bukan tanggal awal
     * pola berulangnya).
     */
    public function getEventTerdekat(int $jumlah = 5): array
    {
        $mulai   = date('Y-m-d');
        $sampai  = date('Y-m-d', strtotime('+90 days'));
        $events  = $this->getEventRentang($mulai, $sampai);

        return array_slice($events, 0, $jumlah);
    }
}
