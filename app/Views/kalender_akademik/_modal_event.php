<div class="modal" id="modalEvent">
  <div class="modal-box">
    <h3 id="modalEventJudul">Tambah event</h3>
    <form method="post" id="formEvent" action="<?= base_url('kalender-akademik/store') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="ev_id">

      <div class="form-group">
        <label for="ev_judul">Judul event</label>
        <input type="text" id="ev_judul" name="judul" required maxlength="200" placeholder="Contoh: Penilaian Tengah Semester">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="ev_kategori">Kategori</label>
          <select id="ev_kategori" name="kategori" required>
            <?php foreach ($KATEGORI as $kunci => $k) : ?>
              <option value="<?= esc($kunci) ?>"><?= esc($k['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="ev_status">Status</label>
          <select id="ev_status" name="status">
            <option value="terjadwal">Terjadwal</option>
            <option value="selesai">Selesai</option>
            <option value="dibatalkan">Dibatalkan</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label for="ev_deskripsi">Deskripsi (opsional)</label>
        <textarea id="ev_deskripsi" name="deskripsi" rows="2"></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="ev_mulai">Tanggal mulai</label>
          <input type="date" id="ev_mulai" name="tanggal_mulai" required>
        </div>
        <div class="form-group">
          <label for="ev_selesai">Tanggal selesai</label>
          <input type="date" id="ev_selesai" name="tanggal_selesai">
          <div class="form-hint">Kosongkan kalau event cuma 1 hari.</div>
        </div>
      </div>

      <label class="checkbox-label" style="margin-bottom:10px">
        <input type="checkbox" id="ev_all_day" name="all_day" value="1" checked onchange="toggleJamEvent()"> Sepanjang hari (tidak ada jam spesifik)
      </label>

      <div class="form-row" id="blokJamEvent" style="display:none">
        <div class="form-group">
          <label for="ev_jam_mulai">Jam mulai</label>
          <input type="time" id="ev_jam_mulai" name="jam_mulai">
        </div>
        <div class="form-group">
          <label for="ev_jam_selesai">Jam selesai</label>
          <input type="time" id="ev_jam_selesai" name="jam_selesai">
        </div>
      </div>

      <div class="form-group">
        <label for="ev_dampak">Dampak ke presensi</label>
        <select id="ev_dampak" name="dampak_presensi">
          <option value="normal">Normal — presensi &amp; jurnal tetap wajib diisi</option>
          <option value="nonaktif">Nonaktif — hari ini dianggap libur (mis. kategori Libur/Nasional)</option>
        </select>
      </div>

      <label class="checkbox-label" style="margin-bottom:8px">
        <input type="checkbox" id="ev_recurring_toggle" onchange="toggleRecurring()"> Event berulang mingguan (mis. upacara tiap Senin)
      </label>
      <div class="form-group" id="blokRecurring" style="display:none">
        <label>Ulangi setiap hari</label>
        <div class="checkbox-group">
          <?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h) : ?>
            <label class="checkbox-label"><input type="checkbox" name="recurring_hari[]" value="<?= $h ?>"> <?= $h ?></label>
          <?php endforeach; ?>
        </div>
        <div class="form-hint">Tanggal mulai/selesai di atas jadi rentang periode berulangnya aktif (mis. awal s/d akhir semester).</div>
      </div>

      <div class="modal-actions" style="justify-content:space-between">
        <button type="button" class="btn-icon btn-icon-danger" id="btnHapusEvent" style="display:none" onclick="hapusEvent()"><svg class="icon-sm"><use href="#i-trash"/></svg> Hapus</button>
        <div style="display:flex;gap:10px;margin-left:auto">
          <button type="button" class="btn btn-outline" onclick="closeModal('modalEvent')">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </div>
    </form>
  </div>
</div>

<form method="post" id="formHapusEvent" style="display:none">
  <?= csrf_field() ?>
</form>

<script>
function toggleJamEvent() {
  const allDay = document.getElementById('ev_all_day').checked;
  document.getElementById('blokJamEvent').style.display = allDay ? 'none' : 'grid';
}

function toggleRecurring() {
  document.getElementById('blokRecurring').style.display = document.getElementById('ev_recurring_toggle').checked ? 'block' : 'none';
}

function resetFormEvent() {
  document.getElementById('formEvent').reset();
  document.getElementById('ev_id').value = '';
  document.getElementById('blokJamEvent').style.display = 'none';
  document.getElementById('blokRecurring').style.display = 'none';
  document.querySelectorAll('input[name="recurring_hari[]"]').forEach(function (cb) { cb.checked = false; });
  document.getElementById('btnHapusEvent').style.display = 'none';
  document.getElementById('formEvent').action = '<?= base_url('kalender-akademik/store') ?>';
  document.getElementById('modalEventJudul').textContent = 'Tambah event';
}

function bukaTambahEvent(tanggal, tanggalSelesai) {
  resetFormEvent();
  document.getElementById('ev_mulai').value = tanggal;
  document.getElementById('ev_selesai').value = tanggalSelesai || tanggal;
  openModal('modalEvent');
}

function bukaEditEvent(ev) {
  resetFormEvent();
  document.getElementById('modalEventJudul').textContent = 'Edit event';
  document.getElementById('formEvent').action = '<?= base_url('kalender-akademik/update') ?>';
  document.getElementById('ev_id').value = ev.id;
  document.getElementById('ev_judul').value = ev.judul;
  document.getElementById('ev_kategori').value = ev.kategori;
  document.getElementById('ev_status').value = ev.status;
  document.getElementById('ev_deskripsi').value = ev.deskripsi || '';
  document.getElementById('ev_mulai').value = ev.tanggal_mulai;
  document.getElementById('ev_selesai').value = ev.tanggal_selesai;
  document.getElementById('ev_all_day').checked = ev.all_day == 1;
  document.getElementById('ev_jam_mulai').value = ev.jam_mulai ? ev.jam_mulai.slice(0, 5) : '';
  document.getElementById('ev_jam_selesai').value = ev.jam_selesai ? ev.jam_selesai.slice(0, 5) : '';
  document.getElementById('ev_dampak').value = ev.dampak_presensi;
  toggleJamEvent();
  if (ev.recurring_hari) {
    document.getElementById('ev_recurring_toggle').checked = true;
    toggleRecurring();
    ev.recurring_hari.split(',').forEach(function (h) {
      const cb = document.querySelector('input[name="recurring_hari[]"][value="' + h.trim() + '"]');
      if (cb) { cb.checked = true; }
    });
  }
  document.getElementById('btnHapusEvent').style.display = 'inline-flex';
  openModal('modalEvent');
}

function hapusEvent() {
  const id = document.getElementById('ev_id').value;
  if (! id || ! confirm('Hapus event ini?')) { return; }
  const form = document.getElementById('formHapusEvent');
  form.action = '<?= base_url('kalender-akademik/delete/') ?>' + id;
  form.submit();
}

/**
 * Hapus cepat langsung dari pill di kalender (tombol × kecil), tanpa perlu
 * buka modal edit dulu — dipakai tampilan Bulan, Minggu, dan Agenda.
 * Pakai form yang sama dengan hapusEvent() di atas.
 */
function hapusEventCepat(id, judul) {
  if (! confirm('Hapus event "' + judul + '"?')) { return; }
  const form = document.getElementById('formHapusEvent');
  form.action = '<?= base_url('kalender-akademik/delete/') ?>' + id;
  form.submit();
}
</script>
