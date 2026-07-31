<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-clipboard"/></svg> Jadwal mengajar — tampilan grid</h1>
  <p class="text-muted">Klik sel kosong untuk menambah, klik sel terisi untuk mengubah. Mirip jadwal dinding yang biasa dipakai sekolah.</p>
</div>

<div class="toolbar">
  <div style="display:flex;gap:6px;flex-wrap:wrap">
    <?php foreach ($hariList as $h) : ?>
      <a href="<?= base_url('master/jadwal/grid?hari=' . $h) ?>" class="btn btn-sm <?= $h === $hariAktif ? 'btn-primary' : 'btn-outline' ?>"><?= esc($h) ?></a>
    <?php endforeach; ?>
  </div>
  <div style="display:flex;gap:8px">
    <a href="<?= base_url('master/jadwal') ?>" class="btn btn-outline btn-sm">Tampilan daftar</a>
    <button type="button" class="btn btn-outline btn-sm" onclick="openModal('modalImportGrid')"><svg class="icon-sm"><use href="#i-upload"/></svg> Import grid Excel</button>
  </div>
</div>

<div id="gridTableWrap">
  <?= view('master/jadwal/_grid_table', ['kelasList' => $kelasList, 'jamList' => $jamList, 'grid' => $grid]) ?>
</div>

<!-- Modal: Tambah dari klik sel -->
<div class="modal" id="modalTambahGrid">
  <div class="modal-box">
    <h3>Tambah jadwal</h3>
    <p class="text-muted" style="font-size:13px" id="infoSelTambah"></p>
    <?php if (! $adaPengampu) : ?>
      <div class="alert alert-danger" style="background:var(--color-warning-soft);color:var(--color-warning);margin-bottom:12px">
        <svg class="icon-sm"><use href="#i-alert"/></svg>
        Belum ada data <strong>Guru Pengampu</strong>. Daftarkan dulu lewat menu
        <a href="<?= base_url('master/guru-pengampu') ?>" style="font-weight:700">Guru Pengampu</a>.
      </div>
    <?php endif; ?>
    <form method="post" action="<?= base_url('master/jadwal/store') ?>" id="formTambahGrid">
      <?= csrf_field() ?>
      <input type="hidden" name="hari" id="tg_hari" value="<?= esc($hariAktif) ?>">
      <input type="hidden" name="hari_aktif_grid" value="<?= esc($hariAktif) ?>">
      <input type="hidden" name="kelas_id" id="tg_kelas">
      <input type="hidden" name="jam_ke_mulai" id="tg_jam_mulai">
      <div class="form-group">
        <label for="tg_guru_pengampu">Guru pengampu (guru + mapel)</label>
        <select id="tg_guru_pengampu" name="guru_pengampu_id" required></select>
        <div class="form-hint">Hanya menampilkan guru yang terdaftar berhak mengajar di tingkat kelas ini.</div>
      </div>
      <div class="form-group">
        <label for="tg_jam_selesai">Sampai jam ke- <span class="text-soft">(pilih lebih dari jam mulai kalau sesinya lebih dari 1 jam pelajaran)</span></label>
        <select id="tg_jam_selesai" name="jam_ke_selesai" required>
          <?php foreach ($jamList as $jp) : ?>
            <option value="<?= esc($jp['jam_ke']) ?>">Ke-<?= esc($jp['jam_ke']) ?> (<?= esc(substr($jp['jam_selesai'], 0, 5)) ?>)</option>
          <?php endforeach; ?>
        </select>
        <div class="form-hint">Jam 1 pelajaran biasa: biarkan sama dengan jam mulai. Contoh: Matematika 3 jam berurutan (ke-1 s/d ke-3) tetap tersimpan sebagai SATU sesi, guru cukup isi 1 presensi &amp; 1 jurnal.</div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalTambahGrid')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit dari klik sel -->
<div class="modal" id="modalEditGrid">
  <div class="modal-box">
    <h3>Edit jadwal</h3>
    <p class="text-muted" style="font-size:13px" id="infoSelEdit"></p>
    <form method="post" action="<?= base_url('master/jadwal/update') ?>" id="formEditGrid">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="eg_id">
      <input type="hidden" name="hari" id="eg_hari" value="<?= esc($hariAktif) ?>">
      <input type="hidden" name="hari_aktif_grid" value="<?= esc($hariAktif) ?>">
      <input type="hidden" name="kelas_id" id="eg_kelas">
      <input type="hidden" name="jam_ke_mulai" id="eg_jam_mulai">
      <div class="form-group">
        <label for="eg_guru_pengampu">Guru pengampu (guru + mapel)</label>
        <select id="eg_guru_pengampu" name="guru_pengampu_id" required></select>
        <div class="form-hint" id="eg_pengampu_warning" style="display:none;color:var(--color-warning)">Jadwal ini belum tertaut ke Guru Pengampu manapun — pilih salah satu.</div>
      </div>
      <div class="form-group">
        <label for="eg_jam_selesai">Sampai jam ke-</label>
        <select id="eg_jam_selesai" name="jam_ke_selesai" required>
          <?php foreach ($jamList as $jp) : ?>
            <option value="<?= esc($jp['jam_ke']) ?>">Ke-<?= esc($jp['jam_ke']) ?> (<?= esc(substr($jp['jam_selesai'], 0, 5)) ?>)</option>
          <?php endforeach; ?>
        </select>
        <div class="form-hint">Ubah supaya lebih panjang/pendek dari jam mulai kalau perlu.</div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalEditGrid')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan perubahan</button>
      </div>
    </form>
    <form method="post" action="<?= base_url('master/jadwal/delete/') ?>" id="formHapusGrid" style="margin-top:10px">
      <?= csrf_field() ?>
      <input type="hidden" name="hari_aktif_grid" value="<?= esc($hariAktif) ?>">
      <button type="submit" class="btn btn-outline" style="width:100%;color:var(--color-danger);border-color:var(--color-danger)">Hapus jadwal ini</button>
    </form>
  </div>
</div>

<!-- Modal: Import Grid Excel -->
<div class="modal" id="modalImportGrid">
  <div class="modal-box">
    <h3>Import jadwal dari template grid</h3>
    <p class="text-muted" style="font-size:13px">
      Template ini berbentuk grid seperti jadwal dinding sekolah: baris Hari &amp; Jam Ke, kolom
      tiap kelas (Guru + Mapel). Jam yang berurutan dengan guru &amp; mapel sama otomatis
      digabung jadi satu sesi mengajar.
    </p>
    <p style="margin-bottom:16px">
      <a href="<?= base_url('master/jadwal/template-grid') ?>" class="btn btn-outline btn-sm"><svg class="icon-sm"><use href="#i-download"/></svg> Unduh template grid</a>
    </p>
    <form method="post" action="<?= base_url('master/jadwal/import-grid') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="file_excel_grid">File Excel (.xlsx / .xls / .csv)</label>
        <input type="file" id="file_excel_grid" name="file_excel_grid" accept=".xlsx,.xls,.csv" required>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalImportGrid')">Batal</button>
        <button type="submit" class="btn btn-primary">Import sekarang</button>
      </div>
    </form>
  </div>
</div>

<script>
const dataPengampuPerTingkatGrid = <?= json_encode($pengampuPerTingkat) ?>;
const dataKelasTingkatGrid = <?= json_encode(array_column($kelasList, 'tingkat', 'id')) ?>;

function isiOpsiPengampuGrid(selectEl, tingkat, nilaiTerpilih) {
  const daftar = dataPengampuPerTingkatGrid[tingkat] || [];
  if (daftar.length === 0) {
    selectEl.innerHTML = '<option value="">Belum ada Guru Pengampu utk tingkat ' + (tingkat || '-') + '</option>';
    return;
  }
  selectEl.innerHTML = '<option value="">-- pilih guru pengampu --</option>' +
    daftar.map(function (p) { return '<option value="' + p.id + '">' + p.label + '</option>'; }).join('');
  if (nilaiTerpilih) { selectEl.value = nilaiTerpilih; }
}

function bukaTambahGrid(jamKe, kelasId, namaKelas, tingkat, jamKeSelesai) {
  document.getElementById('tg_kelas').value = kelasId;
  document.getElementById('tg_jam_mulai').value = jamKe;
  document.getElementById('tg_jam_selesai').value = jamKeSelesai || jamKe;
  isiOpsiPengampuGrid(document.getElementById('tg_guru_pengampu'), tingkat, null);
  const infoRentang = jamKeSelesai && jamKeSelesai != jamKe ? ('ke-' + jamKe + ' s/d ke-' + jamKeSelesai) : ('ke-' + jamKe);
  document.getElementById('infoSelTambah').textContent = '<?= esc($hariAktif) ?>, jam ' + infoRentang + ', kelas ' + namaKelas + ' (tingkat ' + tingkat + ').';
  openModal('modalTambahGrid');
}

function bukaEditGrid(cell, jamKeKlik, rowspan) {
  document.getElementById('eg_id').value = cell.id;
  document.getElementById('eg_kelas').value = cell.kelas_id;
  document.getElementById('eg_jam_mulai').value = cell.jam_ke_mulai;
  document.getElementById('eg_jam_selesai').value = cell.jam_ke_selesai;

  const tingkat = dataKelasTingkatGrid[cell.kelas_id] || '';
  isiOpsiPengampuGrid(document.getElementById('eg_guru_pengampu'), tingkat, cell.guru_pengampu_id);
  document.getElementById('eg_pengampu_warning').style.display = cell.guru_pengampu_id ? 'none' : '';

  document.getElementById('infoSelEdit').textContent = '<?= esc($hariAktif) ?>, jam ke-' + cell.jam_ke_mulai + (rowspan > 1 ? ' s/d ke-' + cell.jam_ke_selesai : '') + '.';
  document.getElementById('formHapusGrid').action = '<?= base_url('master/jadwal/delete/') ?>' + cell.id;
  openModal('modalEditGrid');
}

<?php if ($adaPengampu) : ?>
// ---- Blok-pilih rentang jam (klik-tahan-seret) — SENGAJA hanya bergerak
// VERTIKAL dalam SATU kolom kelas yang sama (bukan horizontal ala Kalender
// Akademik), karena satu sesi jadwal cuma utk SATU kelas, tidak masuk akal
// menyeret melintasi kolom. Kalau jari/mouse keluar dari kolom asal atau
// menyentuh sel yang sudah terisi, seleksi berhenti meluas di titik itu
// (tidak "melompati" sel terisi).
(function () {
  let sedangMenyeleksi = false;
  let selKelasId = null;
  let jamAwal = null;

  function sorotRentangGrid(kelasId, dariJamKe, sampaiJamKe) {
    const awal = Math.min(dariJamKe, sampaiJamKe);
    const akhir = Math.max(dariJamKe, sampaiJamKe);
    document.querySelectorAll('.grid-cell-empty').forEach(function (cell) {
      const cocokKolom = cell.dataset.kelasId === String(kelasId);
      const jk = parseInt(cell.dataset.jamKe, 10);
      cell.classList.toggle('is-selecting', cocokKolom && jk >= awal && jk <= akhir);
    });
  }

  function selesaiSeleksiGrid() {
    if (! sedangMenyeleksi) { return; }
    sedangMenyeleksi = false;
    const terpilih = Array.from(document.querySelectorAll('.grid-cell-empty.is-selecting'));
    document.querySelectorAll('.grid-cell-empty.is-selecting').forEach(function (c) { c.classList.remove('is-selecting'); });
    if (terpilih.length === 0) { return; }
    const jamKeList = terpilih.map(function (c) { return parseInt(c.dataset.jamKe, 10); }).sort(function (a, b) { return a - b; });
    const contoh = terpilih[0];
    bukaTambahGrid(jamKeList[0], selKelasId, contoh.dataset.namaKelas, contoh.dataset.tingkat, jamKeList[jamKeList.length - 1]);
  }

  document.querySelectorAll('.grid-cell-empty').forEach(function (cell) {
    cell.addEventListener('mousedown', function (e) {
      sedangMenyeleksi = true;
      selKelasId = cell.dataset.kelasId;
      jamAwal = parseInt(cell.dataset.jamKe, 10);
      sorotRentangGrid(selKelasId, jamAwal, jamAwal);
      e.preventDefault();
    });
    cell.addEventListener('mouseenter', function () {
      if (! sedangMenyeleksi) { return; }
      // Batasi HANYA kalau masih di kolom kelas yang sama; kalau mouse
      // "bocor" ke kolom lain, sorotan tidak ikut meluas ke situ.
      if (cell.dataset.kelasId !== selKelasId) { return; }
      sorotRentangGrid(selKelasId, jamAwal, parseInt(cell.dataset.jamKe, 10));
    });
    cell.addEventListener('touchstart', function () {
      sedangMenyeleksi = true;
      selKelasId = cell.dataset.kelasId;
      jamAwal = parseInt(cell.dataset.jamKe, 10);
      sorotRentangGrid(selKelasId, jamAwal, jamAwal);
    }, { passive: true });
  });

  document.addEventListener('touchmove', function (e) {
    if (! sedangMenyeleksi) { return; }
    const titik = e.touches[0];
    const el = document.elementFromPoint(titik.clientX, titik.clientY);
    const sel = el ? el.closest('.grid-cell-empty') : null;
    if (sel && sel.dataset.kelasId === selKelasId) {
      sorotRentangGrid(selKelasId, jamAwal, parseInt(sel.dataset.jamKe, 10));
    }
  }, { passive: true });

  document.addEventListener('mouseup', selesaiSeleksiGrid);
  document.addEventListener('touchend', selesaiSeleksiGrid);
})();
<?php endif; ?>

// ---- Submit AJAX utk Tambah/Edit/Hapus grid — TIDAK reload halaman &amp;
// TIDAK kembali ke tampilan daftar, tetap di grid supaya bisa langsung
// lanjut input jadwal berikutnya tanpa harus klik "Tampilan grid" lagi
// tiap kali selesai simpan satu sesi.
// ---- Perbarui token CSRF di SEMUA form di halaman ini — CI4 meregenerasi
// token di setiap request yang lolos validasi CSRF awal (baik hasil akhirnya
// sukses maupun gagal karena alasan lain, mis. bentrok jadwal). Tanpa ini,
// submit KEDUA dst akan pakai token basi dan ditolak diam-diam.
function perbaruiTokenCsrf(nama, hash) {
  if (! nama || ! hash) { return; }
  document.querySelectorAll('input[type="hidden"]').forEach(function (input) {
    if (input.name === nama) {
      input.value = hash;
    }
  });
}

function submitGridAjax(form, tombolSubmit) {
  const teksAsli = tombolSubmit ? tombolSubmit.textContent : '';
  if (tombolSubmit) {
    tombolSubmit.disabled = true;
    tombolSubmit.textContent = 'Menyimpan...';
  }

  fetch(form.action, {
    method: 'POST',
    body: new FormData(form),
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    // Ambil sbg teks dulu (SELALU berhasil apa pun isinya), baru coba parse
    // JSON manual — supaya kalau server ternyata balas HTML/error (bukan
    // JSON yg diharapkan), teksnya kelihatan di console utk didiagnosis,
    // bukan cuma gagal diam-diam kaya sebelumnya.
    .then(function (res) {
      return res.text().then(function (teks) {
        let data;
        try {
          data = JSON.parse(teks);
        } catch (parseErr) {
          console.error('Respons server bukan JSON valid:', teks);
          throw new Error('Respons server tidak valid.');
        }

        return data;
      });
    })
    .then(function (data) {
      perbaruiTokenCsrf(data.csrfName, data.csrfHash);
      if (data.success) {
        if (data.html) {
          document.getElementById('gridTableWrap').innerHTML = data.html;
        }
        tampilkanToast(data.message, 'success');
        closeModal('modalTambahGrid');
        closeModal('modalEditGrid');
      } else {
        tampilkanToast(data.message || 'Gagal menyimpan.', 'error');
      }
    })
    .catch(function (err) {
      console.error('Gagal submit grid:', err);
      tampilkanToast('Gagal menyimpan. Coba muat ulang halaman lalu ulangi.', 'error');
    })
    .finally(function () {
      if (tombolSubmit) {
        tombolSubmit.disabled = false;
        tombolSubmit.textContent = teksAsli;
      }
    });
}

document.getElementById('formTambahGrid').addEventListener('submit', function (e) {
  e.preventDefault();
  submitGridAjax(this, this.querySelector('button[type="submit"]'));
});
document.getElementById('formEditGrid').addEventListener('submit', function (e) {
  e.preventDefault();
  submitGridAjax(this, this.querySelector('button[type="submit"]'));
});
document.getElementById('formHapusGrid').addEventListener('submit', function (e) {
  e.preventDefault();
  const form = this;
  const tombol = form.querySelector('button[type="submit"]');
  konfirmasiAksi('Hapus jadwal ini? Tindakan ini tidak bisa dibatalkan.', function () {
    submitGridAjax(form, tombol);
  });
});
</script>
