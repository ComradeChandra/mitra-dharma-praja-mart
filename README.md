# Mitra Dharma Praja Mart

Aplikasi pemesanan (pre-order) kebutuhan harian untuk **Koperasi Mitra Dharma Praja**.
Anggota dan non-anggota memilih barang dan jumlahnya dalam satu periode pemesanan;
koperasi berbelanja setelah semua pesanan terkumpul, lalu mengirim invoice lewat
WhatsApp dan menerima pembayaran lewat QRIS.

*Kebersamaan untuk Kesejahteraan.*

Dibuat oleh **Chandra Harkat Raharja** (PKL di UPTD Cimahi Technopark, 2026).
Hak cipta dan ketentuan pemakaian ada di berkas [LICENSE](LICENSE).

## Fitur

- **Empat tingkatan pengguna** — Admin Utama dan Pengurus (email + password,
  tiap staf punya akun sendiri), anggota (kode anggota + password dari
  pengurus), dan non-anggota (pilih OPD + kode akses OPD). Admin Utama
  mengelola akun pengurus dan pengaturan; tabel "siapa bisa apa" ada di
  Admin → Akun Pengurus.
- **Lupa password** anggota masuk ke antrean yang bisa ditangani pengurus mana
  pun; password baru dikirim lewat WhatsApp ke nomor terdaftar.
- **Bantuan & FAQ** yang bisa dibuka tanpa masuk, plus Profil Saya anggota yang
  merangkum pesanan dan belanja tahun berjalan.
- **Katalog & pemesanan** per periode, dengan konfirmasi sebelum kirim.
- **Harga fluktuatif** (telur, sayur) dikunci pengurus saat verifikasi, untuk
  satu pesanan atau sekaligus semua pesanan di periode yang sama.
- **Pembatalan pesanan** oleh pemesan (selama periode dibuka dan belum bayar)
  atau oleh pengurus; pesanan batal tetap tercatat tapi tidak dihitung di rekap.
  Pengurus juga bisa menghapus satu barang yang tidak bisa dipenuhi, dengan
  catatan yang terlihat oleh pemesan.
- **Invoice WhatsApp** berupa teks + tautan pembayaran, dikirim lewat wa.me.
- **Tombol "Hubungi Pengurus"** lewat WhatsApp untuk anggota, non-anggota, dan
  tamu, dengan salam pembuka yang sudah menyebut pengirimnya. Nomornya diatur
  pengurus sendiri di Admin → Pengaturan.
- **Pembayaran QRIS** statis, pemesan melampirkan bukti, pengurus mencocokkan.
- **Struk resmi** yang bisa dicetak atau disimpan sebagai PDF.
- **Rekap** per produk (belanja grosir), per OPD, dan per anggota.
- **Usulan produk** dari anggota & non-anggota, disetujui atau ditolak pengurus.
- **Perkiraan SHU** dari akumulasi belanja tahunan anggota.

## Kebutuhan server

| | Versi |
|---|---|
| PHP | 8.3 atau lebih baru, dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `xml`, `ctype`, `curl` |
| Database | MySQL 8 atau MariaDB 10.6+ |
| Composer | 2.x |
| Node.js | 20+ (**cuma untuk membangun CSS/JS**, tidak perlu ada di server) |

Tidak butuh antrean (queue worker), cron, maupun layanan email.

## Menjalankan di laptop

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed      # termasuk data contoh untuk dicoba
npm run dev
```

Akun contoh (hanya di laptop): pengurus `admin@mitradharma.test` / `admin12345`,
anggota `0001 A` / `anggota123`, non-anggota OPD *Sekretariat Daerah* / `opd12345`.

## Memasang di hosting

Data contoh **tidak** ikut terpasang di server: `db:seed` di server cuma membuat
akun pengurus pertama dari `.env`. Akun itu berperan **Admin Utama**; akun staf
lain ditambahkan dari aplikasi (Admin → Akun Pengurus), bukan lewat seeder.

1. **Bangun CSS/JS di laptop**, karena kebanyakan hosting tidak punya Node.js:

   ```bash
   npm ci
   npm run build            # menghasilkan folder public/build
   ```

2. **Unggah kode ke server**, termasuk folder `public/build`. Tidak perlu
   mengunggah `node_modules`, `.env`, maupun `tests`.

3. **Pasang dependensi PHP** di server:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

4. **Arahkan document root domain ke folder `public/`**, bukan ke folder utama
   proyek. Kalau document root mengarah ke folder utama, isi `.env` bisa dibuka
   orang lewat browser.

5. **Siapkan `.env`**: salin `.env.example`, lalu ubah semua baris bertanda
   `[SERVER]`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`,
   `LOG_LEVEL=error`, `SESSION_SECURE_COOKIE=true` (kalau sudah https), data
   database, serta `ADMIN_EMAIL` dan `ADMIN_PASSWORD`. Lalu buat kunci aplikasi
   **sekali saja**:

   ```bash
   php artisan key:generate
   ```

6. **Siapkan database, akun pengurus, dan tautan penyimpanan**:

   ```bash
   php artisan migrate --force
   php artisan db:seed --force      # akun pengurus dari ADMIN_EMAIL/ADMIN_PASSWORD
   php artisan storage:link         # supaya foto produk & profil bisa tampil
   ```

7. **Percepat aplikasi**:

   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

8. Pastikan folder `storage/` dan `bootstrap/cache/` bisa ditulisi oleh server web.

9. **Masuk sebagai pengurus**, lalu isi data asli lewat halaman admin: OPD
   (beserta kode aksesnya), anggota, produk, dan buka periode pemesanan pertama.

### Hal yang wajib diingat

- **Jangan pernah menjalankan `php artisan key:generate` lagi** setelah aplikasi
  dipakai. Semua tautan pembayaran di invoice WhatsApp yang sudah terkirim akan
  tidak berlaku, dan semua orang ter-logout.
- **Hosting yang memakai proxy/CDN** (Cloudflare, Render, Railway, dll): isi
  `TRUSTED_PROXIES=*` di `.env`. Tanda-tandanya kalau lupa: halaman tampil tanpa
  gaya (CSS diblokir browser).
- **Hosting yang melarang symlink** (sebagian shared hosting): kalau
  `storage:link` gagal, tanyakan ke penyedia hosting cara membuat tautan dari
  `public/storage` ke `storage/app/public`.
- **Cadangkan dua hal secara berkala**: database dan folder `storage/app/`.
  Bukti transfer tersimpan di `storage/app/private`, foto produk & profil di
  `storage/app/public`.
- Gambar QRIS dan logo ada di `public/images/`. Untuk menggantinya, cukup timpa
  berkasnya (`qris-koperasi.jpg`, `logo-koperasi.png`).

### Memperbarui aplikasi di server

```bash
# setelah kode terbaru (termasuk public/build hasil npm run build) diunggah:
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## Menjalankan tes

```bash
php artisan test          # memakai SQLite di memori
npm run test:js           # logika form pemesanan
```

Tes juga harus lulus di MySQL, karena SQLite tidak menolak teks kepanjangan atau
angka kebesaran seperti MySQL. Pakai **database terpisah**, jangan database utama:

```bash
# sekali: CREATE DATABASE mitra_dharma_praja_mart_uji;
DB_CONNECTION=mysql DB_DATABASE=mitra_dharma_praja_mart_uji php artisan test
```

## Teknologi

Laravel 13, Blade, Tailwind CSS, Alpine.js, MySQL.
