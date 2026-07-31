<div class="page-header">
  <h1><svg class="icon"><use href="#i-edit"/></svg> Revisi jurnal</h1>
  <p class="text-muted">
    <?= esc($jadwal['nama_mapel']) ?> — kelas <?= esc($jadwal['nama_kelas']) ?> —
    <?= esc(date('d-m-Y', strtotime($jurnal['tanggal']))) ?>
  </p>
</div>

<div class="alert alert-danger" style="background:var(--color-warning-soft);color:var(--color-warning)">
  <svg class="icon-sm"><use href="#i-alert"/></svg>
  Anda sedang merevisi jurnal yang sudah tersimpan sebelumnya. Perubahan tercatat di audit log.
</div>

<form method="post" action="<?= base_url('mengajar/riwayat/revisi-jurnal/' . $jurnal['id']) ?>">
  <?= csrf_field() ?>
  <div class="card">
    <div class="form-group">
      <label for="materi">Materi <span style="color:var(--color-danger)">*</span></label>
      <input type="text" id="materi" name="materi" required maxlength="255" value="<?= esc($jurnal['materi']) ?>">
    </div>

    <?php
    $tpSekarang = $jurnal['tujuan_pembelajaran'] ?? '';
    $tpCocokDaftar = $tpSekarang !== '' && in_array($tpSekarang, array_column($daftarTP ?? [], 'teks'), true);
    $tpPakaiLainnya = $tpSekarang !== '' && ! $tpCocokDaftar;
    ?>
    <div class="form-group">
      <label for="tujuan_pembelajaran_select">Tujuan pembelajaran</label>
      <?php if (empty($daftarTP)) : ?>
        <textarea id="tujuan_pembelajaran" name="tujuan_pembelajaran" rows="2"><?= esc($tpSekarang) ?></textarea>
        <div class="form-hint">Belum ada Master TP untuk mapel ini — <a href="<?= base_url('tujuan-pembelajaran') ?>">kelola Master TP</a>.</div>
      <?php else : ?>
        <select id="tujuan_pembelajaran_select" onchange="tpPilihDariDaftar(this)" style="<?= $tpPakaiLainnya ? 'display:none' : '' ?>">
          <option value="">— pilih TP —</option>
          <?php foreach ($daftarTP as $tp) : ?>
            <option value="<?= esc($tp['teks']) ?>" <?= $tpSekarang === $tp['teks'] ? 'selected' : '' ?>><?= esc($tp['teks']) ?></option>
          <?php endforeach; ?>
          <option value="__lainnya__" <?= $tpPakaiLainnya ? 'selected' : '' ?>>Lainnya (tulis manual)…</option>
        </select>
        <textarea id="tujuan_pembelajaran" name="tujuan_pembelajaran" rows="2"
                  style="<?= $tpPakaiLainnya ? '' : 'display:none' ?>"><?= esc($tpSekarang) ?></textarea>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="kegiatan_pembelajaran">Kegiatan pembelajaran</label>
      <textarea id="kegiatan_pembelajaran" name="kegiatan_pembelajaran" rows="4" placeholder="Ceritakan singkat jalannya pembelajaran hari ini..."><?= esc($jurnal['kegiatan_pembelajaran'] ?? '') ?></textarea>
    </div>

    <div class="form-row form-divider">
      <div class="form-group">
        <label for="kendala">Kendala</label>
        <textarea id="kendala" name="kendala" rows="2" placeholder="Kosongkan jika tidak ada"><?= esc($jurnal['kendala'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label for="tindak_lanjut">Tindak lanjut</label>
        <textarea id="tindak_lanjut" name="tindak_lanjut" rows="2" placeholder="Kosongkan jika tidak ada"><?= esc($jurnal['tindak_lanjut'] ?? '') ?></textarea>
      </div>
    </div>

    <div class="form-group" style="margin-bottom:0">
      <label for="catatan">Catatan tambahan</label>
      <textarea id="catatan" name="catatan" rows="2" placeholder="Opsional"><?= esc($jurnal['catatan'] ?? '') ?></textarea>
    </div>

    <?= view('mengajar/_penilaian_harian', ['daftarSiswa' => $daftarSiswa ?? [], 'penilaianTersimpan' => $penilaianTersimpan ?? []]) ?>
  </div>

  <div class="form-actions sticky-action-mobile">
    <a href="<?= base_url('mengajar/riwayat/detail/' . ($presensi['id'] ?? '')) ?>" class="btn btn-outline">Batal</a>
    <button type="submit" class="btn btn-primary">Simpan revisi</button>
  </div>
</form>

<script>
function tpPilihDariDaftar(select) {
  const textarea = document.getElementById('tujuan_pembelajaran');
  if (select.value === '__lainnya__') {
    select.style.display = 'none';
    textarea.style.display = '';
    textarea.focus();
    return;
  }
  textarea.value = select.value;
}
</script>
