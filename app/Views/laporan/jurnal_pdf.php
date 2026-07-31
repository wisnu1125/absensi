<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap Jurnal Mengajar</title>
<style>
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1f2937; margin: 24px; }
  h1 { font-size: 17px; margin: 0 0 4px; }
  .meta { font-size: 10px; color: #6b7280; margin-bottom: 16px; }
  .entri { border: 1px solid #d1d5db; border-radius: 4px; padding: 10px 12px; margin-bottom: 12px; page-break-inside: avoid; }
  .entri-judul { font-size: 12px; font-weight: bold; margin-bottom: 2px; }
  .entri-sub { font-size: 9.5px; color: #6b7280; margin-bottom: 8px; }
  .baris { margin-bottom: 6px; }
  .label { font-size: 9px; text-transform: uppercase; color: #6b7280; font-weight: bold; }
  .isi { font-size: 10.5px; }
  table.grid2 { width: 100%; }
  table.grid2 td { vertical-align: top; padding: 0 8px 0 0; width: 50%; }
</style>
</head>
<body>
  <h1>Rekap jurnal mengajar</h1>
  <div class="meta">
    Periode <?= esc(date('d-m-Y', strtotime($filter['tanggal_dari']))) ?> s/d <?= esc(date('d-m-Y', strtotime($filter['tanggal_sampai']))) ?>
    &nbsp;&middot;&nbsp; Dicetak <?= esc(date('d-m-Y H:i')) ?>
    &nbsp;&middot;&nbsp; <?= esc(count($rows)) ?> entri jurnal
  </div>

  <?php if (empty($rows)) : ?>
    <p>Tidak ada data pada periode/filter ini.</p>
  <?php endif; ?>

  <?php foreach ($rows as $r) : ?>
    <div class="entri">
      <div class="entri-judul"><?= esc($r['nama_kelas']) ?> — <?= esc($r['nama_mapel']) ?></div>
      <div class="entri-sub">
        <?= esc(date('d-m-Y', strtotime($r['tanggal']))) ?> (<?= esc($r['hari']) ?>) &middot; Guru: <?= esc($r['nama_guru']) ?>
        <?php if (! empty($r['ditukar'])) : ?> &middot; <strong>Ditukar</strong> (jadwal aslinya hari <?= esc($r['hari_asli']) ?>)<?php endif; ?>
      </div>

      <div class="baris"><span class="label">Materi:</span> <span class="isi"><?= esc($r['materi']) ?></span></div>

      <table class="grid2">
        <tr>
          <td><span class="label">Metode:</span> <span class="isi"><?= esc($r['metode'] ?: '-') ?></span></td>
          <td><span class="label">Media:</span> <span class="isi"><?= esc($r['media'] ?: '-') ?></span></td>
        </tr>
      </table>

      <?php if (! empty($r['tujuan_pembelajaran'])) : ?>
        <div class="baris"><span class="label">Tujuan pembelajaran:</span> <span class="isi"><?= esc($r['tujuan_pembelajaran']) ?></span></div>
      <?php endif; ?>
      <?php if (! empty($r['kegiatan_pembelajaran'])) : ?>
        <div class="baris"><span class="label">Kegiatan pembelajaran:</span> <span class="isi"><?= esc($r['kegiatan_pembelajaran']) ?></span></div>
      <?php endif; ?>
      <?php if (! empty($r['kendala'])) : ?>
        <div class="baris"><span class="label">Kendala:</span> <span class="isi"><?= esc($r['kendala']) ?></span></div>
      <?php endif; ?>
      <?php if (! empty($r['tindak_lanjut'])) : ?>
        <div class="baris"><span class="label">Tindak lanjut:</span> <span class="isi"><?= esc($r['tindak_lanjut']) ?></span></div>
      <?php endif; ?>
      <?php if (! empty($r['catatan'])) : ?>
        <div class="baris"><span class="label">Catatan:</span> <span class="isi"><?= esc($r['catatan']) ?></span></div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</body>
</html>
