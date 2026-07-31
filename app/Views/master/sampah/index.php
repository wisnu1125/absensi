<?php if (session()->getFlashdata('message')) : ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1><svg class="icon"><use href="#i-trash"/></svg> Sampah</h1>
  <p class="text-muted">
    Data yang dihapus dari mana pun di aplikasi ini (oleh siapa pun) muncul di sini dulu — tidak
    langsung hilang. Pulihkan kapan saja kalau ternyata terhapus tidak sengaja.
  </p>
</div>

<form method="get" action="<?= base_url('master/sampah') ?>" class="toolbar">
  <select name="jenis" onchange="this.form.submit()" style="max-width:240px">
    <option value="">Semua jenis data</option>
    <?php foreach ($peta as $kunci => $info) : ?>
      <option value="<?= esc($kunci) ?>" <?= $filterJenis === $kunci ? 'selected' : '' ?>><?= esc($info['label']) ?></option>
    <?php endforeach; ?>
  </select>
  <span class="text-soft"><?= esc(count($items)) ?> item di Sampah<?= $filterJenis ? ' (jenis: ' . esc($peta[$filterJenis]['label'] ?? $filterJenis) . ')' : '' ?></span>
</form>

<div class="table-wrap table-responsive-cards">
  <table class="table">
    <thead><tr><th style="width:50px">No.</th><th>Jenis</th><th>Data</th><th>Dihapus pada</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($items)) : ?>
        <tr><td colspan="5"><div class="empty-state"><h3>Sampah kosong</h3><p>Belum ada data yang dihapus<?= $filterJenis ? ' untuk jenis ini' : '' ?>.</p></div></td></tr>
      <?php else : ?>
        <?php foreach ($items as $i => $it) : ?>
          <tr>
            <td class="text-soft" data-label="">#<?= esc($i + 1) ?></td>
            <td data-label="Jenis"><span class="role-badge"><?= esc($it['label_jenis']) ?></span></td>
            <td class="td-card-title"><?= esc($it['label']) ?></td>
            <td class="text-soft" data-label="Dihapus pada" style="white-space:nowrap"><?= esc(date('d-m-Y H:i', strtotime($it['deleted_at']))) ?></td>
            <td class="td-card-actions" data-label="">
              <form method="post" action="<?= base_url('master/sampah/restore/' . $it['jenis'] . '/' . $it['id']) ?>"
                onsubmit="return confirm('Pulihkan <?= esc($it['label_jenis'], 'js') ?> &quot;<?= esc($it['label'], 'js') ?>&quot;?')" style="text-align:right">
                <?= csrf_field() ?>
                <button type="submit" class="btn-icon" style="color:var(--color-success);border-color:var(--color-success)">
                  <svg class="icon-sm"><use href="#i-check-circle"/></svg> Pulihkan
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
