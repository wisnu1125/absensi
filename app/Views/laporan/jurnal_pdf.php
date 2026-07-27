<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap Jurnal Mengajar</title>
<style>
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; margin: 24px; }
  h1 { font-size: 17px; margin: 0 0 4px; }
  .meta { font-size: 10px; color: #6b7280; margin-bottom: 14px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #d1d5db; padding: 5px 7px; text-align: left; font-size: 9.5px; vertical-align: top; }
  th { background: #f3f4f6; font-weight: bold; }
</style>
</head>
<body>
  <h1>Rekap jurnal mengajar</h1>
  <div class="meta">
    Periode <?= esc(date('d-m-Y', strtotime($filter['tanggal_dari']))) ?> s/d <?= esc(date('d-m-Y', strtotime($filter['tanggal_sampai']))) ?>
    &nbsp;&middot;&nbsp; Dicetak <?= esc(date('d-m-Y H:i')) ?>
  </div>

  <table>
    <thead>
      <tr><th>Tanggal</th><th>Kelas</th><th>Mapel</th><th>Guru</th><th>Materi</th><th>Kendala</th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r) : ?>
        <tr>
          <td><?= esc(date('d-m-Y', strtotime($r['tanggal']))) ?></td>
          <td><?= esc($r['nama_kelas']) ?></td>
          <td><?= esc($r['nama_mapel']) ?></td>
          <td><?= esc($r['nama_guru']) ?></td>
          <td><?= esc($r['materi']) ?></td>
          <td><?= esc($r['kendala'] ?: '') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)) : ?>
        <tr><td colspan="6">Tidak ada data pada periode/filter ini.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
