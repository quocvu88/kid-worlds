# Hướng Dẫn Triển Khai Kids World Web CMS Lên Ubuntu Server
**Domain:** `ezm.quocvu.com`  
**Web Root:** `/var/www/ezm.quocvu.com`  
**Kiến trúc:** Ubuntu 22.04 / 24.04 LTS + Nginx + PHP 8.2/8.3-FPM + SQLite3 + Let's Encrypt SSL  

---

## 1. Yêu Cầu Chuẩn Bị Trước Khi Triển Khai

1. **Máy chủ Ubuntu**: Ubuntu 20.04, 22.04 hoặc 24.04 LTS (đã mở cổng 80, 443, 22 trên Firewall/Security Group của VPS/Cloud như DigitalOcean, Linode, AWS EC2, Hetzner, Vietnix,...).
2. **Cấu hình DNS (Bắt buộc trước khi xin SSL)**:
   - Truy cập trang quản trị DNS của domain `quocvu.com` (Cloudflare, Namecheap, v.v.).
   - Tạo bản ghi **A Record**:
     - **Host / Name**: `ezm`
     - **Type**: `A`
     - **Points to (Value)**: `<ĐỊA_CHỈ_IP_CỦA_UBUNTU_SERVER>`
     - **TTL**: Auto hoặc 5 phút (nếu dùng Cloudflare, hãy bật DNS Only / tắt proxy cam ban đầu để Certbot xác thực nhanh chóng).

---

## 2. Cách 1: Cài Đặt Tự Động 1-Click (Khuyên Dùng)

### Bước 2.1: Tải mã nguồn lên máy chủ Ubuntu

Bạn có thể đưa thư mục `backend_web` lên máy chủ bằng một trong các cách sau:

#### Cách A: Dùng Git (Khuyên dùng)
```bash
# SSH vào máy chủ Ubuntu
ssh root@<IP_MAY_CHU>

# Clone repo (hoặc tải thư mục dự án)
git clone https://github.com/quocvu88/kid-worlds.git /tmp/kid-worlds
cd /tmp/kid-worlds/backend_web
```

#### Cách B: Dùng SCP từ máy local
Từ máy tính của bạn (PowerShell hoặc Terminal):
```powershell
# Nén thư mục backend_web
# Sau đó chuyển sang Ubuntu
scp -r "d:\SynologyDrive\AI Workspaces\Projects\KidsWorld\backend_web" root@<IP_MAY_CHU>:/tmp/backend_web
```

### Bước 2.2: Chạy Script Tự Động `setup_ubuntu.sh`

```bash
cd /tmp/kid-worlds/backend_web/deploy   # hoặc cd /tmp/backend_web/deploy
chmod +x setup_ubuntu.sh deploy.sh
sudo bash setup_ubuntu.sh
```

**Script sẽ tự động thực hiện toàn bộ:**
1. Cập nhật hệ thống Ubuntu (`apt-get update`).
2. Cài đặt Nginx, PHP 8.2-FPM cùng toàn bộ extension cần thiết (`sqlite3`, `curl`, `mbstring`, `gd`, `zip`, `xml`).
3. Tối ưu PHP: Nâng giới hạn upload lên **64MB** (`upload_max_filesize = 64M`, `post_max_size = 64M`, `memory_limit = 256M`).
4. Triển khai mã nguồn vào `/var/www/ezm.quocvu.com`.
5. Thiết lập quyền chuẩn Linux (`chown www-data:www-data`, cấp quyền ghi `775` cho `data/` và `public/uploads/`).
6. Cài đặt file cấu hình Nginx Server Block cho `ezm.quocvu.com` và kích hoạt.
7. Cấu hình tường lửa UFW (cho phép cổng 80, 443).
8. Tự động hỏi và cài đặt chứng chỉ **Let's Encrypt SSL HTTPS** miễn phí thông qua Certbot.

---

## 3. Cách 2: Cài Đặt & Cấu Hình Thủ Công Từng Bước

Nếu muốn tự kiểm soát từng lệnh trên server, bạn làm theo các bước sau:

### Bước 3.1: Cài đặt Nginx và PHP 8.2-FPM
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y software-properties-common curl git unzip zip certbot python3-certbot-nginx

# Cài đặt PHP 8.2 và các module cần thiết
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.2-fpm php8.2-cli php8.2-sqlite3 php8.2-curl php8.2-mbstring php8.2-gd php8.2-xml php8.2-zip nginx
```

### Bước 3.2: Tối ưu thông số upload trong `php.ini`
Mở file `/etc/php/8.2/fpm/php.ini`:
```ini
upload_max_filesize = 64M
post_max_size = 64M
memory_limit = 256M
max_execution_time = 180
```
Khởi động lại PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm
```

### Bước 3.3: Tạo thư mục web và copy mã nguồn
```bash
sudo mkdir -p /var/www/ezm.quocvu.com
sudo cp -r /tmp/backend_web/* /var/www/ezm.quocvu.com/

# Tạo các thư mục lưu dữ liệu
sudo mkdir -p /var/www/ezm.quocvu.com/data
sudo mkdir -p /var/www/ezm.quocvu.com/public/uploads/images
sudo mkdir -p /var/www/ezm.quocvu.com/public/uploads/audio

# Phân quyền cho www-data
sudo chown -R www-data:www-data /var/www/ezm.quocvu.com
sudo chmod -R 755 /var/www/ezm.quocvu.com
sudo chmod -R 775 /var/www/ezm.quocvu.com/data
sudo chmod -R 775 /var/www/ezm.quocvu.com/public/uploads
```

### Bước 3.4: Cấu hình Nginx
Tạo file `/etc/nginx/sites-available/ezm.quocvu.com`:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name ezm.quocvu.com;

    root /var/www/ezm.quocvu.com/public;
    index index.php index.html;

    client_max_body_size 64M;
    charset utf-8;

    access_log /var/log/nginx/ezm.quocvu.com.access.log;
    error_log /var/log/nginx/ezm.quocvu.com.error.log warn;

    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/xml+rss application/atom+xml image/svg+xml;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    location /uploads/ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
        access_log off;
        try_files $uri =404;
    }

    location ~* \.(?:ico|css|js|gif|jpe?g|png|webp|woff2?|eot|ttf|svg|mp3|wav|ogg)$ {
        expires 7d;
        add_header Cache-Control "public, must-revalidate, proxy-revalidate";
        access_log off;
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        fastcgi_read_timeout 180s;
        fastcgi_send_timeout 180s;
        fastcgi_connect_timeout 60s;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~ ^/(data|src|docs|deploy)($|/) {
        deny all;
        return 404;
    }

    location ~* \.(sqlite|sqlite3|db|env|log|sh|sql)$ {
        deny all;
        return 404;
    }
}
```

Kích hoạt site và kiểm tra:
```bash
sudo ln -sf /etc/nginx/sites-available/ezm.quocvu.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Bước 3.5: Cài Đặt Chứng Chỉ SSL Let's Encrypt
```bash
sudo certbot --nginx -d ezm.quocvu.com
```
Làm theo hướng dẫn trên màn hình (nhập email và chọn chuyển hướng HTTP sang HTTPS).

---

## 4. Kiểm Thử Hệ Thống Sau Khi Triển Khai

Sau khi hoàn tất, bạn có thể kiểm tra:
1. **Giao diện Web CMS**:  
   Truy cập trình duyệt: `https://ezm.quocvu.com`
2. **Kiểm tra REST API Info**:  
   Truy cập: `https://ezm.quocvu.com/api/info`  
   Kết quả trả về JSON:
   ```json
   {
     "status": "success",
     "app": "Kids World Topic Pack Content Server",
     "version": "1.0.0"
   }
   ```
3. **Kiểm tra Danh sách Topic Packs**:  
   Truy cập: `https://ezm.quocvu.com/api/packs`

---

## 5. Kết Nối Ứng Dụng Flutter (Kids World) Với Máy Chủ Cloud

1. Mở app **Kids World** trên điện thoại / máy tính bảng của bé.
2. Vào mục **Cài đặt Phụ Huynh (Parent Settings)**.
3. Tại ô **Máy chủ nội dung (Content Server URL)**, điền:
   ```text
   https://ezm.quocvu.com
   ```
4. Bấm **Kiểm tra kết nối** và **Lưu cấu hình**.
5. Bây giờ phụ huynh có thể tải và đồng bộ mọi gói chủ đề học tập trực tiếp từ `ezm.quocvu.com` từ bất kỳ đâu qua mạng 4G/Wi-Fi!

---

## 6. Sao Lưu & Bảo Trì Dữ Liệu

Toàn bộ dữ liệu của Kids World CMS nằm trong 2 vị trí:
1. File SQLite: `/var/www/ezm.quocvu.com/data/database.sqlite`
2. Thư mục ảnh/âm thanh: `/var/www/ezm.quocvu.com/public/uploads`

### Script Sao lưu tự động định kỳ (Cronjob)
Tạo file `/usr/local/bin/backup_kidsworld.sh`:
```bash
#!/usr/bin/env bash
BACKUP_DIR="/var/backups/kidsworld"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p "${BACKUP_DIR}"

# Backup sqlite
sqlite3 /var/www/ezm.quocvu.com/data/database.sqlite ".backup '${BACKUP_DIR}/db_${DATE}.sqlite'"

# Backup uploads
tar -czf "${BACKUP_DIR}/uploads_${DATE}.tar.gz" -C /var/www/ezm.quocvu.com/public uploads/

# Xoá các bản sao lưu cũ hơn 30 ngày
find "${BACKUP_DIR}" -type f -mtime +30 -delete
```
Cấp quyền và đưa vào cron:
```bash
chmod +x /usr/local/bin/backup_kidsworld.sh
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/backup_kidsworld.sh") | crontab -
```
