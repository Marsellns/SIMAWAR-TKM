# Data Listrik All dan notifikasi operasional

## Pemeriksaan sumber 2 Oktober 2026

Sumber: `DATASET/03 Electricity/Centralized/Listrik All/Data Master PLN Centralized_Flagging September 2026_Eastern Jabotabek.xlsx`, sheet `Data Master`, header baris 3 dan data baris 4–4030.

- Terdapat 4.027 rekening PLN dan 4.023 Site ID unik. Beberapa site memiliki lebih dari satu rekening; jumlah rekening dan site tidak boleh dipertukarkan.
- Flagging positif Januari–Agustus 2026 mencakup berturut-turut 3.872, 3.882, 3.904, 3.918, 3.942, 3.951, 3.983, dan 4.021 site unik. Angka ini sama dengan database dan diagram sebelum perbaikan.
- Persentase diagram membagi jumlah site yang memiliki flagging positif dengan populasi site pada snapshot, dengan mengecualikan status tidak aktif yang tersedia. Ini adalah cakupan flagging pada snapshot, bukan pengukuran akurasi data atau pelunasan semua rekening per site.
- Master `Listrik PLN/Data Master Export.xlsx` menyatakan seluruh 4.042 barisnya Active/active. File ini tidak memberikan riwayat populasi aktif per bulan untuk menghitung tingkat pembayaran historis yang berbeda.
- `Inquiry` adalah tagihan; `Flagging` digunakan sebagai realisasi pembayaran. September 2026 hanya memiliki `Inquiry September 20262` di kolom BW, tanpa Flagging September. Oktober–Desember belum mempunyai kedua kolom tersebut. Tidak adanya kolom pembayaran tidak berarti tidak ada pembayaran.
- Ada nilai kosong dan error Excel seperti `#N/A` pada beberapa kolom sumber. Nilai tersebut tidak dianggap bukti pembayaran positif. Verifikasi status pelunasan tetap memerlukan sumber transaksi/konfirmasi operasional.

Angka diagram referensi kedua tidak sama dengan agregat Flagging 2026 dari workbook ini. Tahun, arti warna, cakupan site, dan sumber populasi per bulan diperlukan sebelum membandingkan persentasenya. Jangan menyalin angka referensi ke database tanpa rekonsiliasi sumber.

## Alur impor dan tampilan

Unggahan website memakai `CentralizedListrikAllImport` untuk memperbarui `listrik_all` saja. Command `dataset:import-centralized-submodules` mengimpor Listrik All bersama submodul Centralized lain, sedangkan `dataset:import-payment-pln-master` mengisi `payment_pln_master_monthly` secara terpisah dari workbook sumber. Kedua tabel tidak saling diganti oleh impor yang berbeda. Import website membaca kolom Flagging (termasuk variasi nama bulan Indonesia/Inggris), mempertahankan rekening per Site ID, dan menolak file tanpa data. Transaksi unggahan menjaga snapshot sebelumnya bila proses gagal.

Tabel Listrik All memakai nilai master pembayaran sesuai pasangan Site ID dan ID Pelanggan bila tersedia. Ini mencegah total pembayaran satu site diulang untuk setiap rekening. Ekspor tetap mengikuti kontrak sebelumnya: agregat per site dari master pembayaran bila tersedia, atau data Listrik All bila master tidak tersedia. Diagram cakupan menampilkan periode tanpa data sebagai tidak tersedia, bukan 0%.

## Performa dashboard dan rincian flagging

Rincian site tanpa flagging mengagregasi snapshot satu periode dan master NOP terlebih dahulu, lalu menggabungkannya per Site ID yang telah dinormalisasi. Hindari mengembalikan pencarian `UPPER(TRIM(site_id))` berkorelasi untuk setiap baris: pola itu memindai snapshot berulang kali dan terasa lambat di shared hosting. Site dengan beberapa rekening tetap tampil sekali; site tidak aktif serta site yang mempunyai flagging positif pada rekening mana pun dalam periode yang sama dikecualikan. Total dihitung sebelum batas tampilan 5.000 site diterapkan.

Ringkasan Listrik All menghitung seluruh bulan dalam satu query agregasi. Grafik pembayaran menggunakan `electricity_payment` dari respons dashboard yang sama, sehingga tidak mengirim request kedua untuk data yang sudah diterima. Label jumlah memakai dua baris di atas batang, menyesuaikan tema, dan grafik dapat digeser horizontal pada layar sempit. Request rincian dibatalkan saat modal ditutup atau bulan berganti; timeout menampilkan kegagalan dan petunjuk mencoba lagi.

Pengukuran lokal 17 Oktober 2026 pada snapshot Agustus 2026: rincian semua NOP sekitar 4.391 ms menjadi 217 ms, dan filter NOP Bekasi sekitar 23.020 ms menjadi 82 ms. Jumlah dan daftar Site ID sebelum/sesudah sama pada data yang diperiksa. Angka ini bukan pengukuran server live; jaringan, beban akun hosting, dan cache database dapat memengaruhi hasil live. Perubahan ini tidak memerlukan migrasi database.

## Perbandingan Site Makro dan Inbuilding

Dashboard Utama dan Electricity Monitoring memakai `ElectricityPaymentComparisonService` serta renderer grafik bersama. Respons API lama tetap tersedia; payload `electricity_comparison` ditambahkan tanpa mengubah skema tabel atau alur impor. Perubahan ini tidak memerlukan migrasi/SQL di cPanel dan tidak mengimpor ulang atau menghapus data operasional.

- Site Makro memakai `payment_pln_master_monthly`, yaitu snapshot kolom Flagging dari workbook dalam `Centralized/Listrik All`. Bila snapshot periode tidak tersedia, nilai Flagging positif pada `listrik_all` dapat menjadi fallback. Inquiry dan daftar transaksi `payment_pln` tidak menggantikan Flagging. Rekening berbeda dijumlahkan nominalnya, sementara Site ID dihitung satu kali. Status tidak aktif yang tersedia pada snapshot dikecualikan.
- Inbuilding memakai `payment_ibc` berstatus Done/Paid/Lunas/Terbayar. Pending tidak termasuk nominal pembayaran. Site ID dinormalisasi dan dihitung sekali; nominal menjumlahkan transaksi terbayar. Penyebut cakupan adalah site pada master Inbuilding aktif saat ini, bukan populasi historis per bulan. Pembayaran di luar master tetap masuk nominal, tetapi dikeluarkan dari persentase dan diberi keterangan di tooltip.
- Filter NOP mempertahankan kontrak yang ada: berlaku untuk Makro. Inbuilding tidak ditampilkan pada perbandingan berfilter NOP karena master Inbuilding belum menyimpan NOP. Grafik menyampaikan batasan ini, bukan menampilkan seluruh Inbuilding seolah sudah terfilter.
- Grafik jumlah site/persentase dan nominal rupiah dipisahkan. Bulan tanpa sumber diberi nilai `null`, sedangkan periode yang ada tetapi belum memiliki pembayaran positif tetap bernilai nol. Garis skala serta batas kategori diperjelas pada grafik batang melalui helper tampilan Highcharts yang sudah ada; grafik pie tidak diberi grid.

Rekonsiliasi sumber 17 Oktober 2026: file Payment IBC Done 2026 mempunyai 116, 116, 116, 108, 103, 70, dan 11 Site ID unik untuk Januari–Juli. Agustus–Desember belum mempunyai file pembayaran. Master yang telah diimpor berisi 186 site aktif (gabungan master ekspor dan tracker). Workbook tracker sendiri memuat 175 site Inbuilding; 131 di antaranya berkategori TAGIHAN LISTRIK EXCLUDE. Karena kategori tagihan itu belum tersimpan dalam master database, grafik tidak mengklaim persentase berbasis 131 site atau menyalin angka 117/115/112/108/69/2 dari gambar contoh. Penyebut 186 ditulis eksplisit pada caption, dan setiap tooltip menampilkan pembilang/penyebut.

Hasil Flagging Makro Januari–Agustus tetap 3.872, 3.882, 3.904, 3.918, 3.942, 3.951, 3.983, dan 4.021 dari 4.023 site. Nilai 4.021/4.023 ditampilkan 99,95%, bukan dibulatkan menjadi klaim 100%. Dataset keuangan dan workbook tetap di luar repository/paket deployment.

## Notifikasi pagi dan sore

- Waktu: pukul 08.00 dan 17.00 WIB setiap hari (`Asia/Jakarta`). Pengaturan: `NOTIFICATION_DAILY_AT`, `NOTIFICATION_EVENING_AT`, dan `NOTIFICATION_TIMEZONE`.
- Kanal website: pengguna dengan akun approved. Kanal email: alamat tujuan alert yang dikonfigurasi.
- Viewer Electricity memiliki lonceng, jumlah belum dibaca, serta halaman notifikasi khusus anomali listrik Centralized PLN dan Inbuilding. Membuka peringatan menandainya sudah dibaca dan mengarah ke tabel anomali listrik. Aksi tandai semua hanya memengaruhi notifikasi listrik milik akun tersebut; notifikasi modul lain tetap tersembunyi dan akses perubahan data tetap diblokir. Notifikasi listrik yang sudah tersimpan langsung tersedia tanpa migrasi atau pengiriman ulang.
- Anomali listrik dan peringatan Infrastruktur menggunakan tujuan email yang sudah ada. Site Loss juga mendapat email lampiran Excel; `SITE_LOSS_ALERT_EMAIL` dapat diisi untuk tujuan khusus, dengan fallback ke email alert Infrastruktur/Electricity.
- Pengiriman dilakukan hanya jika terdapat kondisi peringatan. Setiap topik dan kanal dibatasi satu pengiriman per slot pagi/sore; pengiriman pagi tidak menghalangi sore. Slot yang gagal dikirim dapat dicoba ulang.
- Email Centralized dan Inbuilding melampirkan hanya baris anomali kenaikan tagihan >50% dari seluruh periode yang tersedia, beserta tautan ke tabel anomali sumbernya. Email Infrastruktur dipisah menurut Sewa Lahan, Site TP, Site Telkomsel, dan Combat; tiap lampiran hanya berisi site yang perlu perhatian sesuai kategori dan tautannya membuka filter peringatan. Email Site Loss memakai periode P&L terbaru, mengecualikan data bertanda anomali, dan menyertakan tautan ke periode yang sama.
- Impor sebelum pukul 08.00 tidak mengambil slot pagi. Pemanggilan manual/import sesudah waktu jadwal menggunakan slot terakhir yang sudah jatuh tempo.
- `docker/supervisord.conf` menjalankan scheduler bersama aplikasi dan dipasang melalui Compose supaya konfigurasi tetap tersedia ketika container dibuat ulang. Container/aplikasi harus tetap hidup pada waktu pengiriman.

Pemeriksaan tanpa mengirim notifikasi: `docker compose exec -T laravel.test php artisan schedule:list`. Uji pengiriman menggunakan transport email `array` pada database testing; keberhasilan pengujian tidak membuktikan penerimaan email oleh server SMTP tujuan.
