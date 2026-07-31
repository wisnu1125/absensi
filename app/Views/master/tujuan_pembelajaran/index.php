<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-cap"/></svg> Master TP — semua guru</h1>
  <p class="text-muted">Kontrol penuh Tujuan Pembelajaran seluruh guru — lihat, tambah, ubah, atau hapus TP siapa pun untuk keperluan pengawasan kualitas pembelajaran.</p>
</div>

<div class="toolbar">
  <form method="get" style="display:flex;gap:8px;align-items:center">
    <select name="guru_id" onchange="this.form.submit()" style="max-width:260px">
      <option value="">Semua guru</option>
      <?php foreach ($daftarGuru as $g) : ?>
        <option value="<?= esc($g['id']) ?>" <?= $guruFilter === (int) $g['id'] ? 'selected' : '' ?>><?= esc($g['nama']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if ($guruFilter) : ?>
      <a href="<?= base_url('master/tujuan-pembelajaran') ?>" class="btn btn-outline btn-sm">Reset filter</a>
    <?php endif; ?>
  </form>
  <span class="kal-count-pill"><?= esc($totalTP) ?> TP total &middot; <?= count($perGuru) ?> guru</span>
</div>

<?php if (empty($perGuru)) : ?>
  <div class="empty-state">
    <h3><?= $guruFilter ? 'Guru ini belum terdaftar sebagai pengampu' : 'Belum ada data Guru Pengampu' ?></h3>
    <p class="text-muted">Daftarkan pengampuan lewat menu <a href="<?= base_url('master/guru-pengampu') ?>">Guru Pengampu</a> terlebih dahulu.</p>
  </div>
<?php else : ?>
  <?php foreach ($perGuru as $guruId => $g) : ?>
    <div class="section">
      <div class="section-title"><svg class="icon-sm"><use href="#i-user"/></svg> <h2><?= esc($g['nama_guru']) ?></h2></div>

      <div class="card-grid" style="grid-template-columns:1fr">
        <?php foreach ($g['pengampu'] as $p) : ?>
          <div class="card">
            <div class="card-title" style="margin-bottom:2px"><?= esc($p['nama_mapel']) ?></div>
            <p class="text-soft" style="font-size:12.5px;margin:0 0 14px">Tingkat <?= esc($p['tingkat']) ?> &middot; <?= count($p['tp_list']) ?> TP</p>

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
                      <form method="post" action="<?= base_url('master/tujuan-pembelajaran/delete/' . $tp['id']) ?>" onsubmit="return confirm('Hapus TP ini?')" style="display:inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-icon btn-icon-danger"><svg class="icon-sm"><use href="#i-trash"/></svg></button>
                      </form>
                    </div>
                  </div>
                  <form method="post" action="<?= base_url('master/tujuan-pembelajaran/update') ?>" class="tp-item-edit" id="tp-edit-<?= esc($tp['id']) ?>" style="display:none">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= esc($tp['id']) ?>">
                    <input type="text" name="teks" value="<?= esc($tp['teks']) ?>" required>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="tpCancelEdit(<?= esc($tp['id']) ?>)">Batal</button>
                  </form>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('master/tujuan-pembelajaran/store') ?>" style="display:flex;gap:8px">
              <?= csrf_field() ?>
              <input type="hidden" name="guru_pengampu_id" value="<?= esc($p['id']) ?>">
              <input type="text" name="teks" placeholder="Tambah TP baru..." required style="flex:1">
              <button type="submit" class="btn btn-primary btn-sm" style="flex-shrink:0"><svg class="icon-sm" style="stroke:#fff"><use href="#i-plus"/></svg> Tambah</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
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
