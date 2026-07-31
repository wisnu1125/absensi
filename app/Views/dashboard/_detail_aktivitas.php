<?php
// Partial ini butuh variabel $detail (array dari Dashboard::detailAktivitasHariIni())
// dari view yang meng-include-nya.
?>
<div class="card-title" style="margin-bottom:10px">Rincian per kelas hari ini — siapa sudah, siapa belum</div>
<?php if (empty($detail)) : ?>
  <p class="text-muted" style="font-size:13px;margin:0">Tidak ada jadwal hari ini.</p>
<?php else : ?>
  <div class="table-wrap table-responsive-cards">
    <table class="table">
      <thead><tr><th style="width:50px">No.</th><th>Jam</th><th>Guru</th><th>Kelas</th><th>Mapel</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($detail as $i => $d) : ?>
          <tr>
            <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
            <td class="text-soft" data-label="Jam" style="white-space:nowrap"><?= esc(substr($d['jam_mulai'], 0, 5)) ?>–<?= esc(substr($d['jam_selesai'], 0, 5)) ?></td>
            <td class="td-card-title"><?= esc($d['nama_guru']) ?></td>
            <td data-label="Kelas"><?= esc($d['nama_kelas']) ?></td>
            <td data-label="Mapel"><?= esc($d['nama_mapel']) ?></td>
            <td data-label="Status">
              <?php if ($d['status'] === 'selesai') : ?>
                <span class="status-badge status-hadir"><svg class="icon-sm"><use href="#i-check-circle"/></svg> Selesai</span>
              <?php elseif ($d['status'] === 'berlangsung') : ?>
                <span class="status-badge status-izin">Presensi ada, jurnal belum</span>
              <?php elseif ($d['status'] === 'digantikan') : ?>
                <span class="role-badge" title="<?= esc($d['keterangan']) ?>"><?= esc($d['keterangan']) ?></span>
              <?php else : ?>
                <span class="status-badge status-alpha">Belum mulai</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
