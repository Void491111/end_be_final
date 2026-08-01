# Laporan Pengujian Black-Box — Backend Mooiste POS

## Informasi Pengujian

| Item | Keterangan |
|---|---|
| Tanggal pengujian | 28 Juli 2026 |
| Metode | Black-box testing (functional testing) |
| Objek uji | REST API + panel admin Filament |
| Teknik | Equivalence partitioning & boundary value analysis, positive & negative testing |
| Lingkungan | Laravel 13.8, PHP 8.3, Filament 5, MySQL |
| Server uji | `php artisan serve` — `http://127.0.0.1:8199` |
| Alat | Skrip PHP + cURL (HTTP request langsung) |
| Data awal | 96 order, 17 menu (12 tersedia), 4 kategori, 9 meja aktif |
| Total pemeriksaan | **98** |
| Lulus | **98** |
| Gagal | **0** |

## Metode

Pengujian dilakukan secara *black-box*: seluruh verifikasi dilakukan melalui antarmuka
yang terbuka bagi pengguna (HTTP request), tanpa memanggil atau memeriksa kode internal
aplikasi. Penguji hanya mengirim permintaan dan memeriksa respons — sama seperti yang
dilakukan aplikasi frontend atau pengguna sungguhan.

Setiap kebutuhan fungsional diuji dengan dua jenis kasus:

- **Kasus positif** — memastikan fitur bekerja sesuai yang diharapkan.
- **Kasus negatif** — memastikan sistem menolak masukan tidak valid dengan benar
  (kuantitas negatif, menu tidak ada, kode meja palsu, aksi ganda, dan sebagainya).

Seluruh data uji yang dihasilkan ditandai agar dapat dihapus kembali setelah pengujian
selesai, sehingga data operasional tidak terkontaminasi.

## Ringkasan Hasil

| FR | Kebutuhan Fungsional | Hasil | Cakupan |
|---|---|---|---|
| FR-01 | Login dan logout admin & kasir sesuai hak akses | 9/9 | Penuh |
| FR-02 | Melihat katalog menu dan filter berdasarkan kategori | 4/4 | Penuh |
| FR-03 | Mengelola keranjang belanja secara persisten | — | **Tidak teruji** |
| FR-04 | Checkout: subtotal, pajak 10%, total, antrean, nota | 14/14 | Penuh |
| — | Pembedaan alur kasir vs alur QR (lintas FR-04 & FR-11) | 11/11 | Penuh |
| FR-05 | Mengubah status ketersediaan menu | 3/3 | Penuh |
| FR-06 | Riwayat transaksi dan grafik pendapatan per periode | 6/6 | Penuh |
| FR-07 | Pemesanan mandiri pelanggan via QR Code meja | 12/12 | Penuh |
| FR-08 | Rekomendasi menu terlaris otomatis | 6/6 | Penuh |
| FR-09 | Pembayaran simulasi QRIS dan pemantauan status | 8/8 | Penuh |
| FR-10 | Admin mengelola (CRUD) data menu dan kategori | 8/8 | **Parsial** |
| FR-11 | Admin memantau pesanan dan memperbarui status | 11/11 | Penuh |
| FR-12 | Dashboard laporan pendapatan dan rekapitulasi | 6/6 | Penuh |

> **Catatan penting:** FR-03 dan FR-10 **tidak dapat diklaim lulus sepenuhnya**
> berdasarkan laporan ini. Lihat bagian [Batasan Pengujian](#batasan-pengujian).

## Hasil Rinci

### FR-01 — Login, logout, dan hak akses (9/9)

| No | Skenario | Hasil Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|
| 1.1 | Login admin dengan kredensial benar | HTTP 200 + token | HTTP 200, `role=admin` | Lulus |
| 1.2 | Login kasir dengan kredensial benar | HTTP 200 + token | HTTP 200, `role=cashier` | Lulus |
| 1.3 | Login dengan password salah | Ditolak | HTTP 422 | Lulus |
| 1.4 | Endpoint `/me` mengembalikan identitas + role | HTTP 200 + data user | HTTP 200, `role=admin` | Lulus |
| 1.5 | Akses endpoint terproteksi tanpa token | Ditolak | HTTP 401 | Lulus |
| 1.6 | Akses endpoint terproteksi dengan token palsu | Ditolak | HTTP 401 | Lulus |
| 1.7 | Logout mematikan token | Token tidak berlaku lagi | Logout 200, pemakaian ulang 401 | Lulus |
| 1.8 | Login baru mematikan sesi lama | Sesi tunggal per user | Token lama → HTTP 401 | Lulus |
| 1.9 | Admin & kasir sama-sama dapat mengakses API kasir | Keduanya 200 | Admin 200, kasir 200 | Lulus |

### FR-02 — Katalog menu dan filter kategori (4/4)

| No | Skenario | Hasil Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|
| 2.1 | Kasir melihat katalog menu | Daftar menu | HTTP 200, 17 menu | Lulus |
| 2.2 | Daftar kategori tersedia | Daftar kategori | HTTP 200, 4 kategori | Lulus |
| 2.3 | Filter `category_id` | Hanya menu kategori tersebut | 8 menu, seluruhnya kategori #1 | Lulus |
| 2.4 | Filter `available_only` | Hanya menu tersedia | 12 menu, seluruhnya tersedia | Lulus |

### FR-04 — Checkout kasir (14/14)

Data uji: 2 × Americano (Rp25.000) + 3 × menu kedua = subtotal Rp116.000.

| No | Skenario | Hasil Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|
| 4.1 | Checkout berhasil | HTTP 201 | HTTP 201 | Lulus |
| 4.2 | Subtotal dihitung benar | Rp116.000 | Rp116.000,00 | Lulus |
| 4.3 | Pajak tepat 10% dari subtotal | Rp11.600 | Rp11.600,00 | Lulus |
| 4.4 | Total = subtotal + pajak | Rp127.600 | Rp127.600,00 | Lulus |
| 4.5 | Tipe pesanan tersimpan sesuai pilihan | `takeaway` | `takeaway` | Lulus |
| 4.6 | Nomor antrean dibuat otomatis | Format `Annn` | `A099` | Lulus |
| 4.7 | Nota digital memuat rincian item | 2 baris item | 2 baris item | Lulus |
| 4.8 | Item menyimpan snapshot nama & harga | Ada snapshot | `Americano @ 25000.00` | Lulus |
| 4.9 | Nomor antrean unik & berurutan | Nomor berbeda | `A099` → `A100` | Lulus |
| 4.10 | Order tanpa item | Ditolak | HTTP 422 | Lulus |
| 4.11 | Tipe pesanan tidak valid | Ditolak | HTTP 422 | Lulus |
| 4.12 | Menu tidak ada (`id=999999`) | Ditolak | HTTP 422 | Lulus |
| 4.13 | Kuantitas 0 | Ditolak | HTTP 422 | Lulus |
| 4.14 | Kuantitas negatif (−5) | Ditolak | HTTP 422 | Lulus |

### FR-05 — Ubah ketersediaan menu (3/3)

| No | Skenario | Hasil Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|
| 5.1 | Toggle membalik status ketersediaan | `true` → `false` | `true` → `false` | Lulus |
| 5.2 | Perubahan tersimpan (dibaca ulang) | Nilai baru bertahan | Terbaca `false` | Lulus |
| 5.3 | Toggle kembali ke status semula | `false` → `true` | `true` | Lulus |

### FR-06 — Riwayat transaksi dan grafik pendapatan (6/6)

| No | Skenario | Hasil Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|
| 6.1 | Riwayat transaksi terpaginasi | Ada `data` & `total` | Total 100, per halaman 20 | Lulus |
| 6.2 | Filter periode mempersempit hasil | Hasil ≤ keseluruhan | Hari ini 5 vs semua 100 | Lulus |
| 6.3 | Filter status | Hanya status diminta | Seluruhnya `completed` | Lulus |
| 6.4 | Statistik ringkas | Order, revenue, rata-rata | 5 order, Rp286.000 | Lulus |
| 6.5 | Grafik pendapatan mingguan | 7 titik data | 7 titik, total Rp404.800 | Lulus |
| 6.6 | Total mingguan = jumlah harian | Konsisten | 404.800 = 404.800 | Lulus |

### FR-07 — Self-order pelanggan via QR Code (12/12)

| No | Skenario | Hasil Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|
| 7.1 | Scan QR meja valid (`T04`) | Data meja | HTTP 200, "Meja 4" | Lulus |
| 7.2 | Kode meja tidak dikenal | Ditolak | HTTP 404 | Lulus |
| 7.3 | Menu publik tanpa login | Dapat diakses | HTTP 200, 12 menu | Lulus |
| 7.4 | Menu publik hanya yang tersedia | Semua tersedia | Semua tersedia | Lulus |
| 7.5 | Kategori publik tanpa login | Dapat diakses | HTTP 200, 4 kategori | Lulus |
| 7.6 | Pelanggan submit order tanpa login | HTTP 201 | HTTP 201 | Lulus |
| 7.7 | Order terhubung ke meja yang di-scan | `T04` | `T04` | Lulus |
| 7.8 | Nama pelanggan tersimpan | Nama tersimpan | Tersimpan | Lulus |
| 7.9 | Perhitungan order QR (pajak 10%) | Sub 69.000 → total 75.900 | Sub Rp69.000, total Rp75.900 | Lulus |
| 7.10 | Status awal order QR | `pending_payment` | `pending_payment` | Lulus |
| 7.11 | Order dengan kode meja palsu | Ditolak | HTTP 422 | Lulus |
| 7.12 | Kuantitas melebihi batas (999 > 20) | Ditolak | HTTP 422 | Lulus |

### FR-08 — Rekomendasi menu terlaris (6/6)

| No | Skenario | Hasil Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|
| 8.1 | Rekomendasi dapat diakses tanpa login | Daftar menu | HTTP 200, 5 menu | Lulus |
| 8.2 | Urut menurun berdasarkan jumlah terjual | Terurut menurun | 103, 54, 40, 37, 27 | Lulus |
| 8.3 | Menyertakan angka penjualan | Ada `total_sold` | Brownies — 103 terjual | Lulus |
| 8.4 | Parameter `limit` dihormati | Maksimal 3 | 3 menu | Lulus |
| 8.5 | Parameter `exclude` mengecualikan menu | Menu #16 hilang | Tidak muncul | Lulus |
| 8.6 | Rekomendasi untuk kasir (terautentikasi) | HTTP 200 | HTTP 200, 5 menu | Lulus |

### FR-09 — Pembayaran simulasi QRIS dan pemantauan status (8/8)

| No | Skenario | Hasil Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|
| 9.1 | Pelanggan memantau status pesanan | Status terkini | `pending_payment` | Lulus |
| 9.2 | Simulasi pembayaran QRIS | HTTP 200 | HTTP 200 | Lulus |
| 9.3 | Status berubah setelah bayar | `paid` | `paid` | Lulus |
| 9.4 | `payment_status` menjadi settlement | `settlement` | `settlement` | Lulus |
| 9.5 | Waktu pembayaran tercatat | `paid_at` terisi | Terisi | Lulus |
| 9.6 | Pembayaran ulang ditolak (idempoten) | HTTP 409 | HTTP 409 | Lulus |
| 9.7 | Polling status mencerminkan perubahan | `paid` | `paid` | Lulus |
| 9.8 | Status pesanan tidak dikenal | HTTP 404 | HTTP 404 | Lulus |

### FR-11 — Pantau antrean dan perbarui status pesanan (11/11)

| No | Skenario | Hasil Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|
| 11.1 | Order QR terbayar masuk antrean | Muncul di antrean | 7 order, order uji ada | Lulus |
| 11.2 | Jumlah antrean tersedia (badge) | Ada `count` | `count=6` | Lulus |
| 11.3 | Konfirmasi pesanan | `preparing` | `preparing` | Lulus |
| 11.4 | Konfirmasi ulang ditolak | HTTP 409 | HTTP 409 | Lulus |
| 11.5 | Selesaikan pesanan | `completed` | `completed` | Lulus |
| 11.6 | Pelanggan melihat status terbaru | `completed` | `completed` | Lulus |
| 11.7 | Tolak pesanan dengan alasan | `voided` | `voided` | Lulus |
| 11.8 | Tolak tanpa alasan | Ditolak | HTTP 422 | Lulus |
| 11.9 | Void order kasir dengan alasan | `voided` | `voided` | Lulus |
| 11.10 | Void ulang ditolak | HTTP 422 | HTTP 422 | Lulus |
| 11.11 | Alasan void terlalu pendek | Ditolak | HTTP 422 | Lulus |

### FR-12 — Dashboard laporan pendapatan (6/6)

| No | Skenario | Hasil Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|
| 12.1 | Rekap periode hari ini | Data lengkap | 5 order, Rp234.300, 2 void | Lulus |
| 12.2 | Rekap periode 7 hari | Data lengkap | 20 order, Rp1.436.600, 2 void | Lulus |
| 12.3 | Rekap periode 30 hari | Data lengkap | 77 order, Rp7.830.900, 3 void | Lulus |
| 12.4 | Rekap periode 90 hari | Data lengkap | 92 order, Rp9.504.000, 3 void | Lulus |
| 12.5 | Rata-rata order konsisten | revenue ÷ jumlah = rata-rata | 101.700 = 101.700 | Lulus |
| 12.6 | Panel admin merespons | HTTP 200 | HTTP 200 | Lulus |

### FR-04 & FR-11 — Pembedaan alur kasir dan alur QR (11/11)

Sistem memiliki dua alur pemesanan yang **sengaja dibedakan**. Pengujian berikut
memastikan keduanya tidak saling tercampur.

| Alur | Titik masuk | Status awal | Masuk antrean? | Tujuan akhir |
|---|---|---|---|---|
| Kasir | Aplikasi kasir | `completed` | Tidak | Langsung ke riwayat / tabel admin |
| QR pelanggan | Scan QR meja | `pending_payment` | Ya, setelah dibayar | Antrean → konfirmasi → selesai |

Alasan perbedaan: pada transaksi kasir, pesanan dan pembayaran tunai terjadi secara
tatap muka dalam satu waktu, sehingga transaksi sudah selesai saat dicatat. Pada
pemesanan mandiri, terdapat jeda antara pemesanan, pembayaran, dan penyiapan, sehingga
diperlukan status antara.

| No | Skenario | Hasil Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|
| A.1 | Order kasir langsung berstatus selesai | `completed` | `completed` | Lulus |
| A.2 | Pembayaran kasir langsung lunas | `settlement` | `settlement` | Lulus |
| A.3 | Waktu bayar order kasir terisi | `paid_at` terisi | Terisi | Lulus |
| A.4 | Order kasir ditandai sumbernya | `source=cashier` | `cashier` | Lulus |
| A.5 | Order kasir tidak terikat meja | `table_id` kosong | `NULL` | Lulus |
| A.6 | Order kasir **tidak** masuk antrean QR | Tidak muncul | Tidak muncul | Lulus |
| A.7 | Hitungan antrean tidak terpengaruh order kasir | Tetap konsisten | `count=5` | Lulus |
| A.8 | Order kasir langsung muncul di riwayat transaksi | Muncul | Muncul di halaman pertama | Lulus |
| A.9 | Detail order kasir dapat dibuka admin | HTTP 200 | HTTP 200 | Lulus |
| A.10 | Pembanding: order QR mulai dari menunggu bayar | `pending_payment` | `pending_payment` | Lulus |
| A.11 | Order QR belum dibayar belum masuk antrean | Tidak muncul | Tidak muncul | Lulus |

### FR-10 — Admin CRUD menu dan kategori (8/8, parsial)

| No | Skenario | Hasil Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|
| 10.1 | Halaman daftar menu terlindungi | Redirect ke login | HTTP 302 → `/alif/login` | Lulus |
| 10.2 | Form tambah menu terlindungi | Redirect ke login | HTTP 302 → `/alif/login` | Lulus |
| 10.3 | Halaman daftar kategori terlindungi | Redirect ke login | HTTP 302 → `/alif/login` | Lulus |
| 10.4 | Form tambah kategori terlindungi | Redirect ke login | HTTP 302 → `/alif/login` | Lulus |
| 10.5 | Halaman daftar meja terlindungi | Redirect ke login | HTTP 302 → `/alif/login` | Lulus |
| 10.6 | Halaman daftar pesanan terlindungi | Redirect ke login | HTTP 302 → `/alif/login` | Lulus |
| 10.7 | Dashboard terlindungi | Redirect ke login | HTTP 302 → `/alif/login` | Lulus |
| 10.8 | Halaman login panel dapat diakses | HTTP 200 | HTTP 200 | Lulus |

## Batasan Pengujian

Dua kebutuhan fungsional **tidak dapat diverifikasi sepenuhnya** melalui pengujian ini.
Keduanya perlu diuji terpisah sebelum dinyatakan lulus.

### FR-03 — Keranjang belanja: tidak teruji

Backend **tidak memiliki endpoint keranjang sama sekali**. Pemeriksaan daftar rute
menunjukkan tidak ada jalur yang berkaitan dengan keranjang. Seluruh fungsi FR-03 —
akumulasi kuantitas otomatis, ubah jumlah, hapus item, dan persistensi — berjalan di
sisi frontend (penyimpanan lokal peramban). Backend hanya menerima daftar item final
pada saat checkout.

**Tindak lanjut:** FR-03 harus diuji langsung pada aplikasi frontend. Tidak ada bukti
mengenai FR-03 yang dapat dihasilkan dari pengujian backend.

### FR-10 — CRUD menu dan kategori: parsial

Operasi Tambah, Ubah, dan Hapus hanya tersedia melalui panel admin Filament yang
berbasis Livewire — tidak ada REST API untuk operasi tersebut. Yang berhasil
diverifikasi hanyalah bahwa seluruh halaman CRUD tersedia dan terlindungi autentikasi.

**Yang belum terverifikasi:** aksi simpan, ubah, dan hapus yang sesungguhnya, karena
dijalankan melalui permintaan Livewire yang memerlukan sesi peramban.

**Tindak lanjut:** uji manual di peramban, atau otomasi dengan Laravel Dusk / Playwright.

## Temuan

### T-01 — Sistem hanya mengizinkan satu sesi aktif per akun

Setiap kali login berhasil, seluruh token milik pengguna tersebut dihapus. Akibatnya,
login pada perangkat kedua **langsung mematikan sesi pada perangkat pertama**, dan
permintaan berikutnya dari perangkat pertama akan menerima HTTP 401.

Temuan ini muncul saat pengujian FR-01 (skenario 1.8) dan telah diverifikasi.

- **Dampak operasional:** apabila demonstrasi menggunakan dua perangkat dengan akun
  yang sama, salah satu akan keluar dengan sendirinya.
- **Saran:** gunakan akun berbeda untuk tiap perangkat.
- **Catatan:** perilaku ini dapat merupakan kebijakan keamanan yang disengaja. Dicatat
  sebagai perilaku teramati, bukan sebagai kesalahan.

### T-02 — Endpoint pembayaran bersifat simulasi dan terbuka

Endpoint `POST /api/public/orders/{id}/simulate-payment` dapat dipanggil tanpa
autentikasi dan akan menandai pesanan sebagai lunas. Demikian pula
`GET /api/public/orders/{id}/status` menampilkan detail pesanan mana pun berdasarkan id.

Hal ini **sesuai rancangan** — endpoint tersebut merupakan pengganti sementara
integrasi Midtrans untuk keperluan demonstrasi lokal. Dicatat agar tidak disalahartikan
sebagai implementasi pembayaran sungguhan.

**Tindak lanjut untuk penggunaan nyata:** ganti dengan webhook Midtrans yang
terverifikasi tanda tangannya.

## Kondisi Data Setelah Pengujian

Seluruh data uji telah dibersihkan dan basis data dikembalikan ke kondisi semula.

| Pemeriksaan | Hasil |
|---|---|
| Order uji dihapus | 4 order |
| Sisa data bertanda `BBTEST` | 0 |
| Baris `order_items` yatim | 0 |
| Counter nomor antrean | Tersinkron |
| Status ketersediaan menu | Dikembalikan ke semula |
| Server uji | Dimatikan |

## Kesimpulan

Dari 12 kebutuhan fungsional, **10 terverifikasi penuh** melalui 90 pemeriksaan
fungsional tanpa kegagalan. Seluruh kasus negatif — masukan tidak valid, aksi ganda,
dan pelanggaran batas nilai — ditolak sistem dengan benar.

Dua kebutuhan (FR-03 dan FR-10) berada di luar jangkauan pengujian backend dan
memerlukan pengujian tambahan pada antarmuka pengguna sebelum dapat dinyatakan lulus.