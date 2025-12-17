#!/bin/bash

# Server Setup Script - Run this on the production server
# This script installs required dependencies and sets up the environment

set -e

echo "🚀 Setting up production server for Addy Business 2.0..."

# Update package list
echo "📦 Updating package list..."
apt-get update -y

# Install PHP and required extensions
echo "🐘 Installing PHP 8.2 and extensions..."
apt-get install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt-get update -y
apt-get install -y \
    php8.2 \
    php8.2-cli \
    php8.2-fpm \
    php8.2-mysql \
    php8.2-pgsql \
    php8.2-sqlite3 \
    php8.2-bcmath \
    php8.2-curl \
    php8.2-gd \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-zip \
    php8.2-intl \
    php8.2-redis \
    php8.2-opcache

# Install Composer
echo "📦 Installing Composer..."
if [ ! -f /usr/local/bin/composer ]; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
fi

# Install Node.js 18.x
echo "📦 Installing Node.js..."
if ! command -v node &> /dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
    apt-get install -y nodejs
fi

# Install MySQL
echo "🗄️  Installing MySQL..."
apt-get install -y mysql-server

# Install Redis
echo "🔴 Installing Redis..."
apt-get install -y redis-server

# Install Nginx
echo "🌐 Installing Nginx..."
apt-get install -y nginx

# Install Git (if not already installed)
apt-get install -y git

# Install Supervisor (for queue workers)
echo "👷 Installing Supervisor..."
apt-get install -y supervisor

# Create web directory
echo "📁 Creating web directory..."
mkdir -p /var/www
chown -R www-data:www-data /var/www

# Install additional tools
apt-get install -y \
    unzip \
    curl \
    wget \
    nano \
    htop

echo "✅ Server setup complete!"
echo ""
echo "📋 Installed versions:"
php8.2 -v
node -v
npm -v
composer --version
mysql --version
nginx -v
redis-server --version

echo ""
echo "✅ Ready for deployment!"

