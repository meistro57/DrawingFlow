# AGENTS.md

## Repository Snapshot

- **Project**: DrawingFlow
- **Type**: Laravel 12 monolith with Inertia.js + Vue 3
- **Backend**: PHP `^8.2`, Laravel `^12.0`
- **Frontend**: Vite 7, Vue 3, Inertia Vue 2, Tailwind CSS 4
- **Infrastructure in repo**: Docker Compose services for `app`, `nginx`, `mysql`, `redis`, `meilisearch`, `mailhog`
- **Runtime model**: Docker app (container-first local development)
- **Primary source roots**: `app/`, `routes/`, `resources/js/`, `database/`, `tests/`

This repository is not empty and contains active application code.

---

## Rule Files Checked

Checked these user-requested locations:

- `.cursor/rules/*.md` → not found
- `.cursorrules` → not found
- `.github/copilot-instructions.md` → not found
- `claude.md` → not found
- `agents.md` (lowercase) → not found
- `AGENTS.md` (this file) → found

No additional rule files were discovered in those locations.

---

## Essential Commands (Observed)

## Composer scripts (`composer.json`)

```bash
composer setup
composer dev
composer test
composer lint
composer lint:fix
composer analyse
```

### Script behavior

- `composer setup`
  - `composer install`
  - create `.env` from `.env.example` if missing
  - `php artisan key:generate`
  - `php artisan migrate --force`
  - `npm install`
  - `npm run build`
- `composer dev`
  - runs concurrently: `php artisan serve`, `php artisan queue:listen --tries=1 --timeout=0`, `php artisan pail --timeout=0`, `npm run dev`
- `composer test`
  - `php artisan config:clear --ansi`
  - `php artisan test`
- `composer lint`
  - `vendor/bin/pint --test`
  - `vendor/bin/phpstan analyse --no-progress --memory-limit=1G`
- `composer lint:fix`
  - `vendor/bin/pint`
- `composer analyse`
  - `vendor/bin/phpstan analyse --no-progress --memory-limit=1G`

## Frontend scripts (`package.json`)

```bash
npm run dev
npm run build
npm run lint
npm run lint:check
npm run format
npm run format:check
```

- ESLint target: `resources/js`
- Prettier target: `resources/js/**/*.{js,vue}`

## Docker / container workflow (`docker-compose.yml`)

```bash
docker compose up -d
docker compose down
docker compose exec app composer install
docker compose exec app php artisan migrate
docker compose exec app php artisan test
```

Published endpoints/ports from compose:

- App (nginx): `http://localhost` (port 80)
- Mailhog UI: `http://localhost:8025`
- MySQL: `localhost:3306`
- Redis: `localhost:6379`
- Meilisearch: `localhost:7700`

## Utility scripts

- `start-and-test.sh`
  - restarts containers, waits for MySQL, runs migrations, then runs `php artisan test --testsuite=Unit`
- `dev-utility.sh`
  - interactive menu wrapper for common Docker/Sail, artisan, composer, npm, lint, test, and import commands

## Domain-specific command

```bash
php artisan data:import-legacy-csv
```

- Implemented by `app/Console/Commands/ImportLegacyCsvData.php`
- Reads these CSV files from project root:
  - `Shop Drawing Request.csv`
  - `Drawing Submittal Log.csv`
  - `Fabrication Drawing Log.csv`

---

## Code Organization

## Backend

- `app/Http/Controllers/` → HTTP controllers (auth, dashboard, customers, projects, submittals, fab queue, admin)
- `app/Http/Requests/` → FormRequest validation classes
- `app/Models/` → Eloquent models for domain entities
- `app/Services/` → business logic services (`DrawingRequestService`, `SubmittalService`, `FabHandoffService`, etc.)
- `app/Console/Commands/` → custom artisan command(s), currently legacy CSV import
- `routes/web.php` → all web routes (guest/auth/admin)
- `app/Providers/AppServiceProvider.php` → explicit route model bindings and admin gate

## Frontend

- `resources/js/app.js` bootstraps Inertia app with Pinia + Ziggy
- `resources/js/Layouts/` contains layout shells
- `resources/js/Pages/` domain pages (`Dashboard`, `DrawingRequests`, `Submittals`, `FabQueue`, `Projects`, `Customers`, `Admin`, `Profile`, `Auth`)
- `resources/js/Components/` shared UI; includes large `PdfMarkupWorkspace.vue`

## Data layer

- `database/migrations/` includes Laravel defaults plus workflow tables
- PDF review tables and follow-up enum migrations are present:
  - `create_pdf_markups_table`
  - `expand_pdf_markup_types`
  - `expand_pdf_markup_types_for_paths`
  - `create_pdf_page_scales_table`
- `database/seeders/DatabaseSeeder.php` seeds two users, customers, projects, and customer workflows

## Tests

- `tests/Feature/` includes domain and UI workflow coverage
- `tests/Feature/Admin/` has backup and user management tests
- `tests/Feature/SubmittalPdfMarkupTest.php` covers PDF file access, markup CRUD/import/export, and page scales
- `tests/Unit/` exists (minimal baseline test present)
- `phpunit.xml` uses in-memory SQLite (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`)

---

## Patterns and Conventions Observed

## Laravel / backend patterns

- Controllers are generally thin and delegate business operations to service classes.
- Multi-step writes are wrapped in `DB::transaction()` in services/commands.
- Validation uses FormRequest classes and route-level validation behavior.
- Models commonly define:
  - typed relationship methods
  - query scopes (for status/priority/etc.)
  - helper methods/accessors (e.g., status labels)
- Several models use `protected function casts(): array` rather than `$casts` property.

## Inertia / Vue patterns

- Vue SFCs use `<script setup>` + Composition API.
- Inertia page resolution uses `resolvePageComponent` with `import.meta.glob('./Pages/**/*.vue')`.
- Shared Inertia props in `HandleInertiaRequests` include:
  - `auth.user` with flattened fields
  - `auth.user.notification_unread_count`
  - `ziggy`
  - `flash.success` / `flash.error`

## Routing and naming patterns

- Named routes are used throughout.
- Admin routes use `admin.` prefix and `can:admin-access` middleware.
- Explicit route model bindings exist for `submittal` and `fab_queue`; preserve these parameter names.
- Numbering formats in domain logic/docs:
  - Drawing requests: `DR-YYYY-####`
  - Submittals: `SUB-YYYY-####`
  - Fab queue: `FAB-YYYY-####`

## PDF workspace domain pattern

- `PdfMarkupWorkspace.vue` supports tools: `circle`, `rectangle`, `cloud`, `pen`, `polyline`, `polygon`, `arrow`, `dimension`, `text`, `highlight`, `stamp`.
- Backend routes include markup CRUD, import/export, and page-scale CRUD under `submittals/{submittal}/files/{submittalFile}/...`.
- Tests validate markup type restrictions and shape-specific payload requirements.

---

## Style and Tooling Conventions

From config files:

- `.editorconfig`
  - default: 4 spaces, LF, UTF-8, trim trailing whitespace, final newline
  - JS/Vue: 2 spaces
  - YAML: 2 spaces
- `.prettierrc`
  - 2 spaces, single quotes, semicolons, trailing commas (`es5`), print width 100
- `eslint.config.js`
  - flat config
  - ignores: `public/build/**`, `vendor/**`, `resources/js/ziggy.js`
  - Vue recommended flat config with multiple Vue formatting rules disabled

---

## Testing Guidance (Observed)

Preferred in this repo (Docker app runtime):

```bash
docker compose exec app php artisan test
docker compose exec app php artisan test --testsuite=Feature
docker compose exec app php artisan test --testsuite=Unit
docker compose exec app php artisan test tests/Feature/Profile/ProfileManagementTest.php
```

Host equivalents also exist (`php artisan ...`) but depend on compatible local PHP/composer platform requirements.

Observed patterns in tests:

- Feature tests often use `RefreshDatabase`.
- File-heavy tests use `Storage::fake(...)` and `UploadedFile::fake()`.
- Submittal PDF tests use JSON route assertions and database assertions for workflow behavior.

---

## Important Gotchas

1. **No additional rule files in requested locations**
   - Only `AGENTS.md` exists among the specified agent-rule paths.

2. **No repository CI workflow config observed**
   - `.github/workflows/` was not found; do not assume CI checks run remotely.

3. **Route-model binding depends on non-standard parameter names**
   - `submittal` and `fab_queue` bindings are explicit in `AppServiceProvider`; changing param names can break resolution.

4. **PDF markup types were extended through follow-up migrations**
   - Enum changes are represented by newer forward migrations; preserve this pattern when adding new types.

5. **Submittal-file routes enforce ownership relationships**
   - Tests verify a file/markup must belong to the routed submittal/submittal file context.

6. **Legacy import command expects exact CSV filenames at repo root**
   - Missing/renamed files will break import.

7. **Host PHP can mismatch composer platform requirements**
   - Observed locally: host `php artisan test` fails with Composer platform check while `docker compose exec app php artisan test --testsuite=Unit` passes.
   - Prefer running artisan/composer via Docker container in this project.

8. **Boost MCP tooling is environment-gated**
   - `laravel/boost` MCP registration depends on local/debug context in `BoostServiceProvider::shouldRun()`.
   - Running Boost MCP commands may require `APP_ENV=local` and `APP_DEBUG=true` in container executions.

---

## Practical Notes for Future Agents

- Prefer service-layer changes (`app/Services`) for business state transitions.
- Keep controllers thin and return named-route redirects / Inertia responses consistent with existing patterns.
- Reuse existing status vocabulary and route names to avoid breaking filters/UI badges/tests.
- For dashboard, notifications, and profile work, preserve shared Inertia payload shape in `HandleInertiaRequests`.
- For PDF features, keep frontend tool names, backend validation, database enum values, and tests aligned.
- For calibration/measurement features, use page-scale routes/data (`page_number`) rather than embedding scale assumptions in individual markups.
- Customers and projects enforce US state abbreviations (`app/Support/UsStates.php`) via FormRequests; keep form dropdown options and validation list aligned.
- Project attachments now include a delete path (`projects.attachments.destroy`) and should remove both DB records and stored files.
- Admin Boost log viewer merges browser logs with MCP status-check events and supports reset via admin routes.

## Operator Preferences (3/17/2026)

- Rebuild the app and clear cache after changes.
- Never perform changes that risk existing production data, especially user passwords.
