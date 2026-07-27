<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-users"/></svg> Siswa</h1>
  <p class="text-muted">Data siswa dipakai saat presensi harian dan rekap wali kelas.</p>
</div>

<div class="toolbar">
  <input type="text" class="toolbar-search" placeholder="Cari siswa..." oninput="filterTable(this.value, 'tabelSiswa')">
  <div style="display:flex;gap:8px">
    <button type="button" class="btn btn-outline" onclick="openModal('modalImport')"><svg class="icon-sm"><use href="#i-upload"/></svg> Import Excel</button>
    <button type="button" class="btn btn-primary" onclick="openModal('modalTambah')"><svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah siswa</button>
  </div>
</div>

<div class="table-wrap">
  <table class="table" id="tabelSiswa">
    <thead><tr><th>NIS</th><th>Nama</th><th>L/P</th><th>Kelas</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($items)) : ?>
        <tr><td colspan="6"><div class="empty-state"><h3>Belum ada data siswa</h3><p>Tambah manual atau import lewat Excel.</p></div></td></tr>
      <?php else : ?>
        <?php foreach ($items as $row) : ?>
          <tr>
            <td><?= esc($row['nis']) ?></td>
            <td><?= esc($row['nama']) ?></td>
            <td><?= esc($row['jenis_kelamin']) ?></td>
            <td><?= esc($row['nama_kelas'] ?? '-') ?></td>
            <td>
              <?php if ($row['status'] === 'aktif') : ?>
                <span class="status-badge status-hadir">Aktif</span>
              <?php else : ?>
                <span class="status-badge status-alpha"><?= esc(ucfirst($row['status'])) ?></span>
              <?php endif; ?>
            </td>
            <td>
              <div class="row-actions">
                <button type="button" class="btn-icon" onclick='fillEditSiswa(<?= json_encode($row) ?>)'><svg class="icon-sm"><use href="#i-edit"/></svg> Edit</button>
                <form method="post" action="<?= base_url('master/siswa/delete/' . $row['id']) ?>"
                  onsubmit="return confirm('Hapus data siswa &quot;<?= esc($row['nama'], 'js') ?>&quot;?')" style="display:inline">
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
  <div class="modal-box modal-lg">
    <h3>Tambah siswa</h3>
    <form method="post" action="<?= base_url('master/siswa/store') ?>">
      <?= csrf_field() ?>
      <div class="form-row">
        <div class="form-group">
          <label for="add_nis">NIS</label>
          <input type="text" id="add_nis" name="nis" required maxlength="30">
        </div>
        <div class="form-group">
          <label for="add_nama">Nama lengkap</label>
          <input type="text" id="add_nama" name="nama" required maxlength="150">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="add_jk">Jenis kelamin</label>
          <select id="add_jk" name="jenis_kelamin" required>
            <option value="L">Laki-laki</option>
            <option value="P">Perempuan</option>
          </select>
        </div>
        <div class="form-group">
          <label for="add_kelas">Kelas</label>
          <select id="add_kelas" name="kelas_id">
            <option value="">-- belum ada kelas --</option>
            <?php foreach ($kelas as $k) : ?>
              <option value="<?= esc($k['id']) ?>"><?= esc($k['nama_kelas']) ?> (<?= esc($k['nama_tahun_ajaran']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="add_tgl">Tanggal lahir</label>
          <input type="date" id="add_tgl" name="tanggal_lahir">
        </div>
        <div class="form-group">
          <label for="add_hp_ortu">No HP orang tua</label>
          <input type="text" id="add_hp_ortu" name="no_hp_ortu" maxlength="20">
        </div>
      </div>
      <div class="form-group">
        <label for="add_nama_ortu">Nama orang tua</label>
        <input type="text" id="add_nama_ortu" name="nama_ortu" maxlength="150">
      </div>
      <div class="form-group">
        <label for="add_alamat">Alamat</label>
        <textarea id="add_alamat" name="alamat" rows="2"></textarea>
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
  <div class="modal-box modal-lg">
    <h3>Edit data siswa</h3>
    <form method="post" action="<?= base_url('master/siswa/update') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="edit_id">
      <div class="form-row">
        <div class="form-group">
          <label for="edit_nis">NIS</label>
          <input type="text" id="edit_nis" name="nis" required maxlength="30">
        </div>
        <div class="form-group">
          <label for="edit_nama">Nama lengkap</label>
          <input type="text" id="edit_nama" name="nama" required maxlength="150">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="edit_jk">Jenis kelamin</label>
          <select id="edit_jk" name="jenis_kelamin" required>
            <option value="L">Laki-laki</option>
            <option value="P">Perempuan</option>
          </select>
        </div>
        <div class="form-group">
          <label for="edit_kelas">Kelas</label>
          <select id="edit_kelas" name="kelas_id">
            <option value="">-- belum ada kelas --</option>
            <?php foreach ($kelas as $k) : ?>
              <option value="<?= esc($k['id']) ?>"><?= esc($k['nama_kelas']) ?> (<?= esc($k['nama_tahun_ajaran']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="edit_tgl">Tanggal lahir</label>
          <input type="date" id="edit_tgl" name="tanggal_lahir">
        </div>
        <div class="form-group">
          <label for="edit_hp_ortu">No HP orang tua</label>
          <input type="text" id="edit_hp_ortu" name="no_hp_ortu" maxlength="20">
        </div>
      </div>
      <div class="form-group">
        <label for="edit_nama_ortu">Nama orang tua</label>
        <input type="text" id="edit_nama_ortu" name="nama_ortu" maxlength="150">
      </div>
      <div class="form-group">
        <label for="edit_alamat">Alamat</label>
        <textarea id="edit_alamat" name="alamat" rows="2"></textarea>
      </div>
      <div class="form-group">
        <label for="edit_status">Status</label>
        <select id="edit_status" name="status" required>
          <option value="aktif">Aktif</option>
          <option value="nonaktif">Nonaktif</option>
          <option value="lulus">Lulus</option>
          <option value="pindah">Pindah</option>
        </select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalEdit')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan perubahan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Import Excel -->
<div class="modal" id="modalImport">
  <div class="modal-box">
    <h3>Import data siswa dari Excel</h3>
    <p class="text-muted" style="font-size:13px">
      Unduh template, isi datanya, lalu unggah kembali. Kolom "Kelas" diisi nama kelas persis (contoh: VII A)
      dan akan dicocokkan otomatis ke kelas pada tahun ajaran yang sedang aktif.
    </p>
    <p style="margin-bottom:16px">
      <a href="<?= base_url('master/siswa/template') ?>" class="btn btn-outline btn-sm"><svg class="icon-sm"><use href="#i-download"/></svg> Unduh template Excel</a>
    </p>
    <form method="post" action="<?= base_url('master/siswa/import') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="file_excel">File Excel (.xlsx / .xls / .csv)</label>
        <input type="file" id="file_excel" name="file_excel" accept=".xlsx,.xls,.csv" required>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalImport')">Batal</button>
        <button type="submit" class="btn btn-primary">Import sekarang</button>
      </div>
    </form>
  </div>
</div>

<script>
function fillEditSiswa(s) {
  document.getElementById('edit_id').value = s.id;
  document.getElementById('edit_nis').value = s.nis;
  document.getElementById('edit_nama').value = s.nama;
  document.getElementById('edit_jk').value = s.jenis_kelamin;
  document.getElementById('edit_kelas').value = s.kelas_id || '';
  document.getElementById('edit_tgl').value = s.tanggal_lahir || '';
  document.getElementById('edit_hp_ortu').value = s.no_hp_ortu || '';
  document.getElementById('edit_nama_ortu').value = s.nama_ortu || '';
  document.getElementById('edit_alamat').value = s.alamat || '';
  document.getElementById('edit_status').value = s.status;
  openModal('modalEdit');
}
</script>
