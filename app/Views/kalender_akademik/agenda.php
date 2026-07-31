<?php
use App\Models\AgendaAkademikModel;
$KATEGORI = AgendaAkademikModel::KATEGORI;
$today = date('Y-m-d');
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
    <span class="ka-bulan-label">Agenda 60 hari ke depan</span>
  </div>
  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <div class="ka-view-tabs">
      <a href="<?= base_url('kalender-akademik?mode=bulan') ?>" class="ka-view-tab">Bulan</a>
      <a href="<?= base_url('kalender-akademik?mode=minggu') ?>" class="ka-view-tab">Minggu</a>
      <button type="button" class="ka-view-tab active">Agenda</button>
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
    <?php if (empty($perTanggal)) : ?>
      <div class="empty-state">
        <h3>Tidak ada agenda dalam 60 hari ke depan</h3>
        <?php if ($bisaKelola) : ?><p>Klik "Tambah event" untuk menambahkan agenda pertama.</p><?php endif; ?>
      </div>
    <?php else : ?>
      <?php foreach ($perTanggal as $tanggal => $events) : ?>
        <div class="card" style="margin-bottom:12px">
          <div style="display:flex;align-items:baseline;gap:8px;margin-bottom:10px">
            <span style="font-size:15px;font-weight:700;color:var(--color-text)"><?= esc(date('d', strtotime($tanggal))) ?></span>
            <span class="text-soft" style="font-size:12.5px;font-weight:600"><?= esc(date('l, d F Y', strtotime($tanggal))) ?><?= $tanggal === $today ? ' · Hari ini' : '' ?></span>
          </div>
          <div style="display:flex;flex-direction:column;gap:8px">
            <?php foreach ($events as $ev) : $kat = $KATEGORI[$ev['kategori']]; ?>
              <div style="display:flex;align-items:center;gap:10px;padding:8px 10px;background:<?= esc($kat['soft']) ?>;border-radius:var(--radius-sm);<?= $bisaKelola ? 'cursor:pointer' : '' ?>"
                   <?= $bisaKelola ? "onclick=\"bukaEditEvent(" . htmlspecialchars(json_encode($ev), ENT_QUOTES) . ")\"" : '' ?>>
                <span class="ka-filter-dot" style="background:<?= esc($kat['warna']) ?>;flex-shrink:0"></span>
                <div style="min-width:0;flex:1">
                  <div style="font-weight:600;font-size:13px;color:var(--color-text)"><?= esc($ev['judul']) ?></div>
                  <?php if ($ev['deskripsi']) : ?><div class="text-soft" style="font-size:11.5px;margin-top:1px"><?= esc($ev['deskripsi']) ?></div><?php endif; ?>
                </div>
                <?php if (! $ev['all_day'] && $ev['jam_mulai']) : ?>
                  <span class="text-soft" style="font-size:11.5px;white-space:nowrap"><?= esc(substr($ev['jam_mulai'], 0, 5)) ?></span>
                <?php endif; ?>
                <span class="role-badge" style="background:var(--color-surface);color:<?= esc($kat['warna']) ?>;flex-shrink:0"><?= esc($kat['label']) ?></span>
                <?php if ($bisaKelola) : ?>
                  <button type="button" class="btn-icon btn-icon-danger" style="flex-shrink:0" title="Hapus event ini"
                          onclick="event.stopPropagation(); hapusEventCepat(<?= (int) $ev['id'] ?>, '<?= esc($ev['judul'], 'js') ?>')">
                    <svg class="icon-sm"><use href="#i-trash"/></svg>
                  </button>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
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
