<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-building"/></svg> Kelas</h1>
  <p class="text-muted">Daftar kelas per tahun ajaran, lengkap dengan wali kelasnya.</p>
</div>

<?php if (empty($waliKelas)) : ?>
  <div class="alert alert-danger">
    <svg class="icon-sm"><use href="#i-alert"/></svg>
    Belum ada guru dengan role Wali Kelas, jadi dropdown wali kelas di bawah masih kosong.
    Beri role itu dulu lewat menu <a href="<?= base_url('master/guru') ?>">Guru</a>.
  </div>
<?php endif; ?>

<div class="toolbar">
  <input type="text" class="toolbar-search" placeholder="Cari kelas..." oninput="filterTable(this.value, 'tabelKelas')">
  <button type="button" class="btn btn-primary" onclick="openModal('modalTambah')"><svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah kelas</button>
</div>

<div class="table-wrap">
  <table class="table" id="tabelKelas">
    <thead><tr><th>Nama kelas</th><th>Tingkat</th><th>Tahun ajaran</th><th>Wali kelas</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($items)) : ?>
        <tr><td colspan="5"><div class="empty-state"><h3>Belum ada kelas</h3><p>Klik "Tambah kelas" untuk menambahkan data pertama.</p></div></td></tr>
      <?php else : ?>
        <?php foreach ($items as $row) : ?>
          <tr>
            <td><?= esc($row['nama_kelas']) ?></td>
            <td><?= esc($row['tingkat']) ?></td>
            <td><?= esc($row['nama_tahun_ajaran']) ?></td>
            <td><?= $row['nama_wali_kelas'] ? esc($row['nama_wali_kelas']) : '<span class="text-soft">Belum ada</span>' ?></td>
            <td>
              <div class="row-actions">
                <button type="button" class="btn-icon" onclick='fillEditKelas(<?= json_encode($row) ?>)'>
                  <svg class="icon-sm"><use href="#i-edit"/></svg> Edit
                </button>
                <form method="post" action="<?= base_url('master/kelas/delete/' . $row['id']) ?>"
                  onsubmit="return confirm('Hapus kelas &quot;<?= esc($row['nama_kelas'], 'js') ?>&quot;?')" style="display:inline">
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
    <h3>Tambah kelas</h3>
    <form method="post" action="<?= base_url('master/kelas/store') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="add_ta">Tahun ajaran</label>
        <select id="add_ta" name="tahun_ajaran_id" required>
          <option value="">-- pilih tahun ajaran --</option>
          <?php foreach ($tahunAjaran as $ta) : ?>
            <option value="<?= esc($ta['id']) ?>"><?= esc($ta['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="add_nama_kelas">Nama kelas</label>
          <input type="text" id="add_nama_kelas" name="nama_kelas" placeholder="Contoh: VII A" required maxlength="50">
        </div>
        <div class="form-group">
          <label for="add_tingkat">Tingkat</label>
          <input type="text" id="add_tingkat" name="tingkat" placeholder="Contoh: VII" required maxlength="10">
        </div>
      </div>
      <div class="form-group">
        <label for="add_wali">Wali kelas (opsional)</label>
        <select id="add_wali" name="wali_kelas_id">
          <option value="">-- belum ditentukan --</option>
          <?php foreach ($waliKelas as $w) : ?>
            <option value="<?= esc($w['id']) ?>"><?= esc($w['nama']) ?></option>
          <?php endforeach; ?>
        </select>
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
    <h3>Edit kelas</h3>
    <form method="post" action="<?= base_url('master/kelas/update') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="edit_id">
      <div class="form-group">
        <label for="edit_ta">Tahun ajaran</label>
        <select id="edit_ta" name="tahun_ajaran_id" required>
          <?php foreach ($tahunAjaran as $ta) : ?>
            <option value="<?= esc($ta['id']) ?>"><?= esc($ta['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="edit_nama_kelas">Nama kelas</label>
          <input type="text" id="edit_nama_kelas" name="nama_kelas" required maxlength="50">
        </div>
        <div class="form-group">
          <label for="edit_tingkat">Tingkat</label>
          <input type="text" id="edit_tingkat" name="tingkat" required maxlength="10">
        </div>
      </div>
      <div class="form-group">
        <label for="edit_wali">Wali kelas (opsional)</label>
        <select id="edit_wali" name="wali_kelas_id">
          <option value="">-- belum ditentukan --</option>
          <?php foreach ($waliKelas as $w) : ?>
            <option value="<?= esc($w['id']) ?>"><?= esc($w['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalEdit')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
function fillEditKelas(k) {
  document.getElementById('edit_id').value = k.id;
  document.getElementById('edit_ta').value = k.tahun_ajaran_id;
  document.getElementById('edit_nama_kelas').value = k.nama_kelas;
  document.getElementById('edit_tingkat').value = k.tingkat;
  document.getElementById('edit_wali').value = k.wali_kelas_guru_id || '';
  openModal('modalEdit');
}
</script>
