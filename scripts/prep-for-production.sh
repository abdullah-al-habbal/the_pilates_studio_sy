#!/bin/bash
set -e

echo "🚀 Preparing for production deployment..."

# Environment validation
if [ "$APP_ENV" != "production" ]; then
    echo "❌ APP_ENV must be 'production'"
    exit 1
fi

if [ "$APP_DEBUG" != "false" ]; then
    echo "❌ APP_DEBUG must be 'false' in production"
    exit 1
fi

# Config validation
echo "🔍 Validating financial configuration..."
php artisan config:validate-financial
if [ $? -ne 0 ]; then
    echo "❌ Config validation failed"
    exit 1
fi

# Cache optimization
echo "🔄 Optimizing caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Database checks
echo "🗄️  Verifying database integrity..."
php artisan migrate --force

# Health check endpoint test
echo "🏥 Testing health endpoint..."
HEALTH_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/admin/health)
if [ "$HEALTH_RESPONSE" != "200" ]; then
    echo "⚠️  Health endpoint returned $HEALTH_RESPONSE (expected 200)"
fi

echo "✅ Production prep complete"
echo "📋 Final checklist:"
echo "  • Verify HTTPS is enforced at load balancer"
echo "  • Confirm CORS headers allow only trusted origins"
echo "  • Set LOG_LEVEL=error in production .env"
echo "  • Enable monitoring for /admin/health endpoint"
echo "  • Schedule exchange rate sync job (if using external API)"
