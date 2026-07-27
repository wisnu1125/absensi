<div class="page-header">
  <h1><svg class="icon"><use href="#i-calendar"/></svg> Kalender jadwal semester</h1>
  <p class="text-muted">
    <?= esc($aktif['nama_tahun_ajaran']) ?> — Semester <?= esc($aktif['nama']) ?>.
    Jadwal berbasis hari dan berulang tiap minggu, jadi grid ini berlaku sepanjang semester berjalan.
  </p>
</div>

<div class="table-wrap">
  <table class="table" style="min-width:760px">
    <thead>
      <tr>
        <th style="width:110px">Jam ke-</th>
        <?php foreach ($hariList as $h) : ?>
          <th><?= esc($h) ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($jamList as $jp) : ?>
        <tr>
          <td class="text-soft" style="white-space:nowrap">
            Ke-<?= esc($jp['jam_ke']) ?><br>
            <span style="font-size:11px"><?= esc(substr($jp['jam_mulai'], 0, 5)) ?>–<?= esc(substr($jp['jam_selesai'], 0, 5)) ?></span>
          </td>
          <?php foreach ($hariList as $h) :
            $cell = $grid[$jp['jam_ke']][$h] ?? null;
            if ($cell === 'lanjutan') { continue; } // sudah tercakup rowspan sel di atasnya
          ?>
            <?php if (is_array($cell)) : ?>
              <td rowspan="<?= esc($cell['rowspan']) ?>" style="background:var(--color-primary-soft);vertical-align:middle">
                <div style="font-weight:700;font-size:12.5px;color:var(--navy-900)"><?= esc($cell['nama_mapel']) ?></div>
                <div class="text-soft"><?= esc($cell['nama_kelas']) ?></div>
              </td>
            <?php else : ?>
              <td></td>
            <?php endif; ?>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if (empty($jamList)) : ?>
  <div class="empty-state">
    <h3>Belum ada data jam pelajaran</h3>
    <p class="text-muted">Hubungi administrator untuk mengatur jam pelajaran terlebih dahulu.</p>
  </div>
<?php endif; ?>
