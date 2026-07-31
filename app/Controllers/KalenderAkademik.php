<?php

namespace App\Controllers;

use App\Libraries\AuditLogger;
use App\Models\AgendaAkademikModel;

/**
 * Kalender Akademik — bisa DILIHAT semua role yang sudah login (cuma perlu
 * filter 'auth' dasar, tidak dibatasi role tertentu), tapi CRUD (tambah/
 * ubah/hapus event) HANYA Administrator — dicek lewat filter rute
 * 'role:administrator' pada method store/update/delete di Routes.php, BUKAN
 * di sini, supaya kalau ada yang mencoba akses langsung tanpa lewat UI pun
 * tetap diblokir di lapisan rute.
 */
class KalenderAkademik extends BaseController
{
    public function index()
    {
        $mode = in_array($this->request->getGet('mode'), ['bulan', 'agenda', 'minggu'], true) ? $this->request->getGet('mode') : 'bulan';

        if ($mode === 'agenda') {
            return $this->tampilanAgenda();
        }

        if ($mode === 'minggu') {
            return $this->tampilanMinggu();
        }

        $bulan = max(1, min(12, (int) ($this->request->getGet('bulan') ?: date('n'))));
        $tahun = (int) ($this->request->getGet('tahun') ?: date('Y'));

        $awalBulan = sprintf('%04d-%02d-01', $tahun, $bulan);
        $akhirBulan = date('Y-m-t', strtotime($awalBulan));

        // Grid kalender menampilkan sisa minggu dari bulan sebelum/sesudah juga
        // (supaya selalu genap mulai Senin, selesai Minggu), jadi butuh rentang
        // event yang SEDIKIT LEBIH LEBAR dari sekadar tanggal 1 s/d akhir bulan.
        $awalGrid  = $this->awalMingguDari($awalBulan);
        $akhirGrid = $this->akhirMingguDari($akhirBulan);

        $model = new AgendaAkademikModel();

        $data = [
            'title'   => 'Kalender Akademik',
            'content' => view('kalender_akademik/index', [
                'bulan'           => $bulan,
                'tahun'           => $tahun,
                'awalBulan'       => $awalBulan,
                'akhirBulan'      => $akhirBulan,
                'awalGrid'        => $awalGrid,
                'akhirGrid'       => $akhirGrid,
                'eventPerTanggal' => $model->getEventPerTanggal($awalGrid, $akhirGrid),
                'eventTerdekat'   => $model->getEventTerdekat(4),
                'timeline'        => $model->getEventTerdekat(5),
                'bisaKelola'      => has_role('administrator'),
            ]),
        ];

        return view('layouts/main', $data);
    }

    private function tampilanAgenda()
    {
        $model = new AgendaAkademikModel();
        $dari  = date('Y-m-d');
        $sampai = date('Y-m-d', strtotime('+60 days'));

        $perTanggal = $model->getEventPerTanggal($dari, $sampai);

        $data = [
            'title'   => 'Kalender Akademik — Agenda',
            'content' => view('kalender_akademik/agenda', [
                'perTanggal'    => $perTanggal,
                'dari'          => $dari,
                'sampai'        => $sampai,
                'eventTerdekat' => $model->getEventTerdekat(4),
                'timeline'      => $model->getEventTerdekat(5),
                'bisaKelola'    => has_role('administrator'),
            ]),
        ];

        return view('layouts/main', $data);
    }

    private function tampilanMinggu()
    {
        $awalPatokan = $this->request->getGet('awal') ?: date('Y-m-d');
        $awalMinggu  = $this->awalMingguDari($awalPatokan);
        $akhirMinggu = $this->akhirMingguDari($awalPatokan);

        $model = new AgendaAkademikModel();

        $data = [
            'title'   => 'Kalender Akademik — Minggu ini',
            'content' => view('kalender_akademik/minggu', [
                'awalMinggu'      => $awalMinggu,
                'akhirMinggu'     => $akhirMinggu,
                'iniMinggu'       => $awalMinggu === $this->awalMingguDari(date('Y-m-d')),
                'eventPerTanggal' => $model->getEventPerTanggal($awalMinggu, $akhirMinggu),
                'eventTerdekat'   => $model->getEventTerdekat(4),
                'timeline'        => $model->getEventTerdekat(5),
                'bisaKelola'      => has_role('administrator'),
            ]),
        ];

        return view('layouts/main', $data);
    }

    private function awalMingguDari(string $tanggal): string
    {
        // Kalender umum: Minggu di awal, Sabtu di akhir (bukan ISO Senin-Minggu).
        // date('N') = 1(Senin)..6(Sabtu),7(Minggu) -> modulo 7 jadi 0(Minggu)..6(Sabtu).
        $n = (int) date('N', strtotime($tanggal)) % 7;

        return date('Y-m-d', strtotime($tanggal . ' -' . $n . ' days'));
    }

    private function akhirMingguDari(string $tanggal): string
    {
        $n = (int) date('N', strtotime($tanggal)) % 7;

        return date('Y-m-d', strtotime($tanggal . ' +' . (6 - $n) . ' days'));
    }

    public function store()
    {
        $model = new AgendaAkademikModel();

        $data = $this->dataDariRequest();

        $id = $model->insert($data);

        if (! $id) {
            return redirect()->back()->withInput()->with('error', implode(' ', $model->errors()));
        }

        (new AuditLogger())->log('tambah_agenda_akademik', 'Menambah event: ' . $data['judul'] . ' (' . $data['kategori'] . ')');

        return redirect()->to('/kalender-akademik?bulan=' . date('n', strtotime($data['tanggal_mulai'])) . '&tahun=' . date('Y', strtotime($data['tanggal_mulai'])))
            ->with('message', 'Event berhasil ditambahkan.');
    }

    public function update()
    {
        $id    = (int) $this->request->getPost('id');
        $model = new AgendaAkademikModel();

        $data     = $this->dataDariRequest();
        $data['id'] = $id;

        $ok = $model->update($id, $data);

        if (! $ok) {
            return redirect()->back()->withInput()->with('error', implode(' ', $model->errors()));
        }

        (new AuditLogger())->log('ubah_agenda_akademik', 'Mengubah event #' . $id . ': ' . $data['judul']);

        return redirect()->to('/kalender-akademik?bulan=' . date('n', strtotime($data['tanggal_mulai'])) . '&tahun=' . date('Y', strtotime($data['tanggal_mulai'])))
            ->with('message', 'Event berhasil diperbarui.');
    }

    public function delete($id)
    {
        $model = new AgendaAkademikModel();
        $event = $model->find((int) $id);

        if (! $event) {
            return redirect()->to('/kalender-akademik')->with('error', 'Event tidak ditemukan.');
        }

        $model->delete((int) $id);

        (new AuditLogger())->log('hapus_agenda_akademik', 'Menghapus event #' . $id . ': ' . $event['judul']);

        return redirect()->to('/kalender-akademik?bulan=' . date('n', strtotime($event['tanggal_mulai'])) . '&tahun=' . date('Y', strtotime($event['tanggal_mulai'])))
            ->with('message', 'Event berhasil dihapus.');
    }

    private function dataDariRequest(): array
    {
        $recurringHari = $this->request->getPost('recurring_hari') ?: [];
        $allDay        = $this->request->getPost('all_day') ? 1 : 0;

        return [
            'judul'           => $this->request->getPost('judul'),
            'deskripsi'       => $this->request->getPost('deskripsi') ?: null,
            'kategori'        => $this->request->getPost('kategori'),
            'tanggal_mulai'   => $this->request->getPost('tanggal_mulai'),
            'tanggal_selesai' => $this->request->getPost('tanggal_selesai') ?: $this->request->getPost('tanggal_mulai'),
            'jam_mulai'       => $allDay ? null : ($this->request->getPost('jam_mulai') ?: null),
            'jam_selesai'     => $allDay ? null : ($this->request->getPost('jam_selesai') ?: null),
            'all_day'         => $allDay,
            'status'          => $this->request->getPost('status') ?: 'terjadwal',
            'dampak_presensi' => $this->request->getPost('dampak_presensi') ?: 'normal',
            'recurring_hari'  => is_array($recurringHari) && ! empty($recurringHari) ? implode(',', $recurringHari) : null,
            'dibuat_oleh'     => (int) session()->get('user_id'),
        ];
    }
}
