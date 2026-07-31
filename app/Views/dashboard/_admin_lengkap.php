<?php
/**
 * Dashboard Admin "lengkap" — dipakai dari _content.php, HANYA kalau ada
 * semester aktif. Variabel $lengkap datang dari DashboardAdminService::build().
 *
 * Catatan desain: pakai class yang SAMA dengan _guru_lengkap.php
 * (.dash-item-title/.dash-item-meta/.dash-list-item/.mini-stat/dst) supaya
 * ukuran font & spacing konsisten di seluruh Dashboard, bukan cuma di
 * bagian guru.
 */
$sc   = $lengkap['statCards'];
$prog = $lengkap['progress'];
?>

<div class="stat-grid">
  <div class="stat-card">
    <span class="stat-icon" style="background:var(--color-primary-soft);color:var(--color-primary)"><svg class="icon-sm"><use href="#i-users"/></svg></span>
    <div class="stat-label">Guru hadir</div>
    <div class="stat-value"><?= esc($sc['guru_hadir']) ?> / <?= esc($sc['guru_total']) ?></div>
    <?php if ($sc['guru_total'] - $sc['guru_hadir'] > 0) : ?>
      <div class="stat-trend down"><?= esc($sc['guru_total'] - $sc['guru_hadir']) ?> belum hadir</div>
    <?php endif; ?>
  </div>
  <div class="stat-card">
    <span class="stat-icon" style="background:var(--color-success-soft);color:var(--color-success)"><svg class="icon-sm"><use href="#i-calendar"/></svg></span>
    <div class="stat-label">Jadwal hari ini</div>
    <div class="stat-value"><?= esc($sc['jadwal_hari_ini']) ?></div>
    <div class="stat-trend"><?= esc($sc['sedang_berlangsung']) ?> sedang berlangsung</div>
  </div>
  <div class="stat-card">
    <span class="stat-icon" style="background:var(--color-substitute-soft);color:var(--color-substitute)"><svg class="icon-sm"><use href="#i-book"/></svg></span>
    <div class="stat-label">Jurnal</div>
    <div class="stat-value"><?= esc($sc['jurnal_terisi']) ?> / <?= esc($sc['jurnal_total']) ?></div>
    <?php if ($sc['jurnal_total'] - $sc['jurnal_terisi'] > 0) : ?>
      <div class="stat-trend down"><?= esc($sc['jurnal_total'] - $sc['jurnal_terisi']) ?> belum diisi</div>
    <?php endif; ?>
  </div>
  <div class="stat-card">
    <span class="stat-icon" style="background:var(--color-warning-soft);color:var(--color-warning)"><svg class="icon-sm"><use href="#i-clipboard-check"/></svg></span>
    <div class="stat-label">Penilaian</div>
    <div class="stat-value"><?= esc($sc['penilaian_hari_ini']) ?></div>
    <div class="stat-trend">entri hari ini</div>
  </div>
  <div class="stat-card">
    <span class="stat-icon" style="background:var(--color-danger-soft);color:var(--color-danger)"><svg class="icon-sm"><use href="#i-cap"/></svg></span>
    <div class="stat-label">TP dilaksanakan</div>
    <div class="stat-value"><?= esc($sc['tp_dilaksanakan']) ?></div>
    <div class="stat-trend">hari ini</div>
  </div>
  <div class="stat-card">
    <span class="stat-icon" style="background:var(--color-info-soft);color:var(--color-info)"><svg class="icon-sm"><use href="#i-history"/></svg></span>
    <div class="stat-label">Tukar jadwal</div>
    <div class="stat-value<?= $sc['tukar_menunggu'] > 0 ? ' text-warning' : '' ?>"><?= esc($sc['tukar_menunggu']) ?></div>
    <?php if ($sc['tukar_menunggu'] > 0) : ?>
      <div class="stat-trend down">menunggu approval</div>
    <?php endif; ?>
  </div>
</div>

<div class="dash-grid-2" style="margin-top:var(--space-4)">
  <div class="card">
    <div class="card-title" style="margin-bottom:var(--space-4)">Progress operasional hari ini</div>
    <?php foreach (['presensi' => 'Presensi guru', 'jurnal' => 'Jurnal mengajar', 'penilaian' => 'Penilaian harian'] as $key => $label) :
      $p = $prog[$key];
      $warna = $key === 'presensi' ? 'var(--color-success)' : ($key === 'jurnal' ? 'var(--color-primary)' : 'var(--color-substitute)');
    ?>
      <div class="mini-progress-row">
        <div class="mini-progress-label">
          <span><?= esc($label) ?></span>
          <b><?= esc($p['terisi']) ?> / <?= esc($p['total']) ?><?= $key === 'penilaian' ? ' Kelas' : '' ?> (<?= esc($p['persen']) ?>%)</b>
        </div>
        <div class="progress-track"><div class="progress-fill" style="width:<?= esc($p['persen']) ?>%;background:<?= esc($warna) ?>"></div></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-title" style="margin-bottom:var(--space-1)">Aktivitas terbaru</div>
    <?php if (empty($lengkap['aktivitasTerbaru'])) : ?>
      <p class="text-soft" style="margin:var(--space-3) 0 0">Belum ada aktivitas hari ini.</p>
    <?php else : ?>
      <?php foreach ($lengkap['aktivitasTerbaru'] as $a) :
        $ikon = ['jurnal' => ['i-file-text', 'var(--color-primary)', 'var(--color-primary-soft)'], 'tukar' => ['i-history', 'var(--color-warning)', 'var(--color-warning-soft)'], 'presensi' => ['i-clipboard-check', 'var(--color-success)', 'var(--color-success-soft)']][$a['jenis']];
        $teks = match ($a['jenis']) {
            'jurnal'   => 'Mengisi jurnal mengajar ' . $a['nama_mapel'] . ' - ' . $a['nama_kelas'],
            'tukar'    => 'Mengajukan tukar jadwal ' . esc(date('d/m', strtotime($a['tanggal_sesi'] ?? 'now'))) . ' (' . $a['nama_mapel'] . ' - ' . $a['nama_kelas'] . ')',
            'presensi' => 'Memulai mengajar ' . $a['nama_mapel'] . ' - ' . $a['nama_kelas'],
            default    => '',
        };
      ?>
        <div class="dash-list-item">
          <span class="dash-list-icon" style="background:<?= esc($ikon[2]) ?>;color:<?= esc($ikon[1]) ?>"><svg class="icon-sm"><use href="#<?= esc($ikon[0]) ?>"/></svg></span>
          <div style="min-width:0">
            <div class="dash-item-title"><b><?= esc($a['nama_guru']) ?></b> — <?= esc($teks) ?></div>
            <div class="dash-item-meta"><?= esc(date('H:i', strtotime($a['waktu']))) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<div class="dash-grid-3" style="margin-top:var(--space-4)">
  <div class="card">
    <div class="card-title" style="margin-bottom:var(--space-4)">Monitoring guru</div>
    <?php if (empty($lengkap['monitoringGuru'])) : ?>
      <p class="text-soft">Tidak ada guru berjadwal hari ini.</p>
    <?php else : ?>
      <div style="display:flex;flex-direction:column">
        <div style="display:grid;grid-template-columns:1fr repeat(3,30px);gap:var(--space-2);padding-bottom:var(--space-2);border-bottom:1px solid var(--color-border);margin-bottom:var(--space-2)">
          <span class="dash-item-meta" style="margin:0">Guru</span>
          <span class="dash-item-meta" style="margin:0;text-align:center">Prs</span>
          <span class="dash-item-meta" style="margin:0;text-align:center">Jrn</span>
          <span class="dash-item-meta" style="margin:0;text-align:center">Nlai</span>
        </div>
        <?php foreach ($lengkap['monitoringGuru'] as $g) : ?>
          <div style="display:grid;grid-template-columns:1fr repeat(3,30px);gap:var(--space-2);align-items:center;padding:6px 0">
            <span class="dash-item-title" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= esc($g['nama_guru']) ?>"><?= esc($g['nama_guru']) ?></span>
            <?php foreach (['status_presensi', 'status_jurnal', 'status_penilaian'] as $sk) :
              $ic = ['sudah' => 'i-check-circle', 'sebagian' => 'i-clock', 'belum' => 'i-close', 'tidak_ada' => 'i-close'][$g[$sk]];
            ?>
              <span style="text-align:center"><span class="status-dot <?= esc($g[$sk]) ?>"><svg class="icon-sm" style="width:12px;height:12px"><use href="#<?= esc($ic) ?>"/></svg></span></span>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-title" style="margin-bottom:2px">Monitoring kelas</div>
    <p class="dash-item-meta" style="margin:0 0 var(--space-4)">Persentase siswa dengan minimal 1 penilaian, 30 hari terakhir</p>
    <?php if (empty($lengkap['monitoringKelas'])) : ?>
      <p class="text-soft">Tidak ada kelas berjadwal hari ini.</p>
    <?php else : ?>
      <?php foreach ($lengkap['monitoringKelas'] as $k) :
        $warnaK = $k['persen'] >= 70 ? 'var(--color-success)' : ($k['persen'] >= 40 ? 'var(--color-warning)' : 'var(--color-danger)');
      ?>
        <div class="mini-progress-row">
          <div class="mini-progress-label"><span><?= esc($k['nama_kelas']) ?></span><b><?= esc($k['persen']) ?>%</b></div>
          <div class="progress-track sm"><div class="progress-fill" style="width:<?= esc($k['persen']) ?>%;background:<?= esc($warnaK) ?>"></div></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-title" style="margin-bottom:var(--space-4)">TP hari ini</div>
    <?php if (empty($lengkap['tpHariIni'])) : ?>
      <p class="text-soft">Belum ada TP yang tercatat hari ini.</p>
    <?php else : ?>
      <?php foreach (array_slice($lengkap['tpHariIni'], 0, 6) as $tp) : ?>
        <div style="margin-bottom:var(--space-3)">
          <div class="dash-item-meta" style="margin:0 0 2px;text-transform:uppercase"><?= esc($tp['nama_mapel']) ?></div>
          <div style="display:flex;justify-content:space-between;gap:var(--space-2);align-items:baseline">
            <span class="dash-item-title" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= esc($tp['tp']) ?>"><?= esc($tp['tp']) ?></span>
            <span class="dash-item-meta" style="margin:0;flex-shrink:0"><?= esc($tp['jumlah_kelas']) ?> kelas</span>
          </div>
        </div>
      <?php endforeach; ?>
      <div class="dash-item-meta" style="margin-top:var(--space-3);padding-top:var(--space-3);border-top:1px solid var(--color-border)">
        Total TP dilaksanakan hari ini: <b style="color:var(--color-text)"><?= esc($sc['tp_dilaksanakan']) ?></b>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="dash-grid-3" style="margin-top:var(--space-4)">
  <div class="card">
    <div class="card-title" style="margin-bottom:var(--space-4)">Analisis penilaian hari ini</div>
    <?php if ($lengkap['analisisPenilaian']['total'] === 0) : ?>
      <p class="text-soft">Belum ada penilaian hari ini.</p>
    <?php else : ?>
      <div style="display:flex;align-items:center;gap:var(--space-4);flex-wrap:wrap">
        <div style="position:relative;flex-shrink:0">
          <?= svg_donut_chart($lengkap['analisisPenilaian']['rincian'], $lengkap['analisisPenilaian']['total'], warna_jenis_penilaian()) ?>
          <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center">
            <div style="font-size:22px;font-weight:700;color:var(--color-text)"><?= esc($lengkap['analisisPenilaian']['total']) ?></div>
            <div class="dash-item-meta" style="margin:0">Total entri</div>
          </div>
        </div>
        <div style="flex:1;min-width:120px">
          <?php $paletDonut = ['var(--color-success)', 'var(--color-primary)', 'var(--color-substitute)', 'var(--color-warning)', 'var(--color-danger)', 'var(--teal-500)']; ?>
          <?php foreach ($lengkap['analisisPenilaian']['rincian'] as $i => $r) :
            $warnaR = warna_jenis_penilaian()[$r['jenis']] ?? $paletDonut[$i % count($paletDonut)];
          ?>
            <div class="donut-legend-item">
              <span class="donut-legend-dot" style="background:<?= esc($warnaR) ?>"></span>
              <span class="donut-legend-label"><?= esc($r['jenis']) ?></span>
              <span class="donut-legend-value"><?= esc($r['jumlah']) ?> (<?= esc($r['persen']) ?>%)</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-title" style="margin-bottom:var(--space-3)">Grafik mingguan</div>
    <div style="display:flex;gap:var(--space-4);margin-bottom:var(--space-2)">
      <span class="dash-item-meta" style="margin:0;display:flex;align-items:center;gap:5px"><span style="width:8px;height:8px;border-radius:50%;background:var(--color-primary);display:inline-block"></span>Jurnal mengajar</span>
      <span class="dash-item-meta" style="margin:0;display:flex;align-items:center;gap:5px"><span style="width:8px;height:8px;border-radius:50%;background:var(--color-success);display:inline-block"></span>Penilaian harian</span>
    </div>
    <?= svg_line_chart($lengkap['grafikMingguan']) ?>
  </div>

  <div class="card">
    <div class="card-title" style="margin-bottom:2px">Siswa perlu perhatian</div>
    <p class="dash-item-meta" style="margin:0 0 var(--space-3)">Belum ada penilaian 14 hari, atau kehadiran &lt;75% bulan ini</p>
    <?php if (empty($lengkap['siswaPerluPerhatian'])) : ?>
      <p class="text-soft">Tidak ada yang perlu perhatian khusus saat ini.</p>
    <?php else : ?>
      <?php foreach ($lengkap['siswaPerluPerhatian'] as $sp) : ?>
        <div class="dash-list-item">
          <span class="user-avatar" style="width:28px;height:28px;font-size:11px"><?= esc(strtoupper(substr($sp['nama'], 0, 1))) ?></span>
          <div style="min-width:0;flex:1">
            <div class="dash-item-title" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= esc($sp['nama']) ?> <span class="text-soft" style="font-weight:400">(<?= esc($sp['nama_kelas']) ?>)</span></div>
            <div class="dash-item-meta" style="color:var(--color-danger)"><?= esc($sp['alasan']) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
