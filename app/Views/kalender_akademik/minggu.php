<?php
use App\Models\AgendaAkademikModel;
$KATEGORI = AgendaAkademikModel::KATEGORI;
$today = date('Y-m-d');
$hariList = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
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
    <a href="<?= base_url('kalender-akademik?mode=minggu&awal=' . date('Y-m-d', strtotime($awalMinggu . ' -7 days'))) ?>" class="kal-nav-btn">&larr;</a>
    <a href="<?= base_url('kalender-akademik?mode=minggu&awal=' . date('Y-m-d', strtotime($awalMinggu . ' +7 days'))) ?>" class="kal-nav-btn">&rarr;</a>
    <?php if (! $iniMinggu) : ?><a href="<?= base_url('kalender-akademik?mode=minggu') ?>" class="btn btn-outline btn-sm">Hari ini</a><?php endif; ?>
    <span class="ka-bulan-label"><?= esc(date('d M', strtotime($awalMinggu))) ?> – <?= esc(date('d M Y', strtotime($akhirMinggu))) ?></span>
  </div>
  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <div class="ka-view-tabs">
      <a href="<?= base_url('kalender-akademik?mode=bulan') ?>" class="ka-view-tab">Bulan</a>
      <button type="button" class="ka-view-tab active">Minggu</button>
      <a href="<?= base_url('kalender-akademik?mode=agenda') ?>" class="ka-view-tab">Agenda</a>
    </div>
    <?php if ($bisaKelola) : ?>
      <button type="button" class="btn btn-primary btn-sm" onclick="bukaTambahEvent('<?= esc($today) ?>')">
        <svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah event
      </button>
    <?php endif; ?>
  </div>
</div>

<div class="ka-layout">
  <div class="ka-main">
    <div class="ka-grid-header">
      <?php foreach ($hariList as $i => $h) :
        $tglHari  = date('Y-m-d', strtotime($awalMinggu . " +{$i} days"));
        $isToday  = $tglHari === $today;
        $isMinggu = $h === 'Minggu';
      ?>
        <div style="<?= $isToday ? 'color:var(--color-primary)' : '' ?>">
          <?= esc($h) ?><br><span style="font-weight:400;font-size:10px;<?= $isMinggu && ! $isToday ? 'color:var(--color-danger)' : '' ?>"><?= esc(date('d M', strtotime($tglHari))) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="ka-grid ka-grid-minggu">
      <?php foreach ($hariList as $i => $h) :
        $tglHari  = date('Y-m-d', strtotime($awalMinggu . " +{$i} days"));
        $isToday  = $tglHari === $today;
        $events   = $eventPerTanggal[$tglHari] ?? [];
      ?>
        <div class="ka-cell ka-cell-minggu<?= $isToday ? ' is-today' : '' ?>"
             onclick="<?= $bisaKelola ? "bukaTambahEvent('" . esc($tglHari, 'js') . "')" : '' ?>">
          <?php if (empty($events)) : ?>
            <div class="text-soft" style="font-size:11px;padding-top:4px">Tidak ada agenda</div>
          <?php else : ?>
            <?php foreach ($events as $ev) : $kat = $KATEGORI[$ev['kategori']]; ?>
              <div class="ka-pill ka-pill-minggu<?= $bisaKelola ? ' is-editable' : '' ?>" style="background:<?= esc($kat['soft']) ?>;color:<?= esc($kat['warna']) ?>"
                   <?= $bisaKelola ? "onclick=\"event.stopPropagation(); bukaEditEvent(" . htmlspecialchars(json_encode($ev), ENT_QUOTES) . ")\"" : '' ?>>
                <span class="ka-pill-dot" style="background:<?= esc($kat['warna']) ?>"></span>
                <span style="min-width:0;flex:1">
                  <?= esc($ev['judul']) ?>
                  <?php if (! $ev['all_day'] && $ev['jam_mulai']) : ?><span style="opacity:.75"> · <?= esc(substr($ev['jam_mulai'], 0, 5)) ?></span><?php endif; ?>
                </span>
                <?php if ($bisaKelola) : ?>
                  <span class="ka-pill-hapus" title="Hapus event ini" onclick="event.stopPropagation(); hapusEventCepat(<?= (int) $ev['id'] ?>, '<?= esc($ev['judul'], 'js') ?>')">&times;</span>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="ka-legend">
      <?php foreach ($KATEGORI as $kunci => $k) : ?>
        <div class="ka-legend-item">
          <span class="ka-legend-dot" style="background:<?= esc($k['warna']) ?>"></span>
          <span class="ka-legend-label"><?= esc($k['label']) ?></span>
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
  </div>
</div>

<?php if ($bisaKelola) : ?>
  <?= view('kalender_akademik/_modal_event', ['KATEGORI' => $KATEGORI]) ?>
<?php endif; ?>
