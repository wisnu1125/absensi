<div class="dash-layout">
<div class="dash-rail">
  <?php
  $rk = $railKalender;
  $KATEGORI = \App\Models\AgendaAkademikModel::KATEGORI;
  $namaBulanRail = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  $bulanSebelumRail = $rk['bulan'] - 1; $tahunSebelumRail = $rk['tahun'];
  if ($bulanSebelumRail < 1) { $bulanSebelumRail = 12; $tahunSebelumRail--; }
  $bulanSesudahRail = $rk['bulan'] + 1; $tahunSesudahRail = $rk['tahun'];
  if ($bulanSesudahRail > 12) { $bulanSesudahRail = 1; $tahunSesudahRail++; }
  $todayRail = date('Y-m-d');
  ?>
  <div class="card">
    <div class="mini-cal-header">
      <span class="mini-cal-title"><svg class="icon-sm" style="margin-right:5px;vertical-align:-2px"><use href="#i-calendar"/></svg><?= esc($namaBulanRail[$rk['bulan']]) ?> <?= esc($rk['tahun']) ?></span>
      <div class="mini-cal-nav">
        <a href="<?= base_url('dashboard') ?>" title="Bulan ini"><svg class="icon-sm"><use href="#i-dashboard"/></svg></a>
      </div>
    </div>
    <div class="mini-cal-grid">
      <?php foreach (['M', 'S', 'S', 'R', 'K', 'J', 'S'] as $dow) : ?>
        <div class="mini-cal-dow"><?= esc($dow) ?></div>
      <?php endforeach; ?>
      <?php
      $cursorRail = $rk['awalGrid'];
      while ($cursorRail <= $rk['akhirGrid']) :
          $eventsRail = $rk['eventPerTanggal'][$cursorRail] ?? [];
          $isOutsideRail = $cursorRail < $rk['awalBulan'] || $cursorRail > $rk['akhirBulan'];
          $isTodayRail = $cursorRail === $todayRail;
      ?>
        <div class="mini-cal-day<?= $isOutsideRail ? ' is-outside' : '' ?><?= $isTodayRail ? ' is-today' : '' ?>" title="<?= ! empty($eventsRail) ? esc(implode(', ', array_column($eventsRail, 'judul'))) : '' ?>">
          <?= esc((int) date('j', strtotime($cursorRail))) ?>
          <?php if (! empty($eventsRail)) : $dotKat = $KATEGORI[$eventsRail[0]['kategori']]; ?>
            <span class="mini-cal-day-dot" style="background:<?= esc($dotKat['warna']) ?>"></span>
          <?php endif; ?>
        </div>
      <?php
          $cursorRail = date('Y-m-d', strtotime($cursorRail . ' +1 day'));
      endwhile;
      ?>
    </div>
  </div>

  <div class="card">
    <div class="mini-cal-header"><span class="mini-cal-title">Event terdekat</span></div>
    <?php if (empty($rk['eventTerdekat'])) : ?>
      <p class="text-soft" style="font-size:12.5px;margin:0">Tidak ada event mendatang.</p>
    <?php else : ?>
      <?php foreach ($rk['eventTerdekat'] as $ev) : $katEv = $KATEGORI[$ev['kategori']]; ?>
        <div class="ka-upcoming-item">
          <div class="ka-upcoming-date">
            <div class="d"><?= esc(date('d', strtotime($ev['tanggal_tampil']))) ?></div>
            <div class="m"><?= esc(strtoupper(date('M', strtotime($ev['tanggal_tampil'])))) ?></div>
          </div>
          <div style="min-width:0;flex:1">
            <div class="ka-upcoming-judul"><?= esc($ev['judul']) ?></div>
            <span class="role-badge" style="background:<?= esc($katEv['soft']) ?>;color:<?= esc($katEv['warna']) ?>;margin-top:2px;display:inline-block"><?= esc($katEv['label']) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
    <a href="<?= base_url('kalender-akademik') ?>" class="btn btn-outline btn-sm btn-block" style="margin-top:var(--space-3)">Buka kalender akademik</a>
  </div>

  <?php if (! empty($pengumuman)) : ?>
    <div class="card">
      <div class="mini-cal-header">
        <span class="mini-cal-title">Pengumuman</span>
        <?php if (array_intersect(['administrator', 'operator'], $user['roles'])) : ?>
          <a href="<?= base_url('master/pengumuman') ?>" style="font-size:11.5px">Kelola</a>
        <?php endif; ?>
      </div>
      <?php foreach ($pengumuman as $pg) : ?>
        <div class="rail-list-item">
          <span class="rail-list-icon" style="background:var(--color-warning-soft);color:var(--color-warning)"><svg class="icon-sm"><use href="#i-alert"/></svg></span>
          <div style="min-width:0">
            <div style="font-size:12.5px;font-weight:600"><?= esc($pg['judul']) ?></div>
            <?php if ($pg['isi']) : ?><div class="text-soft" style="font-size:11.5px;margin-top:2px"><?= esc($pg['isi']) ?></div><?php endif; ?>
            <div class="text-soft" style="font-size:10.5px;margin-top:3px"><?= esc(date('d M Y', strtotime($pg['tanggal_mulai']))) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($statsAdmin !== null) : ?>
    <?php if (! empty($statsAdmin['lengkap'])) :
      $dl = $statsAdmin['lengkap']['deadline'];
      $deadlineItems = array_filter([
          $dl['jurnal_belum_diisi'] > 0 ? [$dl['jurnal_belum_diisi'] . ' jurnal belum diisi', 'Sesi yang sudah lewat jam selesainya', 'i-file-text', 'var(--color-danger)'] : null,
          $dl['tukar_menunggu'] > 0 ? [$dl['tukar_menunggu'] . ' tukar jadwal menunggu approval', null, 'i-history', 'var(--color-warning)'] : null,
          $dl['kelas_belum_penilaian'] > 0 ? [$dl['kelas_belum_penilaian'] . ' kelas belum ada penilaian hari ini', null, 'i-clipboard-check', 'var(--color-substitute)'] : null,
      ]);
    ?>
      <?php if (! empty($deadlineItems)) : ?>
        <div class="card">
          <div class="mini-cal-header"><span class="mini-cal-title">Deadline &amp; pengingat</span></div>
          <?php foreach ($deadlineItems as $di) : ?>
            <div class="rail-list-item">
              <span class="rail-list-icon" style="background:var(--color-danger-soft);color:<?= esc($di[3]) ?>"><svg class="icon-sm"><use href="#<?= esc($di[2]) ?>"/></svg></span>
              <div style="min-width:0">
                <div style="font-size:12.5px;font-weight:600"><?= esc($di[0]) ?></div>
                <?php if ($di[1]) : ?><div class="text-soft" style="font-size:11px;margin-top:2px"><?= esc($di[1]) ?></div><?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  <?php endif; ?>
</div>

<div class="dash-main">

<?php if (! empty($agendaHariIni)) : ?>
  <div class="card">
    <div class="card-title" style="margin-bottom:10px;display:flex;align-items:center;gap:7px">
      <svg class="icon-sm"><use href="#i-calendar"/></svg> Agenda hari ini
    </div>
    <div style="display:flex;flex-direction:column;gap:8px">
      <?php foreach ($agendaHariIni as $ev) : $kat = \App\Models\AgendaAkademikModel::KATEGORI[$ev['kategori']]; ?>
        <div style="display:flex;align-items:center;gap:9px">
          <span class="role-badge" style="background:<?= esc($kat['soft']) ?>;color:<?= esc($kat['warna']) ?>;flex-shrink:0"><?= esc($kat['label']) ?></span>
          <span style="font-weight:600;font-size:13px"><?= esc($ev['judul']) ?></span>
          <?php if (! $ev['all_day'] && $ev['jam_mulai']) : ?>
            <span class="text-soft" style="font-size:12px"><?= esc(substr($ev['jam_mulai'], 0, 5)) ?></span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php if ($ringkasanGuru !== null) : ?>
  <?php if (empty($ringkasanGuru['guru'])) : ?>
    <div class="alert alert-danger">
      <svg class="icon-sm"><use href="#i-alert"/></svg>
      Akun Anda punya role Guru tapi belum dihubungkan ke data guru. Minta administrator menghubungkan akun ini di menu Guru.
    </div>
  <?php elseif (empty($ringkasanGuru['aktif'])) : ?>
    <div class="alert alert-danger"><svg class="icon-sm"><use href="#i-alert"/></svg> Belum ada semester aktif.</div>
  <?php else : ?>

    <?= view('dashboard/_guru_lengkap', ['ringkasanGuru' => $ringkasanGuru]) ?>

    <div class="section">
      <div class="section-title"><svg class="icon"><use href="#i-calendar"/></svg> <h2>Jadwal minggu ini</h2></div>

      <?php if (empty($ringkasanGuru['mingguan'])) : ?>
        <div class="empty-state"><h3>Belum ada jadwal minggu ini</h3></div>
      <?php else : ?>
        <div class="table-wrap table-responsive-cards">
          <table class="table">
            <thead><tr><th style="width:50px">No.</th><th>Hari</th><th>Jam</th><th>Kelas</th><th>Mapel</th><th>Keterangan</th></tr></thead>
            <tbody>
              <?php
              $todayIso = date('Y-m-d');
              ?>
              <?php foreach ($ringkasanGuru['mingguan'] as $i => $m) : ?>
                <tr style="<?= $m['tanggal_master'] === $todayIso ? 'background:var(--color-primary-soft)' : '' ?>">
                  <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
                  <td class="td-card-title"><?= esc($m['hari']) ?></td>
                  <td class="text-soft" data-label="Jam" style="white-space:nowrap"><?= esc(substr($m['jam_mulai'], 0, 5)) ?>–<?= esc(substr($m['jam_selesai'], 0, 5)) ?></td>
                  <td data-label="Kelas"><?= esc($m['nama_kelas']) ?></td>
                  <td data-label="Mapel"><?= esc($m['nama_mapel']) ?></td>
                  <td data-label="Keterangan">
                    <?php if (! empty($m['ditukar'])) : ?>
                      <span class="role-badge role-badge-warning">Pindah dari <?= esc($m['hari_asli']) ?></span>
                    <?php else : ?>
                      <span class="text-soft">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php if ($statsKepsek !== null) : ?>
  <div class="section">
    <div class="section-title"><svg class="icon"><use href="#i-dashboard"/></svg> <h2>Monitoring hari ini</h2></div>

    <?php if (empty($statsKepsek['aktif'])) : ?>
      <div class="alert alert-danger"><svg class="icon-sm"><use href="#i-alert"/></svg> Belum ada semester aktif.</div>
    <?php elseif (empty($statsKepsek['hariIni'])) : ?>
      <div class="empty-state"><h3>Bukan hari sekolah</h3><p class="text-muted">Tidak ada jadwal untuk hari ini.</p></div>
    <?php else : ?>
      <div class="stat-grid">
        <div class="stat-card"><span class="stat-icon"><svg class="icon-sm"><use href="#i-calendar"/></svg></span><div class="stat-label">Jadwal hari ini</div><div class="stat-value"><?= esc($statsKepsek['total_jadwal_hari_ini']) ?></div></div>
        <div class="stat-card"><span class="stat-icon" style="background:var(--color-warning-soft);color:var(--color-warning)"><svg class="icon-sm"><use href="#i-clock"/></svg></span><div class="stat-label">Sedang mengajar</div><div class="stat-value text-warning"><?= esc($statsKepsek['sedang_mengajar']) ?></div></div>
        <div class="stat-card"><span class="stat-icon" style="background:var(--color-success-soft);color:var(--color-success)"><svg class="icon-sm"><use href="#i-check-circle"/></svg></span><div class="stat-label">Selesai mengajar</div><div class="stat-value text-success"><?= esc($statsKepsek['selesai_mengajar']) ?></div></div>
        <div class="stat-card"><span class="stat-icon" style="background:var(--color-danger-soft);color:var(--color-danger)"><svg class="icon-sm"><use href="#i-alert"/></svg></span><div class="stat-label">Belum presensi</div><div class="stat-value text-danger"><?= esc($statsKepsek['belum_presensi']) ?></div></div>
        <div class="stat-card"><span class="stat-icon" style="background:var(--color-danger-soft);color:var(--color-danger)"><svg class="icon-sm"><use href="#i-file-text"/></svg></span><div class="stat-label">Belum jurnal</div><div class="stat-value text-danger"><?= esc($statsKepsek['belum_jurnal']) ?></div></div>
        <div class="stat-card"><span class="stat-icon"><svg class="icon-sm"><use href="#i-history"/></svg></span><div class="stat-label">Tukar jadwal hari ini</div><div class="stat-value"><?= esc($statsKepsek['tukar_hari_ini']) ?></div></div>
      </div>

      <div class="card" style="margin-top:var(--space-4)">
        <div class="card-title" style="margin-bottom:10px">Persentase kehadiran siswa hari ini: <?= esc($statsKepsek['persen_hadir']) ?>%</div>
        <div class="progress-track">
          <div class="progress-fill" style="width:<?= esc($statsKepsek['persen_hadir']) ?>%;background:var(--color-success)"></div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">
          <?php foreach ($statsKepsek['rekap'] as $status => $jumlah) : ?>
            <span class="status-badge status-<?= esc($status) ?>"><?= esc(ucfirst($status)) ?>: <?= esc($jumlah) ?></span>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card" style="margin-top:var(--space-4)">
        <?= view('dashboard/_detail_aktivitas', ['detail' => $statsKepsek['detail_hari_ini']]) ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($statsAdmin !== null) : ?>
  <div class="section">
    <div class="section-title"><svg class="icon"><use href="#i-chart"/></svg> <h2>Ringkasan sekolah hari ini</h2></div>

    <?php if (! empty($statsAdmin['lengkap'])) : ?>
      <?= view('dashboard/_admin_lengkap', ['lengkap' => $statsAdmin['lengkap']]) ?>
    <?php else : ?>
      <div class="alert alert-danger"><svg class="icon-sm"><use href="#i-alert"/></svg> Belum ada semester aktif — ringkasan lengkap butuh semester aktif utk menentukan jadwal hari ini.</div>

      <div class="stat-grid">
        <div class="stat-card"><span class="stat-icon"><svg class="icon-sm"><use href="#i-users"/></svg></span><div class="stat-label">Total guru</div><div class="stat-value"><?= esc($statsAdmin['total_guru']) ?></div></div>
        <div class="stat-card"><span class="stat-icon"><svg class="icon-sm"><use href="#i-user"/></svg></span><div class="stat-label">Total siswa</div><div class="stat-value"><?= esc($statsAdmin['total_siswa']) ?></div></div>
        <div class="stat-card"><span class="stat-icon"><svg class="icon-sm"><use href="#i-building"/></svg></span><div class="stat-label">Total kelas</div><div class="stat-value"><?= esc($statsAdmin['total_kelas']) ?></div></div>
        <div class="stat-card"><span class="stat-icon"><svg class="icon-sm"><use href="#i-calendar"/></svg></span><div class="stat-label">Total jadwal</div><div class="stat-value"><?= esc($statsAdmin['total_jadwal']) ?></div></div>
      </div>

      <div class="card" style="margin-top:var(--space-4)">
        <?= view('dashboard/_detail_aktivitas', ['detail' => $statsAdmin['detail_hari_ini']]) ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php
$rolesLain = array_filter($user['roles'], static fn ($r) => ! in_array($r, ['guru', 'administrator', 'operator', 'kepala_sekolah'], true));
?>
<?php if (! empty($rolesLain)) : ?>
  <div class="section">
    <div class="section-title"><h2>Role lainnya</h2></div>
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
  </div>
<?php endif; ?>

</div>
</div>
