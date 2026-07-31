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

<form method="post" action="<?= base_url($formAction ?? ('mengajar/jurnal/' . $jadwal['id'])) ?>">
  <?= csrf_field() ?>
  <div class="card">
    <div class="form-group">
      <label for="materi">Materi <span style="color:var(--color-danger)">*</span></label>
      <input type="text" id="materi" name="materi" required maxlength="255"
        value="<?= esc($jurnal['materi'] ?? '') ?>" placeholder="Contoh: Persamaan linear satu variabel">
    </div>

    <?php
    $tpSekarang = $jurnal['tujuan_pembelajaran'] ?? '';
    $tpCocokDaftar = $tpSekarang !== '' && in_array($tpSekarang, array_column($daftarTP ?? [], 'teks'), true);
    $tpPakaiLainnya = $tpSekarang !== '' && ! $tpCocokDaftar;
    ?>
    <div class="form-group">
      <label for="tujuan_pembelajaran_select">Tujuan pembelajaran</label>
      <?php if (empty($daftarTP)) : ?>
        <textarea id="tujuan_pembelajaran" name="tujuan_pembelajaran" rows="2" placeholder="Tulis TP untuk sesi ini..."><?= esc($tpSekarang) ?></textarea>
        <div class="form-hint">Belum ada Master TP untuk mapel ini — <a href="<?= base_url('tujuan-pembelajaran') ?>">kelola Master TP</a> supaya bisa pilih dari daftar mulai sesi berikutnya.</div>
      <?php else : ?>
        <select id="tujuan_pembelajaran_select" onchange="tpPilihDariDaftar(this)" style="<?= $tpPakaiLainnya ? 'display:none' : '' ?>">
          <option value="">— pilih TP —</option>
          <?php foreach ($daftarTP as $tp) : ?>
            <option value="<?= esc($tp['teks']) ?>" <?= $tpSekarang === $tp['teks'] ? 'selected' : '' ?>><?= esc($tp['teks']) ?></option>
          <?php endforeach; ?>
          <option value="__lainnya__" <?= $tpPakaiLainnya ? 'selected' : '' ?>>Lainnya (tulis manual)…</option>
        </select>
        <textarea id="tujuan_pembelajaran" name="tujuan_pembelajaran" rows="2" placeholder="Tulis TP untuk sesi ini..."
                  style="<?= $tpPakaiLainnya ? '' : 'display:none' ?>"><?= esc($tpSekarang) ?></textarea>
        <div class="form-hint"><a href="<?= base_url('tujuan-pembelajaran') ?>">Kelola Master TP</a> kalau TP yang Anda cari belum ada di daftar.</div>
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
    <button type="submit" class="btn btn-primary"><svg class="icon-sm" style="stroke:#fff"><use href="#i-check-circle"/></svg> Simpan &amp; selesaikan sesi mengajar</button>
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
