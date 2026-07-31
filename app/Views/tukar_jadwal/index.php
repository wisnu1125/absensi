<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-users"/></svg> Cari guru pengganti</h1>
  <p class="text-muted">
    Minta satu guru menggantikan Anda mengajar SATU sesi tertentu (misalnya karena berhalangan
    hadir). Jadwal Anda sendiri tidak berubah — minggu berikutnya otomatis normal kembali.
    <br>Butuh menukar seluruh slot jadwal (hari &amp; jam) dengan guru lain? Pakai menu
    <a href="<?= base_url('jadwal-swap') ?>">Tukar Jadwal</a>.
  </p>
</div>

<?php if (empty($aktif)) : ?>
  <div class="alert alert-danger"><svg class="icon-sm"><use href="#i-alert"/></svg> Belum ada semester aktif.</div>
<?php elseif (empty($jadwalSaya)) : ?>
  <div class="empty-state"><h3>Anda belum punya jadwal mengajar</h3></div>
<?php else : ?>
  <div class="card" style="margin-bottom:24px">
    <div class="card-title" style="margin-bottom:12px">Ajukan guru pengganti</div>
    <form method="post" action="<?= base_url('tukar-jadwal/ajukan') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="jadwal_id">Jadwal saya</label>
        <select id="jadwal_id" name="jadwal_id" required onchange="perbaruiForm()">
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
          <label for="tanggal">Tanggal</label>
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

      <div class="form-group" id="blokKetersediaan" style="display:none">
        <label style="display:flex;align-items:center;gap:6px;font-size:12.5px;font-weight:600">
          <svg class="icon-sm"><use href="#i-users"/></svg> Guru lain yang sibuk di jam ini
        </label>
        <div class="table-wrap">
          <table class="table" style="font-size:12px">
            <thead><tr><th>Guru</th><th>Sedang mengajar</th></tr></thead>
            <tbody id="isiKetersediaan"></tbody>
          </table>
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

<div class="section">
  <div class="section-title">
    <svg class="icon"><use href="#i-alert"/></svg>
    <h2>Menunggu respon Anda</h2>
    <?php if (! empty($menunggu)) : ?><span class="role-badge"><?= count($menunggu) ?></span><?php endif; ?>
  </div>
<div class="table-wrap table-responsive-cards" style="margin-bottom:24px">
  <table class="table">
    <thead><tr><th style="width:50px">No.</th><th>Dari</th><th>Sesi</th><th>Tanggal</th><th>Alasan</th><th style="text-align:right">Respon</th></tr></thead>
    <tbody>
      <?php if (empty($menunggu)) : ?>
        <tr><td colspan="6"><div class="empty-state"><h3>Tidak ada pengajuan masuk</h3></div></td></tr>
      <?php else : ?>
        <?php foreach ($menunggu as $i => $m) : ?>
          <tr>
            <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
            <td class="td-card-title"><?= esc($m['nama_guru_asal']) ?></td>
            <td data-label="Sesi"><?= esc($m['nama_kelas']) ?> — <?= esc($m['nama_mapel']) ?><div class="text-soft"><?= esc($m['hari']) ?>, <?= esc(substr($m['jam_mulai'], 0, 5)) ?>–<?= esc(substr($m['jam_selesai'], 0, 5)) ?></div></td>
            <td data-label="Tanggal"><?= esc(date('d-m-Y', strtotime($m['tanggal']))) ?></td>
            <td data-label="Alasan"><?= $m['alasan'] ? esc($m['alasan']) : '<span class="text-soft">-</span>' ?></td>
            <td class="td-card-actions" data-label="">
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
</div>

<div class="section">
  <div class="section-title"><h2>Riwayat pengajuan saya</h2></div>
<div class="table-wrap table-responsive-cards">
  <table class="table">
    <thead><tr><th style="width:50px">No.</th><th>Tanggal</th><th>Sesi</th><th>Arah</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($riwayat)) : ?>
        <tr><td colspan="6"><div class="empty-state"><h3>Belum ada riwayat</h3></div></td></tr>
      <?php else : ?>
        <?php foreach ($riwayat as $i => $r) : ?>
          <?php $sayaAsal = (int) $r['guru_asal_id'] === (int) $guru['id']; ?>
          <tr>
            <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
            <td class="td-card-title"><?= esc(date('d-m-Y', strtotime($r['tanggal']))) ?></td>
            <td data-label="Sesi"><?= esc($r['nama_kelas']) ?> — <?= esc($r['nama_mapel']) ?><div class="text-soft"><?= esc($r['hari']) ?>, <?= esc(substr($r['jam_mulai'], 0, 5)) ?>–<?= esc(substr($r['jam_selesai'], 0, 5)) ?></div></td>
            <td data-label="Arah"><?= $sayaAsal ? 'Ke ' . esc($r['nama_guru_pengganti']) : 'Dari ' . esc($r['nama_guru_asal']) ?></td>
            <td data-label="Status">
              <?php $warna = ['menunggu' => 'status-izin', 'disetujui' => 'status-hadir', 'ditolak' => 'status-alpha', 'dibatalkan' => 'status-sakit']; ?>
              <span class="status-badge <?= esc($warna[$r['status']] ?? '') ?>"><?= esc(ucfirst($r['status'])) ?></span>
            </td>
            <td class="td-card-actions" data-label="">
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
</div>

<script>
const dataKetersediaan = <?= json_encode($ketersediaan) ?>;

function perbaruiForm() {
  const select = document.getElementById('jadwal_id');
  const opt = select.options[select.selectedIndex];
  const hari = opt ? opt.getAttribute('data-hari') : null;
  const jadwalId = select.value;

  document.getElementById('hariHint').textContent = hari
    ? 'Tanggal yang dipilih harus jatuh pada hari ' + hari + '.'
    : 'Pilih jadwal dulu untuk tahu hari yang sesuai.';

  const blok = document.getElementById('blokKetersediaan');
  const isi = document.getElementById('isiKetersediaan');

  if (! jadwalId || ! dataKetersediaan[jadwalId]) {
    blok.style.display = 'none';
    return;
  }

  const daftar = dataKetersediaan[jadwalId];
  isi.innerHTML = '';
  if (daftar.length === 0) {
    isi.innerHTML = '<tr><td colspan="2" class="text-soft">Tidak ada guru lain yang tercatat mengajar di jam ini.</td></tr>';
  } else {
    daftar.forEach(function (d) {
      const tr = document.createElement('tr');
      tr.innerHTML = '<td>' + d.nama_guru + '</td><td>' + d.nama_kelas + ' — ' + d.nama_mapel + '</td>';
      isi.appendChild(tr);
    });
  }
  blok.style.display = 'block';
}
</script>
