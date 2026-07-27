<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-calendar"/></svg> Hari libur</h1>
  <p class="text-muted">Kalender akademik — tanggal libur sekolah, tidak dihitung sebagai hari mengajar.</p>
</div>

<div class="toolbar">
  <input type="text" class="toolbar-search" placeholder="Cari keterangan..." oninput="filterTable(this.value, 'tabelLibur')">
  <button type="button" class="btn btn-primary" onclick="openModal('modalTambah')"><svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah hari libur</button>
</div>

<div class="table-wrap">
  <table class="table" id="tabelLibur">
    <thead><tr><th>Tanggal</th><th>Hari</th><th>Keterangan</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($items)) : ?>
        <tr><td colspan="4"><div class="empty-state"><h3>Belum ada hari libur</h3><p>Tambahkan tanggal libur sekolah, misalnya libur nasional atau libur semester.</p></div></td></tr>
      <?php else : ?>
        <?php
        $hariIndo = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        ?>
        <?php foreach ($items as $row) : ?>
          <tr>
            <td><?= esc(date('d-m-Y', strtotime($row['tanggal']))) ?></td>
            <td><?= esc($hariIndo[date('l', strtotime($row['tanggal']))] ?? '-') ?></td>
            <td><?= esc($row['keterangan']) ?></td>
            <td>
              <div class="row-actions">
                <button type="button" class="btn-icon" onclick="fillEditLibur('<?= esc($row['id'], 'js') ?>','<?= esc($row['tanggal'], 'js') ?>','<?= esc($row['keterangan'], 'js') ?>')">
                  <svg class="icon-sm"><use href="#i-edit"/></svg> Edit
                </button>
                <form method="post" action="<?= base_url('master/hari-libur/delete/' . $row['id']) ?>"
                  onsubmit="return confirm('Hapus hari libur ini?')" style="display:inline">
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
    <h3>Tambah hari libur</h3>
    <form method="post" action="<?= base_url('master/hari-libur/store') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="add_tanggal">Tanggal</label>
        <input type="date" id="add_tanggal" name="tanggal" required>
      </div>
      <div class="form-group">
        <label for="add_keterangan">Keterangan</label>
        <input type="text" id="add_keterangan" name="keterangan" placeholder="Contoh: Libur Hari Kemerdekaan" required maxlength="255">
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
    <h3>Edit hari libur</h3>
    <form method="post" action="<?= base_url('master/hari-libur/update') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="edit_id">
      <div class="form-group">
        <label for="edit_tanggal">Tanggal</label>
        <input type="date" id="edit_tanggal" name="tanggal" required>
      </div>
      <div class="form-group">
        <label for="edit_keterangan">Keterangan</label>
        <input type="text" id="edit_keterangan" name="keterangan" required maxlength="255">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalEdit')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
function fillEditLibur(id, tanggal, keterangan) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_tanggal').value = tanggal;
  document.getElementById('edit_keterangan').value = keterangan;
  openModal('modalEdit');
}
</script>
