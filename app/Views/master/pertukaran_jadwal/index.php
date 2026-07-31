<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-history"/></svg> Tukar jadwal</h1>
  <p class="text-muted">Persetujuan akhir (Admin/Waka Kurikulum) untuk pertukaran slot jadwal yang sudah di-ACC guru tujuan.</p>
</div>

<div class="section">
  <div class="section-title">
    <svg class="icon"><use href="#i-alert"/></svg>
    <h2>Menunggu persetujuan Anda</h2>
    <?php if (! empty($menunggu)) : ?><span class="role-badge"><?= count($menunggu) ?></span><?php endif; ?>
  </div>
<div class="table-wrap table-responsive-cards" style="margin-bottom:28px">
  <table class="table">
    <thead><tr><th style="width:50px">No.</th><th>Pengaju</th><th>Jadwal asal</th><th>Jadwal tujuan</th><th>Periode</th><th>Alasan</th><th style="text-align:right">Keputusan</th></tr></thead>
    <tbody>
      <?php if (empty($menunggu)) : ?>
        <tr><td colspan="7"><div class="empty-state"><h3>Tidak ada yang menunggu</h3></div></td></tr>
      <?php else : ?>
        <?php foreach ($menunggu as $i => $m) : ?>
          <tr>
            <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
            <td class="td-card-title"><?= esc($m['nama_guru_pengaju']) ?></td>
            <td data-label="Jadwal asal"><?= esc($m['hari_asal']) ?>, <?= esc(substr($m['jam_mulai_asal'], 0, 5)) ?>–<?= esc(substr($m['jam_selesai_asal'], 0, 5)) ?><div class="text-soft"><?= esc($m['kelas_asal']) ?> (<?= esc($m['mapel_asal']) ?>)</div></td>
            <td data-label="Jadwal tujuan"><?= esc($m['nama_guru_penyetuju'] ?? '-') ?> — <?= esc($m['hari_tujuan']) ?>, <?= esc(substr($m['jam_mulai_tujuan'], 0, 5)) ?>–<?= esc(substr($m['jam_selesai_tujuan'], 0, 5)) ?><div class="text-soft"><?= esc($m['kelas_tujuan']) ?> (<?= esc($m['mapel_tujuan']) ?>)</div></td>
            <td data-label="Periode"><?= esc(date('d-m-Y', strtotime($m['tanggal_mulai']))) ?> s/d <?= esc(date('d-m-Y', strtotime($m['tanggal_selesai']))) ?></td>
            <td data-label="Alasan"><?= $m['alasan'] ? esc($m['alasan']) : '<span class="text-soft">-</span>' ?></td>
            <td class="td-card-actions" data-label="">
              <div class="row-actions">
                <form method="post" action="<?= base_url('master/pertukaran-jadwal/setuju/' . $m['id']) ?>" style="display:inline">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn-icon" style="color:var(--color-success);border-color:var(--color-success)"><svg class="icon-sm"><use href="#i-check-circle"/></svg> Setuju</button>
                </form>
                <form method="post" action="<?= base_url('master/pertukaran-jadwal/tolak/' . $m['id']) ?>" style="display:inline" onsubmit="return confirm('Tolak pengajuan ini?')">
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
  <div class="section-title"><h2>Semua pengajuan</h2></div>
  <form method="get" action="<?= base_url('master/pertukaran-jadwal') ?>" class="toolbar">
    <select name="status" onchange="this.form.submit()" style="max-width:220px">
      <option value="">Semua status</option>
      <?php foreach (['pending', 'disetujui', 'ditolak', 'dibatalkan'] as $s) : ?>
        <option value="<?= esc($s) ?>" <?= $filter['status'] === $s ? 'selected' : '' ?>><?= esc(ucfirst($s)) ?></option>
      <?php endforeach; ?>
    </select>
  </form>

  <div class="table-wrap table-responsive-cards">
    <table class="table">
      <thead><tr><th style="width:50px">No.</th><th>Pengaju</th><th>Guru tujuan</th><th>Periode</th><th>Status</th><th>Disetujui oleh</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($semua)) : ?>
        <tr><td colspan="7"><div class="empty-state"><h3>Tidak ada data</h3></div></td></tr>
      <?php else : ?>
        <?php foreach ($semua as $i => $s) : ?>
          <tr>
            <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
            <td class="td-card-title"><?= esc($s['nama_guru_pengaju']) ?><div class="text-soft" style="font-weight:400;font-size:12px"><?= esc($s['hari_asal']) ?>, <?= esc($s['kelas_asal']) ?> (<?= esc($s['mapel_asal']) ?>)</div></td>
            <td data-label="Guru tujuan"><?= esc($s['nama_guru_penyetuju']) ?><div class="text-soft"><?= esc($s['hari_tujuan']) ?>, <?= esc($s['kelas_tujuan']) ?> (<?= esc($s['mapel_tujuan']) ?>)</div></td>
            <td data-label="Periode"><?= esc(date('d-m-Y', strtotime($s['tanggal_mulai']))) ?> s/d <?= esc(date('d-m-Y', strtotime($s['tanggal_selesai']))) ?></td>
            <td data-label="Status">
              <?php
                $labelStatus = $s['status'] === 'pending' ? ($s['guru_setuju'] ? 'Menunggu admin' : 'Menunggu guru') : ucfirst($s['status']);
                $warna = ['pending' => 'status-izin', 'disetujui' => 'status-hadir', 'ditolak' => 'status-alpha', 'dibatalkan' => 'status-sakit'];
              ?>
              <span class="status-badge <?= esc($warna[$s['status']] ?? '') ?>"><?= esc($labelStatus) ?></span>
            </td>
            <td data-label="Disetujui oleh"><?= $s['approved_at'] ? esc(date('d-m-Y H:i', strtotime($s['approved_at']))) : '<span class="text-soft">-</span>' ?></td>
            <td class="td-card-actions" data-label="">
              <?php if (in_array($s['status'], ['pending', 'disetujui'], true)) : ?>
                <form method="post" action="<?= base_url('master/pertukaran-jadwal/batalkan/' . $s['id']) ?>"
                  onsubmit="return confirm('Batalkan pertukaran jadwal ini sebagai admin?')" style="text-align:right">
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
