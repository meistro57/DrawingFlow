# AGENTS.md

## Repository Snapshot

- **Project**: DrawingFlow
- **Type**: Laravel 12 monolith with Inertia.js + Vue 3
- **Backend**: PHP (`composer.json` requires `^8.2`), Laravel Framework `^12.0`
- **Frontend**: Vite 7, Vue 3, Inertia Vue 2, Tailwind CSS 4
- **Dev infra in repo**: Docker Compose services for `app`, `nginx`, `mysql`, `redis`, `meilisearch`, `mailhog`
- **Current source roots**: `app/`, `resources/js/`, `routes/`, `database/`, `tests/`

This repository is **not empty** and contains active application code.

---

## Rule Files Found (from requested locations)

Checked these locations:

- `.cursor/rules/*.md` → not found
- `.cursorrules` → not found
- `.github/copilot-instructions.md` → not found (`.github/` directory not present)
- `claude.md` → not found
- `agents.md` (lowercase) → not found
- `AGENTS.md` (this file) → found

No other rule files were discovered in those requested paths.

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

### What they do

- `composer setup`
  - `composer install`
  - copy `.env.example` to `.env` if missing
  - `php artisan key:generate`
  - `php artisan migrate --force`
  - `npm install`
  - `npm run build`
- `composer dev`
  - runs concurrently: `php artisan serve`, `php artisan queue:listen --tries=1 --timeout=0`, `php artisan pail --timeout=0`, `npm run dev`
- `composer test`
  - clears config then runs `php artisan test`
- `composer lint`
  - runs Pint in test mode + PHPStan
- `composer lint:fix`
  - runs Pint formatter
- `composer analyse`
  - runs PHPStan

## Frontend scripts (`package.json`)

```bash
npm run dev
npm run build
npm run lint
npm run lint:check
npm run format
npm run format:check
```

- ESLint scope: `resources/js`
- Prettier scope: `resources/js/**/*.{js,vue}`

## Docker Compose workflow (`docker-compose.yml`)

```bash
docker compose up -d
docker compose down
docker compose exec app composer install
docker compose exec app php artisan migrate
docker compose exec app php artisan test
```

Published ports:

- App (nginx): `http://localhost:8080`
- MySQL: `3306`
- Redis: `6379`
- Meilisearch: `7700`
- Mailhog UI: `http://localhost:8025`

## Helper script

`start-and-test.sh` exists and:

- brings containers down/up
- waits for MySQL
- runs migrations
- runs **Unit** test suite (`php artisan test --testsuite=Unit`)

---

## Project Structure

## Backend

- `app/Http/Controllers/`
  - Inertia controllers and auth/admin/profile controllers
- `app/Http/Requests/`
  - Form Requests, including nested folders like `Profile/` and `Admin/`
- `app/Models/`
  - Domain models: `DrawingRequest`, `DrawingSubmittal`, `FabQueue`, `ProjectAttachment`, etc.
- `app/Services/`
  - Business logic services (`DrawingRequestService`, `SubmittalService`, `FabHandoffService`, etc.)
- `app/Providers/AppServiceProvider.php`
  - explicit route-model bindings for `submittal` and `fab_queue`
  - `Gate::define('admin-access', ...)`

## Frontend

- `resources/js/app.js`
  - bootstraps Inertia app, Pinia, ZiggyVue, and page resolution via `import.meta.glob`
- `resources/js/Layouts/`
  - app shell + guest shell
- `resources/js/Pages/`
  - feature pages grouped by domain (Dashboard, DrawingRequests, Submittals, FabQueue, Projects, Customers, Admin, Profile, Auth)
- `resources/js/Components/`
  - shared UI components (e.g., `StatusBadge`, `Pagination`, `Modal`, `EmptyState`, `PdfMarkupWorkspace`)
  - `PdfMarkupWorkspace.vue` is now a substantial review surface with compare mode, import/export, delete/update, history filters, layer visibility toggles, per-page scale calibration, on-canvas editing, and path-based markup tools (`pen`, `polyline`, `polygon`)

## Routing

- `routes/web.php` has all web routes (guest + auth + admin route group)
- Uses named routes throughout; admin routes are under `admin.` prefix
- PDF review routes include markup CRUD, markup import/export, and per-page scale CRUD on `submittals/{submittal}/files/{submittalFile}/...`

## Data layer

- `database/migrations/` contains Laravel defaults + workflow domain tables + profile/avatar + notifications + submittal notes migrations
- PDF review now also includes `pdf_markups` and `pdf_page_scales` migrations, including later enum-expansion migrations for new markup types
- `database/seeders/DatabaseSeeder.php` seeds two users and sample customers/projects/workflows
- `database/factories/` includes `UserFactory` and `SubmittalNoteFactory`

## Tests

- `tests/Feature/` includes coverage for profile management, dashboard queue behavior, customer import/filtering, request validation, project attachments, admin user management, notification center, submittal notes, and PDF markup workflows
- `tests/Feature/SubmittalPdfMarkupTest.php` covers PDF file viewing, markup CRUD, import/export, page-scale persistence, and path-based markup validation
- `tests/Unit/` currently minimal (`ExampleTest`)
- `phpunit.xml` uses in-memory sqlite for tests

---

## Coding Patterns Observed

## Laravel / backend patterns

- Controllers are generally thin and delegate workflow actions to services.
- Multi-step business operations use `DB::transaction()` inside services (`app/Services/*.php`).
- Form Requests are used for validation (example: `app/Http/Requests/DrawingRequestFormRequest.php`).
- Models frequently include:
  - typed relationship methods
  - query scopes (e.g., `scopeStatus`, `scopePriority`, `queued`, `active`)
  - helper/accessor methods (`getStatusLabelAttribute`, `isApproved`, `avatarUrl`)
- Casts are defined via `casts()` methods rather than `$casts` property in multiple models.

## Inertia / Vue patterns

- Vue SFCs use `<script setup>` + Composition API.
- Page components receive props directly from controller `Inertia::render(...)` payloads.
- `HandleInertiaRequests` shares:
  - `auth.user` (flattened useful fields, including `notification_unread_count`)
  - `ziggy`
  - `flash.success` / `flash.error`
- Layout-driven pages (`<AppLayout>`) are standard for authenticated screens.

## Naming/status patterns

- Drawing request numbers: `DR-YYYY-####`
- Submittal numbers: `SUB-YYYY-####`
- Fab queue numbers: `FAB-YYYY-####`
- Status strings are snake_case and domain-specific (examples: `ready_to_submit`, `approved_as_noted`, `revise_and_resubmit`, `field_verify_required`).

---

## Style & Tooling Conventions

From observed config files:

- `.editorconfig`
  - default: 4 spaces, LF, UTF-8, final newline
  - JS/Vue: 2 spaces
  - YAML: 2 spaces
- `.prettierrc`
  - single quotes, semicolons, trailing commas (`es5`), print width 100
- `eslint.config.js`
  - Flat config
  - JS recommended + Vue recommended rules
  - `vue/multi-word-component-names` disabled

---

## Testing Conventions Observed

- PHPUnit class-based tests (`Tests\Feature\...`, `Tests\Unit\...`).
- Feature tests commonly use `RefreshDatabase`.
- Inertia responses are asserted with `Inertia\Testing\AssertableInertia`.
- Mix of factory usage and direct `Model::create()` in feature tests.
- Profile/file tests use `Storage::fake('public')` + `UploadedFile::fake()`.

Useful commands:

```bash
php artisan test
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
php artisan test tests/Feature/Profile/ProfileManagementTest.php
```

---

## Important Gotchas (Observed)

1. **Rule-file discovery paths are mostly absent**
   - The specific requested agent rule file locations are not present (except `AGENTS.md`).

2. **README contains mixed command styles**
   - Most README Docker commands use `docker compose`, but the Testing section still shows `docker-compose` examples.

3. **No CI workflows in repository**
   - `.github/workflows/` is not present; do not assume automated remote checks.

4. **Route model binding is explicit for non-standard parameter names**
   - `submittal` and `fab_queue` bindings are registered in `app/Providers/AppServiceProvider.php`; preserve parameter names in routes/controllers.

5. **Historical enum migrations have been extended over time**
   - `pdf_markups.markup_type` has follow-up MySQL enum expansion migrations. When adding new markup tools, add a new forward migration instead of relying only on edits to older migrations.

---

## Practical Agent Notes for This Repo

- Prefer adding workflow logic to `app/Services` when it changes business state across models.
- Keep controller responses Inertia-first and return named route redirects with flash messages.
- Reuse existing domain status vocabulary exactly; many UI badges and dashboard filters depend on it.
- When adding dashboard queue logic, maintain `queue_filter` whitelist handling in `app/Http/Controllers/DashboardController.php`.
- For profile/avatar behavior, keep storage disk usage consistent with `public` disk and existing `avatar_path` conventions.
- Notification dropdown polls `/notifications`; keep payload shape from `NotificationController` stable for layout consumers.
- Submittal collaboration uses `submittal_notes`; prefer `submittal->submittalNotes()->with('user')` for page hydration.
- Validate route parameter naming against `routes/web.php` + `AppServiceProvider` before refactors.
- For PDF review work, keep frontend tool names aligned with backend `markup_type` validation and MySQL enum migrations (`circle`, `arrow`, `text`, `highlight`, `stamp`, `dimension`, `rectangle`, `cloud`, `pen`, `polyline`, `polygon`).
- Per-page PDF calibration is persisted separately from markups through `page-scales` routes; measurement features should read page scale by `page_number`, not from individual markup payloads.
- The current frontend quality gate is `npm run lint:check` using ESLint flat config over `resources/js`; do not reintroduce deprecated `.eslintignore` or `--ext` usage.
