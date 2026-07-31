<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-history"/></svg> Tukar jadwal</h1>
  <p class="text-muted">
    Menukar SELURUH slot (hari &amp; jam) mengajar dengan guru lain untuk rentang tanggal
    tertentu — bukan sekadar cari pengganti. Jadwal master tidak pernah diubah; perlu disetujui
    guru tujuan dan Admin/Waka Kurikulum sebelum aktif.
    <br>Cuma perlu seseorang meng-cover satu sesi karena berhalangan hadir? Pakai menu
    <a href="<?= base_url('tukar-jadwal') ?>">Cari Guru Pengganti</a>.
  </p>
</div>

<?php if (empty($aktif)) : ?>
  <div class="alert alert-danger"><svg class="icon-sm"><use href="#i-alert"/></svg> Belum ada semester aktif.</div>
<?php elseif (empty($jadwalSaya)) : ?>
  <div class="empty-state"><h3>Anda belum punya jadwal mengajar</h3></div>
<?php else : ?>
  <div class="card" style="margin-bottom:24px">
    <div class="card-title" style="margin-bottom:12px">Ajukan pertukaran jadwal</div>
    <form method="post" action="<?= base_url('jadwal-swap/ajukan') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="jadwal_asal_id">Jadwal saya yang ditukar</label>
        <select id="jadwal_asal_id" name="jadwal_asal_id" required onchange="hitungJP()">
          <option value="">-- pilih salah satu jadwal Anda --</option>
          <?php foreach ($jadwalSaya as $j) :
            $jp = (int) $j['jam_ke_selesai'] - (int) $j['jam_ke_mulai'] + 1;
          ?>
            <option value="<?= esc($j['id']) ?>" data-jp="<?= esc($jp) ?>">
              <?= esc($j['hari']) ?>, <?= esc(substr($j['jam_mulai'], 0, 5)) ?>–<?= esc(substr($j['jam_selesai'], 0, 5)) ?> —
              <?= esc($j['nama_kelas']) ?> (<?= esc($j['nama_mapel']) ?>) — <?= esc($jp) ?> JP
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="jadwal_tujuan_id">Ditukar dengan jadwal guru lain</label>
        <select id="jadwal_tujuan_id" name="jadwal_tujuan_id" required onchange="hitungJP()">
          <option value="">-- pilih jadwal guru lain --</option>
          <?php foreach ($jadwalLain as $j) :
            $jp = (int) $j['jam_ke_selesai'] - (int) $j['jam_ke_mulai'] + 1;
          ?>
            <option value="<?= esc($j['id']) ?>" data-jp="<?= esc($jp) ?>">
              <?= esc($j['nama_guru']) ?> — <?= esc($j['hari']) ?>, <?= esc(substr($j['jam_mulai'], 0, 5)) ?>–<?= esc(substr($j['jam_selesai'], 0, 5)) ?> —
              <?= esc($j['nama_kelas']) ?> (<?= esc($j['nama_mapel']) ?>) — <?= esc($jp) ?> JP
            </option>
          <?php endforeach; ?>
        </select>
        <div class="form-hint" id="hintJP">Jumlah JP kedua jadwal harus sama.</div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="tanggal_mulai">Tanggal mulai berlaku</label>
          <input type="date" id="tanggal_mulai" name="tanggal_mulai" required min="<?= esc(date('Y-m-d')) ?>">
        </div>
        <div class="form-group">
          <label for="tanggal_selesai">Tanggal selesai</label>
          <input type="date" id="tanggal_selesai" name="tanggal_selesai" required min="<?= esc(date('Y-m-d')) ?>">
        </div>
      </div>

      <div class="form-group">
        <label for="alasan">Alasan (opsional)</label>
        <input type="text" id="alasan" name="alasan" maxlength="255" placeholder="Contoh: menyesuaikan jadwal kuliah S2">
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
    <thead><tr><th style="width:50px">No.</th><th>Dari</th><th>Jadwal dia</th><th>Ditukar dengan jadwal Anda</th><th>Periode</th><th style="text-align:right">Respon</th></tr></thead>
    <tbody>
      <?php if (empty($menunggu)) : ?>
        <tr><td colspan="6"><div class="empty-state"><h3>Tidak ada pengajuan masuk</h3></div></td></tr>
      <?php else : ?>
        <?php foreach ($menunggu as $i => $m) : ?>
          <tr>
            <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
            <td class="td-card-title"><?= esc($m['nama_guru_pengaju']) ?><?php if ($m['alasan']) : ?><div class="text-soft" style="font-weight:400;font-size:12px"><?= esc($m['alasan']) ?></div><?php endif; ?></td>
            <td data-label="Jadwal dia"><?= esc($m['hari_asal']) ?>, <?= esc(substr($m['jam_mulai_asal'], 0, 5)) ?>–<?= esc(substr($m['jam_selesai_asal'], 0, 5)) ?><div class="text-soft"><?= esc($m['kelas_asal']) ?> (<?= esc($m['mapel_asal']) ?>)</div></td>
            <td data-label="Ditukar dengan jadwal Anda"><?= esc($m['hari_tujuan']) ?>, <?= esc(substr($m['jam_mulai_tujuan'], 0, 5)) ?>–<?= esc(substr($m['jam_selesai_tujuan'], 0, 5)) ?><div class="text-soft"><?= esc($m['kelas_tujuan']) ?> (<?= esc($m['mapel_tujuan']) ?>)</div></td>
            <td data-label="Periode"><?= esc(date('d-m-Y', strtotime($m['tanggal_mulai']))) ?> s/d <?= esc(date('d-m-Y', strtotime($m['tanggal_selesai']))) ?></td>
            <td class="td-card-actions" data-label="">
              <div class="row-actions">
                <form method="post" action="<?= base_url('jadwal-swap/setuju-guru/' . $m['id']) ?>" style="display:inline">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn-icon" style="color:var(--color-success);border-color:var(--color-success)"><svg class="icon-sm"><use href="#i-check-circle"/></svg> Setuju</button>
                </form>
                <form method="post" action="<?= base_url('jadwal-swap/tolak-guru/' . $m['id']) ?>" style="display:inline" onsubmit="return confirm('Tolak pengajuan ini?')">
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
<?php if (! empty($menunggu)) : ?>
  <p class="text-soft" style="font-size:12.5px;margin-top:-14px">Setelah Anda setuju, pengajuan masih menunggu persetujuan akhir dari Admin/Waka Kurikulum sebelum benar-benar aktif.</p>
<?php endif; ?>
</div>

<div class="section">
  <div class="section-title"><h2>Riwayat pengajuan saya</h2></div>
<div class="table-wrap table-responsive-cards">
  <table class="table">
    <thead><tr><th style="width:50px">No.</th><th>Periode</th><th>Jadwal Anda</th><th>Ditukar dengan</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($riwayat)) : ?>
        <tr><td colspan="6"><div class="empty-state"><h3>Belum ada riwayat</h3></div></td></tr>
      <?php else : ?>
        <?php foreach ($riwayat as $i => $r) : ?>
          <?php $sayaPengaju = (int) $r['guru_pengaju_id'] === (int) $guru['id']; ?>
          <tr>
            <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
            <td class="td-card-title"><?= esc(date('d-m-Y', strtotime($r['tanggal_mulai']))) ?> s/d <?= esc(date('d-m-Y', strtotime($r['tanggal_selesai']))) ?></td>
            <td data-label="Jadwal Anda"><?= esc($sayaPengaju ? $r['hari_asal'] : $r['hari_tujuan']) ?> — <?= esc($sayaPengaju ? $r['kelas_asal'] : $r['kelas_tujuan']) ?> (<?= esc($sayaPengaju ? $r['mapel_asal'] : $r['mapel_tujuan']) ?>)</td>
            <td data-label="Ditukar dengan"><?= esc($sayaPengaju ? $r['nama_guru_penyetuju'] : $r['nama_guru_pengaju']) ?> — <?= esc($sayaPengaju ? $r['hari_tujuan'] : $r['hari_asal']) ?></td>
            <td data-label="Status">
              <?php
                $labelStatus = $r['status'] === 'pending' ? ($r['guru_setuju'] ? 'Menunggu admin' : 'Menunggu guru') : ucfirst($r['status']);
                $warna = ['pending' => 'status-izin', 'disetujui' => 'status-hadir', 'ditolak' => 'status-alpha', 'dibatalkan' => 'status-sakit'];
              ?>
              <span class="status-badge <?= esc($warna[$r['status']] ?? '') ?>"><?= esc($labelStatus) ?></span>
            </td>
            <td class="td-card-actions" data-label="">
              <?php if ($sayaPengaju && $r['status'] === 'pending') : ?>
                <form method="post" action="<?= base_url('jadwal-swap/batal/' . $r['id']) ?>" onsubmit="return confirm('Batalkan pengajuan ini?')">
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
function hitungJP() {
  const asal = document.getElementById('jadwal_asal_id');
  const tujuan = document.getElementById('jadwal_tujuan_id');
  const hint = document.getElementById('hintJP');
  const jpAsal = asal.options[asal.selectedIndex]?.getAttribute('data-jp');
  const jpTujuan = tujuan.options[tujuan.selectedIndex]?.getAttribute('data-jp');

  if (! jpAsal || ! jpTujuan) {
    hint.textContent = 'Jumlah JP kedua jadwal harus sama.';
    hint.style.color = '';
    return;
  }
  if (jpAsal === jpTujuan) {
    hint.textContent = 'Cocok — sama-sama ' + jpAsal + ' JP.';
    hint.style.color = 'var(--color-success)';
  } else {
    hint.textContent = 'Tidak cocok: jadwal Anda ' + jpAsal + ' JP, jadwal tujuan ' + jpTujuan + ' JP. Pengajuan akan ditolak sistem.';
    hint.style.color = 'var(--color-danger)';
  }
}
</script>
