# Data operasional dan pembaruan laporan

Perubahan ini mempertahankan Laravel, controller, tabel sumber, hak akses admin/viewer, rumus P&L, dan alur modul yang sudah ada. Diagram membaca data tersimpan. Penambahan data melalui website mengubah versi laporan sehingga cache lama langsung ditinggalkan.

## Input dan Excel

- Profit & Loss: tersedia formulir manual dan template satu baris per site/bulan/tahun. Upload Excel lama dengan kolom Rev/Cost per bulan tetap tersedia. Profit/Loss dihitung dari Revenue − Cost. Nol yang diisi pada Excel mengoreksi angka lama; bulan kosong mempertahankannya. Penanda anomali INT32 max tetap berlaku.
- Feasibility: formulir dan template Site Owner serta Asset Tower tersedia di modulnya. Site Owner baru juga dibuat pada master `sites`; site baru dari P&L mendapat master Site Owner agar dapat dicari. Metadata NOP/owner perlu diisi sesuai sumber; tidak ditebak dari nama site.
- PO HQ dan PO Varcost: formulir lama dipertahankan, Excel dapat menambah atau memperbarui berdasarkan nomor PO.
- Equipment: inventaris dapat ditambah manual/Excel berdasarkan kode unik equipment; alur monitoring/relokasi yang ada tetap dipakai.
- Electricity: master PLN dan tagihan IBC mendapat formulir/template tambahan. Input dan edit Status Pembayaran PLN masuk ke laporan global. Beberapa invoice IBC dalam satu bulan dijumlahkan pada diagram site dan matriks tahunan.
- Infrastruktur: input manual/Excel tetap memakai alur modul yang sama. Upload BAPSS dan workbook Rebuild menggabungkan data, mempertahankan baris/kontrak di luar unggahan. Perintah CLI Rebuild tetap merupakan penggantian snapshot secara eksplisit dengan backup.
- Bongkar Rampung: unggah ulang memperbarui rekening/site yang sama dan mempertahankan rekening/site lain. File kosong ditolak.
- Presales dan PDF: tetap mengikuti formulir, persetujuan, dan unggah dokumen yang ada. PDF bukan sumber angka P&L atau tagihan listrik.

Template tambahan memiliki header pada baris pertama. Kolom identitas berformat teks agar nol di depan tidak hilang saat diketik di Excel. Nilai nominal harus berupa angka Excel. Isi tanggal sebagai `YYYY-MM-DD`, periode IBC sebagai `YYYY-MM`. Sel opsional yang kosong pada upload tambahan tidak menghapus metadata lama. Baris dengan identitas berulang dalam satu file ditolak. File tambahan yang mempunyai baris tidak valid dibatalkan seluruhnya.

Identitas Asset Tower adalah gabungan Site ID, Site Company, Brand, Part Name, dan Owner. Perubahan identitas berarti aset berbeda; unggah metadata dengan identitas yang sama untuk mengoreksinya. Identitas inventaris adalah `uniq_key`; identitas P&L adalah site/bulan/tahun.

## Aturan Electricity

Tabel impor tetap disimpan. Enam SQL view menyatukan pembacaan operasional tanpa menggandakan transaksi ke tabel impor:

- `payment_pln_effective`: koreksi manual Status Pembayaran menggantikan tagihan impor pada rekening/site/bulan/tahun yang sama. Riwayat Status Pembayaran hasil impor tetap independen sampai diedit melalui formulir operasional. Kolom baru `status_pembayaran.is_operational` membedakannya.
- `payment_pln_master_monthly_effective`: koreksi yang sama juga berlaku pada snapshot Flagging. Remark mengandung `pending`, `belum`, atau `unpaid` berarti Pending; selain itu Done, mengikuti formulir Status Pembayaran yang sudah ada. Untuk pembayaran PLN baru, isi remark dengan jelas.
- `payment_ibc_effective`: invoice BM manual yang cocok menggantikan invoice impor pada site/periode yang sama; nomor invoice berbeda menambah tagihan. Tanpa invoice BM, input menjadi total periode dan mengesampingkan rincian invoice untuk periode itu. Beberapa baris total tanpa invoice dijumlahkan; gunakan satu total periode atau rincian invoice secara konsisten. Tanggal bayar kosong berarti Pending, terisi berarti Done. Nominal tagihan menggunakan Total Tagihan (F); Total Payment (K) tetap tersedia pada input untuk pajak/potongan.
- `inbuilding_all_effective`: matriks menggunakan jumlah tagihan transaksi efektif pada bulan yang tersedia dan angka matriks lama pada bulan tanpa transaksi.
- `anomali_tagihan_pln_effective` dan `anomali_tagihan_inbuilding_effective`: kenaikan dihitung terhadap bulan kalender tepat sebelumnya, termasuk Desember–Januari. Kenaikan dicatat bila nilai sebelumnya positif. Tabel/ekspor peringatan tetap memakai ambang **lebih dari 50%**. Rekaman historis hanya menjadi fallback ketika pasangan bulan belum tersedia; koreksi angka dapat menghilangkan peringatan lama.

Upload Payment PLN memperbarui status tagihan pada rekening/site/bulan/tahun yang sama. Upload Listrik All memperbarui snapshot hanya untuk kolom **Flagging** yang diunggah; Inquiry tidak dihitung sebagai pembayaran. Koreksi manual tetap mendapat prioritas sampai koreksi itu dihapus. Menghapus koreksi mengembalikan pembacaan data impor yang masih tersimpan.

Billing, pembayaran, dan populasi site merupakan metrik berbeda. Billing menggunakan seluruh status tagihan; diagram pembayaran hanya menghitung status dibayar sesuai sumbernya. Scope Infrastruktur tetap Sewa Lahan/Combat. Filter tahun/NOP/modul tetap menentukan data yang ditampilkan; penambahan di luar filter tidak seharusnya masuk ke angka filter tersebut.

Grafik rekening PLN membaca tagihan efektif agar upload Payment juga memperbarui grafik site. Riwayat Status Pembayaran lama dipakai pada bulan yang belum mempunyai transaksi Payment.

Jumlah kenaikan pada ringkasan dapat berubah karena sekarang dihitung dari transaksi terkini, termasuk periode yang belum ada pada file anomali lama. Baris sumber historis tetap tersedia; daftar peringatan lebih dari 50% tetap memakai ambang yang sama.

## Halaman yang sedang terbuka

Website memeriksa versi data setiap 30 detik dan saat tab kembali aktif. Dashboard utama/Electricity memperbarui angka memakai filter aktif; tabel AJAX memuat ulang tanpa kembali ke halaman pertama. Laporan lain memuat ulang secara aman. Input yang belum disimpan atau dialog yang masih terbuka menunda pemuatan ulang dan menampilkan tombol pemberitahuan.

Impor melalui perintah aplikasi juga membatalkan cache. Bila data diubah langsung melalui DBeaver, SQL terminal, atau phpMyAdmin, jalankan:

```bash
php artisan simaster:refresh-reports
```

Perintah ini menyegarkan definisi view dan versi cache, tanpa mengubah data sumber. Server cPanel harus memakai PHP CLI yang sesuai, sebagaimana contoh berikut.

## Terapkan pada beta melalui terminal cPanel

Target: `beta.simawar.web.id`, checkout `/home3/simawarw/beta.simawar.web.id`. Tunggu workflow **Build SIMAWAR beta for cPanel Git** berhasil untuk commit yang akan ditarik. Langkah ini tidak memerlukan impor ulang database lokal atau workbook.

```bash
cd /home3/simawarw/beta.simawar.web.id
PHP_BIN=/opt/alt/php83/usr/bin/php
"$PHP_BIN" artisan down
"$PHP_BIN" artisan simaster:backup
git pull --ff-only origin main
/bin/bash -euo pipefail -- scripts/cpanel-deploy-beta.sh --prepare-only
RELEASE="$HOME/simawar-beta-deploy/releases/$(git rev-parse HEAD)"
"$PHP_BIN" "$RELEASE/artisan" migrate:status
"$PHP_BIN" "$RELEASE/artisan" migrate --force
"$PHP_BIN" "$RELEASE/artisan" simaster:refresh-reports
/bin/bash -euo pipefail -- scripts/cpanel-deploy-beta.sh
"$PHP_BIN" "$RELEASE/artisan" up
```

Jalankan tiap baris berurutan dan hentikan jika ada langkah gagal; aplikasi baru dibuka kembali setelah migrasi/deployment sukses. Periksa dan tinjau daftar migrasi tertunda dari paket rilis sebelum menjalankan `migrate --force`. Backup harus berhasil sebelum migrasi. Migrasi baru `2026_10_17_020000_add_live_electricity_projections` menambahkan satu penanda berindeks dan enam view; tidak menghapus tabel sumber. Migrasi ini berhasil diterapkan pada MariaDB 10.6.28 di beta. Akun database memerlukan izin CREATE VIEW.

Verifikasi login, tambah satu data uji manual, unggah template berisi satu site/periode baru, lalu cocokkan tabel, diagram, filter, dan ekspor. Edit/hapus data uji setelah selesai. Pastikan cron Laravel yang sudah ada tetap aktif untuk notifikasi terjadwal. Deployment kode/database tidak mengirim email pengujian.

Untuk pemulihan, gunakan prosedur rollback kode pada [panduan beta](deploy-beta-cpanel.md). Penambahan view/kolom dapat tetap berada saat kode sebelumnya dipulihkan. Jangan menghapus penanda operasional atau mengembalikan database lama setelah ada transaksi baru tanpa meninjau transaksi tersebut; pemulihan database/dokumen dilakukan dari backup secara terpisah. Perintah `simaster:refresh-reports` aman diulang setelah migrasi.
