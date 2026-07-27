<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-file-text"/></svg> Jurnal mengajar</h1>
  <p class="text-muted">
    <?= esc($jadwal['nama_mapel']) ?> — kelas <?= esc($jadwal['nama_kelas']) ?> —
    <?= esc(date('d-m-Y', strtotime($tanggal))) ?>
  </p>
</div>

<form method="post" action="<?= base_url('mengajar/jurnal/' . $jadwal['id']) ?>">
  <?= csrf_field() ?>
  <div class="card">
    <div class="form-group">
      <label for="materi">Materi <span style="color:var(--color-danger)">*</span></label>
      <input type="text" id="materi" name="materi" required maxlength="255"
        value="<?= esc($jurnal['materi'] ?? '') ?>" placeholder="Contoh: Persamaan linear satu variabel">
    </div>

    <div class="form-group">
      <label for="tujuan_pembelajaran">Tujuan pembelajaran</label>
      <textarea id="tujuan_pembelajaran" name="tujuan_pembelajaran" rows="2"><?= esc($jurnal['tujuan_pembelajaran'] ?? '') ?></textarea>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="metode">Metode</label>
        <input type="text" id="metode" name="metode" maxlength="150" value="<?= esc($jurnal['metode'] ?? '') ?>" placeholder="Contoh: Diskusi kelompok">
      </div>
      <div class="form-group">
        <label for="media">Media</label>
        <input type="text" id="media" name="media" maxlength="150" value="<?= esc($jurnal['media'] ?? '') ?>" placeholder="Contoh: Papan tulis, LKS">
      </div>
    </div>

    <div class="form-group">
      <label for="kegiatan_pembelajaran">Kegiatan pembelajaran</label>
      <textarea id="kegiatan_pembelajaran" name="kegiatan_pembelajaran" rows="3"><?= esc($jurnal['kegiatan_pembelajaran'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
      <label for="kendala">Kendala</label>
      <textarea id="kendala" name="kendala" rows="2" placeholder="Kosongkan jika tidak ada"><?= esc($jurnal['kendala'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
      <label for="tindak_lanjut">Tindak lanjut</label>
      <textarea id="tindak_lanjut" name="tindak_lanjut" rows="2"><?= esc($jurnal['tindak_lanjut'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
      <label for="catatan">Catatan tambahan</label>
      <textarea id="catatan" name="catatan" rows="2"><?= esc($jurnal['catatan'] ?? '') ?></textarea>
    </div>
  </div>

  <div style="margin-top:20px;display:flex;justify-content:flex-end">
    <button type="submit" class="btn btn-primary"><svg class="icon-sm" style="stroke:#fff"><use href="#i-check-circle"/></svg> Simpan &amp; selesaikan sesi mengajar</button>
  </div>
</form>
