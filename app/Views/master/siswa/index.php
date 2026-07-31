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
  <input type="text" class="toolbar-search" id="cariSiswa" placeholder="Cari siswa..." oninput="filterSiswa()">
  <select id="filterKelas" onchange="filterSiswa()" style="max-width:200px">
    <option value="">Semua kelas</option>
    <?php foreach ($kelas as $k) : ?>
      <option value="<?= esc($k['id']) ?>"><?= esc($k['nama_kelas']) ?></option>
    <?php endforeach; ?>
  </select>
  <select id="filterJenjang" onchange="filterSiswa()" style="max-width:160px">
    <option value="">Semua jenjang</option>
    <?php foreach ($tingkatList as $t) : ?>
      <option value="<?= esc($t) ?>">Tingkat <?= esc($t) ?></option>
    <?php endforeach; ?>
  </select>
  <div style="display:flex;gap:8px">
    <button type="button" class="btn btn-outline" onclick="openModal('modalImport')"><svg class="icon-sm"><use href="#i-upload"/></svg> Import Excel</button>
    <button type="button" class="btn btn-primary" onclick="openModal('modalTambah')"><svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah siswa</button>
  </div>
</div>

<form method="post" action="<?= base_url('master/siswa/bulk-delete') ?>" id="formBulkDelete" onsubmit="return confirm('Hapus ' + document.querySelectorAll('.cek-siswa:checked').length + ' siswa terpilih?')">
  <?= csrf_field() ?>
  <div id="barAksiMassal" class="alert alert-danger" style="display:none;background:var(--color-primary-soft);color:var(--color-primary);margin-bottom:12px;display:flex;justify-content:space-between;align-items:center">
    <span><strong id="jumlahTerpilih">0</strong> siswa dipilih</span>
    <button type="submit" class="btn btn-icon-danger btn-sm" style="background:#fff"><svg class="icon-sm"><use href="#i-trash"/></svg> Hapus yang dipilih</button>
  </div>

  <div class="table-wrap table-responsive-cards">
    <table class="table" id="tabelSiswa">
      <thead><tr>
        <th style="width:36px"><input type="checkbox" id="cekSemua" onchange="toggleSemua(this)"></th>
        <th style="width:50px">No.</th><th>NIS</th><th>Nama</th><th>L/P</th><th>Kelas</th><th>Status</th><th style="text-align:right">Aksi</th>
      </tr></thead>
      <tbody>
        <?php if (empty($items)) : ?>
          <tr><td colspan="8"><div class="empty-state"><h3>Belum ada data siswa</h3><p>Tambah manual atau import lewat Excel.</p></div></td></tr>
        <?php else : ?>
          <?php foreach ($items as $i => $row) : ?>
            <tr data-kelas-id="<?= esc($row['kelas_id'] ?? '') ?>" data-tingkat="<?= esc($row['tingkat'] ?? '') ?>">
              <td data-label=""><input type="checkbox" class="cek-siswa" name="ids[]" value="<?= esc($row['id']) ?>" onchange="perbaruiAksiMassal()"></td>
              <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
              <td data-label="NIS"><?= esc($row['nis']) ?></td>
              <td class="td-card-title"><?= esc($row['nama']) ?></td>
              <td data-label="L/P"><?= esc($row['jenis_kelamin']) ?></td>
              <td data-label="Kelas"><?= esc($row['nama_kelas'] ?? '-') ?></td>
              <td data-label="Status">
                <?php if ($row['status'] === 'aktif') : ?>
                  <span class="status-badge status-hadir">Aktif</span>
                <?php else : ?>
                  <span class="status-badge status-alpha"><?= esc(ucfirst($row['status'])) ?></span>
                <?php endif; ?>
              </td>
              <td class="td-card-actions" data-label="">
                <div class="row-actions">
                  <button type="button" class="btn-icon" onclick='fillEditSiswa(<?= json_encode($row) ?>)'><svg class="icon-sm"><use href="#i-edit"/></svg> Edit</button>
                  <button type="button" class="btn-icon btn-icon-danger" onclick="hapusSatu(<?= (int) $row['id'] ?>, '<?= esc($row['nama'], 'js') ?>')"><svg class="icon-sm"><use href="#i-trash"/></svg> Hapus</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</form>

<!-- Form terpisah khusus utk hapus SATU siswa (di luar form bulk-delete, supaya action-nya beda) -->
<form method="post" id="formHapusSatu" style="display:none">
  <?= csrf_field() ?>
</form>

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
function filterSiswa() {
  const q = document.getElementById('cariSiswa').value.trim().toLowerCase();
  const kelasId = document.getElementById('filterKelas').value;
  const tingkat = document.getElementById('filterJenjang').value;

  document.querySelectorAll('#tabelSiswa tbody tr[data-kelas-id]').forEach(function (row) {
    const cocokTeks = ! q || row.textContent.toLowerCase().includes(q);
    const cocokKelas = ! kelasId || row.dataset.kelasId === kelasId;
    const cocokTingkat = ! tingkat || row.dataset.tingkat === tingkat;
    row.style.display = (cocokTeks && cocokKelas && cocokTingkat) ? '' : 'none';
  });

  // Lepas centang baris yang jadi tersembunyi, supaya tidak "terpilih diam-diam".
  document.querySelectorAll('#tabelSiswa tbody tr[style*="display: none"] .cek-siswa:checked').forEach(function (cb) {
    cb.checked = false;
  });
  perbaruiAksiMassal();
}

function toggleSemua(master) {
  document.querySelectorAll('#tabelSiswa tbody tr:not([style*="display: none"]) .cek-siswa').forEach(function (cb) {
    cb.checked = master.checked;
  });
  perbaruiAksiMassal();
}

function perbaruiAksiMassal() {
  const jumlah = document.querySelectorAll('.cek-siswa:checked').length;
  document.getElementById('jumlahTerpilih').textContent = jumlah;
  document.getElementById('barAksiMassal').style.display = jumlah > 0 ? 'flex' : 'none';
}

function hapusSatu(id, nama) {
  if (! confirm('Hapus data siswa "' + nama + '"?')) {
    return;
  }
  const form = document.getElementById('formHapusSatu');
  form.action = '<?= base_url('master/siswa/delete/') ?>' + id;
  form.submit();
}

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

// Kalau datang dari link "X siswa" di halaman Kelas (?kelas_id=..), langsung terapkan filternya.
(function () {
  const kelasIdUrl = new URLSearchParams(window.location.search).get('kelas_id');
  if (kelasIdUrl) {
    document.getElementById('filterKelas').value = kelasIdUrl;
    filterSiswa();
  }
})();
</script>
