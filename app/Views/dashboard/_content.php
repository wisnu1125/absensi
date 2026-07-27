<div class="page-header">
  <h1>Selamat datang, <?= esc($user['full_name']) ?></h1>
  <p class="text-muted">Menu di samping kiri sudah menyesuaikan seluruh role yang Anda miliki.</p>
</div>

<?php if ($jadwalGuru !== null) : ?>
  <?php if (empty($jadwalGuru['guru'])) : ?>
    <div class="alert alert-danger">
      <svg class="icon-sm"><use href="#i-alert"/></svg>
      Akun Anda punya role Guru tapi belum dihubungkan ke data guru. Minta administrator menghubungkan akun ini di menu Guru.
    </div>
  <?php elseif (empty($jadwalGuru['aktif'])) : ?>
    <div class="alert alert-danger"><svg class="icon-sm"><use href="#i-alert"/></svg> Belum ada semester aktif, jadwal hari ini tidak bisa ditampilkan.</div>
  <?php else : ?>
    <h2 style="margin-top:24px;display:flex;align-items:center;gap:8px">
      <svg class="icon"><use href="#i-clipboard-check"/></svg>
      Jadwal hari ini<?= $jadwalGuru['hari'] ? ' — ' . esc($jadwalGuru['hari']) : '' ?>
    </h2>

    <?php if (empty($jadwalGuru['items'])) : ?>
      <div class="empty-state">
        <h3>Tidak ada jadwal mengajar hari ini</h3>
        <p>Nikmati harinya! Jadwal akan muncul otomatis sesuai hari dan jam yang dibuat administrator.</p>
      </div>
    <?php else : ?>
      <div class="card-grid">
        <?php foreach ($jadwalGuru['items'] as $j) : ?>
          <div class="card">
            <div class="text-soft" style="margin-bottom:6px;display:flex;align-items:center;gap:5px">
              <svg class="icon-sm"><use href="#i-clock"/></svg>
              <?= esc(substr($j['jam_mulai'], 0, 5)) ?>–<?= esc(substr($j['jam_selesai'], 0, 5)) ?>
            </div>
            <div class="card-title"><?= esc($j['nama_kelas']) ?> — <?= esc($j['nama_mapel']) ?></div>

            <?php if ($j['status'] === 'selesai') : ?>
              <span class="status-badge status-hadir"><svg class="icon-sm"><use href="#i-check-circle"/></svg> Selesai</span>
            <?php elseif ($j['status'] === 'berlangsung') : ?>
              <div style="margin-top:10px">
                <span class="status-badge status-izin">Sedang berlangsung</span>
                <a href="<?= base_url('mengajar/jurnal/' . $j['id']) ?>" class="btn btn-primary btn-sm" style="margin-top:10px;width:100%">
                  <svg class="icon-sm" style="stroke:#fff"><use href="#i-file-text"/></svg> Lanjut ke jurnal
                </a>
              </div>
            <?php else : ?>
              <div style="margin-top:10px">
                <span class="text-soft">Belum dimulai</span>
                <a href="<?= base_url('mengajar/presensi/' . $j['id']) ?>" class="btn btn-primary btn-sm" style="margin-top:10px;width:100%">
                  <svg class="icon-sm" style="stroke:#fff"><use href="#i-clipboard-check"/></svg> Mulai mengajar
                </a>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
<?php endif; ?>

<?php if (count($user['roles']) > 1 || $jadwalGuru === null) : ?>
  <h2 style="margin-top:28px">Role Anda</h2>
  <div class="card-grid">
    <?php foreach ($user['roles'] as $role) : ?>
      <?php if ($role === 'guru') continue; // sudah ditampilkan sebagai jadwal di atas ?>
      <div class="card">
        <div class="card-title" style="display:flex;align-items:center;gap:8px">
          <svg class="icon"><use href="#i-shield"/></svg>
          <?= esc(role_label($role)) ?>
        </div>
        <p class="text-muted" style="font-size:13px;margin:0">
          Modul untuk role ini akan tersedia bertahap pada pembaruan berikutnya.
        </p>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
