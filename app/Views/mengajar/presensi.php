<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-clipboard-check"/></svg> Presensi siswa</h1>
  <p class="text-muted">
    <?= esc($jadwal['nama_mapel']) ?> — kelas <?= esc($jadwal['nama_kelas']) ?> —
    <?= esc(date('d-m-Y', strtotime($tanggal))) ?>,
    jam <?= esc(substr($jadwal['jam_mulai'], 0, 5)) ?>–<?= esc(substr($jadwal['jam_selesai'], 0, 5)) ?>
  </p>
</div>

<form method="post" action="<?= base_url($formAction ?? ('mengajar/presensi/' . $jadwal['id'])) ?>">
  <?= csrf_field() ?>

  <div class="toolbar">
    <input type="text" class="toolbar-search" placeholder="Cari siswa..." oninput="filterTable(this.value, 'tabelPresensi')">
    <button type="button" class="btn btn-outline" onclick="hadirSemua()"><svg class="icon-sm"><use href="#i-check-circle"/></svg> Hadir semua</button>
  </div>

  <div class="table-wrap table-responsive-cards">
    <table class="table" id="tabelPresensi">
      <thead>
        <tr>
          <th style="width:50px">No.</th>
          <th>Siswa</th>
          <th>Status kehadiran</th>
          <th>Catatan (opsional)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($siswa)) : ?>
          <tr><td colspan="4">
            <div class="empty-state">
              <h3>Belum ada siswa di kelas ini</h3>
              <p>Tambahkan data siswa lewat menu Siswa terlebih dahulu.</p>
            </div>
          </td></tr>
        <?php else : ?>
          <?php foreach ($siswa as $i => $s) :
              $statusSaatIni = $existing[$s['id']]['status'] ?? 'hadir';
              $catatanSaatIni = $existing[$s['id']]['catatan'] ?? '';
          ?>
            <tr>
              <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
              <td class="td-card-title"><?= esc($s['nama']) ?><div class="text-soft" style="font-weight:400;font-size:12px"><?= esc($s['nis']) ?></div></td>
              <td class="td-stack" data-label="Status kehadiran">
                <div class="status-pills" data-siswa="<?= esc($s['id']) ?>">
                  <?php foreach (['hadir' => 'Hadir', 'sakit' => 'Sakit', 'izin' => 'Izin', 'terlambat' => 'Terlambat', 'alpha' => 'Alpha'] as $val => $label) : ?>
                    <label class="pill pill-<?= $val ?>">
                      <input type="radio" name="status[<?= esc($s['id']) ?>]" value="<?= $val ?>" <?= $statusSaatIni === $val ? 'checked' : '' ?>>
                      <span><?= $label ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
              </td>
              <td class="td-stack" data-label="Catatan">
                <input type="text" name="catatan[<?= esc($s['id']) ?>]" value="<?= esc($catatanSaatIni) ?>" placeholder="-" class="catatan-input">
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if (! empty($siswa)) : ?>
    <div class="form-actions sticky-action-mobile">
      <button type="submit" class="btn btn-primary">Simpan &amp; lanjut ke jurnal <svg class="icon-sm" style="stroke:#fff"><use href="#i-chevron-right"/></svg></button>
    </div>
  <?php endif; ?>
</form>

<script>
// Menandai pill yang sedang dipilih dengan class 'is-checked' (dipakai untuk styling,
// tidak bergantung pada dukungan CSS :has() supaya aman di browser lama sekalipun).
function refreshPillState(group) {
  group.querySelectorAll('.pill').forEach(function (pill) {
    pill.classList.toggle('is-checked', pill.querySelector('input').checked);
  });
}
document.querySelectorAll('.status-pills').forEach(function (group) {
  refreshPillState(group);
  group.addEventListener('change', function () { refreshPillState(group); });
});

function hadirSemua() {
  document.querySelectorAll('.status-pills').forEach(function (group) {
    const radio = group.querySelector('input[value="hadir"]');
    if (radio) {
      radio.checked = true;
      refreshPillState(group);
    }
  });
}
</script>
