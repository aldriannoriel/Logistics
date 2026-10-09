# Logistics Web and API

This folder contains the Laravel application for the Logistics Courier project: the sorting-center web portal, JSON API, database migrations, tests, and frontend assets.

## Requirements

- PHP 8.3 or later with PDO MySQL enabled
- Composer
- MySQL 8 or compatible
- Node.js and npm (use a current Node.js LTS release)

## Local setup

Run these commands from this folder:

```powershell
Copy-Item .env.example .env
composer install
npm ci
php artisan key:generate
```

Create a MySQL database:

```sql
CREATE DATABASE logistics_courier_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Update the `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` values in `.env`. Then run:

```powershell
php artisan config:clear
php artisan migrate
php artisan storage:link
npm run build
```

Start the web application:

```powershell
php artisan serve
```

Open `http://127.0.0.1:8000`. Sorting-center registration and sign-in are at `/sorting-center/register` and `/sorting-center/login`.

Never copy a teammate's `.env` or database backup into Git. Each developer should use their own local credentials and database.

## Tests

Run the Laravel feature and unit tests with:

```powershell
php artisan test
```

The test configuration uses SQLite in memory, so PHP must have the `pdo_sqlite` extension enabled in addition to `pdo_mysql`.

## Main Laravel folders

| Path | Purpose |
| --- | --- |
| `app/Http/Controllers` | Web/API request handling |
| `app/Http/Middleware` | Authentication and approval checks |
| `app/Models` | Eloquent data models |
| `app/Services` | Shared business logic |
| `database/migrations` | Database schema changes |
| `database/seeders` | Optional local/demo data |
| `resources/js/components` | Sorting-center Vue screens |
| `resources/views/sorting-center` | Blade hosts for Vue screens |
| `routes/api.php` | Rider and sorting-center API routes |
| `routes/web.php` | Portal routes |
| `tests/Feature` | End-to-end backend behavior tests |

Registration documents are saved under `storage/app/public` and served through Laravel's `public/storage` link. These files are publicly accessible, matching the previous public-bucket behavior.

## Logistics workflow notes

- Parcel order IDs are unique, and area names are unique within a sorting center.
- Parcels and pickup requests are associated with the sorting center that handles them.
- Existing parcel-to-center associations are backfilled from parcel areas where possible. Parcels without an area remain unassigned until checked in.
- Rider applications select an approved sorting center. Each center's rider queue and approval actions are scoped to its own applicants.
- Existing riders are backfilled from assigned areas when the center is unambiguous; ambiguous legacy riders remain unassigned for manual review.
