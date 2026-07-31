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
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="<?= base_url('master/jadwal/grid') ?>" class="btn btn-outline"><svg class="icon-sm"><use href="#i-dashboard"/></svg> Tampilan grid</a>
    <button type="button" class="btn btn-outline" onclick="openModal('modalImport')"><svg class="icon-sm"><use href="#i-upload"/></svg> Import Excel</button>
    <button type="button" class="btn btn-primary" onclick="bukaTambahJadwal()"><svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah jadwal</button>
  </div>
</div>

<div class="table-wrap table-responsive-cards">
  <table class="table" id="tabelJadwal">
    <thead>
      <tr><th style="width:50px">No.</th><th>Hari</th><th>Jam</th><th>Kelas</th><th>Mata pelajaran</th><th>Guru</th><th style="text-align:right">Aksi</th></tr>
    </thead>
    <tbody>
      <?php if (empty($items)) : ?>
        <tr><td colspan="7"><div class="empty-state"><h3>Belum ada jadwal</h3><p>Klik "Tambah jadwal" untuk mulai membuat jadwal semester ini.</p></div></td></tr>
      <?php else : ?>
        <?php foreach ($items as $i => $row) : ?>
          <tr>
            <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
            <td data-label="Hari"><?= esc($row['hari']) ?></td>
            <td class="text-soft" data-label="Jam"><?= esc(substr($row['jam_mulai'], 0, 5)) ?>–<?= esc(substr($row['jam_selesai'], 0, 5)) ?></td>
            <td class="td-card-title"><?= esc($row['nama_kelas']) ?></td>
            <td data-label="Mata pelajaran"><?= esc($row['nama_mapel']) ?></td>
            <td data-label="Guru"><?= esc($row['nama_guru']) ?></td>
            <td class="td-card-actions" data-label="">
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
    <?php if (! $adaPengampu) : ?>
      <div class="alert alert-danger" style="background:var(--color-warning-soft);color:var(--color-warning)">
        <svg class="icon-sm"><use href="#i-alert"/></svg>
        Belum ada data <strong>Guru Pengampu</strong>. Daftarkan dulu guru mana mengajar mapel apa di tingkat mana lewat menu
        <a href="<?= base_url('master/guru-pengampu') ?>" style="font-weight:700">Guru Pengampu</a> sebelum membuat jadwal.
      </div>
    <?php endif; ?>
    <form method="post" action="<?= base_url('master/jadwal/store') ?>">
      <?= csrf_field() ?>
      <div class="form-row">
        <div class="form-group">
          <label for="add_kelas">Kelas</label>
          <select id="add_kelas" name="kelas_id" required onchange="perbaruiPengampuAdd()">
            <option value="">-- pilih kelas --</option>
            <?php foreach ($kelas as $k) : ?>
              <option value="<?= esc($k['id']) ?>" data-tingkat="<?= esc($k['tingkat']) ?>"><?= esc($k['nama_kelas']) ?> (Tingkat <?= esc($k['tingkat']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="add_guru_pengampu">Guru pengampu (guru + mapel)</label>
          <select id="add_guru_pengampu" name="guru_pengampu_id" required>
            <option value="">-- pilih kelas dulu --</option>
          </select>
          <div class="form-hint">Hanya menampilkan guru yang terdaftar sebagai pengampu di tingkat kelas terpilih.</div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="add_hari">Hari</label>
          <select id="add_hari" name="hari" required onchange="perbaruiJamAdd()">
            <?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h) : ?>
              <option value="<?= $h ?>"><?= $h ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="add_jam_mulai">Jam ke (mulai)</label>
          <select id="add_jam_mulai" name="jam_ke_mulai" required></select>
        </div>
        <div class="form-group">
          <label for="add_jam_selesai">Jam ke (selesai)</label>
          <select id="add_jam_selesai" name="jam_ke_selesai" required></select>
        </div>
      </div>
      <p class="form-hint">Pilihan jam menyesuaikan hari yang dipilih (jam bisa beda tiap hari). Untuk 1 jam pelajaran biasa, pilih jam ke-mulai dan ke-selesai yang sama.</p>
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
          <label for="edit_kelas">Kelas</label>
          <select id="edit_kelas" name="kelas_id" required onchange="perbaruiPengampuEdit()">
            <?php foreach ($kelas as $k) : ?>
              <option value="<?= esc($k['id']) ?>" data-tingkat="<?= esc($k['tingkat']) ?>"><?= esc($k['nama_kelas']) ?> (Tingkat <?= esc($k['tingkat']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="edit_guru_pengampu">Guru pengampu (guru + mapel)</label>
          <select id="edit_guru_pengampu" name="guru_pengampu_id" required></select>
          <div class="form-hint" id="edit_pengampu_warning" style="display:none;color:var(--color-warning)">Jadwal ini belum tertaut ke Guru Pengampu manapun — pilih salah satu.</div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="edit_hari">Hari</label>
          <select id="edit_hari" name="hari" required onchange="perbaruiJamEdit()">
            <?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h) : ?>
              <option value="<?= $h ?>"><?= $h ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="edit_jam_mulai">Jam ke (mulai)</label>
          <select id="edit_jam_mulai" name="jam_ke_mulai" required></select>
        </div>
        <div class="form-group">
          <label for="edit_jam_selesai">Jam ke (selesai)</label>
          <select id="edit_jam_selesai" name="jam_ke_selesai" required></select>
        </div>
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
    <h3>Import jadwal dari Excel</h3>
    <p class="text-muted" style="font-size:13px">
      Unduh template — semua kolom (Guru, Mata Pelajaran, Kelas, Hari, Jam Ke) sudah berupa
      dropdown, tinggal klik sel lalu pilih, tidak perlu mengetik manual. Isi satu baris untuk
      setiap jadwal, lalu unggah kembali. Setiap baris tetap dicek bentrok guru &amp; kelas
      persis seperti tambah manual — baris yang bentrok akan dilewati dengan keterangan jelas.
      <br><br>
      <strong>Lebih suka bentuk grid seperti jadwal dinding sekolah?</strong>
      Coba <a href="<?= base_url('master/jadwal/grid') ?>">tampilan grid</a> — ada template &amp;
      import versi grid di sana.
    </p>
    <p style="margin-bottom:16px">
      <a href="<?= base_url('master/jadwal/template') ?>" class="btn btn-outline btn-sm"><svg class="icon-sm"><use href="#i-download"/></svg> Unduh template Excel</a>
    </p>
    <form method="post" action="<?= base_url('master/jadwal/import') ?>" enctype="multipart/form-data">
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
const dataJamPerHari = <?= json_encode($jamPelajaran) ?>;
const dataPengampuPerTingkat = <?= json_encode($pengampuPerTingkat) ?>;

function isiOpsiJam(selectMulai, selectSelesai, hari) {
  const daftar = dataJamPerHari[hari] || [];
  selectMulai.innerHTML = '';
  selectSelesai.innerHTML = '';
  if (daftar.length === 0) {
    selectMulai.innerHTML = '<option value="">Belum ada jam untuk hari ini</option>';
    selectSelesai.innerHTML = '<option value="">Belum ada jam untuk hari ini</option>';
    return;
  }
  daftar.forEach(function (jp) {
    selectMulai.innerHTML += '<option value="' + jp.jam_ke + '">Ke-' + jp.jam_ke + ' (' + jp.jam_mulai.slice(0, 5) + ')</option>';
    selectSelesai.innerHTML += '<option value="' + jp.jam_ke + '">Ke-' + jp.jam_ke + ' (' + jp.jam_selesai.slice(0, 5) + ')</option>';
  });
}

function perbaruiJamAdd() {
  const hari = document.getElementById('add_hari').value;
  isiOpsiJam(document.getElementById('add_jam_mulai'), document.getElementById('add_jam_selesai'), hari);
}

function perbaruiJamEdit() {
  const hari = document.getElementById('edit_hari').value;
  isiOpsiJam(document.getElementById('edit_jam_mulai'), document.getElementById('edit_jam_selesai'), hari);
}

// Isi dropdown Guru Pengampu sesuai TINGKAT dari kelas yang dipilih (bukan
// per-kelas spesifik, krn Guru Pengampu didaftarkan per jenjang/tingkat).
function isiOpsiPengampu(selectKelas, selectPengampu, nilaiTerpilih) {
  const opsiTerpilih = selectKelas.selectedOptions[0];
  const tingkat = opsiTerpilih ? opsiTerpilih.dataset.tingkat : '';
  const daftar = dataPengampuPerTingkat[tingkat] || [];

  if (daftar.length === 0) {
    selectPengampu.innerHTML = '<option value="">Belum ada Guru Pengampu utk tingkat ' + (tingkat || '-') + '</option>';
    return;
  }
  selectPengampu.innerHTML = '<option value="">-- pilih guru pengampu --</option>' +
    daftar.map(function (p) { return '<option value="' + p.id + '">' + p.label + '</option>'; }).join('');
  if (nilaiTerpilih) { selectPengampu.value = nilaiTerpilih; }
}

function perbaruiPengampuAdd() {
  isiOpsiPengampu(document.getElementById('add_kelas'), document.getElementById('add_guru_pengampu'), null);
}

function perbaruiPengampuEdit(nilaiTerpilih) {
  isiOpsiPengampu(document.getElementById('edit_kelas'), document.getElementById('edit_guru_pengampu'), nilaiTerpilih || null);
}

function bukaTambahJadwal() {
  perbaruiJamAdd();
  perbaruiPengampuAdd();
  openModal('modalTambah');
}

function fillEditJadwal(j) {
  document.getElementById('edit_id').value = j.id;
  document.getElementById('edit_kelas').value = j.kelas_id;
  document.getElementById('edit_hari').value = j.hari;
  perbaruiJamEdit();
  document.getElementById('edit_jam_mulai').value = j.jam_ke_mulai;
  document.getElementById('edit_jam_selesai').value = j.jam_ke_selesai;

  // Jadwal LAMA (dibuat sebelum fitur Guru Pengampu ada) mungkin belum
  // punya guru_pengampu_id — beri tahu admin supaya memilih salah satu
  // sebelum menyimpan, bukan diam-diam gagal tersimpan.
  perbaruiPengampuEdit(j.guru_pengampu_id);
  document.getElementById('edit_pengampu_warning').style.display = j.guru_pengampu_id ? 'none' : '';

  openModal('modalEdit');
}
</script>
