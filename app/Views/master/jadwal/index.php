<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-clipboard"/></svg> Jadwal mengajar</h1>
  <p class="text-muted">
    Tahun ajaran <strong><?= esc($aktif['nama_tahun_ajaran'] ?? '') ?></strong>,
    semester <strong><?= esc($aktif['nama'] ?? '') ?></strong>
    — jadwal ini otomatis jadi acuan presensi &amp; jurnal, guru tidak perlu pilih apa pun lagi.
  </p>
</div>

<div class="toolbar">
  <input type="text" class="toolbar-search" placeholder="Cari guru, kelas, atau mapel..." oninput="filterTable(this.value, 'tabelJadwal')">
  <button type="button" class="btn btn-primary" onclick="openModal('modalTambah')"><svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah jadwal</button>
</div>

<div class="table-wrap">
  <table class="table" id="tabelJadwal">
    <thead>
      <tr><th>Hari</th><th>Jam</th><th>Kelas</th><th>Mata pelajaran</th><th>Guru</th><th style="text-align:right">Aksi</th></tr>
    </thead>
    <tbody>
      <?php if (empty($items)) : ?>
        <tr><td colspan="6"><div class="empty-state"><h3>Belum ada jadwal</h3><p>Klik "Tambah jadwal" untuk mulai membuat jadwal semester ini.</p></div></td></tr>
      <?php else : ?>
        <?php foreach ($items as $row) : ?>
          <tr>
            <td><?= esc($row['hari']) ?></td>
            <td><?= esc(substr($row['jam_mulai'], 0, 5)) ?>–<?= esc(substr($row['jam_selesai'], 0, 5)) ?></td>
            <td><?= esc($row['nama_kelas']) ?></td>
            <td><?= esc($row['nama_mapel']) ?></td>
            <td><?= esc($row['nama_guru']) ?></td>
            <td>
              <div class="row-actions">
                <button type="button" class="btn-icon" onclick='fillEditJadwal(<?= json_encode($row) ?>)'><svg class="icon-sm"><use href="#i-edit"/></svg> Edit</button>
                <form method="post" action="<?= base_url('master/jadwal/delete/' . $row['id']) ?>"
                  onsubmit="return confirm('Hapus jadwal ini?')" style="display:inline">
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
    <h3>Tambah jadwal</h3>
    <p class="text-muted" style="font-size:13px">Sistem otomatis menolak jadwal yang bentrok dengan guru atau kelas yang sama.</p>
    <form method="post" action="<?= base_url('master/jadwal/store') ?>">
      <?= csrf_field() ?>
      <div class="form-row">
        <div class="form-group">
          <label for="add_guru">Guru</label>
          <select id="add_guru" name="guru_id" required>
            <option value="">-- pilih guru --</option>
            <?php foreach ($guru as $g) : ?>
              <option value="<?= esc($g['id']) ?>"><?= esc($g['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="add_mapel">Mata pelajaran</label>
          <select id="add_mapel" name="mapel_id" required>
            <option value="">-- pilih mata pelajaran --</option>
            <?php foreach ($mapel as $m) : ?>
              <option value="<?= esc($m['id']) ?>"><?= esc($m['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="add_kelas">Kelas</label>
          <select id="add_kelas" name="kelas_id" required>
            <option value="">-- pilih kelas --</option>
            <?php foreach ($kelas as $k) : ?>
              <option value="<?= esc($k['id']) ?>"><?= esc($k['nama_kelas']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="add_hari">Hari</label>
          <select id="add_hari" name="hari" required>
            <?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h) : ?>
              <option value="<?= $h ?>"><?= $h ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="add_jam_mulai">Jam ke (mulai)</label>
          <select id="add_jam_mulai" name="jam_ke_mulai" required>
            <?php foreach ($jamPelajaran as $jp) : ?>
              <option value="<?= esc($jp['jam_ke']) ?>">Ke-<?= esc($jp['jam_ke']) ?> (<?= esc(substr($jp['jam_mulai'], 0, 5)) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="add_jam_selesai">Jam ke (selesai)</label>
          <select id="add_jam_selesai" name="jam_ke_selesai" required>
            <?php foreach ($jamPelajaran as $jp) : ?>
              <option value="<?= esc($jp['jam_ke']) ?>">Ke-<?= esc($jp['jam_ke']) ?> (<?= esc(substr($jp['jam_selesai'], 0, 5)) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <p class="form-hint">Untuk 1 jam pelajaran biasa, pilih jam ke-mulai dan ke-selesai yang sama.</p>
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
    <h3>Edit jadwal</h3>
    <form method="post" action="<?= base_url('master/jadwal/update') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="edit_id">
      <div class="form-row">
        <div class="form-group">
          <label for="edit_guru">Guru</label>
          <select id="edit_guru" name="guru_id" required>
            <?php foreach ($guru as $g) : ?>
              <option value="<?= esc($g['id']) ?>"><?= esc($g['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="edit_mapel">Mata pelajaran</label>
          <select id="edit_mapel" name="mapel_id" required>
            <?php foreach ($mapel as $m) : ?>
              <option value="<?= esc($m['id']) ?>"><?= esc($m['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="edit_kelas">Kelas</label>
          <select id="edit_kelas" name="kelas_id" required>
            <?php foreach ($kelas as $k) : ?>
              <option value="<?= esc($k['id']) ?>"><?= esc($k['nama_kelas']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="edit_hari">Hari</label>
          <select id="edit_hari" name="hari" required>
            <?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h) : ?>
              <option value="<?= $h ?>"><?= $h ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="edit_jam_mulai">Jam ke (mulai)</label>
          <select id="edit_jam_mulai" name="jam_ke_mulai" required>
            <?php foreach ($jamPelajaran as $jp) : ?>
              <option value="<?= esc($jp['jam_ke']) ?>">Ke-<?= esc($jp['jam_ke']) ?> (<?= esc(substr($jp['jam_mulai'], 0, 5)) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="edit_jam_selesai">Jam ke (selesai)</label>
          <select id="edit_jam_selesai" name="jam_ke_selesai" required>
            <?php foreach ($jamPelajaran as $jp) : ?>
              <option value="<?= esc($jp['jam_ke']) ?>">Ke-<?= esc($jp['jam_ke']) ?> (<?= esc(substr($jp['jam_selesai'], 0, 5)) ?>)</option>
            <?php endforeach; ?>
          </select>
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
function fillEditJadwal(j) {
  document.getElementById('edit_id').value = j.id;
  document.getElementById('edit_guru').value = j.guru_id;
  document.getElementById('edit_mapel').value = j.mapel_id;
  document.getElementById('edit_kelas').value = j.kelas_id;
  document.getElementById('edit_hari').value = j.hari;
  document.getElementById('edit_jam_mulai').value = j.jam_ke_mulai;
  document.getElementById('edit_jam_selesai').value = j.jam_ke_selesai;
  openModal('modalEdit');
}
</script>
