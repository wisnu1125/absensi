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

function openModal(id) {
  document.getElementById(id).classList.add('open');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

// Tutup modal kalau klik area gelap di luar modal-box
document.addEventListener('click', function (e) {
  if (e.target.classList && e.target.classList.contains('modal')) {
    e.target.classList.remove('open');
  }
});

// Tutup modal dengan tombol Esc
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal.open').forEach(function (m) {
      m.classList.remove('open');
    });
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
