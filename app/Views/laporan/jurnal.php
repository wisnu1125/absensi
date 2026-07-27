<?php $qs = http_build_query(array_filter($filter, static fn ($v) => $v !== '')); ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-file-text"/></svg> Rekap jurnal mengajar</h1>
  <p class="text-muted">Daftar materi &amp; kegiatan pembelajaran yang sudah diisi guru.</p>
</div>

<form method="get" action="<?= base_url('laporan/jurnal') ?>" class="card" style="margin-bottom:20px">
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
  <div class="form-row">
    <div class="form-group">
      <label for="guru_id">Guru</label>
      <select id="guru_id" name="guru_id">
        <option value="">Semua guru</option>
        <?php foreach ($guru as $g) : ?>
          <option value="<?= esc($g['id']) ?>" <?= (string) $filter['guru_id'] === (string) $g['id'] ? 'selected' : '' ?>><?= esc($g['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="kelas_id">Kelas</label>
      <select id="kelas_id" name="kelas_id">
        <option value="">Semua kelas</option>
        <?php foreach ($kelas as $k) : ?>
          <option value="<?= esc($k['id']) ?>" <?= (string) $filter['kelas_id'] === (string) $k['id'] ? 'selected' : '' ?>><?= esc($k['nama_kelas']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap">
    <button type="submit" class="btn btn-primary"><svg class="icon-sm" style="stroke:#fff"><use href="#i-search"/></svg> Terapkan filter</button>
    <a href="<?= base_url('laporan/jurnal/export-pdf?' . $qs) ?>" class="btn btn-outline" target="_blank"><svg class="icon-sm"><use href="#i-file-text"/></svg> Export PDF</a>
    <a href="<?= base_url('laporan/jurnal/export-excel?' . $qs) ?>" class="btn btn-outline"><svg class="icon-sm"><use href="#i-download"/></svg> Export Excel</a>
  </div>
</form>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Tanggal</th><th>Kelas</th><th>Mapel</th><th>Guru</th><th>Materi</th><th>Kendala</th></tr></thead>
    <tbody>
      <?php if (empty($rows)) : ?>
        <tr><td colspan="6"><div class="empty-state"><h3>Tidak ada data</h3><p>Coba ubah rentang tanggal atau filter lainnya.</p></div></td></tr>
      <?php else : ?>
        <?php foreach ($rows as $r) : ?>
          <tr>
            <td><?= esc(date('d-m-Y', strtotime($r['tanggal']))) ?></td>
            <td><?= esc($r['nama_kelas']) ?></td>
            <td><?= esc($r['nama_mapel']) ?></td>
            <td><?= esc($r['nama_guru']) ?></td>
            <td><?= esc($r['materi']) ?></td>
            <td><?= esc($r['kendala'] ?: '-') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
