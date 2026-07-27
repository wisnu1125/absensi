<div class="page-header">
  <h1><svg class="icon"><use href="#i-history"/></svg> Riwayat mengajar</h1>
  <p class="text-muted">Semua sesi presensi &amp; jurnal yang pernah Anda isi.</p>
</div>

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

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Tanggal</th><th>Kelas</th><th>Mapel</th><th>Materi</th><th>Rekap kehadiran</th></tr></thead>
    <tbody>
      <?php if (empty($rows)) : ?>
        <tr><td colspan="5"><div class="empty-state"><h3>Belum ada riwayat</h3><p>Coba ubah rentang tanggal, atau mulai mengajar dari dashboard.</p></div></td></tr>
      <?php else : ?>
        <?php foreach ($rows as $r) : ?>
          <tr>
            <td>
              <?= esc(date('d-m-Y', strtotime($r['tanggal']))) ?>
              <div class="text-soft"><?= esc($r['hari']) ?>, <?= esc(substr($r['jam_mulai'], 0, 5)) ?>–<?= esc(substr($r['jam_selesai'], 0, 5)) ?></div>
            </td>
            <td><?= esc($r['nama_kelas']) ?></td>
            <td><?= esc($r['nama_mapel']) ?></td>
            <td><?= $r['materi'] ? esc($r['materi']) : '<span class="text-soft">Jurnal belum diisi</span>' ?></td>
            <td>
              <div style="display:flex;gap:5px;flex-wrap:wrap">
                <?php foreach ($r['rekap'] as $status => $jumlah) : ?>
                  <?php if ($jumlah > 0) : ?>
                    <span class="status-badge status-<?= esc($status) ?>"><?= esc(ucfirst($status)) ?>: <?= esc($jumlah) ?></span>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
