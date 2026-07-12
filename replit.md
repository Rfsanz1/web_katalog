# Bagisto

An open-source e-commerce platform built on **Laravel 12** (PHP 8.3+) with a **Vue.js** frontend and **Vite** for asset bundling.

## Project overview

Bagisto supports multi-vendor marketplaces, B2B platforms, and headless commerce. The codebase is highly modular — business logic lives in `packages/Webkul/`, with separate packages for the Shop storefront, Admin panel, Cart, Checkout, Product, etc.

A custom `packages/Webkul/KledoIntegration` package auto-syncs every new order to Kledo (Indonesian accounting SaaS) as an invoice via REST API.

## Stack

- **Backend:** PHP 8.4 (installed via Replit module; project requires ^8.3), Laravel 12
- **Frontend:** Vue.js, Tailwind CSS, Vite 6
- **Database:** Replit-managed PostgreSQL (`heliumdb`, connected via `DATABASE_URL`/`DB_*` Replit env vars). **Important:** Replit injects real `DB_CONNECTION=pgsql`/`DB_HOST=helium`/etc. environment variables, which take precedence over `.env`'s `DB_CONNECTION=mysql` settings (PHP dotenv does not override real env vars). So even though `start.sh` also spins up a local MariaDB and writes mysql credentials into `.env`, the running app actually reads/writes the Postgres `heliumdb` database — the local MariaDB is unused dead weight. Use `psql -h helium -U postgres -d heliumdb` (password from `PGPASSWORD`/`DB_PASSWORD` env var) to inspect real data.
- **Cache/Sessions:** File-based (default)
- **Payments:** Stripe, PayPal, Razorpay integrations included

## Key entry points

- `public/index.php` — web entry point
- `artisan` — Laravel CLI
- `routes/web.php` — base routes
- `packages/Webkul/Shop/src/Routes/` — storefront routes
- `packages/Webkul/Admin/src/Routes/` — admin panel routes
- `packages/Webkul/KledoIntegration/` — Kledo invoice sync package

## Running on Replit

The app starts automatically via `bash start.sh`. The script handles everything on first run and is idempotent on subsequent runs:

1. Starts MariaDB (local, data in `.mysql/data/`)
2. Creates `bagisto` database and user if missing
3. Copies `.env.example` → `.env` and sets DB credentials
4. Generates `APP_KEY` if blank
5. Runs `composer install` if `vendor/` is missing
6. Runs `php artisan migrate --force`
7. Seeds the database (once, checks `channels` table count)
8. Builds frontend assets via `npm run build` (once)
9. Starts PHP dev server on port 5000

### Database credentials (local MariaDB)

| Variable | Value |
|---|---|
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_DATABASE` | `bagisto` |
| `DB_USERNAME` | `bagisto` |
| `DB_PASSWORD` | `bagisto_pass` |

### Admin panel

Visit `/admin` — default seeded credentials: **admin@example.com / admin123**

### Homepage banner slider

The homepage image carousel ("Karousel Gambar") is managed via `theme_customizations` / `theme_customization_translations` (id=1, locale `id`) in the Postgres database — the same system as Admin > Settings > Theme Customization. Images live in `storage/app/public/theme/1/`. Current slides show the "GentongMas Elektronik" brand banners (2076x758px, ~2.74:1 ratio matching the theme's expected aspect ratio).

### Kledo Integration

Set these in `.env` to enable auto-invoice sync:

```
KLEDO_ACCESS_TOKEN=your_static_bearer_token
KLEDO_API_BASE_URL=https://gentongmas.api.kledo.com/api/v1
KLEDO_DUE_DAYS=30
```

Test connectivity: `php artisan kledo:test-connection`

Admin UI: `/admin/kledo` — shows sync status, per-order logs, retry button, and payment method mappings.

### Notable .env variables

| Variable | Purpose |
|---|---|
| `APP_URL` | Full public URL of the app |
| `APP_ADMIN_URL` | Admin panel path (default: `admin`) |
| `KLEDO_ACCESS_TOKEN` | Kledo static bearer token |
| `KLEDO_API_BASE_URL` | Kledo API base URL |
| `QUEUE_CONNECTION` | Set to `database` or `redis` for async queue processing |

## User preferences

<!-- Add user preferences here as you learn them -->
