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
    <h2 style="margin-top:8px;display:flex;align-items:center;gap:8px">
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
          <div class="card" style="<?= $j['status'] === 'digantikan' ? 'opacity:.65' : '' ?>">
            <div class="text-soft" style="margin-bottom:6px;display:flex;align-items:center;gap:5px">
              <svg class="icon-sm"><use href="#i-clock"/></svg>
              <?= esc(substr($j['jam_mulai'], 0, 5)) ?>–<?= esc(substr($j['jam_selesai'], 0, 5)) ?>
            </div>
            <div class="card-title"><?= esc($j['nama_kelas']) ?> — <?= esc($j['nama_mapel']) ?></div>

            <?php if (! empty($j['menggantikan'])) : ?>
              <div class="role-badge" style="margin-bottom:6px;display:inline-block">Menggantikan <?= esc($j['menggantikan']) ?></div>
            <?php endif; ?>

            <?php if ($j['status'] === 'digantikan') : ?>
              <div style="margin-top:6px">
                <span class="text-soft">Digantikan oleh <?= esc($j['nama_pengganti'] ?? '-') ?></span>
              </div>
            <?php elseif ($j['status'] === 'selesai') : ?>
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
    <p style="margin-top:14px">
      <a href="<?= base_url('tukar-jadwal') ?>" class="btn btn-outline btn-sm"><svg class="icon-sm"><use href="#i-users"/></svg> Ajukan tukar jadwal</a>
    </p>
  <?php endif; ?>
<?php endif; ?>

<?php if ($statsKepsek !== null) : ?>
  <h2 style="margin-top:28px;display:flex;align-items:center;gap:8px"><svg class="icon"><use href="#i-dashboard"/></svg> Monitoring hari ini</h2>

  <?php if (empty($statsKepsek['aktif'])) : ?>
    <div class="alert alert-danger"><svg class="icon-sm"><use href="#i-alert"/></svg> Belum ada semester aktif.</div>
  <?php elseif (empty($statsKepsek['hariIni'])) : ?>
    <div class="empty-state"><h3>Bukan hari sekolah</h3><p class="text-muted">Tidak ada jadwal untuk hari ini.</p></div>
  <?php else : ?>
    <div class="card-grid" style="margin-top:12px;grid-template-columns:repeat(auto-fill, minmax(150px, 1fr))">
      <div class="card"><div class="text-soft" style="font-size:11px;text-transform:uppercase">Jadwal hari ini</div><div style="font-size:26px;font-weight:700"><?= esc($statsKepsek['total_jadwal_hari_ini']) ?></div></div>
      <div class="card"><div class="text-soft" style="font-size:11px;text-transform:uppercase">Sedang mengajar</div><div style="font-size:26px;font-weight:700;color:var(--color-warning)"><?= esc($statsKepsek['sedang_mengajar']) ?></div></div>
      <div class="card"><div class="text-soft" style="font-size:11px;text-transform:uppercase">Selesai mengajar</div><div style="font-size:26px;font-weight:700;color:var(--color-success)"><?= esc($statsKepsek['selesai_mengajar']) ?></div></div>
      <div class="card"><div class="text-soft" style="font-size:11px;text-transform:uppercase">Belum presensi</div><div style="font-size:26px;font-weight:700;color:var(--color-danger)"><?= esc($statsKepsek['belum_presensi']) ?></div></div>
      <div class="card"><div class="text-soft" style="font-size:11px;text-transform:uppercase">Belum jurnal</div><div style="font-size:26px;font-weight:700;color:var(--color-danger)"><?= esc($statsKepsek['belum_jurnal']) ?></div></div>
      <div class="card"><div class="text-soft" style="font-size:11px;text-transform:uppercase">Tukar jadwal hari ini</div><div style="font-size:26px;font-weight:700"><?= esc($statsKepsek['tukar_hari_ini']) ?></div></div>
    </div>

    <div class="card" style="margin-top:14px">
      <div class="card-title" style="margin-bottom:10px">Persentase kehadiran siswa hari ini: <?= esc($statsKepsek['persen_hadir']) ?>%</div>
      <div style="background:var(--color-bg);border-radius:999px;height:12px;overflow:hidden">
        <div style="width:<?= esc($statsKepsek['persen_hadir']) ?>%;height:100%;background:var(--color-success);border-radius:999px"></div>
      </div>
      <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:12px">
        <?php foreach ($statsKepsek['rekap'] as $status => $jumlah) : ?>
          <span class="status-badge status-<?= esc($status) ?>"><?= esc(ucfirst($status)) ?>: <?= esc($jumlah) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php if ($statsAdmin !== null) : ?>
  <h2 style="margin-top:28px;display:flex;align-items:center;gap:8px"><svg class="icon"><use href="#i-chart"/></svg> Statistik sekolah</h2>

  <div class="card-grid" style="margin-top:12px;grid-template-columns:repeat(auto-fill, minmax(150px, 1fr))">
    <div class="card"><div class="text-soft" style="font-size:11px;text-transform:uppercase">Total guru</div><div style="font-size:26px;font-weight:700"><?= esc($statsAdmin['total_guru']) ?></div></div>
    <div class="card"><div class="text-soft" style="font-size:11px;text-transform:uppercase">Total siswa</div><div style="font-size:26px;font-weight:700"><?= esc($statsAdmin['total_siswa']) ?></div></div>
    <div class="card"><div class="text-soft" style="font-size:11px;text-transform:uppercase">Total kelas</div><div style="font-size:26px;font-weight:700"><?= esc($statsAdmin['total_kelas']) ?></div></div>
    <div class="card"><div class="text-soft" style="font-size:11px;text-transform:uppercase">Total jadwal</div><div style="font-size:26px;font-weight:700"><?= esc($statsAdmin['total_jadwal']) ?></div></div>
    <div class="card"><div class="text-soft" style="font-size:11px;text-transform:uppercase">Presensi/jurnal hari ini</div><div style="font-size:20px;font-weight:700"><?= esc($statsAdmin['presensi_hari_ini']) ?> / <?= esc($statsAdmin['jurnal_hari_ini']) ?></div></div>
    <div class="card">
      <div class="text-soft" style="font-size:11px;text-transform:uppercase">Tukar jadwal menunggu</div>
      <div style="font-size:26px;font-weight:700;<?= $statsAdmin['tukar_menunggu'] > 0 ? 'color:var(--color-warning)' : '' ?>"><?= esc($statsAdmin['tukar_menunggu']) ?></div>
    </div>
  </div>

  <?php $totalRekapAdmin = array_sum($statsAdmin['rekap_hari_ini']); ?>
  <?php if ($totalRekapAdmin > 0) : ?>
    <div class="card" style="margin-top:14px">
      <div class="card-title" style="margin-bottom:10px">Grafik kehadiran hari ini</div>
      <?php foreach ($statsAdmin['rekap_hari_ini'] as $status => $jumlah) :
        $persen = round($jumlah / $totalRekapAdmin * 100);
      ?>
        <div style="margin-bottom:10px">
          <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px">
            <span style="font-weight:600;text-transform:capitalize"><?= esc($status) ?></span>
            <span class="text-muted"><?= esc($jumlah) ?> (<?= esc($persen) ?>%)</span>
          </div>
          <div style="background:var(--color-bg);border-radius:999px;height:9px;overflow:hidden">
            <div class="status-<?= esc($status) ?>" style="width:<?= esc($persen) ?>%;height:100%;border-radius:999px;background:currentColor;opacity:.85"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php
$rolesLain = array_filter($user['roles'], static fn ($r) => ! in_array($r, ['guru', 'administrator', 'operator', 'kepala_sekolah'], true));
?>
<?php if (! empty($rolesLain)) : ?>
  <h2 style="margin-top:28px">Role lainnya</h2>
  <div class="card-grid">
    <?php foreach ($rolesLain as $role) : ?>
      <div class="card">
        <div class="card-title" style="display:flex;align-items:center;gap:8px">
          <svg class="icon"><use href="#i-shield"/></svg>
          <?= esc(role_label($role)) ?>
        </div>
        <?php if ($role === 'wali_kelas') : ?>
          <p class="text-muted" style="font-size:13px;margin:0 0 10px">Lihat data &amp; rekap kelas Anda di halaman khusus.</p>
          <a href="<?= base_url('wali-kelas') ?>" class="btn btn-outline btn-sm">Buka halaman wali kelas</a>
        <?php else : ?>
          <p class="text-muted" style="font-size:13px;margin:0">Modul untuk role ini akan tersedia bertahap pada pembaruan berikutnya.</p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
