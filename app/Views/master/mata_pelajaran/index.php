<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-book"/></svg> Mata pelajaran</h1>
  <p class="text-muted">Kelola daftar mata pelajaran yang dipakai di jadwal mengajar.</p>
</div>

<div class="toolbar">
  <input type="text" class="toolbar-search" placeholder="Cari mata pelajaran..." oninput="filterTable(this.value, 'tabelMapel')">
  <button type="button" class="btn btn-primary" onclick="openModal('modalTambah')"><svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah mata pelajaran</button>
</div>

<div class="table-wrap">
  <table class="table" id="tabelMapel">
    <thead>
      <tr>
        <th>Kode</th>
        <th>Nama mata pelajaran</th>
        <th style="text-align:right">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($items)) : ?>
        <tr><td colspan="3">
          <div class="empty-state">
            <h3>Belum ada mata pelajaran</h3>
            <p>Klik "Tambah mata pelajaran" untuk menambahkan data pertama.</p>
          </div>
        </td></tr>
      <?php else : ?>
        <?php foreach ($items as $row) : ?>
          <tr>
            <td><?= esc($row['kode']) ?></td>
            <td><?= esc($row['nama']) ?></td>
            <td>
              <div class="row-actions">
                <button type="button" class="btn-icon"
                  onclick="fillEditMapel('<?= esc($row['id'], 'js') ?>','<?= esc($row['kode'], 'js') ?>','<?= esc($row['nama'], 'js') ?>')">
                  <svg class="icon-sm"><use href="#i-edit"/></svg> Edit
                </button>
                <form method="post" action="<?= base_url('master/mata-pelajaran/delete/' . $row['id']) ?>"
                  onsubmit="return confirm('Hapus mata pelajaran &quot;<?= esc($row['nama'], 'js') ?>&quot;?')" style="display:inline">
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
    <h3>Tambah mata pelajaran</h3>
    <p class="text-muted" style="font-size:13px">Isi kode singkat dan nama lengkap mata pelajaran.</p>
    <form method="post" action="<?= base_url('master/mata-pelajaran/store') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="add_kode">Kode</label>
        <input type="text" id="add_kode" name="kode" placeholder="Contoh: MTK" required maxlength="20">
      </div>
      <div class="form-group">
        <label for="add_nama">Nama mata pelajaran</label>
        <input type="text" id="add_nama" name="nama" placeholder="Contoh: Matematika" required maxlength="100">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalTambah')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit (satu modal dipakai ulang untuk semua baris) -->
<div class="modal" id="modalEdit">
  <div class="modal-box">
    <h3>Edit mata pelajaran</h3>
    <form method="post" action="<?= base_url('master/mata-pelajaran/update') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="edit_id">
      <div class="form-group">
        <label for="edit_kode">Kode</label>
        <input type="text" id="edit_kode" name="kode" required maxlength="20">
      </div>
      <div class="form-group">
        <label for="edit_nama">Nama mata pelajaran</label>
        <input type="text" id="edit_nama" name="nama" required maxlength="100">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalEdit')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
function fillEditMapel(id, kode, nama) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_kode').value = kode;
  document.getElementById('edit_nama').value = nama;
  openModal('modalEdit');
}
</script>
