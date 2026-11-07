# Peta site per modul

Peta memakai komponen Blade/JavaScript bersama di dalam panel Filament yang sama. Route operasional, controller bisnis, query tabel, dan format impor tetap digunakan. Pendaftaran halaman peta berada di `config/site-map.php`. `DashboardSiteMapService` membaca data sesuai modul; endpoint JSON `/reports/site-map/{module}` memerlukan akun disetujui dan menghormati akses viewer per modul.

## Menyiapkan rilis

```sh
npm ci
npm run build
php artisan migrate --force
php artisan sites:resolve-locations
php artisan sites:import-tracy-coordinates "DATASET/RRU/Cost Tracy_202601_202607.csv"
```

Migrasi hanya menambahkan tabel `site_locations`. Resolver tidak mengubah Site Owner, P&L, listrik, atau data Infrastruktur. Jalankan ulang setelah impor/perubahan alamat; gunakan `--retry` untuk mencoba lagi alamat yang sebelumnya belum ditemukan. Hasil lokasi disimpan di database sehingga tidak perlu mencari ulang saat dashboard dibuka. Backup database harus mencakup tabel ini.

Hasil pencarian koordinat berada pada database lingkungan tempat resolver dijalankan. Deployment kode saja tidak memindahkan hasil tersebut; sertakan tabel lokasi melalui prosedur pemindahan database yang sudah ada atau jalankan impor sumber di lingkungan tujuan. Berkas sumber internal dan cache respons geocoder tidak perlu dimasukkan ke Git.

## Sumber lokasi

Urutan sumber: koordinat valid pada detail sumber aktif, pencocokan Site ID dengan sumber Infrastruktur, master koordinat tersimpan, lalu hasil decoding Plus Code/geocoding dengan alamat yang masih sesuai. Ejaan `longtitude`, koma desimal, serta detail Combat bertingkat didukung. Koordinat kosong, tertukar, atau di luar Indonesia ditolak. Kode site dibandingkan setelah trim dan normalisasi huruf.

Resolver lokal membaca alamat Site Owner, PLN, dan Inbuilding. Plus Code pendek hanya diterima ketika satu kandidat berada dalam poligon kabupaten/kota yang sesuai. Referensi wilayah disimpan di `resources/data/site-map-localities.geojson`, berasal dari [geoBoundaries IDN ADM2](https://www.geoboundaries.org/api/current/gbOpen/IDN/ADM2/), BPS/WFP/OCHA ROAP, CC BY 3.0 IGO. Referensi ini menggambarkan wilayah sumber tahun 2020 dan bukan pembagian operasional NOP. `scripts/prepare-site-map-boundaries.mjs` dapat membangun ulang subset dari unduhan GeoJSON sumber.

Site tanpa koordinat tetap dihitung dan tercantum bersama alamatnya dalam daftar yang dapat dicari. Lokasi pusat kota, titik acak, atau titik yang digeser tidak digunakan sebagai pengganti lokasi site. Warna dan label mengikuti NOP. Area NOP pada peta merupakan convex hull titik yang tersedia, diberi keterangan sebagai perkiraan cakupan titik, bukan batas administratif resmi. Titik yang bertumpuk tetap pada koordinat asli; daftar menyediakan pilihan site untuk membuka detail titiknya.

NOP kosong ditampilkan sebagai `Tanpa NOP`; nama NOP tidak ditebak dari kabupaten/kota. Label NOP yang berdekatan diatur agar tetap terbaca tanpa memindahkan koordinat site.

## Filter dan isi tooltip

- Dashboard utama/P&L: NOP, tahun, bulan; P&L juga kota dan status. Nilai tooltip mengikuti agregasi periode, termasuk pengecualian anomali pada mode tahunan.
- Infrastruktur/Sewa Lahan/Combat: sumber, tahun, kepemilikan, dan Jarvis mengikuti aturan canonical site, ownership, serta soft-delete yang sudah ada. Peta mencantumkan site unik dan jumlah baris sumber; kartu modul mempertahankan hitungan baris sumber yang dapat berulang pada Site ID yang sama.
- Electricity: populasi master Centralized/Inbuilding dan NOP sama dengan total site dashboard. Periode pembayaran tidak mengurangi populasi master. Site yang tercatat pada kedua master tetap mempertahankan keanggotaan sumbernya.
- Recurring Tagihan IPAS: filter TP memakai rincian termin; site dihitung satu kali.
- Equipment Relocation: daftar Site ID dari hasil filter UI yang sudah ada membatasi data peta di browser. Ribuan ID tidak dikirim melalui URL.

Tooltip berukuran 205 px dengan teks 12 px dan tinggi baris 18 px. Isinya Site ID, nama, NOP, kabupaten/kota, kecamatan jika tersedia, informasi modul, dan asal koordinat. MoM/YoY/payload tidak dibuat jika sumber tidak menyimpan metrik tersebut. Hover dan ketukan membuka tooltip; daftar menyediakan akses melalui keyboard. Peta memakai Leaflet 1.9.4 lokal dan tile OpenStreetMap, sehingga latar peta memerlukan internet.

## Geocoding tambahan (opsional)

Tidak ada geocoding eksternal otomatis. Jika tersedia endpoint Photon perusahaan/self-hosted, isi `SITE_GEOCODING_URL`, token bila diperlukan, dan jeda request, lalu jalankan:

```sh
php artisan sites:resolve-locations --geocode --retry --limit=100
```

Gunakan endpoint yang mengizinkan volume dan penyimpanan hasil yang dibutuhkan. Permintaan mengirim alamat ke endpoint tersebut, secara berurutan, dengan timeout, jeda, dan lock proses. Kegagalan jaringan menghentikan proses tanpa menghapus hasil sebelumnya. Kecocokan pusat kota saja ditolak; nama jalan harus cocok utuh, kecamatan eksplisit dan nomor bangunan tidak boleh bertentangan, serta titik harus berada dalam poligon kabupaten/kota. Beberapa titik berbeda yang sama-sama cocok tidak dipilih otomatis. Kecocokan nomor bangunan diberi label pencocokan alamat bangunan; hasil jalan ditandai sebagai perkiraan ruas jalan. Tidak memakai server publik Nominatim sebagai layanan geocoding massal.

Jika alamat lengkap belum menghasilkan titik, resolver mencoba nama jalan dan nomor bangunan yang diekstrak dari alamat tersebut. Pemeriksaan tetap memakai alamat asli; kandidat dari kedua pencarian digabung agar kandidat ambigu tidak dipilih. Jeda request juga berlaku pada pencarian kedua. Tanpa kecocokan nomor bangunan, hanya objek ruas jalan yang memiliki rentang geometri maksimal 750 meter dapat dipakai sebagai perkiraan; gedung lain yang kebetulan berada pada jalan yang sama ditolak.

Hasil yang sebelumnya belum ditemukan disimpan agar tidak diminta berulang. Mode `--geocode` dapat melanjutkan alamat yang belum pernah dikirim ke geocoder meskipun resolver lokal sebelumnya belum menemukan koordinatnya. Jalankan tanpa `--retry` untuk melanjutkan batch berikutnya; `--retry` mengulangi hasil gagal dari provider. Alamat tanpa wilayah yang diketahui tidak dikirim dan tidak menghabiskan batas batch. Perubahan alamat membuat hasil lama tidak dipakai untuk alamat baru. Data master dengan `source = master` dipertahankan saat resolver berjalan.

## Koordinat dari CSV Tracy

CSV Tracy menyimpan `siteid`, `latitude`, `longitude`, `yearmonth`, kabupaten, serta kecamatan. Importer membacanya secara streaming dan mencocokkan Site ID dengan master modul yang ada. Untuk setiap Site ID dipilih periode valid terbaru; dua koordinat berbeda pada periode terbaru yang sama dianggap konflik dan tidak diimpor. Koordinat kosong/tertukar/di luar Indonesia juga ditolak.

Kabupaten/kota dan kecamatan disimpan di tabel lokasi sebagai pelengkap tooltip jika master modul belum menyediakannya. Migrasi tambahan hanya menambahkan dua kolom nullable pada `site_locations`. Pengisian wilayah pada lokasi yang sudah ada tidak mengubah koordinatnya.

Gunakan `--dry-run` untuk memeriksa hasil sebelum menyimpan. Impor mengisi tabel `site_locations`, mempertahankan hasil dataset/Plus Code/master yang telah tersedia, dan tidak mengubah alamat, Site ID, atau data bisnis. Impor ulang dapat memperbarui hasil Tracy jika periode sumber lebih baru. Tooltip mencantumkan periode serta Site ID sumber. Jika nama site secara eksplisit diawali kode site fisik berbeda, pencocokan alias hanya diterima apabila kode tersebut ada di CSV dan kabupaten/kotanya sama; nama yang sekadar mirip tidak dicocokkan.

Lokasi Tracy dicocokkan melalui identitas site, sehingga koreksi penulisan alamat tidak menghilangkan koordinatnya. Resolver alamat mempertahankan hasil impor tersebut. Tidak perlu mengirim alamat lengkap ke layanan geocoding publik ketika koordinat site sudah tersedia dalam dataset ini.

CSV koordinat yang dinormalisasi dari inventaris RRU atau Site Full Cost menggunakan kolom yang sama dan dapat dibaca dengan `--source=rru_inventory` atau `--source=site_cost`. Sumbernya ditampilkan sesuai asal data. Untuk RRU, titik di luar kabupaten/kota master yang memiliki referensi batas wilayah dilewati; ini mencegah koordinat snapshot lama dari site berpindah dipakai pada wilayah yang berbeda. Sumber lain tidak menimpa koordinat yang sudah tersedia.

`SiteAddressCatalog` hanya membaca alamat master yang sudah ada. Nilai placeholder seperti `tbd`, `ogc`, dan `#N/A` dapat memakai alamat lengkap dari PLN/Inbuilding/Recurring dengan Site ID yang sama. Alamat asli tidak diubah. Alamat master yang sudah terisi tetap didahulukan; kecocokan geocoding diikat ke alamat aktif agar alamat lama tidak mempertahankan titik setelah alamat diperbarui.

Referensi poligon mencakup 41 kabupaten/kota Jawa Barat, Banten, dan Jakarta yang berkaitan dengan sumber data. Alamat dengan nama administratif eksplisit dapat memakai referensi ini. Untuk Plus Code yang hanya menyebut Jakarta, poligon Jakarta digabung dan hasil tetap harus unik; pusat wilayah tidak dijadikan koordinat site.

Notasi identitas lama yang eksplisit, misalnya `PHY001 EX PHY002`, menggunakan kode saat ini `PHY001` dan tetap menyimpan notasi asli sebagai identitas baris modul. Kode asal koordinat tetap ditampilkan pada tooltip; aturan ini tidak mengubah Site ID pada tabel bisnis.

## Validasi

```sh
php artisan test --filter='DashboardSiteMapTest|AddressPlusCodeTest|DashboardViewerAccessTest'
node --test tests/dashboard-site-map.test.cjs tests/infrastructure-site-map.test.cjs tests/electricity-dashboard-ui.test.cjs
```
