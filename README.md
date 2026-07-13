# Payout Management System

Laravel + Inertia.js + Vue 3 app for managing payouts, employees, and users.

## Requirements

- PHP 8.3+
- Composer
- Node.js + npm
- Database (MySQL/PostgreSQL/SQLite)

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
npm install
npm run build
```

## Run

```bash
composer run dev
```

This starts the Laravel dev server. Open `http://payout-management-system.test` in your browser.

## Seed permissions and roles

```bash
php artisan db:seed --class=CompletePermissionSeeder
```

## Tests

```bash
php artisan test --compact
```

## Scripts

| Command | Description |
|---------|-------------|
| `composer run dev` | Start dev server |
| `npm run build` | Build frontend assets |
| `php artisan test --compact` | Run tests |
| `vendor/bin/pint` | Format PHP code |
| `php artisan wayfinder:generate` | Regenerate route types |
