<div class="page-header">
  <h1><svg class="icon"><use href="#i-file-text"/></svg> Isi jurnal terlewat</h1>
  <p class="text-muted">
    <?= esc($jadwal['nama_mapel']) ?> — kelas <?= esc($jadwal['nama_kelas']) ?> —
    <?= esc(date('d-m-Y', strtotime($presensi['tanggal']))) ?>
  </p>
</div>

<div class="alert alert-danger" style="background:var(--color-warning-soft);color:var(--color-warning)">
  <svg class="icon-sm"><use href="#i-alert"/></svg>
  Presensi untuk sesi ini sudah tercatat, tapi jurnalnya belum pernah diisi. Lengkapi di bawah
  supaya sesi ini tercatat selesai.
</div>

<form method="post" action="<?= base_url('mengajar/riwayat/isi-jurnal/' . $presensi['id']) ?>">
  <?= csrf_field() ?>
  <div class="card">
    <div class="form-group">
      <label for="materi">Materi <span style="color:var(--color-danger)">*</span></label>
      <input type="text" id="materi" name="materi" required maxlength="255" placeholder="Contoh: Bab 3 - Persamaan Linear">
    </div>

    <div class="form-group">
      <label for="tujuan_pembelajaran">Tujuan pembelajaran</label>
      <textarea id="tujuan_pembelajaran" name="tujuan_pembelajaran" rows="2"></textarea>
    </div>

    <div class="form-group">
      <label for="kegiatan_pembelajaran">Kegiatan pembelajaran</label>
      <textarea id="kegiatan_pembelajaran" name="kegiatan_pembelajaran" rows="4" placeholder="Ceritakan singkat jalannya pembelajaran hari ini..."></textarea>
    </div>

    <div class="form-row form-divider">
      <div class="form-group">
        <label for="kendala">Kendala</label>
        <textarea id="kendala" name="kendala" rows="2" placeholder="Kosongkan jika tidak ada"></textarea>
      </div>
      <div class="form-group">
        <label for="tindak_lanjut">Tindak lanjut</label>
        <textarea id="tindak_lanjut" name="tindak_lanjut" rows="2" placeholder="Kosongkan jika tidak ada"></textarea>
      </div>
    </div>

    <div class="form-group" style="margin-bottom:0">
      <label for="catatan">Catatan tambahan</label>
      <textarea id="catatan" name="catatan" rows="2" placeholder="Opsional"></textarea>
    </div>
  </div>

  <div class="form-actions sticky-action-mobile">
    <a href="<?= base_url('mengajar/riwayat') ?>" class="btn btn-outline">Batal</a>
    <button type="submit" class="btn btn-primary"><svg class="icon-sm" style="stroke:#fff"><use href="#i-check-circle"/></svg> Simpan jurnal</button>
  </div>
</form>
