# Deployment beta.simawar.web.id melalui cPanel Git

Checkout cPanel berada di `/home3/simawarw/beta.simawar.web.id`. Document Root subdomain harus `/home3/simawarw/beta.simawar.web.id/public`; pengaturan ini sudah berhasil diubah melalui **Domains → Manage**. Akar repository, `.env`, `storage`, dan dependency tetap berada di luar Document Root.

## Pemeriksaan cPanel Git dan runtime

cPanel mensyaratkan `.cpanel.yml` yang sudah **committed** di branch yang sedang di-checkout, serta checkout yang bersih. `Update from Remote` mengambil commit baru dari GitHub, sedangkan tombol **Deploy HEAD Commit** menjalankan `.cpanel.yml`. Jika tombol masih nonaktif ketika checkout bersih, jalankan task yang sama dari Terminal di akar repository untuk melihat kegagalan runtime secara langsung:

```bash
cd /home3/simawarw/beta.simawar.web.id
/bin/bash -euo pipefail -- scripts/cpanel-deploy-beta.sh
```

Jika commit baru memuat migrasi database, siapkan dan verifikasi paketnya lebih dulu dengan `scripts/cpanel-deploy-beta.sh --prepare-only`. Opsi ini tidak menjalankan preflight, menyalin aset publik, atau mengaktifkan rilis. Jalankan migrasi yang sudah ditinjau lewat `artisan` di `~/simawar-beta-deploy/releases/$(git rev-parse HEAD)`, lalu jalankan skrip tanpa opsi setelah skema siap. Perintah lengkap tersedia pada panduan [Tagihan IPAS](recurring-tagihan-ipas.md) dan [data operasional](operational-data-flow.md).

Jika HEAD cPanel sudah memuat `.cpanel.yml` tetapi tetap menampilkan **The system cannot deploy**, periksa checkout **di server**, bukan `git status` pada komputer pengembangan:

```bash
cd /home3/simawarw/beta.simawar.web.id
git status --short --untracked-files=all
git ls-files --error-unmatch .cpanel.yml
```

File `public/error_log` yang dibuat hosting dapat membuat checkout kotor; `.gitignore` mengabaikannya setelah perubahan aturan ignore di-commit dan ditarik ke server. Baca isi log itu untuk mencari penyebab HTTP 500. File sementara seperti `public/php-check-*.php` juga dapat muncul sebagai untracked; periksa lalu hapus file uji yang sudah tidak dipakai. Jangan memakai `git clean -fd` di checkout ini karena `.env`, storage, dan data server harus tetap terjaga. Bila `git status` menampilkan file tracked yang berubah, periksa perbedaannya sebelum memulihkan file tersebut. Refresh halaman cPanel setelah checkout bersih.

HTTP 500 adalah masalah runtime yang terpisah dari tombol Deploy. Selama paket runtime belum aktif, `public/index.php` dapat gagal saat memuat `vendor/autoload.php`. Setelah deploy berhasil, jika 500 masih muncul, baca `public/error_log` dan `storage/logs/laravel.log` di server; periksa juga versi PHP web, `.env` server, koneksi database, dan izin tulis `storage`.

Hosting ini mempunyai PHP CLI 8.3 di `/opt/alt/php83/usr/bin/php`, tetapi `php` default adalah 8.0 dan Composer, Node, serta npm tidak tersedia. Kebijakan `DISEVAL` pada hosting memblokir opsi PHP `-r`, sehingga skrip deployment memakai berkas PHP biasa untuk pemeriksaan CLI dan metadata. Karena itu GitHub Actions membangun dependency dan aset terlebih dahulu, lalu menerbitkan paket ZIP untuk SHA commit yang tepat sebagai GitHub prerelease `beta-<SHA>`. Paket ini tidak memuat `.env`, database, atau dokumen operasional. cPanel mengunduh paket untuk HEAD yang sama dan memasang hanya runtime hasil build ke checkout Git. `curl` dan `unzip` sudah tersedia di hosting. Workflow produksi `simawars.web.id` tetap terpisah.

## Sebelum deploy pertama

1. Pastikan `beta.simawar.web.id` memakai **PHP web 8.2 atau lebih baru**. Versi PHP CLI tidak membuktikan versi web; pemeriksaan pertama menunjukkan PHP web 8.0.30. Menu **Select PHP Version** pada akun ini menampilkan **Account PHP Settings**, bukan pemilih per subdomain. `public/.htaccess` mengikuti [panduan Jagoan Hosting untuk subdomain](https://www.jagoanhosting.com/tutorial/hosting/multi-versi-php-untuk-domain-addon-subdomain-directory-pada-hosting) dengan handler `alt-php83___lsphp`. Skrip memeriksa hasilnya melalui berkas PHP sementara yang segera dihapus. Jika web tetap memakai PHP 8.0 atau memberi 500 setelah aturan diterapkan, minta Jagoan Hosting mengaktifkan handler PHP 8.3 khusus `beta.simawar.web.id` tanpa mengubah domain lain.
2. Buat database MySQL dan user di cPanel. Jika memindahkan database dan dokumen lama, lakukan backup dan pemulihan sesuai [panduan cPanel utama](deploy-cpanel.md). Jangan menjalankan migrasi otomatis pada database berisi data tanpa backup dan tinjauan skema.
3. Buat `.env` privat di akar checkout server, mengacu pada `.env.cpanel.example`. Set `APP_NAME=SIMAWAR`, `APP_URL=https://beta.simawar.web.id`, `APP_ENV=production`, `APP_DEBUG=false`, APP_KEY yang sesuai, koneksi MySQL, dan SMTP. Pertahankan APP_KEY lama bila data lama dipindahkan. Jangan commit `.env`, atau mengirim password lewat chat. Batasi akses file (`chmod 600 .env`).
4. Pastikan direktori `storage/` berisi data yang memang diperlukan dan writable oleh PHP hosting. Preflight menuntut tabel migrasi dan tidak adanya migrasi tertunda. Jalankan migrasi yang telah ditinjau secara terpisah menggunakan PHP CLI 8.2 atau lebih baru.
5. Jika sudah ada `vendor` atau direktori aset hasil build di checkout cPanel, tinjau isinya dan pindahkan secara sadar ke lokasi backup pribadi sebelum deploy. Skrip menolak menimpa direktori yang sudah ada.

## Memperbarui beta

Untuk pembaruan input manual/Excel dan laporan dinamis, ikuti urutan backup, migrasi view operasional, refresh laporan, serta deployment pada [panduan data operasional](operational-data-flow.md).

Untuk pembaruan Tagihan IPAS menjadi satu baris per site, gunakan urutan maintenance, backup, migrasi khusus, dan deployment di [panduan Tagihan IPAS](recurring-tagihan-ipas.md). Tidak perlu mengimpor ulang workbook atau menyalin database lokal ke beta.

1. Commit dan push perubahan ke branch `main` di `https://github.com/Marsellns/SIMAWAR-TKM`. Pastikan remote checkout cPanel juga menunjuk repository ini; skrip mengambil paket rilis dari repository yang sama. Tunggu GitHub Actions **Build SIMAWAR beta for cPanel Git** berhasil dan prerelease `beta-<SHA>` berisi ZIP serta `.sha256`. Paket dibangun dari commit yang sama dengan source Git.
2. Di cPanel **Git Version Control**, klik **Update from Remote**. Periksa HEAD berubah ke SHA commit tersebut dan checkout bersih.
3. Klik **Deploy HEAD Commit**. `.cpanel.yml` menjalankan `scripts/cpanel-deploy-beta.sh`. Skrip memeriksa Document Root dan PHP web melalui URL HTTPS sementara, memakai PHP CLI 8.2 atau lebih baru, mengunduh paket dengan checksum, memeriksa `RELEASE.json` dan `composer.lock`, menjalankan preflight database, lalu mengaktifkan runtime yang cocok dengan commit. Pesan gagal diawali `SIMAWAR beta deployment stopped:`.
4. Verifikasi `/login` dan URL CSS/JS yang benar-benar dimuat halaman, login dan logout, serta fungsi yang memerlukan database dan notifikasi. Pastikan cron scheduler dan SMTP dikonfigurasi sesuai kebutuhan; deploy tidak membuat cron atau mengirim email pengujian.

Paket runtime lama disimpan di `~/simawar-beta-deploy/releases/<SHA>`. Source tetap berasal dari checkout Git. Untuk kembali ke kode sebelumnya, revert commit di GitHub lalu ulangi **Update from Remote** dan **Deploy HEAD Commit**; paket untuk SHA revert dibangun oleh workflow. Pemulihan database/dokumen dilakukan terpisah. cPanel menarik source sebelum task deploy berjalan, sehingga selama pembaruan beta mungkin ada jeda singkat dengan source dan runtime versi berbeda.

Script hanya membuat symlink `vendor` ke release privat. Direktori release berizin `0700` sehingga web server hosting tidak dapat melayani aset publik melalui symlink ke sana. Aset `public/build`, `public/css/filament`, `public/js/filament`, `public/fonts/filament`, dan `public/vendor/simaster` disalin sebagai file biasa berizin `0644` dalam direktori berizin `0755`. Pada deploy pertama setelah perubahan ini, skrip hanya mengganti symlink aset lama yang menunjuk ke lokasi release yang diharapkan. Deploy berikutnya hanya memperbarui direktori aset yang memiliki penanda `.simawar-deploy-managed`; file lama tetap disimpan agar permintaan aset dari halaman yang masih terbuka tidak langsung 404. Source privat, `.env`, dan database tetap di luar Document Root.
