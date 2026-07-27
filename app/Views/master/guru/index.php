<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-user"/></svg> Guru</h1>
  <p class="text-muted">Data profil dan akun login guru dikelola dalam satu tempat yang sama.</p>
</div>

<div class="toolbar">
  <input type="text" class="toolbar-search" placeholder="Cari guru..." oninput="filterTable(this.value, 'tabelGuru')">
  <div style="display:flex;gap:8px">
    <button type="button" class="btn btn-outline" onclick="openModal('modalImport')"><svg class="icon-sm"><use href="#i-upload"/></svg> Import Excel</button>
    <button type="button" class="btn btn-primary" onclick="bukaTambah()"><svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah guru</button>
  </div>
</div>

<div class="table-wrap">
  <table class="table" id="tabelGuru">
    <thead><tr><th>Nama</th><th>NIP</th><th>L/P</th><th>Akun &amp; role</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($items)) : ?>
        <tr><td colspan="6"><div class="empty-state"><h3>Belum ada data guru</h3><p>Tambah manual atau import lewat Excel.</p></div></td></tr>
      <?php else : ?>
        <?php foreach ($items as $row) : ?>
          <tr>
            <td><?= esc($row['nama']) ?></td>
            <td><?= esc($row['nip'] ?? '-') ?></td>
            <td><?= esc($row['jenis_kelamin']) ?></td>
            <td>
              <?php if ($row['username']) : ?>
                <div style="font-weight:600;font-size:12.5px"><?= esc($row['username']) ?></div>
                <div style="margin-top:3px">
                  <?php foreach ($row['role_slugs'] as $rs) : ?>
                    <span class="role-badge" style="margin-right:2px"><?= esc(role_label($rs)) ?></span>
                  <?php endforeach; ?>
                </div>
              <?php else : ?>
                <span class="text-soft">Belum ada akun login</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($row['status'] === 'aktif') : ?>
                <span class="status-badge status-hadir">Aktif</span>
              <?php else : ?>
                <span class="status-badge status-alpha">Nonaktif</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="row-actions">
                <button type="button" class="btn-icon"
                  onclick='fillEditGuru(<?= json_encode($row) ?>)'>
                  <svg class="icon-sm"><use href="#i-edit"/></svg> Edit
                </button>
                <form method="post" action="<?= base_url('master/guru/delete/' . $row['id']) ?>"
                  onsubmit="return confirm('Hapus data guru &quot;<?= esc($row['nama'], 'js') ?>&quot;?<?= $row['username'] ? ' (akun login tidak ikut terhapus, kelola terpisah di menu Pengguna kalau perlu)' : '' ?>')" style="display:inline">
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

<?php
// Daftar checkbox role selain "guru" (guru otomatis diberikan server-side, tidak perlu dicentang manual).
$roleTambahan = array_filter($roles, static fn ($r) => $r['slug'] !== 'guru');
?>

<!-- Modal: Tambah -->
<div class="modal" id="modalTambah">
  <div class="modal-box modal-lg">
    <h3>Tambah guru</h3>
    <form method="post" action="<?= base_url('master/guru/store') ?>">
      <?= csrf_field() ?>

      <div class="form-row">
        <div class="form-group">
          <label for="add_nama">Nama lengkap</label>
          <input type="text" id="add_nama" name="nama" required maxlength="150">
        </div>
        <div class="form-group">
          <label for="add_nip">NIP</label>
          <input type="text" id="add_nip" name="nip" maxlength="30">
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
          <label for="add_hp">No HP</label>
          <input type="text" id="add_hp" name="no_hp" maxlength="20">
        </div>
      </div>
      <div class="form-group">
        <label for="add_alamat">Alamat</label>
        <textarea id="add_alamat" name="alamat" rows="2"></textarea>
      </div>

      <div class="form-group" style="border-top:1px solid var(--color-border);padding-top:14px;margin-top:4px">
        <label style="display:flex;align-items:center;gap:6px"><svg class="icon-sm"><use href="#i-key"/></svg> Akun login (opsional)</label>
        <p class="form-hint" style="margin:-2px 0 10px">Kosongkan username kalau guru ini belum perlu login ke sistem — bisa ditambahkan belakangan lewat Edit.</p>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="add_username">Username</label>
          <input type="text" id="add_username" name="username" maxlength="50" placeholder="Kosongkan jika belum perlu">
        </div>
        <div class="form-group">
          <label for="add_password">Password</label>
          <input type="password" id="add_password" name="password" minlength="6" placeholder="Minimal 6 karakter">
        </div>
      </div>
      <div class="form-group">
        <label for="add_email">Email (opsional)</label>
        <input type="email" id="add_email" name="email" maxlength="100">
      </div>
      <div class="form-group">
        <label>Role tambahan <span class="text-soft">(role Guru otomatis diberikan)</span></label>
        <div style="display:flex;flex-wrap:wrap;gap:10px">
          <?php foreach ($roleTambahan as $r) : ?>
            <label style="display:flex;align-items:center;gap:6px;font-weight:400;font-size:13.5px">
              <input type="checkbox" name="role_ids[]" value="<?= esc($r['id']) ?>"> <?= esc($r['name']) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <label style="display:flex;align-items:center;gap:6px;font-weight:400;font-size:13.5px;margin-top:6px">
        <input type="checkbox" name="is_active" value="1" checked> Akun aktif (bisa login)
      </label>

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
    <h3>Edit data guru</h3>
    <form method="post" action="<?= base_url('master/guru/update') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="edit_id">

      <div class="form-row">
        <div class="form-group">
          <label for="edit_nama">Nama lengkap</label>
          <input type="text" id="edit_nama" name="nama" required maxlength="150">
        </div>
        <div class="form-group">
          <label for="edit_nip">NIP</label>
          <input type="text" id="edit_nip" name="nip" maxlength="30">
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
          <label for="edit_hp">No HP</label>
          <input type="text" id="edit_hp" name="no_hp" maxlength="20">
        </div>
      </div>
      <div class="form-group">
        <label for="edit_alamat">Alamat</label>
        <textarea id="edit_alamat" name="alamat" rows="2"></textarea>
      </div>
      <div class="form-group">
        <label for="edit_status">Status profil</label>
        <select id="edit_status" name="status" required>
          <option value="aktif">Aktif</option>
          <option value="nonaktif">Nonaktif</option>
        </select>
      </div>

      <div class="form-group" style="border-top:1px solid var(--color-border);padding-top:14px;margin-top:4px">
        <label style="display:flex;align-items:center;gap:6px"><svg class="icon-sm"><use href="#i-key"/></svg> Akun login</label>
        <p class="form-hint" id="edit_akun_hint" style="margin:-2px 0 10px"></p>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="edit_username">Username</label>
          <input type="text" id="edit_username" name="username" maxlength="50">
        </div>
        <div class="form-group">
          <label for="edit_password">Password</label>
          <input type="password" id="edit_password" name="password" minlength="6" placeholder="Kosongkan jika tidak diganti">
        </div>
      </div>
      <div class="form-group">
        <label for="edit_email">Email (opsional)</label>
        <input type="email" id="edit_email" name="email" maxlength="100">
      </div>
      <div class="form-group">
        <label>Role tambahan <span class="text-soft">(role Guru otomatis diberikan)</span></label>
        <div id="edit_roles" style="display:flex;flex-wrap:wrap;gap:10px">
          <?php foreach ($roleTambahan as $r) : ?>
            <label style="display:flex;align-items:center;gap:6px;font-weight:400;font-size:13.5px">
              <input type="checkbox" name="role_ids[]" value="<?= esc($r['id']) ?>" class="edit-role-cb"> <?= esc($r['name']) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <label style="display:flex;align-items:center;gap:6px;font-weight:400;font-size:13.5px;margin-top:6px">
        <input type="checkbox" name="is_active" value="1" id="edit_is_active"> Akun aktif (bisa login)
      </label>

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
    <h3>Import data guru dari Excel</h3>
    <p class="text-muted" style="font-size:13px">
      Unduh template, isi datanya, lalu unggah kembali. Kolom wajib: NIP, Nama, Jenis Kelamin (L/P), No HP, Alamat.
      Kolom akun (Username, Password, Email, Role Tambahan) <strong>opsional per baris</strong> — isi Username
      &amp; Password (minimal 6 karakter) kalau guru itu langsung perlu login. Kolom Role Tambahan diisi nama
      role selain Guru, pisahkan dengan koma kalau lebih dari satu, contoh: <code>wali_kelas</code> atau
      <code>wali_kelas,operator</code>. Role Guru selalu otomatis diberikan.
    </p>
    <p style="margin-bottom:16px">
      <a href="<?= base_url('master/guru/template') ?>" class="btn btn-outline btn-sm"><svg class="icon-sm"><use href="#i-download"/></svg> Unduh template Excel</a>
    </p>
    <form method="post" action="<?= base_url('master/guru/import') ?>" enctype="multipart/form-data">
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
function bukaTambah() {
  document.getElementById('add_username').readOnly = false;
  openModal('modalTambah');
}

function fillEditGuru(g) {
  document.getElementById('edit_id').value = g.id;
  document.getElementById('edit_nama').value = g.nama;
  document.getElementById('edit_nip').value = g.nip || '';
  document.getElementById('edit_jk').value = g.jenis_kelamin;
  document.getElementById('edit_hp').value = g.no_hp || '';
  document.getElementById('edit_alamat').value = g.alamat || '';
  document.getElementById('edit_status').value = g.status;
  document.getElementById('edit_password').value = '';

  const userField   = document.getElementById('edit_username');
  const hint         = document.getElementById('edit_akun_hint');
  const emailField   = document.getElementById('edit_email');
  const activeField  = document.getElementById('edit_is_active');

  if (g.username) {
    // Sudah ada akun -> username terkunci, sisanya bisa diubah.
    userField.value = g.username;
    userField.readOnly = true;
    hint.textContent = 'Akun sudah ada, username tidak bisa diubah. Kosongkan password kalau tidak ingin menggantinya.';
    emailField.value = g.email || '';
    activeField.checked = String(g.is_active_akun) === '1';
  } else {
    // Belum ada akun -> semua field kosong & terbuka, admin bisa isi untuk membuatkan sekarang.
    userField.value = '';
    userField.readOnly = false;
    hint.textContent = 'Guru ini belum punya akun login. Isi username & password di bawah untuk membuatkannya sekarang, atau biarkan kosong.';
    emailField.value = '';
    activeField.checked = true;
  }

  document.querySelectorAll('.edit-role-cb').forEach(function (cb) {
    cb.checked = (g.role_ids || []).includes(parseInt(cb.value, 10));
  });

  openModal('modalEdit');
}
</script>
