# Production Deployment & Hardening Guide
## Electronic Installment SaaS Platform (Phase 18)

This guide documents the enterprise production deployment, system hardening, automated disaster recovery, and operational checklist for the **Electronic Installment SaaS Platform**.

---

## 1. System Requirements & Architecture

| Component | Minimum Specification | Recommended Production Spec |
| :--- | :--- | :--- |
| **Operating System** | Ubuntu 22.04 LTS / Debian 12 / Rocky Linux 9 | Ubuntu 24.04 LTS |
| **PHP Runtime** | PHP 8.3 | PHP 8.3.x with OPcache & JIT |
| **Database Engine** | MySQL 8.0 / MariaDB 10.6+ | Managed MySQL 8.0+ (AWS RDS / GCP Cloud SQL) |
| **Cache & Queue Driver**| Redis 7.0+ | AWS ElastiCache / Redis Cluster |
| **Web Server** | Nginx 1.24+ or Apache 2.4+ | Nginx with TLS 1.3 & HTTP/2 |
| **Process Manager** | Systemd / Supervisor | Systemd / Supervisor |

### Required PHP 8.3 Extensions
```bash
php -m | grep -E "(bcmath|ctype|curl|dom|fileinfo|filter|hash|intl|json|mbstring|openssl|pcre|pdo|pdo_mysql|redis|session|tokenizer|xml|zip)"
```

---

## 2. Production Environment Configuration (`.env`)

Ensure the following critical environment variables are set in production:

```ini
APP_NAME="Electronic Installment SaaS"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_APP_KEY
APP_DEBUG=false
APP_URL=https://installment.yourdomain.com

# Database Connection Pool
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=electronic_installment
DB_USERNAME=installment_user
DB_PASSWORD="<STRONG_CRYPTOGRAPHIC_PASSWORD>"

# Caching & Session
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

# Asynchronous Background Queues
QUEUE_CONNECTION=redis

# Disaster Recovery Backup Directory
BACKUP_DISK=local
BACKUP_RETENTION_DAYS=30
```

---

## 3. Production Deployment Commands

Run these steps during each production deployment pipeline (CI/CD):

```bash
# 1. Enter maintenance mode
php artisan down --retry=60 --secret="production-maintenance-bypass-token"

# 2. Pull latest code & install optimized dependencies
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Compile front-end assets
npm ci
npm run build

# 4. Run database migrations
php artisan migrate --force

# 5. Optimize configurations, routes, and views
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Restart queue workers and scheduled workers
php artisan queue:restart

# 7. Bring application back online
php artisan up
```

---

## 4. OWASP Security Hardening & Headers

The application implements standard OWASP security headers globally via `App\Http\Middleware\SecurityHeaders`:

- `X-Frame-Options: SAMEORIGIN` (Clickjacking defense)
- `X-Content-Type-Options: nosniff` (MIME sniffing prevention)
- `X-XSS-Protection: 1; mode=block` (Reflected XSS filter)
- `Referrer-Policy: strict-origin-when-cross-origin` (Leakage protection)
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- `Content-Security-Policy: default-src 'self' ...`

### Nginx Server Block Hardening
```nginx
server {
    listen 443 ssl http2;
    server_name installment.yourdomain.com;
    root /var/www/electronic-installment/public;

    ssl_certificate /etc/letsencrypt/live/installment.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/installment.yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 5. Automated Scheduling & Disaster Recovery

### Crontab Setup
Add the master Laravel scheduler to the server's crontab (`crontab -e -u www-data`):

```cron
* * * * * cd /var/www/electronic-installment && php artisan schedule:run >> /dev/null 2>&1
```

### Registered System Schedules
1. `saas:check-subscriptions`: Daily evaluation of past-due tenant subscriptions and automated suspension locks.
2. `installment:send-reminders`: Daily at 09:00 AM dispatch of installment payment due notices via SMS & WhatsApp.
3. `system:backup-database --clean-days=30`: Daily at 02:00 AM automated database snapshot backup and pruning of snapshots older than 30 days.

### Manual Backup Trigger
Platform Super Administrators can trigger on-demand database snapshots anytime from the Web UI:
- **Route**: `POST /admin/backup/trigger`
- **CLI**: `php artisan system:backup-database --clean-days=30`
- **Storage Location**: `storage/app/backups/`

---

## 6. Queue Worker Configuration (Supervisor)

Create `/etc/supervisor/conf.d/installment-worker.conf`:

```ini
[program:installment-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/electronic-installment/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/electronic-installment/storage/logs/worker.log
stopwaitsecs=3600
```

Update supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start installment-worker:*
```

---

## 7. Audit Trail & Immutability Rules

- All database mutations to `InstallmentAgreement`, `Payment`, `LateFeeWaiver`, `CreditApproval`, `Customer`, `RecoveryCase`, `Subscription`, and `InventoryTransfer` automatically generate append-only logs in `audit_logs`.
- `AuditLog` models are strictly immutable: attempting to call `update()` or `delete()` triggers a `RuntimeException`.
- Logs record the authenticated actor, tenant company ID, IP address, user agent, requested URL, before-and-after attribute diffs, and exact timestamps.
