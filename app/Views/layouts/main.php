<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($title ?? 'Sistem Presensi') ?> — Presensi &amp; Jurnal Mengajar</title>
  <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=4">
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

  <script src="<?= base_url('assets/js/app.js') ?>?v=4"></script>
</body>
</html>
