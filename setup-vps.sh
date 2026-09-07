#!/bin/bash
set -euo pipefail

# =============================================================================
# setup-vps.sh — Provision a fresh Ubuntu 24.04 VPS for CrediData
# =============================================================================
# Run as root on a fresh Digital Ocean droplet (Ubuntu 24.04, 2GB+ RAM).
# Usage: sudo bash setup-vps.sh
# =============================================================================

APP_NAME="credidata"
APP_USER="deploy"
APP_PATH="/var/www/$APP_NAME"
PHP_VERSION="8.4"
MYSQL_DB="$APP_NAME"
MYSQL_USER="$APP_NAME"
CRED_FILE="/root/.mysql_credentials"
REDIS_PASS_FILE="/root/.redis_password"

# --- Resolve public IP once ---
PUBLIC_IP=$(curl -s --max-time 5 ifconfig.me || echo "UNKNOWN")

# --- Load or generate passwords (idempotent) ---
if [ -f "$CRED_FILE" ]; then
    MYSQL_PASSWORD=$(grep 'Password:' "$CRED_FILE" | awk '{print $2}')
    echo "==> Loaded existing MySQL password from $CRED_FILE"
else
    MYSQL_PASSWORD=$(openssl rand -base64 24)
    echo "==> Generated new MySQL password"
fi

if [ -f "$REDIS_PASS_FILE" ]; then
    REDIS_PASSWORD=$(cat "$REDIS_PASS_FILE")
    echo "==> Loaded existing Redis password from $REDIS_PASS_FILE"
else
    REDIS_PASSWORD=$(openssl rand -base64 24)
    echo "==> Generated new Redis password"
fi

echo "============================================"
echo "  CrediData VPS Setup"
echo "  This will install and configure:"
echo "  - Nginx"
echo "  - PHP $PHP_VERSION + FPM"
echo "  - MySQL 8"
echo "  - Redis"
echo "  - Supervisor"
echo "  - Node.js 22"
echo "  - Composer"
echo "============================================"
echo ""
echo "MySQL credentials will be saved to: $CRED_FILE"
echo "Redis password will be saved to:    $REDIS_PASS_FILE"
echo ""

# --- 1. System Update & Base Packages ---
echo "==> Updating system packages..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq
apt-get install -y -qq \
    software-properties-common \
    curl \
    wget \
    git \
    unzip \
    ufw \
    fail2ban \
    logrotate \
    build-essential \
    ca-certificates \
    gnupg \
    lsb-release \
    acl

# --- 2. Create Deploy User ---
echo "==> Ensuring user '$APP_USER' exists..."
if ! id "$APP_USER" &>/dev/null; then
    adduser --disabled-password --gecos "" "$APP_USER"
    usermod -aG sudo "$APP_USER"
fi

# www-data must be in deploy group to write runtime dirs (idempotent)
usermod -aG "$APP_USER" www-data

# Sudoers: one rule per line (no backslash continuations — sudoers
# preserves leading whitespace on continuation lines, breaking the rule).
# Idempotent: regenerated every run.
echo "==> Writing sudoers for '$APP_USER'..."
cat > "/etc/sudoers.d/$APP_USER" <<SUDOERS
# CrediData deploy user — scoped to service restarts only
${APP_USER} ALL=(root) NOPASSWD: /usr/bin/systemctl reload nginx
${APP_USER} ALL=(root) NOPASSWD: /usr/bin/install -o root -g root -m 644 /var/www/credidata/deploy/nginx/credidata.conf /etc/nginx/sites-available/credidata
${APP_USER} ALL=(root) NOPASSWD: /usr/sbin/nginx -t
${APP_USER} ALL=(root) NOPASSWD: /usr/bin/systemctl reload php${PHP_VERSION}-fpm
${APP_USER} ALL=(root) NOPASSWD: /usr/bin/supervisorctl restart horizon
${APP_USER} ALL=(root) NOPASSWD: /usr/bin/supervisorctl reread
${APP_USER} ALL=(root) NOPASSWD: /usr/bin/supervisorctl update
SUDOERS
chmod 0440 "/etc/sudoers.d/$APP_USER"
visudo -cf "/etc/sudoers.d/$APP_USER" || {
    echo "ERROR: Invalid sudoers syntax. Removing file."
    rm -f "/etc/sudoers.d/$APP_USER"
    exit 1
}

# --- 3. Swap (1GB) ---
echo "==> Configuring swap..."
if [ ! -f /swapfile ]; then
    fallocate -l 1G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
    sysctl vm.swappiness=10
    echo 'vm.swappiness=10' >> /etc/sysctl.conf
fi

# --- 4. PHP ---
echo "==> Installing PHP $PHP_VERSION..."
add-apt-repository -y ppa:ondrej/php > /dev/null 2>&1
apt-get update -qq
apt-get install -y -qq \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-redis \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-soap \
    php${PHP_VERSION}-swoole \
    php${PHP_VERSION}-grpc

# Configure PHP-FPM
# NOTE: pm.max_children=20 is tight on 2GB RAM + MySQL + Redis.
# Tune after first deploy with: free -m
sed -i 's/^;clear_env = .*/clear_env = no/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
sed -i 's/^pm\.max_children = .*/pm.max_children = 20/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
sed -i 's/^pm\.start_servers = .*/pm.start_servers = 5/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
sed -i 's/^pm\.min_spare_servers = .*/pm.min_spare_servers = 3/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
sed -i 's/^pm\.max_spare_servers = .*/pm.max_spare_servers = 10/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 20M/' /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i 's/^post_max_size = .*/post_max_size = 25M/' /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i 's/^max_execution_time = .*/max_execution_time = 120/' /etc/php/${PHP_VERSION}/fpm/php.ini

# --- 5. MySQL ---
echo "==> Installing MySQL..."
apt-get install -y -qq mysql-server
mysql -e "CREATE DATABASE IF NOT EXISTS \`${MYSQL_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'127.0.0.1' IDENTIFIED BY '${MYSQL_PASSWORD}';"
mysql -e "ALTER USER '${MYSQL_USER}'@'127.0.0.1' IDENTIFIED BY '${MYSQL_PASSWORD}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${MYSQL_DB}\`.* TO '${MYSQL_USER}'@'127.0.0.1';"
mysql -e "FLUSH PRIVILEGES;"

# Save credentials (only on first run)
if [ ! -f "$CRED_FILE" ]; then
    cat > "$CRED_FILE" <<EOF
Host:     127.0.0.1
Database: ${MYSQL_DB}
User:     ${MYSQL_USER}
Password: ${MYSQL_PASSWORD}
EOF
    chmod 600 "$CRED_FILE"
fi

# --- 6. Redis ---
echo "==> Installing Redis..."
apt-get install -y -qq redis-server
# Replace any existing requirepass line (handles both commented and uncommented)
sed -i "s/^#* requirepass .*/requirepass ${REDIS_PASSWORD}/" /etc/redis/redis.conf
# Ensure the line exists if no match was found
if ! grep -q "^requirepass " /etc/redis/redis.conf; then
    echo "requirepass ${REDIS_PASSWORD}" >> /etc/redis/redis.conf
fi
systemctl restart redis-server

# Save password (only on first run)
if [ ! -f "$REDIS_PASS_FILE" ]; then
    echo "$REDIS_PASSWORD" > "$REDIS_PASS_FILE"
    chmod 600 "$REDIS_PASS_FILE"
fi

# --- 7. Nginx ---
echo "==> Installing Nginx..."
apt-get install -y -qq nginx
systemctl enable nginx

# --- 8. Node.js 22 ---
echo "==> Installing Node.js 22..."
curl -fsSL https://deb.nodesource.com/setup_22.x | bash - > /dev/null 2>&1
apt-get install -y -qq nodejs

# --- 9. Composer ---
echo "==> Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# --- 10. Supervisor ---
echo "==> Installing Supervisor..."
apt-get install -y -qq supervisor
systemctl enable supervisor

# --- 11. Firewall ---
echo "==> Configuring UFW firewall..."
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# --- 12. Fail2ban ---
echo "==> Configuring Fail2ban..."
systemctl enable fail2ban

# --- 13. Clone Application ---
echo "==> Setting up application directory..."
mkdir -p "$APP_PATH"
chown "$APP_USER:$APP_USER" "$APP_PATH"

# Ensure SSH deploy key exists for cloning (idempotent)
APP_HOME=$(eval echo "~${APP_USER}")
sudo -H -u "$APP_USER" bash -c '
    mkdir -p ~/.ssh
    chmod 700 ~/.ssh
    if [ ! -f ~/.ssh/id_ed25519 ]; then
        ssh-keygen -t ed25519 -N "" -C "credidata-$(hostname)-deploy" -f ~/.ssh/id_ed25519
    fi
    touch ~/.ssh/authorized_keys
    chmod 600 ~/.ssh/authorized_keys
    touch ~/.ssh/known_hosts
    chmod 600 ~/.ssh/known_hosts
    if ! grep -q "github.com" ~/.ssh/known_hosts; then
        for i in 1 2 3; do
            ssh-keyscan -t rsa,ed25519 github.com >> ~/.ssh/known_hosts 2>/dev/null && break
            sleep 2
        done
        grep -q "github.com" ~/.ssh/known_hosts || {
            echo "ERROR: could not fetch GitHub host key after 3 tries." >&2
            exit 1
        }
    fi
'

GIT_REPO="${GIT_REPO:-git@github.com:multialejo/credidata.git}"

if [ -d "$APP_PATH/.git" ]; then
    echo "  App repo already cloned at $APP_PATH — skipping clone."
else
    # Only show the deploy-key banner when we actually need it
    DEPLOY_PUB=$(sudo -H -u "$APP_USER" cat "$APP_HOME/.ssh/id_ed25519.pub")
    echo ""
    echo "============================================"
    echo "  Add this deploy key on GitHub"
    echo "  Settings → Deploy keys → Add deploy key"
    echo "  Title: credidata-$(hostname)-deploy"
    echo "  Read-only: YES"
    echo "============================================"
    echo ""
    echo "  $DEPLOY_PUB"
    echo ""
    echo "============================================"
    echo ""

    sudo -H -u "$APP_USER" env GIT_SSH_COMMAND="ssh -o StrictHostKeyChecking=accept-new" \
        git clone "$GIT_REPO" "$APP_PATH" || {
        echo ""
        echo "ERROR: git clone failed for $GIT_REPO" >&2
        echo "  The deploy key may not be registered yet." >&2
        echo "  Add the key above on GitHub, then re-run this script." >&2
        echo "  (Re-running is safe — clone will be retried, config is idempotent.)" >&2
        echo "" >&2
        exit 1
    }
fi

# --- 14-16. Config & Setup (only if repo was cloned) ---
if [ -d "$APP_PATH/.git" ]; then

    # --- 14. Deploy Configs ---
    echo "==> Deploying Nginx config..."
    cp "$APP_PATH/deploy/nginx/credidata.conf" /etc/nginx/sites-available/credidata
    ln -sf /etc/nginx/sites-available/credidata /etc/nginx/sites-enabled/credidata
    rm -f /etc/nginx/sites-enabled/default

    # Update nginx config with correct PHP version
    sed -i "s|php8.3|php${PHP_VERSION}|g" /etc/nginx/sites-available/credidata

    echo "==> Deploying Supervisor configs..."
    cp "$APP_PATH/deploy/supervisor/horizon.conf" /etc/supervisor/conf.d/horizon.conf

    # Update supervisor config with correct PHP version
    sed -i "s|php8.3|php${PHP_VERSION}|g" /etc/supervisor/conf.d/horizon.conf

    # --- 15. Initial Setup ---
    echo "==> Running initial setup..."
    cd "$APP_PATH"

    # Install dependencies
    sudo -u "$APP_USER" composer install --no-dev --optimize-autoloader --no-interaction
    sudo -u "$APP_USER" npm ci --ignore-scripts
    sudo -u "$APP_USER" npm run build

    # Generate .env if not exists
    if [ ! -f "$APP_PATH/.env" ]; then
        sudo -u "$APP_USER" cp .env.example .env
        sudo -u "$APP_USER" php${PHP_VERSION} artisan key:generate
    fi

    # Build .env for production (only if not already appended)
    if ! grep -q "# === PRODUCTION SETTINGS" "$APP_PATH/.env" 2>/dev/null; then
        cat >> "$APP_PATH/.env" <<ENVEOF

# === PRODUCTION SETTINGS (added by setup-vps.sh) ===
APP_ENV=production
APP_DEBUG=false
APP_URL=http://${PUBLIC_IP}

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${MYSQL_DB}
DB_USERNAME=${MYSQL_USER}
DB_PASSWORD=${MYSQL_PASSWORD}

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=${REDIS_PASSWORD}
REDIS_PORT=6379

# Sessions & Cache
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# Sanctum
SANCTUM_STATEFUL_DOMAINS=${PUBLIC_IP}
ENVEOF
    fi

    # Ensure runtime dirs exist that git doesn't track (scp can't create them)
    mkdir -p "$APP_PATH/storage/app/firebase"

    # Set permissions — deploy owns everything, www-data gets group write via ACL
    chown -R deploy:deploy "$APP_PATH"
    chown -R deploy:www-data "$APP_PATH/storage" "$APP_PATH/bootstrap/cache"
    chmod -R 775 "$APP_PATH/storage" "$APP_PATH/bootstrap/cache"
    # Ensure www-data can write even for new files/dirs created by deploy
    setfacl -R -m g:www-data:rwx "$APP_PATH/storage" "$APP_PATH/bootstrap/cache"
    setfacl -R -d -m g:www-data:rwx "$APP_PATH/storage" "$APP_PATH/bootstrap/cache"
    chown deploy:www-data "$APP_PATH/.env"
    chmod 640 "$APP_PATH/.env"

    # Run migrations
    sudo -u "$APP_USER" php${PHP_VERSION} artisan migrate --force
    sudo -u "$APP_USER" php${PHP_VERSION} artisan config:cache
    sudo -u "$APP_USER" php${PHP_VERSION} artisan route:cache
    sudo -u "$APP_USER" php${PHP_VERSION} artisan view:cache

else
    echo "  Skipping config deploy — repo not cloned."
    echo "  Set GIT_REPO and re-run this script after cloning."
fi

# --- 16. Restart Services ---
echo "==> Restarting services..."
systemctl reload nginx
systemctl restart php${PHP_VERSION}-fpm
supervisorctl reread
supervisorctl update

echo ""
echo "============================================"
echo "  Setup Complete!"
echo "============================================"
echo ""
echo "  App URL:  http://${PUBLIC_IP}"
echo "  App path: $APP_PATH"
echo ""
echo "  MySQL credentials: $CRED_FILE"
echo "  Redis password:    $REDIS_PASS_FILE"
echo ""
echo "  NEXT STEPS:"
echo "  1. Edit $APP_PATH/.env with your Firebase, PayPal, Payphone credentials"
echo "  2. Deploy the Firebase service account JSON:"
echo "     scp service-account.json $APP_USER@${PUBLIC_IP}:$APP_PATH/storage/app/firebase/"
echo "  3. Restart Horizon: sudo supervisorctl restart horizon"
echo "  4. Test the app at http://${PUBLIC_IP}"
echo ""
echo "  OPTIONAL: If you have a domain pointed to this VPS, run:"
echo "    apt-get install -y certbot python3-certbot-nginx"
echo "    certbot --nginx -d yourdomain.com"
echo ""
echo "  To deploy updates later, run from your local machine:"
echo "    DEPLOY_HOST=$APP_USER@${PUBLIC_IP} ./deploy.sh"
echo ""
