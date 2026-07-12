#!/usr/bin/env bash
set -e

DATADIR="$(pwd)/.mysql/data"

# ─── 1. Start MariaDB if not already running ────────────────────────────────
if ! mysqladmin -u root --socket=/tmp/mysql.sock status 2>/dev/null; then
    echo "[start] Starting MariaDB..."
    rm -f /tmp/mysql.sock /tmp/mysql.pid

    mysqld \
        --datadir="$DATADIR" \
        --socket=/tmp/mysql.sock \
        --pid-file=/tmp/mysql.pid \
        --port=3306 \
        --user=runner \
        --bind-address=127.0.0.1 \
        --innodb-use-native-aio=0 \
        --log-error="$DATADIR/mysql.err" &

    # Wait up to 30 s for MariaDB to accept connections
    for i in $(seq 1 30); do
        sleep 1
        if mysqladmin -u root --socket=/tmp/mysql.sock status 2>/dev/null; then
            echo "[start] MariaDB ready (${i}s)"
            break
        fi
        if [ "$i" -eq 30 ]; then
            echo "[start] ERROR: MariaDB did not start in time"
            cat "$DATADIR/mysql.err" | tail -20
            exit 1
        fi
    done
else
    echo "[start] MariaDB already running"
fi

# ─── 2. Create database / user if missing ───────────────────────────────────
mysql -u root --socket=/tmp/mysql.sock 2>/dev/null <<'SQL'
CREATE DATABASE IF NOT EXISTS bagisto CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'bagisto'@'localhost' IDENTIFIED BY 'bagisto_pass';
GRANT ALL PRIVILEGES ON bagisto.* TO 'bagisto'@'localhost';
FLUSH PRIVILEGES;
SQL
echo "[start] Database ready"

# ─── 3. Bootstrap .env ──────────────────────────────────────────────────────
if [ ! -f .env ]; then
    echo "[start] Creating .env from .env.example..."
    cp .env.example .env

    # Set DB credentials
    sed -i 's|^DB_CONNECTION=.*|DB_CONNECTION=mysql|' .env
    sed -i 's|^DB_HOST=.*|DB_HOST=127.0.0.1|' .env
    sed -i 's|^DB_PORT=.*|DB_PORT=3306|' .env
    sed -i 's|^DB_DATABASE=.*|DB_DATABASE=bagisto|' .env
    sed -i 's|^DB_USERNAME=.*|DB_USERNAME=bagisto|' .env
    sed -i 's|^DB_PASSWORD=.*|DB_PASSWORD=bagisto_pass|' .env

    # Use file-based sessions (safer before first migration)
    sed -i 's|^SESSION_DRIVER=.*|SESSION_DRIVER=file|' .env

    # Cache and queue — file-based to avoid Redis dependency
    sed -i 's|^CACHE_STORE=.*|CACHE_STORE=file|' .env
    sed -i 's|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=sync|' .env

    # Set APP_URL from environment if available
    if [ -n "$APP_URL" ]; then
        sed -i "s|^APP_URL=.*|APP_URL=${APP_URL}|" .env
    fi

    echo "[start] .env created"
fi

# ─── 3b. Install PHP dependencies if vendor is missing ──────────────────────
if [ ! -f vendor/autoload.php ]; then
    echo "[start] Running composer install (first run, may take ~2 min)..."
    composer install --no-interaction --prefer-dist --optimize-autoloader 2>&1 | tail -5
fi

# ─── 4. Generate APP_KEY if missing ─────────────────────────────────────────
if grep -qE '^APP_KEY=$' .env; then
    echo "[start] Generating APP_KEY..."
    php artisan key:generate --force
fi

# ─── 5. Run migrations (idempotent) ─────────────────────────────────────────
echo "[start] Running migrations..."
php artisan migrate --force 2>&1 | tail -5

# ─── 6. Run seeders only if tables are empty ────────────────────────────────
# NOTE: Replit injects real DB_CONNECTION/DB_HOST/etc. environment variables
# (pointing at the managed Postgres database) which take precedence over the
# mysql credentials written into .env above. So the app's actual database is
# NOT the local MariaDB — this check must ask Laravel/the real DB connection,
# not query MariaDB directly, or it will always see 0 rows and re-seed
# (wiping any admin/manual customizations) on every restart.
CHANNEL_COUNT=$(php -r '
require __DIR__."/vendor/autoload.php";
$app = require __DIR__."/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    echo \Illuminate\Support\Facades\Schema::hasTable("channels")
        ? \Illuminate\Support\Facades\DB::table("channels")->count()
        : 0;
} catch (\Throwable $e) {
    echo 0;
}
' 2>/dev/null || echo "0")

if [ "$CHANNEL_COUNT" -eq 0 ] 2>/dev/null; then
    echo "[start] Seeding database..."
    php artisan db:seed --force 2>&1 | tail -5
else
    echo "[start] Database already seeded (channels: $CHANNEL_COUNT)"
fi

# ─── 7. Build frontend assets if not already built ──────────────────────────
if [ ! -f public/build/manifest.json ]; then
    echo "[start] Building frontend assets (first run, may take ~2 min)..."
    npm install --silent 2>&1 | tail -3
    npm run build 2>&1 | tail -5
else
    echo "[start] Frontend assets already built"
fi

# ─── 8. Create storage symlink ───────────────────────────────────────────────
php artisan storage:link --force 2>/dev/null || true

# ─── 9. Start PHP development server ─────────────────────────────────────────
echo "[start] Starting PHP server on :5000..."
cd public
exec php -S 0.0.0.0:5000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
