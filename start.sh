#!/usr/bin/env bash
set -e

# ─── 1. Skip local MariaDB — app uses Replit-managed PostgreSQL (heliumdb) ──
# Real DB credentials are injected by Replit via environment variables:
#   DB_CONNECTION=pgsql, DB_HOST=helium, DB_PORT=5432,
#   DB_DATABASE=heliumdb, DB_USERNAME=postgres, DB_PASSWORD=...
echo "[start] Using Replit-managed PostgreSQL (helium/heliumdb)"

# ─── 2. Bootstrap .env ──────────────────────────────────────────────────────
if [ ! -f .env ]; then
    echo "[start] Creating .env from .env.example..."
    cp .env.example .env

    # Use Replit Postgres (env vars injected by Replit take precedence over .env,
    # but write them here too so artisan commands pick them up correctly)
    sed -i 's|^DB_CONNECTION=.*|DB_CONNECTION=pgsql|' .env
    sed -i "s|^DB_HOST=.*|DB_HOST=${DB_HOST:-helium}|" .env
    sed -i "s|^DB_PORT=.*|DB_PORT=${DB_PORT:-5432}|" .env
    sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE:-heliumdb}|" .env
    sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME:-postgres}|" .env
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD:-password}|" .env

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
