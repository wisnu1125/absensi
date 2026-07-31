<div class="page-header">
  <h1><svg class="icon"><use href="#i-clipboard-check"/></svg> Jadwal hari ini<?= ! empty($jadwalGuru['hari']) ? ' — ' . esc($jadwalGuru['hari']) : '' ?></h1>
  <p class="text-muted">Cuma jadwal hari ini — untuk ringkasan &amp; progres semester, buka Dashboard.</p>
</div>

<?php if (empty($jadwalGuru['guru'])) : ?>
  <div class="alert alert-danger">
    <svg class="icon-sm"><use href="#i-alert"/></svg>
    Akun Anda punya role Guru tapi belum dihubungkan ke data guru. Minta administrator menghubungkan akun ini di menu Guru.
  </div>
<?php elseif (empty($jadwalGuru['aktif'])) : ?>
  <div class="alert alert-danger"><svg class="icon-sm"><use href="#i-alert"/></svg> Belum ada semester aktif, jadwal hari ini tidak bisa ditampilkan.</div>
<?php elseif (! empty($jadwalGuru['liburEvents'])) : ?>
  <div class="empty-state">
    <svg class="icon-lg" style="color:var(--color-success);margin-bottom:8px"><use href="#i-calendar"/></svg>
    <h3>Hari ini libur</h3>
    <?php foreach ($jadwalGuru['liburEvents'] as $lb) : ?>
      <p><strong><?= esc($lb['judul']) ?></strong><?= $lb['deskripsi'] ? ' — ' . esc($lb['deskripsi']) : '' ?></p>
    <?php endforeach; ?>
  </div>
<?php elseif (empty($jadwalGuru['items'])) : ?>
  <div class="empty-state">
    <h3>Tidak ada jadwal mengajar hari ini</h3>
    <p>Nikmati harinya! Jadwal akan muncul otomatis sesuai hari dan jam yang dibuat administrator.</p>
  </div>
<?php else : ?>
  <div class="timeline">
    <?php foreach ($jadwalGuru['items'] as $j) :
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
