#!/bin/bash
# Forge deployment script for v01.13

# Exit on error
set -e

# Set directory permissions
echo "Setting directory permissions..."
chmod -R 775 storage bootstrap/cache

# Install Composer dependencies
echo "Installing Composer dependencies..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Install NPM dependencies and build assets
echo "Installing NPM dependencies..."
npm ci
echo "Building assets for production..."
npm run build-production

# Clear application cache
echo "Clearing application cache..."
php artisan optimize:clear

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Cache routes and config for better performance
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue worker
echo "Restarting queue worker..."
php artisan queue:restart

echo "Deployment completed successfully!" 