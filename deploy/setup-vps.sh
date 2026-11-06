#!/usr/bin/env bash
# =====================================================================
# Setup VPS kosong (Ubuntu 24.04 / 26.04) sampai Cheery Chic Online Shop jalan.
#
# Cara pakai (login sebagai root di VPS):
#   curl -fsSL https://raw.githubusercontent.com/eganproject/cheerychic-onlineshop/main/deploy/setup-vps.sh -o setup-vps.sh
#   bash setup-vps.sh
#
# Skrip ini aman dijalankan ulang: langkah yang sudah selesai dilewati.
# Penjelasan tiap langkah ada di docs/SETUP-VPS.md.
# =====================================================================
set -euo pipefail

# ---------- Pengaturan (bisa diubah lewat variabel lingkungan) ----------
DOMAIN="${DOMAIN:-cheery-chic.com}"
APP_ENV_NAME="${APP_ENV_NAME:-staging}"          # staging | production
APP_USER="${APP_USER:-cheerychic}"               # user Linux pemilik aplikasi
APP_DIR="${APP_DIR:-/var/www/cheerychic}"
REPO_URL="${REPO_URL:-https://github.com/eganproject/cheerychic-onlineshop.git}"
BRANCH="${BRANCH:-main}"
DB_NAME="${DB_NAME:-cheerychic}"
DB_USER="${DB_USER:-cheerychic}"
NODE_MAJOR="${NODE_MAJOR:-22}"
CRED_FILE="/root/cheerychic-credentials.txt"

# ---------- Fungsi bantu ----------
step() { printf '\n\033[1;33m==> %s\033[0m\n' "$*"; }
info() { printf '    %s\n' "$*"; }
fail() { printf '\n\033[1;31mGAGAL: %s\033[0m\n' "$*" >&2; exit 1; }
as_app() { sudo -u "$APP_USER" -H bash -c "cd '$APP_DIR' && $*"; }

# Ubah satu baris KUNCI=nilai di .env (tambahkan bila belum ada).
set_env() {
    local key="$1" value="$2" file="$APP_DIR/.env"
    if grep -qE "^#?\s*${key}=" "$file"; then
        # Pakai | sebagai pemisah; nilai tidak boleh berisi |
        sed -i -E "s|^#?\s*${key}=.*|${key}=${value}|" "$file"
    else
        echo "${key}=${value}" >> "$file"
    fi
}

[[ $EUID -eq 0 ]] || fail "Jalankan sebagai root (atau: sudo bash setup-vps.sh)."
. /etc/os-release
[[ "${ID:-}" == "ubuntu" ]] || fail "Skrip ini untuk Ubuntu (terdeteksi: ${PRETTY_NAME:-?})."
export DEBIAN_FRONTEND=noninteractive

echo "Setup Cheery Chic di ${PRETTY_NAME}"
read -rp "Domain toko [${DOMAIN}]: " input </dev/tty || true
DOMAIN="${input:-$DOMAIN}"
read -rp "Email untuk sertifikat SSL (pemberitahuan kedaluwarsa): " SSL_EMAIL </dev/tty || true

# ---------------------------------------------------------------------
step "1/12 Update sistem & alat dasar"
apt-get update -y
apt-get upgrade -y
apt-get install -y ca-certificates curl gnupg git unzip ufw cron
timedatectl set-timezone Asia/Jakarta || true

# ---------------------------------------------------------------------
step "2/12 Swap (memori cadangan supaya build tidak kehabisan RAM)"
if [[ -z "$(swapon --show --noheadings)" ]]; then
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile && swapon /swapfile
    grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
    info "Swap 2 GB dibuat."
else
    info "Swap sudah ada, dilewati."
fi

# ---------------------------------------------------------------------
step "3/12 Firewall (hanya SSH, HTTP, HTTPS yang dibuka)"
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
ufw status

# ---------------------------------------------------------------------
step "4/12 PHP-FPM + ekstensi yang dibutuhkan Laravel"
apt-get install -y php-fpm php-cli php-mysql php-mbstring php-xml php-curl \
    php-zip php-gd php-intl php-bcmath
PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
info "PHP ${PHP_VER} terpasang."
php -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);' \
    || fail "Butuh PHP 8.3 atau lebih baru, yang terpasang ${PHP_VER}."
php -r 'exit(version_compare(PHP_VERSION, "8.6.0", "<") ? 0 : 1);' \
    || fail "PHP ${PHP_VER} belum didukung pustaka impor Excel (maks 8.5)."
PHP_FPM_SERVICE="php${PHP_VER}-fpm"

# ---------------------------------------------------------------------
step "5/12 Composer (pengelola paket PHP)"
if ! command -v composer >/dev/null; then
    EXPECTED="$(curl -fsSL https://composer.github.io/installer.sig)"
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    ACTUAL="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
    [[ "$EXPECTED" == "$ACTUAL" ]] || fail "Installer Composer tidak valid."
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer --quiet
    rm -f /tmp/composer-setup.php
fi
composer --version

# ---------------------------------------------------------------------
step "6/12 Node.js ${NODE_MAJOR} (untuk build CSS/JS)"
if ! command -v node >/dev/null || [[ "$(node -v | cut -d. -f1 | tr -d v)" -lt 20 ]]; then
    curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash -
    apt-get install -y nodejs
fi
node -v && npm -v

# ---------------------------------------------------------------------
step "7/12 MySQL + database aplikasi"
apt-get install -y mysql-server
systemctl enable --now mysql
if [[ -f "$CRED_FILE" ]] && grep -q '^DB_PASSWORD=' "$CRED_FILE"; then
    DB_PASS="$(grep '^DB_PASSWORD=' "$CRED_FILE" | cut -d= -f2-)"
    info "Memakai password database yang tersimpan di $CRED_FILE."
else
    DB_PASS="Cc_$(openssl rand -hex 16)"
    umask 077
    printf 'DB_DATABASE=%s\nDB_USERNAME=%s\nDB_PASSWORD=%s\n' "$DB_NAME" "$DB_USER" "$DB_PASS" > "$CRED_FILE"
    umask 022
    info "Password database dibuat & disimpan di $CRED_FILE (hanya root yang bisa baca)."
fi
mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

# ---------------------------------------------------------------------
step "8/12 Nginx, Supervisor, Certbot"
apt-get install -y nginx supervisor certbot python3-certbot-nginx
systemctl enable --now nginx supervisor

# ---------------------------------------------------------------------
step "9/12 User aplikasi & kode dari GitHub"
if ! id "$APP_USER" >/dev/null 2>&1; then
    useradd --create-home --shell /bin/bash "$APP_USER"
    info "User Linux '$APP_USER' dibuat (tanpa password, masuk lewat: sudo -iu $APP_USER)."
fi
mkdir -p "$(dirname "$APP_DIR")"
if [[ ! -d "$APP_DIR/.git" ]]; then
    install -d -o "$APP_USER" -g "$APP_USER" "$APP_DIR"
    sudo -u "$APP_USER" git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
else
    as_app "git pull --ff-only origin '$BRANCH'"
fi
chmod 755 "$APP_DIR"

# Izinkan user aplikasi me-reload PHP-FPM tanpa password (dipakai deploy/deploy.sh).
echo "${APP_USER} ALL=(root) NOPASSWD: /usr/bin/systemctl reload ${PHP_FPM_SERVICE}" > /etc/sudoers.d/cheerychic
chmod 440 /etc/sudoers.d/cheerychic
visudo -cf /etc/sudoers.d/cheerychic >/dev/null

# ---------------------------------------------------------------------
step "10/12 Konfigurasi .env, dependency, build aset, migrasi"
FIRST_INSTALL=false
if [[ ! -f "$APP_DIR/.env" ]]; then
    FIRST_INSTALL=true
    sudo -u "$APP_USER" cp "$APP_DIR/.env.production.example" "$APP_DIR/.env"
    chmod 640 "$APP_DIR/.env"
    set_env APP_NAME '"Cheery Chic"'
    set_env APP_ENV "$APP_ENV_NAME"
    set_env APP_DEBUG false
    set_env APP_URL "http://${DOMAIN}"
    set_env FORCE_HTTPS false
    set_env SESSION_SECURE_COOKIE false
    set_env CACHE_PREFIX cheerychic_
    set_env DB_DATABASE "$DB_NAME"
    set_env DB_USERNAME "$DB_USER"
    set_env DB_PASSWORD "$DB_PASS"
    # Email belum diatur: catat ke log dulu. Isi MAIL_* bila SMTP sudah ada.
    set_env MAIL_MAILER log
    set_env MAIL_HOST ''
    set_env MAIL_USERNAME ''
    set_env MAIL_PASSWORD ''
    set_env MAIL_FROM_ADDRESS "\"halo@${DOMAIN}\""
    set_env SHOP_ORIGIN_DISTRICT_ID ''
    set_env SHOP_WHATSAPP ''
    set_env SHOP_ADDRESS '""'
    # Midtrans & RajaOngkir diisi sendiri di Admin > Integrasi. Sebelum itu pakai mode contoh.
    set_env PAYMENT_DRIVER fake
    set_env SHIPPING_DRIVER fake
    set_env MIDTRANS_SERVER_KEY ''
    set_env MIDTRANS_CLIENT_KEY ''
    set_env RAJAONGKIR_API_KEY ''
    if [[ "$APP_ENV_NAME" != "production" ]]; then
        set_env SEO_INDEXING false           # staging: jangan diindeks Google
        set_env SECURITY_HSTS false
        set_env MIDTRANS_IS_PRODUCTION false # sandbox
    fi
fi

as_app "composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader"
grep -qE '^APP_KEY=.+' "$APP_DIR/.env" || as_app "php artisan key:generate --force"
as_app "npm ci --no-audit --no-fund && npm run build"
as_app "php artisan migrate --force"

PRODUCT_COUNT="$(mysql -N -e "SELECT COUNT(*) FROM \`${DB_NAME}\`.products" 2>/dev/null || echo 0)"
if [[ "$PRODUCT_COUNT" == "0" ]]; then
    if [[ -f "$APP_DIR/database/seeders/CatalogSeeder.php" ]]; then
        info "Mengisi katalog produk Cheery Chic (tanpa akun/pesanan contoh)."
        as_app "php artisan db:seed --class=CatalogSeeder --force"
    else
        info "CatalogSeeder belum ada di repo, katalog dilewati (produk bisa diimpor dari admin)."
    fi
fi

as_app "php artisan storage:link" 2>/dev/null || true
as_app "php artisan optimize"

# ---------------------------------------------------------------------
step "11/12 PHP-FPM pool, Nginx, SSL"
POOL="/etc/php/${PHP_VER}/fpm/pool.d/cheerychic.conf"
cat > "$POOL" <<EOF
; Pool PHP khusus Cheery Chic: PHP berjalan sebagai user ${APP_USER}.
[cheerychic]
user = ${APP_USER}
group = ${APP_USER}
listen = /run/php/cheerychic.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 4
pm.max_requests = 500
php_admin_value[upload_max_filesize] = 20M
php_admin_value[post_max_size] = 25M
php_admin_value[memory_limit] = 256M
php_admin_flag[expose_php] = off
EOF
systemctl restart "$PHP_FPM_SERVICE"

SITE="/etc/nginx/sites-available/cheerychic"
sed -e "s|server_name .*;|server_name ${DOMAIN} www.${DOMAIN};|" \
    -e "s|^\(\s*\)root .*;|\1root ${APP_DIR}/public;|" \
    -e "s|fastcgi_pass .*;|fastcgi_pass unix:/run/php/cheerychic.sock;|" \
    "$APP_DIR/deploy/nginx.conf.example" > "$SITE"
ln -sf "$SITE" /etc/nginx/sites-enabled/cheerychic
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

# SSL hanya bisa dibuat bila domain sudah mengarah ke IP VPS ini.
SERVER_IP="$(curl -4 -fsS --max-time 10 https://api.ipify.org || hostname -I | awk '{print $1}')"
resolves_here() { getent ahostsv4 "$1" | awk '{print $1}' | sort -u | grep -qx "$SERVER_IP"; }
SSL_OK=false
if [[ -n "$SERVER_IP" ]] && resolves_here "$DOMAIN"; then
    CERT_DOMAINS=(-d "$DOMAIN")
    resolves_here "www.${DOMAIN}" && CERT_DOMAINS+=(-d "www.${DOMAIN}")
    if [[ -n "${SSL_EMAIL:-}" ]]; then MAIL_OPT=(-m "$SSL_EMAIL"); else MAIL_OPT=(--register-unsafely-without-email); fi
    if certbot --nginx --non-interactive --agree-tos --redirect "${MAIL_OPT[@]}" "${CERT_DOMAINS[@]}"; then
        SSL_OK=true
        set_env APP_URL "https://${DOMAIN}"
        set_env FORCE_HTTPS true
        set_env SESSION_SECURE_COOKIE true
        as_app "php artisan optimize"
    fi
else
    info "Domain ${DOMAIN} belum mengarah ke IP ${SERVER_IP:-VPS ini}. SSL dilewati."
fi

# ---------------------------------------------------------------------
step "12/12 Worker antrean (Supervisor) & jadwal (cron)"
cat > /etc/supervisor/conf.d/cheerychic-worker.conf <<EOF
[program:cheerychic-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=${APP_DIR}
user=${APP_USER}
numprocs=1
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stopwaitsecs=3600
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3
EOF
supervisorctl reread && supervisorctl update
supervisorctl restart 'cheerychic-worker:*' || true

# Perintah update satu kata untuk root: update-cheerychic
cat > /usr/local/bin/update-cheerychic <<EOF
#!/usr/bin/env bash
# Ambil kode terbaru dari GitHub lalu build & migrasi (dijalankan sebagai user ${APP_USER}).
set -e
exec sudo -u ${APP_USER} -H bash -c 'cd ${APP_DIR} && bash deploy/deploy.sh'
EOF
chmod 755 /usr/local/bin/update-cheerychic

CRON_LINE="* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
( crontab -u "$APP_USER" -l 2>/dev/null | grep -vF 'artisan schedule:run'; echo "$CRON_LINE" ) | crontab -u "$APP_USER" -

# ---------------------------------------------------------------------
step "Akun Superadmin"
if $FIRST_INSTALL; then
    info "Isi nama, email, dan password untuk login admin (password tidak terlihat saat diketik)."
    as_app "php artisan shop:create-admin" </dev/tty || info "Lewati. Buat nanti: sudo -iu ${APP_USER} php ${APP_DIR}/artisan shop:create-admin"
fi

step "Pemeriksaan akhir"
as_app "php artisan shop:check" || true

PROTO=http; $SSL_OK && PROTO=https
cat <<EOF

=====================================================================
 Selesai. Toko: ${PROTO}://${DOMAIN}    Admin: ${PROTO}://${DOMAIN}/admin
 Folder aplikasi : ${APP_DIR} (user: ${APP_USER})
 Password DB     : ${CRED_FILE}
 Update kode     : update-cheerychic   (jalankan sebagai root)
EOF
if ! $SSL_OK; then
cat <<EOF

 SSL BELUM AKTIF. Arahkan DNS ${DOMAIN} dan www.${DOMAIN} (record A) ke ${SERVER_IP:-IP VPS},
 tunggu beberapa menit, lalu jalankan ulang: bash setup-vps.sh
EOF
fi
echo "====================================================================="
