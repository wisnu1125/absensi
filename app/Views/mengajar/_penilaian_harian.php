<?php
// Partial ini butuh: $daftarSiswa (array siswa kelas ini), $penilaianTersimpan
// (array keyed siswa_id, kalau sedang revisi jurnal yang sudah pernah dinilai).
$penilaianTersimpan ??= [];
$adaPenilaianTersimpan = ! empty($penilaianTersimpan);

$opsiJenis = ['Keaktifan', 'Bertanya', 'Menjawab pertanyaan', 'Presentasi', 'Praktik', 'Penugasan', 'Ulangan harian', 'Hafalan', 'Sikap', 'Kedisiplinan'];
$opsiNilai = ['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D'];

/**
 * Cetak <select> + fallback teks bebas "Lainnya..." — dipilih "Lainnya" maka
 * select disembunyikan & dinonaktifkan (supaya TIDAK ikut ter-submit), dan
 * input teks yang tampil (dengan NAME SAMA) yang menggantikannya. Jadi di
 * sisi server, $_POST-nya SELALU nilai akhir yang benar tanpa perlu logika
 * tambahan buat membedakan asalnya dari dropdown atau ketikan bebas.
 */
if (! function_exists('cetakSelectPenilaian')) {
    function cetakSelectPenilaian(string $name, array $opsi, ?string $nilaiSekarang, string $placeholder): void
    {
        $nilaiSekarang = $nilaiSekarang ?? '';
        $cocokOpsi     = $nilaiSekarang !== '' && in_array($nilaiSekarang, $opsi, true);
        $pakaiLainnya  = $nilaiSekarang !== '' && ! $cocokOpsi;
        ?>
        <div class="pnl-field-wrap">
          <select name="<?= esc($name) ?>" class="pnl-select" onchange="pnlToggleLainnya(this)" style="<?= $pakaiLainnya ? 'display:none' : '' ?>" <?= $pakaiLainnya ? 'disabled' : '' ?>>
            <option value="">—</option>
            <?php foreach ($opsi as $o) : ?>
              <option value="<?= esc($o) ?>" <?= $nilaiSekarang === $o ? 'selected' : '' ?>><?= esc($o) ?></option>
            <?php endforeach; ?>
            <option value="__lainnya__" <?= $pakaiLainnya ? 'selected' : '' ?>>Lainnya…</option>
          </select>
          <input type="text" name="<?= esc($name) ?>" class="pnl-input-lainnya" placeholder="<?= esc($placeholder) ?>"
                 value="<?= esc($pakaiLainnya ? $nilaiSekarang : '') ?>"
                 style="<?= $pakaiLainnya ? '' : 'display:none' ?>" <?= $pakaiLainnya ? '' : 'disabled' ?>>
        </div>
        <?php
    }
}
?>
<div class="pnl-section">
  <div class="pnl-header">
    <div class="pnl-header-icon"><svg class="icon"><use href="#i-cap"/></svg></div>
    <div style="flex:1;min-width:0">
      <div class="pnl-title">Penilaian harian <span class="pnl-badge-baru">Baru</span> <span class="pnl-badge-opsional">Opsional</span></div>
      <p class="pnl-desc">Catat siswa yang aktif, bertanya, presentasi, dst. Tidak perlu isi semua siswa — kosongkan yang tidak dinilai hari ini.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" id="btnTogglePenilaian" onclick="togglePenilaianHarian()" style="flex-shrink:0">
      <svg class="icon-sm" style="stroke:#fff" id="iconTogglePenilaian"><use href="#i-plus"/></svg> <span id="labelTogglePenilaian"><?= $adaPenilaianTersimpan ? 'Lihat penilaian' : 'Tambah penilaian' ?></span>
    </button>
  </div>

  <div id="blokPenilaianHarian" style="<?= $adaPenilaianTersimpan ? '' : 'display:none' ?>">
    <?php if (empty($daftarSiswa)) : ?>
      <p class="text-muted" style="font-size:12.5px;padding:14px 16px 16px">Belum ada data siswa aktif di kelas ini.</p>
    <?php else : ?>
      <div class="pnl-counter" id="pnlCounter">
        <svg class="icon-sm"><use href="#i-check-circle"/></svg>
        <span id="pnlCounterText">0 dari <?= esc(count($daftarSiswa)) ?> siswa dinilai</span>
      </div>
      <div class="table-wrap table-responsive-cards" style="margin:0 16px 16px;width:auto">
        <table class="table pnl-table">
          <thead><tr>
            <th style="width:40px">No.</th><th>NIS</th><th>Nama siswa</th>
            <th style="min-width:150px">Jenis penilaian</th><th style="width:110px">Nilai</th><th>Catatan</th>
          </tr></thead>
          <tbody>
            <?php foreach ($daftarSiswa as $i => $s) :
              $ada = $penilaianTersimpan[$s['id']] ?? null;
            ?>
              <tr class="pnl-row<?= $ada ? ' is-filled' : '' ?>" data-pnl-row>
                <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
                <td data-label="NIS"><?= esc($s['nis']) ?></td>
                <td class="td-card-title"><?= esc($s['nama']) ?></td>
                <td data-label="Jenis penilaian"><?php cetakSelectPenilaian('penilaian[' . $s['id'] . '][jenis_penilaian]', $opsiJenis, $ada['jenis_penilaian'] ?? null, 'Tulis jenis penilaian'); ?></td>
                <td data-label="Nilai"><?php cetakSelectPenilaian('penilaian[' . $s['id'] . '][nilai]', $opsiNilai, $ada['nilai'] ?? null, 'Nilai'); ?></td>
                <td data-label="Catatan">
                  <input type="text" name="penilaian[<?= esc($s['id']) ?>][catatan]"
                         value="<?= esc($ada['catatan'] ?? '') ?>" placeholder="Opsional">
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="form-hint" style="margin:0 16px 16px">Baris yang jenis penilaian atau nilainya dikosongkan tidak akan tersimpan sebagai data penilaian — bukan berarti siswa itu bernilai 0.</p>
    <?php endif; ?>
  </div>
</div>

<script>
function togglePenilaianHarian() {
  const blok = document.getElementById('blokPenilaianHarian');
  const label = document.getElementById('labelTogglePenilaian');
  const icon = document.getElementById('iconTogglePenilaian');
  const tampil = blok.style.display === 'none';
  blok.style.display = tampil ? '' : 'none';
  label.textContent = tampil ? 'Sembunyikan' : (pnlHitungTerisi() > 0 ? 'Lihat penilaian' : 'Tambah penilaian');
  icon.innerHTML = '<use href="#' + (tampil ? 'i-close' : 'i-plus') + '"/>';
}

function pnlToggleLainnya(select) {
  if (select.value !== '__lainnya__') { return; }
  const input = select.parentElement.querySelector('.pnl-input-lainnya');
  select.style.display = 'none';
  select.disabled = true;
  input.style.display = '';
  input.disabled = false;
  input.value = '';
  input.focus();
}

// Hitung & tampilkan berapa siswa yang sudah punya penilaian lengkap (jenis
// DAN nilai sama-sama terisi) -- update langsung tiap kali guru mengetik,
// supaya guru dapat umpan balik jelas tanpa perlu submit dulu.
function pnlNilaiField(td, cls) {
  const select = td.querySelector('select.' + cls);
  const input = td.querySelector('input.' + cls);
  if (select && select.style.display !== 'none') { return select.value; }
  if (input && input.style.display !== 'none') { return input.value.trim(); }
  return '';
}

function pnlHitungTerisi() {
  let jumlah = 0;
  document.querySelectorAll('[data-pnl-row]').forEach(function (row) {
    const tds = row.querySelectorAll('td');
    const jenis = pnlNilaiField(tds[3], 'pnl-select') || pnlNilaiField(tds[3], 'pnl-input-lainnya');
    const nilai = pnlNilaiField(tds[4], 'pnl-select') || pnlNilaiField(tds[4], 'pnl-input-lainnya');
    const terisi = jenis !== '' && jenis !== '__lainnya__' && nilai !== '' && nilai !== '__lainnya__';
    row.classList.toggle('is-filled', terisi);
    if (terisi) { jumlah++; }
  });
  const counterText = document.getElementById('pnlCounterText');
  const total = document.querySelectorAll('[data-pnl-row]').length;
  if (counterText) { counterText.textContent = jumlah + ' dari ' + total + ' siswa dinilai'; }
  return jumlah;
}

document.querySelectorAll('[data-pnl-row]').forEach(function (row) {
  row.addEventListener('input', pnlHitungTerisi);
  row.addEventListener('change', pnlHitungTerisi);
});
pnlHitungTerisi();
</script>
