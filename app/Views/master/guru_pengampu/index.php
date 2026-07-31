<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-cap"/></svg> Guru pengampu</h1>
  <p class="text-muted">Menentukan guru mana berhak mengajar mata pelajaran apa di tingkat kelas mana — dipakai sebagai sumber pilihan saat membuat Jadwal Mengajar, supaya penjadwalan tidak bisa asal pilih guru+mapel bebas.</p>
</div>

<div class="toolbar">
  <input type="text" class="toolbar-search" placeholder="Cari guru/mapel..." oninput="filterTable(this.value, 'tabelPengampu')">
  <button type="button" class="btn btn-primary" onclick="openModal('modalTambah')"><svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah pengampu</button>
</div>

<div class="table-wrap table-responsive-cards">
  <table class="table" id="tabelPengampu">
    <thead><tr><th style="width:50px">No.</th><th>Guru</th><th>Mata pelajaran</th><th>Tingkat</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($items)) : ?>
        <tr><td colspan="5"><div class="empty-state"><h3>Belum ada data Guru Pengampu</h3><p>Klik "Tambah pengampu" untuk menambahkan data pertama — mis. "Bu Sari mengajar Bahasa Inggris di tingkat VII".</p></div></td></tr>
      <?php else : ?>
        <?php foreach ($items as $i => $row) : ?>
          <tr>
            <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
            <td class="td-card-title"><?= esc($row['nama_guru']) ?></td>
            <td data-label="Mata pelajaran"><?= esc($row['nama_mapel']) ?></td>
            <td data-label="Tingkat"><span class="role-badge">Tingkat <?= esc($row['tingkat']) ?></span></td>
            <td class="td-card-actions" data-label="">
              <div class="row-actions">
                <button type="button" class="btn-icon" onclick='fillEdit(<?= json_encode($row) ?>)'><svg class="icon-sm"><use href="#i-edit"/></svg> Edit</button>
                <form method="post" action="<?= base_url('master/guru-pengampu/delete/' . $row['id']) ?>"
                  onsubmit="return confirm('Hapus pengampu &quot;<?= esc($row['nama_guru'], 'js') ?> - <?= esc($row['nama_mapel'], 'js') ?> - Tingkat <?= esc($row['tingkat'], 'js') ?>&quot;?')" style="display:inline">
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
    <h3>Tambah Guru Pengampu</h3>
    <form method="post" action="<?= base_url('master/guru-pengampu/store') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="add_guru">Guru</label>
        <select id="add_guru" name="guru_id" required>
          <option value="">— pilih guru —</option>
          <?php foreach ($daftarGuru as $g) : ?>
            <option value="<?= esc($g['id']) ?>"><?= esc($g['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="add_mapel">Mata pelajaran</label>
          <select id="add_mapel" name="mapel_id" required>
            <option value="">— pilih mapel —</option>
            <?php foreach ($daftarMapel as $m) : ?>
              <option value="<?= esc($m['id']) ?>"><?= esc($m['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="add_tingkat">Tingkat</label>
          <?php if (empty($daftarTingkat)) : ?>
            <input type="text" id="add_tingkat" name="tingkat" required placeholder="mis. VII">
            <div class="form-hint">Belum ada data Kelas — ketik manual tingkatnya.</div>
          <?php else : ?>
            <select id="add_tingkat" name="tingkat" required>
              <option value="">— pilih tingkat —</option>
              <?php foreach ($daftarTingkat as $t) : ?>
                <option value="<?= esc($t) ?>"><?= esc($t) ?></option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
        </div>
      </div>
      <div class="form-hint">Satu guru bisa punya banyak pengampuan (mapel/tingkat berbeda-beda) — tambahkan satu per satu.</div>
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
    <h3>Edit Guru Pengampu</h3>
    <form method="post" action="<?= base_url('master/guru-pengampu/update') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="edit_id">
      <div class="form-group">
        <label for="edit_guru">Guru</label>
        <select id="edit_guru" name="guru_id" required>
          <?php foreach ($daftarGuru as $g) : ?>
            <option value="<?= esc($g['id']) ?>"><?= esc($g['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="edit_mapel">Mata pelajaran</label>
          <select id="edit_mapel" name="mapel_id" required>
            <?php foreach ($daftarMapel as $m) : ?>
              <option value="<?= esc($m['id']) ?>"><?= esc($m['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="edit_tingkat">Tingkat</label>
          <?php if (empty($daftarTingkat)) : ?>
            <input type="text" id="edit_tingkat" name="tingkat" required>
          <?php else : ?>
            <select id="edit_tingkat" name="tingkat" required>
              <?php foreach ($daftarTingkat as $t) : ?>
                <option value="<?= esc($t) ?>"><?= esc($t) ?></option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
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
function fillEdit(row) {
  document.getElementById('edit_id').value = row.id;
  document.getElementById('edit_guru').value = row.guru_id;
  document.getElementById('edit_mapel').value = row.mapel_id;
  document.getElementById('edit_tingkat').value = row.tingkat;
  openModal('modalEdit');
}
</script>
