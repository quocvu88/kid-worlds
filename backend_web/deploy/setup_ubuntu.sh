#!/usr/bin/env bash
# ==============================================================================
# Kids World CMS - Ubuntu Automated Deployment & Server Provisioning Script
# Target Domain: ezm.quocvu.com
# Directory: /var/www/ezm.quocvu.com
# ==============================================================================

set -euo pipefail

DOMAIN="ezm.quocvu.com"
APP_DIR="/home/quocvu88/domains/${DOMAIN}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKEND_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}==================================================================${NC}"
echo -e "${GREEN}      Kids World Web CMS - Ubuntu Server Deployment Setup         ${NC}"
echo -e "${BLUE}==================================================================${NC}"
echo -e "Target Domain : ${YELLOW}${DOMAIN}${NC}"
echo -e "Web Directory : ${YELLOW}${APP_DIR}${NC}"
echo ""

# 1. Require Root / Sudo
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}[ERROR] This script must be run as root or with sudo privileges.${NC}"
   echo "Please run: sudo bash $0"
   exit 1
fi

# 2. Update System Packages
echo -e "${BLUE}==> [1/7] Updating package index...${NC}"
apt-get update -y
apt-get install -y software-properties-common curl git unzip zip ufw

# 3. Add PHP PPA repository if needed and install PHP + Extensions
echo -e "${BLUE}==> [2/7] Installing Nginx, PHP and required extensions...${NC}"
if ! command -v add-apt-repository &>/dev/null; then
    apt-get install -y software-properties-common
fi

# Ensure Ondrej PHP PPA for modern PHP versions
if ! grep -q "ondrej/php" /etc/apt/sources.list /etc/apt/sources.list.d/* 2>/dev/null; then
    add-apt-repository -y ppa:ondrej/php
    apt-get update -y
fi

# Choose PHP 8.2 or 8.3
PHP_VER="8.2"
apt-get install -y \
    nginx \
    certbot \
    python3-certbot-nginx \
    php${PHP_VER}-fpm \
    php${PHP_VER}-cli \
    php${PHP_VER}-sqlite3 \
    php${PHP_VER}-curl \
    php${PHP_VER}-mbstring \
    php${PHP_VER}-gd \
    php${PHP_VER}-xml \
    php${PHP_VER}-zip

PHP_SOCKET="/run/php/php${PHP_VER}-fpm.sock"

# 4. Tune PHP-FPM settings for CMS and file uploads
echo -e "${BLUE}==> [3/7] Tuning PHP-FPM configurations (uploads up to 64MB)...${NC}"
PHP_INI="/etc/php/${PHP_VER}/fpm/php.ini"
if [ -f "$PHP_INI" ]; then
    sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 64M/' "$PHP_INI"
    sed -i 's/^post_max_size = .*/post_max_size = 64M/' "$PHP_INI"
    sed -i 's/^memory_limit = .*/memory_limit = 256M/' "$PHP_INI"
    sed -i 's/^max_execution_time = .*/max_execution_time = 180/' "$PHP_INI"
fi

systemctl restart php${PHP_VER}-fpm
systemctl enable php${PHP_VER}-fpm

# 5. Setup Project Files in /var/www/ezm.quocvu.com
echo -e "${BLUE}==> [4/7] Deploying application files to ${APP_DIR}...${NC}"
mkdir -p "${APP_DIR}"

# If running directly from git clone or uploaded package, sync backend files
if [ -d "${BACKEND_DIR}/src" ] && [ -d "${BACKEND_DIR}/public" ]; then
    echo -e "Copying backend_web files into ${APP_DIR}..."
    rsync -av --exclude='.git' --exclude='node_modules' "${BACKEND_DIR}/" "${APP_DIR}/"
fi

# Ensure data and upload directories exist
mkdir -p "${APP_DIR}/data"
mkdir -p "${APP_DIR}/public/uploads/images"
mkdir -p "${APP_DIR}/public/uploads/audio"

# If database.sqlite doesn't exist, create an empty one or touch it
if [ ! -f "${APP_DIR}/data/database.sqlite" ]; then
    touch "${APP_DIR}/data/database.sqlite"
fi

# 6. Set Proper Linux Permissions for Nginx & PHP-FPM (www-data)
echo -e "${BLUE}==> [5/7] Setting permissions for www-data...${NC}"
chown -R www-data:www-data "${APP_DIR}"
find "${APP_DIR}" -type d -exec chmod 755 {} \;
find "${APP_DIR}" -type f -exec chmod 644 {} \;

# Writable directories for SQLite and uploads
chmod -R 775 "${APP_DIR}/data"
chmod 664 "${APP_DIR}/data/database.sqlite"
chmod -R 775 "${APP_DIR}/public/uploads"

# 7. Configure Nginx Virtual Host
echo -e "${BLUE}==> [6/7] Configuring Nginx Virtual Host for ${DOMAIN}...${NC}"
NGINX_CONF_AVAILABLE="/etc/nginx/sites-available/${DOMAIN}"
NGINX_CONF_ENABLED="/etc/nginx/sites-enabled/${DOMAIN}"

# Use provided config template and update PHP socket path
cp "${SCRIPT_DIR}/ezm.quocvu.com.conf" "${NGINX_CONF_AVAILABLE}"
sed -i "s|unix:/run/php/php-fpm.sock;|unix:${PHP_SOCKET};|g" "${NGINX_CONF_AVAILABLE}"

ln -sf "${NGINX_CONF_AVAILABLE}" "${NGINX_CONF_ENABLED}"

# Remove default site if present
if [ -f /etc/nginx/sites-enabled/default ]; then
    rm -f /etc/nginx/sites-enabled/default
fi

# Configure UFW firewall
if command -v ufw &>/dev/null; then
    ufw allow OpenSSH || true
    ufw allow 'Nginx Full' || true
fi

# Test Nginx syntax
nginx -t
systemctl reload nginx
systemctl enable nginx

# 8. SSL Certificate via Let's Encrypt Certbot
echo -e "${BLUE}==> [7/7] Checking SSL Certificate for ${DOMAIN}...${NC}"
echo -e "${YELLOW}Note: Domain DNS (A Record) must point to this Ubuntu Server IP before requesting SSL.${NC}"
read -p "Do you want to obtain Let's Encrypt SSL certificate now? (y/N): " -r WANT_SSL
if [[ $WANT_SSL =~ ^[Yy]$ ]]; then
    read -p "Enter your email for Certbot notifications: " -r CERT_EMAIL
    if [ -n "$CERT_EMAIL" ]; then
        certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos --email "${CERT_EMAIL}" --redirect
    else
        certbot --nginx -d "${DOMAIN}" --redirect
    fi
    systemctl reload nginx
    echo -e "${GREEN}[SUCCESS] SSL certificate installed successfully!${NC}"
else
    echo -e "${YELLOW}Skipping SSL setup for now. You can run it later with:${NC}"
    echo -e "sudo certbot --nginx -d ${DOMAIN}"
fi

echo ""
echo -e "${GREEN}==================================================================${NC}"
echo -e "${GREEN}   Deployment Complete! Kids World CMS is running on Ubuntu       ${NC}"
echo -e "${GREEN}==================================================================${NC}"
echo -e "Web CMS URL     : ${YELLOW}http://${DOMAIN}${NC} (or ${YELLOW}https://${DOMAIN}${NC} if SSL enabled)"
echo -e "REST API Info   : ${YELLOW}http://${DOMAIN}/api/info${NC}"
echo -e "REST API Packs  : ${YELLOW}http://${DOMAIN}/api/packs${NC}"
echo -e "Web Directory   : ${APP_DIR}"
echo -e "SQLite Database : ${APP_DIR}/data/database.sqlite"
echo -e "Uploads Dir     : ${APP_DIR}/public/uploads"
echo -e "=================================================================="
