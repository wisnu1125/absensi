<?php
$todayIso = date('Y-m-d');
// Hitung ringkasan minggu ini sekali di awal (dipakai pill hitungan di toolbar).
$ringkasan = ['selesai' => 0, 'jurnal_kosong' => 0, 'terlewat' => 0, 'berlangsung' => 0, 'belum' => 0];
$jumlahGantian = 0;
foreach ($grid as $baris) {
    foreach ($baris as $cell) {
        if (! is_array($cell)) {
            continue;
        }
        if (isset($ringkasan[$cell['status_sel'] ?? ''])) {
            $ringkasan[$cell['status_sel']]++;
        }
        if (($cell['status_sel'] ?? '') === 'digantikan' || ! empty($cell['menggantikan'])) {
            $jumlahGantian++;
        }
    }
}
$belumDiisi = $ringkasan['jurnal_kosong'] + $ringkasan['berlangsung'];
$akanDatang = 0;
foreach ($grid as $baris) {
    foreach ($baris as $cell) {
        if (is_array($cell) && ($cell['status_sel'] ?? '') === 'belum' && $cell['tanggal'] > $todayIso) {
            $akanDatang++;
        }
    }
}
?>
<div class="page-header">
  <h1><svg class="icon"><use href="#i-calendar"/></svg> Kalender jadwal</h1>
  <p class="text-muted"><?= esc($aktif['nama_tahun_ajaran']) ?> — Semester <?= esc($aktif['nama']) ?>. Klik jadwal untuk melihat/melengkapi detailnya.</p>
</div>

<div class="toolbar" style="row-gap:10px">
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <?php if ($mingguSebelumnyaValid) : ?>
      <a href="<?= base_url('mengajar/kalender?awal=' . date('Y-m-d', strtotime($awalMinggu . ' -7 days'))) ?>" class="kal-nav-btn" title="Minggu sebelumnya">&larr;</a>
    <?php else : ?>
      <span class="kal-nav-btn is-disabled" title="Di luar tanggal berlaku semester">&larr;</span>
    <?php endif; ?>
    <?php if ($mingguBerikutnyaValid) : ?>
      <a href="<?= base_url('mengajar/kalender?awal=' . date('Y-m-d', strtotime($awalMinggu . ' +7 days'))) ?>" class="kal-nav-btn" title="Minggu berikutnya">&rarr;</a>
    <?php else : ?>
      <span class="kal-nav-btn is-disabled" title="Di luar tanggal berlaku semester">&rarr;</span>
    <?php endif; ?>
    <span style="font-size:14px;font-weight:700;color:var(--color-text)">
      <?= esc(date('d M', strtotime($awalMinggu))) ?> – <?= esc(date('d M Y', strtotime($akhirMinggu))) ?>
    </span>
    <?php if (! $iniMinggu) : ?>
      <a href="<?= base_url('mengajar/kalender') ?>" class="btn btn-outline btn-sm">Hari ini</a>
    <?php endif; ?>
  </div>
  <div style="display:flex;gap:6px;flex-wrap:wrap">
    <span class="kal-count-pill"><span class="kal-count-dot" style="background:var(--color-success)"></span><?= esc($ringkasan['selesai']) ?> Selesai</span>
    <span class="kal-count-pill"><span class="kal-count-dot" style="background:var(--color-warning)"></span><?= esc($belumDiisi) ?> Belum diisi</span>
    <span class="kal-count-pill"><span class="kal-count-dot" style="background:var(--color-danger)"></span><?= esc($ringkasan['terlewat']) ?> Terlewat</span>
    <span class="kal-count-pill"><span class="kal-count-dot" style="background:var(--color-primary)"></span><?= esc($akanDatang) ?> Akan datang</span>
    <?php if ($jumlahGantian > 0) : ?>
      <span class="kal-count-pill"><span class="kal-count-dot" style="background:var(--color-substitute)"></span><?= esc($jumlahGantian) ?> Tukar/Gantian</span>
    <?php endif; ?>
  </div>
</div>

<?php if (! $aktif['tanggal_mulai'] || ! $aktif['tanggal_selesai']) : ?>
  <div class="alert alert-danger" style="background:var(--color-warning-soft);color:var(--color-warning);margin-bottom:14px">
    <svg class="icon-sm"><use href="#i-alert"/></svg>
    Semester aktif belum diisi tanggal berlakunya — hubungi Admin di menu Tahun Ajaran &amp; Semester.
  </div>
<?php endif; ?>

<div class="table-wrap" id="kalenderWrap" style="position:relative">
  <table class="table" style="min-width:820px;border-collapse:separate;border-spacing:0">
    <thead>
      <tr>
        <th style="width:90px;color:var(--color-text)">Jam ke-</th>
        <?php foreach ($hariList as $h) :
          $isToday = $tanggalHari[$h] === $todayIso;
        ?>
          <th class="<?= $isToday ? 'is-today-col' : '' ?>" style="text-align:center;color:<?= $isToday ? 'var(--color-primary)' : 'var(--color-text)' ?>">
            <?= esc($h) ?><?= $isToday ? ' •' : '' ?>
            <div style="font-weight:400;font-size:11px;color:var(--navy-600);margin-top:1px"><?= esc(date('d M', strtotime($tanggalHari[$h]))) ?></div>
          </th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php
      // Peta jam_ke -> jam mulai/selesai HARI INI SAJA, dipakai garis "sekarang" di JS.
      $jamHariIniPeta = [];
      foreach ($jamHariIni as $jh) {
          $jamHariIniPeta[$jh['jam_ke']] = ['mulai' => substr($jh['jam_mulai'], 0, 5), 'selesai' => substr($jh['jam_selesai'], 0, 5)];
      }
      ?>
      <?php foreach ($jamKeList as $jamKe) :
        $todayTime = $jamHariIniPeta[$jamKe] ?? null;
      ?>
        <tr <?= $todayTime ? 'data-mulai="' . esc($todayTime['mulai']) . '" data-selesai="' . esc($todayTime['selesai']) . '"' : '' ?>>
          <td style="white-space:nowrap;vertical-align:top;padding-top:12px;color:var(--color-text);font-weight:600;font-size:12.5px">
            Ke-<?= esc($jamKe) ?>
          </td>
          <?php foreach ($hariList as $h) :
            $isToday = $tanggalHari[$h] === $todayIso;
            $cell = $grid[$jamKe][$h] ?? null;
            if ($cell === 'lanjutan') { continue; } // sudah tercakup rowspan sel di atasnya

            if (is_array($cell)) {
                $status = $cell['status_sel'] ?? 'akan_datang';

                $tema = [
                    'selesai'       => ['accent' => 'var(--color-success)', 'soft' => 'var(--color-success-soft)', 'icon' => 'i-check-circle'],
                    'jurnal_kosong' => ['accent' => 'var(--color-warning)', 'soft' => 'var(--color-warning-soft)', 'icon' => 'i-file-text'],
                    'berlangsung'   => ['accent' => 'var(--color-warning)', 'soft' => 'var(--color-warning-soft)', 'icon' => 'i-clock'],
                    'terlewat'      => ['accent' => 'var(--color-danger)', 'soft' => 'var(--color-danger-soft)', 'icon' => 'i-alert'],
                    'belum'         => ['accent' => 'var(--color-primary)', 'soft' => 'var(--color-primary-soft)', 'icon' => 'i-clock'],
                    'akan_datang'   => ['accent' => 'var(--color-primary)', 'soft' => 'var(--color-primary-soft)', 'icon' => 'i-clock'],
                ][$status] ?? ['accent' => 'var(--color-primary)', 'soft' => 'var(--color-primary-soft)', 'icon' => 'i-clock'];

                // Sesi yang sedang MENGGANTIKAN guru lain ditandai warna ungu tersendiri
                // (bukan warna status selesai/belum biasa) — supaya kelihatan beda dari
                // sekilas bahwa ini bukan jadwal rutin, sambil tetap menampilkan ikon
                // status penyelesaian yang sebenarnya (selesai/belum/terlewat).
                $adalahGantian = ! empty($cell['menggantikan']);
                if ($adalahGantian) {
                    $tema['accent'] = 'var(--color-substitute)';
                    $tema['soft']   = 'var(--color-substitute-soft)';
                }

                $tautan = match ($status) {
                    'selesai'       => isset($cell['presensi_id']) ? base_url('mengajar/riwayat/detail/' . $cell['presensi_id']) : null,
                    'jurnal_kosong' => isset($cell['presensi_id']) ? base_url('mengajar/riwayat/isi-jurnal/' . $cell['presensi_id']) : null,
                    'terlewat'      => base_url('mengajar/riwayat/hari-terlewat/presensi/' . $cell['id'] . '/' . $cell['tanggal']),
                    'berlangsung'   => base_url('mengajar/jurnal/' . $cell['id']),
                    'belum'         => base_url('mengajar/presensi/' . $cell['id']),
                    default         => null,
                };
            }
          ?>
            <?php if (is_array($cell) && $status === 'digantikan') : ?>
              <td rowspan="<?= esc($cell['rowspan']) ?>" class="kal-cell-wrap<?= $isToday ? ' is-today-col' : '' ?>">
                <div class="kal-card" style="border-left-color:var(--color-substitute)">
                  <span class="kal-card-icon" style="color:var(--color-substitute);background:var(--color-substitute-soft)"><svg><use href="#i-users"/></svg></span>
                  <div class="kal-card-title"><?= esc($cell['nama_mapel']) ?></div>
                  <div class="kal-card-sub"><?= esc($cell['nama_kelas']) ?></div>
                  <div class="kal-card-note" style="color:var(--color-substitute)">Digantikan <?= esc($cell['digantikan']) ?></div>
                </div>
              </td>
            <?php elseif (is_array($cell)) : ?>
              <td rowspan="<?= esc($cell['rowspan']) ?>" class="kal-cell-wrap<?= $isToday ? ' is-today-col' : '' ?>">
                <?php if ($cell['rowspan'] > 1) : ?>
                  <div class="kal-span-line" style="background:<?= esc($tema['accent']) ?>"></div>
                  <div class="kal-span-label" style="color:<?= esc($tema['accent']) ?>">s/d Ke-<?= esc($cell['jam_ke_selesai'] ?? '') ?></div>
                <?php endif; ?>
                <div class="kal-card<?= $tautan ? ' is-clickable' : '' ?>"
                     style="border-left-color:<?= esc($tema['accent']) ?>"
                     <?= $tautan ? 'onclick="window.location.href=\'' . $tautan . '\'" tabindex="0" role="button"' : '' ?>>
                  <?php if ($tema['icon']) : ?>
                    <span class="kal-card-icon" style="color:<?= esc($tema['accent']) ?>;background:<?= esc($tema['soft']) ?>"><svg><use href="#<?= esc($tema['icon']) ?>"/></svg></span>
                  <?php endif; ?>
                  <div class="kal-card-title"><?= esc($cell['nama_mapel']) ?></div>
                  <div class="kal-card-sub"><?= esc($cell['nama_kelas']) ?></div>
                  <?php if ($adalahGantian) : ?>
                    <div class="kal-card-note" style="color:var(--color-substitute)">Menggantikan <?= esc($cell['menggantikan']) ?></div>
                  <?php endif; ?>
                  <?php if (! empty($cell['ditukar_slot'])) : ?>
                    <div class="kal-card-note" style="color:var(--color-warning)">Pindah dari <?= esc($cell['hari_asli']) ?></div>
                  <?php endif; ?>
                </div>
              </td>
            <?php else : ?>
              <td class="kal-cell-empty<?= $isToday ? ' is-today-col' : '' ?>"></td>
            <?php endif; ?>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if (empty($jamKeList)) : ?>
  <div class="empty-state">
    <h3>Belum ada data jam pelajaran</h3>
    <p class="text-muted">Hubungi administrator untuk mengatur jam pelajaran terlebih dahulu.</p>
  </div>
<?php endif; ?>

<script>
document.querySelectorAll('.kal-card.is-clickable').forEach(function (card) {
  card.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      card.click();
    }
  });
});

<?php if ($iniMinggu) : ?>
// Garis "sekarang" — cuma digambar kalau minggu yang dilihat memuat hari ini.
// Posisinya dihitung dari baris <tr> yang jam-nya mencakup waktu sekarang,
// diinterpolasi proporsional di dalam baris itu (bukan cuma nempel di batas atas/bawah).
(function () {
  const now = new Date();
  const menitSekarang = now.getHours() * 60 + now.getMinutes();
  const wrap = document.getElementById('kalenderWrap');
  const rows = wrap.querySelectorAll('tbody tr');
  let posisi = null;

  rows.forEach(function (tr) {
    if (! tr.dataset.mulai || ! tr.dataset.selesai) {
      return; // jam ke- ini tidak ada untuk hari ini, lewati
    }
    const [jm, jh] = tr.dataset.mulai.split(':').map(Number);
    const [sm, sh] = tr.dataset.selesai.split(':').map(Number);
    const mulaiMenit = jm * 60 + jh;
    const selesaiMenit = sm * 60 + sh;
    if (menitSekarang >= mulaiMenit && menitSekarang <= selesaiMenit && selesaiMenit > mulaiMenit) {
      const fraksi = (menitSekarang - mulaiMenit) / (selesaiMenit - mulaiMenit);
      posisi = tr.offsetTop + fraksi * tr.offsetHeight;
    }
  });

  if (posisi !== null) {
    const jamTeks = now.toTimeString().slice(0, 5);
    const line = document.createElement('div');
    line.className = 'kal-now-line';
    line.style.top = posisi + 'px';
    line.innerHTML = '<span class="kal-now-badge">' + jamTeks + '</span>';
    wrap.appendChild(line);
  }
})();
<?php endif; ?>
</script>
