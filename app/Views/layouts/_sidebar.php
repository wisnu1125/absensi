<?php
$current = current_url();
$isActive = static fn (string $path): string => str_ends_with(rtrim($current, '/'), $path) ? 'active' : '';
?>
<aside class="app-sidebar" id="appSidebar">
  <div class="sidebar-brand">
    <span class="brand-mark"><svg class="icon-lg" style="stroke:#fff"><use href="#i-cap"/></svg></span>
    <span class="brand-text">Presensi &amp; Jurnal<small>Sistem Sekolah</small></span>
    <button type="button" class="sidebar-close" onclick="closeSidebar()" aria-label="Tutup menu" style="display:flex;align-items:center;justify-content:center">
      <svg class="icon"><use href="#i-close"/></svg>
    </button>
  </div>

  <nav class="sidebar-nav">
    <a href="<?= base_url('dashboard') ?>" class="nav-item <?= $isActive('dashboard') ?>">
      <svg class="icon"><use href="#i-dashboard"/></svg> Dashboard
    </a>

    <?php if (has_any_role(['guru', 'wali_kelas'])): ?>
      <div class="nav-section">Mengajar</div>
      <a href="<?= base_url('dashboard') ?>" class="nav-item">
        <svg class="icon"><use href="#i-clipboard-check"/></svg> Jadwal hari ini
      </a>
      <a href="<?= base_url('mengajar/kalender') ?>" class="nav-item <?= $isActive('mengajar/kalender') ?>">
        <svg class="icon"><use href="#i-calendar"/></svg> Kalender jadwal
      </a>
      <a href="<?= base_url('mengajar/riwayat') ?>" class="nav-item <?= $isActive('mengajar/riwayat') ?>">
        <svg class="icon"><use href="#i-history"/></svg> Riwayat mengajar
      </a>
      <a href="<?= base_url('tukar-jadwal') ?>" class="nav-item <?= $isActive('tukar-jadwal') ?>">
        <svg class="icon"><use href="#i-users"/></svg> Tukar jadwal
      </a>
    <?php endif; ?>

    <?php if (has_role('wali_kelas')): ?>
      <div class="nav-section">Wali kelas</div>
      <a href="<?= base_url('wali-kelas') ?>" class="nav-item <?= $isActive('wali-kelas') ?>">
        <svg class="icon"><use href="#i-chart"/></svg> Rekap &amp; data kelas
      </a>
    <?php endif; ?>

    <?php if (has_role('kepala_sekolah')): ?>
      <div class="nav-section">Monitoring</div>
      <a href="<?= base_url('dashboard') ?>" class="nav-item">
        <svg class="icon"><use href="#i-dashboard"/></svg> Monitoring &amp; statistik
      </a>
    <?php endif; ?>

    <?php if (has_any_role(['administrator', 'operator'])): ?>
      <div class="nav-section">Data master</div>
      <a href="<?= base_url('master/guru') ?>" class="nav-item <?= $isActive('master/guru') ?>">
        <svg class="icon"><use href="#i-user"/></svg> Guru
      </a>
      <a href="<?= base_url('master/siswa') ?>" class="nav-item <?= $isActive('master/siswa') ?>">
        <svg class="icon"><use href="#i-users"/></svg> Siswa
      </a>
      <a href="<?= base_url('master/kelas') ?>" class="nav-item <?= $isActive('master/kelas') ?>">
        <svg class="icon"><use href="#i-building"/></svg> Kelas
      </a>
      <a href="<?= base_url('master/mata-pelajaran') ?>" class="nav-item <?= $isActive('master/mata-pelajaran') ?>">
        <svg class="icon"><use href="#i-book"/></svg> Mata pelajaran
      </a>
      <a href="<?= base_url('master/jam-pelajaran') ?>" class="nav-item <?= $isActive('master/jam-pelajaran') ?>">
        <svg class="icon"><use href="#i-clock"/></svg> Jam pelajaran
      </a>
      <a href="<?= base_url('master/hari-libur') ?>" class="nav-item <?= $isActive('master/hari-libur') ?>">
        <svg class="icon"><use href="#i-calendar"/></svg> Hari libur
      </a>
      <a href="<?= base_url('master/tahun-ajaran') ?>" class="nav-item <?= $isActive('master/tahun-ajaran') ?>">
        <svg class="icon"><use href="#i-calendar"/></svg> Tahun ajaran &amp; semester
      </a>
      <a href="<?= base_url('master/jadwal') ?>" class="nav-item <?= $isActive('master/jadwal') ?>">
        <svg class="icon"><use href="#i-clipboard"/></svg> Jadwal mengajar
      </a>
    <?php endif; ?>

    <?php if (has_any_role(['administrator', 'operator', 'kepala_sekolah'])): ?>
      <div class="nav-section">Laporan</div>
      <a href="<?= base_url('laporan/presensi') ?>" class="nav-item <?= $isActive('laporan/presensi') ?>">
        <svg class="icon"><use href="#i-chart"/></svg> Laporan presensi
      </a>
      <a href="<?= base_url('laporan/jurnal') ?>" class="nav-item <?= $isActive('laporan/jurnal') ?>">
        <svg class="icon"><use href="#i-file-text"/></svg> Rekap jurnal
      </a>
      <a href="<?= base_url('laporan/tukar-jadwal') ?>" class="nav-item <?= $isActive('laporan/tukar-jadwal') ?>">
        <svg class="icon"><use href="#i-history"/></svg> Tukar jadwal
      </a>
    <?php endif; ?>

    <?php if (has_role('administrator')): ?>
      <div class="nav-section">Sistem</div>
      <a href="<?= base_url('master/pengguna') ?>" class="nav-item <?= $isActive('master/pengguna') ?>">
        <svg class="icon"><use href="#i-shield"/></svg> Pengguna &amp; role
      </a>
      <a href="<?= base_url('master/audit-log') ?>" class="nav-item <?= $isActive('master/audit-log') ?>">
        <svg class="icon"><use href="#i-history"/></svg> Audit log
      </a>
    <?php endif; ?>
  </nav>
</aside>
