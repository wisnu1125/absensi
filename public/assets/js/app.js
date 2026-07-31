/**
 * app.js — helper generik, dipakai di semua halaman Data Master.
 * Tidak pakai library apa pun, murni vanilla JS supaya ringan.
 */

function openSidebar() {
  document.getElementById('appSidebar').classList.add('open');
  document.getElementById('sidebarBackdrop').classList.add('open');
}

function closeSidebar() {
  document.getElementById('appSidebar').classList.remove('open');
  document.getElementById('sidebarBackdrop').classList.remove('open');
}

// ---- Ingat posisi scroll sidebar antar halaman — aplikasi ini server-
// rendered, jadi sidebar ikut dimuat ulang tiap pindah halaman & posisi
// scroll-nya reset ke atas. Kalau menu yang mau diklik letaknya di bawah
// (mis. submenu Laporan), ini bikin harus scroll ulang tiap kali abis klik
// satu menu. Disimpan di sessionStorage (bukan localStorage) supaya cuma
// berlaku selama tab/sesi ini terbuka, otomatis bersih kalau tab ditutup.
(function () {
  const sidebar = document.getElementById('appSidebar');
  if (! sidebar) { return; }

  const posisiTersimpan = sessionStorage.getItem('sidebarScrollPos');
  if (posisiTersimpan !== null) {
    sidebar.scrollTop = parseInt(posisiTersimpan, 10) || 0;
  }

  const simpanPosisi = function () {
    sessionStorage.setItem('sidebarScrollPos', String(sidebar.scrollTop));
  };
  sidebar.addEventListener('scroll', simpanPosisi);
  // Simpan juga PERSIS sebelum link diklik (navigasi ke halaman lain) —
  // jaga-jaga kalau browser belum sempat proses event scroll terakhir.
  sidebar.addEventListener('click', function (e) {
    if (e.target.closest('a')) { simpanPosisi(); }
  });
})();

function openModal(id) {
  document.getElementById(id).classList.add('open');
  document.body.classList.add('modal-open');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
  if (! document.querySelector('.modal.open')) {
    document.body.classList.remove('modal-open');
  }
}

// Tutup modal kalau klik area gelap di luar modal-box
document.addEventListener('click', function (e) {
  if (e.target.classList && e.target.classList.contains('modal')) {
    e.target.classList.remove('open');
    if (! document.querySelector('.modal.open')) {
      document.body.classList.remove('modal-open');
    }
  }
});

// Tutup modal dengan tombol Esc
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal.open').forEach(function (m) {
      m.classList.remove('open');
    });
    document.body.classList.remove('modal-open');
  }
});

/**
 * Filter baris tabel secara instan berdasarkan teks pencarian.
 * Dipakai lewat: <input oninput="filterTable(this.value, 'tabelId')">
 */
function filterTable(keyword, tableId) {
  const rows = document.querySelectorAll('#' + tableId + ' tbody tr');
  const q = keyword.trim().toLowerCase();

  rows.forEach(function (row) {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(q) ? '' : 'none';
  });
}

/**
 * Notifikasi toast — dipakai form yang disubmit lewat AJAX (tanpa reload
 * halaman), karena flash message CI4 biasa cuma muncul setelah reload.
 * Pemakaian: tampilkanToast('Berhasil disimpan.', 'success')
 */
function tampilkanToast(pesan, jenis) {
  jenis = jenis || 'success';
  let container = document.querySelector('.toast-container');
  if (! container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const ikon = jenis === 'error' ? '#i-alert' : '#i-check-circle';
  const toast = document.createElement('div');
  toast.className = 'toast toast-' + jenis;
  toast.innerHTML =
    '<svg class="icon-sm toast-icon"><use href="' + ikon + '"/></svg>' +
    '<span class="toast-body"></span>' +
    '<button type="button" class="toast-close" aria-label="Tutup">&times;</button>';
  toast.querySelector('.toast-body').textContent = pesan;
  container.appendChild(toast);

  const hapus = function () {
    toast.classList.add('toast-out');
    setTimeout(function () { toast.remove(); }, 200);
  };
  toast.querySelector('.toast-close').addEventListener('click', hapus);
  setTimeout(hapus, 4000);
}

/**
 * Konfirmasi aksi — pengganti confirm() bawaan browser (tampilannya beda-beda
 * tiap browser &amp; tidak konsisten dgn desain aplikasi). Pemakaian:
 *   konfirmasiAksi('Hapus jadwal ini?', function () { ...lanjutkan aksi... });
 * Fallback otomatis ke confirm() bawaan kalau markup modalnya (di
 * layouts/main.php) entah kenapa tidak ada di halaman ini.
 */
function konfirmasiAksi(pesan, onKonfirmasi) {
  const modal = document.getElementById('modalKonfirmasiGlobal');
  if (! modal) {
    if (confirm(pesan)) { onKonfirmasi(); }
    return;
  }

  modal.querySelector('.konfirmasi-pesan').textContent = pesan;
  const tombolYa = modal.querySelector('.konfirmasi-ya');

  const handler = function () {
    tombolYa.removeEventListener('click', handler);
    closeModal('modalKonfirmasiGlobal');
    onKonfirmasi();
  };
  tombolYa.addEventListener('click', handler);
  openModal('modalKonfirmasiGlobal');
}
