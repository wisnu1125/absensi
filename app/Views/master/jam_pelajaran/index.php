<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-clock"/></svg> Jam pelajaran</h1>
  <p class="text-muted">Atur jam masuk &amp; durasi tiap periode — bisa berbeda per hari (mis. Senin jam ke-1 mulai 08.05, Selasa mulai 07.30). Dipakai sebagai pilihan saat membuat jadwal mengajar.</p>
</div>

<div class="toolbar">
  <div class="tabs" id="tabsHari">
    <?php foreach (array_keys($dikelompokkan) as $idx => $h) : ?>
      <button type="button" class="tab-btn <?= $idx === 0 ? 'active' : '' ?>" data-hari="<?= esc($h) ?>" onclick="pindahTabHari('<?= esc($h, 'js') ?>')">
        <?= esc($h) ?> <span class="text-soft">(<?= count($dikelompokkan[$h]) ?>)</span>
      </button>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn btn-primary" onclick="bukaTambah()"><svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah jam pelajaran</button>
</div>

<?php foreach ($dikelompokkan as $hari => $items) : ?>
  <div class="table-wrap table-responsive-cards panel-hari" data-hari="<?= esc($hari) ?>" style="<?= $hari !== 'Senin' ? 'display:none' : '' ?>">
    <table class="table">
      <thead><tr><th style="width:50px">No.</th><th>Jam ke-</th><th>Jam mulai</th><th>Jam selesai</th><th>Durasi</th><th style="text-align:right">Aksi</th></tr></thead>
      <tbody>
        <?php if (empty($items)) : ?>
          <tr><td colspan="6">
            <div class="empty-state">
              <h3>Belum ada jam pelajaran untuk hari <?= esc($hari) ?></h3>
              <p>Klik "Tambah jam pelajaran" untuk menambahkan periode pertama hari ini.</p>
            </div>
          </td></tr>
        <?php else : ?>
          <?php foreach ($items as $i => $row) :
            $menit = (strtotime($row['jam_selesai']) - strtotime($row['jam_mulai'])) / 60;
          ?>
            <tr>
              <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
              <td class="td-card-title">Ke-<?= esc($row['jam_ke']) ?></td>
              <td data-label="Jam mulai"><?= esc(substr($row['jam_mulai'], 0, 5)) ?></td>
              <td data-label="Jam selesai"><?= esc(substr($row['jam_selesai'], 0, 5)) ?></td>
              <td class="text-muted" data-label="Durasi"><?= esc((int) $menit) ?> menit</td>
              <td class="td-card-actions" data-label="">
                <div class="row-actions">
                  <button type="button" class="btn-icon"
                    onclick='fillEditJam(<?= json_encode($row) ?>)'>
                    <svg class="icon-sm"><use href="#i-edit"/></svg> Edit
                  </button>
                  <form method="post" action="<?= base_url('master/jam-pelajaran/delete/' . $row['id']) ?>"
                    onsubmit="return confirm('Hapus jam ke-<?= esc($row['jam_ke'], 'js') ?> hari <?= esc($hari, 'js') ?>?')" style="display:inline">
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
<?php endforeach; ?>

<!-- Modal: Tambah -->
<div class="modal" id="modalTambah">
  <div class="modal-box">
    <h3>Tambah jam pelajaran</h3>
    <form method="post" action="<?= base_url('master/jam-pelajaran/store') ?>">
      <?= csrf_field() ?>
      <div class="form-row">
        <div class="form-group">
          <label for="add_hari">Hari</label>
          <select id="add_hari" name="hari" required>
            <?php foreach (array_keys($dikelompokkan) as $h) : ?>
              <option value="<?= esc($h) ?>"><?= esc($h) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="add_jam_ke">Jam ke-</label>
          <input type="number" id="add_jam_ke" name="jam_ke" min="1" required>
        </div>
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
      <div class="form-hint">Sistem otomatis menolak jam yang tumpang tindih dengan periode lain di HARI YANG SAMA.</div>
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
      <div class="form-row">
        <div class="form-group">
          <label for="edit_hari">Hari</label>
          <select id="edit_hari" name="hari" required>
            <?php foreach (array_keys($dikelompokkan) as $h) : ?>
              <option value="<?= esc($h) ?>"><?= esc($h) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="edit_jam_ke">Jam ke-</label>
          <input type="number" id="edit_jam_ke" name="jam_ke" min="1" required>
        </div>
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
function pindahTabHari(hari) {
  document.querySelectorAll('#tabsHari .tab-btn').forEach(function (btn) {
    btn.classList.toggle('active', btn.dataset.hari === hari);
  });
  document.querySelectorAll('.panel-hari').forEach(function (panel) {
    panel.style.display = panel.dataset.hari === hari ? '' : 'none';
  });
}

function hariAktifSaatIni() {
  const aktif = document.querySelector('#tabsHari .tab-btn.active');
  return aktif ? aktif.dataset.hari : 'Senin';
}

function bukaTambah() {
  document.getElementById('add_hari').value = hariAktifSaatIni();
  openModal('modalTambah');
}

function fillEditJam(row) {
  document.getElementById('edit_id').value = row.id;
  document.getElementById('edit_hari').value = row.hari;
  document.getElementById('edit_jam_ke').value = row.jam_ke;
  document.getElementById('edit_jam_mulai').value = row.jam_mulai.slice(0, 5);
  document.getElementById('edit_jam_selesai').value = row.jam_selesai.slice(0, 5);
  openModal('modalEdit');
}
</script>
