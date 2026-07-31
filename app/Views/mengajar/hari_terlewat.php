<div class="page-header">
  <h1><svg class="icon"><use href="#i-alert"/></svg> Hari terlewat</h1>
  <p class="text-muted">
    Jadwal dalam 30 hari terakhir yang presensinya belum pernah diisi sama sekali (bukan cuma
    jurnalnya) — kemungkinan lupa mulai mengajar sama sekali hari itu. Klik "Isi sekarang" untuk
    melengkapi presensi &amp; jurnalnya.
  </p>
</div>

<?php if (empty($terlewat)) : ?>
  <div class="empty-state">
    <h3>Tidak ada hari yang terlewat</h3>
    <p class="text-muted">Semua jadwal dalam 30 hari terakhir sudah ada presensinya. Kerja bagus!</p>
  </div>
<?php else : ?>
  <div class="table-wrap table-responsive-cards">
    <table class="table">
      <thead><tr><th style="width:50px">No.</th><th>Tanggal</th><th>Kelas</th><th>Mapel</th><th style="text-align:right">Aksi</th></tr></thead>
      <tbody>
        <?php foreach ($terlewat as $i => $t) : ?>
          <tr>
            <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
            <td class="td-card-title">
              <?= esc(date('d-m-Y', strtotime($t['tanggal']))) ?>
              <div class="text-soft" style="font-weight:400;font-size:12px"><?= esc($t['hari']) ?>, <?= esc(substr($t['jam_mulai'], 0, 5)) ?>–<?= esc(substr($t['jam_selesai'], 0, 5)) ?></div>
            </td>
            <td data-label="Kelas"><?= esc($t['nama_kelas']) ?></td>
            <td data-label="Mapel"><?= esc($t['nama_mapel']) ?></td>
            <td class="td-card-actions" data-label="">
              <a href="<?= base_url('mengajar/riwayat/hari-terlewat/presensi/' . $t['jadwal_id'] . '/' . $t['tanggal']) ?>" class="btn btn-primary btn-sm">
                <svg class="icon-sm" style="stroke:#fff"><use href="#i-clipboard-check"/></svg> Isi sekarang
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<p style="margin-top:20px"><a href="<?= base_url('mengajar/riwayat') ?>">&larr; Kembali ke riwayat mengajar</a></p>
