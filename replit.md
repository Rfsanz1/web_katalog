# Bagisto

An open-source e-commerce platform built on **Laravel 12** (PHP 8.3+) with a **Vue.js** frontend and **Vite** for asset bundling.

## Project overview

Bagisto supports multi-vendor marketplaces, B2B platforms, and headless commerce. The codebase is highly modular — business logic lives in `packages/Webkul/`, with separate packages for the Shop storefront, Admin panel, Cart, Checkout, Product, etc.

## Stack

- **Backend:** PHP 8.3+, Laravel 12
- **Frontend:** Vue.js, Tailwind CSS, Vite 6
- **Database:** MySQL (required)
- **Cache/Sessions:** Redis (optional but recommended)
- **Search:** Elasticsearch (optional)
- **Payments:** Stripe, PayPal, Razorpay integrations included

## Key entry points

- `public/index.php` — web entry point
- `artisan` — Laravel CLI
- `routes/web.php` — base routes
- `packages/Webkul/Shop/src/Routes/` — storefront routes
- `packages/Webkul/Admin/src/Routes/` — admin panel routes

## Running locally / on Replit

### Prerequisites

1. **Database** — Replit provides a **PostgreSQL** database by default (see `.replit` env vars). Bagisto officially supports MySQL, but the Replit environment is pre-configured with PostgreSQL (`DB_CONNECTION=pgsql`). To use MySQL instead, provision an external MySQL service and update the `DB_*` env vars.
2. **PHP extensions:** `intl`, `mbstring`, `pdo_pgsql` (or `pdo_mysql` for MySQL), `openssl`, `curl`

### Setup steps

```bash
cp .env.example .env
# Edit .env: set DB_DATABASE, DB_USERNAME, DB_PASSWORD, APP_URL

composer install

php artisan key:generate
php artisan migrate --seed

npm install && npm run build
```

### Run

```bash
cd public && php -S 0.0.0.0:5000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

### Notable .env variables

| Variable | Purpose |
|---|---|
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | MySQL connection |
| `APP_URL` | Full public URL of the app |
| `APP_ADMIN_URL` | Admin panel path (default: `admin`) |
| `MAIL_MAILER` | Mailer driver (Bagisto uses a custom dynamic SMTP driver) |

## User preferences

<!-- Add user preferences here as you learn them -->
