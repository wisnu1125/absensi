<?php
$totalRekap = array_sum($rekap);
$qs = array_filter($filter, static fn ($v) => $v !== '');
?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-users"/></svg> Wali kelas — <?= esc($kelas['nama_kelas']) ?></h1>
  <p class="text-muted">Tingkat <?= esc($kelas['tingkat']) ?> &middot; <?= esc(count($siswa)) ?> siswa</p>
</div>

<form method="get" action="<?= base_url('wali-kelas') ?>" class="card" style="margin-bottom:20px">
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

<h2 style="display:flex;align-items:center;gap:8px"><svg class="icon"><use href="#i-chart"/></svg> Grafik kehadiran</h2>

<?php if ($totalRekap === 0) : ?>
  <div class="empty-state">
    <h3>Belum ada data presensi pada periode ini</h3>
    <p class="text-muted">Coba perlebar rentang tanggal.</p>
  </div>
<?php else : ?>
  <div class="card" style="margin-bottom:24px">
    <?php foreach ($rekap as $status => $jumlah) :
      $persen = $totalRekap > 0 ? round($jumlah / $totalRekap * 100) : 0;
    ?>
      <div style="margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px">
          <span style="font-weight:600;text-transform:capitalize"><?= esc($status) ?></span>
          <span class="text-muted"><?= esc($jumlah) ?> (<?= esc($persen) ?>%)</span>
        </div>
        <div style="background:var(--color-bg);border-radius:999px;height:10px;overflow:hidden">
          <div class="status-<?= esc($status) ?>" style="width:<?= esc($persen) ?>%;height:100%;border-radius:999px;background:currentColor;opacity:.85"></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<h2 style="display:flex;align-items:center;gap:8px"><svg class="icon"><use href="#i-users"/></svg> Data &amp; rekap siswa</h2>

<div class="table-wrap">
  <table class="table">
    <thead>
      <tr><th>NIS</th><th>Nama</th><th>Hadir</th><th>Sakit</th><th>Izin</th><th>Alpha</th><th>Terlambat</th></tr>
    </thead>
    <tbody>
      <?php if (empty($rekapPerSiswa)) : ?>
        <tr><td colspan="7"><div class="empty-state"><h3>Belum ada siswa di kelas ini</h3></div></td></tr>
      <?php else : ?>
        <?php foreach ($rekapPerSiswa as $r) : ?>
          <tr>
            <td><?= esc($r['nis']) ?></td>
            <td><?= esc($r['nama']) ?></td>
            <td><?= esc($r['hadir']) ?></td>
            <td><?= esc($r['sakit']) ?></td>
            <td><?= esc($r['izin']) ?></td>
            <td><?= $r['alpha'] > 0 ? '<strong style="color:var(--color-danger)">' . esc($r['alpha']) . '</strong>' : esc($r['alpha']) ?></td>
            <td><?= esc($r['terlambat']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
