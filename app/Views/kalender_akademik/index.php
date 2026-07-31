<?php
use App\Models\AgendaAkademikModel;
$KATEGORI = AgendaAkademikModel::KATEGORI;
$namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$today = date('Y-m-d');

$bulanSebelum = $bulan - 1; $tahunSebelum = $tahun;
if ($bulanSebelum < 1) { $bulanSebelum = 12; $tahunSebelum--; }
$bulanSesudah = $bulan + 1; $tahunSesudah = $tahun;
if ($bulanSesudah > 12) { $bulanSesudah = 1; $tahunSesudah++; }
?>
<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-calendar"/></svg> Kalender akademik</h1>
  <p class="text-muted">Semua agenda sekolah dalam satu tempat — KBM, ujian, libur, rapat, dan kegiatan lainnya.</p>
</div>

<div class="ka-toolbar">
  <div class="ka-toolbar-left">
    <a href="<?= base_url('kalender-akademik?bulan=' . $bulanSebelum . '&tahun=' . $tahunSebelum) ?>" class="kal-nav-btn" title="Bulan sebelumnya: <?= esc($namaBulan[$bulanSebelum]) ?> <?= esc($tahunSebelum) ?>">&larr;</a>
    <a href="<?= base_url('kalender-akademik?bulan=' . $bulanSesudah . '&tahun=' . $tahunSesudah) ?>" class="kal-nav-btn" title="Bulan berikutnya: <?= esc($namaBulan[$bulanSesudah]) ?> <?= esc($tahunSesudah) ?>">&rarr;</a>
    <?php if (! ($bulan === (int) date('n') && $tahun === (int) date('Y'))) : ?>
      <a href="<?= base_url('kalender-akademik') ?>" class="btn btn-outline btn-sm">Hari ini</a>
    <?php endif; ?>
    <span class="ka-bulan-label"><?= esc($namaBulan[$bulan]) ?> <?= esc($tahun) ?></span>
    <?php if ($bulan === (int) date('n') && $tahun === (int) date('Y')) : ?>
      <span class="ka-bulan-ini-badge">Bulan ini</span>
    <?php endif; ?>
  </div>
  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <div class="ka-view-tabs">
      <button type="button" class="ka-view-tab active">Bulan</button>
      <a href="<?= base_url('kalender-akademik?mode=minggu') ?>" class="ka-view-tab">Minggu</a>
      <a href="<?= base_url('kalender-akademik?mode=agenda') ?>" class="ka-view-tab">Agenda</a>
    </div>
    <?php if ($bisaKelola) : ?>
      <button type="button" class="btn btn-primary btn-sm" onclick="bukaTambahEvent('<?= esc($today) ?>')">
        <svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah event
      </button>
    <?php endif; ?>
  </div>
</div>

<div class="ka-filter-row" id="filterKategori">
  <?php foreach ($KATEGORI as $kunci => $k) : ?>
    <label class="ka-filter-chip is-on" style="--chip-warna:<?= esc($k['warna']) ?>;--chip-soft:<?= esc($k['soft']) ?>">
      <input type="checkbox" checked data-kategori="<?= esc($kunci) ?>" onchange="terapkanFilterKategori()">
      <span class="ka-filter-dot"></span>
      <?= esc($k['label']) ?>
    </label>
  <?php endforeach; ?>
</div>

<div class="ka-layout">
  <div class="ka-main">
    <div class="ka-grid-header">
      <?php foreach (['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h) : ?>
        <div><?= esc($h) ?></div>
      <?php endforeach; ?>
    </div>
    <div class="ka-grid">
      <?php
      $cursor = $awalGrid;
      while ($cursor <= $akhirGrid) :
          $eventHariIni = $eventPerTanggal[$cursor] ?? [];
          $isOutside    = $cursor < $awalBulan || $cursor > $akhirBulan;
          $isToday      = $cursor === $today;
          $isMinggu     = (int) date('N', strtotime($cursor)) === 7;
          $tampil       = array_slice($eventHariIni, 0, 2);
          $sisa         = count($eventHariIni) - count($tampil);
      ?>
        <div class="ka-cell<?= $isOutside ? ' is-outside' : '' ?><?= $isToday ? ' is-today' : '' ?><?= $bisaKelola ? ' is-selectable' : '' ?>"
             data-tanggal="<?= esc($cursor) ?>"
             <?= $bisaKelola ? '' : "onclick=\"lihatDetailHari('" . esc($cursor, 'js') . "')\"" ?>>
          <div class="ka-cell-num<?= $isMinggu && ! $isToday ? ' is-minggu' : '' ?>"><?= esc((int) date('j', strtotime($cursor))) ?></div>
          <?php foreach ($tampil as $ev) : $kat = $KATEGORI[$ev['kategori']]; ?>
            <div class="ka-pill<?= $bisaKelola ? ' is-editable' : '' ?> kategori-<?= esc($ev['kategori']) ?>" style="background:<?= esc($kat['soft']) ?>;color:<?= esc($kat['warna']) ?>" title="<?= esc($ev['judul']) ?>"
                 <?= $bisaKelola ? "onclick=\"event.stopPropagation(); bukaEditEvent(" . htmlspecialchars(json_encode($ev), ENT_QUOTES) . ")\"" : '' ?>>
              <span class="ka-pill-dot" style="background:<?= esc($kat['warna']) ?>"></span><?= esc($ev['judul']) ?>
              <?php if ($bisaKelola) : ?>
                <span class="ka-pill-hapus" title="Hapus event ini" onclick="event.stopPropagation(); hapusEventCepat(<?= (int) $ev['id'] ?>, '<?= esc($ev['judul'], 'js') ?>')">&times;</span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          <?php if ($sisa > 0) : ?>
            <div class="ka-more">+<?= esc($sisa) ?> lainnya</div>
          <?php endif; ?>
        </div>
      <?php
          $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
      endwhile;
      ?>
    </div>

    <div class="ka-legend">
      <?php foreach ($KATEGORI as $kunci => $k) : ?>
        <div class="ka-legend-item">
          <span class="ka-legend-dot" style="background:<?= esc($k['warna']) ?>"></span>
          <span class="ka-legend-label"><?= esc($k['label']) ?></span>
          <span class="ka-legend-desc">— <?= esc($k['deskripsi']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="ka-sidebar">
    <div class="card ka-side-card">
      <div class="ka-side-title"><h3>Timeline akademik</h3></div>
      <?php if (empty($timeline)) : ?>
        <p class="text-muted" style="font-size:12.5px;margin:0">Tidak ada agenda dalam 90 hari ke depan.</p>
      <?php else : ?>
        <div class="ka-timeline">
          <?php foreach ($timeline as $ev) : $kat = $KATEGORI[$ev['kategori']]; ?>
            <div class="ka-timeline-item">
              <span class="ka-timeline-dot" style="background:<?= esc($kat['warna']) ?>"></span>
              <div class="ka-timeline-tanggal"><?= esc(date('d M Y', strtotime($ev['tanggal_tampil']))) ?></div>
              <div class="ka-timeline-judul"><?= esc($ev['judul']) ?></div>
              <span class="role-badge" style="background:<?= esc($kat['soft']) ?>;color:<?= esc($kat['warna']) ?>"><?= esc($kat['label']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="card ka-side-card">
      <div class="ka-side-title"><h3>Event terdekat</h3></div>
      <?php if (empty($eventTerdekat)) : ?>
        <p class="text-muted" style="font-size:12.5px;margin:0">Tidak ada event mendatang.</p>
      <?php else : ?>
        <?php foreach ($eventTerdekat as $ev) : $kat = $KATEGORI[$ev['kategori']]; ?>
          <div class="ka-upcoming-item">
            <div class="ka-upcoming-date">
              <div class="d"><?= esc(date('d', strtotime($ev['tanggal_tampil']))) ?></div>
              <div class="m"><?= esc(strtoupper(date('M', strtotime($ev['tanggal_tampil'])))) ?></div>
            </div>
            <div style="min-width:0;flex:1">
              <div class="ka-upcoming-judul"><?= esc($ev['judul']) ?></div>
              <div class="ka-upcoming-meta">
                <?php if (! $ev['all_day'] && $ev['jam_mulai']) : ?>
                  <svg class="icon-sm"><use href="#i-clock"/></svg> <?= esc(substr($ev['jam_mulai'], 0, 5)) ?>
                <?php endif; ?>
              </div>
              <span class="role-badge" style="background:<?= esc($kat['soft']) ?>;color:<?= esc($kat['warna']) ?>;margin-top:4px;display:inline-block"><?= esc($kat['label']) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($bisaKelola) : ?>
  <?= view('kalender_akademik/_modal_event', ['KATEGORI' => $KATEGORI]) ?>
<?php endif; ?>

<script>
function terapkanFilterKategori() {
  const aktif = [];
  document.querySelectorAll('#filterKategori input[type="checkbox"]').forEach(function (cb) {
    cb.closest('.ka-filter-chip').classList.toggle('is-on', cb.checked);
    if (cb.checked) { aktif.push(cb.dataset.kategori); }
  });
  document.querySelectorAll('.ka-pill').forEach(function (pill) {
    const kat = Array.from(pill.classList).find(function (c) { return c.startsWith('kategori-'); });
    const kodeKat = kat ? kat.replace('kategori-', '') : '';
    pill.style.display = aktif.includes(kodeKat) ? '' : 'none';
  });
}

function lihatDetailHari(tanggal) {
  // Guru/kepsek (bukan admin): klik tanggal cukup info, belum ada modal detail
  // khusus di fase ini — cukup arahkan fokus visual saja utk sekarang.
}

<?php if ($bisaKelola) : ?>
// ---- Blok-pilih rentang tanggal (klik-tahan-seret) untuk Tambah Event ----
// Klik biasa (mousedown+mouseup di sel yang sama tanpa geser) = 1 hari saja,
// perilakunya sama seperti sebelumnya. Menekan lalu menyeret ke sel lain =
// rentang tanggal, dari yang paling awal sampai paling akhir yang disorot.
(function () {
  let sedangMenyeleksi = false;
  let tanggalMulaiSeleksi = null;

  function sorotRentang(a, b) {
    const awal = a < b ? a : b;
    const akhir = a < b ? b : a;
    document.querySelectorAll('.ka-cell.is-selectable').forEach(function (cell) {
      const t = cell.dataset.tanggal;
      cell.classList.toggle('is-selecting', t >= awal && t <= akhir);
    });
  }

  function mulaiSeleksi(tanggal) {
    sedangMenyeleksi = true;
    tanggalMulaiSeleksi = tanggal;
    sorotRentang(tanggal, tanggal);
  }

  function selesaiSeleksi() {
    if (! sedangMenyeleksi) { return; }
    sedangMenyeleksi = false;
    const terpilih = Array.from(document.querySelectorAll('.ka-cell.is-selecting')).map(function (c) { return c.dataset.tanggal; }).sort();
    document.querySelectorAll('.ka-cell.is-selecting').forEach(function (c) { c.classList.remove('is-selecting'); });
    if (terpilih.length > 0) {
      bukaTambahEvent(terpilih[0], terpilih[terpilih.length - 1]);
    }
  }

  document.querySelectorAll('.ka-cell.is-selectable').forEach(function (cell) {
    cell.addEventListener('mousedown', function (e) {
      if (e.target.closest('.ka-pill')) { return; } // klik pill = edit event, bukan mulai seleksi
      mulaiSeleksi(cell.dataset.tanggal);
    });
    cell.addEventListener('mouseenter', function () {
      if (sedangMenyeleksi) { sorotRentang(tanggalMulaiSeleksi, cell.dataset.tanggal); }
    });

    // Sentuhan (HP/tablet): touchstart mulai seleksi, touchmove cari sel di
    // bawah jari lewat elementFromPoint (touch tidak punya mouseenter per elemen).
    cell.addEventListener('touchstart', function (e) {
      if (e.target.closest('.ka-pill')) { return; }
      mulaiSeleksi(cell.dataset.tanggal);
    }, { passive: true });
  });

  document.addEventListener('touchmove', function (e) {
    if (! sedangMenyeleksi) { return; }
    const titik = e.touches[0];
    const el = document.elementFromPoint(titik.clientX, titik.clientY);
    const sel = el ? el.closest('.ka-cell.is-selectable') : null;
    if (sel) { sorotRentang(tanggalMulaiSeleksi, sel.dataset.tanggal); }
  }, { passive: true });

  document.addEventListener('mouseup', selesaiSeleksi);
  document.addEventListener('touchend', selesaiSeleksi);
})();
<?php endif; ?>
</script>
