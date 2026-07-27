<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-history"/></svg> Tukar jadwal</h1>
  <p class="text-muted">Ajukan guru pengganti untuk satu sesi tertentu. Jadwal asli Anda tidak berubah — minggu berikutnya otomatis kembali seperti semula.</p>
</div>

<?php if (empty($aktif)) : ?>
  <div class="alert alert-danger"><svg class="icon-sm"><use href="#i-alert"/></svg> Belum ada semester aktif.</div>
<?php elseif (empty($jadwalSaya)) : ?>
  <div class="empty-state"><h3>Anda belum punya jadwal mengajar</h3></div>
<?php else : ?>
  <div class="card" style="margin-bottom:24px">
    <div class="card-title" style="margin-bottom:12px">Ajukan tukar jadwal</div>
    <form method="post" action="<?= base_url('tukar-jadwal/ajukan') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="jadwal_id">Jadwal saya</label>
        <select id="jadwal_id" name="jadwal_id" required>
          <option value="">-- pilih salah satu jadwal Anda --</option>
          <?php foreach ($jadwalSaya as $j) : ?>
            <option value="<?= esc($j['id']) ?>" data-hari="<?= esc($j['hari']) ?>">
              <?= esc($j['hari']) ?>, <?= esc(substr($j['jam_mulai'], 0, 5)) ?>–<?= esc(substr($j['jam_selesai'], 0, 5)) ?> —
              <?= esc($j['nama_kelas']) ?> (<?= esc($j['nama_mapel']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="tanggal">Tanggal sesi yang ditukar</label>
          <input type="date" id="tanggal" name="tanggal" required min="<?= esc(date('Y-m-d')) ?>">
          <div class="form-hint" id="hariHint">Pilih jadwal dulu untuk tahu hari yang sesuai.</div>
        </div>
        <div class="form-group">
          <label for="guru_pengganti_id">Guru pengganti</label>
          <select id="guru_pengganti_id" name="guru_pengganti_id" required>
            <option value="">-- pilih guru --</option>
            <?php foreach ($guruLain as $g) : ?>
              <option value="<?= esc($g['id']) ?>"><?= esc($g['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label for="alasan">Alasan (opsional)</label>
        <input type="text" id="alasan" name="alasan" maxlength="255" placeholder="Contoh: ada tugas dinas luar">
      </div>
      <button type="submit" class="btn btn-primary"><svg class="icon-sm" style="stroke:#fff"><use href="#i-check-circle"/></svg> Kirim pengajuan</button>
    </form>
  </div>
<?php endif; ?>

<h2 style="display:flex;align-items:center;gap:8px">
  <svg class="icon"><use href="#i-alert"/></svg> Menunggu respon Anda
  <?php if (! empty($menunggu)) : ?><span class="role-badge"><?= count($menunggu) ?></span><?php endif; ?>
</h2>

<div class="table-wrap" style="margin-bottom:24px">
  <table class="table">
    <thead><tr><th>Dari</th><th>Sesi</th><th>Tanggal</th><th>Alasan</th><th style="text-align:right">Respon</th></tr></thead>
    <tbody>
      <?php if (empty($menunggu)) : ?>
        <tr><td colspan="5"><div class="empty-state"><h3>Tidak ada pengajuan masuk</h3></div></td></tr>
      <?php else : ?>
        <?php foreach ($menunggu as $m) : ?>
          <tr>
            <td><?= esc($m['nama_guru_asal']) ?></td>
            <td><?= esc($m['nama_kelas']) ?> — <?= esc($m['nama_mapel']) ?><div class="text-soft"><?= esc($m['hari']) ?>, <?= esc(substr($m['jam_mulai'], 0, 5)) ?>–<?= esc(substr($m['jam_selesai'], 0, 5)) ?></div></td>
            <td><?= esc(date('d-m-Y', strtotime($m['tanggal']))) ?></td>
            <td><?= $m['alasan'] ? esc($m['alasan']) : '<span class="text-soft">-</span>' ?></td>
            <td>
              <div class="row-actions">
                <form method="post" action="<?= base_url('tukar-jadwal/setuju/' . $m['id']) ?>" style="display:inline">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn-icon" style="color:var(--color-success);border-color:var(--color-success)"><svg class="icon-sm"><use href="#i-check-circle"/></svg> Setuju</button>
                </form>
                <form method="post" action="<?= base_url('tukar-jadwal/tolak/' . $m['id']) ?>" style="display:inline" onsubmit="return confirm('Tolak pengajuan ini?')">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn-icon btn-icon-danger">Tolak</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<h2>Riwayat pengajuan saya</h2>
<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Tanggal</th><th>Sesi</th><th>Arah</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($riwayat)) : ?>
        <tr><td colspan="5"><div class="empty-state"><h3>Belum ada riwayat</h3></div></td></tr>
      <?php else : ?>
        <?php foreach ($riwayat as $r) : ?>
          <?php $sayaAsal = (int) $r['guru_asal_id'] === (int) $guru['id']; ?>
          <tr>
            <td><?= esc(date('d-m-Y', strtotime($r['tanggal']))) ?></td>
            <td><?= esc($r['nama_kelas']) ?> — <?= esc($r['nama_mapel']) ?><div class="text-soft"><?= esc($r['hari']) ?>, <?= esc(substr($r['jam_mulai'], 0, 5)) ?>–<?= esc(substr($r['jam_selesai'], 0, 5)) ?></div></td>
            <td><?= $sayaAsal ? 'Ke ' . esc($r['nama_guru_pengganti']) : 'Dari ' . esc($r['nama_guru_asal']) ?></td>
            <td>
              <?php
                $warna = ['menunggu' => 'status-izin', 'disetujui' => 'status-hadir', 'ditolak' => 'status-alpha', 'dibatalkan' => 'status-sakit'];
              ?>
              <span class="status-badge <?= esc($warna[$r['status']] ?? '') ?>"><?= esc(ucfirst($r['status'])) ?></span>
            </td>
            <td>
              <?php if ($sayaAsal && $r['status'] === 'menunggu') : ?>
                <form method="post" action="<?= base_url('tukar-jadwal/batal/' . $r['id']) ?>" onsubmit="return confirm('Batalkan pengajuan ini?')">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn-icon btn-icon-danger">Batalkan</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
document.getElementById('jadwal_id')?.addEventListener('change', function () {
  const opt = this.options[this.selectedIndex];
  const hari = opt.getAttribute('data-hari');
  document.getElementById('hariHint').textContent = hari
    ? 'Tanggal yang dipilih harus jatuh pada hari ' + hari + '.'
    : 'Pilih jadwal dulu untuk tahu hari yang sesuai.';
});
</script>
