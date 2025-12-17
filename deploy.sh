#!/bin/bash

# Production Deployment Script for Addy Business 2.0
# Run this script on your production server after pulling the latest code

set -e

echo "🚀 Starting production deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${RED}❌ .env file not found!${NC}"
    echo "Please copy .env.example to .env and configure it."
    exit 1
fi

# Install/Update dependencies
echo -e "${YELLOW}📦 Installing dependencies...${NC}"
composer install --optimize-autoloader --no-dev --no-interaction
npm ci --production

# Run migrations
echo -e "${YELLOW}🗄️  Running database migrations...${NC}"
php artisan migrate --force

# Clear and cache configuration
echo -e "${YELLOW}⚙️  Optimizing Laravel...${NC}"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build frontend assets
echo -e "${YELLOW}🎨 Building frontend assets...${NC}"
npm run build

# Set permissions
echo -e "${YELLOW}🔐 Setting permissions...${NC}"
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || echo "Note: Could not change ownership (may need sudo)"

# Create storage link if it doesn't exist
if [ ! -L public/storage ]; then
    echo -e "${YELLOW}🔗 Creating storage symlink...${NC}"
    php artisan storage:link
fi

# Clear application cache
php artisan cache:clear

echo -e "${GREEN}✅ Deployment complete!${NC}"
echo ""
echo "📋 Next steps:"
echo "1. Change the default admin password"
echo "2. Configure AI provider in System Settings"
echo "3. Set up queue workers (Supervisor)"
echo "4. Configure cron for scheduled tasks"
echo "5. Test the application"
echo ""
echo "See PRODUCTION_DEPLOYMENT.md for detailed instructions."

