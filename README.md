# Payout Management System

Laravel + Inertia.js + Vue 3 app for managing payouts, employees, and users.

## Requirements

- PHP 8.3+
- Composer
- Node.js + npm
- MySQL

## Database Setup

Create a MySQL database and update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=payout_management_system
DB_USERNAME=your_db_name
DB_PASSWORD=your_password
```

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
npm install
npm run build
```

Seed permissions and roles:

```bash
php artisan db:seed --class=CompletePermissionSeeder
```

## Run

```bash
composer run dev
```


## Default Users

| Email | Password | Role |
|-------|----------|------|
| admin@example.com | password | Admin |
| staff@example.com | password | Staff |

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


