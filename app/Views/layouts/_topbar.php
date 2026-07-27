<?php
$user = current_user();
$inisial = '';
foreach (explode(' ', trim($user['full_name'] ?? '')) as $bagian) {
    if ($bagian !== '') { $inisial .= mb_strtoupper(mb_substr($bagian, 0, 1)); }
    if (mb_strlen($inisial) >= 2) { break; }
}
?>
<header class="app-topbar">
  <button type="button" class="menu-toggle" onclick="openSidebar()" aria-label="Buka menu">
    <svg class="icon"><use href="#i-menu"/></svg>
  </button>

  <div class="topbar-title"><?= esc($title ?? '') ?></div>

  <div class="topbar-user">
    <div class="user-info">
      <span class="user-name"><?= esc($user['full_name'] ?? '') ?></span>
      <span class="user-roles">
        <?php foreach ($user['roles'] ?? [] as $role): ?>
          <span class="role-badge"><?= esc(role_label($role)) ?></span>
        <?php endforeach; ?>
      </span>
    </div>
    <span class="user-avatar"><?= esc($inisial ?: '?') ?></span>
    <a href="<?= base_url('logout') ?>" class="btn-logout" title="Keluar">
      <svg class="icon"><use href="#i-logout"/></svg>
      <span>Keluar</span>
    </a>
  </div>
</header>
