<div class="page-header">
  <h1><svg class="icon"><use href="#i-clipboard"/></svg> Detail sesi mengajar</h1>
  <p class="text-muted">
    <?= esc($jadwal['nama_mapel']) ?> — kelas <?= esc($jadwal['nama_kelas']) ?> —
    <?= esc(date('d-m-Y', strtotime($presensi['tanggal']))) ?>, <?= esc($jadwal['hari']) ?>,
    jam <?= esc(substr($jadwal['jam_mulai'], 0, 5)) ?>–<?= esc(substr($jadwal['jam_selesai'], 0, 5)) ?>
  </p>
</div>

<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="section">
  <div class="toolbar">
    <h2 style="margin:0;display:flex;align-items:center;gap:8px"><svg class="icon"><use href="#i-clipboard-check"/></svg> Presensi siswa</h2>
    <a href="<?= base_url('mengajar/riwayat/revisi-presensi/' . $presensi['id']) ?>" class="btn btn-outline btn-sm"><svg class="icon-sm"><use href="#i-edit"/></svg> Revisi presensi</a>
  </div>

  <div class="table-wrap table-responsive-cards">
    <table class="table">
      <thead><tr><th style="width:50px">No.</th><th>NIS</th><th>Nama</th><th>Status</th><th>Catatan</th></tr></thead>
      <tbody>
        <?php if (empty($siswaPresensi)) : ?>
          <tr><td colspan="5"><div class="empty-state"><h3>Tidak ada data presensi</h3></div></td></tr>
        <?php else : ?>
          <?php foreach ($siswaPresensi as $i => $s) : ?>
            <tr>
              <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
              <td data-label="NIS"><?= esc($s['nis']) ?></td>
              <td class="td-card-title"><?= esc($s['nama']) ?></td>
              <td data-label="Status"><span class="status-badge status-<?= esc($s['status']) ?>"><?= esc(ucfirst($s['status'])) ?></span></td>
              <td data-label="Catatan"><?= $s['catatan'] ? esc($s['catatan']) : '<span class="text-soft">-</span>' ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="section">
  <div class="toolbar">
    <h2 style="margin:0;display:flex;align-items:center;gap:8px"><svg class="icon"><use href="#i-file-text"/></svg> Jurnal mengajar</h2>
    <?php if ($jurnal) : ?>
      <a href="<?= base_url('mengajar/riwayat/revisi-jurnal/' . $jurnal['id']) ?>" class="btn btn-outline btn-sm"><svg class="icon-sm"><use href="#i-edit"/></svg> Revisi jurnal</a>
    <?php endif; ?>
  </div>

  <?php if (! $jurnal) : ?>
    <div class="empty-state"><h3>Jurnal belum diisi untuk sesi ini</h3><p class="text-muted">Bisa dilengkapi lewat <a href="<?= base_url('mengajar/kalender') ?>">Kalender jadwal</a>.</p></div>
  <?php else : ?>
    <div class="card">
      <?php
      $bidang = [
          'materi' => 'Materi', 'tujuan_pembelajaran' => 'Tujuan pembelajaran', 'metode' => 'Metode',
          'media' => 'Media', 'kegiatan_pembelajaran' => 'Kegiatan pembelajaran', 'kendala' => 'Kendala',
          'tindak_lanjut' => 'Tindak lanjut', 'catatan' => 'Catatan tambahan',
      ];
      ?>
      <?php foreach ($bidang as $kunci => $label) : ?>
        <div style="margin-bottom:14px">
          <div class="stat-label" style="margin-bottom:3px"><?= esc($label) ?></div>
          <div><?= $jurnal[$kunci] ? esc($jurnal[$kunci]) : '<span class="text-soft">-</span>' ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<p style="margin-top:8px"><a href="<?= base_url('mengajar/riwayat') ?>">&larr; Kembali ke riwayat mengajar</a></p>
