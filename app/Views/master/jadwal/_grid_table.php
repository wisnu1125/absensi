<?php if (empty($kelasList)) : ?>
  <div class="empty-state"><h3>Belum ada kelas untuk tahun ajaran aktif</h3></div>
<?php else : ?>
  <div class="table-wrap table-grid-matrix">
    <table class="table" style="min-width:<?= 110 + count($kelasList) * 130 ?>px">
      <thead>
        <tr>
          <th style="width:110px">Jam ke-</th>
          <?php foreach ($kelasList as $k) : ?>
            <th><?= esc($k['nama_kelas']) ?></th>
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
            <?php foreach ($kelasList as $k) :
              $cell = $grid[$jp['jam_ke']][$k['id']] ?? null;
              if ($cell === 'lanjutan') { continue; }
            ?>
              <?php if (is_array($cell)) : ?>
                <td rowspan="<?= esc($cell['rowspan']) ?>" data-kelas-id="<?= esc($k['id']) ?>" style="cursor:pointer;background:var(--color-primary-soft)"
                    onclick='bukaEditGrid(<?= json_encode($cell) ?>, <?= esc($jp["jam_ke"]) ?>, <?= (int) $cell["rowspan"] ?>)'>
                  <div class="grid-cell-title"><?= esc($cell['nama_mapel']) ?></div>
                  <div class="text-soft" style="font-size:11px"><?= esc($cell['nama_guru']) ?></div>
                </td>
              <?php else : ?>
                <td class="grid-cell-empty" data-jam-ke="<?= esc($jp['jam_ke']) ?>" data-kelas-id="<?= esc($k['id']) ?>"
                    data-nama-kelas="<?= esc($k['nama_kelas'], 'js') ?>" data-tingkat="<?= esc($k['tingkat'] ?? '', 'js') ?>"
                    style="cursor:pointer;text-align:center;color:var(--color-text-soft)"
                    onclick="bukaTambahGrid('<?= esc($jp['jam_ke']) ?>','<?= esc($k['id'], 'js') ?>','<?= esc($k['nama_kelas'], 'js') ?>','<?= esc($k['tingkat'], 'js') ?>')">+</td>
              <?php endif; ?>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
