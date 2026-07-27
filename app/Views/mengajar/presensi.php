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

<form method="post" action="<?= base_url('mengajar/presensi/' . $jadwal['id']) ?>">
  <?= csrf_field() ?>

  <div class="toolbar">
    <input type="text" class="toolbar-search" placeholder="Cari siswa..." oninput="filterTable(this.value, 'tabelPresensi')">
    <button type="button" class="btn btn-outline" onclick="hadirSemua()"><svg class="icon-sm"><use href="#i-check-circle"/></svg> Hadir semua</button>
  </div>

  <div class="table-wrap">
    <table class="table" id="tabelPresensi">
      <thead>
        <tr>
          <th>Siswa</th>
          <th>Status kehadiran</th>
          <th>Catatan (opsional)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($siswa)) : ?>
          <tr><td colspan="3">
            <div class="empty-state">
              <h3>Belum ada siswa di kelas ini</h3>
              <p>Tambahkan data siswa lewat menu Siswa terlebih dahulu.</p>
            </div>
          </td></tr>
        <?php else : ?>
          <?php foreach ($siswa as $s) :
              $statusSaatIni = $existing[$s['id']]['status'] ?? 'hadir';
              $catatanSaatIni = $existing[$s['id']]['catatan'] ?? '';
          ?>
            <tr>
              <td><?= esc($s['nama']) ?><div class="text-soft"><?= esc($s['nis']) ?></div></td>
              <td>
                <div class="status-pills" data-siswa="<?= esc($s['id']) ?>">
                  <?php foreach (['hadir' => 'Hadir', 'sakit' => 'Sakit', 'izin' => 'Izin', 'terlambat' => 'Terlambat', 'alpha' => 'Alpha'] as $val => $label) : ?>
                    <label class="pill pill-<?= $val ?>">
                      <input type="radio" name="status[<?= esc($s['id']) ?>]" value="<?= $val ?>" <?= $statusSaatIni === $val ? 'checked' : '' ?>>
                      <span><?= $label ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
              </td>
              <td>
                <input type="text" name="catatan[<?= esc($s['id']) ?>]" value="<?= esc($catatanSaatIni) ?>" placeholder="-" class="catatan-input">
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if (! empty($siswa)) : ?>
    <div style="margin-top:20px;display:flex;justify-content:flex-end">
      <button type="submit" class="btn btn-primary">Simpan &amp; lanjut ke jurnal <svg class="icon-sm" style="stroke:#fff"><use href="#i-chevron-right"/></svg></button>
    </div>
  <?php endif; ?>
</form>

<style>
  .status-pills { display: flex; gap: 6px; flex-wrap: wrap; }
  .pill { display: inline-flex; align-items: center; gap: 5px; padding: 5px 11px; border-radius: 999px; border: 1px solid var(--color-border); cursor: pointer; font-size: 12.5px; font-weight: 500; color: var(--color-text-muted); }
  .pill input { position: absolute; opacity: 0; width: 0; height: 0; }
  .pill-hadir.is-checked { background: var(--color-success-soft); border-color: var(--color-success); color: var(--color-success); }
  .pill-sakit.is-checked { background: var(--color-info-soft); border-color: var(--color-info); color: var(--color-info); }
  .pill-izin.is-checked, .pill-terlambat.is-checked { background: var(--color-warning-soft); border-color: var(--color-warning); color: var(--color-warning); }
  .pill-alpha.is-checked { background: var(--color-danger-soft); border-color: var(--color-danger); color: var(--color-danger); }
  .catatan-input { width: 100%; min-width: 140px; padding: 7px 10px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-size: 13px; font-family: inherit; }
</style>

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
