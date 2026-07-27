<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-calendar"/></svg> Tahun ajaran &amp; semester</h1>
  <p class="text-muted">Hanya satu tahun ajaran dan satu semester yang aktif dalam satu waktu — dipakai sebagai acuan seluruh jadwal &amp; laporan.</p>
</div>

<div class="toolbar">
  <div></div>
  <button type="button" class="btn btn-primary" onclick="openModal('modalTambahTA')"><svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah tahun ajaran</button>
</div>

<div class="table-wrap" style="margin-bottom:24px">
  <table class="table">
    <thead><tr><th>Tahun ajaran</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($tahunAjaran)) : ?>
        <tr><td colspan="3"><div class="empty-state"><h3>Belum ada tahun ajaran</h3></div></td></tr>
      <?php else : ?>
        <?php foreach ($tahunAjaran as $row) : ?>
          <tr>
            <td><?= esc($row['nama']) ?></td>
            <td>
              <?php if ((int) $row['is_active'] === 1) : ?>
                <span class="status-badge status-hadir">Aktif</span>
              <?php else : ?>
                <span class="text-soft">Nonaktif</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="row-actions">
                <?php if ((int) $row['is_active'] !== 1) : ?>
                  <form method="post" action="<?= base_url('master/tahun-ajaran/aktifkan/' . $row['id']) ?>" style="display:inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-icon">Jadikan aktif</button>
                  </form>
                <?php endif; ?>
                <form method="post" action="<?= base_url('master/tahun-ajaran/delete/' . $row['id']) ?>"
                  onsubmit="return confirm('Hapus tahun ajaran ini? Kelas &amp; jadwal terkait ikut terhapus.')" style="display:inline">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn-icon btn-icon-danger">Hapus</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="toolbar">
  <h2 style="margin:0;display:flex;align-items:center;gap:8px"><svg class="icon-sm"><use href="#i-calendar"/></svg> Semester</h2>
  <button type="button" class="btn btn-primary" onclick="openModal('modalTambahSmt')"><svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah semester</button>
</div>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Tahun ajaran</th><th>Semester</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($semester)) : ?>
        <tr><td colspan="4"><div class="empty-state"><h3>Belum ada semester</h3></div></td></tr>
      <?php else : ?>
        <?php foreach ($semester as $row) : ?>
          <tr>
            <td><?= esc($row['nama_tahun_ajaran']) ?></td>
            <td><?= esc($row['nama']) ?></td>
            <td>
              <?php if ((int) $row['is_active'] === 1) : ?>
                <span class="status-badge status-hadir">Aktif</span>
              <?php else : ?>
                <span class="text-soft">Nonaktif</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="row-actions">
                <?php if ((int) $row['is_active'] !== 1) : ?>
                  <form method="post" action="<?= base_url('master/tahun-ajaran/semester/aktifkan/' . $row['id']) ?>" style="display:inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-icon">Jadikan aktif</button>
                  </form>
                <?php endif; ?>
                <form method="post" action="<?= base_url('master/tahun-ajaran/semester/delete/' . $row['id']) ?>"
                  onsubmit="return confirm('Hapus semester ini?')" style="display:inline">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn-icon btn-icon-danger">Hapus</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal: Tambah Tahun Ajaran -->
<div class="modal" id="modalTambahTA">
  <div class="modal-box">
    <h3>Tambah tahun ajaran</h3>
    <form method="post" action="<?= base_url('master/tahun-ajaran/store') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="ta_nama">Nama tahun ajaran</label>
        <input type="text" id="ta_nama" name="nama" placeholder="Contoh: 2026/2027" required maxlength="20">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalTambahTA')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Tambah Semester -->
<div class="modal" id="modalTambahSmt">
  <div class="modal-box">
    <h3>Tambah semester</h3>
    <form method="post" action="<?= base_url('master/tahun-ajaran/semester/store') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="smt_ta">Tahun ajaran</label>
        <select id="smt_ta" name="tahun_ajaran_id" required>
          <option value="">-- pilih tahun ajaran --</option>
          <?php foreach ($tahunAjaran as $ta) : ?>
            <option value="<?= esc($ta['id']) ?>"><?= esc($ta['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="smt_nama">Semester</label>
        <select id="smt_nama" name="nama" required>
          <option value="Ganjil">Ganjil</option>
          <option value="Genap">Genap</option>
        </select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalTambahSmt')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
