#!/bin/bash
set -euo pipefail

# =============================================================================
# deploy.sh — Deploy CrediData to VPS
# =============================================================================
# Usage: ./deploy.sh
# Run this from the LOCAL machine after pushing code to main.
# It SSHs into the VPS, pulls the latest code, and restarts services.
# =============================================================================

# --- Configuration (edit these) ---
VPS_HOST="${DEPLOY_HOST:?Set DEPLOY_HOST env var (e.g. user@1.2.3.4)}"
VPS_PATH="/var/www/credidata"
BRANCH="${DEPLOY_BRANCH:-main}"
PHP="php8.4"

echo "==> Deploying $BRANCH to $VPS_HOST"

# 1. SSH into VPS and deploy
ssh "$VPS_HOST" bash <<REMOTE
set -euo pipefail

# --- Deploy lock (atomic mkdir) ---
LOCK="/tmp/credidata-deploy.lock"
if ! mkdir "\$LOCK" 2>/dev/null; then
    echo "ERROR: Deploy already in progress. Aborting."
    exit 1
fi
trap 'rm -rf "\$LOCK"' EXIT

cd $VPS_PATH

echo "==> Pulling latest code..."
git fetch origin
git reset --hard "origin/$BRANCH"
git clean -fd

echo "==> Installing composer dependencies..."
composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress

echo "==> Installing npm dependencies and building assets..."
npm ci --ignore-scripts
npm run build

echo "==> Installing and validating Nginx configuration..."
sudo install -o root -g root -m 644 deploy/nginx/credidata.conf /etc/nginx/sites-available/credidata
sudo nginx -t

echo "==> Entering maintenance mode..."
$PHP artisan down --render="errors::503" --retry=60

echo "==> Running database migrations..."
$PHP artisan migrate --force

echo "==> Caching configuration..."
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
$PHP artisan event:cache

echo "==> Exiting maintenance mode..."
$PHP artisan up

echo "==> Setting permissions..."
sudo chown -R deploy:www-data $VPS_PATH/storage $VPS_PATH/bootstrap/cache
sudo chmod -R 775 $VPS_PATH/storage $VPS_PATH/bootstrap/cache
sudo chown deploy:www-data $VPS_PATH/.env
sudo chmod 640 $VPS_PATH/.env

echo "==> Restarting services..."
sudo supervisorctl restart horizon
sudo systemctl reload ${PHP}-fpm
sudo systemctl reload nginx

echo "==> Running health check..."
sleep 2
HTTP_CODE=\$(curl -s -o /dev/null -w "%{http_code}" http://localhost/ || echo "000")
if [ "\$HTTP_CODE" != "200" ] && [ "\$HTTP_CODE" != "302" ]; then
    echo "WARNING: Health check returned HTTP \$HTTP_CODE"
    echo "The app may not be responding correctly."
    exit 1
fi

echo "==> Deploy complete! \$(date)"
REMOTE

echo "==> Deploy finished successfully."
