# DrawingFlow
<img width="706" height="585" alt="image" src="https://github.com/user-attachments/assets/54d4bd5f-7612-4780-b12b-62b4a20e81a1" />

Steel fabrication drawing workflow system built with Laravel 12, Inertia, and Vue 3.

## Tech Stack

- PHP `^8.2`
- Laravel `^12.0`
- Inertia Laravel `^2.0`
- Vue `^3.5`
- Vite `^7`
- Tailwind CSS `^4`
- MySQL 8, Redis 7, Meilisearch, Mailhog (via Docker Compose)

## Repository Layout

```text
app/
  Http/
  Models/
  Providers/
  Services/
bootstrap/
config/
database/
  factories/
  migrations/
  seeders/
resources/
  css/
  js/
routes/
tests/
docker/
```

## Quick Start

### Option A: Composer setup script

```bash
composer setup
```

This installs PHP and Node dependencies, creates `.env` if needed, generates app key, runs migrations, and builds frontend assets.

### Option B: Docker Compose

```bash
cp .env.example .env
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
npm install
npm run dev
```

## Local Development Commands

### Composer

```bash
composer dev
composer test
composer lint
composer lint:fix
composer analyse
```

### Frontend

```bash
npm run dev
npm run build
npm run lint
npm run lint:check
npm run format
npm run format:check
```

## Testing

```bash
docker compose exec app php artisan test
docker compose exec app php artisan test --testsuite=Feature
docker compose exec app php artisan test --testsuite=Unit
docker compose exec app php artisan test tests/Feature/Profile/ProfileManagementTest.php
```

## Data Import and Backup

```bash
docker compose exec app php artisan data:import-legacy-csv
```

The importer reads:

- `Shop Drawing Request.csv`
- `Drawing Submittal Log.csv`
- `Fabrication Drawing Log.csv`

Admin users can create, restore, and download JSON backups from the **Admin > Data Backup** page.

`phpunit.xml` uses:

- `APP_ENV=testing`
- `DB_CONNECTION=sqlite`
- `DB_DATABASE=:memory:`

## Docker Services

From `docker-compose.yml`:

- App (nginx): `http://localhost`
- Mailhog UI: `http://localhost:8025`
- MySQL: `localhost:3306`
- Redis: `localhost:6379`
- Meilisearch: `localhost:7700`

## Default Seeded Users

From `database/seeders/DatabaseSeeder.php`:

- `mark@drawingflow.local` / `password`
- `detailer@drawingflow.local` / `password`

## Domain Notes

- Drawing request numbers are generated as `DR-YYYY-####`.
- Submittal numbers are generated as `SUB-YYYY-####`.
- Fab queue numbers are generated as `FAB-YYYY-####`.
- Main workflow pages are under `resources/js/Pages/`.
- Web routes are in `routes/web.php`.

## Recent Feature Updates

- **Admin backup/restore** page is available at `admin.backups.index` for JSON backups, restore upload, and backup download.
- **Legacy CSV import** command `php artisan data:import-legacy-csv` imports shop drawing requests, submittals, and fabrication queue records from the provided legacy CSV files.
- **Fab Queue and Submittals tables** now match the richer Customers table UX with quick table filters and density controls.
- **Phase 3 PDF workspace** on submittals now supports viewer + markups (circle, arrow, text, highlight, stamp).
- **Markup export** endpoint provides a JSON export for saved markups.
- **Revision comparison** is available with side-by-side PDF viewing in the submittal workspace.
- **Markup history** is shown in the workspace with author and timestamp.
- **Notification center** added to the app layout with unread badge, polling, and mark read / mark all read actions.
- **Internal submittal notes** are now available on submittal detail pages for team communication.

## Additional Project Context

- See `AGENTS.md` for agent-oriented implementation notes and project conventions.
