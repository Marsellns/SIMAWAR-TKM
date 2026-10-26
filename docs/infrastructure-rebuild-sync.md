# Sinkronisasi Rebuild Sewa Lahan dan Combat

Sumber aktif adalah `DATASET/02 Infrastruktur management/Sewa Lahan & Combat/25 Oct 26_Database IM SIMAWAR.xlsm`.

- Sewa Lahan menggunakan **DATA SEWA LAHAN** saja: 48 transaksi dan 42 Site ID unik pada workbook 25 Oktober.
- Combat menggunakan **DATABASE COMBAT**: 186 catatan dari 76 Site ID unik. Setiap catatan tahun/kontrak, termasuk tahun 2027, disimpan agar histori tidak hilang.
- Sheet `CONFIG COMBAT` dan `CONFIG SEWA LAHAN` tidak ditambahkan sebagai transaksi.
- Semua kolom asli, nomor baris, nama sheet, serta SHA-256 workbook disimpan pada `source_details._source`. Header ganda menggunakan nilai nonkosong pertama.
- Kolom tahun, status, kontrak, tanggal, dan nominal dipetakan ke tabel aplikasi yang sudah ada. Nilai waktu tanpa tanggal seperti `00:00:00` tidak diubah menjadi tanggal hari ini.
- Kartu Total Site menghitung seluruh baris sumber: 48 Sewa Lahan dan 186 Combat, sehingga ringkasan Infrastruktur dan kartu Site Sewa Lahan & Combat pada Dashboard menampilkan 234. Klik kartu membuka seluruh baris dalam cakupannya, termasuk Site ID yang berulang pada periode/kontrak lain. Diagram operasional dan detail diagram tetap memakai pemilihan Site ID kanonis sesuai analisisnya.
- Label pemilik dari workbook tetap menjadi sumber utama; master Site Owner menjadi fallback bila label kosong. Site yang belum dapat dipetakan ditampilkan pada kategori tersendiri, bukan dipaksa menjadi TP/Telkomsel.

## Menjalankan sinkronisasi

```bash
php artisan dataset:import-infrastructure-rebuild --dry-run
php artisan dataset:import-infrastructure-rebuild
php artisan simaster:database-status
```

Untuk memperbarui hanya satu tabel, tambahkan `--only=sewa-lahan` atau `--only=combat`. `--file=...` dapat digunakan untuk workbook pengganti dengan kedua nama sheet yang sama. Perintah `dataset:import-all --only=sewa-lahan` dan `--only=combat` juga memakai importer ini.

Seluruh baris sumber dibaca dan divalidasi sebelum database diubah. Snapshot sebelumnya dicadangkan ke `storage/app/backups/infrastructure-*.json`, lalu penggantian kedua tabel dilakukan dalam satu transaksi MySQL. Kegagalan baca/insert membatalkan perubahan. Sinkronisasi tidak mengirim email atau notifikasi.

Satu catatan Combat (`COC542`, tahun 2025) memiliki Status Dokumen 288 karakter, melebihi kolom MySQL 150 karakter. Kolom pencarian menyimpan baris pertamanya dengan penanda lanjutan; detail Combat dan `source_details` tetap memuat teks lengkap.

## Upload workbook melalui aplikasi

Halaman Upload Excel Sewa Lahan dan Combat menerima workbook Rebuild `.xlsx` atau `.xlsm`. Importer memilih sheet aktif berdasarkan modul yang dibuka, meskipun sheet pertama workbook adalah backup. Macro tidak dijalankan.

Upload biasa dari Sewa Lahan dan Combat **menggabungkan** kontrak berdasarkan identitasnya pada modul masing-masing. Untuk mengganti snapshot kedua tabel agar jumlah dan histori sama persis dengan workbook, gunakan opsi **Ganti snapshot** atau perintah CLI di atas. File template biasa juga memakai pembaruan inkremental.

Admin dapat mencentang **Ganti snapshot Sewa Lahan dan Combat sesuai kedua sheet workbook** pada halaman Upload Excel salah satu modul. Satu unggahan akan membaca kedua sheet, membuat backup privat, lalu mengganti kedua tabel dalam satu transaksi. Pilihan ini membutuhkan workbook lengkap dengan kedua sheet aktif; unggahan biasa tetap menggabungkan satu modul.

Nama file asli, fingerprint, sheet dan baris sumber disimpan pada metadata impor. Diagram membaca hasil sinkronisasi langsung dari database pada pemuatan halaman berikutnya. File kosong atau sheet aktif yang tidak valid membatalkan penggantian data.

## Pemeriksaan workbook yang tersedia

Workbook SHA-256 `8e5b0a97df248809874efa27242c2c81605855ac4c09305558002d35c706cc25` memuat 48 catatan/42 site Sewa Lahan dan 186 catatan/76 site Combat. Gabungannya 112 Site ID unik karena enam site ada pada kedua sumber.

Pembagian portofolio mengikuti label pemilik pada workbook aktif dan fallback master Site Owner yang sudah tersedia.

## Monitoring melalui DBeaver

DBeaver adalah klien untuk server MySQL aplikasi. Pada lingkungan pengembangan yang diperiksa, koneksinya adalah `127.0.0.1:3306`, database `simawar_dashboard`; aplikasi Docker mengakses server yang sama melalui host `mysql`.

Refresh tabel atau jalankan ulang SQL di DBeaver setelah sinkronisasi:

```sql
SELECT COUNT(*) AS transaksi, COUNT(DISTINCT site_code) AS site_unik
FROM sewa_lahan_renewals WHERE deleted_at IS NULL;

SELECT COUNT(*) AS catatan, COUNT(DISTINCT site_code) AS site_unik
FROM combat_sites WHERE deleted_at IS NULL;

SELECT site_code, tahun_justi_dirnet, status_perpanjangan,
       JSON_UNQUOTE(JSON_EXTRACT(source_details, '$._source.sheet')) AS sheet_sumber,
       JSON_EXTRACT(source_details, '$._source.row') AS baris_excel
FROM combat_sites WHERE deleted_at IS NULL
ORDER BY site_code, tahun_justi_dirnet;
```

`simaster:database-status` memeriksa tabel/view seluruh modul: P&L, Infrastruktur, Electricity Centralized/Inbuilding, PO, Equipment, dan Feasibility Site. Tabel alur input seperti `po_varcost`, `equipment_relocations`, `upload_files`, dan `bongkar_rampung_pln` dapat kosong jika belum ada transaksi/input sumber. Status kosong tidak berarti datanya disimpan di browser.

Push GitHub hanya mengirim kode. Workbook, `.env`, dan data MySQL tidak masuk Git. Untuk live, terapkan dahulu kode importer yang menangani Status Dokumen panjang, lalu login admin dan unggah workbook 25 Oktober dengan opsi **Ganti snapshot**. Cocokkan 48/186 baris pada Dashboard atau tabel modul. Sebagai alternatif CLI, salin workbook secara privat ke hosting di luar document root, jalankan `dataset:import-infrastructure-rebuild --file=/path/privat/25\ Oct\ 26_Database\ IM\ SIMAWAR.xlsm --dry-run`, lalu perintah yang sama tanpa `--dry-run` memakai PHP CLI hosting. Jalankan `simaster:database-status` dan cocokkan 48/186 baris serta SHA-256 sumber pada `source_details._source.sha256`. Kedua jalur menyimpan backup dua tabel sebelumnya di storage privat.
