# Setup VPS dari nol untuk Cheery Chic

Panduan ini menjelaskan isi skrip `deploy/setup-vps.sh`: apa yang dipasang, kenapa, dan di mana letaknya. Skrip ditulis untuk VPS Ubuntu **kosong** (24.04 atau 26.04) dan aman dijalankan ulang.

## Cara menjalankan

1. Arahkan domain dulu (di panel tempat domain dibeli), supaya SSL bisa langsung aktif:

   | Tipe | Nama | Nilai |
   |---|---|---|
   | A | `@` | IP VPS |
   | A | `www` | IP VPS |

2. Masuk ke VPS sebagai root, lalu jalankan:

   ```bash
   ssh root@IP-VPS
   curl -fsSL https://raw.githubusercontent.com/eganproject/cheerychic-onlineshop/main/deploy/setup-vps.sh -o setup-vps.sh
   bash setup-vps.sh
   ```

3. Skrip menanyakan domain (Enter = `cheery-chic.com`) dan email untuk SSL. Di akhir, skrip meminta nama, email, dan password akun Superadmin.

Lama prosesnya sekitar 10–20 menit. Bila DNS belum mengarah ke VPS, situs tetap jalan lewat `http://`, dan cukup jalankan `bash setup-vps.sh` lagi setelah DNS aktif untuk memasang SSL.

## Yang dipasang dan fungsinya

| # | Langkah | Paket / file | Fungsi |
|---|---|---|---|
| 1 | Update sistem | `apt upgrade`, `git`, `curl`, `unzip`, `ufw`, `cron` | Patch keamanan terbaru dan alat dasar. Zona waktu diset `Asia/Jakarta`. |
| 2 | Swap 2 GB | `/swapfile` | Memori cadangan di disk. VPS RAM kecil sering kehabisan memori saat `npm run build`. |
| 3 | Firewall | `ufw` | Hanya port 22 (SSH), 80 (HTTP), 443 (HTTPS) yang terbuka. MySQL tidak bisa diakses dari luar. |
| 4 | PHP | `php-fpm`, `php-cli` + ekstensi `mysql mbstring xml curl zip gd intl bcmath` | Menjalankan Laravel. FPM = proses PHP yang melayani web; CLI = perintah `php artisan`. Versi PHP bawaan Ubuntu dipakai (harus 8.3–8.5). |
| 5 | Composer | `/usr/local/bin/composer` | Mengunduh pustaka PHP (folder `vendor/`). Installer dicek tanda tangannya. |
| 6 | Node.js 22 | repo NodeSource | Hanya untuk build CSS/JS (Tailwind, Vite) ke `public/build`. |
| 7 | MySQL | `mysql-server` | Database `cheerychic`, user `cheerychic@localhost` dengan password acak. Password tersimpan di `/root/cheerychic-credentials.txt` (hanya root yang bisa baca) dan di `.env`. |
| 8 | Nginx, Supervisor, Certbot | `nginx`, `supervisor`, `certbot` | Nginx = web server. Supervisor = menjaga worker antrean tetap hidup. Certbot = SSL gratis Let's Encrypt, diperpanjang otomatis. |
| 9 | User & kode | user Linux `cheerychic`, folder `/var/www/cheerychic` | Aplikasi tidak berjalan sebagai root. Kode diambil dari GitHub (branch `main`). |
| 10 | Aplikasi | `.env`, `composer install`, `npm run build`, `migrate`, seeder katalog | Lihat bagian `.env` di bawah. Database diisi produk Cheery Chic saja, tanpa akun/pesanan contoh. |
| 11 | Web | `/etc/php/<versi>/fpm/pool.d/cheerychic.conf`, `/etc/nginx/sites-available/cheerychic` | Pool PHP khusus (user `cheerychic`, upload maks 20 MB, memori 256 MB). Nginx meneruskan request ke pool itu. SSL dipasang bila DNS sudah benar, HTTP otomatis dialihkan ke HTTPS. |
| 12 | Latar belakang | `/etc/supervisor/conf.d/cheerychic-worker.conf`, crontab user `cheerychic` | Worker antrean (email, impor produk besar). Cron tiap menit menjalankan jadwal Laravel: backup harian, kedaluwarsa pembayaran, selesai otomatis pesanan, lacak resi. |

Terakhir skrip membuat akun Superadmin (`php artisan shop:create-admin`) dan menjalankan `php artisan shop:check`.

## Isi `.env` staging

File: `/var/www/cheerychic/.env`

| Kunci | Nilai | Alasan |
|---|---|---|
| `APP_ENV` | `staging` | Bukan production dulu. |
| `APP_URL`, `FORCE_HTTPS`, `SESSION_SECURE_COOKIE` | `https://...`, `true`, `true` | Diaktifkan otomatis setelah SSL terpasang. |
| `SEO_INDEXING` | `false` | Situs staging tidak muncul di Google. |
| `SECURITY_HSTS` | `false` | Aktifkan saat production. |
| `PAYMENT_DRIVER`, `SHIPPING_DRIVER` | `fake` | Mode contoh sampai kunci Midtrans & RajaOngkir diisi di **Admin › Integrasi**. |
| `MIDTRANS_IS_PRODUCTION` | `false` | Sandbox, tidak ada uang sungguhan. |
| `MAIL_MAILER` | `log` | Email dicatat ke `storage/logs`, belum terkirim. Isi `MAIL_*` dengan SMTP bila sudah punya. |

Setelah mengubah `.env`, jalankan:

```bash
sudo -iu cheerychic
cd /var/www/cheerychic && php artisan optimize
```

## Perintah sehari-hari

Update toko dari GitHub cukup satu perintah sebagai root (tidak perlu pindah user):

```bash
update-cheerychic
```

Perintah ini menjalankan `deploy/deploy.sh` sebagai user `cheerychic`: mode perawatan, `git pull`, composer, build aset, migrasi, cache, restart worker. Lainnya:

```bash
# Masuk sebagai user aplikasi
sudo -iu cheerychic
cd /var/www/cheerychic

# Lihat log error
tail -f storage/logs/laravel-$(date +%F).log

# Cek konfigurasi
php artisan shop:check
```

Sebagai root:

```bash
supervisorctl status                 # status worker antrean
systemctl status nginx mysql         # status layanan
certbot renew --dry-run              # uji perpanjangan SSL
```

## Pindah ke production nanti

Ubah di `.env`: `APP_ENV=production`, `SEO_INDEXING=true`, `SECURITY_HSTS=true`, `MIDTRANS_IS_PRODUCTION=true`, isi SMTP, lalu `php artisan optimize`.
