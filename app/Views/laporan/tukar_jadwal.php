<?php $qs = array_filter($filter, static fn ($v) => $v !== ''); ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-history"/></svg> Laporan tukar jadwal</h1>
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

<div class="card-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
  <div class="card"><div class="text-soft" style="font-size:11px;text-transform:uppercase">Menunggu</div><div style="font-size:24px;font-weight:700;color:var(--color-warning)"><?= esc($rekap['menunggu']) ?></div></div>
  <div class="card"><div class="text-soft" style="font-size:11px;text-transform:uppercase">Disetujui</div><div style="font-size:24px;font-weight:700;color:var(--color-success)"><?= esc($rekap['disetujui']) ?></div></div>
  <div class="card"><div class="text-soft" style="font-size:11px;text-transform:uppercase">Ditolak</div><div style="font-size:24px;font-weight:700;color:var(--color-danger)"><?= esc($rekap['ditolak']) ?></div></div>
  <div class="card"><div class="text-soft" style="font-size:11px;text-transform:uppercase">Dibatalkan</div><div style="font-size:24px;font-weight:700"><?= esc($rekap['dibatalkan']) ?></div></div>
</div>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Tanggal sesi</th><th>Sesi</th><th>Guru asal</th><th>Guru pengganti</th><th>Alasan</th><th>Status</th></tr></thead>
    <tbody>
      <?php if (empty($rows)) : ?>
        <tr><td colspan="6"><div class="empty-state"><h3>Tidak ada pengajuan</h3><p>Coba ubah rentang tanggal atau filter status.</p></div></td></tr>
      <?php else : ?>
        <?php
        $warna = ['menunggu' => 'status-izin', 'disetujui' => 'status-hadir', 'ditolak' => 'status-alpha', 'dibatalkan' => 'status-sakit'];
        ?>
        <?php foreach ($rows as $r) : ?>
          <tr>
            <td><?= esc(date('d-m-Y', strtotime($r['tanggal']))) ?><div class="text-soft"><?= esc($r['hari']) ?></div></td>
            <td><?= esc($r['nama_kelas']) ?> — <?= esc($r['nama_mapel']) ?></td>
            <td><?= esc($r['nama_guru_asal']) ?></td>
            <td><?= esc($r['nama_guru_pengganti']) ?></td>
            <td><?= $r['alasan'] ? esc($r['alasan']) : '<span class="text-soft">-</span>' ?></td>
            <td><span class="status-badge <?= esc($warna[$r['status']] ?? '') ?>"><?= esc(ucfirst($r['status'])) ?></span></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
