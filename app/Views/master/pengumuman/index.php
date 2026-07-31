<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-alert"/></svg> Pengumuman</h1>
  <p class="text-muted">Muncul di widget "Pengumuman" pada Dashboard Admin selama tanggalnya masih berlaku.</p>
</div>

<div class="toolbar">
  <input type="text" class="toolbar-search" placeholder="Cari judul..." oninput="filterTable(this.value, 'tabelPengumuman')">
  <button type="button" class="btn btn-primary" onclick="openModal('modalTambah')"><svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah pengumuman</button>
</div>

<div class="table-wrap table-responsive-cards">
  <table class="table" id="tabelPengumuman">
    <thead><tr><th style="width:50px">No.</th><th>Judul</th><th>Berlaku</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($items)) : ?>
        <tr><td colspan="5"><div class="empty-state"><h3>Belum ada pengumuman</h3><p>Klik "Tambah pengumuman" — akan otomatis muncul di Dashboard Admin selama masih dalam rentang tanggalnya.</p></div></td></tr>
      <?php else : ?>
        <?php $today = date('Y-m-d'); ?>
        <?php foreach ($items as $i => $row) :
          $aktif = $row['tanggal_mulai'] <= $today && (! $row['tanggal_selesai'] || $row['tanggal_selesai'] >= $today);
        ?>
          <tr>
            <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
            <td class="td-card-title"><?= esc($row['judul']) ?></td>
            <td data-label="Berlaku">
              <?= esc(date('d-m-Y', strtotime($row['tanggal_mulai']))) ?>
              <?= $row['tanggal_selesai'] ? ' s/d ' . esc(date('d-m-Y', strtotime($row['tanggal_selesai']))) : ' (tanpa batas akhir)' ?>
            </td>
            <td data-label="Status">
              <?php if ($aktif) : ?>
                <span class="status-badge status-hadir">Aktif tampil</span>
              <?php else : ?>
                <span class="text-soft"><?= $row['tanggal_mulai'] > $today ? 'Belum mulai' : 'Sudah berakhir' ?></span>
              <?php endif; ?>
            </td>
            <td class="td-card-actions" data-label="">
              <div class="row-actions">
                <button type="button" class="btn-icon" onclick='fillEditPengumuman(<?= json_encode($row) ?>)'>
                  <svg class="icon-sm"><use href="#i-edit"/></svg> Edit
                </button>
                <form method="post" action="<?= base_url('master/pengumuman/delete/' . $row['id']) ?>"
                  onsubmit="return confirm('Hapus pengumuman &quot;<?= esc($row['judul'], 'js') ?>&quot;?')" style="display:inline">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn-icon btn-icon-danger"><svg class="icon-sm"><use href="#i-trash"/></svg> Hapus</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal: Tambah -->
<div class="modal" id="modalTambah">
  <div class="modal-box">
    <h3>Tambah pengumuman</h3>
    <form method="post" action="<?= base_url('master/pengumuman/store') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="add_judul">Judul</label>
        <input type="text" id="add_judul" name="judul" placeholder="Contoh: Libur Akhir Semester" required maxlength="200">
      </div>
      <div class="form-group">
        <label for="add_isi">Isi (opsional)</label>
        <textarea id="add_isi" name="isi" placeholder="Detail pengumuman..."></textarea>
      </div>
      <div class="form-row" style="grid-template-columns:1fr 1fr">
        <div class="form-group">
          <label for="add_mulai">Mulai tampil</label>
          <input type="date" id="add_mulai" name="tanggal_mulai" required>
        </div>
        <div class="form-group">
          <label for="add_selesai">Sampai (opsional)</label>
          <input type="date" id="add_selesai" name="tanggal_selesai">
          <p class="form-hint">Kosongkan kalau tidak ada batas akhir.</p>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalTambah')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit -->
<div class="modal" id="modalEdit">
  <div class="modal-box">
    <h3>Edit pengumuman</h3>
    <form method="post" action="<?= base_url('master/pengumuman/update') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="edit_id">
      <div class="form-group">
        <label for="edit_judul">Judul</label>
        <input type="text" id="edit_judul" name="judul" required maxlength="200">
      </div>
      <div class="form-group">
        <label for="edit_isi">Isi (opsional)</label>
        <textarea id="edit_isi" name="isi"></textarea>
      </div>
      <div class="form-row" style="grid-template-columns:1fr 1fr">
        <div class="form-group">
          <label for="edit_mulai">Mulai tampil</label>
          <input type="date" id="edit_mulai" name="tanggal_mulai" required>
        </div>
        <div class="form-group">
          <label for="edit_selesai">Sampai (opsional)</label>
          <input type="date" id="edit_selesai" name="tanggal_selesai">
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalEdit')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
function fillEditPengumuman(row) {
  document.getElementById('edit_id').value = row.id;
  document.getElementById('edit_judul').value = row.judul;
  document.getElementById('edit_isi').value = row.isi || '';
  document.getElementById('edit_mulai').value = row.tanggal_mulai;
  document.getElementById('edit_selesai').value = row.tanggal_selesai || '';
  openModal('modalEdit');
}
</script>
