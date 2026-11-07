# Deploy peta site melalui cPanel Git

Panduan ini memakai alur yang sudah ada untuk `beta.simawar.web.id`, checkout `/home3/simawarw/beta.simawar.web.id`, dan Document Root pada subfolder `public`. GitHub Actions membangun paket runtime sesuai commit; cPanel mengambil paket yang sama saat **Deploy HEAD Commit**. `.env`, `APP_KEY`, storage, dan data bisnis hosting tetap dipakai.

## Persiapan sekali untuk rilis peta

1. Di GitHub **Actions**, tunggu **Build SIMAWAR beta for cPanel Git** pada commit yang akan dipasang berstatus sukses. Build membuat release `beta-<SHA>` dengan ZIP dan checksum. Workflow ini sudah menyertakan Leaflet lokal; tidak perlu menjalankan npm di hosting.
2. Cadangkan database live melalui fasilitas backup yang biasa digunakan.
3. Di cPanel **Git Version Control → Manage → Pull or Deploy**, pastikan branch `main`, lalu klik **Update from Remote**. Cocokkan HEAD dengan commit yang build-nya berhasil.
4. Di cPanel **Terminal**, jalankan:

   ```bash
   cd /home3/simawarw/beta.simawar.web.id
   bash scripts/cpanel-prepare-site-map.sh
   ```

   Script memilih PHP CLI 8.2+, menyiapkan paket commit itu melalui `--prepare-only`, lalu menjalankan hanya dua migrasi peta. Migrasi membuat tabel `site_locations` dan menambahkan kolom kota/kecamatan. Script tidak mengaktifkan release dan tidak menjalankan migrasi modul lain. Jalankan persiapan ini sebelum Deploy HEAD agar pemeriksaan migrasi tidak menghentikan deployment.
5. Buka **phpMyAdmin**, pilih database yang dipakai `.env` hosting, buka tab **Import**, lalu pilih berkas privat `site-map-locations-20261107.sql.gz` yang disiapkan terpisah dari GitHub. Berkas ini hanya berisi `INSERT IGNORE` ke `site_locations`, tanpa kolom ID lokal. Baris dengan Site ID yang sudah ada dipertahankan; data bisnis, struktur tabel lain, dan akun tidak diubah. Impor dapat diulang tanpa menggandakan Site ID. Berkas tidak berisi `DROP`, `TRUNCATE`, atau perintah mengganti database.
6. Kembali ke cPanel Git dan klik **Deploy HEAD Commit**. Tunggu log berakhir dengan `SIMAWAR beta activated commit <SHA>` dan cocokkan SHA dengan langkah 1. Jika masih ada migrasi modul lain yang tertunda, pemeriksaan deploy akan tetap berhenti; tinjau migrasi tersebut secara terpisah.
7. Buka website dan lakukan **Ctrl+F5**. Periksa peta pada dashboard, filter NOP, hover titik, dan daftar belum terpetakan. Pastikan endpoint peta hanya dapat dibuka setelah login dengan akses modul yang sesuai.

Berkas koordinat mengandung informasi lokasi internal. Gunakan file lokal langsung pada phpMyAdmin melalui HTTPS; simpan salinan di lokasi privat. Repo dan GitHub Release hanya membawa kode, aset, dan referensi batas administratif publik. Jangan memasukkan berkas koordinat atau database ke repo publik.

## Deploy berikutnya

Jika tidak ada migrasi baru atau penggantian data koordinat, urutannya cukup: tunggu build pada `main` sukses → **Update from Remote** → **Deploy HEAD Commit**. Hasil koordinat tetap berada pada database hosting dan tidak perlu dicari ulang.

## Pemulihan

Simpan SHA rilis sebelumnya dan backup database sebelum persiapan. Untuk pemulihan kode, gunakan prosedur release sebelumnya yang sudah ada; tabel koordinat tambahan dapat tetap tersimpan ketika kode lama dipakai. Hindari rollback database penuh jika perubahan hanya menyangkut tampilan peta. Pemulihan database diperlukan bila data benar-benar diubah dan harus dilakukan dari backup hosting yang sesuai.
