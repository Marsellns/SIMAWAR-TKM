# Deploy SIMASTER Laravel 12 / Filament 4 / PHP 8.2 ke cPanel Jagoan Hosting

Panduan ini untuk hosting dengan SSH/Terminal, cron, Apache/LiteSpeed, dan MySQL. DBeaver adalah aplikasi pengelola MySQL; aplikasi menggunakan koneksi `.env` ke server MySQL yang sama. Data operasional ada dalam database dan berkas dokumen privat.

## Deploy produksi simawars.web.id melalui GitHub Actions

Workflow `.github/workflows/deploy-simawars.yml` menerbitkan paket kode dari commit `main` melalui SSH. Untuk deploy manual, pilih branch `main` pada **Run workflow** dan ketik `simawars.web.id` pada `confirm_target`. Jalur ini tersedia walaupun `SIMAWARS_DEPLOY_ENABLED` belum disetel. Push ke `main` hanya deploy otomatis jika *repository variable* `SIMAWARS_DEPLOY_ENABLED=true`; saat tidak aktif, job pemeriksaan menulis alasan di log dan ringkasan run. Dispatch manual dari branch lain atau tanpa konfirmasi target yang tepat ditolak. Job deploy memakai GitHub environment `production` dan mengikuti aturan proteksi yang dikonfigurasi di sana.

Siapkan konfigurasi GitHub berikut sebelum menjalankan deploy manual. Variabel `CPANEL_SSH_*` dan secret boleh berada pada repository atau environment `production`, tetapi sakelar otomatis `SIMAWARS_DEPLOY_ENABLED` harus berada pada repository karena diperiksa sebelum job masuk ke environment.

- Variables: `CPANEL_SSH_HOST` (hostname/IP SSH), `CPANEL_SSH_PORT` (1–65535), `CPANEL_SSH_USER` (akun cPanel).
- Secrets: `CPANEL_SSH_PRIVATE_KEY` (private key akun deploy), `CPANEL_SSH_KNOWN_HOSTS` (baris host key yang **sudah diverifikasi** dari penyedia; untuk port selain 22 gunakan format `[host]:port`). Jangan membuat trust otomatis dari `ssh-keyscan` tanpa verifikasi fingerprint.
- Jika ingin deploy otomatis saat push, set `SIMAWARS_DEPLOY_ENABLED=true` sebagai repository variable secara terpisah. Nilai kosong atau selain `true` membuat push tidak mendeploy, tetapi tidak menghalangi deploy manual.

Di cPanel, pastikan SSH akun tersebut aktif dan dapat memakai `bash`, `unzip`, `sha256sum`, serta PHP CLI 8.2 atau lebih baru. Workflow memerlukan layout release sendiri: document root **tepat** di `$HOME/public_html/simawars.web.id` (direktori biasa dan writable), direktori privat `$HOME/simawars-deploy/shared`, serta `.env` yang dapat dibaca di `$HOME/simawars-deploy/shared/.env`. Layout ini berbeda dari ekstraksi manual ke `/home/USER/simaster` pada bagian 3; jangan menggabungkan kedua lokasi. Siapkan `.env` sesuai bagian konfigurasi produksi di bawah dan pertahankan `APP_KEY` lama bila memindahkan data. PHP **web** untuk `simawars.web.id` juga harus 8.2 atau lebih baru; pemeriksaan SSH hanya membuktikan versi CLI, jadi verifikasi versi web melalui pengaturan domain/hosting sebelum deploy. Siapkan database MySQL dan migrasi yang telah ditinjau lebih dulu; workflow tidak menjalankan migrasi atau seeder.

Urutan workflow: periksa trigger dan konfigurasi GitHub → periksa cPanel lewat SSH tanpa perubahan server → build paket pada commit yang sama → unggah ZIP ke direktori privat → verifikasi checksum, isi paket, `.env`, koneksi database, dan migrasi tertunda → aktifkan release. Kegagalan prasyarat menampilkan `::error::` di GitHub atau `Deployment stopped:` dari skrip cPanel. Bila koneksi SSH gagal, periksa host, port, user, key, dan host key terverifikasi. Pemeriksaan ini tidak membuktikan cron, SMTP, atau fungsi HTTP setelah aktivasi; lakukan verifikasi pada bagian 8. Sebelum deploy aktual, siapkan backup database/dokumen dan paket sebelumnya untuk pemulihan.

Untuk produksi yang sudah memiliki login PHP 8.0, tempatkan modul Laravel pada subdomain dengan document root tersendiri. Sistem utama memilih tujuan berdasarkan role; PHP 8.2 menjalankan seluruh request Laravel. Paket ini memakai login Laravel mandiri. Login otomatis dari sistem utama memerlukan integrasi autentikasi berdasarkan kode/protokol login produksi, yang belum tersedia di workspace. Pengaturan versi PHP saja tidak menghubungkan kedua session login.

## 1. Syarat hosting

- **PHP web dan CLI 8.2**, gunakan patch 8.2 terbaru yang disediakan hosting. Lockfile ditujukan untuk PHP 8.2.0 atau lebih baru dan memakai Laravel 12, Filament 4, Livewire 3, Activitylog 4, Permission 6, DataTables 12, Excel 3.1 dan Symfony 7. Lihat [persyaratan resmi Filament 4](https://filamentphp.com/docs/4.x/introduction/installation).
- Ekstensi: bcmath, ctype, curl, dom, fileinfo, filter, gd, hash, iconv, **intl**, mbstring, openssl, pcre, PDO/pdo_mysql, session, SimpleXML, tokenizer, xml, xmlreader, xmlwriter, zip. Gunakan `composer check-platform-reqs --no-dev` untuk daftar lengkap sesuai dependency.
- MySQL 8.0+ dengan akses SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES, CREATE VIEW, SHOW VIEW dan TRIGGER. Pemulihan paket telah diuji pada MySQL 8.4; MariaDB perlu pengujian tersendiri.
- HTTPS/AutoSSL aktif. Akses Terminal/SSH, cron, dan SMTP yang diizinkan hosting.
- PHP `memory_limit` minimal 512M untuk ekspor besar; `upload_max_filesize=50M`, `post_max_size=64M`. Impor dataset besar lebih cocok melalui CLI dengan `memory_limit=1G`, bukan request web. Batas hosting harus disesuaikan dengan ukuran berkas yang benar-benar diunggah.

Pilih PHP 8.2 untuk domain/subdomain modul report. Pada Jagoan Hosting, menu bisa berupa Select PHP Version atau MultiPHP Manager, sesuai layanan. Bila domain utama harus tetap memakai PHP 8.0, atur versi hanya untuk lokasi Laravel. Jagoan menjelaskan konfigurasi handler per subdomain/direktori dalam [panduan multi versi PHP](https://www.jagoanhosting.com/tutorial/hosting/multi-versi-php-untuk-domain-addon-subdomain-directory-pada-hosting), dan [MultiPHP Manager untuk Dedicated Hosting](https://www.jagoanhosting.com/tutorial/cpanel/setting-php-dengan-multiphp-manager-di-dedicated-hosting). Jangan menambahkan handler versi berbeda secara bersamaan.

Periksa CLI terpisah. Contoh path EasyApache:

```bash
/opt/cpanel/ea-php82/root/usr/bin/php -v
```

Pada layanan CloudLinux, path dapat berupa `/opt/alt/php82/usr/bin/php`. Gunakan executable yang benar-benar ada dan hasil `-v` yang menunjukkan PHP 8.2. Jangan menganggap `php` default sama dengan versi web. Laravel 12 mensyaratkan PHP 8.2; lihat [persyaratan deployment Laravel 12](https://laravel.com/framework/docs/12.x/deployment).

## 2. Paket aplikasi dan data

Dua arsip dipindahkan terpisah:

1. `storage/app/releases/simaster-cpanel-*.zip`: kode, dependency **tanpa paket dev**, dan hasil build frontend. Tidak mengandung `.env`, database, dokumen, workbook, atau cache konfigurasi lokal.
2. `storage/app/backups/<waktu>/simaster-data.zip`: `database.sql`, `manifest.json` berisi jumlah baris/hash, dan `storage/app/private`/`public` yang berisi dokumen. SQL tidak mengikat view ke akun MySQL lokal melalui DEFINER.

Arsip data berisi informasi internal dan akun pengguna. Upload ke `/home/USER/transfer`, **di luar `public_html`**. `.env` dan `APP_KEY` dikirim melalui kanal privat terpisah. Simpan backup sebelum dan setelah perbaikan sampai pemindahan terverifikasi.

Untuk membangun ulang dari komputer pengembangan:

```bash
docker compose exec -T laravel.test npm ci
docker compose exec -T laravel.test npm run build
docker compose exec -T laravel.test php artisan simaster:backup
docker compose exec -T laravel.test php scripts/build-cpanel-package.php
```

Jika aplikasi masih menerima perubahan data, hentikan penulisan selama snapshot final (`php artisan down`, backup, lalu `php artisan up`). Backup database memakai consistent snapshot InnoDB; jangan melakukan migrasi atau perubahan struktur saat backup berjalan.

## 3. Ekstrak dan atur document root

Ekstrak arsip aplikasi ke `/home/USER`; hasilnya `/home/USER/simaster`. Buat `/home/USER/transfer/data` dan ekstrak arsip data di sana. Jangan mengekstrak `database.sql` ke folder publik.

**Pilihan utama: subdomain modul report dengan document root `/home/USER/simaster/public`.** Set melalui cPanel Domains. Semua folder app, vendor, storage dan `.env` tetap berada di luar document root. Laravel mensyaratkan entry point `public/index.php`; lihat [panduan deployment Laravel 12](https://laravel.com/framework/docs/12.x/deployment).

**Alternatif untuk instalasi mandiri pada domain utama dengan document root tetap `public_html`:** gunakan langkah berikut hanya bila `public_html` tidak menjadi lokasi sistem login PHP 8.0 yang harus dipertahankan. cPanel tidak mengizinkan perubahan document root domain utama melalui menu Domains. Lihat [dokumentasi cPanel](https://docs.cpanel.net/cpanel/domains/domains/manage-the-domain/110/).

- Pertahankan aplikasi di `/home/USER/simaster`.
- Cadangkan isi website lama bila `public_html` sudah digunakan.
- Salin **isi** `/home/USER/simaster/public/` (termasuk `.htaccess`, build dan assets) ke `/home/USER/public_html/`. Pertahankan salinan public asli untuk pemeriksaan CLI.
- Ganti `/home/USER/public_html/index.php` dengan salinan `/home/USER/simaster/deploy/cpanel/public_html-index.php`. Template mengarah ke folder saudara bernama `simaster`; sesuaikan `$basePath` bila nama folder berbeda.
- Dokumen aplikasi tidak membutuhkan `public/storage` atau `storage:link`. Download dilakukan melalui route yang memeriksa login/hak akses.

## 4. Buat MySQL dan pulihkan snapshot

Di cPanel Database Wizard buat database dan user, lalu beri hak akses ke database tersebut. Nama biasanya memiliki prefix akun cPanel, misalnya `USER_simaster`.

Impor SQL **ke database baru yang kosong**. Gunakan Terminal untuk menghindari batas ukuran phpMyAdmin. `-p` meminta password secara interaktif dan tidak memasukkannya ke riwayat command:

```bash
mysql --default-character-set=utf8mb4 -u USER_simaster -p USER_simaster < /home/USER/transfer/data/database.sql
cp -a /home/USER/transfer/data/storage/app/private/. /home/USER/simaster/storage/app/private/
cp -a /home/USER/transfer/data/storage/app/public/. /home/USER/simaster/storage/app/public/
```

Jika folder `public` tidak berisi dokumen, tidak perlu disalin. Jangan menjalankan `migrate:fresh`, `db:wipe`, atau `dataset:import-all` pada database yang telah dipulihkan. Snapshot final sudah memuat seluruh 23.168 baris Recurring. Import snapshot dataset menghapus isi tabel tujuan sebelum mengisinya kembali.

Hubungkan DBeaver ke host/database cPanel ini menggunakan SSH tunnel jika tersedia. Koneksi DBeaver lokal `127.0.0.1:3306` tidak otomatis berpindah ke database cPanel.

## 5. Konfigurasi produksi

Salin `.env.cpanel.example` menjadi `.env` dalam `/home/USER/simaster`, isi domain, database, password SMTP dan penerima notifikasi yang benar.

- Pertahankan **APP_KEY lama** saat memindahkan database aplikasi. Jangan menjalankan `key:generate` untuk pemindahan ini. Hanya instalasi baru tanpa data lama yang memakai key baru.
- `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `APP_URL=https://DOMAIN`.
- `SESSION_COOKIE=simaster_report_session`, `SESSION_DOMAIN=null`: session Laravel memakai cookie sendiri pada subdomain report. Session PHP 8.0 tidak otomatis dapat dibaca Laravel.
- `TRUSTED_PROXIES` kosong untuk HTTPS langsung. Jika ada proxy, isi hanya alamat IP/CIDR proxy resmi dari hosting; jangan memakai `*`.
- `DB_HOST=localhost` lazim pada cPanel; ikuti host yang diberikan penyedia. Jangan gunakan host Docker `mysql`, user `sail`, atau database tanpa prefix bila hosting memberikan prefix.
- Klik kartu notifikasi untuk membuka tabel data bermasalah sesuai konteks peringatan. Tautan notifikasi lama dibuat ulang pada domain aplikasi yang sedang aktif, sehingga tidak mengarah kembali ke domain pengembangan setelah pemindahan.
- `QUEUE_CONNECTION=sync` sesuai notifikasi saat ini; cron menjalankan scheduler. Tidak perlu proses `artisan serve`, Docker, Vite dev server, atau `schedule:work` pada cPanel.
- Isi `ELECTRICITY_ALERT_EMAIL`, `INFRASTRUCTURE_ALERT_EMAIL`, `SITE_LOSS_ALERT_EMAIL`. Jadwal memakai **08.00 dan 17.00 WIB** (`Asia/Jakarta`). Timestamp aplikasi tetap UTC untuk konsistensi data lama.
- SMTP port 587: `MAIL_SCHEME=smtp` (STARTTLS); port 465: `MAIL_SCHEME=smtps`. Ikuti sertifikat, host, dan konfigurasi penyedia; jangan menonaktifkan validasi TLS.

Atur `.env` agar hanya dibaca pemilik akun (600). Folder storage dan bootstrap/cache harus writable oleh PHP akun cPanel; umumnya 755 cukup. Hindari chmod 777.

## 6. Jalankan instalasi

Contoh menggunakan PHP 8.2; ubah USER dan executable sesuai hosting:

```bash
cd /home/USER/simaster
PHP_BIN=/opt/cpanel/ea-php82/root/usr/bin/php
"$PHP_BIN" artisan down
"$PHP_BIN" /path/to/composer check-platform-reqs --no-dev
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan filament:optimize-clear
"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan db:seed --class=RoleAndUserSeeder --force
"$PHP_BIN" artisan filament:assets
"$PHP_BIN" artisan optimize
"$PHP_BIN" artisan filament:optimize
"$PHP_BIN" artisan simaster:production-check
"$PHP_BIN" artisan schedule:list
```

Dependency produksi sudah ada di arsip. Jika memakai kode tanpa vendor, jalankan `"$PHP_BIN" /path/to/composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction` sebelum Artisan. Perintah composer harus memakai PHP yang sama dengan web; path composer berasal dari hosting.

`/report` mengarahkan pengguna ke Dashboard Utama; login Filament tersedia di `/report/login`. Judul grup Infrastruktur Management membuka dashboard seluruh Infrastruktur di `/infrastruktur`, sedangkan judul grup Electricity membuka dashboard seluruh Electricity di `/electricity`. Panah di samping judul grup membuka dan menutup submodul langsung di sidebar. Nama modul bertingkat seperti Sewa Lahan, Listrik Centralized, dan Listrik Inbuilding membuka halaman modul; panah kecil di samping namanya membuka submodul. Sewa Lahan membawahi Site TP dan Site Telkomsel; Electricity membawahi Listrik Centralized dan Listrik Inbuilding, masing-masing dengan submodul master, payment, anomali, dan laporan terkait. Tidak ada item Dashboard Infrastruktur atau Dashboard Electricity tersendiri. Lonceng di samping avatar akun membuka notifikasi akun dari tabel MySQL yang sama, menampilkan jumlah belum dibaca, dan diperbarui setiap 30 detik. Persetujuan Akun tersedia di menu avatar khusus admin (`/report/account-approvals`), tanpa menu administrasi terpisah. Login Laravel `/login` tetap menggunakan guard dan akun yang sama. Hanya akun berstatus approved yang dapat masuk; persetujuan akun dibatasi role admin. Halaman operasional, termasuk form/detail, memakai custom page Filament; tabel/chart operasional tetap menggunakan implementasi teruji yang membaca MySQL. Layout Bootstrap dibatasi pada konten laporan agar tidak mengubah sidebar dan komponen Filament. Tidak ada iframe maupun database contoh baru.

Panel dan aset Livewire harus dapat diakses melalui HTTPS pada domain yang sama. Jangan memblokir endpoint update Livewire dengan aturan server tambahan. Jika document root menggunakan salinan `public_html`, salin kembali seluruh aset public setelah `filament:assets` (public/css/filament, public/js/filament, public/fonts/filament, public/vendor/simaster dan public/build). Build lokal memakai `npm ci && npm run build`; Node tidak diperlukan di hosting bila memakai paket ZIP yang sudah dibangun.

Migrasi kompatibilitas menambahkan `activity_log.batch_uuid` dan menyalin perubahan audit lama dari `attribute_changes` ke `properties`, sambil mempertahankan kolom sumber, metadata dan timestamp. Backup baru sudah memuat migrasi ini; pemulihan snapshot lama menerapkannya melalui `migrate --force`.

Seeder hanya menyediakan role; tidak mengisi data contoh. Bila database baru belum memiliki admin, isi `SIMASTER_INITIAL_ADMIN_*`, jalankan `optimize:clear` dan seeder, kemudian hapus ketiga nilai provisioning dari `.env`, jalankan `optimize:clear` dan `optimize` lagi. Password wajib minimal 12 karakter dengan huruf besar/kecil, angka dan simbol. Administrator dari snapshot lama tetap digunakan; akun demo dengan password lama dinonaktifkan oleh migrasi keamanan.

`simaster:production-check` mengembalikan exit code 1 bila konfigurasi/data/migrasi/aset/dokumen belum memenuhi pemeriksaan. Command tidak menghubungi SMTP dan tidak membuktikan cron sudah terpasang. Periksa semua baris FAIL sebelum menjalankan `"$PHP_BIN" artisan up`.

## 7. Pasang cron

Di cPanel Cron Jobs tambahkan setiap menit (gunakan path absolut; [dokumentasi cron cPanel](https://docs.cpanel.net/cpanel/advanced/cron-jobs/)):

```cron
* * * * * /opt/cpanel/ea-php82/root/usr/bin/php /home/USER/simaster/artisan schedule:run >> /home/USER/simaster/storage/logs/scheduler.log 2>&1
```

Aktifkan setelah domain, DB, SMTP, dan penerima sudah benar. Scheduler memberi lock `withoutOverlapping`; notifikasi mempunyai guard tiap slot WIB. Pantau log cron pada slot 08.00/17.00 dan lonceng aplikasi. Menjalankan `notifications:send-scheduled` secara manual dapat mengirim email nyata; lakukan hanya setelah penerima disepakati.

## 8. Verifikasi sebelum go-live

1. `simaster:production-check` lulus di PHP 8.2 CLI hosting. Periksa PHP 8.2 web melalui pengaturan domain juga; konfigurasi server lama tetap mengikuti kebutuhan sistem utama.
2. HTTPS login berhasil, logout berhasil, akun pending tidak bisa masuk, viewer tidak bisa import/edit, admin bisa mengelola data.
3. Dashboard Infrastruktur, tabel Sewa/Combat, dan drilldown menampilkan angka yang konsisten. Pilih periode Search All Resource dan bandingkan revenue/cost satu site dengan modul P&L pada periode yang sama.
4. Bandingkan jumlah baris dengan `manifest.json` backup final; Recurring 23.168, Sewa 46, Combat 77. Jumlah ini adalah snapshot saat paket dibuat, bukan target permanen sesudah input baru.
5. Lima dokumen Presales dan lima PDF BAPSS/BA Dismantle dari snapshot dapat dibuka setelah login. File Dismantle gabungan dipakai empat site sesuai isi PDF dan remark sumber. URL dokumen langsung `/storage/document-circulation/...` atau `/storage/bapss/...` tidak dapat digunakan. `/.env`, `/composer.json`, `/database.sql`, `/vendor/` harus memberi 403/404, bukan isi berkas.
6. `/report` membuka Dashboard Utama. Klik judul Infrastruktur Management/Electricity untuk membuka dashboard masing-masing dan klik panah di sisinya untuk menampilkan submodul langsung di sidebar, lalu periksa mode terang/gelap, filter dan modal. Lonceng di samping avatar menampilkan notifikasi milik akun aktif saja; klik seluruh kartu untuk membuka tabel masalah terkait dan pastikan hanya data relevan yang muncul. Persetujuan Akun muncul dalam menu avatar admin; viewer tidak dapat membuka halaman tersebut. Aset Filament, Bootstrap/jQuery/DataTables lokal dan CDN chart dimuat tanpa mixed content atau error browser. Halaman chart masih memakai CDN Highcharts/ApexCharts/Chart.js; koneksi keluar pengguna ke CDN tersebut diperlukan.
7. Email nyata dan cron harus diverifikasi di hosting. Pastikan `ELECTRICITY_ALERT_EMAIL`, `INFRASTRUCTURE_ALERT_EMAIL`, dan `SITE_LOSS_ALERT_EMAIL` mengarah ke penerima yang diinginkan; nilai yang kosong mengikuti fallback pada `config/mail.php` dan dapat berujung pada alamat yang sama. Pada slot 08.00/17.00 WIB, cocokkan email Centralized, Inbuilding, Site Loss dan empat kategori Infrastruktur dengan jumlah, tautan halaman, serta baris lampiran masing-masing. Lampiran listrik hanya memuat kenaikan >50% pada seluruh periode; Site Loss memakai periode terbaru dan mengabaikan baris data anomali. Periksa folder inbox dan log cron pada hosting karena tes lokal dan koneksi SMTP saja belum membuktikan penerimaan pesan.
8. Setelah verifikasi, buka aplikasi (`artisan up`), simpan satu backup di lokasi privat terpisah, dan hapus salinan SQL/ZIP dari area transfer yang tidak lagi diperlukan.

## Pemulihan

Sebelum perubahan berikutnya buat backup DB/dokumen dan pertahankan paket kode sebelumnya. Untuk kembali, hentikan penulisan, pulihkan kode, database dan dokumen dari pasangan snapshot yang sama, pertahankan APP_KEY yang sesuai, jalankan `optimize:clear`, `optimize`, pemeriksaan dan smoke test. Jangan memakai `migrate:rollback` untuk mempublikasikan kembali dokumen privat atau mengaktifkan akun demo.

## Batas data yang diketahui

Paket memakai snapshot workbook yang tersedia, bukan feed otomatis ke sistem sumber. Modul PO Varcost, progress relokasi dan upload PDF dapat kosong bila belum ada input operasional. Search All Resource memakai `site_monthly_metrics` untuk periode terpilih; tabel legacy `site_financials`/view lama tetap dipertahankan untuk kompatibilitas dan bukan sumber finansial layar ini. Tidak ada angka pengganti yang dibuat ketika suatu site tidak memiliki P&L pada bulan tersebut.

Label “Download” dari workbook BAPSS disimpan sebagai metadata `source_documents`, bukan sebagai path. PDF yang tersedia di folder sumber telah disalin dengan verifikasi SHA-256. BA Dismantle DPK120, BAPSS BKS372, dan BA Dismantle BKS372 belum ada pada sumber yang tersedia; UI menampilkan “Berkas sumber belum tersedia” (termasuk site yang merujuk BKS372). Unggah berkas asli melalui Edit BAPSS ketika tersedia. Perubahan metadata lewat upload Excel tidak menghapus path dokumen yang sudah diunggah.

Untuk penambahan PDF secara massal, buat manifest JSON di folder yang sama dengan berkas, berisi daftar objek `file`, `type` (`bapss`/`dismantle`), dan `site_ids`. Jalankan `php artisan dataset:attach-bapss-pdfs /path/manifest.json`. Command menolak berkas di luar folder manifest, memeriksa signature PDF dan hash salinan, serta mempertahankan dokumen yang sudah tersedia. Manifest pemetaan sumber lokal berada di folder DATASET BAPSS; data ini tidak ditanamkan ke kode aplikasi.
