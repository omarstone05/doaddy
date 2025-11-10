# Production Deployment Guide

## Pre-Deployment Checklist

### 1. Environment Configuration

#### Required Environment Variables

```env
APP_NAME="Addy Business"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=addy_production
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

# AI Provider (configure via admin panel after deployment)
# OPENAI_API_KEY=your_key_here
# ANTHROPIC_API_KEY=your_key_here
```

### 2. Security Checklist

- [ ] Change default admin password (`admin@addybusiness.com` / `admin123`)
- [ ] Generate new `APP_KEY` using `php artisan key:generate`
- [ ] Set `APP_DEBUG=false` in production
- [ ] Use strong database passwords
- [ ] Configure Redis with password if exposed
- [ ] Set up SSL/TLS certificates
- [ ] Configure proper file permissions
- [ ] Review and restrict file upload sizes
- [ ] Enable rate limiting
- [ ] Configure CORS properly
- [ ] Set up firewall rules
- [ ] Enable database backups

### 3. Server Requirements

- PHP 8.2+ with extensions: BCMath, Ctype, cURL, DOM, Fileinfo, JSON, Mbstring, OpenSSL, PCRE, PDO, Tokenizer, XML
- MySQL 8.0+ or PostgreSQL 13+
- Redis 6.0+ (for caching and queues)
- Node.js 18+ and npm (for building assets)
- Composer 2.0+
- Web server: Nginx or Apache
- SSL certificate

### 4. Deployment Steps

#### Step 1: Clone Repository
```bash
git clone https://github.com/omarstone05/doaddy.git
cd doaddy
```

#### Step 2: Install Dependencies
```bash
# PHP dependencies
composer install --optimize-autoloader --no-dev

# Node dependencies
npm ci
```

#### Step 3: Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env with production values
nano .env
```

#### Step 4: Database Setup
```bash
# Run migrations
php artisan migrate --force

# Seed admin user and settings (optional, or create manually)
php artisan db:seed --class=AdminSeeder
```

#### Step 5: Optimize Laravel
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache events (if using)
php artisan event:cache
```

#### Step 6: Build Frontend Assets
```bash
# Production build
npm run build
```

#### Step 7: Storage Setup
```bash
# Create storage link
php artisan storage:link

# Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### Step 8: Queue Worker Setup
```bash
# Start queue worker (use supervisor for production)
php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
```

#### Step 9: Schedule Setup (Cron)
Add to crontab:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### 5. Post-Deployment Tasks

1. **Change Default Admin Password**
   - Login as `admin@addybusiness.com`
   - Go to Settings → Change Password
   - Use a strong, unique password

2. **Configure AI Provider**
   - Go to Admin Panel → System Settings
   - Enter OpenAI or Anthropic API keys
   - Test connection

3. **Configure Email Settings**
   - Go to Admin Panel → Platform Settings
   - Set up SMTP configuration
   - Test email sending

4. **Set Up Monitoring**
   - Configure error logging
   - Set up application monitoring (e.g., Sentry)
   - Monitor queue workers
   - Set up database backups

5. **Performance Optimization**
   - Enable OPcache
   - Configure Redis properly
   - Set up CDN for static assets
   - Enable HTTP/2
   - Configure gzip compression

### 6. Nginx Configuration Example

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com;
    root /var/www/addy/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 7. Supervisor Configuration (Queue Workers)

Create `/etc/supervisor/conf.d/addy-worker.conf`:

```ini
[program:addy-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/addy/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/addy/storage/logs/worker.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start addy-worker:*
```

### 8. Backup Strategy

#### Database Backups
```bash
# Daily backup script
mysqldump -u user -p addy_production > backup_$(date +%Y%m%d).sql
```

#### File Backups
```bash
# Backup storage and uploads
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/app
```

### 9. Monitoring & Logs

- **Application Logs**: `storage/logs/laravel.log`
- **Queue Logs**: `storage/logs/worker.log`
- **Nginx Logs**: `/var/log/nginx/access.log` and `/var/log/nginx/error.log`
- **PHP-FPM Logs**: `/var/log/php8.2-fpm.log`

### 10. Troubleshooting

#### Clear All Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### Rebuild Assets
```bash
npm run build
```

#### Check Queue Status
```bash
php artisan queue:failed
php artisan queue:retry all
```

### 11. Security Hardening

1. **File Permissions**
   ```bash
   find . -type f -exec chmod 644 {} \;
   find . -type d -exec chmod 755 {} \;
   chmod -R 775 storage bootstrap/cache
   ```

2. **Hide Sensitive Files**
   - Ensure `.env` is not publicly accessible
   - Block access to `.git` directory
   - Protect `storage` and `bootstrap/cache` directories

3. **Rate Limiting**
   - Already configured in Laravel
   - Adjust in `app/Http/Kernel.php` if needed

4. **CSRF Protection**
   - Enabled by default
   - Ensure sessions are properly configured

### 12. Performance Tips

1. **Enable OPcache** in `php.ini`
2. **Use Redis** for caching and sessions
3. **Enable HTTP/2** and gzip compression
4. **Use CDN** for static assets
5. **Optimize images** before upload
6. **Monitor slow queries** and optimize database
7. **Use queue workers** for heavy tasks
8. **Enable query caching** where appropriate

## Emergency Procedures

### Rollback
```bash
git checkout <previous-commit>
php artisan migrate:rollback
php artisan config:cache
php artisan route:cache
npm run build
```

### Maintenance Mode
```bash
php artisan down
# Make changes
php artisan up
```

## Support

For issues or questions:
- Check logs: `storage/logs/laravel.log`
- Review error tracking (if configured)
- Contact development team

---

**Last Updated**: January 2025
**Version**: 2.0

