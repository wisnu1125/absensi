<div class="empty-state">
  <?php if ($alasan === 'akun') : ?>
    <h3>Akun belum terhubung ke data guru</h3>
    <p class="text-muted">Minta administrator menghubungkan akun Anda di menu Guru.</p>
  <?php elseif ($alasan === 'tahun_ajaran') : ?>
    <h3>Belum ada tahun ajaran aktif</h3>
    <p class="text-muted">Hubungi administrator untuk mengaktifkan tahun ajaran &amp; semester.</p>
  <?php else : ?>
    <h3>Anda belum ditugaskan sebagai wali kelas</h3>
    <p class="text-muted">Minta administrator menunjuk Anda sebagai wali kelas lewat menu Kelas.</p>
  <?php endif; ?>
</div>
