#!/usr/bin/env bash
# ==============================================================================
# Kids World CMS - Code Sync & Fast Deploy Script
# Synchronizes backend_web to /var/www/ezm.quocvu.com and refreshes services
# ==============================================================================

set -euo pipefail

DOMAIN="ezm.quocvu.com"
APP_DIR="/home/quocvu88/domains/${DOMAIN}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}[ERROR] This script must be run as root or with sudo.${NC}"
   exit 1
fi

echo -e "${BLUE}==> Syncing files to ${APP_DIR}...${NC}"

# Sync files while protecting production database and user uploads
rsync -av --delete \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='data/database.sqlite*' \
    --exclude='data/secrets.json*' \
    --exclude='public/uploads/' \
    --exclude='deploy/' \
    "${SRC_DIR}/" "${APP_DIR}/"

# Thư viện Composer (SDK Anthropic cho tính năng vẽ ảnh bằng Claude).
# Bản upload có sẵn vendor/ thì dùng luôn; không có thì cài trên server.
if [ ! -f "${APP_DIR}/vendor/autoload.php" ]; then
    if command -v composer &>/dev/null; then
        echo -e "${BLUE}==> Installing Composer dependencies...${NC}"
        COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction -d "${APP_DIR}"
    else
        echo "[WARN] Chưa có vendor/ và chưa cài composer: tính năng vẽ ảnh bằng Claude sẽ không chạy."
    fi
fi

# Re-apply safe permissions
chown -R www-data:www-data "${APP_DIR}"
chmod -R 775 "${APP_DIR}/data"
chmod -R 775 "${APP_DIR}/public/uploads"

# Detect PHP-FPM service and reload
if systemctl is-active --quiet php8.2-fpm; then
    systemctl reload php8.2-fpm
elif systemctl is-active --quiet php8.3-fpm; then
    systemctl reload php8.3-fpm
fi

systemctl reload nginx

echo -e "${GREEN}[OK] Deploy and file sync completed successfully!${NC}"
