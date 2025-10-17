#!/bin/bash
set -e

echo "🚀 Starting PROSIGHT API initialization..."

# Copy docker environment file
if [ ! -f /var/www/html/.env ]; then
    echo "📋 Setting up environment configuration..."
    if [ "$APP_ENV" = "testing" ]; then
        echo "🧪 Using testing environment..."
        cp /var/www/html/.env.example /var/www/html/.env
        # Update with Docker database settings for testing
        sed -i 's/DB_HOST=127.0.0.1/DB_HOST=postgres/' /var/www/html/.env
        sed -i 's/DB_DATABASE=laravel/DB_DATABASE=salesmen_api/' /var/www/html/.env
        sed -i 's/DB_USERNAME=root/DB_USERNAME=postgres/' /var/www/html/.env
        sed -i 's/DB_PASSWORD=/DB_PASSWORD=password/' /var/www/html/.env
    else
        cp /var/www/html/.env.docker /var/www/html/.env
    fi
fi

# Generate application key if not set
echo "🔑 Generating application key..."
php artisan key:generate --force

# Wait for database to be ready
echo "⏳ Waiting for database to be ready..."
until pg_isready -h postgres -p 5432 -U postgres; do
    echo "Database is unavailable - sleeping"
    sleep 2
done
echo "✅ Database is ready!"

# Clear and cache config
echo "🧹 Clearing and caching configuration..."
php artisan config:clear
php artisan config:cache

# Run database migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Clear and warm up caches
echo "⚡ Warming up application caches..."
php artisan route:clear
php artisan route:cache
php artisan view:clear
php artisan view:cache

# Set final permissions
echo "🔐 Setting final permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "🎉 PROSIGHT API is ready!"
echo "📡 API available at: http://localhost:8000"
echo "🏥 Health check: http://localhost:8000/health"

# Start Apache
exec apache2-foreground