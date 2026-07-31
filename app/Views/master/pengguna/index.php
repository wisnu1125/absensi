<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-shield"/></svg> Pengguna &amp; role</h1>
  <p class="text-muted">
    Satu pengguna bisa punya lebih dari satu role sekaligus. Untuk akun guru, lebih mudah dikelola langsung
    lewat menu <a href="<?= base_url('master/guru') ?>">Guru</a> — halaman ini untuk akun non-guru
    (administrator/operator/kepala sekolah) dan pengaturan role secara umum.
  </p>
</div>

<div class="toolbar">
  <input type="text" class="toolbar-search" placeholder="Cari pengguna..." oninput="filterTable(this.value, 'tabelUser')">
  <button type="button" class="btn btn-primary" onclick="openModal('modalTambah')"><svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah pengguna</button>
</div>

<div class="table-wrap table-responsive-cards">
  <table class="table" id="tabelUser">
    <thead><tr><th style="width:50px">No.</th><th>Nama</th><th>Username</th><th>Role</th><th>Profil guru</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($users)) : ?>
        <tr><td colspan="7"><div class="empty-state"><h3>Belum ada pengguna</h3></div></td></tr>
      <?php else : ?>
        <?php foreach ($users as $i => $u) : ?>
          <tr>
            <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
            <td class="td-card-title"><?= esc($u['full_name']) ?></td>
            <td data-label="Username"><?= esc($u['username']) ?></td>
            <td data-label="Role">
              <?php foreach ($u['role_slugs'] as $r) : ?>
                <span class="role-badge" style="margin-right:3px"><?= esc(role_label($r)) ?></span>
              <?php endforeach; ?>
            </td>
            <td data-label="Profil guru"><?= $u['guru_nama'] ? esc($u['guru_nama']) : '<span class="text-soft">-</span>' ?></td>
            <td data-label="Status">
              <?php if ((int) $u['is_active'] === 1) : ?>
                <span class="status-badge status-hadir">Aktif</span>
              <?php else : ?>
                <span class="status-badge status-alpha">Nonaktif</span>
              <?php endif; ?>
            </td>
            <td class="td-card-actions" data-label="">
              <div class="row-actions">
                <button type="button" class="btn-icon" onclick='bukaEditUser(<?= json_encode($u) ?>)'><svg class="icon-sm"><use href="#i-edit"/></svg> Edit</button>
                <form method="post" action="<?= base_url('master/pengguna/delete/' . $u['id']) ?>"
                  onsubmit="return confirm('Hapus pengguna &quot;<?= esc($u['full_name'], 'js') ?>&quot;?')" style="display:inline">
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
    <h3>Tambah pengguna</h3>
    <p class="text-muted" style="font-size:13px">Untuk guru, gunakan menu Guru supaya profil &amp; akunnya langsung tertaut otomatis.</p>
    <form method="post" action="<?= base_url('master/pengguna/store') ?>">
      <?= csrf_field() ?>
      <div class="form-row">
        <div class="form-group">
          <label for="add_username">Username</label>
          <input type="text" id="add_username" name="username" required maxlength="50">
        </div>
        <div class="form-group">
          <label for="add_password">Password</label>
          <input type="password" id="add_password" name="password" required minlength="6">
          <div class="form-hint">Minimal 6 karakter.</div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="add_full_name">Nama lengkap</label>
          <input type="text" id="add_full_name" name="full_name" required maxlength="150">
        </div>
        <div class="form-group">
          <label for="add_email">Email (opsional)</label>
          <input type="email" id="add_email" name="email" maxlength="100">
        </div>
      </div>

      <div class="form-group">
        <label>Role</label>
        <div class="checkbox-group">
          <?php foreach ($roles as $r) : ?>
            <label class="checkbox-label">
              <input type="checkbox" name="role_ids[]" value="<?= esc($r['id']) ?>"> <?= esc($r['name']) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <label class="checkbox-label" style="margin-top:10px">
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
    <h3>Edit pengguna</h3>
    <form method="post" action="<?= base_url('master/pengguna/update') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="edit_id">
      <div class="form-row">
        <div class="form-group">
          <label for="edit_username">Username</label>
          <input type="text" id="edit_username" disabled style="background:var(--color-bg)">
          <div class="form-hint">Username tidak bisa diubah.</div>
        </div>
        <div class="form-group">
          <label for="edit_password">Password baru (opsional)</label>
          <input type="password" id="edit_password" name="password" minlength="6" placeholder="Kosongkan jika tidak diganti">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="edit_full_name">Nama lengkap</label>
          <input type="text" id="edit_full_name" name="full_name" required maxlength="150">
        </div>
        <div class="form-group">
          <label for="edit_email">Email (opsional)</label>
          <input type="email" id="edit_email" name="email" maxlength="100">
        </div>
      </div>

      <div class="form-group">
        <label>Role</label>
        <div id="edit_roles" class="checkbox-group">
          <?php foreach ($roles as $r) : ?>
            <label class="checkbox-label">
              <input type="checkbox" name="role_ids[]" value="<?= esc($r['id']) ?>" class="edit-role-cb"> <?= esc($r['name']) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <?php // Catatan: tautan ke profil guru sekarang dikelola dari menu Guru, bukan di sini. ?>

      <label class="checkbox-label" style="margin-top:10px">
        <input type="checkbox" name="is_active" value="1" id="edit_is_active"> Akun aktif (bisa login)
      </label>

      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalEdit')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
function bukaEditUser(u) {
  document.getElementById('edit_id').value = u.id;
  document.getElementById('edit_username').value = u.username;
  document.getElementById('edit_full_name').value = u.full_name;
  document.getElementById('edit_email').value = u.email || '';
  document.getElementById('edit_password').value = '';
  document.getElementById('edit_is_active').checked = String(u.is_active) === '1';

  document.querySelectorAll('.edit-role-cb').forEach(function (cb) {
    cb.checked = u.role_ids.includes(parseInt(cb.value, 10));
  });

  openModal('modalEdit');
}
</script>
