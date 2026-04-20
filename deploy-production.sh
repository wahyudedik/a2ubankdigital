#!/bin/bash

# Production Deployment Script for A2U Bank Digital
# This script handles the secure deployment to production environment

set -e  # Exit on any error

echo "🚀 Starting Production Deployment for A2U Bank Digital"
echo "=================================================="

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the Laravel root directory."
    exit 1
fi

# Backup current production (if exists)
echo "📦 Creating backup..."
if [ -d "storage/backups" ]; then
    mkdir -p storage/backups
fi

BACKUP_NAME="backup-$(date +%Y%m%d-%H%M%S)"
echo "Creating backup: $BACKUP_NAME"

# Database backup
if command -v mysqldump &> /dev/null; then
    echo "🗄️  Backing up database..."
    mysqldump -u root -p a2ubank > "storage/backups/${BACKUP_NAME}-database.sql"
    echo "✅ Database backup created"
fi

# File backup
echo "📁 Backing up files..."
tar -czf "storage/backups/${BACKUP_NAME}-files.tar.gz" \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs' \
    --exclude='storage/framework/cache' \
    --exclude='storage/framework/sessions' \
    --exclude='storage/framework/views' \
    .

echo "✅ File backup created"

# Install/Update dependencies
echo "📚 Installing production dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --production

# Clear and optimize caches
echo "🧹 Clearing and optimizing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Run database migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Build frontend assets
echo "🎨 Building frontend assets..."
npm run build

# Set proper permissions
echo "🔐 Setting file permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 644 storage/logs

# Security checks
echo "🔒 Running security checks..."

# Check for debug mode
if grep -q "APP_DEBUG=true" .env; then
    echo "❌ ERROR: APP_DEBUG is still set to true in .env file!"
    echo "Please set APP_DEBUG=false before deploying to production."
    exit 1
fi

# Check for empty database password
if grep -q "DB_PASSWORD=$" .env || grep -q "DB_PASSWORD=\"\"" .env; then
    echo "⚠️  WARNING: Database password is empty in .env file!"
    echo "Please set a strong database password before deploying to production."
fi

# Check for exposed VAPID keys
if grep -q "VAPID_" .env; then
    echo "⚠️  WARNING: VAPID keys found in .env file!"
    echo "Consider moving these to server environment variables for better security."
fi

# Verify critical files exist
CRITICAL_FILES=(".env" "public/.htaccess" ".htaccess")
for file in "${CRITICAL_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        echo "❌ ERROR: Critical file missing: $file"
        exit 1
    fi
done

# Test application
echo "🧪 Testing application..."
php artisan route:list > /dev/null
if [ $? -eq 0 ]; then
    echo "✅ Application routes loaded successfully"
else
    echo "❌ ERROR: Application failed to load routes"
    exit 1
fi

# Final security reminders
echo ""
echo "🔒 SECURITY CHECKLIST - Please verify manually:"
echo "=============================================="
echo "□ SSL/TLS certificate is installed and configured"
echo "□ Firewall rules are properly configured"
echo "□ Database password is strong and secure"
echo "□ Server environment variables are set for sensitive data"
echo "□ Backup strategy is in place and tested"
echo "□ Monitoring and alerting are configured"
echo "□ Error logging is configured (without exposing sensitive data)"
echo "□ Regular security updates are scheduled"
echo ""

echo "✅ Production deployment completed successfully!"
echo ""
echo "📋 Post-deployment tasks:"
echo "- Test all critical user flows"
echo "- Verify SSL certificate"
echo "- Check error logs for any issues"
echo "- Monitor application performance"
echo "- Verify backup systems are working"
echo ""
echo "🎉 A2U Bank Digital is ready for production!"