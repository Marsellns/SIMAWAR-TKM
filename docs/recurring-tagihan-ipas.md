# Tagihan IPAS per Site ID

Migrasi `2026_10_17_010000_normalize_recurring_tagihan_ipas_sites` mengubah penyimpanan Tagihan IPAS:

- `recurring_tagihan_ipas`: satu baris per Site ID dengan unique index `site_code`, daftar termin/periode yang tersedia, tanggal paling awal–akhir, jumlah rincian, dan total nominal semua rincian.
- `recurring_tagihan_ipas_terms`: semua baris asli, ID, timestamp, Site ID sumber dan JSON `source_details` dipertahankan. Kolom `site_id` menghubungkan setiap rincian ke baris utama dengan foreign key yang menolak penghapusan master yang masih memiliki rincian.

Nomor termin dikompresi hanya jika berurutan: `1, 2, 3, 4, 5` menjadi `1–5`, sedangkan `1, 3, 5` tetap `1, 3, 5`. Beberapa siklus kontrak dapat memakai nomor termin yang sama. `term_count` menghitung baris rincian, bukan jumlah nomor termin unik. Nominal master merupakan penjumlahan SQL DECIMAL, bukan nilai satu termin.

Tabel aplikasi membaca master, dan rincian sumber dimuat ketika site dibuka. Klik kartu atau batang diagram memfilter tabel site yang sama berdasarkan rincian yang cocok; tidak ada tabel kedua. Filter TP juga memilih site yang mempunyai rincian dari TP tersebut. Angka pada kartu/diagram dihitung dari rincian yang dipilih, sedangkan setiap baris tabel site tetap merangkum seluruh kontrak site tersebut. Download Excel menghasilkan ringkasan satu baris per site dan satu kolom Rentang Termin. Download Rincian CSV berisi seluruh rincian beserta JSON sumber dan dapat diunggah ulang. Ringkasan Excel ditolak oleh importer agar tidak berubah menjadi termin/tagihan baru.

Upload memakai Recurring ID sumber bila tersedia; kolom `Recurring` pada template boleh diisi untuk membedakan kontrak paralel. Tanpa Recurring ID, upload hanya memperbarui satu rincian dengan identitas termin/periode/tanggal/TP/jenis kontrak yang cocok. Jika beberapa kontrak cocok, upload ditolak dan pengguna diminta memakai CSV rincian. Metadata PO/invoice lama dipertahankan saat tidak disertakan dalam pembaruan. Input manual menambah rincian ke site yang sudah ada dan menolak rincian yang sama.

## Penerapan lokal dan beta cPanel

DBeaver membaca server MySQL yang sama dengan `.env` aplikasi. Setelah migrasi, refresh daftar tabel dan baca `recurring_tagihan_ipas` untuk ringkasan; gunakan tabel `recurring_tagihan_ipas_terms` untuk audit rincian.

Target beta yang dikonfirmasi adalah `beta.simawar.web.id`, dengan checkout `/home3/simawarw/beta.simawar.web.id`. Jangan mengganti database beta dengan snapshot lokal karena data server bisa berbeda. Migrasi mengolah data yang sudah berada di server.

Selesaikan build GitHub Actions untuk commit yang dipilih. Jalankan perintah terminal berikut dengan checkout server yang bersih dan vendor yang sudah tersedia. Backup dibuat sebelum source diperbarui. `--prepare-only` memverifikasi dan menyiapkan paket rilis tanpa mengaktifkannya; jalankan migrasi dengan `artisan` dari paket tersebut agar kode dan autoload cocok. Pada rilis yang juga memuat migrasi view Electricity, jalankan kedua migrasi sebelum preflight deployment:

```bash
set -e
cd /home3/simawarw/beta.simawar.web.id
PHP_BIN=/opt/alt/php83/usr/bin/php
"$PHP_BIN" artisan down
"$PHP_BIN" artisan simaster:backup
git pull --ff-only origin main
/bin/bash -euo pipefail -- scripts/cpanel-deploy-beta.sh --prepare-only
RELEASE="$HOME/simawar-beta-deploy/releases/$(git rev-parse HEAD)"
"$PHP_BIN" "$RELEASE/artisan" migrate:status
"$PHP_BIN" "$RELEASE/artisan" migrate --path=database/migrations/2026_10_17_010000_normalize_recurring_tagihan_ipas_sites.php --force
"$PHP_BIN" "$RELEASE/artisan" migrate --path=database/migrations/2026_10_17_020000_add_live_electricity_projections.php --force
"$PHP_BIN" "$RELEASE/artisan" simaster:refresh-reports
/bin/bash -euo pipefail -- scripts/cpanel-deploy-beta.sh
"$PHP_BIN" "$RELEASE/artisan" up
```

Jalankan tiap baris berurutan. `set -e` menghentikan langkah berikutnya jika backup, pull, migrasi, atau deploy gagal. Periksa status dan rekonsiliasi SQL di bawah setelah migrasi IPAS, sebelum menjalankan migrasi berikutnya. Jika berhenti setelah maintenance aktif, perbaiki kegagalan sebelum membuka aplikasi kembali. Jangan menjalankan impor snapshot hanya untuk menerapkan migrasi ini.

## Rekonsiliasi

Sebelum migrasi catat `COUNT(*)`, `COUNT(DISTINCT UPPER(TRIM(site_code)))`, dan `SUM(amount)` dari tabel lama. Setelah migrasi:

```sql
SELECT COUNT(*) AS site_count, COUNT(DISTINCT site_code) AS unique_sites,
       SUM(term_count) AS detail_count, SUM(amount) AS amount_total
FROM recurring_tagihan_ipas;

SELECT COUNT(*) AS detail_count, SUM(amount) AS amount_total,
       SUM(site_id IS NULL) AS unlinked_rows
FROM recurring_tagihan_ipas_terms;

SELECT site_code, termin, periode_ke, termin_start, termin_end, term_count, amount
FROM recurring_tagihan_ipas WHERE site_code = 'BKS648';

SELECT COUNT(*) AS mismatched_sites
FROM recurring_tagihan_ipas s
JOIN (SELECT site_id, COUNT(*) AS n, SUM(amount) AS total
      FROM recurring_tagihan_ipas_terms GROUP BY site_id) t ON t.site_id = s.id
WHERE s.term_count <> t.n OR NOT (s.amount <=> t.total);
```

Jumlah site harus sama dengan Site ID unik sebelum migrasi, jumlah rincian dan total nominal harus sama dengan sumber, unlinked/mismatched harus nol. Snapshot lokal sebelum migrasi: 149.286 rincian, 10.124 Site ID unik, total Rp21.510.166.453.717,53. BKS648 memiliki beberapa siklus kontrak sehingga rincian sebenarnya lebih banyak dari lima baris contoh pada tangkapan layar.

## Pemulihan

Migrasi `down()` mengembalikan tabel rincian sebagai `recurring_tagihan_ipas` beserta ID dan metadata asli, lalu menghapus tabel ringkasan hasil migrasi. Jalankan rollback khusus migrasi ini saat maintenance, bersama pemulihan versi kode sebelumnya. Perubahan data setelah migrasi tetap berada pada rincian. Untuk pemulihan snapshot tepat sebelum perubahan, gunakan backup database privat yang dibuat sebelum migrasi, bukan rollback seluruh batch migrasi yang tidak terkait.
