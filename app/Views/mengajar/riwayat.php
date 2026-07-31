<div class="page-header">
  <h1><svg class="icon"><use href="#i-history"/></svg> Riwayat mengajar</h1>
  <p class="text-muted">Semua sesi presensi &amp; jurnal yang pernah Anda isi. Untuk cek status &amp; melengkapi yang belum, buka <a href="<?= base_url('mengajar/kalender') ?>">Kalender jadwal</a>.</p>
</div>

<?php if ($statistik === null) : ?>
  <div class="alert alert-danger" style="background:var(--color-warning-soft);color:var(--color-warning);margin-bottom:20px">
    <svg class="icon-sm"><use href="#i-alert"/></svg>
    Statistik jumlah mengajar semester ini belum bisa dihitung — semester aktif belum diisi
    tanggal berlakunya (Admin bisa mengisinya di menu Tahun Ajaran &amp; Semester).
  </div>
<?php else : ?>
  <div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card"><div class="stat-label">Sudah mengajar</div><div class="stat-value text-success"><?= esc($statistik['sudah']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Seharusnya (semester ini)</div><div class="stat-value"><?= esc($statistik['seharusnya']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Terlewat</div><div class="stat-value<?= $statistik['terlewat'] > 0 ? ' text-danger' : '' ?>"><?= esc($statistik['terlewat']) ?></div></div>
  </div>
<?php endif; ?>

<form method="get" action="<?= base_url('mengajar/riwayat') ?>" class="card" style="margin-bottom:20px">
  <div class="form-row">
    <div class="form-group">
      <label for="tanggal_dari">Tanggal dari</label>
      <input type="date" id="tanggal_dari" name="tanggal_dari" value="<?= esc($filter['tanggal_dari']) ?>">
    </div>
    <div class="form-group">
      <label for="tanggal_sampai">Tanggal sampai</label>
      <input type="date" id="tanggal_sampai" name="tanggal_sampai" value="<?= esc($filter['tanggal_sampai']) ?>">
    </div>
  </div>
  <button type="submit" class="btn btn-primary"><svg class="icon-sm" style="stroke:#fff"><use href="#i-search"/></svg> Terapkan filter</button>
</form>

<div class="table-wrap table-responsive-cards">
  <table class="table">
    <thead><tr><th style="width:50px">No.</th><th>Tanggal</th><th>Kelas</th><th>Mapel</th><th>Materi</th><th>Rekap kehadiran</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($rows)) : ?>
        <tr><td colspan="7"><div class="empty-state"><h3>Belum ada riwayat</h3><p>Coba ubah rentang tanggal, atau mulai mengajar dari dashboard.</p></div></td></tr>
      <?php else : ?>
        <?php foreach ($rows as $i => $r) : ?>
          <tr>
            <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
            <td class="td-card-title">
              <?= esc(date('d-m-Y', strtotime($r['tanggal']))) ?>
              <div class="text-soft" style="font-weight:400;font-size:12px"><?= esc($r['hari']) ?>, <?= esc(substr($r['jam_mulai'], 0, 5)) ?>–<?= esc(substr($r['jam_selesai'], 0, 5)) ?></div>
            </td>
            <td data-label="Kelas"><?= esc($r['nama_kelas']) ?></td>
            <td data-label="Mapel"><?= esc($r['nama_mapel']) ?></td>
            <td data-label="Materi"><?= $r['materi'] ? esc($r['materi']) : '<span class="text-soft">Jurnal belum diisi</span>' ?></td>
            <td data-label="Rekap kehadiran">
              <div style="display:flex;gap:5px;flex-wrap:wrap">
                <?php foreach ($r['rekap'] as $status => $jumlah) : ?>
                  <?php if ($jumlah > 0) : ?>
                    <span class="status-badge status-<?= esc($status) ?>"><?= esc(ucfirst($status)) ?>: <?= esc($jumlah) ?></span>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </td>
            <td class="td-card-actions" data-label="">
              <a href="<?= base_url('mengajar/riwayat/detail/' . $r['presensi_id']) ?>" class="btn-icon"><svg class="icon-sm"><use href="#i-clipboard"/></svg> Detail</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
