#!/bin/bash

# =============================================================================
# Deploy Script - Laravel LMS
# =============================================================================
# Gunakan script ini untuk deploy update di VPS
# Usage: bash deploy.sh

# Pertama kali, beri permission executable
# chmod +x deploy.sh

# Setiap ada update, cukup jalankan:
# ./deploy.sh
# =============================================================================

set -e

# =============================================================================
# Self-healing: Jika script dijalankan tanpa permission execute, auto-fix
# =============================================================================
SCRIPT_PATH="$(realpath "$0")"
if [ ! -x "$SCRIPT_PATH" ]; then
    echo "[FIX] deploy.sh tidak memiliki permission execute. Memperbaiki..."
    chmod +x "$SCRIPT_PATH"
    echo "[FIX] Permission diperbaiki. Menjalankan ulang..."
    exec bash "$SCRIPT_PATH" "$@"
fi

# Pastikan git selalu simpan permission execute
git config core.fileMode true 2>/dev/null || true

# Warna untuk output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Fungsi helper
info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

step() {
    echo -e "${CYAN}[STEP]${NC} $1"
}

# =============================================================================
# Konfigurasi - Sesuaikan dengan VPS kamu
# =============================================================================
APP_DIR=$(dirname "$(realpath "$0")")

# Auto-detect PHP binary untuk aaPanel (PHP-84 / PHP-83 / PHP-82 / system)
# Prioritas: PHP 8.4 > PHP 8.3 > PHP 8.2 > system php
if [ -f "/www/server/php/84/bin/php" ]; then
    PHP_BIN="/www/server/php/84/bin/php"
elif [ -f "/www/server/php/83/bin/php" ]; then
    PHP_BIN="/www/server/php/83/bin/php"
elif [ -f "/www/server/php/82/bin/php" ]; then
    PHP_BIN="/www/server/php/82/bin/php"
else
    PHP_BIN="php"
fi

# Tampilkan info PHP yang terdeteksi
PHP_FULL_VERSION=$($PHP_BIN -v 2>/dev/null | head -1)
if [ -n "$PHP_FULL_VERSION" ]; then
    info "[Auto-Detect] PHP Binary: $PHP_BIN ($PHP_FULL_VERSION)"
else
    warn "[Auto-Detect] PHP Binary: $PHP_BIN (tidak dapat membaca versi)"
    warn "Pastikan PHP sudah terinstall!"
fi

# Auto-detect Composer binary
# Di aaPanel, composer mungkin ada di /usr/local/bin/composer
if [ -f "/usr/local/bin/composer" ]; then
    COMPOSER_BIN="/usr/local/bin/composer"
elif [ -f "/www/server/php/84/bin/composer" ]; then
    COMPOSER_BIN="/www/server/php/84/bin/composer"
elif [ -f "/www/server/php/83/bin/composer" ]; then
    COMPOSER_BIN="/www/server/php/83/bin/composer"
elif command -v composer &>/dev/null; then
    COMPOSER_BIN="$(command -v composer)"
else
    COMPOSER_BIN="composer"
fi
info "[Auto-Detect] Composer: $COMPOSER_BIN"

NPM_BIN="npm"
GIT_BRANCH="main"

# Izinkan Composer berjalan sebagai root tanpa warning
export COMPOSER_ALLOW_SUPERUSER=1

# =============================================================================
# Mulai Deploy
# =============================================================================
echo ""
echo "============================================="
echo "  🚀 Deploy Laravel LMS"
echo "============================================="
echo ""

cd "$APP_DIR" || error "Gagal masuk ke direktori aplikasi: $APP_DIR"
info "Direktori: $APP_DIR"
info "Branch: $GIT_BRANCH"
echo ""

# =============================================================================
# Fungsi: Deteksi Versi PHP
# =============================================================================
detect_php_version() {
    local php_version
    php_version=$($PHP_BIN -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null)
    if [ -z "$php_version" ]; then
        error "PHP tidak ditemukan. Pastikan PHP sudah terinstall."
    fi
    echo "$php_version"
}

# =============================================================================
# Fungsi: Deteksi Package Manager
# =============================================================================
detect_package_manager() {
    if command -v apt-get &>/dev/null; then
        echo "apt"
    elif command -v dnf &>/dev/null; then
        echo "dnf"
    elif command -v yum &>/dev/null; then
        echo "yum"
    else
        echo "unknown"
    fi
}

# =============================================================================
# Fungsi: Cek apakah PHP extension terinstall
# =============================================================================
check_php_extension() {
    local ext_name="$1"
    $PHP_BIN -m 2>/dev/null | grep -qi "^${ext_name}$"
}

# =============================================================================
# Fungsi: Install PHP extension berdasarkan OS
# =============================================================================
install_php_extension() {
    local ext_name="$1"
    local php_version="$2"
    local pkg_mgr="$3"

    # Mapping nama extension ke nama package
    local pkg_name=""
    case "$ext_name" in
        pdo_mysql)
            case "$pkg_mgr" in
                apt)  pkg_name="php${php_version}-mysql" ;;
                dnf)  pkg_name="php-mysqlnd" ;;
                yum)  pkg_name="php-mysqlnd" ;;
            esac
            ;;
        curl)
            case "$pkg_mgr" in
                apt)  pkg_name="php${php_version}-curl" ;;
                dnf)  pkg_name="php-curl" ;;
                yum)  pkg_name="php-curl" ;;
            esac
            ;;
        gd)
            case "$pkg_mgr" in
                apt)  pkg_name="php${php_version}-gd" ;;
                dnf)  pkg_name="php-gd" ;;
                yum)  pkg_name="php-gd" ;;
            esac
            ;;
        mbstring)
            case "$pkg_mgr" in
                apt)  pkg_name="php${php_version}-mbstring" ;;
                dnf)  pkg_name="php-mbstring" ;;
                yum)  pkg_name="php-mbstring" ;;
            esac
            ;;
        xml)
            case "$pkg_mgr" in
                apt)  pkg_name="php${php_version}-xml" ;;
                dnf)  pkg_name="php-xml" ;;
                yum)  pkg_name="php-xml" ;;
            esac
            ;;
        zip)
            case "$pkg_mgr" in
                apt)  pkg_name="php${php_version}-zip" ;;
                dnf)  pkg_name="php-zip" ;;
                yum)  pkg_name="php-zip" ;;
            esac
            ;;
        bcmath)
            case "$pkg_mgr" in
                apt)  pkg_name="php${php_version}-bcmath" ;;
                dnf)  pkg_name="php-bcmath" ;;
                yum)  pkg_name="php-bcmath" ;;
            esac
            ;;
        intl)
            case "$pkg_mgr" in
                apt)  pkg_name="php${php_version}-intl" ;;
                dnf)  pkg_name="php-intl" ;;
                yum)  pkg_name="php-intl" ;;
            esac
            ;;
    esac

    if [ -z "$pkg_name" ]; then
        warn "Tidak ditemukan package untuk extension '$ext_name' pada package manager '$pkg_mgr'"
        return 1
    fi

    info "  Menginstall $pkg_name..."
    case "$pkg_mgr" in
        apt)
            apt-get install -y "$pkg_name" 2>/dev/null
            ;;
        dnf)
            dnf install -y "$pkg_name" 2>/dev/null
            ;;
        yum)
            yum install -y "$pkg_name" 2>/dev/null
            ;;
        *)
            warn "  Package manager tidak dikenal. Install manual: $pkg_name"
            return 1
            ;;
    esac
}

# =============================================================================
# 0. Pengecekan & Installasi PHP Extensions
# =============================================================================
step "0. Pengecekan PHP Extensions..."

PHP_VERSION=$(detect_php_version)
PKG_MGR=$(detect_package_manager)

info "PHP Version: $PHP_VERSION"
info "Package Manager: $PKG_MGR"

# Daftar extensions yang dibutuhkan
REQUIRED_EXTENSIONS=("pdo_mysql" "curl" "gd" "mbstring" "xml" "zip" "bcmath" "intl")

MISSING_EXTENSIONS=()
FAILED_INSTALL=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if check_php_extension "$ext"; then
        info "  ✅ $ext - sudah terinstall"
    else
        warn "  ❌ $ext - belum terinstall"
        MISSING_EXTENSIONS+=("$ext")

        if [ "$PKG_MGR" != "unknown" ]; then
            if install_php_extension "$ext" "$PHP_VERSION" "$PKG_MGR"; then
                # Verifikasi apakah berhasil
                if check_php_extension "$ext"; then
                    info "  ✅ $ext - berhasil diinstall"
                else
                    warn "  ⚠️  $ext - install selesai tapi belum terdeteksi (mungkin perlu restart php-fpm)"
                    FAILED_INSTALL+=("$ext")
                fi
            else
                warn "  ⚠️  $ext - gagal diinstall otomatis"
                FAILED_INSTALL+=("$ext")
            fi
        else
            warn "  ⚠️  Package manager tidak dikenal, tidak bisa install otomatis"
            FAILED_INSTALL+=("$ext")
        fi
    fi
done

echo ""

# Jika masih ada extension yang gagal diinstall, tampilkan instruksi manual
if [ ${#FAILED_INSTALL[@]} -gt 0 ]; then
    warn "==========================================="
    warn "  Extension yang perlu diinstall MANUAL:"
    warn "==========================================="
    for ext in "${FAILED_INSTALL[@]}"; do
        warn "  - $ext"
    done
    echo ""
    warn "Lihat SERVER_SETUP.md untuk instruksi lengkap install manual."
    warn "Atau jalankan:"
    echo ""
    if [ "$PKG_MGR" = "apt" ]; then
        echo -e "  ${CYAN}sudo apt-get update${NC}"
        for ext in "${FAILED_INSTALL[@]}"; do
            local pkg=""
            case "$ext" in
                pdo_mysql) pkg="php${PHP_VERSION}-mysql" ;;
                curl)      pkg="php${PHP_VERSION}-curl" ;;
                gd)        pkg="php${PHP_VERSION}-gd" ;;
                mbstring)  pkg="php${PHP_VERSION}-mbstring" ;;
                xml)       pkg="php${PHP_VERSION}-xml" ;;
                zip)       pkg="php${PHP_VERSION}-zip" ;;
                bcmath)    pkg="php${PHP_VERSION}-bcmath" ;;
                intl)      pkg="php${PHP_VERSION}-intl" ;;
            esac
            if [ -n "$pkg" ]; then
                echo -e "  ${CYAN}sudo apt-get install -y $pkg${NC}"
            fi
        done
    elif [ "$PKG_MGR" = "dnf" ] || [ "$PKG_MGR" = "yum" ]; then
        echo -e "  ${CYAN}sudo $PKG_MGR update${NC}"
        for ext in "${FAILED_INSTALL[@]}"; do
            local pkg=""
            case "$ext" in
                pdo_mysql) pkg="php-mysqlnd" ;;
                curl)      pkg="php-curl" ;;
                gd)        pkg="php-gd" ;;
                mbstring)  pkg="php-mbstring" ;;
                xml)       pkg="php-xml" ;;
                zip)       pkg="php-zip" ;;
                bcmath)    pkg="php-bcmath" ;;
                intl)      pkg="php-intl" ;;
            esac
            if [ -n "$pkg" ]; then
                echo -e "  ${CYAN}sudo $PKG_MGR install -y $pkg${NC}"
            fi
        done
    fi
    echo ""
    warn "Setelah install manual, jalankan ulang deploy script."
    warn "Atau lanjutkan dengan --ignore-platform-reqs (tidak disarankan)."
    echo ""
fi

# =============================================================================
# 1. Aktifkan Maintenance Mode
# =============================================================================
info "Mengaktifkan maintenance mode..."
$PHP_BIN artisan down --refresh=15 --retry=60 || true

# =============================================================================
# 2. Reset local changes & pull perubahan terbaru dari Git
# =============================================================================
info "Mereset perubahan lokal..."
git checkout -- .
git clean -fd -e public/.user.ini -e public/.well-known

info "Pulling perubahan terbaru dari git..."
git pull origin "$GIT_BRANCH" || error "Gagal pull dari git"

# Pastikan deploy.sh tetap executable setelah pull
chmod +x "$APP_DIR/deploy.sh"

# =============================================================================
# 3. Install/update dependencies PHP
# =============================================================================
info "Menginstall dependencies PHP (production)..."

# Coba install normal terlebih dahulu
if ! $PHP_BIN $COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction 2>/dev/null; then
    warn "Composer install gagal. Kemungkinan ada extension yang belum terinstall."

    if [ ${#FAILED_INSTALL[@]} -gt 0 ]; then
        warn "Beberapa extension belum terinstall: ${FAILED_INSTALL[*]}"
        warn ""
        warn "Mencoba composer install dengan --ignore-platform-reqs..."
        warn "⚠️  PERINGATAN: Ini hanya sementara. Install extension yang missing sesegera mungkin!"
        warn ""

        $PHP_BIN $COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs \
            || error "Composer install gagal bahkan dengan --ignore-platform-reqs. Periksa SERVER_SETUP.md"
    else
        error "Composer install gagal. Periksa log error di atas."
    fi
else
    info "✅ Composer install berhasil"
fi

# =============================================================================
# 4. Install/update dependencies Node.js & build assets
# =============================================================================
info "Menginstall dependencies Node.js..."
$NPM_BIN ci

info "Building assets (Vite)..."
$NPM_BIN run build

# =============================================================================
# 5. Jalankan migrasi database
# =============================================================================
info "Menjalankan migrasi database..."
$PHP_BIN artisan migrate --force

# =============================================================================
# 6. Optimasi Laravel
# =============================================================================
info "Mengoptimasi aplikasi..."
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan event:cache

# =============================================================================
# 7. Clear cache lama
# =============================================================================
info "Membersihkan cache lama..."
$PHP_BIN artisan cache:clear

# =============================================================================
# 8. Link storage (jika belum)
# =============================================================================
info "Memastikan storage link..."
$PHP_BIN artisan storage:link 2>/dev/null || true

# =============================================================================
# 9. Restart queue worker
# =============================================================================
info "Merestart queue worker..."
$PHP_BIN artisan queue:restart

# =============================================================================
# 10. Restart PHP-FPM jika extension baru diinstall
# =============================================================================
if [ ${#FAILED_INSTALL[@]} -eq 0 ] && [ ${#MISSING_EXTENSIONS[@]} -gt 0 ]; then
    info "Merestart PHP-FPM karena ada extension baru..."
    if command -v systemctl &>/dev/null; then
        systemctl restart "php${PHP_VERSION}-fpm" 2>/dev/null \
            || systemctl restart php-fpm 2>/dev/null \
            || warn "Gagal restart PHP-FPM. Restart manual jika diperlukan."
    fi
fi

# =============================================================================
# 11. Set permission yang benar
# =============================================================================
info "Mengatur permission..."
chmod -R 775 storage bootstrap/cache

# Deteksi user web server (www-data untuk Ubuntu/Debian, www untuk aaPanel/BT Panel)
WEB_USER="www-data"
if id "www" &>/dev/null; then
    WEB_USER="www"
fi
chown -R "$WEB_USER:$WEB_USER" storage bootstrap/cache 2>/dev/null || warn "Gagal chown. Pastikan permission sudah benar secara manual."

# =============================================================================
# 12. Nonaktifkan Maintenance Mode
# =============================================================================
info "Menonaktifkan maintenance mode..."
$PHP_BIN artisan up

# =============================================================================
# Selesai
# =============================================================================
echo ""
echo "============================================="
echo -e "  ${GREEN}✅ Deploy selesai!${NC}"
echo "============================================="
echo ""
info "Jangan lupa cek:"
echo "  - Website bisa diakses"
echo "  - Queue worker berjalan (supervisord)"
echo "  - Log error: storage/logs/laravel.log"

# Ringkasan extension status
if [ ${#FAILED_INSTALL[@]} -gt 0 ]; then
    echo ""
    warn "⚠️  PERINGATAN: Beberapa PHP extension belum terinstall:"
    for ext in "${FAILED_INSTALL[@]}"; do
        warn "  - $ext"
    done
    warn "Lihat SERVER_SETUP.md untuk instruksi install manual."
fi
echo ""
