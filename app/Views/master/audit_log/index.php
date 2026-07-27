<?php
$qs = array_filter($filter, static fn ($v) => $v !== '');
$halamanIni  = $pager->getCurrentPage();
$totalHalaman = max(1, $pager->getPageCount());
$urlHalaman  = static fn (int $p) => base_url('master/audit-log?' . http_build_query(array_merge($qs, ['page' => $p])));
?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-history"/></svg> Audit log</h1>
  <p class="text-muted">Riwayat aktivitas penting: login/logout, tambah/ubah/hapus data, di seluruh aplikasi.</p>
</div>

<form method="get" action="<?= base_url('master/audit-log') ?>" class="card" style="margin-bottom:20px">
  <div class="form-row">
    <div class="form-group">
      <label for="tanggal_dari">Tanggal dari</label>
      <input type="date" id="tanggal_dari" name="tanggal_dari" value="<?= esc($filter['tanggal_dari']) ?>">
    </div>
    <div class="form-group">
      <label for="tanggal_sampai">Tanggal sampai</label>
      <input type="date" id="tanggal_sampai" name="tanggal_sampai" value="<?= esc($filter['tanggal_sampai']) ?>">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label for="user_id">Pengguna</label>
      <select id="user_id" name="user_id">
        <option value="">Semua pengguna</option>
        <?php foreach ($users as $u) : ?>
          <option value="<?= esc($u['id']) ?>" <?= (string) $filter['user_id'] === (string) $u['id'] ? 'selected' : '' ?>><?= esc($u['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="aktivitas">Jenis aktivitas</label>
      <select id="aktivitas" name="aktivitas">
        <option value="">Semua aktivitas</option>
        <?php foreach ($aktivitasList as $a) : ?>
          <option value="<?= esc($a) ?>" <?= $filter['aktivitas'] === $a ? 'selected' : '' ?>><?= esc(ucwords(str_replace('_', ' ', $a))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="cari">Cari keterangan / nama</label>
      <input type="text" id="cari" name="cari" value="<?= esc($filter['cari']) ?>" placeholder="Contoh: VII A">
    </div>
  </div>
  <button type="submit" class="btn btn-primary"><svg class="icon-sm" style="stroke:#fff"><use href="#i-search"/></svg> Terapkan filter</button>
</form>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aktivitas</th><th>Keterangan</th><th>IP Address</th></tr></thead>
    <tbody>
      <?php if (empty($rows)) : ?>
        <tr><td colspan="5"><div class="empty-state"><h3>Tidak ada aktivitas</h3><p>Coba ubah rentang tanggal atau filter lainnya.</p></div></td></tr>
      <?php else : ?>
        <?php foreach ($rows as $r) : ?>
          <tr>
            <td class="text-soft" style="white-space:nowrap"><?= esc(date('d-m-Y H:i', strtotime($r['created_at']))) ?></td>
            <td><?= $r['full_name'] ? esc($r['full_name']) : '<span class="text-soft">(tidak diketahui)</span>' ?></td>
            <td><span class="role-badge"><?= esc(ucwords(str_replace('_', ' ', $r['aktivitas']))) ?></span></td>
            <td><?= esc($r['keterangan'] ?? '-') ?></td>
            <td class="text-soft"><?= esc($r['ip_address'] ?? '-') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($totalHalaman > 1) : ?>
  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px">
    <span class="text-soft">Halaman <?= esc($halamanIni) ?> dari <?= esc($totalHalaman) ?> &middot; <?= esc($pager->getTotal()) ?> catatan</span>
    <div style="display:flex;gap:8px">
      <?php if ($halamanIni > 1) : ?>
        <a href="<?= $urlHalaman($halamanIni - 1) ?>" class="btn btn-outline btn-sm">&larr; Sebelumnya</a>
      <?php endif; ?>
      <?php if ($halamanIni < $totalHalaman) : ?>
        <a href="<?= $urlHalaman($halamanIni + 1) ?>" class="btn btn-outline btn-sm">Berikutnya &rarr;</a>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
