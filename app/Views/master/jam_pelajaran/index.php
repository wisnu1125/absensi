<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-clock"/></svg> Jam pelajaran</h1>
  <p class="text-muted">Atur jam masuk &amp; durasi tiap periode sesuai ketentuan sekolah Anda. Dipakai sebagai pilihan saat membuat jadwal mengajar.</p>
</div>

<div class="toolbar">
  <div></div>
  <button type="button" class="btn btn-primary" onclick="openModal('modalTambah')"><svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah jam pelajaran</button>
</div>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Jam ke-</th><th>Jam mulai</th><th>Jam selesai</th><th>Durasi</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($items)) : ?>
        <tr><td colspan="5">
          <div class="empty-state">
            <h3>Belum ada jam pelajaran</h3>
            <p>Tambahkan periode pertama sesuai jadwal sekolah Anda, misalnya jam ke-1 mulai 07.00.</p>
          </div>
        </td></tr>
      <?php else : ?>
        <?php foreach ($items as $row) :
          $menit = (strtotime($row['jam_selesai']) - strtotime($row['jam_mulai'])) / 60;
        ?>
          <tr>
            <td>Ke-<?= esc($row['jam_ke']) ?></td>
            <td><?= esc(substr($row['jam_mulai'], 0, 5)) ?></td>
            <td><?= esc(substr($row['jam_selesai'], 0, 5)) ?></td>
            <td class="text-muted"><?= esc((int) $menit) ?> menit</td>
            <td>
              <div class="row-actions">
                <button type="button" class="btn-icon"
                  onclick="fillEditJam('<?= esc($row['id'], 'js') ?>','<?= esc($row['jam_ke'], 'js') ?>','<?= esc(substr($row['jam_mulai'], 0, 5), 'js') ?>','<?= esc(substr($row['jam_selesai'], 0, 5), 'js') ?>')">
                  <svg class="icon-sm"><use href="#i-edit"/></svg> Edit
                </button>
                <form method="post" action="<?= base_url('master/jam-pelajaran/delete/' . $row['id']) ?>"
                  onsubmit="return confirm('Hapus jam ke-<?= esc($row['jam_ke'], 'js') ?>?')" style="display:inline">
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
    <h3>Tambah jam pelajaran</h3>
    <form method="post" action="<?= base_url('master/jam-pelajaran/store') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="add_jam_ke">Jam ke-</label>
        <input type="number" id="add_jam_ke" name="jam_ke" min="1" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="add_jam_mulai">Jam mulai</label>
          <input type="time" id="add_jam_mulai" name="jam_mulai" required>
        </div>
        <div class="form-group">
          <label for="add_jam_selesai">Jam selesai</label>
          <input type="time" id="add_jam_selesai" name="jam_selesai" required>
        </div>
      </div>
      <div class="form-hint">Sistem otomatis menolak jam yang tumpang tindih dengan periode lain.</div>
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
    <h3>Edit jam pelajaran</h3>
    <form method="post" action="<?= base_url('master/jam-pelajaran/update') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="edit_id">
      <div class="form-group">
        <label for="edit_jam_ke">Jam ke-</label>
        <input type="number" id="edit_jam_ke" name="jam_ke" min="1" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="edit_jam_mulai">Jam mulai</label>
          <input type="time" id="edit_jam_mulai" name="jam_mulai" required>
        </div>
        <div class="form-group">
          <label for="edit_jam_selesai">Jam selesai</label>
          <input type="time" id="edit_jam_selesai" name="jam_selesai" required>
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
function fillEditJam(id, jamKe, jamMulai, jamSelesai) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_jam_ke').value = jamKe;
  document.getElementById('edit_jam_mulai').value = jamMulai;
  document.getElementById('edit_jam_selesai').value = jamSelesai;
  openModal('modalEdit');
}
</script>
