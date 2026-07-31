<?php $qs = array_filter($filter, static fn ($v) => $v !== ''); ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-history"/></svg> Laporan cari guru pengganti</h1>
  <p class="text-muted">Seluruh pengajuan guru pengganti se-sekolah — menunggu, disetujui, ditolak, maupun dibatalkan.</p>
</div>

<form method="get" action="<?= base_url('laporan/tukar-jadwal') ?>" class="card" style="margin-bottom:20px">
  <div class="form-row">
    <div class="form-group">
      <label for="tanggal_dari">Tanggal dari</label>
      <input type="date" id="tanggal_dari" name="tanggal_dari" value="<?= esc($filter['tanggal_dari']) ?>">
    </div>
    <div class="form-group">
      <label for="tanggal_sampai">Tanggal sampai</label>
      <input type="date" id="tanggal_sampai" name="tanggal_sampai" value="<?= esc($filter['tanggal_sampai']) ?>">
    </div>
    <div class="form-group">
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="">Semua status</option>
        <?php foreach (['menunggu', 'disetujui', 'ditolak', 'dibatalkan'] as $s) : ?>
          <option value="<?= esc($s) ?>" <?= $filter['status'] === $s ? 'selected' : '' ?>><?= esc(ucfirst($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <button type="submit" class="btn btn-primary"><svg class="icon-sm" style="stroke:#fff"><use href="#i-search"/></svg> Terapkan filter</button>
</form>

<div class="stat-grid" style="margin-bottom:20px">
  <div class="stat-card"><div class="stat-label">Menunggu</div><div class="stat-value text-warning"><?= esc($rekap['menunggu']) ?></div></div>
  <div class="stat-card"><div class="stat-label">Disetujui</div><div class="stat-value text-success"><?= esc($rekap['disetujui']) ?></div></div>
  <div class="stat-card"><div class="stat-label">Ditolak</div><div class="stat-value text-danger"><?= esc($rekap['ditolak']) ?></div></div>
  <div class="stat-card"><div class="stat-label">Dibatalkan</div><div class="stat-value"><?= esc($rekap['dibatalkan']) ?></div></div>
</div>

<div class="table-wrap table-responsive-cards">
  <table class="table">
    <thead><tr><th style="width:50px">No.</th><th>Tanggal sesi</th><th>Sesi</th><th>Guru asal</th><th>Guru pengganti</th><th>Alasan</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($rows)) : ?>
        <tr><td colspan="8"><div class="empty-state"><h3>Tidak ada pengajuan</h3><p>Coba ubah rentang tanggal atau filter status.</p></div></td></tr>
      <?php else : ?>
        <?php
        $warna = ['menunggu' => 'status-izin', 'disetujui' => 'status-hadir', 'ditolak' => 'status-alpha', 'dibatalkan' => 'status-sakit'];
        ?>
        <?php foreach ($rows as $i => $r) : ?>
          <tr>
            <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
            <td data-label="Tanggal sesi"><?= esc(date('d-m-Y', strtotime($r['tanggal']))) ?><div class="text-soft"><?= esc($r['hari']) ?></div></td>
            <td data-label="Sesi"><?= esc($r['nama_kelas']) ?> — <?= esc($r['nama_mapel']) ?></td>
            <td class="td-card-title"><?= esc($r['nama_guru_asal']) ?></td>
            <td data-label="Guru pengganti"><?= esc($r['nama_guru_pengganti']) ?></td>
            <td data-label="Alasan"><?= $r['alasan'] ? esc($r['alasan']) : '<span class="text-soft">-</span>' ?></td>
            <td data-label="Status"><span class="status-badge <?= esc($warna[$r['status']] ?? '') ?>"><?= esc(ucfirst($r['status'])) ?></span></td>
            <td class="td-card-actions" data-label="">
              <?php if (in_array($r['status'], ['menunggu', 'disetujui'], true)) : ?>
                <form method="post" action="<?= base_url('laporan/tukar-jadwal/batalkan/' . $r['id']) ?>"
                  onsubmit="return confirm('Batalkan pengajuan ini sebagai admin? Guru pengaju &amp; pengganti tidak diminta konfirmasi ulang.')" style="text-align:right">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn-icon btn-icon-danger">Batalkan</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
