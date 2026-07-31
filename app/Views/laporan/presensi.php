<?php $qs = http_build_query(array_filter($filter, static fn ($v) => $v !== '')); ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-chart"/></svg> Laporan presensi</h1>
  <p class="text-muted">Filter bebas dikombinasikan — tanggal saja untuk laporan harian/bulanan, tambah guru/kelas/mapel untuk rekap per masing-masing.</p>
</div>

<form method="get" action="<?= base_url('laporan/presensi') ?>" class="card" style="margin-bottom:20px">
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
    <div class="form-group">
      <label for="mapel_id">Mata pelajaran</label>
      <select id="mapel_id" name="mapel_id">
        <option value="">Semua mapel</option>
        <?php foreach ($mapel as $m) : ?>
          <option value="<?= esc($m['id']) ?>" <?= (string) $filter['mapel_id'] === (string) $m['id'] ? 'selected' : '' ?>><?= esc($m['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap">
    <button type="submit" class="btn btn-primary"><svg class="icon-sm" style="stroke:#fff"><use href="#i-search"/></svg> Terapkan filter</button>
    <a href="<?= base_url('laporan/presensi/export-pdf?' . $qs) ?>" class="btn btn-outline" target="_blank"><svg class="icon-sm"><use href="#i-file-text"/></svg> Export PDF</a>
    <a href="<?= base_url('laporan/presensi/export-excel?' . $qs) ?>" class="btn btn-outline"><svg class="icon-sm"><use href="#i-download"/></svg> Export Excel</a>
  </div>
</form>

<div class="stat-grid" style="margin-bottom:20px">
  <?php foreach ($rekap as $status => $jumlah) : ?>
    <div class="stat-card">
      <div class="stat-label"><?= esc($status) ?></div>
      <div class="stat-value sm"><?= esc($jumlah) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="table-wrap table-responsive-cards">
  <table class="table">
    <thead><tr><th style="width:50px">No.</th><th>Tanggal</th><th>Kelas</th><th>Mapel</th><th>Guru</th><th>Siswa</th><th>Status</th><th>Catatan</th><th>Tukar Jadwal</th></tr></thead>
    <tbody>
      <?php if (empty($rows)) : ?>
        <tr><td colspan="9"><div class="empty-state"><h3>Tidak ada data</h3><p>Coba ubah rentang tanggal atau filter lainnya.</p></div></td></tr>
      <?php else : ?>
        <?php foreach ($rows as $i => $r) : ?>
          <tr>
            <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
            <td data-label="Tanggal"><?= esc(date('d-m-Y', strtotime($r['tanggal']))) ?></td>
            <td data-label="Kelas"><?= esc($r['nama_kelas']) ?></td>
            <td data-label="Mapel"><?= esc($r['nama_mapel']) ?></td>
            <td data-label="Guru"><?= esc($r['nama_guru']) ?></td>
            <td class="td-card-title"><?= esc($r['nama']) ?> <span class="text-soft" style="font-weight:400;font-size:12px">(<?= esc($r['nis']) ?>)</span></td>
            <td data-label="Status"><span class="status-badge status-<?= esc($r['status']) ?>"><?= esc(ucfirst($r['status'])) ?></span></td>
            <td data-label="Catatan"><?= esc($r['catatan'] ?? '-') ?></td>
            <td data-label="Tukar Jadwal"><?php if (! empty($r['ditukar'])) : ?><span class="status-badge status-izin" title="Jadwal aslinya hari <?= esc($r['hari_asli']) ?>">Ditukar</span><?php else : ?><span class="text-soft">-</span><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
