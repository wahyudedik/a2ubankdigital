# Deployment Guide - A2U Bank Digital

Panduan untuk deploy aplikasi ke production server.

## 📋 Pre-Deployment Checklist

- [ ] Database backup
- [ ] Environment variables configured
- [ ] SSL certificate ready
- [ ] Domain DNS configured
- [ ] Server requirements met
- [ ] Email SMTP configured
- [ ] Payment gateway configured

## 🖥️ Server Requirements

### Minimum Specifications
- CPU: 2 cores
- RAM: 4GB
- Storage: 20GB SSD
- OS: Ubuntu 20.04+ / CentOS 7+

### Software Requirements
- PHP 7.4+ (recommended 8.1)
- MySQL 5.7+ / MariaDB 10.3+
- Nginx 1.18+ / Apache 2.4+
- Node.js 18+ (for building frontend)
- Composer 2.x
- SSL Certificate (Let's Encrypt recommended)

## 🚀 Deployment Steps

### 1. Server Preparation

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y nginx mysql-server php8.1-fpm php8.1-mysql \
  php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip \
  composer git certbot python3-certbot-nginx
```

### 2. Upload Files

```bash
# Via Git
cd /var/www
sudo git clone <repository-url> a2ubankdigital.my.id
cd a2ubankdigital.my.id

# Or via FTP/SCP
# Upload all files to /var/www/a2ubankdigital.my.id
```

### 3. Set Permissions

```bash
cd /var/www/a2ubankdigital.my.id

# Set ownership
sudo chown -R www-data:www-data .

# Set directory permissions
sudo find . -type d -exec chmod 755 {} \;

# Set file permissions
sudo find . -type f -exec chmod 644 {} \;

# Writable directories
sudo chmod -R 775 uploads/
sudo chmod -R 775 cache/
sudo chmod -R 775 app/cache/
```

### 4. Database Setup

```bash
# Login to MySQL
sudo mysql -u root -p

# Create database and user
CREATE DATABASE a2uj2723_au2 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'a2ubank'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON a2uj2723_au2.* TO 'a2ubank'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import database
mysql -u a2ubank -p a2uj2723_au2 < a2uj2723_au2.sql
```

### 5. Environment Configuration

```bash
# Copy and edit .env
cp .env.example .env
nano .env
```

Update production values:
```env
APP_ENV=production

DB_HOST=localhost
DB_USER=a2ubank
DB_PASS=strong_password_here
DB_NAME=a2uj2723_au2

JWT_SECRET=generate_strong_random_secret_32_chars
JWT_ISSUER=a2ubankdigital.my.id
JWT_AUDIENCE=a2ubankdigital.my.id

MAIL_HOST=mail.a2ubankdigital.my.id
MAIL_PORT=465
MAIL_USERNAME=support@a2ubankdigital.my.id
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=ssl

ALLOWED_ORIGINS=https://a2ubankdigital.my.id

# Production keys
MIDTRANS_SERVER_KEY=your_production_server_key
MIDTRANS_CLIENT_KEY=your_production_client_key
DIGIFLAZZ_USERNAME=your_production_username
DIGIFLAZZ_API_KEY=your_production_api_key
```

### 6. Install Dependencies

```bash
# Backend dependencies
cd app
composer install --no-dev --optimize-autoloader
cd ..

# Frontend build (if not already built)
cd cgi-bin/frontend
npm install
npm run build
cd ../..
```

### 7. Nginx Configuration

```bash
sudo nano /etc/nginx/sites-available/a2ubankdigital.my.id
```

```nginx
server {
    listen 80;
    server_name a2ubankdigital.my.id www.a2ubankdigital.my.id;
    root /var/www/a2ubankdigital.my.id;
    index index.html index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json;

    # Frontend (React SPA)
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Backend API
    location /app {
        try_files $uri $uri/ /app/index.php?$query_string;
        
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
            
            # Security
            fastcgi_hide_header X-Powered-By;
        }
    }

    # Uploads
    location /uploads {
        alias /var/www/a2ubankdigital.my.id/uploads;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Assets
    location /assets {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to sensitive files
    location ~ /\.env {
        deny all;
    }

    location ~ /\.git {
        deny all;
    }

    location ~ /composer\.(json|lock) {
        deny all;
    }

    location ~ /package(-lock)?\.json {
        deny all;
    }

    # Logs
    access_log /var/log/nginx/a2ubank_access.log;
    error_log /var/log/nginx/a2ubank_error.log;
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/a2ubankdigital.my.id /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 8. SSL Certificate (Let's Encrypt)

```bash
# Install SSL certificate
sudo certbot --nginx -d a2ubankdigital.my.id -d www.a2ubankdigital.my.id

# Auto-renewal (already configured by certbot)
sudo certbot renew --dry-run
```

### 9. PHP Configuration

```bash
sudo nano /etc/php/8.1/fpm/php.ini
```

Update these values:
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.1-fpm
```

### 10. MySQL Optimization

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Add/update:
```ini
[mysqld]
max_connections = 200
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
query_cache_size = 64M
```

Restart MySQL:
```bash
sudo systemctl restart mysql
```

## 🔒 Security Hardening

### 1. Firewall Configuration

```bash
# UFW firewall
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 2. Fail2Ban (Brute Force Protection)

```bash
sudo apt install fail2ban -y
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

### 3. Disable PHP Functions

Edit `/etc/php/8.1/fpm/php.ini`:
```ini
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

### 4. Database Security

```bash
sudo mysql_secure_installation
```

### 5. Regular Updates

```bash
# Create update script
sudo nano /root/update.sh
```

```bash
#!/bin/bash
apt update
apt upgrade -y
apt autoremove -y
certbot renew
systemctl restart nginx
systemctl restart php8.1-fpm
```

```bash
sudo chmod +x /root/update.sh

# Add to crontab (weekly updates)
sudo crontab -e
# Add: 0 3 * * 0 /root/update.sh
```

## 📊 Monitoring

### 1. Setup Log Rotation

```bash
sudo nano /etc/logrotate.d/a2ubank
```

```
/var/log/nginx/a2ubank_*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data adm
    sharedscripts
    postrotate
        [ -f /var/run/nginx.pid ] && kill -USR1 `cat /var/run/nginx.pid`
    endscript
}
```

### 2. Database Backup

```bash
sudo nano /root/backup_db.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/mysql"
mkdir -p $BACKUP_DIR

mysqldump -u a2ubank -p'password' a2uj2723_au2 | gzip > $BACKUP_DIR/a2ubank_$DATE.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete
```

```bash
sudo chmod +x /root/backup_db.sh

# Daily backup at 2 AM
sudo crontab -e
# Add: 0 2 * * * /root/backup_db.sh
```

## ✅ Post-Deployment Verification

### 1. Test Endpoints

```bash
# Test backend
curl https://a2ubankdigital.my.id/app/utility_get_public_config.php

# Test frontend
curl https://a2ubankdigital.my.id
```

### 2. Test Login

- Open browser: https://a2ubankdigital.my.id/login
- Try login with test account
- Check browser console for errors

### 3. Monitor Logs

```bash
# Nginx logs
sudo tail -f /var/log/nginx/a2ubank_error.log

# PHP logs
sudo tail -f /var/log/php/error.log

# MySQL logs
sudo tail -f /var/log/mysql/error.log
```

## 🔄 Update Procedure

```bash
cd /var/www/a2ubankdigital.my.id

# Backup current version
sudo tar -czf /var/backups/a2ubank_$(date +%Y%m%d).tar.gz .

# Pull updates
sudo git pull origin main

# Update dependencies
cd app && sudo -u www-data composer install --no-dev
cd ../cgi-bin/frontend && npm install && npm run build

# Clear cache
sudo rm -rf cache/*

# Restart services
sudo systemctl reload nginx
sudo systemctl restart php8.1-fpm
```

## 🆘 Rollback Procedure

```bash
cd /var/www

# Stop services
sudo systemctl stop nginx

# Restore backup
sudo rm -rf a2ubankdigital.my.id
sudo tar -xzf /var/backups/a2ubank_YYYYMMDD.tar.gz -C a2ubankdigital.my.id

# Restore database
mysql -u a2ubank -p a2uj2723_au2 < /var/backups/mysql/a2ubank_YYYYMMDD.sql

# Start services
sudo systemctl start nginx
```

## 📞 Support

Jika ada masalah saat deployment:
1. Check error logs
2. Verify file permissions
3. Test database connection
4. Check PHP-FPM status
5. Verify Nginx configuration

Contact: support@a2ubankdigital.my.id
