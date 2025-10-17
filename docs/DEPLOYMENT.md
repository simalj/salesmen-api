# Deployment Guide

## 🐳 Docker Deployment (Recommended)

The fastest and most reliable way to deploy the Salesmen API.

### Quick Start

```bash
# Clone repository
git clone <repository-url>
cd salesmen-api

# Deploy with single command
docker-compose up -d --build
```

### Docker Architecture

**Services:**
- **app**: Laravel API (PHP 8.2-Apache) on port 8000
- **postgres**: PostgreSQL 16 database on port 5432

**Automated Setup:**
- Environment configuration (`.env.docker` → `.env`)
- Application key generation
- Database migrations
- Cache warming and permissions
- Health checks and service dependencies

### Docker Configuration Files

- `Dockerfile` - Laravel application container
- `docker-compose.yml` - Multi-service orchestration
- `docker-entrypoint.sh` - Startup automation script
- `.env.docker` - Docker environment template

### Health Monitoring

```bash
# Check service status
docker-compose ps

# View application logs
docker-compose logs app

# Database logs
docker-compose logs postgres

# Health check endpoint
curl http://localhost:8000/api/health
```

### Docker Production Deployment

For production, customize the environment:

1. **Create production docker-compose.override.yml**:
```yaml
version: '3.8'
services:
  app:
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_URL=https://your-domain.com
    restart: unless-stopped
  
  postgres:
    volumes:
      - /var/lib/postgresql/data:/var/lib/postgresql/data
    restart: unless-stopped
```

2. **Deploy with overrides**:
```bash
docker-compose -f docker-compose.yml -f docker-compose.override.yml up -d --build
```

### 🔄 **Environment Management**

```bash
# Switch to Development (with testing tools)
docker-compose -f docker-compose.dev.yml up -d --build

# Switch to Production (optimized)
docker-compose down && docker-compose up -d --build

# Production with custom overrides
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# Stop all environments
docker-compose down  # or docker-compose -f docker-compose.dev.yml down
```

---

## Manual Production Deployment

For traditional server deployment without Docker.

### Prerequisites

-   PHP 8.2+ with required extensions
-   PostgreSQL 16+
-   Composer
-   Web server (Apache/Nginx)
-   SSL certificate for HTTPS

### Environment Configuration

Create production `.env` file:

```env
APP_NAME="Salesmen API"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_TIMEZONE=Europe/Bratislava
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=salesmen_api_prod
DB_USERNAME=your-username
DB_PASSWORD=your-secure-password

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-redis-password
REDIS_PORT=6379
```

### Production Setup

1. **Clone and setup**:

```bash
git clone https://github.com/your-org/salesmen-api.git
cd salesmen-api
composer install --no-dev --optimize-autoloader
```

2. **Environment setup**:

```bash
cp .env.production .env
php artisan key:generate
```

3. **Database setup**:

```bash
php artisan migrate --force
php artisan db:seed --force
```

4. **Optimize for production**:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

5. **Set permissions**:

```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chown -R www-data:www-data storage/
chown -R www-data:www-data bootstrap/cache/
```

### Web Server Configuration

#### Nginx Configuration

```nginx
server {
    listen 80;
    listen 443 ssl;
    server_name your-domain.com;
    root /var/www/salesmen-api/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

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

    # SSL configuration
    ssl_certificate /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/private.key;
}
```

#### Apache Configuration

```apache
<VirtualHost *:443>
    ServerName your-domain.com
    DocumentRoot /var/www/salesmen-api/public

    SSLEngine on
    SSLCertificateFile /path/to/ssl/cert.pem
    SSLCertificateKeyFile /path/to/ssl/private.key

    <Directory /var/www/salesmen-api/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/salesmen-api_error.log
    CustomLog ${APACHE_LOG_DIR}/salesmen-api_access.log combined
</VirtualHost>
```

### Performance Optimization

#### Database Optimization

```sql
-- Add production indexes
CREATE INDEX CONCURRENTLY IF NOT EXISTS salesmen_prosight_id_btree
ON salesmen USING btree (prosight_id);

CREATE INDEX CONCURRENTLY IF NOT EXISTS salesmen_email_btree
ON salesmen USING btree (email);

CREATE INDEX CONCURRENTLY IF NOT EXISTS salesmen_created_at_btree
ON salesmen USING btree (created_at DESC);

-- Analyze tables
ANALYZE salesmen;
ANALYZE genders;
ANALYZE marital_statuses;
```

#### PHP-FPM Configuration

```ini
; /etc/php/8.2/fpm/pool.d/salesmen-api.conf
[salesmen-api]
user = www-data
group = www-data
listen = /var/run/php/php8.2-fpm-salesmen.sock
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
```

#### Redis Configuration

```conf
# /etc/redis/redis.conf
maxmemory 256mb
maxmemory-policy allkeys-lru
timeout 300
tcp-keepalive 60
```

### Monitoring and Logging

#### Log Rotation

```bash
# /etc/logrotate.d/salesmen-api
/var/www/salesmen-api/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 644 www-data www-data
    postrotate
        php /var/www/salesmen-api/artisan config:cache
    endscript
}
```

#### Health Check Monitoring

Set up monitoring for health endpoints:

```bash
# Cron job for health checks
*/5 * * * * curl -f https://your-domain.com/health || echo "API health check failed" | mail -s "API Alert" admin@your-domain.com
```

#### Application Monitoring

Consider implementing:

-   **New Relic** or **DataDog** for APM
-   **Sentry** for error tracking
-   **Prometheus + Grafana** for metrics
-   **ELK Stack** for log analysis

### Security Hardening

#### SSL/TLS Configuration

```nginx
# Strong SSL configuration
ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384;
ssl_prefer_server_ciphers off;
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 10m;
```

#### Rate Limiting

```nginx
# Nginx rate limiting
http {
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;

    server {
        location /api/ {
            limit_req zone=api burst=20 nodelay;
        }
    }
}
```

#### Firewall Rules

```bash
# UFW rules
ufw allow ssh
ufw allow 'Nginx Full'
ufw deny from any to any port 5432  # PostgreSQL
ufw deny from any to any port 6379  # Redis
ufw enable
```

### Backup Strategy

#### Database Backup

```bash
#!/bin/bash
# /opt/scripts/backup-db.sh
BACKUP_DIR="/var/backups/salesmen-api"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

pg_dump -U postgres -h localhost salesmen_api_prod \
    | gzip > $BACKUP_DIR/salesmen_api_$DATE.sql.gz

# Keep only last 30 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete
```

#### Application Backup

```bash
#!/bin/bash
# /opt/scripts/backup-app.sh
BACKUP_DIR="/var/backups/salesmen-api"
APP_DIR="/var/www/salesmen-api"
DATE=$(date +%Y%m%d_%H%M%S)

tar -czf $BACKUP_DIR/app_$DATE.tar.gz \
    --exclude='storage/logs' \
    --exclude='storage/framework/cache' \
    --exclude='storage/framework/sessions' \
    --exclude='vendor' \
    --exclude='.git' \
    $APP_DIR
```

### Deployment Automation

#### GitHub Actions

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
    push:
        branches: [main]

jobs:
    deploy:
        runs-on: ubuntu-latest
        steps:
            - uses: actions/checkout@v3

            - name: Deploy to server
              uses: appleboy/ssh-action@v0.1.5
              with:
                  host: ${{ secrets.HOST }}
                  username: ${{ secrets.USERNAME }}
                  key: ${{ secrets.SSH_KEY }}
                  script: |
                      cd /var/www/salesmen-api
                      git pull origin main
                      composer install --no-dev --optimize-autoloader
                      php artisan migrate --force
                      php artisan config:cache
                      php artisan route:cache
                      php artisan view:cache
                      sudo systemctl reload php8.2-fpm
                      sudo systemctl reload nginx
```

### Troubleshooting

#### Common Issues

1. **Permission errors**:

```bash
chmod -R 755 storage/
chown -R www-data:www-data storage/
```

2. **Cache issues**:

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

3. **Database connection issues**:

-   Check PostgreSQL service status
-   Verify connection parameters in `.env`
-   Check firewall rules

#### Performance Issues

1. **Slow queries**:

```sql
-- Enable slow query logging
ALTER DATABASE salesmen_api_prod SET log_min_duration_statement = 1000;
```

2. **Memory issues**:

```bash
# Monitor PHP-FPM processes
ps aux | grep php-fpm
# Check memory usage
free -h
```

### Production Checklist

-   [ ] SSL certificate installed and configured
-   [ ] Environment variables properly set
-   [ ] Database migrations run
-   [ ] Composer dependencies installed (production)
-   [ ] Laravel optimizations applied
-   [ ] File permissions set correctly
-   [ ] Web server configured
-   [ ] Firewall rules applied
-   [ ] Monitoring setup
-   [ ] Backup procedures in place
-   [ ] Log rotation configured
-   [ ] Health checks working
-   [ ] Rate limiting configured
-   [ ] Error tracking setup

This deployment guide ensures a secure, optimized, and maintainable production environment.
