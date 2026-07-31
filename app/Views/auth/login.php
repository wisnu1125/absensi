<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masuk — Presensi &amp; Jurnal Mengajar</title>
  <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=43">
</head>
<body class="auth-body">
  <?= view('layouts/_icons') ?>

  <div class="auth-card">
    <div class="auth-brand">
      <span class="brand-mark"><svg class="icon-lg" style="stroke:#fff;width:28px;height:28px"><use href="#i-cap"/></svg></span>
      <h1>Presensi &amp; Jurnal Mengajar</h1>
      <p>Masuk dengan akun yang diberikan sekolah</p>
    </div>

    <?php if (session()->getFlashdata('error')) : ?>
      <div class="alert alert-danger"><svg class="icon-sm"><use href="#i-alert"/></svg> <?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('message')) : ?>
      <div class="alert alert-success"><svg class="icon-sm"><use href="#i-check-circle"/></svg> <?= esc(session()->getFlashdata('message')) ?></div>
    <?php endif; ?>

    <form action="<?= base_url('login') ?>" method="post" class="auth-form">
      <?= csrf_field() ?>

      <label for="username">Username</label>
      <div class="input-icon-wrap">
        <svg class="icon"><use href="#i-user"/></svg>
        <input type="text" id="username" name="username" value="<?= esc(old('username')) ?>" required autofocus autocomplete="username">
      </div>

      <label for="password">Password</label>
      <div class="input-icon-wrap">
        <svg class="icon"><use href="#i-lock"/></svg>
        <input type="password" id="password" name="password" required autocomplete="current-password">
      </div>

      <button type="submit" class="btn btn-primary btn-block">
        <svg class="icon-sm" style="stroke:#fff"><use href="#i-key"/></svg> Masuk
      </button>
    </form>
  </div>
</body>
</html>
