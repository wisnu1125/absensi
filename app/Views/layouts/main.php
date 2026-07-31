<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($title ?? 'Sistem Presensi') ?> — Presensi &amp; Jurnal Mengajar</title>
  <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=43">
</head>
<body>
  <?= view('layouts/_icons') ?>

  <div class="app-shell">
    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>
    <?= view('layouts/_sidebar') ?>

    <div class="app-main">
      <?= view('layouts/_topbar', ['title' => $title ?? '']) ?>

      <main class="app-content">
        <?= $content ?? '' ?>
      </main>
    </div>
  </div>

  <!-- Modal konfirmasi generik — pengganti confirm() bawaan browser, dipicu
       lewat JS konfirmasiAksi(pesan, callback) di app.js -->
  <div class="modal" id="modalKonfirmasiGlobal">
    <div class="modal-box" style="max-width:360px;text-align:center">
      <span class="konfirmasi-icon"><svg class="icon"><use href="#i-alert"/></svg></span>
      <p class="konfirmasi-pesan" style="font-size:14px;color:var(--color-text);margin:0 0 var(--space-5)"></p>
      <div class="modal-actions" style="justify-content:center">
        <button type="button" class="btn btn-outline" onclick="closeModal('modalKonfirmasiGlobal')">Batal</button>
        <button type="button" class="btn btn-primary konfirmasi-ya" style="background:var(--color-danger)">Ya, lanjutkan</button>
      </div>
    </div>
  </div>

  <script src="<?= base_url('assets/js/app.js') ?>?v=7"></script>
</body>
</html>
