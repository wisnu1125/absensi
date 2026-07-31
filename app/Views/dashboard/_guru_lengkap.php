<?php
/**
 * Dashboard Guru "lengkap" — dipakai dari _content.php. $ringkasanGuru
 * datang dari Dashboard::ringkasanGuru(), sudah termasuk items hari ini,
 * progress, riwayatTerbaru, pengingat.
 *
 * Catatan desain: semua widget di sini pakai class CSS yang sama
 * (.dash-item-title/.dash-item-meta/.dash-list-item/.mini-stat/
 * .quick-action-*) supaya ukuran font & spacing KONSISTEN antar bagian —
 * sebelumnya banyak style inline dengan angka berbeda-beda tipis yang
 * bikin terasa berantakan.
 */
$items = $ringkasanGuru['hariIni']['items'] ?? [];
$prog  = $ringkasanGuru['progress'];
$peng  = $ringkasanGuru['pengingat'];

// Item yang paling butuh perhatian SEKARANG (utk highlight kartu + tombol
// mengambang) — prioritas: sedang berlangsung (jurnal blm diisi), lalu
// yang belum dimulai paling awal.
$itemAktif = null;
foreach ($items as $it) {
    if ($it['status'] === 'berlangsung') { $itemAktif = $it; break; }
}
if (! $itemAktif) {
    foreach ($items as $it) {
        if ($it['status'] === 'belum') { $itemAktif = $it; break; }
    }
}
?>

<div class="dash-grid-2" style="margin-top:0;grid-template-columns:1fr">
  <div class="card">
    <div class="section-title" style="margin-bottom:var(--space-4)">
      <svg class="icon"><use href="#i-calendar"/></svg>
      <h2 style="flex:1">Jadwal hari ini<?= ! empty($ringkasanGuru['hariIni']['hari']) ? ' — ' . esc($ringkasanGuru['hariIni']['hari']) : '' ?></h2>
      <a href="<?= base_url('mengajar/kalender') ?>" class="btn btn-outline btn-sm">Lihat kalender</a>
    </div>

    <?php if (! empty($ringkasanGuru['hariIni']['liburEvents'])) : ?>
      <div class="empty-state">
        <svg class="icon-lg" style="color:var(--color-success);margin-bottom:8px"><use href="#i-calendar"/></svg>
        <h3>Hari ini libur</h3>
        <?php foreach ($ringkasanGuru['hariIni']['liburEvents'] as $lb) : ?><p><strong><?= esc($lb['judul']) ?></strong></p><?php endforeach; ?>
      </div>
    <?php elseif (empty($items)) : ?>
      <div class="empty-state"><h3>Tidak ada jadwal mengajar hari ini</h3><p>Nikmati harinya!</p></div>
    <?php else : ?>
      <div class="timeline">
        <?php foreach ($items as $j) :
          $dotColor = match ($j['status']) {
              'selesai'     => 'var(--color-success)',
              'berlangsung' => 'var(--color-warning)',
              'digantikan'  => 'var(--color-text-soft)',
              default       => 'var(--color-border-strong)',
          };
        ?>
          <div class="timeline-item">
            <span class="timeline-dot" style="--dot-color:<?= esc($dotColor) ?>"></span>
            <div class="timeline-time"><svg class="icon-sm"><use href="#i-clock"/></svg> <?= esc(substr($j['jam_mulai'], 0, 5)) ?>–<?= esc(substr($j['jam_selesai'], 0, 5)) ?></div>
            <div class="timeline-card<?= $j['status'] === 'digantikan' ? ' is-muted' : '' ?>">
              <div class="card-title" style="margin-bottom:2px"><?= esc($j['nama_kelas']) ?> — <?= esc($j['nama_mapel']) ?></div>

              <?php if (! empty($j['menggantikan'])) : ?>
                <div class="role-badge role-badge-block">Menggantikan <?= esc($j['menggantikan']) ?></div>
              <?php endif; ?>
              <?php if (! empty($j['ditukar'])) : ?>
                <div class="role-badge role-badge-block role-badge-warning">Pindah dari <?= esc($j['hari_asli']) ?> (tukar jadwal)</div>
              <?php endif; ?>

              <?php if ($j['status'] === 'digantikan') : ?>
                <span class="text-soft">Digantikan oleh <?= esc($j['nama_pengganti'] ?? '-') ?></span>
              <?php elseif ($j['status'] === 'selesai') : ?>
                <span class="status-badge status-hadir"><svg class="icon-sm"><use href="#i-check-circle"/></svg> Selesai</span>
              <?php elseif ($j['status'] === 'berlangsung') : ?>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                  <span class="status-badge status-izin">Sedang berlangsung</span>
                  <a href="<?= base_url('mengajar/jurnal/' . $j['id']) ?>" class="btn btn-primary btn-sm"><svg class="icon-sm" style="stroke:#fff"><use href="#i-file-text"/></svg> Lanjut ke jurnal</a>
                </div>
              <?php else : ?>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                  <span class="text-soft">Belum dimulai</span>
                  <a href="<?= base_url('mengajar/presensi/' . $j['id']) ?>" class="btn btn-primary btn-sm"><svg class="icon-sm" style="stroke:#fff"><use href="#i-clipboard-check"/></svg> Mulai mengajar</a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-title" style="margin-bottom:var(--space-4)">Progress hari ini</div>
    <div class="mini-stat-grid">
      <div class="mini-stat">
        <span class="mini-stat-icon" style="background:var(--color-primary-soft);color:var(--color-primary)"><svg class="icon-sm"><use href="#i-calendar"/></svg></span>
        <div class="mini-stat-label">Jadwal</div>
        <div class="mini-stat-value"><?= esc($prog['jadwal_total']) ?></div>
        <div class="mini-stat-caption">Total jadwal</div>
      </div>
      <div class="mini-stat">
        <span class="mini-stat-icon" style="background:var(--color-success-soft);color:var(--color-success)"><svg class="icon-sm"><use href="#i-clipboard-check"/></svg></span>
        <div class="mini-stat-label">Presensi</div>
        <div class="mini-stat-value"><?= esc($prog['presensi_terisi']) ?> / <?= esc($prog['jadwal_total']) ?></div>
        <div class="progress-track sm" style="margin-top:var(--space-2)"><div class="progress-fill" style="width:<?= esc($prog['presensi_persen']) ?>%;background:var(--color-success)"></div></div>
      </div>
      <div class="mini-stat">
        <span class="mini-stat-icon" style="background:var(--color-substitute-soft);color:var(--color-substitute)"><svg class="icon-sm"><use href="#i-book"/></svg></span>
        <div class="mini-stat-label">Jurnal</div>
        <div class="mini-stat-value"><?= esc($prog['jurnal_terisi']) ?> / <?= esc($prog['jadwal_total']) ?></div>
        <div class="progress-track sm" style="margin-top:var(--space-2)"><div class="progress-fill" style="width:<?= esc($prog['jurnal_persen']) ?>%;background:var(--color-substitute)"></div></div>
      </div>
      <div class="mini-stat">
        <span class="mini-stat-icon" style="background:var(--color-warning-soft);color:var(--color-warning)"><svg class="icon-sm"><use href="#i-clipboard"/></svg></span>
        <div class="mini-stat-label">Penilaian</div>
        <div class="mini-stat-value"><?= esc($prog['penilaian_hari_ini']) ?></div>
        <div class="mini-stat-caption">Entri hari ini</div>
      </div>
    </div>
  </div>
</div>

<div class="dash-grid-2" style="margin-top:var(--space-4)">
  <div class="card">
    <div class="card-title" style="margin-bottom:var(--space-1);display:flex;justify-content:space-between;align-items:center">
      Riwayat terbaru <a href="<?= base_url('mengajar/riwayat') ?>" style="font-size:12px;font-weight:600">Lihat semua</a>
    </div>
    <?php if (empty($ringkasanGuru['riwayatTerbaru'])) : ?>
      <p class="text-soft" style="margin:var(--space-3) 0 0">Belum ada aktivitas.</p>
    <?php else : ?>
      <?php foreach ($ringkasanGuru['riwayatTerbaru'] as $rt) :
        $ic = $rt['jenis'] === 'jurnal' ? ['i-file-text', 'var(--color-substitute)', 'var(--color-substitute-soft)'] : ['i-clipboard-check', 'var(--color-success)', 'var(--color-success-soft)'];
        $waktuT = strtotime($rt['waktu']);
        $labelWaktu = date('Y-m-d', $waktuT) === date('Y-m-d') ? 'Hari ini, ' . date('H:i', $waktuT) : (date('Y-m-d', $waktuT) === date('Y-m-d', strtotime('-1 day')) ? 'Kemarin, ' . date('H:i', $waktuT) : date('d M Y, H:i', $waktuT));
        $teksT = ($rt['jenis'] === 'jurnal' ? 'Mengisi jurnal mengajar ' : 'Presensi mengajar ') . $rt['nama_mapel'] . ' - ' . $rt['nama_kelas'];
      ?>
        <div class="dash-list-item">
          <span class="dash-list-icon" style="background:<?= esc($ic[2]) ?>;color:<?= esc($ic[1]) ?>"><svg class="icon-sm"><use href="#<?= esc($ic[0]) ?>"/></svg></span>
          <div style="min-width:0">
            <div class="dash-item-title"><?= esc($teksT) ?></div>
            <div class="dash-item-meta"><?= esc($labelWaktu) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-title" style="margin-bottom:var(--space-1);display:flex;justify-content:space-between;align-items:center">
      Pengingat <?php if ($peng['jurnal_belum'] + $peng['penilaian_belum'] + $peng['tukar_menunggu'] > 3) : ?><a href="<?= base_url('mengajar/kalender') ?>" style="font-size:12px;font-weight:600">Lihat semua</a><?php endif; ?>
    </div>
    <?php
    $itemPengingat = array_filter([
        $peng['jurnal_belum'] > 0 ? [$peng['jurnal_belum'] . ' jurnal belum diisi', 'Sesi yang sudah lewat jam selesainya', 'i-file-text', 'var(--color-danger)', base_url('mengajar/kalender'), 'Hari ini'] : null,
        $peng['penilaian_belum'] > 0 ? [$peng['penilaian_belum'] . ' penilaian belum dibuat', 'Untuk sesi yang jurnalnya sudah diisi', 'i-clipboard', 'var(--color-warning)', base_url('mengajar/kalender'), 'Hari ini'] : null,
        $peng['tukar_menunggu'] > 0 ? [$peng['tukar_menunggu'] . ' tukar jadwal menunggu', 'Approval', 'i-history', 'var(--color-info)', base_url('tukar-jadwal'), null] : null,
    ]);
    ?>
    <?php if (empty($itemPengingat)) : ?>
      <p class="text-soft" style="margin:var(--space-3) 0 0">Semua sudah lengkap. Kerja bagus!</p>
    <?php else : ?>
      <?php foreach ($itemPengingat as $ip) : ?>
        <a href="<?= esc($ip[4]) ?>" class="dash-list-item">
          <span class="dash-list-icon" style="background:var(--color-danger-soft);color:<?= esc($ip[3]) ?>"><svg class="icon-sm"><use href="#<?= esc($ip[2]) ?>"/></svg></span>
          <div style="min-width:0;flex:1">
            <div class="dash-item-title"><?= esc($ip[0]) ?></div>
            <div class="dash-item-meta"><?= esc($ip[1]) ?></div>
          </div>
          <?php if ($ip[5]) : ?><span class="role-badge" style="flex-shrink:0"><?= esc($ip[5]) ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<div class="card" style="margin-top:var(--space-4)">
  <div class="card-title" style="margin-bottom:var(--space-4)">Aksi cepat</div>
  <div class="quick-action-grid">
    <?php foreach ([
        ['Riwayat jurnal', 'i-history', 'mengajar/riwayat'],
        ['Kalender jadwal', 'i-calendar', 'mengajar/kalender'],
        ['Kalender akademik', 'i-calendar', 'kalender-akademik'],
        ['Tujuan pembelajaran', 'i-cap', 'tujuan-pembelajaran'],
        ['Tukar jadwal', 'i-history', 'tukar-jadwal'],
    ] as $qa) : ?>
      <a href="<?= base_url($qa[2]) ?>" class="quick-action-item">
        <span class="quick-action-icon"><svg class="icon-sm"><use href="#<?= esc($qa[1]) ?>"/></svg></span>
        <span class="quick-action-label"><?= esc($qa[0]) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($itemAktif) : ?>
  <a href="<?= base_url(($itemAktif['status'] === 'berlangsung' ? 'mengajar/jurnal/' : 'mengajar/presensi/') . $itemAktif['id']) ?>" class="floating-cta">
    <svg class="icon-sm" style="stroke:#fff"><use href="#i-clipboard-check"/></svg>
    Lanjutkan: <?= esc($itemAktif['status'] === 'berlangsung' ? 'Isi Jurnal' : 'Presensi') ?> - <?= esc($itemAktif['nama_kelas']) ?>
  </a>
<?php endif; ?>
