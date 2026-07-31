# Sistem Presensi Siswa & Jurnal Mengajar — Panduan Lengkap (Fase 1–6)

Dokumen ini **menggantikan semua README_FASE*.md sebelumnya**. Kalau Anda masih punya file-file
README lama dari zip sebelumnya, boleh dihapus — semua isinya sudah dirangkum di sini, plus
perbaikan untuk file yang sempat kelewat.

## ⚠️ Kalau Anda upgrade dari zip fase sebelumnya

**Wajib jalankan `database/tambahan_tukar_jadwal.sql` DAN `database/tambahan_jadwal_swap.sql`
DAN `database/tambahan_soft_delete.sql` DAN `database/tambahan_tanggal_semester.sql` DAN
`database/tambahan_index_performa.sql`
lewat phpMyAdmin (tab Import)** kalau database Anda sudah terisi data sebelumnya — yang
pertama menambahkan tabel `tukar_jadwal` (guru pengganti), yang kedua menambahkan tabel
`jadwal_swap` (pertukaran slot), yang ketiga menambahkan kolom `deleted_at` ke 7 tabel Data
Master supaya fitur Hapus &amp; Sampah bisa dipakai — **tanpa file ketiga ini, tombol Hapus
di Data Master akan ERROR** karena kolomnya belum ada. Yang keempat menambahkan kolom tanggal
berlaku ke tabel `semester`, yang kelima menambah index performa (aman dijalankan kapan saja).
**Yang keenam, `database/tambahan_jam_per_hari.sql`, WAJIB dijalankan** kalau Anda pernah
memakai versi sebelumnya — mengubah `jam_pelajaran` supaya jamnya bisa beda tiap hari (baca
detail lengkapnya di bawah). **Yang ketujuh, `database/tambahan_penilaian_harian.sql`, WAJIB
dijalankan** untuk fitur Penilaian Harian (baca detail di bawah). **Yang kedelapan,
`database/tambahan_guru_pengampu.sql`, WAJIB dijalankan** untuk fondasi Guru Pengampu (baca
detail di bawah — migrasi ini OTOMATIS mengisi data Guru Pengampu dari jadwal yang sudah ada,
jadwal lama Anda tidak perlu diedit ulang). Kalau Anda instalasi baru dari nol pakai
`schema.sql` yang ada di paket ini, semuanya sudah otomatis ikut, tidak perlu
jalankan file tambahan ini.

0q. **[REFACTOR FONDASI, SEBAGIAN] Konsep "Guru Pengampu" (Guru x Mapel x Tingkat).**
   Ini refactor arsitektur besar sesuai permintaan Anda — statusnya SEBAGIAN, saya jelaskan
   persis apa yang sudah dan belum di bagian akhir.
   - **Analisis dulu, sesuai urutan yang Anda minta**: saya audit seluruh kodebase dan
     menemukan `jadwal.guru_id`/`mapel_id` dipakai di **90 titik across 13 file** (Dashboard,
     Laporan, ScheduleResolverService, TukarJadwal, dst). Berdasar temuan ini saya putuskan
     pendekatan ADITIF: kolom lama TETAP ADA dan tetap tersinkron otomatis, bukan diganti —
     supaya ke-90 titik pemakaian itu tidak perlu disentuh sama sekali, sesuai permintaan Anda
     soal menjaga kompatibilitas.
   - **Tabel baru `guru_pengampu`** (guru_id, mapel_id, tingkat — bukan kelas spesifik, sesuai
     yang Anda jelaskan karena TP/materi kelas VIII beda dari IX walau sama-sama IPA).
   - **Tabel baru `tujuan_pembelajaran`** (fondasi Master TP, dimiliki oleh Guru Pengampu) —
     Model sudah lengkap dan siap pakai.
   - **`jadwal.guru_pengampu_id`** kolom baru (nullable, aditif) sebagai sumber kebenaran baru
     ke depannya, tanpa menghapus `guru_id`/`mapel_id` yang sudah ada.
   - **Migrasi dengan backfill otomatis** — diuji dengan skenario sekolah yang SUDAH punya
     data jadwal: kombinasi guru+mapel+tingkat yang sudah ada di jadwal Anda otomatis dijadikan
     data Guru Pengampu, dan seluruh jadwal lama langsung tertaut ke situ tanpa perlu diedit
     manual satu-satu — diverifikasi semua baris jadwal ter-backfill benar, tidak ada yang
     tertinggal NULL.
   - **Halaman kelola Guru Pengampu baru** di Data Master (khusus admin/operator) — CRUD
     lengkap, tingkat diambil dinamis dari data Kelas yang sudah ada (bukan di-hardcode
     VII/VIII/IX, supaya cocok jenjang SD/SMA juga), mencegah hapus pengampu yang masih
     dipakai jadwal aktif.
   - **BELUM DIKERJAKAN, ini yang paling besar tersisa**: Poin 4 permintaan Anda (halaman
     Jadwal Pelajaran disesuaikan memakai Guru Pengampu, dengan alur pilih Tingkat → Guru
     Pengampu → Kelas spesifik) belum saya sentuh — form Tambah/Edit Jadwal saat ini MASIH
     memakai pilihan guru+mapel bebas seperti sebelumnya. UI Master TP di Dashboard Guru dan
     dropdown TP di Jurnal (poin 5) juga belum dikerjakan, meski fondasi datanya
     (`TujuanPembelajaranModel`) sudah siap.

0q2. **[LANJUTAN] Poin 4 selesai: form Jadwal (tampilan daftar) sekarang memakai Guru Pengampu.**
   - Alur form Tambah/Edit Jadwal sekarang: pilih **Kelas** dulu → dropdown **Guru Pengampu**
     otomatis terfilter cuma menampilkan guru yang terdaftar berhak mengajar di TINGKAT kelas
     itu (bukan lagi guru+mapel bebas terpisah) — persis seperti yang Anda minta.
   - **Validasi dobel**: selain difilter di JS, Controller JUGA memvalidasi ulang tingkat
     kelas vs tingkat Guru Pengampu sebelum menyimpan (jaga-jaga kalau ada yang mencoba
     mem-bypass form) — diuji dengan skenario valid (diterima) dan tidak valid (ditolak
     dengan pesan jelas).
   - **Jadwal lama (dibuat sebelum fitur ini) ditangani dengan baik**: kalau field
     `guru_pengampu_id`-nya kosong, form Edit menampilkan peringatan &amp; guru diminta
     memilih salah satu sebelum menyimpan — bukan gagal diam-diam.
   - Saya sempat menemukan &amp; memperbaiki sendiri bug nyata sebelum sempat terkirim:
     variabel `$kelasModel` tidak sengaja terhapus saat merombak `index()`, yang akan
     menyebabkan error "Undefined variable" — ketahuan lewat pengecekan ulang manual, bukan
     lolos ke pengujian.
   - **Cakupan turn ini**: baru tampilan DAFTAR (list) Jadwal yang diperbarui. Tampilan GRID
     menyusul di turn berikutnya (lihat 0q3 di bawah).

0q3. **[LANJUTAN, MENDESAK] Tampilan Grid Jadwal — perbaikan darurat + Guru Pengampu.**
   - **Temuan penting saat memulai turn ini**: tampilan Grid ternyata submit ke Controller
     `store()`/`update()` yang SAMA dengan tampilan Daftar yang sudah saya ubah sebelumnya —
     artinya begitu turn sebelumnya terkirim, form Grid **rusak** (masih mengirim `guru_id`/
     `mapel_id` yang sudah tidak diterima lagi Controller). Saya perbaiki ini sebagai
     prioritas pertama di turn ini, bukan celah yang dibiarkan.
   - Sekarang form Tambah/Edit di Grid juga pakai dropdown **Guru Pengampu**, otomatis
     terfilter sesuai tingkat kelas dari SEL yang diklik (kelasnya sudah otomatis diketahui
     dari posisi sel, jadi tidak perlu pilih kelas dulu seperti di tampilan Daftar).
   - Jadwal lama tanpa `guru_pengampu_id` ditangani sama seperti di tampilan Daftar: muncul
     peringatan &amp; guru diminta memilih salah satu.
   - Diuji render dengan skenario campuran (sel terisi + sel kosong di kelas berbeda tingkat)
     — parameter tingkat, data JSON sel, dan lookup kelas→tingkat semuanya diverifikasi benar.
   - **Belum dikerjakan**: alur IMPORT Excel (baik format daftar panjang maupun format grid)
     masih membuat jadwal tanpa validasi Guru Pengampu — jadwal hasil import akan tersimpan
     dengan `guru_pengampu_id` kosong (tidak error, tapi belum tervalidasi terhadap Guru
     Pengampu). Dengan ini, kedua alur INTERAKTIF (Daftar &amp; Grid) sudah selesai — cuma
     alur BULK IMPORT yang masih tersisa dari poin 4.

0q4. **[LANJUTAN] Poin 5 selesai: Master TP (menu guru baru) + dropdown TP di Jurnal.**
   Ini bagian yang Anda sebut sendiri "paling utama" dari seluruh refactor — sekarang fondasi
   Guru Pengampu yang dibangun di turn-turn sebelumnya mulai terasa manfaatnya secara nyata.
   - **Menu baru "Master TP"** di sidebar guru — tiap mata pelajaran+tingkat yang diampu
     tampil sebagai kartu terpisah, masing-masing dengan daftar TP yang bisa ditambah/edit/
     hapus langsung di tempat (tanpa reload halaman).
   - **Validasi kepemilikan ketat** di setiap aksi CRUD — guru A tidak bisa mengubah atau
     menghapus TP milik guru B walau tahu ID record-nya, karena Guru Pengampu-nya divalidasi
     ulang di Controller (bukan cuma disembunyikan di tampilan).
   - **Kolom TP di form Jurnal (baru maupun revisi) sekarang dropdown**, mengambil dari
     Master TP milik Guru Pengampu jadwal yang sedang diajar — plus opsi "Lainnya (tulis
     manual)…" supaya tetap fleksibel &amp; kompatibel dengan jadwal lama yang belum tertaut
     ke Guru Pengampu manapun (otomatis jatuh ke textarea biasa dengan saran mengelola Master
     TP, bukan macet).
   - Struktur data jurnal_mengajar SENGAJA tidak diubah (kolom `tujuan_pembelajaran` tetap
     teks bebas, bukan foreign key) — dropdown ini murni lapisan UI yang memandu guru memilih
     teks yang sudah ada, TP yang akhirnya tersimpan tetap teks biasa. Ini menjaga kompatibel
     dengan seluruh Laporan/query lain yang sudah memakai kolom itu, konsisten dengan
     pendekatan aditif yang dipakai di seluruh refactor ini.
   - **Bug saya temukan &amp; perbaiki sendiri** sebelum sempat terkirim: textarea
     tersembunyi (saat dropdown menampilkan preset yang cocok) sempat tidak ikut membawa
     nilai yang benar — kalau guru submit tanpa mengubah apa pun, TP-nya akan hilang jadi
     kosong. Ketahuan lewat pengujian saya sendiri, diperbaiki sebelum pengujian akhir.
   - Diuji 4 skenario render (jurnal baru kosong, TP cocok preset, TP custom/lawas, dan tidak
     ada Master TP sama sekali) — semua benar tanpa kendala.

0q5. **[BUG KRITIS DIPERBAIKI] `guru_pengampu_id` ternyata tidak pernah benar-benar tersimpan
   — mempengaruhi SELURUH pekerjaan Guru Pengampu dari turn-turn sebelumnya.**
   Ini temuan paling penting di turn ini. Saat mengerjakan import Excel, saya sadar
   `JadwalModel::$allowedFields` (daftar kolom yang boleh diisi CodeIgniter — bentuk
   perlindungan bawaan framework supaya tidak ada kolom "nyelonong" tersimpan) **tidak pernah
   diperbarui** saat kolom `guru_pengampu_id` ditambahkan beberapa turn lalu. Akibatnya CI4
   DIAM-DIAM membuang nilai itu setiap kali form Jadwal (Daftar maupun Grid) disimpan —
   secara teknis semuanya "berhasil tersimpan" tanpa error, TAPI keterkaitan ke Guru Pengampu
   kemungkinan besar selalu kosong walau formnya sudah benar memilih Guru Pengampu.
   - **Saya buktikan ini bukan dugaan** — mensimulasikan persis algoritma pembuangan kolom
     CodeIgniter (`array_intersect_key` terhadap `allowedFields`) dengan data yang sama persis
     dikirim form: sebelum perbaikan, `guru_pengampu_id` terbukti hilang dari data yang
     benar-benar tersimpan; sesudah perbaikan, terbukti ikut tersimpan.
   - **Ini kenapa bug ini lolos dari pengujian saya sebelumnya**: pengujian saya di turn-turn
     lalu memverifikasi lewat SQL langsung (query database), bukan lewat lapisan Model
     CodeIgniter sungguhan — jadi tervalidasi logika query-nya benar, tapi tidak menangkap
     perlindungan mass-assignment framework yang beroperasi SEBELUM data sampai ke query.
     Sudah saya perbaiki (`guru_pengampu_id` ditambahkan ke `allowedFields`) dan didokumentasi
     di sini supaya jelas bagi Anda.
   - **Dampaknya ke instalasi Anda**: kalau Anda sempat menyimpan jadwal baru lewat form
     Daftar/Grid di antara turn Guru Pengampu diperkenalkan dan turn ini, jadwal itu
     kemungkinan besar TIDAK tertaut ke Guru Pengampu manapun (`guru_pengampu_id` NULL) —
     buka lagi lewat Edit, form akan menampilkan peringatan "belum tertaut", tinggal pilih
     Guru Pengampu yang sesuai dan simpan ulang.

0q6. **[LANJUTAN] Import Excel format daftar panjang: bug lama + validasi Guru Pengampu.**
   - **Bug lama ikut ditemukan &amp; diperbaiki**: kode import ini ternyata masih memanggil
     `findByJamKe()` yang sudah dihapus sejak refactor "jam per hari" beberapa turn lalu —
     kalau dipakai, akan error total (bukan celah kecil, benar-benar tidak berfungsi). Sudah
     diganti ke `findByHariJamKe()` yang sesuai.
   - Import sekarang memvalidasi tiap baris terhadap Guru Pengampu (sama seperti form
     interaktif) — baris dengan kombinasi guru+mapel+tingkat yang belum terdaftar akan
     DILEWATI dengan pesan error yang jelas menyebutkan baris keberapa dan kenapa ditolak.
   - **Template Excel-nya ikut diperbarui**: sekarang ada sheet kedua "Referensi Guru
     Pengampu" berisi semua kombinasi yang SAH, plus catatan di sheet utama — supaya admin
     tahu kombinasi valid SEBELUM mengisi, bukan baru tahu setelah baris ditolak.
   - Diuji dengan data nyata: kombinasi valid diterima, kombinasi dengan tingkat kelas yang
     tidak sesuai pengampu ditolak dengan pesan yang tepat.
   - **Import format GRID selesai di turn berikutnya** — lihat 0q7 di bawah.

0q7. **[LANJUTAN] Import Excel format Grid — selesai, tanpa duplikasi kode.**
   - Ternyata `importGrid()` sudah mendelegasikan seluruh pemrosesan barisnya ke
     `JadwalModel::importRows()` yang SAMA dengan format daftar panjang — jadi perbaikan bug
     `findByJamKe()` dan validasi Guru Pengampu dari turn sebelumnya OTOMATIS berlaku di sini
     juga, tanpa perlu menduplikasi kode validasi. Saya verifikasi ini langsung ke source-nya
     sebelum menyimpulkan tidak ada kerja tambahan yang diperlukan di sisi validasi.
   - Yang saya tambahkan: **sheet "Referensi Guru Pengampu" yang sama** di template grid
     (sebelumnya cuma ada di template daftar panjang), supaya admin yang pakai format grid
     juga tahu kombinasi guru+mapel yang sah sebelum mengisi.
   - Saya berhati-hati soal penamaan variabel saat menambahkan kode ini — sengaja pakai nama
     berbeda dari variabel penghitung baris yang sudah dipakai sheet utama grid, supaya tidak
     saling menimpa, dan saya verifikasi ulang ini tidak bentrok sebelum menyelesaikan.
   - **Dengan ini, seluruh cakupan refactor Guru Pengampu dari permintaan awal Anda (poin 1-5)
     sudah selesai**: analisis, struktur data, form Jadwal (Daftar &amp; Grid), import Excel
     (kedua format), dan Master TP + dropdown Jurnal.

0r. **[FITUR] Master TP versi Administrator — kontrol penuh lintas guru.**
   Sebelumnya Master TP cuma bisa dikelola tiap guru untuk dirinya sendiri. Sekarang admin/
   operator punya halaman terpisah dengan akses penuh:
   - **Menu baru "Master TP"** di Data Master (beda dari menu "Master TP" milik guru di
     sidebar guru — dua controller terpisah, satu untuk admin satu untuk guru, sama-sama
     memakai Model yang sama di baliknya).
   - Admin bisa **melihat, menambah, mengedit, dan menghapus TP siapa pun** — tanpa
     pengecekan kepemilikan seperti versi guru (memang itu tujuannya: untuk pengawasan
     kualitas), tapi tetap dibatasi role admin/operator lewat filter rute, dan setiap aksi
     tercatat di Audit Log dengan label berbeda (`admin_tambah_tp` dst) supaya jejaknya
     terlihat itu aksi admin, bukan guru yang bersangkutan.
   - Data dikelompokkan **per guru** (bukan satu daftar panjang tercampur), dengan filter
     dropdown untuk fokus ke satu guru saja — cocok untuk sekolah dengan banyak guru.
   - Ringkasan jumlah TP &amp; guru ditampilkan di toolbar untuk gambaran cepat.
   - Diuji render 3 skenario (tanpa filter, dengan filter, kosong sama sekali) — sempat ada
     satu hasil uji yang terlihat gagal, saya telusuri dan ternyata itu memang perilaku benar
     (nama guru lain tetap muncul di dropdown filter supaya bisa dipindah, bukan di bagian
     data) — bukan bug.

0s. **[REFACTOR UI/UX — FASE 1 DARI RENCANA BESAR] Fondasi design system + Sidebar + Dashboard.**
   Ini permintaan redesain menyeluruh gaya SaaS modern (Notion/Linear/Stripe/Vercel), TIDAK
   mengubah alur bisnis atau fitur. Mengingat cakupannya sangat besar (25+ halaman), dikerjakan
   bertahap — status detail di bagian akhir catatan ini.
   - **Warna brand TIDAK diubah sama sekali**, sesuai permintaan eksplisit Anda — semua warna
     Deep Blue Ocean yang sudah ada dipertahankan persis.
   - **Font ditingkatkan ke Inter** (dipakai Linear/Vercel), Roboto tetap jadi fallback —
     perubahan kecil tapi berdampak besar ke kesan "modern" tanpa mengubah identitas.
   - **Skala spacing baru berbasis 4px** (`--space-1` s/d `--space-16`) — menggantikan angka
     piksel ad-hoc yang sebelumnya berbeda tipis antar halaman.
   - **Target sentuh 44px** (`--touch-target`) diterapkan ke tombol, input, select, dan item
     sidebar — sesuai permintaan mobile-first Anda.
   - **Shadow dibuat berlapis & lebih halus** (dua lapis: kontak dekat + sebaran jauh) —
     terasa lebih premium dibanding drop-shadow datar gaya Bootstrap sebelumnya.
   - **Sidebar**: active state diubah dari gradient tebal jadi highlight lembut + garis aksen
     kiri tipis (gaya Notion/Linear) — struktur menu SAMA SEKALI tidak disentuh.
   - **Checkbox &amp; radio dikustomisasi** (sebelumnya kotak/bulat default browser yang beda
     gaya tiap OS), ditambah utility skeleton-loading untuk keperluan loading state nanti.
   - **Bug ditemukan &amp; diperbaiki saat proses**: satu duplikasi CSS asli (`.checkbox-label`
     didefinisikan dua kali dengan nilai berbeda, versi lama berpotensi menimpa versi baru) —
     saya audit MENYELURUH untuk duplikasi selector lain, 4 kandidat lain ternyata pola yang
     memang disengaja (bukan bug), bukan cuma diasumsikan aman.
   - **Dashboard** (prioritas utama menurut Anda): sapaan dinamis sesuai waktu (pagi/siang/
     sore/malam), "Jadwal Hari Ini" guru diubah dari grid kartu jadi **timeline vertikal**
     terinspirasi referensi yang Anda kirim, stat card admin/kepsek dapat ikon. Data &amp;
     logika bisnis sepenuhnya dipertahankan.
   - **Regresi dicek eksplisit**: dua halaman dari turn-turn sebelumnya (Guru Pengampu, form
     Jurnal) di-render ulang setelah perubahan CSS global untuk memastikan tidak ada yang
     rusak — keduanya lolos tanpa error.
   - **BELUM DIKERJAKAN — cakupan tersisa masih sangat besar**: dari ~25+ halaman yang ada,
     baru Dashboard yang benar-benar diredesain. Halaman lain (Jadwal Mengajar, Presensi,
     Jurnal, Penilaian Harian, Kalender Akademik, seluruh Data Master, Tukar Jadwal, Laporan,
     Wali Kelas, dst) masih pakai styling lama, walau sudah otomatis mewarisi refinement
     komponen global (card/button/form/table/modal) karena berbagi CSS yang sama. Transformasi
     tabel jadi card di mobile (diminta eksplisit) — lihat 0t di bawah untuk progres ini.

0t. **[LANJUTAN FASE 2] Tabel jadi kartu di mobile — pola baru + 2 tabel pertama.**
   - **Pola CSS baru yang bisa dipakai ulang** (`table-responsive-cards`) — di bawah 720px,
     baris tabel berubah jadi kartu bertumpuk dengan label di tiap baris (bukan tabel yang
     dipaksa muat atau scroll horizontal) — persis pendekatan yang Anda minta eksplisit.
     Diaktifkan cukup dengan menambah satu class di `.table-wrap` dan atribut `data-label`
     di tiap sel; kolom "judul" (mis. Nama) dan kolom Aksi dapat penanganan khusus supaya
     tetap terlihat rapi sebagai kartu.
   - **Diterapkan ke tabel Siswa dan Guru** sebagai bukti konsep sekaligus dua tabel yang
     paling sering dipakai — checkbox bulk-select, tombol edit/hapus, dan semua interaktivitas
     yang sudah ada tetap utuh, cuma tampilannya yang berubah di layar sempit.
   - Diuji render dengan data nyata — sempat ada error yang terlihat menakutkan (fatal error),
     saya telusuri sampai ke output mentahnya dan ternyata itu variabel modal "Tambah Guru"
     yang tidak saya sertakan di skrip uji, sama sekali tidak terkait perubahan tabelnya
     sendiri, yang terbukti benar dari output yang sempat tercetak sebelum error tersebut.
   - **Bug README ditemukan &amp; diperbaiki**: satu kalimat di paragraf pengantar migrasi
     ternyata terpotong dan bagian penutupnya "tersasar" 200+ baris ke bawah, tertimbun
     entri-entri changelog yang disisipkan berturut-turut sepanjang percakapan ini — sudah
     disatukan kembali jadi satu kalimat utuh.
   - **Belum diterapkan** ke tabel-tabel lain — lihat 0u di bawah untuk progres lanjutannya.

0u. **[LANJUTAN FASE 2] 4 tabel lagi dikonversi ke pola kartu mobile.**
   Kelas, Mata Pelajaran, Hari Libur, dan Guru Pengampu sekarang ikut memakai pola
   `table-responsive-cards` yang sama — kolom paling identitatif tiap tabel (Nama kelas,
   Nama mata pelajaran, Tanggal libur, Nama guru) jadi judul kartu, sisanya jadi baris
   berlabel. Diuji render ke-4nya sekaligus — semua benar.
   - **Masih tersisa**: lihat 0v di bawah untuk progres lanjutannya.
   - **Cakupan halaman non-tabel** (Jadwal Mengajar, Presensi, Jurnal, Penilaian Harian,
     Kalender Akademik, Tukar Jadwal, dst) masih belum diredesain sama sekali di luar
     refinement komponen global yang otomatis terwarisi.

0v. **[LANJUTAN FASE 2] 6 instansi tabel lagi dikonversi ke pola kartu mobile.**
   Jam Pelajaran (pola diterapkan ke template per-hari sekali, otomatis berlaku ke keenam
   tab), Sampah, Audit Log (baca-saja, tanpa kolom Aksi), dan halaman Tukar Jadwal admin yang
   punya DUA tabel sekaligus (menunggu persetujuan + riwayat semua pengajuan) — total sekarang
   **11 tabel/instansi** yang sudah pakai pola ini.
   - Diuji lewat kombinasi lint + pemeriksaan struktur langsung ke file (bukan cuma render
     penuh) untuk 2 halaman yang butuh objek CI4 kompleks (Pager) yang sulit di-stub di
     lingkungan pengujian — pendekatan ini tetap memastikan markup-nya benar tanpa perlu
     framework penuh.
   - **Masih tersisa**: lihat 0w di bawah.

0w. **[BUG KRITIS DIPERBAIKI] Kolom tabel Siswa &amp; Wali Kelas tidak sejajar di desktop
   sejak paket sebelumnya — plus 3 tabel baru lagi.**
   - **Temuan penting**: saat mengonversi tabel Wali Kelas, saya sadar pola yang saya pakai di
     Siswa (menggabung kolom NIS ke dalam judul kartu, mis. "Ahmad · NIS 1001") **lupa
     menghapus header NIS yang terpisah** — akibatnya `&lt;thead&gt;` punya 8 kolom tapi baris
     data cuma 7 sel. Di tampilan mobile (kartu) ini tidak masalah karena label otomatis
     menyesuaikan, TAPI di desktop tabel aslinya kolom akan bergeser satu sejak "Nama" —
     data L/P akan muncul di bawah header "Nama", dst. Bug yang sama ternyata ada di Wali
     Kelas yang baru saya kerjakan dengan pola serupa.
   - **Kedua tabel sudah diperbaiki** — NIS dikembalikan jadi kolom terpisah, judul kartu di
     mobile tetap cuma menampilkan Nama.
   - **Diaudit MENYELURUH, bukan cuma dua ini** — saya tulis skrip Python yang mencocokkan
     jumlah `&lt;th&gt;` di tiap `&lt;thead&gt;` dengan jumlah `&lt;td&gt;` di baris data
     pertama, dijalankan ke SEMUA 13 instansi tabel yang sudah dikonversi sejauh ini (termasuk
     halaman dengan 2 tabel sekaligus). Hasilnya: HANYA Siswa &amp; Wali Kelas yang bermasalah,
     11 lainnya sudah benar sejak awal.
   - **3 tabel baru ikut dikonversi** di turn ini: Riwayat Mengajar (guru), dan pemeriksaan
     ulang Wali Kelas — total sekarang **13 instansi tabel** pakai pola kartu mobile.
   - Pelajaran untuk saya: pola "gabungkan kolom ke judul kartu" perlu SELALU diikuti
     penyesuaian jumlah kolom di `&lt;thead&gt;`, bukan cuma di baris data — akan lebih hati-
     hati soal ini utk tabel-tabel berikutnya.

0x. **[LANJUTAN FASE 2] 3 tabel Laporan dikonversi — pelajaran dari bug sebelumnya diterapkan.**
   Laporan Presensi, Laporan Jurnal (14 kolom — tabel terlebar di aplikasi, paling diuntungkan
   dari pola ini karena scroll horizontal-nya akan sangat menyiksa), dan Laporan Tukar Jadwal.
   Total sekarang **16 instansi tabel**.
   - **Kali ini jumlah kolom diverifikasi SEGERA setelah tiap edit** (bukan menunggu audit di
     akhir) — pelajaran langsung dari bug 0w di atas. Ketiganya cocok dari awal.
   - Diuji render juga — sempat ada satu hasil yang terlihat gagal, saya telusuri ke output
     mentahnya dan ternyata pencarian teks saya sendiri terlalu spesifik (tidak
     memperhitungkan atribut lain di elemen yang sama), bukan bug di kodenya.
   - **Masih tersisa**: lihat 0y di bawah — mulai giliran halaman non-tabel yang dipakai
     harian.

0z. **[REDESAIN ULANG — RESPONS ATAS FEEDBACK "BELUM TERASA BEDA"] Perubahan struktural
   nyata, bukan cuma penyesuaian angka CSS.**
   Anda menyampaikan feedback yang jelas dan tepat: perubahan Fase 1 sebelumnya terasa terlalu
   halus untuk benar-benar terasa seperti Notion/Linear/Stripe. Saya kerjakan ulang dengan
   pendekatan yang jauh lebih berani, dikerjakan penuh sampai titik ini SEBELUM mengirim file
   apa pun (sesuai permintaan Anda), dengan 4 titik pengecekan internal di antaranya.
   - **Palet netral dirombak** — latar &amp; border sebelumnya bertona biru (`#eef4f9`,
     `#dbe6ef`), sekarang netral asli (`#f6f8fa`, `#e6ebf0`) tanpa mengubah SATU PUN warna
     brand (ocean/teal/navy tetap identik). Ini akar dari kenapa Notion/Linear terasa
     "bersih" — bukan sekadar lebih terang, tapi HILANG tona birunya dari elemen netral.
   - **Shadow diratakan hampir tak terlihat saat diam** — card sekarang mengandalkan border
     tipis, bukan drop-shadow, shadow cuma muncul saat hover. Disapu bersih ke SELURUH file
     CSS termasuk 3 nilai shadow lama yang tertinggal (masih pakai tona biru lama) — dicek
     lewat grep menyeluruh, bukan diasumsikan sudah konsisten.
   - **Dashboard dirombak jadi layout 2 kolom sungguhan** dengan widget kalender mini yang
     BENAR-BENAR berfungsi di sidebar kanan (hari ini tersorot, titik warna per kategori
     event, daftar "Event Terdekat") — ini elemen visual paling mencolok di kedua gambar
     referensi Anda dan sebelumnya sama sekali tidak ada. Datanya memakai ulang
     `AgendaAkademikModel` yang sama dengan modul Kalender Akademik penuh, bukan query baru.
   - **Tombol utama diratakan** — dari gradient tebal jadi warna solid dengan shadow tipis,
     kesan lebih presisi ala Linear.
   - **Sidebar**: jarak antar grup menu diperlebar untuk pemisahan yang lebih jelas.
   - **Kalender Akademik**: sel grid diperbesar (84px→96px) supaya tidak terasa sempit.
   - **Jadwal Hari Ini** guru dikonversi ke pola timeline yang SAMA dengan Dashboard —
     sebelumnya dua halaman yang menampilkan konsep identik (jadwal hari ini) pakai gaya
     berbeda, sekarang konsisten.
   - **Jurnal &amp; Revisi Jurnal** dapat sticky action bar mobile, sama seperti Presensi.
   - Setiap tahap diuji render dengan data nyata dan di-lint sebelum lanjut ke tahap
     berikutnya; regresi dicek eksplisit ke beberapa halaman dari sesi-sesi sebelumnya untuk
     memastikan perubahan token global tidak merusak apa pun yang sudah ada.
   - **Kejujuran soal cakupan**: ini transformasi nyata pada fondasi, navigasi, Dashboard, dan
     alur harian guru yang paling sering dipakai (Jadwal Hari Ini, Presensi, Jurnal) plus 16+
     tabel dari fase sebelumnya. Halaman lain (Penilaian Harian di luar form Jurnal, Kalender
     Jadwal guru, Cari Guru Pengganti, sisa halaman Data Master, Laporan, dst) BELUM disentuh
     di putaran ini — mewarisi perbaikan token global secara otomatis, tapi belum diaudit satu
     per satu seperti halaman-halaman di atas.

0zk. **[BUG DIPERBAIKI] Sidebar reset ke atas tiap ganti halaman — posisi scroll sekarang
   diingat.**
   Akar masalahnya: aplikasi ini server-rendered, jadi sidebar ikut dimuat ulang PENUH
   tiap kali klik menu, dan posisi scroll-nya otomatis reset ke atas — bikin repot kalau
   menu yang mau diklik berikutnya letaknya di bawah juga.
   - Posisi scroll sidebar disimpan ke `sessionStorage` browser tiap kali di-scroll ATAU
     tepat sebelum klik link (jaga-jaga), lalu dipulihkan otomatis begitu halaman
     berikutnya selesai dimuat.
   - Pakai `sessionStorage` (bukan `localStorage`) supaya cuma berlaku selama tab ini
     terbuka — otomatis bersih sendiri kalau tab ditutup, tidak numpuk selamanya.

0zj. **[SESUAI PERMINTAAN] Kalender/Event/Pengumuman pindah ke kanan di desktop, tetap di
   bawah di mobile — guru &amp; admin.**
   Solusinya cukup satu baris CSS (`row-reverse`), bukan mengubah struktur HTML — urutan
   elemen di kode tetap sama, cuma ARAH tata letaknya yang dibalik di layar lebar. Karena
   perilaku mobile ("rail di bawah") sepenuhnya bergantung pada urutan elemen tsb (bukan
   arah), tidak perlu disentuh sama sekali dan otomatis tetap benar.
   Diverifikasi class ini cuma dipakai di Dashboard (tidak ada di halaman lain), jadi
   perubahannya aman &amp; terisolasi.

0zi. **[SESUAI PERMINTAAN] Header sapaan "Selamat pagi..." dihapus dari Dashboard —
   guru &amp; admin, langsung ke kartu yang berguna.**
   Dihapus dari satu tempat (`_content.php`, dipakai bareng kedua role) supaya konsisten.
   Diuji render kedua skenario — sapaan &amp; subjudulnya dipastikan benar-benar hilang,
   layout langsung mulai dari kartu tanpa jarak kosong di atas.

0zh. **[PERBAIKAN LEBIH FUNDAMENTAL] Modal tidak mau hilang &amp; notifikasi tidak muncul —
   jujur soal keterbatasan diagnosis saya, plus penggantian confirm() yang jelek.**
   Perbaikan CSRF saya kemarin ternyata belum cukup — Anda laporkan modal masih tidak
   tertutup dan notifikasi tidak muncul di 3 skenario (simpan, hapus, DAN error konflik
   jadwal). Saya jujur di sini: **saya tidak bisa membuka browser sungguhan utk melihat
   persis apa yang terjadi**, jadi saya ambil pendekatan yang lebih kuat dari sekadar
   menambal satu titik:
   - **Solusi yang lebih fundamental**: dibanding terus menyinkronkan token CSRF lewat
     JavaScript setiap kali ada respons (yang terbukti masih bermasalah), saya buat
     `app/Config/Security.php` khusus yang **mematikan regenerasi token per-request**
     (`regenerate = false`, bawaan CI4-nya `true`). Ini pengaturan resmi yang didukung
     CI4, dan wajar utk aplikasi internal ber-login seperti ini (bukan aplikasi publik
     berisiko tinggi) — token tetap sama selama SATU SESI LOGIN, bukan berubah tiap
     request. Ini menghilangkan SELURUH kelas bug token-basi sekaligus, bukan cuma
     menambal satu gejala.
   - **Penanganan error di JS diperkuat total**: sebelumnya kalau respons server bukan
     JSON valid (misalnya karena error PHP yang tidak terduga), kegagalannya diam-diam
     tanpa jejak. Sekarang: respons diambil sbg teks dulu, dicoba di-parse manual, dan
     kalau gagal, TEKS MENTAHNYA dicatat ke console browser (F12 → Console) supaya kalau
     ini masih terjadi, saya atau siapa pun bisa lihat PERSIS apa yang dikirim server.
   - **confirm() bawaan browser diganti modal custom** — sesuai keluhan Anda soal
     tampilannya jelek. Modal baru ini (`konfirmasiAksi()`) dipasang di layout utama,
     jadi otomatis tersedia di halaman lain juga kalau nanti dibutuhkan, bukan cuma di
     Grid Jadwal.
   - **Kejujuran soal ketidakpastian**: saya CUKUP YAKIN soal akar CSRF (diverifikasi ke
     source code CI4), tapi TIDAK 100% yakin ini penyebab TUNGGAL dari yang Anda alami,
     krn saya tidak bisa menguji di browser sungguhan. Kalau setelah ini MASIH ada modal
     yang tidak mau hilang tanpa notifikasi, tolong buka Console browser (klik kanan →
     Inspect → tab Console) saat kejadian dan kabari pesan merah apa yang muncul di
     sana — itu akan sangat membantu menemukan akar yang sesungguhnya kalau dugaan saya
     kali ini masih belum tepat sasaran.

0zg. **[BUG KRITIS DIPERBAIKI] "Klik simpan tidak menyimpan" di Grid — akar masalahnya token
   CSRF basi, ditemukan lewat kode sumber CI4 lagi.**
   Anda laporkan tombol Simpan di Grid tidak berfungsi setelah perubahan AJAX kemarin. Ini
   akar masalahnya:
   - **CodeIgniter meregenerasi token CSRF di SETIAP request** (pengaturan bawaan
     `regenerate = true`, saya cek langsung ke source code, bukan diasumsikan). Respons AJAX
     saya sebelumnya cuma kirim `{success, message, html}` — TIDAK mengirim token baru.
     Akibatnya: submission PERTAMA sukses, tapi form yang masih terbuka di halaman tetap
     pakai token dari page-load awal — submission KEDUA dan seterusnya DITOLAK validasi CSRF
     secara DIAM-DIAM (tanpa pesan error yang jelas), persis terasa seperti "tidak
     menyimpan". Ini sangat match dengan keluhan Anda soal input banyak data berturut-turut.
   - **Diperbaiki**: setiap respons AJAX sekarang SELALU menyertakan token CSRF terbaru
     (`csrfName` + `csrfHash`), dan JavaScript memperbarui token itu di SEMUA form di
     halaman setiap kali dapat respons — baik berhasil maupun gagal karena alasan lain
     (mis. bentrok jadwal), karena token tetap teregenerasi di kedua kasus itu.
   - Diuji: fungsi &amp; pemanggilannya terverifikasi ada persis 1x di JS yang di-render,
     sintaks tervalidasi, render halaman penuh tidak ada regresi.
   - Kalau setelah ini masih ada kendala simpan, kemungkinan besar penyebabnya beda (misalnya
     validasi bentrok jadwal) — akan muncul sebagai pesan toast merah, bukan diam saja
     seperti sebelumnya, jadi lebih mudah dilaporkan persis pesannya kalau masih terjadi.

0zf. **[FITUR BARU] Grid Jadwal: simpan tanpa reload halaman, tetap di grid.**
   Anda benar soal titik sakitnya — sebelumnya tombol Simpan di Grid Jadwal SELALU
   me-redirect ke "tampilan daftar" apa pun asal request-nya, jadi harus klik "Tampilan
   grid" lagi tiap kali selesai satu sesi. Kalau input banyak jadwal berturut-turut, itu
   sangat mengganggu.
   - **Tambah/Edit/Hapus di Grid sekarang lewat AJAX** — submit tanpa reload, modal
     tertutup otomatis, tabel grid diperbarui di tempat, dan Anda TETAP di hari &amp; grid
     yang sama, siap klik sel kosong berikutnya langsung.
   - **Notifikasi toast baru** (pojok kanan atas, hilang otomatis) menggantikan flash
     message CI4 biasa — flash message cuma muncul setelah reload, jadi tidak akan
     terlihat sama sekali di alur tanpa-reload ini kalau tidak diganti.
   - **Diverifikasi lewat kode sumber CI4 asli** (bukan diasumsikan) — `isAJAX()` &amp;
     `setJSON()` saya cek langsung ke source code, terutama karena kedua fungsi ini belum
     pernah dipakai di proyek ini sebelumnya dan saya baru saja kena bug dari asumsi yang
     salah soal Query Builder minggu ini.
   - **Tetap aman utk browser tanpa JavaScript** — kalau AJAX gagal terdeteksi, Controller
     jatuh kembali ke perilaku redirect lama (tidak ada jalur yang benar-benar rusak).
   - Diuji: partial tabel grid render sendiri dengan benar, struktur 3 form (Tambah/Edit/
     Hapus) semuanya punya field baru yang dibutuhkan, fungsi JS AJAX-nya terdefinisi,
     sintaks JS tervalidasi utk kedua skenario (ada/tidak ada Guru Pengampu).
   - **Soal cakupan**: ini baru Grid Jadwal, yang paling Anda soroti. Form CRUD lain
     (Guru, Siswa, Kelas, dst) masih pakai submit+redirect biasa — itu masuk akal karena
     biasanya cuma 1 entri per kunjungan, beda dengan Grid yang memang didesain utk input
     banyak sesi berturut-turut. Kalau Anda mau pola serupa diterapkan ke halaman lain,
     kabari halaman mana yang paling terasa sering diulang-ulang.

0ze. **[FITUR DIHAPUS] Hari Libur — Anda benar, sudah redundan dengan Kalender Akademik.
   WAJIB jalankan migrasi sebelum update kalau Anda punya data lama.**
   - **`database/tambahan_hapus_hari_libur.sql` WAJIB dijalankan** di phpMyAdmin SEBELUM
     pakai versi ini kalau Anda sebelumnya sudah pernah menambahkan tanggal libur — skrip ini
     memindahkan data lama Anda ke Kalender Akademik dulu (sebagai event kategori "Libur"),
     BARU tabel lamanya dihapus. Aman kalau dijalankan di instalasi yang belum pernah pakai
     fitur Hari Libur sama sekali (tidak melakukan apa-apa yang merusak).
   - **Ditelusuri MENYELURUH dulu sebelum menghapus** — saya cek fungsi
     `is_hari_nonaktif()` yang ternyata mengecek DUA sumber (hari_libur DAN Kalender
     Akademik) untuk menentukan "apakah hari ini libur", dipakai di seluruh aplikasi
     (Dashboard, jadwal, dst). Sempat ketinggalan SATU pemakaian lagi di
     `badge_jurnal_terlewat()` (notifikasi "sesi belum lengkap") pada sapuan pencarian
     pertama — baru ketemu di sapuan kedua yang lebih menyeluruh ke seluruh file, bukan cuma
     nama fungsi yang sudah saya duga.
   - **Migrasi diuji dengan skenario nyata**: data aktif, data yang sudah di-soft-delete
     (harus DILEWATI, bukan ikut pindah), dan data yang tanggalnya SUDAH ADA event Libur
     manual di Kalender Akademik (harus TIDAK dobel) — ketiganya benar di pengujian.
   - **Bug ditemukan &amp; diperbaiki di proses saya sendiri**: saat menghapus definisi
     tabel dari `schema.sql`, edit pertama saya meninggalkan baris `) ENGINE=InnoDB...;`
     duplikat &amp; yatim piatu yang bikin instalasi baru gagal dengan error sintaks SQL —
     ketahuan karena saya validasi schema.sql SEBELUM mengirim, bukan diasumsikan benar.
   - Dihapus: Controller, Model, View, route, dan link sidebar Hari Libur. `Mengajar.php`
     &amp; `Sampah.php` disesuaikan supaya tidak lagi bergantung padanya.
   - Diverifikasi akhir: 107 file PHP (turun dari 110) lolos lint, schema.sql tervalidasi
     bersih dari database kosong, DAN migrasi diuji ulang dengan simulasi instalasi lama
     yang masih punya tabel hari_libur — semuanya benar.
   - **Soal permintaan kedua Anda** ("cek fitur apa saja yang sudah tidak perlu") — saya
     belum melakukan audit menyeluruh ke fitur lain. Lebih aman saya laporkan temuan spesifik
     dulu untuk persetujuan Anda per-fitur, daripada menghapus sesuatu yang ternyata masih
     Anda perlukan tanpa konfirmasi.

0zd. **[BUG DIPERBAIKI] Pengumuman tidak muncul di Dashboard guru — Anda benar,
   itu celah nyata.**
   Data Pengumuman saya taruh DI DALAM `$statsAdmin`, yang cuma terisi utk role
   administrator/operator. Guru (dan kepala sekolah) tidak pernah melihat widget ini sejak
   awal dibuat.
   - **Pengumuman sekarang diambil independen dari role** — bukan lagi bagian dari data
     admin, jadi tampil ke SEMUA role yang login, sesuai maksud awal fitur ini (pengumuman
     sekolah relevan utk semua orang, bukan cuma admin).
   - **Link "Lihat semua" diubah jadi "Kelola" dan dibuat kondisional** — cuma tampil kalau
     yang login administrator/operator. Kalau tidak diperbaiki, guru yang klik akan diblokir
     filter role di route tersebut (halaman kelola memang admin-only) — jadi link itu memang
     seharusnya tidak terlihat sama sekali oleh guru, bukan cuma dibiarkan berujung ditolak.
   - Diuji 2 skenario eksplisit (guru &amp; admin) — widget muncul di keduanya, link Kelola
     cuma muncul utk admin, keduanya terverifikasi lewat render langsung bukan diasumsikan.

0zc. **[PERBAIKAN] Presensi mobile, form Jurnal dirapikan, Penilaian disatukan ke card
   Jurnal.**
   - **Akar masalah Presensi di mobile**: pola kartu-mobile saya (label kiri, nilai kanan)
     memaksa 5 pill status kehadiran (Hadir/Sakit/Izin/Terlambat/Alpha) berdesakan di ruang
     sempit di sebelah label "Status kehadiran". Diperbaiki dengan modifier baru khusus utk
     sel berkonten kompleks — label pindah ke ATAS, pill/input jadi FULL WIDTH di bawahnya.
     Diterapkan ke Presensi &amp; Revisi Presensi.
   - **Metode &amp; Media dihapus** dari SEMUA halaman "mengisi jurnal" (Jurnal, Revisi
     Jurnal, Isi Jurnal Terlewat) — 3 file, bukan cuma satu. Kolomnya di database TIDAK
     dihapus (tetap nullable, data lama kalau ada tidak hilang), cuma tidak diminta lagi di
     form.
   - **Penilaian Harian disatukan secara visual ke dalam card Jurnal** — sebelumnya kartu
     terpisah dengan border &amp; shadow sendiri, sekarang jadi seksi di dalam card yang sama
     dengan pembatas garis, terasa satu form utuh bukan dua widget berdampingan.
   - **Layout desktop diperbaiki**: Kendala &amp; Tindak Lanjut sekarang berdampingan 2 kolom
     di layar lebar (dulu bertumpuk penuh membuang ruang), tetap bertumpuk di mobile.
   - Diuji render kelima file yang berubah — field metode/media dipastikan benar-benar hilang
     di ketiganya, pola sel-full-width dipastikan aktif di kedua form presensi.

0zb. **[SESUAI PERMINTAAN] Jadwal Hari Ini di Dashboard pakai gaya timeline, banner
   notifikasi dihapus supaya kartu naik.**
   - **Gaya kartu-baris "jadwal-item" yang baru dibuat 2 turn lalu diganti balik ke gaya
     timeline** (titik warna + ikon jam + kartu di bawahnya) — persis seperti yang sudah ada
     di halaman "Jadwal Hari Ini" mandiri, sesuai screenshot yang Anda kirim.
   - **Banner "Hari ini Anda memiliki X jadwal mengajar..." dihapus total** dari Dashboard —
     kartu Jadwal Hari Ini sekarang jadi elemen paling atas, sejajar dengan kartu Progress
     Hari Ini di sebelahnya, tidak ada lagi jarak ekstra di atas.
   - Diuji render — banner dipastikan benar-benar hilang, status "Selesai" tampil badge
     hijau &amp; status "Belum dimulai" tampil tombol "Mulai mengajar" persis referensi Anda.

0za. **[PEMBERSIHAN] Font &amp; spacing dashboard dirapikan — akar masalah "berantakan"
   ditemukan &amp; diperbaiki, bukan cuma dipoles sedikit.**
   Anda bilang tampilannya terasa berantakan. Saya audit dan ketemu akarnya: saya menulis
   45+ style inline ad-hoc di tiap widget, dengan angka font-size yang beda-beda TIPIS
   (10.5px, 11px, 11.5px, 12px, 12.5px berdampingan) — bukan mengikuti skala yang konsisten.
   - **Dashboard Guru**: dari 48 style inline dengan 5 ukuran font berbeda → 31 style inline
     (sisanya warna ikon yang memang harus dinamis per data) dengan cuma 2 ukuran font.
   - **Dashboard Admin**: dari 56 style inline dengan 6 ukuran font (9.5–22px) → font
     disederhanakan jadi 2 ukuran (angka besar di tengah donut sengaja tetap beda, itu
     memang harus menonjol).
   - **Class baru dipakai KONSISTEN di kedua dashboard** (guru &amp; admin): `.dash-item-title`
     / `.dash-item-meta` utk semua daftar (Riwayat, Pengingat, Aktivitas, Siswa Perlu
     Perhatian), `.mini-stat` utk kartu progress guru (lebih lega dari grid rapat
     sebelumnya), `.quick-action-item` utk Aksi Cepat (sebelumnya 100% inline tanpa class
     sama sekali).
   - Kedua partial ditulis ulang penuh, bukan ditambal — supaya konsisten dari awal, bukan
     campuran gaya lama &amp; baru.
   - Diuji render ulang setelah penulisan ulang — semua data &amp; link yang sudah benar
     sebelumnya (termasuk perbaikan link Penilaian Harian dari turn sebelumnya) dipastikan
     tetap utuh, bukan cuma tampilan yang berubah.

0z9. **[FITUR BESAR] Dashboard Guru dirombak total — jadwal jadi prioritas utama sesuai
   permintaan Anda, termasuk urutan mobile.**
   - **"Jadwal Hari Ini" diubah dari timeline bertitik jadi kartu baris** (waktu, ikon,
     mapel+kelas, badge status, tombol aksi) — sesi yang paling butuh perhatian (sedang
     berlangsung, atau yang pertama belum dimulai) disorot dgn border biru, sesi lain tampil
     lebih redup dengan status "Belum dimulai" tanpa tombol besar, persis pola referensi Anda.
   - **Urutan mobile**: Jadwal Hari Ini ditaruh sebagai elemen PALING ATAS &amp; PERTAMA di
     kolom utama — karena layout dasarnya sudah 1-kolom-menumpuk di mobile (dari redesain
     sebelumnya) dan Jadwal ditulis PALING AWAL di kode, otomatis jadi yang paling atas begitu
     ditumpuk, TANPA perlu trik reorder CSS yang rapuh.
   - **Kartu "Progress Hari Ini"** (2×2: Jadwal, Presensi, Jurnal, Penilaian) — dihitung dari
     sesi guru yang login sendiri hari ini, bukan agregat sekolah.
   - **Riwayat Terbaru** &amp; **Pengingat** (jurnal belum diisi, penilaian belum dibuat,
     tukar jadwal menunggu) — khusus milik guru yang login.
   - **Aksi Cepat** 5 tombol, semua diverifikasi mengarah ke halaman yang BENAR-BENAR ada —
     saya sempat menulis link ke "Penilaian Harian" sebagai halaman berdiri sendiri, lalu
     sadar sendiri saat cek Routes.php bahwa halaman itu TIDAK ADA (Penilaian Harian cuma
     tertanam di form Jurnal per sesi) — diganti sebelum sempat jadi link 404 bagi Anda.
   - **Tombol mengambang "Lanjutkan Aktivitas"** — muncul otomatis mengarah ke sesi yang
     paling butuh tindakan sekarang.
   - **2 hal SENGAJA belum dibuat, jujur disampaikan**: (1) info "Ruang 7A" dst di gambar
     referensi Anda TIDAK saya buat — skema `jadwal` di aplikasi ini tidak punya kolom ruang
     sama sekali, dan menambah field baru di luar cakupan permintaan Anda; (2) stepper
     "Langkah Pembelajaran" (Presensi Guru → Presensi Siswa → Jurnal → Penilaian) TIDAK saya
     tiru persis — alur nyata aplikasi ini cuma 2 langkah (Presensi, lalu Jurnal yang SUDAH
     termasuk Penilaian dalam satu form yang sama), jadi meniru 4 langkah terpisah akan
     menyesatkan soal cara kerja aplikasi yang sebenarnya.
   - Jam berjalan di topbar ("07:15 WIB") DITUNDA — detail kecil, waktu terbatas lebih
     berguna dipakai memastikan prioritas utama Anda (jadwal + urutan mobile) benar.
   - Diuji dengan pola regex presisi (bukan pencarian substring longgar yang sempat memberi
     hasil menyesatkan di percobaan pertama) — jumlah kartu sesi, highlight sesi aktif, dan
     urutan DOM semuanya terverifikasi benar.

0z8. **[BUG KRITIS DIPERBAIKI, DIVERIFIKASI LEWAT KODE SUMBER ASLI] Dashboard Admin error
   fatal #1064 — akar masalah &amp; perbaikan dikonfirmasi 100%, bukan cuma dugaan.**
   Anda kirim screenshot error fatal MySQL #1064 pas buka Dashboard. Ini akar masalahnya:
   - **`->select('DISTINCT ph.siswa_id')`** di `siswaPerluPerhatian()` — saya taruh kata
     "DISTINCT" sebagai bagian dari STRING select(), padahal cara yang benar di Query Builder
     CodeIgniter adalah method terpisah `->distinct()`. Akibatnya, CI4 salah mengira
     "ph.siswa_id" adalah ALIAS dari kolom bernama "DISTINCT" (karena logikanya mencari SPASI
     TERAKHIR untuk menentukan alias), menghasilkan SQL rusak — persis titik error di
     screenshot Anda ("near '.`siswa_id`").
   - **Diverifikasi dgn cara yang lebih kuat dari biasanya**: karena framework CodeIgniter
     sendiri tidak ada di sandbox saya dan tidak bisa diinstal (packagist bukan domain yang
     diizinkan), saya clone LANGSUNG source code CodeIgniter 4.7.4 dari GitHub (bukan lewat
     composer) dan baca sendiri method `select()`, `protectIdentifiers()`, `where()`, dan
     `groupBy()` di file aslinya — bukan cuma menduga dari dokumentasi atau ingatan. Ini
     mengonfirmasi 100% akar masalah DAN bahwa perbaikannya benar, sekaligus menemukan bug
     KEDUA yang mirip (3 pemanggilan `select()` lain yang pakai literal string `"jurnal"` dst
     sbg alias, berisiko masalah serupa) yang saya perbaiki sekalian sebelum sempat jadi bug
     lain di kemudian hari.
   - Pola-pola lain yang saya pakai luas di file yang sama (`where('kolom >=', nilai)`,
     `groupBy('kolom1, kolom2')`) diverifikasi AMAN lewat kode sumber yang sama — bukan
     ikut-ikutan dicurigai tanpa dasar.
   - **Pelajaran soal metode pengujian saya sendiri**: pengujian sebelumnya (PDO mentah)
     memverifikasi LOGIKA query-nya benar, tapi tidak menguji pemanggilan Query Builder CI4
     yang sesungguhnya — itu celah yang membiarkan bug ini lolos. Kali ini saya tutup celah
     itu dengan membaca kode sumber asli, bukan cuma berasumsi.

0z7. **[FITUR BESAR] Dashboard Admin "lengkap" — stat card, progress, monitoring, chart,
   Pengumuman. WAJIB jalankan migrasi baru sebelum pakai.**
   - **`database/tambahan_pengumuman.sql` WAJIB dijalankan** di phpMyAdmin sebelum dashboard
     baru ini bisa dipakai — satu tabel baru, satu-satunya bagian yang genuinely fitur baru
     (bukan sekadar agregasi data lama).
   - **`DashboardAdminService.php`** (baru, di `app/Libraries`) — pusat semua perhitungan:
     stat card (guru hadir, jadwal hari ini, jurnal, penilaian, TP dilaksanakan, tukar
     jadwal), progress operasional, aktivitas terbaru, monitoring guru per-orang, monitoring
     kelas (% siswa dengan minimal 1 penilaian 30 hari terakhir), TP hari ini per mapel,
     analisis penilaian (donut), grafik mingguan (line), siswa perlu perhatian, dan deadline.
     SEMUA dihitung dari tabel yang sudah ada (jadwal, presensi, jurnal, penilaian_harian,
     tukar_jadwal) sesuai arahan Anda — bukan tabel baru.
   - **Diuji SERIUS, bukan cuma lint**: bikin database uji dengan data seed bertanggal hari
     ini, lalu verifikasi 9 titik logika lewat query langsung (jadwal efektif, presensi/
     jurnal mapping, guru hadir, status berlangsung, pengelompokan penilaian, persentase
     kelas, TP hari ini, tukar menunggu) — semua 9 benar. Sempat coba boot CodeIgniter penuh
     tapi ternyata `app/Config/Database.php` tidak ada di struktur proyek ini (temuan baru),
     jadi dialihkan ke verifikasi query langsung yang lebih andal.
   - **Chart SVG murni** (`chart_helper.php`, baru) — donut &amp; line chart tanpa library JS
     sama sekali, sesuai prinsip performa di spesifikasi Anda.
   - **Siswa Perlu Perhatian disederhanakan jadi 2 kriteria** yang bisa dihitung akurat dari
     data yang ada (belum ada penilaian 14 hari; kehadiran &lt;75% bulan ini) — BUKAN 4
     seperti contoh gambar Anda. "Nilai menurun" &amp; "belum mengumpulkan tugas" TIDAK
     dipaksakan karena skema nilai di aplikasi ini teks bebas (bukan angka baku) dan tidak
     ada pelacakan pengumpulan tugas terpisah — mengarang angka di sini lebih berbahaya
     daripada jujur bilang belum ada datanya.
   - **Pengumuman**: model + controller CRUD lengkap + halaman kelola (`master/pengumuman`,
     link sidebar ditambahkan) + widget di rail kanan Dashboard.
   - **Widget Deadline &amp; Pengingat** ditambahkan di rail kanan (jurnal belum diisi, tukar
     jadwal menunggu, kelas belum ada penilaian).
   - Diuji render 3 skenario sekaligus (partial admin_lengkap sendiri, _content.php penuh
     dgn data admin, integrasi widget rail) — total 11 pemeriksaan spesifik, semua benar.
   - 109 file PHP (naik dari 103), lint bersih, skema tervalidasi termasuk tabel baru.

0z6. **[BUG DIPERBAIKI, AKAR YANG BENAR] Dugaan sebelumnya (sticky-column) ternyata bukan
   penyebabnya — Anda kirim screenshot lagi, saya temukan akar sebenarnya.**
   Screenshot Anda menunjukkan bug MASIH terjadi setelah sticky-column dibalikkan — artinya
   dugaan saya sebelumnya salah. Setelah dicek ulang lebih teliti:
   - **Penyebab sebenarnya**: aturan CSS global "sorot seluruh baris tabel saat hover"
     (dipakai luas di tabel data biasa, bagus utk itu) TERNYATA ikut berlaku ke Grid Jadwal
     juga karena sama-sama pakai class `table`. Masalahnya, di Grid Jadwal tiap KOLOM mewakili
     KELAS yang BERBEDA — jadi menyorot satu baris penuh saat hover 1 sel bikin terlihat
     seperti "kelas lain ikut ke-highlight", persis yang Anda gambarkan sebagai "nyasar ke
     kolom lain secara horizontal".
   - **Diperbaiki dengan class baru `table-grid-matrix`** yang menonaktifkan sorot-sebaris
     KHUSUS di tabel bergaya matrix (Grid Jadwal) — sorot per-sel individual (yang sudah saya
     buat sebelumnya) tetap jalan normal, cuma sorot SEBARIS PENUH-nya yang dimatikan.
   - Sel terisi (mis. "Bahasa Inggris") tidak terdampak bug ini sejak awal — warnanya
     eksplisit inline jadi otomatis menang dari aturan hover; bug ini spesifik ke sel kosong
     ("+") yang mengandalkan hover bawaan tabel.
   - Pelajaran: laporan bug dengan screenshot konteks penuh (bukan cuma potongan tabel) jauh
     lebih membantu menemukan akar sebenarnya — dugaan pertama saya masuk akal tapi salah,
     dan tanpa laporan susulan ini kemungkinan saya tidak akan sadar analisisnya keliru.

0z5. **[BUG DIPERBAIKI + FITUR BARU] Sticky-column Grid Jadwal dibalikkan (menyebabkan salah
   sorot), diganti drag-select vertikal per kolom kelas.**
   Anda melaporkan gambar: hover di satu sel malah menyorot sel yang salah (bergeser).
   - **Akar masalahnya kemungkinan besar perubahan sticky-column yang saya tambahkan di turn
     sebelumnya** — saya TIDAK BISA memverifikasi CSS `position: sticky` secara visual tanpa
     browser sungguhan, jadi begitu ada laporan regresi tepat di halaman yang baru saya ubah,
     saya langsung membalikkannya daripada mencoba menambal sesuatu yang tidak bisa saya lihat
     sendiri hasilnya. Grid Jadwal kembali ke scroll horizontal biasa seperti semula.
   - **Drag-select ditambahkan sebagai gantinya** — tahan mouse di sel kosong, seret ke sel
     kosong lain, lepas → form Tambah otomatis terisi jam mulai/selesai sesuai rentang yang
     disorot (memakai dropdown "Sampai jam ke-" yang sudah ada, bukan struktur baru).
   - **Sengaja dibatasi HANYA vertikal** dalam satu kolom kelas yang sama — beda dari Kalender
     Akademik yang horizontal per tanggal, karena satu sesi jadwal memang cuma untuk satu
     kelas; kalau mouse "bocor" ke kolom lain, sorotan berhenti meluas di situ, tidak ikut ke
     kolom sebelah. Kalau menyentuh sel yang sudah terisi, seleksi juga berhenti di situ (tidak
     melompati sesi yang sudah ada).
   - Berfungsi juga di HP/tablet lewat sentuhan.
   - Diuji render dengan data 2 kelas x 2 jam — kelima pemeriksaan struktural (penghapusan
     sticky-col, atribut data pada sel, parameter fungsi baru, mekanisme drag, pembatasan
     kolom) semuanya benar dari percobaan pertama, plus validasi sintaks JavaScript terpisah
     utk skenario ada/tidak ada Guru Pengampu.

0z4. **[PENYELESAIAN] Grid Jadwal, riwayat detail, dan penjelasan kenapa satu halaman
   sengaja tidak disamakan.**
   - **Grid Jadwal (admin)** secara struktural adalah MATRIX (baris=jam, kolom=kelas, dengan
     rowspan utk sesi berdurasi banyak periode) — pola kartu mobile TIDAK cocok di sini karena
     akan menghilangkan makna spasial baris×kolomnya. Sebagai gantinya saya buat pola baru:
     **kolom pertama (label jam) jadi sticky** saat digeser horizontal, jadi tetap tahu ini
     baris jam yang mana walau sudah scroll jauh ke kanan — pola standar utk tabel matrix
     lebar, ditambah petunjuk visual (fade tipis) di tepi kiri.
   - **Riwayat detail presensi** (halaman kecil, per-siswa) dikonversi ke pola kartu mobile
     standar.
   - **Kalender Jadwal guru sendiri (`mengajar/kalender.php`) SENGAJA saya lewati** — bukan
     lupa. Tabel ini punya JavaScript "garis sekarang" yang menghitung posisi berdasar waktu
     berjalan; menambah `position: sticky` di situ berisiko mengganggu perhitungan posisinya
     dan saya tidak bisa memverifikasi visual tanpa browser sungguhan. Lebih aman saya
     laporkan sebagai pengecualian yang disengaja daripada mengambil risiko merusak fitur yang
     sudah berfungsi.
   - Dengan ini, SEMUA tabel data di aplikasi (kecuali satu pengecualian di atas) sudah
     mendapat perlakuan sesuai jenisnya — kartu mobile utk tabel data biasa, kolom sticky utk
     matrix lebar.

0z3. **[BUG AKAR DITEMUKAN &amp; DIPERBAIKI] Input di luar `.form-group` sama sekali tanpa
   styling — inilah yang Anda lihat di Penilaian Harian &amp; Master TP.**
   Anda menunjuk 3 halaman spesifik. Saya cek dan ternyata bukan 3 masalah terpisah, tapi
   SATU akar yang sama: CSS saya punya jaring pengaman untuk `&lt;select&gt;` (supaya tidak
   pernah tampil polos di luar `.form-group`), tapi TIDAK PERNAH dibuatkan yang sama untuk
   `&lt;input&gt;`. Jadi input teks APAPUN yang ditulis di luar `.form-group` — seperti kolom
   Catatan di tabel Penilaian Harian, atau kolom tambah/edit TP yang ditulis inline dalam
   card — 100% tampilan browser default, bukan sekadar "kurang modern".
   - **Diperbaiki sekali di CSS global**, bukan per halaman — jaring pengaman baru untuk
     SEMUA jenis input teks (text/date/time/number/email/dst) &amp; textarea, meniru pola
     yang sudah ada utk select.
   - **Dicek langsung**: grep ke seluruh Views menemukan 4 input yang genuinely tanpa
     `.form-group` (2 di Master TP admin, 2 di Master TP guru) — semuanya otomatis ikut
     styling begitu CSS globalnya diperbaiki, TANPA perlu menyentuh file-file itu satu-satu.
   - **Penilaian Harian juga dirombak sekalian**: card section-nya sebelumnya masih pakai
     gaya lama (border tebal warna kuning, gradient berat) dari sebelum redesain besar —
     diganti hairline border + ikon soft-badge, konsisten dengan Dashboard/Sidebar yang sudah
     diperbarui. Tabelnya juga dikonversi ke pola kartu mobile, dengan hati-hati menjaga
     posisi kolom persis sama (bukan digabung) karena JS-nya bergantung pada indeks kolom.
   - Diuji render ketiga halaman yang Anda sebut — semua benar.

0z2. **[LANJUTAN REDESAIN] Sidebar, touch-target, Kalender Akademik, dan 8 tabel lagi —
   hampir seluruh tabel data di aplikasi kini terkonversi.**
   Melanjutkan redesain ulang di atas dengan beberapa putaran berturut-turut, tetap menahan
   pengiriman file sampai ada kemajuan berarti di tiap putaran.
   - **Audit sistematis target sentuh**: mencari elemen interaktif di bawah 44px lewat grep
     menyeluruh ke seluruh CSS, ditemukan tombol navigasi kalender (`.kal-nav-btn`, dipakai
     Kalender Jadwal guru DAN Kalender Akademik) cuma 30px — dinaikkan ke 36px.
   - **Kalender Akademik**: sel grid diperbesar lagi, jarak antar grup menu sidebar diperlebar.
   - **Jadwal Hari Ini** dikonversi ke pola timeline yang sama dengan Dashboard.
   - **Jurnal &amp; Revisi Jurnal** dapat sticky action bar mobile, sama seperti Presensi.
   - **8 tabel lagi dikonversi ke pola kartu mobile**, ditemukan lewat pencarian sistematis
     (bukan diingat manual) ke semua file yang masih pakai `table-wrap` tanpa
     `table-responsive-cards`: Jadwal admin, Pengguna, Cari Guru Pengganti (2 tabel), Tukar
     Jadwal penuh (2 tabel), Hari Terlewat, rincian aktivitas di Dashboard, dan Tahun
     Ajaran/Semester (2 tabel). **Total sekarang 26+ instansi tabel** — sisa cuma grid Jadwal
     (bukan tabel baris biasa), form Penilaian Harian (tabel dengan dropdown per sel, beda
     kebutuhan), dan 2 halaman detail kecil.
   - Setiap tabel diverifikasi jumlah kolomnya SEGERA setelah diedit (pelajaran dari bug
     sebelumnya), bukan menunggu audit di akhir — semuanya cocok dari percobaan pertama kali
     ini, tidak ada pengulangan bug yang sama.

0y. **[FASE 3 DIMULAI: HALAMAN NON-TABEL] Presensi &amp; Revisi Presensi.**
   Setelah cakupan tabel cukup luas, giliran halaman yang benar-benar dipakai guru setiap
   hari — dimulai dari Presensi, salah satu yang paling sering diisi.
   - Tabel presensi ikut dikonversi ke pola kartu mobile (kolom paling identitatif: nama +
     NIS siswa, jadi judul kartu).
   - **Pill status kehadiran disentralkan** — sebelumnya CSS-nya diketik ulang persis sama di
     2 file terpisah (Presensi &amp; Revisi Presensi), sekarang satu sumber di CSS global,
     sekaligus target sentuhnya disegarkan ke 40px+ (sebelumnya cukup kecil utk standar
     mobile-first yang Anda minta).
   - **Sticky action bar di mobile** — tombol "Simpan" sekarang menempel di bawah layar HP
     (di bawah breakpoint 720px), jadi guru tidak perlu scroll sampai ke bawah dulu setelah
     mengisi presensi kelas dengan banyak siswa. Ini persis fitur yang Anda sebutkan eksplisit
     ("sticky action jika diperlukan") — di desktop perilakunya tetap seperti biasa.
   - Diuji render kedua form sekaligus — semua benar dari percobaan pertama.

0p8. **[FITUR BESAR] Penilaian Harian — bagian dari Jurnal Mengajar, bukan menu sendiri.**
   Sesuai spesifikasi Anda: mencatat penilaian siswa SELAMA proses belajar (keaktifan,
   bertanya, presentasi, dst), BUKAN modul nilai rapor, dan TIDAK memaksa semua siswa dinilai.
   - **Muncul di bagian bawah form Jurnal** sebagai section "Penilaian harian (opsional)"
     dengan tombol "Tambah penilaian" yang membuka tabel daftar siswa kelas yang diajar —
     guru cukup isi baris siswa yang memang dinilai, sisanya dikosongkan.
   - **Aturan paling penting — "Belum Dinilai ≠ Nilai Nol" — diuji sangat ketat** dengan data
     nyata di database: baris yang jenis penilaian ATAU nilainya dikosongkan TIDAK dibuatkan
     record sama sekali (bukan disimpan sebagai kosong/0). Diuji juga skenario revisi: siswa
     yang sudah punya nilai lalu dikosongkan → record lamanya ikut terhapus; nilai yang diubah
     → ter-update (bukan jadi baris duplikat); siswa baru yang diisi kemudian → tersimpan benar.
   - **Guru/Mapel/Kelas/Tanggal/TP/Materi tidak perlu dipilih ulang** — otomatis mengikuti data
     Jurnal Mengajar yang sedang diisi (lewat relasi ke jurnal_id), persis seperti diminta.
   - Kolom "Jenis Penilaian" dan "Nilai" sengaja dibuat teks bebas (bukan pilihan baku) supaya
     fleksibel sesuai kondisi kelas nyata, tapi dilengkapi saran cepat (datalist) — mis.
     Keaktifan/Bertanya/Presentasi untuk jenis, A/A-/B+ dst untuk nilai — supaya input tetap
     cepat tanpa membatasi guru yang ingin mencatat hal lain.
   - Terintegrasi di SEMUA alur pengisian jurnal yang ada (jurnal baru, revisi jurnal, dan
     jurnal untuk hari yang terlewat) — bukan cuma satu jalur, supaya konsisten di mana pun
     guru mengisi jurnal.
   - **Belum dikerjakan, menyusul di sesi berikutnya**: halaman "Riwayat Penilaian" per siswa
     (melihat histori penilaian seorang siswa dari waktu ke waktu) — tabel &amp; logika
     pengambilan datanya (`PenilaianHarianModel::getRiwayatSiswa()`) sudah saya siapkan dan
     ikut teruji, tapi HALAMAN untuk menampilkannya belum saya buat.

0p9. **[REVISI] Penilaian Harian: dropdown asli, tampilan dirombak, dibuat menonjol.**
   - **"Jenis Penilaian" &amp; "Nilai" sekarang dropdown sungguhan** (bukan lagi ketikan bebas
     dengan saran datalist yang kurang terlihat) — berisi pilihan umum (Keaktifan, Bertanya,
     Presentasi, Hafalan, dst untuk jenis; A/A-/B+ dst untuk nilai), plus opsi **"Lainnya…"**
     yang memunculkan kotak teks bebas — jadi tetap fleksibel sesuai filosofi awal fitur ini,
     tidak dikunci ke daftar baku.
   - **Tampilan dirombak jadi jauh lebih menonjol**: border &amp; header warna keemasan,
     ikon, badge "Baru" dan "Opsional", serta **penghitung langsung** ("X dari Y siswa
     dinilai") yang update seketika sambil guru mengisi — baris yang sudah lengkap ikut
     disorot warna hijau.
   - Diuji ketat untuk 3 skenario: form kosong, data yang cocok salah satu pilihan preset
     (dropdown ter-pilih otomatis), dan data KUSTOM yang tidak cocok preset manapun (dropdown
     otomatis pindah ke "Lainnya…" dan kotak teksnya terisi nilai aslinya) — ditelusuri sampai
     ke posisi karakter yang persis di HTML mentahnya, bukan cuma percaya hasil tes sekilas.

0p. **[MODUL BARU] Kalender Akademik.** **Wajib jalankan
   `database/tambahan_kalender_akademik.sql`** kalau database Anda sudah terisi data.
   - Tabel baru khusus (`agenda_akademik`), 8 kategori dengan warna yang SAMA dengan sistem
     warna aplikasi yang sudah ada (KBM=biru, Ujian=merah, Libur=hijau, Rapat=ungu,
     Kegiatan=oranye — dipakai ulang, bukan warna baru — plus PPDB=cyan, Pesantren=coklat,
     Nasional=abu-abu yang baru ditambahkan).
   - **Event berulang mingguan** (mis. "Upacara tiap Senin") disimpan SATU baris saja dan
     diekspansi otomatis saat dibaca — pola yang sama dengan bagaimana Jadwal Mengajar
     bekerja, bukan menyimpan ratusan baris duplikat. Diuji ketat: 6 baris database
     menghasilkan 17 kemunculan kalender yang benar untuk satu bulan, termasuk PTS 5 hari dan
     dua pola berulang berbeda.
   - **Akses sesuai permintaan**: SEMUA role yang login bisa membuka &amp; melihat kalender
     ini, tapi tombol Tambah/Edit/Hapus HANYA muncul untuk Administrator — dan dicek dobel:
     tombolnya disembunyikan di tampilan, DAN rute tambah/ubah/hapusnya sendiri dikunci filter
     role di level routing (bukan cuma disembunyikan di UI), jadi tetap aman walau ada yang
     mencoba akses langsung.
   - Tampilan bulan dengan sidebar Timeline Akademik &amp; Event Terdekat, filter kategori,
     legenda warna, mengambang di kartu bergaya sama dengan Kalender Jadwal yang sudah ada.
     Grid kalendernya pakai CSS Grid (bukan tabel lebar) supaya otomatis menyesuaikan lebar
     layar tanpa scroll horizontal di HP.

0p2. **[LANJUTAN] Integrasi ke Presensi/Jurnal/Dashboard, dan mode tampilan Agenda.**
   - **Fungsi bersama baru `is_hari_nonaktif()`** (di `auth_helper.php`) jadi satu sumber
     kebenaran "apakah tanggal ini libur" — mengecek DUA sumber sekaligus: tabel `hari_libur`
     lama DAN Kalender Akademik baru (kategori apa pun dengan dampak presensi "nonaktif",
     termasuk yang berulang mingguan).
   - Dashboard &amp; "Jadwal Hari Ini" guru sekarang otomatis kosong pada hari libur (dari
     kedua sumber), dengan **pesan spesifik "Hari ini libur: [nama event]"** — bukan lagi
     cuma pesan generik. Statistik progres semester &amp; deteksi "Hari Terlewat" juga ikut
     mengecualikan tanggal libur dari Kalender Akademik, konsisten dengan `hari_libur` lama.
   - **Banner "Agenda hari ini"** baru di puncak Dashboard (semua role) menampilkan Ujian/
     Kegiatan/Rapat yang berlangsung hari ini, dengan tautan langsung ke Kalender Akademik.
   - **Mode tampilan Agenda** sekarang aktif (daftar 60 hari ke depan, dikelompokkan per
     tanggal) — tab Agenda di halaman Bulan sudah tersambung.
   - Diuji end-to-end dengan data nyata (termasuk skenario gabungan banner + pesan libur guru
     tampil bersamaan) — nol error PHP di semua skenario.

0p3. **[LANJUTAN] Mode tampilan Minggu, ketiga tab sekarang saling tersambung.**
   - Grid 7 kolom seperti Bulan, tapi selnya jauh lebih tinggi dan menampilkan **SEMUA** event
     hari itu tanpa batas "+N lainnya" — masuk akal karena cuma 7 sel per layar (bukan 35-42
     seperti Bulan), jadi ruangnya jauh lebih lega per hari.
   - Navigasi minggu sebelumnya/berikutnya, tombol "Hari ini" (cuma muncul kalau BUKAN sedang
     di minggu berjalan), kolom hari ini disorot warna.
   - Tab Bulan/Minggu/Agenda di ketiga tampilan sekarang saling tersambung penuh (sebelumnya
     tombol Minggu nonaktif di dua tampilan lain).
   - Diuji dengan skenario 3 event di satu hari untuk memastikan ketiganya benar-benar tampil
     tanpa terpotong — sempat ada kecurigaan bug palsu (kata "lainnya" ditemukan di teks),
     saya telusuri dan ternyata itu cuma bagian dari kalimat subjudul biasa ("...dan kegiatan
     lainnya"), bukan indikator pembatas — dikonfirmasi lewat pengecekan langsung ke file.

0p4. **[REVISI] Checkbox filter kategori dirombak, dan 2 warna kategori dibetulkan.**
   - **Checkbox mini warna-warni dihapus** (kesannya berantakan karena 8 warna berbeda
     berjejer kecil-kecil) — diganti pola "chip toggle" ala Notion/Linear: status aktif/
     nonaktif sekarang ditandai lewat warna chip itu sendiri (latar lembut + border + teks
     warna kategori saat aktif, abu-abu netral saat nonaktif), bukan lewat kotak centang.
     Checkbox aslinya tetap ada secara fungsional (disembunyikan visual saja) supaya tetap
     bisa dijangkau keyboard/pembaca layar.
   - **Bug ditemukan saat audit warna**: kategori "Nasional" ternyata pakai warna navy (biru
     tua) yang KELIRU — spesifikasi asli Anda minta "Nasional = Abu", bukan biru. Sudah
     diperbaiki pakai abu-abu netral yang genuinely abu-abu (baru ditambahkan ke palet, bukan
     ada di sistem warna sebelumnya karena semua warna lama condong ke biru).
   - **Kategori PPDB dipindah ke warna teal** (bagian dari identitas warna aplikasi sendiri,
     dipakai di logo/sidebar) — sebelumnya warna cyan yang dipilih terlalu berdekatan dengan
     biru KBM, gampang tertukar sekilas lihat.
   - Karena warnanya terpusat di satu tempat (`AgendaAkademikModel::KATEGORI`), perbaikan ini
     otomatis berlaku di SEMUA tempat sekaligus — grid Bulan, Minggu, Agenda, legenda,
     timeline, tanpa perlu ubah satu-satu. Diuji ulang dengan file Model asli (bukan tiruan)
     untuk memastikan warna yang benar-benar tersimpan yang muncul di hasil render.

0p5. **[FITUR] Blok-pilih rentang tanggal di Kalender Akademik (klik-tahan-seret).** Sekarang
   di tampilan Bulan, admin bisa tekan mouse di satu tanggal lalu seret ke tanggal lain untuk
   langsung memilih rentangnya — form Tambah Event otomatis terisi tanggal mulai &amp;
   selesai sesuai yang disorot, tidak perlu ketik manual satu-satu. Klik biasa (tanpa
   menyeret) tetap berfungsi seperti sebelumnya, cuma memilih 1 hari.
   - Berfungsi juga di HP/tablet lewat sentuhan (tahan lalu geser jari), bukan cuma mouse.
   - Klik pada event yang sudah ada (pill) tetap membuka mode edit seperti biasa, tidak ikut
     memulai seleksi rentang baru — sudah saya pastikan lewat pengecekan target klik.
   - Diuji render dengan skenario admin vs non-admin (mekanisme ini cuma aktif untuk admin,
     sesuai batasan hak CRUD yang sudah ada), plus validasi sintaks JavaScript terpisah.

0p6. **[REVISI] Urutan kalender, warna, dan navigasi Kalender Akademik dibenahi.**
   - **Urutan hari sekarang Minggu-Senin-...-Sabtu** (kalender umum), bukan lagi Senin di
     awal — berlaku di tampilan Bulan maupun Minggu.
   - **Angka tanggal hari Minggu diberi warna merah**, sesuai konvensi kalender pada umumnya.
   - **Nama-nama hari di header tabel dibuat warna gelap/hitam** (sebelumnya abu-abu yang
     memang kurang kelihatan) — diterapkan lewat satu aturan CSS yang dipakai kedua tampilan
     sekaligus.
   - **Panah navigasi bulan sekarang punya keterangan** (arahkan kursor ke panahnya) yang
     menyebutkan nama bulan tujuan, mis. "Bulan sebelumnya: Juli 2026".
   - **Badge "Bulan ini"** muncul di sebelah nama bulan saat sedang menampilkan bulan
     berjalan — tombol "Hari ini" otomatis disembunyikan saat itu (karena jadi tidak perlu),
     dan muncul lagi begitu Anda pindah ke bulan lain.
   - **Bug ditemukan &amp; diperbaiki sebelum sempat terkirim**: mengubah urutan hari di
     Controller sempat bikin tampilan Minggu jadi TIDAK SELARAS (kolom "Senin" akan
     menampilkan tanggal hari Minggu yang sebenarnya) karena daftar nama harinya di View
     belum ikut diubah — ketahuan &amp; dibetulkan sebelum pengujian akhir, bukan sesudah.
   - Saya pastikan perubahan ini TIDAK memengaruhi Kalender Jadwal (jadwal mengajar guru)
     karena keduanya memakai fungsi hitung minggu yang benar-benar terpisah, sudah dicek
     sebelum mengubah apa pun.

0p7. **[FITUR] Hapus event lebih mudah ditemukan.** Sebenarnya sudah ada sejak awal (lewat
   tombol Hapus di dalam modal Edit), tapi caranya kurang terlihat — harus klik event dulu
   baru ketemu tombolnya di dalam modal. Sekarang ditambah jalur cepat:
   - **Tampilan Bulan &amp; Minggu**: setiap event yang sudah dibuat, kalau kursor diarahkan
     ke situ, muncul tombol × kecil yang langsung menghapus (dengan konfirmasi) — tidak perlu
     buka modal edit dulu.
   - **Tampilan Agenda**: setiap baris event punya ikon tempat sampah yang jelas terlihat
     tanpa perlu hover, karena baris di sana lebih lega.
   - Event juga sekarang punya `cursor:pointer` yang jelas untuk admin (sebelumnya kursor
     tetap panah biasa walau event-nya bisa diklik) — supaya lebih terlihat interaktif.
   - Diuji di ketiga tampilan untuk admin maupun non-admin; sempat ada 2 hasil uji yang
     terlihat gagal, saya telusuri sampai tuntas dan keduanya ternyata murni kesalahan skrip
     pengujian saya sendiri (bukan bug aplikasi) — dibuktikan dengan menjalankan ulang
     memakai logika perhitungan minggu yang sama persis dengan Controller sungguhan.

0o. **[BUG DIPERBAIKI] Dropdown filter di toolbar tampil polos tanpa gaya** (border tebal
   default browser, bukan gaya aplikasi) — akar masalahnya CSS saya cuma menata `<select>`
   yang ada DI DALAM `.form-group`, sedangkan dropdown filter di toolbar (Siswa, Sampah, Tukar
   Jadwal admin) sengaja saya taruh langsung di toolbar tanpa pembungkus itu. Saya audit
   seluruh aplikasi untuk pola yang sama, ketemu 4 dropdown di 3 halaman — semuanya sekarang
   tertata rapi lewat satu aturan CSS umum yang berlaku ke semua `<select>` di mana pun
   dipakai, tingginya disamakan persis dengan kotak pencarian di sebelahnya.

0n. **[BUG DIPERBAIKI, KRITIS] "NIS ini sudah dipakai siswa lain" muncul padahal NIS-nya cuma
   dipakai siswa itu sendiri** — sama persis terjadi juga di menu Hari Libur ("Tanggal ini
   sudah terdaftar"). Saya lacak sampai ke source code asli CodeIgniter 4 untuk memastikan
   akar masalahnya (bukan cuma tebak-tebakan): aturan validasi `is_unique[...,id,{id}]`
   dipakai untuk mengecualikan data itu sendiri saat sedang diedit, tapi placeholder `{id}`-nya
   CUMA diganti kalau (a) field `id` punya aturan validasinya sendiri, DAN (b) `id` benar-benar
   ada sebagai key di data yang dikirim ke `update()` — dua-duanya belum terpenuhi di kode
   sebelumnya. Akibatnya placeholder `{id}` tidak pernah diganti, jadi sistem selalu
   menganggap "data itu sendiri" sebagai duplikat setiap kali diedit, walau NIS/tanggalnya
   tidak diubah sama sekali. Saya verifikasi perbaikannya dengan mereplikasi persis algoritma
   dari source code CodeIgniter (bukan cuma percaya dokumentasi) — sebelum perbaikan
   placeholder terbukti tidak pernah ditemukan, sesudah perbaikan terbukti ditemukan dan
   diganti dengan benar.

0m. **[FITUR] Data Master Siswa: filter &amp; hapus massal, Kelas: jumlah siswa.**
   - **Filter Kelas &amp; Jenjang** di halaman Siswa — bisa dikombinasikan dengan kotak cari
     yang sudah ada, semuanya bekerja bersamaan secara langsung (tanpa reload halaman).
   - **Checkbox pilih banyak siswa sekaligus** — centang di header untuk pilih semua yang
     SEDANG TERLIHAT (menghormati filter yang aktif, bukan asal semua baris), tombol "Hapus
     yang dipilih" muncul begitu ada yang dicentang. Tetap soft delete seperti hapus satu-satu
     (bisa dipulihkan lewat Sampah), sudah saya uji langsung: hapus 2 dari 4 siswa, tersaring
     dari tampilan normal tapi baris fisiknya tetap ada 4 di database.
   - **Halaman Kelas menampilkan jumlah siswa aktif per kelas**, diklik langsung membuka
     halaman Siswa dengan filter kelas itu otomatis terpasang — teruji hitungannya benar
     (siswa nonaktif/tanpa kelas tidak ikut terhitung).

0k. **[FITUR BESAR] Jam pelajaran sekarang bisa beda tiap hari** — sebelumnya satu set jam
   (jam ke-1 mulai jam X, dst) berlaku SAMA untuk semua hari. Sekarang tiap hari punya jamnya
   sendiri, sesuai kondisi sekolah Anda yang sebenarnya (mis. Senin jam ke-1 mulai 08.05,
   Selasa mulai 07.30).
   - **Halaman Jam Pelajaran** dirombak jadi tab per hari (Senin-Sabtu), masing-masing punya
     daftar periode &amp; tombol Tambah/Edit/Hapus sendiri.
   - **Form Tambah/Edit Jadwal** (daftar maupun grid): dropdown "Jam ke" sekarang otomatis
     menyesuaikan pilihan hari — pilih Selasa, opsinya berubah ke jam-jam Selasa, bukan lagi
     daftar jam yang sama terus untuk semua hari.
   - **Template &amp; import Excel** (bentuk grid maupun daftar panjang) ikut disesuaikan —
     template grid sekarang punya kolom referensi "Jam" yang menampilkan jam sungguhan tiap
     hari (supaya Anda tidak perlu bolak-balik cek menu Jam Pelajaran saat mengisi), dan baris
     Jumat/Sabtu otomatis lebih sedikit kalau memang jam pelajarannya lebih sedikit hari itu.
   - **Kalender Jadwal guru**: garis "sekarang" sekarang menghitung posisi dari jam sungguhan
     HARI INI, bukan jam yang disamaratakan untuk semua hari.
   - Migrasinya AMAN untuk data yang sudah ada — jam yang sudah Anda atur sebelumnya otomatis
     digandakan ke keenam hari sebagai titik awal yang sama seperti sebelumnya; tinggal EDIT
     hari-hari yang memang berbeda sesuai kondisi sekolah Anda.
   - **Ditemukan &amp; diperbaiki 3 bug nyata saat membangun ini** (diuji langsung, bukan
     cuma dicek sintaks): (1) data contoh jam pelajaran sempat belum menyertakan kolom hari
     yang baru; (2) urutan penghapusan aturan lama di file migrasi sempat salah, bikin migrasi
     gagal; (3) beberapa Controller (termasuk Kalender guru dan beberapa bagian halaman Jadwal
     admin) sempat masih memanggil fungsi lama yang sudah diganti — kalau saya tidak
     menemukannya sendiri lewat sapuan menyeluruh, halaman-halaman itu akan error total begitu
     dibuka.

0l. **[UI] Navbar Administrator dirapikan** — "Tukar Jadwal" (persetujuan) sebelumnya
   menumpang di bawah "Data Master", sekarang punya section sendiri ("Tukar jadwal") bersama
   "Cari Guru Pengganti" yang dipindah dari Laporan — keduanya memang satu tema (pengelolaan
   pertukaran/pengganti jadwal), bukan laporan baca-saja atau data master murni. Section
   "Laporan" sekarang murni berisi laporan baca-saja (Presensi, Jurnal).

0i. **[UI] Nomor urut ("No.") ditambahkan di SEMUA tabel data** — sebelumnya kolom pertama
   langsung nama/tanggal, sekarang semua diawali nomor baris. Mencakup 26 tabel di seluruh
   aplikasi (Data Master, Laporan, Riwayat, Cari Guru Pengganti, Tukar Jadwal, Dashboard, dll)
   — sudah saya sapu ulang secara otomatis untuk memastikan tidak ada yang terlewat. Audit Log
   nomornya sadar-halaman (di halaman 2 lanjut 26, 27, dst, bukan reset ke 1) karena tabel itu
   pakai pagination. Dua tabel bentuk GRID (Kalender, dan tampilan grid Jadwal admin) sengaja
   tidak diberi nomor karena bukan daftar baris, melainkan silang hari×jam.
0j. **[PERFORMA] Index database ditambahkan** untuk query rentang tanggal di Laporan (Rekap
   Presensi &amp; Rekap Jurnal) yang sebelumnya kurang optimal karena filternya cuma tanggal
   tanpa jadwal_id spesifik — index gabungan yang sudah ada sebelumnya kurang membantu untuk
   pola ini. Sebagian besar tabel lain ternyata sudah terindeks dengan baik sejak awal (mis.
   `presensi`/`jurnal_mengajar` sudah punya index `(jadwal_id, tanggal)` dari constraint unique
   yang sekaligus berfungsi sebagai index, `jadwal` sudah ada index `(guru_id, hari)`) — jadi
   penambahan kali ini ditargetkan ke celah yang benar-benar ada, bukan menambah index secara
   membabi buta. Tabel baru: lihat `database/tambahan_index_performa.sql` untuk database yang
   sudah ada isinya (aman dijalankan kapan saja, cuma menambah index).
   - **Soal responsif**: fondasinya (viewport meta tag, `.table-wrap` dengan scroll horizontal
     terkendali di setiap tabel, `.toolbar` yang otomatis melipat ke baris baru di layar
     sempit) sudah saya verifikasi masih berlaku termasuk di komponen-komponen terbaru
     (Kalender, tabel mingguan Dashboard). Saya belum melakukan audit visual penuh di berbagai
     ukuran layar sungguhan (keterbatasan lingkungan kerja saya) — kalau Anda menemukan
     halaman/komponen spesifik yang masih terlihat janggal di HP, kasih tahu saya, saya
     perbaiki spesifik ke situ.

0h. **[REVISI] Dashboard guru disederhanakan jadi tepat 3 bagian** sesuai arahan Anda, tidak
   lebih: (1) jadwal hari ini (kartu, sama seperti sebelumnya), (2) **jadwal minggu ini dalam
   bentuk tabel** (Hari/Jam/Kelas/Mapel, satu baris per sesi, ikut memperhitungkan Tukar
   Jadwal yang aktif minggu ini — baris hari ini disorot), (3) notifikasi kalau ada presensi/
   jurnal yang belum diisi. Stat progres semester, kartu "Butuh perhatian", dan tombol aksi
   cepat yang sebelumnya saya tambahkan sudah dilepas semua.

0g. **[FITUR BESAR] Dashboard dirombak — dipisah dari Jadwal Hari Ini, dan Dashboard Admin
   sekarang punya rincian per kelas.**
   - **Jadwal Hari Ini jadi halaman sendiri** (`/jadwal-hari-ini`) — cuma memuat kartu jadwal
     hari ini, tidak ada statistik atau konten lain lagi.
   - **Dashboard guru** sekarang berisi ringkasan (bukan daftar kartu lagi): progres hari ini
     (X/Y selesai), progres semester (pakai perhitungan yang sama dengan Riwayat Mengajar),
     kartu pengingat "Butuh perhatian" yang menarik dari badge sidebar yang sama (jurnal
     terlewat, pengajuan Cari Guru Pengganti/Tukar Jadwal yang menunggu respon), dan tautan
     cepat ke Jadwal Hari Ini/Kalender/Cari Guru Pengganti.
   - **Dashboard Admin &amp; Kepala Sekolah** sekarang punya tabel rincian "siapa sudah, siapa
     belum" — setiap sesi hari ini se-sekolah (jadwal EFEKTIF, ikut memperhitungkan Tukar
     Jadwal yang aktif) ditampilkan satu per satu: jam, guru, kelas, mapel, status. Diurutkan
     yang paling butuh perhatian (belum mulai) di atas. Sebelumnya cuma angka agregat
     ("3 belum presensi"), sekarang langsung kelihatan SIAPA dan KELAS MANA.
   - Diuji dengan render simulasi penuh (guru+admin+kepala sekolah sekaligus, plus skenario
     nyata di database mencakup ketiga status: selesai, belum, dan digantikan) — nol error.

0f. **[VISUAL] Kalender Jadwal dirombak jadi gaya floating card** (terinspirasi Google
   Calendar/Linear/Notion, atas masukan Anda) — bukan lagi seluruh sel tabel diwarnai penuh.
   - Tiap jadwal tampil sebagai **kartu mengambang**: latar putih, shadow halus, sudut
     membulat 12px, strip warna 4px di kiri sebagai penanda status, ikon kecil bulat di
     pojok kanan atas (centang/dokumen/jam/peringatan).
   - **Kolom hari ini** diberi latar abu-abu muda supaya langsung kelihatan tanpa perlu cari.
   - **Garis "sekarang"** (garis merah tipis + label jam) otomatis muncul melintasi grid pada
     posisi jam sungguhan saat ini — dihitung &amp; digambar lewat JavaScript (proporsional di
     dalam periode jam yang sedang berlangsung, bukan cuma nempel di batas atas/bawah baris),
     cuma muncul kalau minggu yang sedang dilihat memuat hari ini.
   - **Ringkasan mingguan dipadatkan jadi pill kecil di toolbar** ("5 Selesai", "2 Belum
     diisi", dst — dot warna + angka), bukan kartu besar terpisah — sesuai masukan Anda soal
     "jangan terlalu besar-besar".
   - Saya uji dengan simulasi render PHP penuh (bukan cuma cek sintaks) untuk memastikan tidak
     ada warning/error tersembunyi, dan JavaScript-nya divalidasi terpisah dengan Node.
   - **[REVISI] Bug kontras ditemukan &amp; diperbaiki**: label "Jam ke-" dan nama hari di
     header ternyata sempat pakai abu-abu terlalu muda (`text-soft`), susah dibaca — sekarang
     semua teks struktural (header, label jam) pakai warna gelap solid, `text-soft` cuma
     dipakai untuk info sekunder yang genuinely boleh redup. Toolbar juga dirapikan (tombol
     navigasi bulat minimal ala Notion Calendar), padding sel dilebarkan ke 7px, shadow kartu
     dibuat lebih tipis lagi, dan kartu sekarang benar-benar mengisi tinggi rowspan-nya (pakai
     flexbox, bukan cuma min-height) supaya sesi 2-3 jam berurutan terlihat menyatu.
   - **[KOREKSI dari revisi di atas]** Pendekatan "kartu melar mengisi penuh rowspan" ternyata
     bikin kartu jadi kotak putih kosong yang besar untuk sesi berjam-jam (ketahuan dari
     screenshot yang Anda kirim). Diganti pendekatan "penanda": kartu sekarang ringkas
     mengikuti isinya di bagian atas sel, dan sesi yang berlangsung lebih dari satu jam ke
     ditandai garis tipis warna aksen + label kecil "s/d Ke-N" di bawahnya — bukan kartu yang
     dipaksa melar.
   - **[REVISI lagi]** Label "s/d Ke-N" tadi ternyata terlalu pudar (opacity kelewat rendah),
     diperbaiki jadi lebih tegas &amp; terbaca. Kasus "Digantikan"/"Menggantikan" sekarang
     punya **warna ungu tersendiri** (`--color-substitute`) — tidak lagi menumpang ke warna
     abu-abu pudar atau warna status selesai/belum biasa — dan ikut ditampilkan sebagai pill
     hitungan "N Tukar/Gantian" di toolbar, sejajar dengan Selesai/Belum diisi/Terlewat/Akan
     datang. Untuk kasus "Menggantikan", ikon status penyelesaian yang sebenarnya (selesai/
     belum/terlewat) tetap ditampilkan, cuma warnanya ikut ungu — jadi tetap tahu progresnya
     tanpa kehilangan sinyal "ini sesi gantian".
   - **[REVISI, audit warna abu muda]** Dua sisa pemakaian abu muda di Kalender ditemukan &amp;
     diganti: kartu "Akan datang" sebelumnya pakai border abu-abu (tidak sinkron dengan pill
     birunya di toolbar) — disamakan jadi biru. Kartu "Digantikan" sebelumnya diberi efek pudar
     (opacity 65%) yang bikin warna ungunya ikut terlihat pucat — efek pudar itu dihapus,
     warna ungunya sekarang tampil solid.
   - **[BUG DIPERBAIKI]** Label "s/d Ke-" tampil kosong tanpa angka khusus untuk sesi
     "Menggantikan" (saya meng-cover jadwal guru lain lewat Cari Guru Pengganti). Akar
     masalahnya: kode yang menyusun sel kalender untuk kasus ini menghitung jam awal/akhirnya
     tapi lupa menyertakannya balik ke data sel — beda dari jalur jadwal milik sendiri yang
     sudah benar dari awal. Sudah diuji ulang dengan skenario persis dari screenshot Anda
     (sesi 3 jam ke, "Menggantikan contoh") — sekarang benar tampil "s/d Ke-3".

0. **[FITUR BESAR] Soft Delete + Sampah — data yang dihapus bisa dipulihkan.** Sebelumnya
   tombol "Hapus" di Data Master (Guru, Siswa, Kelas, Mata Pelajaran, Jam Pelajaran, Hari
   Libur, Jadwal) menghapus permanen. Sekarang datanya cuma ditandai terhapus (kolom
   `deleted_at`), tetap ada di database, dan bisa dipulihkan kapan saja lewat menu **Sampah**
   (Sistem → Sampah, khusus Administrator).
   - Pakai fitur bawaan CodeIgniter (`useSoftDeletes`), bukan bikin sendiri dari nol — jadi
     otomatis konsisten dengan cara Model bekerja: query normal (`findAll`, dsb) otomatis
     menyaring data terhapus tanpa saya ubah satu pun kode Controller yang sudah ada.
   - Halaman Sampah menampilkan gabungan SEMUA jenis data yang terhapus (bisa difilter per
     jenis), lengkap kapan dihapusnya, dengan tombol Pulihkan satu klik.
   - **Pengguna (users) ternyata sudah soft-delete sejak awal** — kolom `deleted_at`-nya
     sudah ada di skema paling awal, cuma belum pernah saya sambungkan ke halaman Sampah;
     sekarang sudah ikut tampil di sana juga.
   - Setiap pemulihan tercatat di Audit Log ("Memulihkan [jenis]: [nama] dari Sampah").
   - Tabel baru: lihat `database/tambahan_soft_delete.sql` untuk database yang sudah ada
     isinya (menambahkan kolom `deleted_at` ke 7 tabel — `users` sudah punya dari awal).
   - **Catatan jujur**: Wajib jalankan file tambahan itu SEKALI di database Anda sebelum
     fitur Hapus di Data Master dipakai lagi — kalau belum, `useSoftDeletes` akan mencari
     kolom `deleted_at` yang belum ada dan tombol Hapus akan error. Instalasi baru dari nol
     lewat `schema.sql` sudah otomatis termasuk, tidak perlu file tambahan.
   - **Belum selesai** (bagian kedua dari permintaan Anda): hak admin untuk membatalkan
     pengajuan Tukar Jadwal / Cari Guru Pengganti MILIK GURU LAIN — saat ini pembatalan masih
     terbatas ke pemilik pengajuannya sendiri. Ini pekerjaan berikutnya, beri tahu saya untuk
     lanjut ke situ.

0c. **[FITUR PENTING] Isi Jurnal Terlewat** — untuk sesi yang presensinya sudah diisi tapi
   jurnalnya lupa tidak pernah diisi sama sekali. Sebelumnya sesi seperti ini "hilang" begitu
   harinya lewat, karena Dashboard cuma menampilkan jadwal HARI INI — tidak ada jalan untuk
   kembali melengkapinya.
   - **Riwayat Mengajar** sekarang menandai sesi yang jurnalnya kosong dengan jelas ("Jurnal
     belum diisi — isi sekarang", bisa diklik langsung dari daftar), bukan cuma teks pasif
     seperti sebelumnya.
   - Halaman **Detail sesi** juga saya perbaiki — sebelumnya kalau jurnal belum ada cuma
     menampilkan pesan kosong tanpa tombol aksi apa pun (jalan buntu). Sekarang ada tombol
     "Isi jurnal sekarang" yang langsung ke form pengisian.
   - Mengisi jurnal lewat jalur ini otomatis mengunci presensi &amp; jurnal-nya sekaligus,
     persis seperti alur normal — sesi jadi tercatat selesai sepenuhnya.
   - **Badge pengingat baru** di menu Riwayat Mengajar: jumlah sesi 30 hari terakhir yang
     presensinya ada tapi jurnalnya belum diisi — supaya guru tidak perlu ingat-ingat sendiri,
     langsung kelihatan begitu buka aplikasi.
   - Tercatat di Audit Log, dan dilindungi jalur yang sama seperti fitur lain (cuma pemilik
     sesi atau guru pengganti yang disetujui yang bisa mengisi, tidak bisa dobel isi).

0e. **[REVISI berdasarkan masukan Anda] "Terlewat" dipindah dari Riwayat ke Kalender, plus
   tanggal berlaku semester.**
   - **Kalender Jadwal** sekarang menampilkan status tiap sel (Selesai / Jurnal belum diisi /
     Terlewat / Hari ini-akan datang, dengan warna &amp; legenda) — klik sel yang belum
     lengkap untuk langsung melengkapinya, termasuk untuk minggu-minggu yang sudah lewat. Ini
     menggantikan pendekatan sebelumnya yang menaruh aksi "lengkapi" di halaman Riwayat.
   - **Riwayat Mengajar dikembalikan murni jadi riwayat** — tombol/tautan "isi yang terlewat"
     saya lepas dari sana; yang tersisa cuma "Revisi" (untuk memperbaiki data yang SUDAH ada),
     karena itu memang tindakan yang wajar ada di halaman riwayat.
   - **Tanggal berlaku semester** (menu Tahun Ajaran &amp; Semester → kolom baru "Tanggal
     berlaku", bisa diedit) — fondasi supaya ke depannya rekap "berapa kali mengajar dari
     berapa yang seharusnya" bisa dihitung akurat berdasarkan tanggal semester sungguhan,
     bukan tebakan 30 hari terakhir seperti sebelumnya.
   - Tabel baru: lihat `database/tambahan_tanggal_semester.sql` untuk database yang sudah
     ada isinya.
   - **[LANJUTAN, SELESAI] Badge &amp; deteksi "terlewat" sekarang disambungkan ke tanggal
     semester** — jendela pengecekan maksimal 30 hari ke belakang seperti sebelumnya, TAPI
     sekarang dijepit supaya tidak pernah melewati tanggal_mulai/tanggal_selesai semester
     aktif. Sudah saya uji dua arah: semester yang baru mulai (kurang dari 30 hari) tidak lagi
     salah menandai hari SEBELUM semester dimulai sebagai "terlewat", dan semester yang sudah
     lama berjalan tetap dibatasi 30 hari (tidak menumpuk daftar yang membengkak).
   - **[LANJUTAN, SELESAI] Statistik "sudah mengajar X dari Y sesi" ditambahkan** di halaman
     Riwayat Mengajar — Y dihitung dari pola jadwal mingguan dikalikan berapa kali harinya
     sudah lewat sejak semester mulai (dikurangi hari libur), X dari jurnal yang benar-benar
     terisi. Saya uji dengan data nyata: semester dengan 3 kali jadwal Rabu sejak mulai, baru
     1 yang diisi jurnalnya — hasil "seharusnya=3, sudah=1, terlewat=2" persis sesuai hitungan
     manual. Kalau semester aktif belum diisi tanggal berlakunya, halaman ini menampilkan
     pengingat alih-alih angka yang menyesatkan.
   - Kalender Jadwal juga saya sesuaikan: tombol "Minggu sebelumnya/berikutnya" sekarang
     otomatis nonaktif kalau akan menavigasi ke luar tanggal berlaku semester.

0d. **[FITUR PENTING, LANJUTAN] Hari Terlewat — untuk presensi yang lupa diisi sama sekali**,
   beda dari Isi Jurnal Terlewat di atas (yang presensinya SUDAH ada, cuma jurnalnya kosong).
   Ini untuk kasus guru benar-benar lupa membuka aplikasi sama sekali di hari itu — tidak ada
   presensi maupun jurnal sama sekali.
   - Karena tidak ada riwayat presensi untuk dirujuk, tanggal yang "terlewat" harus
     DIREKONSTRUKSI dari pola jadwal mingguan guru (hari yang cocok dengan jadwal, bukan hari
     libur, dan belum ada presensi) — sudah saya uji dengan data nyata, termasuk memastikan
     tanggal yang kebetulan hari libur TIDAK ikut dianggap terlewat.
   - Menu baru "Hari Terlewat" (link dari halaman Riwayat Mengajar) menampilkan daftar
     tanggal+jadwal yang terlewat 30 hari terakhir, tinggal klik "Isi sekarang" untuk
     melengkapi presensi lalu lanjut ke jurnal — alurnya sama seperti mengajar hari ini,
     cuma untuk tanggal yang sudah lewat.
   - Badge di menu Riwayat Mengajar sekarang gabungan dua kondisi (jurnal kosong + hari
     terlewat sepenuhnya), supaya guru cukup lihat satu angka untuk tahu ada berapa yang
     perlu dilengkapi.
   - **Bug yang saya temukan &amp; perbaiki sendiri saat membangun ini**: sempat tidak sengaja
     membuat definisi fungsi PHP dobel (`badge_jurnal_terlewat`) yang membuat logika barunya
     tidak pernah benar-benar jalan — ketahuan &amp; diperbaiki sebelum dikirim, bukan lolos ke
     paket akhir.

0b. **[LANJUTAN] Admin/Operator sekarang punya hak override pembatalan** — bagian kedua dari
   permintaan Sampah di atas.
   - **Laporan → Cari Guru Pengganti**: tombol "Batalkan" baru di tiap baris (status menunggu
     atau disetujui), tidak terikat siapa pemilik pengajuannya.
   - **Data Master → Tukar Jadwal**: tombol "Batalkan" baru di tabel "Semua pengajuan", bisa
     dipakai bahkan untuk pertukaran yang SUDAH AKTIF (status disetujui) — begitu dibatalkan,
     jadwal yang bersangkutan otomatis kembali ke posisi aslinya karena `ScheduleResolverService`
     memang cuma menganggap aktif pertukaran berstatus "disetujui". Sudah saya uji langsung
     skenario ini dengan data nyata: sebelum dibatalkan resolver mendeteksi aktif, sesudah
     dibatalkan resolver langsung tidak mendeteksinya lagi.
   - Kedua override ini dibatasi role Administrator &amp; Operator (bukan Kepala Sekolah, yang
     tetap murni memantau), dan setiap pembatalan tercatat di Audit Log dengan jelas siapa
     adminnya, plus catatan otomatis ditempel ke pengajuan yang dibatalkan.

1. **[UI/UX] Audit menyeluruh lanjutan — semua file view sudah diperiksa satu per satu**, bukan
   cuma yang paling sering dipakai. Temuan &amp; perbaikannya:
   - **Pola "stat card manual" disatukan di 3 tempat tambahan** yang sebelumnya lolos dari sapuan
     pertama karena urutan propertinya beda (`text-transform:uppercase;font-size:11px` vs
     `font-size:11px;text-transform:uppercase`) — Laporan Presensi &amp; Laporan Cari Guru
     Pengganti. Sekarang benar-benar nol sisa di seluruh aplikasi (sudah di-grep ulang untuk
     memastikan).
   - **Ketidakkonsistenan tinggi input ditemukan &amp; diperbaiki**: input di halaman Login
     punya padding vertikal 11px, sementara SEMUA form lain di aplikasi 10px — beda 1px yang
     bikin field password terlihat sedikit lebih tinggi dari field lain kalau dibandingkan
     berdampingan. Disamakan ke 10px.
   - Kelas baru `.grid-cell-title`/`.grid-cell-note` dipakai di grid Jadwal (admin) &amp;
     Kalender (guru), sekaligus membetulkan beda tipis 12px vs 12.5px di antara keduanya.
   - `.pagination`/`.pagination-links` — dipakai Audit Log, siap dipakai lagi kalau tabel lain
     ditambah pagination nanti.
   - Diverifikasi juga: seluruh tabel lebar (grid jadwal, rekap jurnal dengan banyak kolom)
     sudah benar-benar di dalam `.table-wrap` (scroll ke samping terkendali, TIDAK bikin
     seluruh halaman overflow ke samping di HP) — tidak ada tabel "telanjang" yang lolos.
   - Diverifikasi: setiap modal punya `.modal-box` yang benar, tidak ada struktur modal yang
     rusak/tidak lengkap di seluruh aplikasi.
   - File yang TERNYATA sudah cukup rapi sejak awal (tidak perlu diubah): Siswa, Kelas, Mata
     Pelajaran, Jam Pelajaran, Hari Libur, Jadwal (tampilan daftar) — inline style yang tersisa
     di situ semuanya wajar (warna ikon putih di tombol, lebar kolom "Aksi", dsb), bukan
     inkonsistensi.

2. **[UI/UX] Fondasi desain diperkuat + Dashboard dirapikan.** Audit UI/UX menyeluruh
   sedang berjalan bertahap (lihat catatan di bawah soal cakupannya). Yang sudah selesai:
   - `app.css` dapat kelas reusable baru: `.stat-card`/`.stat-value`/`.stat-label` (statistik),
     `.section`/`.section-title` (judul antar-bagian), `.progress-track`/`.progress-fill`
     (bar persentase), `.nav-badge` (badge notifikasi), `.skeleton`/`.spinner` (loading state).
   - Aksesibilitas: outline fokus-keyboard yang jelas di semua tombol/link/menu (sebelumnya
     cuma ada di input form), dan `prefers-reduced-motion` dihormati.
   - **Bug diperbaiki**: modal tidak lagi bisa memicu double-scroll — body halaman sekarang
     otomatis terkunci selagi modal terbuka (`app.js`).
   - Scrollbar tabel &amp; sidebar dibuat lebih tipis dan rapi.
   - **Dashboard** (halaman kualitas tertinggi sesuai prioritas Anda) ditulis ulang memakai
     kelas-kelas baru di atas — semua card statistik sekarang seragam ukuran, padding, dan
     tipografinya, tidak ada lagi gaya inline yang beda-beda antar card.
   - **Badge notifikasi** ditambahkan di sidebar: "Jadwal hari ini" (jumlah sesi yang belum
     selesai/masih perlu tindakan), "Cari guru pengganti" dan "Tukar jadwal" (jumlah pengajuan
     yang menunggu respon Anda) — muncul otomatis, hilang kalau tidak ada yang perlu diperhatikan.
   - **Cakupan yang BELUM selesai** (jujur, bukan disembunyikan): permintaan Anda mencakup
     audit SETIAP halaman (Data Master, Laporan, Presensi/Jurnal, Kalender, dst) — baru
     Dashboard yang benar-benar saya tulis ulang. Perbaikan fondasi CSS di atas (fokus-
     keyboard, reduced-motion, scroll-lock modal, scrollbar) otomatis berlaku ke SEMUA halaman
     karena sifatnya global, tapi penggantian gaya inline di halaman lain jadi kelas baru yang
     konsisten masih perlu dilanjutkan satu-satu. Beri tahu halaman mana yang paling penting
     buat Anda, saya lanjutkan dari situ.
2. **[UI DISEDERHANAKAN] Istilah &amp; tampilan dibedakan dengan jelas** antara dua fitur yang
   sebelumnya bisa membingungkan namanya mirip:
   - **"Cari Guru Pengganti"** — satu guru minta digantikan untuk SATU sesi, SATU tanggal.
     Tampilannya form sederhana (dropdown jadwal saya + tanggal + dropdown guru pengganti +
     alasan). **Tidak pakai grid** — cukup dropdown untuk memilih semuanya.
   - **"Tukar Jadwal"** — menukar SELURUH slot (hari &amp; jam) dengan guru lain untuk rentang
     tanggal, dengan syarat JP kedua slot sama. Juga form sederhana, tidak pernah pakai grid.
   - Kedua halaman saling mengarahkan lewat catatan singkat di bagian atas ("butuh yang
     satunya? klik di sini"), supaya tidak salah pilih menu. Menu sidebar, judul halaman,
     laporan, dan label di Dashboard/Kalender semuanya konsisten pakai istilah ini.
2. **[FITUR BESAR] Tukar Jadwal** (pertukaran slot penuh, bukan guru pengganti) — tabel baru
   `jadwal_swap` (lihat `database/tambahan_jadwal_swap.sql` untuk database yang sudah ada
   isinya). Jadwal master **tidak pernah diubah**; semua dihitung lewat `ScheduleResolverService`
   yang baru (`app/Libraries/`), satu-satunya sumber kebenaran untuk "jadwal efektif".
   - Alur persetujuan 2 tahap: guru yang dituju meng-ACC dulu (menu Guru → Tukar Jadwal), baru
     Admin/Waka Kurikulum memberi persetujuan akhir (menu Data Master → Tukar Jadwal).
   - Sistem otomatis menolak pengajuan kalau jumlah JP kedua slot tidak sama.
   - **Terintegrasi penuh** ke Kalender Jadwal, Dashboard ("jadwal hari ini" guru & "Monitoring
     hari ini" Kepala Sekolah), dan Laporan (kolom Hari selalu dihitung dari tanggal sungguhan,
     baris yang kena pertukaran ditandai badge "Tukar Jadwal" beserta hari aslinya).
   - Presensi &amp; Jurnal ternyata sudah otomatis kompatibel tanpa perlu diubah — keduanya
     tidak memvalidasi ulang hari, cuma ikut link yang disodorkan Dashboard.
3. **[BUG DIPERBAIKI, KRITIS] Error "Undefined array key jam_ke_mulai" di Kalender Jadwal.**
   Query yang mengambil data sesi pengganti tidak menyertakan kolom `jam_ke_mulai`/
   `jam_ke_selesai`, padahal kode Kalender butuh keduanya untuk menentukan baris grid. Sudah
   diperbaiki dan diuji ulang dengan query yang sama persis.
4. **[BUG DIPERBAIKI] Dropdown template Excel Jadwal (grid maupun daftar biasa) yang tidak
   bisa dipakai.** Akar masalahnya: formula dropdown Excel punya batas 255 karakter — kalau
   daftar nama guru/mapel panjang, dropdown-nya otomatis hilang sendiri saat file dibuka
   (bukan cuma di aplikasi Anda, ini keterbatasan Excel). Sudah diperbaiki dengan menaruh
   daftar guru/mapel/kelas di sheet referensi terpisah lalu dropdown menunjuk ke RANGE sel
   itu — jadi tidak terbatas jumlah guru/mapel lagi, seberapa pun banyaknya.
5. **Riwayat Mengajar sekarang punya Detail &amp; Revisi.** Klik "Detail" pada satu sesi untuk
   lihat presensi per siswa dan seluruh isi jurnal. Ada tombol "Revisi presensi" dan "Revisi
   jurnal" untuk membetulkan kalau ada yang salah input — beda dari alur presensi/jurnal hari
   ini yang otomatis terkunci, jalur revisi ini SENGAJA tetap bisa dipakai untuk sesi lama,
   tapi tetap dibatasi hanya untuk guru pemiliknya (atau guru pengganti yang disetujui), dan
   setiap revisi tercatat di audit log.
6. **Laporan Rekap Jurnal sekarang lengkap.** Sebelumnya cuma tampil Materi &amp; Kendala —
   sekarang semua field (Tujuan Pembelajaran, Metode, Media, Kegiatan Pembelajaran, Tindak
   Lanjut, Catatan) ikut tampil di layar, di Excel, dan di PDF (PDF-nya saya rombak jadi
   bentuk kartu per entri supaya tetap enak dibaca saat dicetak, bukan tabel sempit).
7. **Import &amp; tampilan grid untuk Jadwal Mengajar** (menu Jadwal Mengajar → Tampilan grid
   — beda dari fitur Cari Guru Pengganti/Tukar Jadwal di atas, grid ini untuk ADMIN mengelola
   jadwal induk). Klik sel kosong untuk menambah, klik sel terisi untuk mengubah/menghapus.
   Ada pilihan "Sampai jam ke-" saat menambah/mengubah, jadi sesi 2-3 jam berurutan tersimpan
   sebagai SATU jadwal, bukan beberapa sesi terpisah. Template &amp; import Excel bentuk grid
   juga tersedia, dengan penggabungan otomatis jam berurutan yang guru+mapelnya sama.
8. **Kalender Jadwal sekarang per-minggu.** Ada navigasi "Minggu sebelumnya / Minggu ini /
   Minggu berikutnya" dengan tanggal sungguhan di tiap kolom hari, dan untuk minggu yang
   sedang dilihat: sesi yang sudah punya pengganti disetujui tampil pudar dengan keterangan
   "Digantikan [nama]", sedang sesi yang Anda gantikan untuk guru lain ikut muncul dengan
   keterangan "Menggantikan [nama]" — begitu pindah ke minggu lain, otomatis kembali normal.

Berikut catatan dari fase-fase sebelumnya:

Ada 2 perbaikan penting di paket ini yang **wajib** disalin ulang meski Anda sudah pernah copas fase 1-6:

1. **`app/Controllers/BaseController.php`** — file ini SEKARANG BARU BENAR-BENAR DISERTAKAN.
   Sebelumnya cuma disebut lewat instruksi teks ("tambahkan 'auth' ke $helpers"), makanya
   gampang kelewat. Ini penyebab error `Call to undefined function current_user()`.
2. **`app/Config/Filters.php`** — filter `csrf` sekarang diaktifkan secara global. Sebelumnya
   setiap form sudah mengirim token CSRF (`csrf_field()`) tapi token itu tidak pernah benar-benar
   diperiksa karena filternya belum dinyalakan.
3. **Jam Pelajaran** sekarang jadi Master Data tersendiri yang bisa di-CRUD (menu Data Master →
   Jam Pelajaran), bukan cuma data bawaan di `schema.sql`. Alasannya: tiap sekolah punya jam
   masuk & durasi periode yang berbeda-beda.
4. **Audit Log** sekarang punya halaman untuk melihatnya (menu Sistem → Audit Log), lengkap
   dengan filter tanggal/pengguna/jenis aktivitas/kata kunci dan pagination.
5. **Tampilan dirombak total**: tema deep blue ocean, font Roboto, ikon di seluruh menu &amp;
   tombol, dan mobile-first responsive (sidebar jadi menu geser di layar kecil, bukan cuma
   menyempit). Karena perubahan ini menyentuh hampir semua file, cara paling aman adalah
   **timpa seluruh folder `app/` dan `public/` Anda dengan isi zip ini**, bukan pilih-pilih file.
6. **Guru & akun login digabung jadi satu form.** Menu Guru sekarang langsung punya field
   username/password/email/role — tidak perlu lagi bolak-balik ke menu Pengguna untuk
   menautkan akun guru baru. Menu Pengguna & Role tetap ada, tapi sekarang khusus untuk akun
   non-guru (administrator/operator/kepala sekolah) dan pengaturan role secara umum.
7. **Import Excel Guru ikut disesuaikan** — template sekarang punya kolom tambahan opsional:
   Username, Password, Email, Role Tambahan (pisah koma, contoh: `wali_kelas` atau
   `wali_kelas,operator`). Kosongkan Username kalau baris itu belum perlu akun login; kalau
   diisi, akun &amp; role langsung dibuat sekaligus persis seperti form tambah satuan.
8. **Penunjukan wali kelas + halaman kerjanya kini ada.** Menu Kelas sekarang punya field
   "Wali kelas" (hanya menampilkan guru yang sudah punya role Wali Kelas). Guru yang ditunjuk
   otomatis punya menu baru "Rekap & data kelas" — data siswa, grafik kehadiran, dan rekap
   per-siswa (Hadir/Sakit/Izin/Alpha/Terlambat) untuk kelas yang jadi tanggung jawabnya.
9. **Semua modul yang tadinya "menyusul" sekarang lengkap** — tidak ada lagi menu nonaktif
   di sidebar:
   - **Dashboard Administrator/Operator**: statistik total guru/siswa/kelas/jadwal, jumlah
     presensi & jurnal hari ini, dan grafik kehadiran hari ini se-sekolah.
   - **Dashboard Kepala Sekolah**: monitoring real time — jadwal hari ini, berapa guru sedang
     mengajar/sudah selesai/belum presensi/belum jurnal, dan persentase kehadiran siswa.
   - **Hari Libur**: CRUD kalender akademik (menu Data Master), dipakai sebagai referensi
     tanggal libur sekolah.
   - **Riwayat Mengajar**: guru bisa melihat semua sesi presensi &amp; jurnal yang pernah
     diisi (bukan cuma hari ini), lengkap rekap kehadiran per sesi dan materi yang diajarkan.
10. **Dropdown di template Excel.** Kolom yang datanya mengacu ke Data Master (Jenis Kelamin,
    Kelas, Role Tambahan) sekarang berupa dropdown asli Excel — klik sel, pilih dari daftar,
    tidak perlu mengetik manual. Jenis Kelamin &amp; Kelas dipaksa (tidak bisa isi nilai lain);
    Role Tambahan cuma saran karena kolom itu boleh diisi lebih dari satu nilai dipisah koma.
11. **Import Excel untuk Jadwal Mengajar** (menu Jadwal → Import Excel). Kolom Guru, Mata
    Pelajaran, Kelas, Hari, dan Jam Ke semuanya dropdown. Setiap baris tetap divalidasi anti-
    bentrok guru &amp; kelas — persis seperti tambah manual — termasuk bentrok ANTAR baris di
    file yang sama, bukan cuma terhadap jadwal yang sudah ada.
12. **Kalender jadwal semester untuk guru** (menu Guru → Kalender jadwal). Karena jadwal
    berbasis hari dan berulang tiap minggu, satu grid Hari × Jam Ke ini sudah mewakili seluruh
    pola mengajar guru selama semester berjalan — termasuk menangani kelas yang jamnya
    membentang lebih dari satu periode sekaligus (rowspan otomatis).
13. **Fitur Tukar Jadwal** (menu Guru → Tukar Jadwal). Guru bisa mengajukan guru pengganti
    untuk SATU sesi tertentu (jadwal + tanggal spesifik) ke guru lain manapun, tunggu
    disetujui. Tabel `jadwal` (template mingguan) TIDAK PERNAH diubah — begitu tanggal yang
    diajukan lewat, minggu berikutnya otomatis kembali ke guru asli tanpa tindakan apa pun,
    karena pengajuan ini memang hanya berlaku untuk tanggal itu saja. Guru pengganti yang
    disetujui otomatis bisa mengisi presensi &amp; jurnal sesi itu di hari-H, dan sesi itu
    muncul di dashboard kedua belah pihak (guru asal: "Digantikan oleh...", pengganti:
    "Menggantikan..."). Tercatat lengkap di audit log, dan ada laporannya sendiri (menu
    Laporan → Tukar Jadwal) untuk Administrator, Operator, dan Kepala Sekolah, plus ringkasan
    jumlah pengajuan di dashboard masing-masing.
14. **Input &amp; import jadwal bentuk grid**, meniru jadwal dinding sekolah (hari/jam di
    baris, kelas di kolom):
    - **Tampilan grid** (menu Jadwal → Tampilan grid): klik sel kosong untuk menambah, klik
      sel terisi untuk mengubah/menghapus — pakai validasi anti-bentrok yang sama seperti form
      biasa, cuma tampilannya jadi tabel yang bisa langsung diklik. Ada pilihan "Sampai jam
      ke-" saat menambah/mengubah, jadi sesi 2-3 jam berurutan (mis. Matematika jam ke-1 s/d
      ke-3, guru sama) bisa langsung disimpan sebagai SATU jadwal lewat satu klik+isi form —
      bukan mengklik 3 sel terpisah — supaya gurunya cukup isi 1 presensi &amp; 1 jurnal untuk
      sesi itu, bukan 3 kali.
    - **Template &amp; import Excel bentuk grid**: tiap kelas dapat 2 kolom (Guru + Mapel,
      masing-masing dropdown terpisah). Jam yang berurutan dengan guru &amp; mapel sama
      otomatis digabung jadi satu sesi mengajar (bukan dianggap sesi terpisah), supaya di
      dashboard guru tetap muncul sebagai satu kartu "Mulai Mengajar", bukan berkali-kali
      untuk mapel yang sama.
    - **Ketersediaan guru di form Tukar Jadwal**: begitu memilih jadwal yang mau ditukar,
      otomatis tampil daftar guru yang SIBUK di jam yang sama — guru yang tidak ada di
      daftar itu kemungkinan besar kosong dan jadi kandidat pengganti yang lebih aman.

Kalau ragu, cara paling aman: **timpa seluruh folder `app/` dan `public/` Anda dengan isi zip ini.**

---

## 1. Import database

1. Buat database baru di phpMyAdmin (contoh nama: `db_presensi`)
2. Tab **Import** → pilih `database/schema.sql` → **Go**
3. Login default setelah import — **username:** `admin` **password:** `admin123`
   (wajib diganti setelah login pertama, lewat menu Pengguna & Role)

## 2. Salin semua file ke project CI4 Anda

Salin folder `app/`, `public/`, dan `database/` di zip ini ke root project CI4 Anda (hasil
composer install), **timpa file yang namanya sama**. Berikut daftar lengkap 55 file yang ada:

```
app/Config/Filters.php
app/Config/Routes.php
app/Controllers/Auth.php
app/Controllers/BaseController.php        <- WAJIB, lihat catatan di atas
app/Controllers/Dashboard.php
app/Controllers/Laporan.php
app/Controllers/Mengajar.php
app/Controllers/TukarJadwal.php
app/Controllers/JadwalSwap.php
app/Controllers/WaliKelas.php
app/Controllers/Master/AuditLog.php
app/Controllers/Master/PertukaranJadwal.php
app/Controllers/Master/Sampah.php
app/Controllers/Master/Guru.php
app/Controllers/Master/HariLibur.php
app/Controllers/Master/Jadwal.php
app/Controllers/Master/JamPelajaran.php
app/Controllers/Master/Kelas.php
app/Controllers/Master/MataPelajaran.php
app/Controllers/Master/Pengguna.php
app/Controllers/Master/Siswa.php
app/Controllers/Master/TahunAjaran.php
app/Filters/AuthFilter.php
app/Filters/RoleFilter.php
app/Helpers/auth_helper.php
app/Libraries/AuditLogger.php
app/Libraries/ScheduleResolverService.php
app/Models/AuditLogModel.php
app/Models/GuruModel.php
app/Models/HariLiburModel.php
app/Models/JadwalModel.php
app/Models/JamPelajaranModel.php
app/Models/JurnalMengajarModel.php
app/Models/KelasModel.php
app/Models/MataPelajaranModel.php
app/Models/PresensiDetailModel.php
app/Models/PresensiModel.php
app/Models/RoleModel.php
app/Models/SemesterModel.php
app/Models/SiswaModel.php
app/Models/TahunAjaranModel.php
app/Models/UserModel.php
app/Models/UserRoleModel.php
app/Models/TukarJadwalModel.php
app/Models/JadwalSwapModel.php
app/Models/WaliKelasModel.php
app/Views/auth/login.php
app/Views/dashboard/_content.php
app/Views/dashboard/jadwal_hari_ini.php
app/Views/dashboard/_detail_aktivitas.php
app/Controllers/KalenderAkademik.php
app/Models/AgendaAkademikModel.php
app/Views/kalender_akademik/index.php
app/Views/kalender_akademik/_modal_event.php
app/Views/kalender_akademik/agenda.php
app/Views/kalender_akademik/minggu.php
app/Models/PenilaianHarianModel.php
app/Views/mengajar/_penilaian_harian.php
app/Models/GuruPengampuModel.php
app/Models/TujuanPembelajaranModel.php
app/Controllers/Master/GuruPengampu.php
app/Views/master/guru_pengampu/index.php
app/Controllers/TujuanPembelajaran.php
app/Views/tujuan_pembelajaran/index.php
app/Controllers/Master/TujuanPembelajaran.php
app/Views/master/tujuan_pembelajaran/index.php
app/Views/laporan/jurnal.php
app/Views/laporan/jurnal_pdf.php
app/Views/laporan/presensi.php
app/Views/laporan/presensi_pdf.php
app/Views/laporan/tukar_jadwal.php
app/Views/layouts/_icons.php
app/Views/layouts/_sidebar.php
app/Views/layouts/_topbar.php
app/Views/layouts/main.php
app/Views/master/audit_log/index.php
app/Views/master/guru/index.php
app/Views/master/hari_libur/index.php
app/Views/master/jadwal/index.php
app/Views/master/jadwal/grid.php
app/Views/master/jadwal/kosong.php
app/Views/master/jam_pelajaran/index.php
app/Views/master/kelas/index.php
app/Views/master/mata_pelajaran/index.php
app/Views/master/pengguna/index.php
app/Views/master/siswa/index.php
app/Views/master/tahun_ajaran/index.php
app/Views/mengajar/jurnal.php
app/Views/mengajar/kalender.php
app/Views/mengajar/kalender_kosong.php
app/Views/mengajar/presensi.php
app/Views/mengajar/riwayat.php
app/Views/mengajar/riwayat_detail.php
app/Views/mengajar/revisi_presensi.php
app/Views/mengajar/revisi_jurnal.php
app/Views/mengajar/isi_jurnal_terlewat.php
app/Views/mengajar/hari_terlewat.php
app/Views/wali_kelas/index.php
app/Views/wali_kelas/kosong.php
app/Views/tukar_jadwal/index.php
app/Views/jadwal_swap/index.php
app/Views/master/pertukaran_jadwal/index.php
app/Views/master/sampah/index.php
database/schema.sql
database/tambahan_tukar_jadwal.sql
database/tambahan_jadwal_swap.sql
database/tambahan_soft_delete.sql
database/tambahan_tanggal_semester.sql
database/tambahan_index_performa.sql
database/tambahan_jam_per_hari.sql
database/tambahan_kalender_akademik.sql
database/tambahan_penilaian_harian.sql
database/tambahan_guru_pengampu.sql
public/assets/css/app.css
public/assets/js/app.js
public/favicon.svg
```

Kalau Anda punya file `.env` dari permintaan sebelumnya, itu file terpisah (di root project, bukan
di dalam `app/`) — bukan bagian dari daftar di atas. Isinya panduan koneksi database, cek lagi kalau
belum ada.

## 3. Set koneksi database

Di file `.env` (root project, sejajar folder `app/`), isi bagian database sesuai yang Anda buat di
langkah 1:

```
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = db_presensi
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.charset = utf8mb4
database.default.DBCollat = utf8mb4_unicode_ci
```

## 4. Jalankan

```
php spark serve
```

Buka `http://localhost:8080`, login dengan akun admin di atas.

---

## Ringkasan fitur yang sudah jadi

| Area | Isi |
|---|---|
| Fondasi | Login, session, Multi-Role RBAC (`users`–`user_roles`–`roles`), layout clean minimalist, tema deep blue ocean, mobile-first |
| Data Master | Mata Pelajaran, Jam Pelajaran, Hari Libur, Tahun Ajaran & Semester, Kelas (+wali kelas), Guru (+akun+role terintegrasi), Siswa — pola "index saja", import Excel Guru &amp; Siswa |
| Jadwal | CRUD + validasi anti-bentrok (guru & kelas); Import Excel (dropdown Guru/Mapel/Kelas/Hari/Jam); Kalender semester (grid Hari×Jam untuk guru) |
| Alur mengajar | Dashboard guru (jadwal hari ini + status) → Mulai Mengajar → Presensi → Jurnal → terkunci; + Riwayat Mengajar |
| Tukar Jadwal | Guru ajukan pengganti untuk 1 sesi spesifik, perlu persetujuan; jadwal asli tidak berubah, otomatis normal lagi minggu berikutnya; ada laporannya untuk Admin/Kepsek |
| Wali Kelas | Penunjukan lewat menu Kelas; halaman kerja: data siswa, grafik &amp; rekap kehadiran per siswa |
| Dashboard Admin/Operator | Statistik total data master + presensi/jurnal hari ini + grafik kehadiran se-sekolah |
| Dashboard Kepala Sekolah | Monitoring real time: progres jadwal hari ini, guru belum presensi/jurnal, persentase kehadiran |
| Laporan | Filter tanggal/guru/kelas/mapel, export PDF (DomPDF) & Excel (PhpSpreadsheet) untuk Presensi & Jurnal |
| Pengguna | Kelola akun + role (multi-role lewat checkbox), guru+akun jadi satu form |
| Audit Log | Riwayat login/logout & tambah-ubah-hapus data di seluruh modul, bisa difilter dan berhalaman |

## Cara menambah guru baru (sekarang 1 langkah)

Menu **Guru** → Tambah guru → isi data profil, lalu isi juga bagian "Akun login" (username,
password, role tambahan kalau ada) di form yang sama. Profil dan akun langsung tertaut otomatis
— tidak perlu buka menu lain. Kosongkan username kalau guru itu belum perlu akses sistem; bisa
ditambahkan belakangan lewat Edit.

## Catatan jujur soal kelengkapan

Seluruh modul di SRS sudah ada dan berfungsi, termasuk seluruh dashboard per role, dan tidak
ada lagi menu "segera hadir" di sidebar. Dua hal berikut sengaja saya beri tahu apa adanya,
bukan disembunyikan:

- **Penguncian presensi/jurnal** saat ini terjadi begitu jurnal disimpan (sesi resmi "Selesai"),
  bukan berdasarkan jam tertentu di malam hari. Ini karena "batas waktu" di SRS lebih natural
  diartikan sebagai "sesi itu sendiri berakhir" untuk alur guru — implementasi jam cut-off
  tertentu (misal terkunci otomatis jam 23:59) bisa ditambahkan lewat scheduled task kalau
  sekolah Anda butuh itu secara spesifik.
- **Pencarian di Data Master** (Guru, Siswa, dst) bekerja instan di sisi browser dan mencakup
  semua baris yang dimuat — cocok untuk skala sekolah pada umumnya (puluhan-ratusan data).
  Kalau nanti jumlah siswa/guru sampai ribuan, pertimbangkan pagination sisi server; belum
  saya tambahkan sekarang karena akan mengubah cara kerja pencarian instan yang sudah ada.

---

## Troubleshooting

**Tampilan tidak berubah / style baru tidak muncul padahal file sudah ditimpa**
→ Cek `app/Views/layouts/main.php` dan `app/Views/auth/login.php`, pastikan angka `?v=` di
belakang `app.css`/`app.js` SUDAH dinaikkan dibanding sebelumnya. Browser menyimpan cache CSS/JS
berdasarkan URL persis — kalau angkanya sama, browser tidak akan mengambil file yang baru sama
sekali walau isinya sudah berbeda di server. (Ini pernah terjadi persis di paket sebelumnya —
sudah diperbaiki ke `?v=5`, tapi kalau ke depannya Anda sendiri mengedit `app.css`, ingat naikkan
angkanya juga.)

**`Call to undefined function App\Controllers\current_user()`**
→ Pastikan `app/Controllers/BaseController.php` dari paket ini sudah tersalin, dan isinya
punya baris `$this->helpers = ['auth'];` sebelum `parent::initController(...)`.

**Import Excel gagal / halaman Laporan PDF-Excel error**
→ Pastikan `composer require phpoffice/phpspreadsheet` dan `composer require dompdf/dompdf`
sudah dijalankan di project Anda (folder `vendor/phpoffice` dan `vendor/dompdf` harus ada).

**Field jadwal/kelas kosong padahal sudah diisi datanya**
→ Pastikan ada 1 Tahun Ajaran dan 1 Semester yang statusnya **Aktif** (menu Tahun Ajaran & Semester)
sebelum membuat Kelas atau Jadwal.

**Dashboard guru tidak menampilkan jadwal**
→ Cek 3 hal: (1) semester aktif sudah ada, (2) akun guru sudah ditautkan ke profil guru di menu
Pengguna & Role, (3) ada jadwal untuk **hari ini** (sesuai hari berjalan) di menu Jadwal Mengajar.
