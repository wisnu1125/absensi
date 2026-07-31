<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-cap"/></svg> Master tujuan pembelajaran</h1>
  <p class="text-muted">Kelola daftar TP untuk tiap mata pelajaran &amp; tingkat yang Anda ampu. Nanti saat mengisi Jurnal Mengajar, TP tinggal dipilih dari daftar ini.</p>
</div>

<?php if (empty($pengampuList)) : ?>
  <div class="empty-state">
    <h3>Anda belum terdaftar sebagai Guru Pengampu</h3>
    <p class="text-muted">Minta administrator mendaftarkan Anda sebagai pengampu mata pelajaran tertentu lewat menu Guru Pengampu, baru Master TP bisa diisi.</p>
  </div>
<?php else : ?>
  <div class="card-grid" style="grid-template-columns:1fr">
    <?php foreach ($pengampuList as $p) : ?>
      <div class="card">
        <div class="card-title" style="margin-bottom:2px"><?= esc($p['nama_mapel']) ?></div>
        <p class="text-soft" style="font-size:12.5px;margin:0 0 14px">Tingkat <?= esc($p['tingkat']) ?> · <?= count($p['tp_list']) ?> TP</p>

        <?php if (empty($p['tp_list'])) : ?>
          <p class="text-muted" style="font-size:12.5px;margin-bottom:12px">Belum ada TP untuk pengampuan ini.</p>
        <?php else : ?>
          <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px">
            <?php foreach ($p['tp_list'] as $i => $tp) : ?>
              <div class="tp-item" id="tp-tampil-<?= esc($tp['id']) ?>">
                <span class="tp-item-nomor"><?= esc($i + 1) ?></span>
                <span class="tp-item-teks"><?= esc($tp['teks']) ?></span>
                <div class="row-actions">
                  <button type="button" class="btn-icon" onclick="tpEditMode(<?= esc($tp['id']) ?>)"><svg class="icon-sm"><use href="#i-edit"/></svg></button>
                  <form method="post" action="<?= base_url('tujuan-pembelajaran/delete/' . $tp['id']) ?>" onsubmit="return confirm('Hapus TP ini?')" style="display:inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-icon btn-icon-danger"><svg class="icon-sm"><use href="#i-trash"/></svg></button>
                  </form>
                </div>
              </div>
              <form method="post" action="<?= base_url('tujuan-pembelajaran/update') ?>" class="tp-item-edit" id="tp-edit-<?= esc($tp['id']) ?>" style="display:none">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= esc($tp['id']) ?>">
                <input type="text" name="teks" value="<?= esc($tp['teks']) ?>" required>
                <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                <button type="button" class="btn btn-outline btn-sm" onclick="tpCancelEdit(<?= esc($tp['id']) ?>)">Batal</button>
              </form>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('tujuan-pembelajaran/store') ?>" style="display:flex;gap:8px">
          <?= csrf_field() ?>
          <input type="hidden" name="guru_pengampu_id" value="<?= esc($p['id']) ?>">
          <input type="text" name="teks" placeholder="Tambah TP baru untuk <?= esc($p['nama_mapel']) ?> tingkat <?= esc($p['tingkat']) ?>..." required style="flex:1">
          <button type="submit" class="btn btn-primary btn-sm" style="flex-shrink:0"><svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah TP</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<script>
function tpEditMode(id) {
  document.getElementById('tp-tampil-' + id).style.display = 'none';
  document.getElementById('tp-edit-' + id).style.display = 'flex';
}
function tpCancelEdit(id) {
  document.getElementById('tp-edit-' + id).style.display = 'none';
  document.getElementById('tp-tampil-' + id).style.display = 'flex';
}
</script>
