# PoolStream - Agent Guide

Pool hall management: billiard table control, timed sessions, F&B orders.
Laravel 13 + Inertia.js + Vue 3 + Bootstrap 5. SQLite in test/dev, PostgreSQL in production.

## Commands

- `composer setup` - full first-time setup (install, .env, key, migrate, npm, build)
- `composer dev` - runs concurrently: artisan serve, queue worker, vite, schedule:work
- `composer test` - clears config then runs `php artisan test`
- `php artisan tables:auto-checkout` - manually trigger expired session checkout (runs every minute via scheduler)
- `npm run dev` - vite dev server only
- `php artisan test --filter TestName` - run a single test or class

## Architecture

- **Frontend:** Vue 3 `<script setup>` (no TS) in `resources/js/Pages/`, resolved via Inertia. Components in `resources/js/Components/`. Custom SCSS (`bb-` prefix classes) at `resources/scss/app.scss`. `@/` alias -> `resources/js/`. Ziggy `route()` helper available globally.
- **Backend:** Controllers in `app/Http/Controllers/`. Models in `app/Models/`. All routes manually defined (no `Route::resource`).
- **Routes:** `routes/web.php` (app), `routes/auth.php` (Breeze). Admin routes under `role:admin` middleware at `/master/*`. No `/register` route -- admin-only user creation via `/master/users`.
- **Database:** `.env` uses **MySQL** (`poolstream` DB, `mysql` driver, `host.docker.internal:3306` — served by the local `mysql-server` Docker container). `.env.example` uses **SQLite**. Tests use **in-memory SQLite** (`phpunit.xml`). Sessions/queue/cache all database-backed (non-default).
- **Hardware:** `app/Services/relay_controller.py` called via `shell_exec` -- **dummy placeholder** (real `hidapi` code commented out).
- **Scheduler:** `tables:auto-checkout` completes expired transactions and turns off relays. Every minute.
- **AI Evaluation:** admin-only `/master/ai-evaluations`. Flow: `ReportDataService` builds an aggregate fact pack (never raw rows by default) → `GenerateAiEvaluation` job (dispatched after the response, so the UI never blocks) calls the local 9router endpoint (`config('services.ai')`, env `AI_BASE_URL` / `AI_API_KEY` / `AI_MODEL` / `AI_TIMEOUT`, `0` = no timeout, applied explicitly because Laravel defaults to 30s) → JSON result saved in `ai_evaluations`; the result page polls while status is `running`. 9router needs an explicit `"stream" => false` or it answers with SSE chunks. When the app runs in Docker, `AI_BASE_URL` must use `host.docker.internal`, not `127.0.0.1`.
- **AI result page & PDF:** `Show.vue` renders the answer as a full-width AI response (section headings + prose, no chat bubbles) with the PDF attachment at the end of the answer; no model/token metadata is shown in the UI or PDF (treated as internal). `GET /master/ai-evaluations/{id}/pdf` builds an A4 report with `barryvdh/laravel-dompdf` from `resources/views/reports/ai-evaluation-pdf.blade.php`. PHP has **no GD/Imagick**, so the PDF header uses `public/images/logo.jpg` (dompdf embeds JPEG natively); the chart is CSS bars, not an image. dompdf quirk: **never use `float` inside a `position: fixed` element** (it breaks pagination and inflates the page count).

## Domain Model

- **Roles:** `admin` (full access), `kasir` (dashboard only), `customer` (online booking only). Enforced by `CheckRole` middleware (`role:admin,kasir` etc.).
- **UUIDs:** `User` and `Table` use `HasUuids`. `Transaction`, `TransactionItem`, `Package`, `FnbItem` use auto-increment.
- **Transactions:** status `booked|active|completed|cancelled`, type `billiard` (table session) or `fnb_only` (standalone F&B). `table_id` nullable for F&B-only. Cost fields (`billiard_cost`, `fnb_cost`, `total_cost`) recalculated via `TransactionService::recalculate()`. `booked` rows are future reservations created by customers; cashier starts them from the dashboard (`tables.start-booking`) within 30 minutes of the scheduled start.
- **Auth:** username-based (no email login requirement for staff), but users now have an optional `email`; customers can self-register at `/register` with a 6-digit email OTP and must verify before booking (`customer.verified` middleware). Optional Google OAuth via `laravel/socialite`. `password_reset_tokens` uses Laravel's default `email` column name but stores the username (see migration `2026_10_28_000000`). OTP fields live on `users` (`otp_code` hashed, `otp_expires_at`, `otp_attempts`).
- **User model:** `photo_path` + `photo_url` accessor, `is_active` boolean, `role` enum, PHP attribute `#[Fillable]`/`#[Hidden]`.

## Tests

- All models (`User`, `Table`, `Package`, `FnbItem`, `Transaction`, `TransactionItem`) have Laravel model factories.
- Stale Breeze tests (email verification, registration, self-deletion, email-based auth) have been cleaned up and adapted to username-based auth.
- Core business logic tested via `TransactionServiceTest` (verifies recalculation).

## Conventions & Gotchas

- **Bootstrap 5 classes only** (no Tailwind despite `@tailwindcss/forms` in devDeps).
- **Fixed overlay sidebar** (`position: fixed`, `translateX(-100%)`) at all screen sizes.
- **Dark-by-default** theme via `data-bs-theme` on `<html>`, persisted to `localStorage('theme')`.
- **UI strings in Indonesian** (`"Meja berhasil ditambahkan"`), code in English.
- **File uploads:** user photos -> `storage/app/public/user_photos/`, F&B images -> `fnb_images/`. Always use `FormData` + `forceFormData: true`. Client-side image compression in FnbItems.vue (800px max, 85%).
- **Name auto-capitalization**: `.replace(/\b\w/g, l => l.toUpperCase())` used in multiple forms.
- **Receipt printing:** custom `printReceipt` utility (`resources/js/utils/receipt.js`) utilizing 80mm thermal receipt HTML/CSS template loaded in `window.open()`.
- **Inconsistencies:** Two PATCH routes to `updateQuantity`.
- **Vite base path:** `/poolstream/public/build/` (not default). SCSS deprecation warnings silenced in config.
- **Date inputs:** always use `flatPickr` (`vue-flatpickr-component` with Indonesian locale / `fpConfig`), never use native `<input type="date">`.
- **4-space indent, LF line endings** (`.editorconfig`).
- **No pagination** in backend queries (all `->get()`).

## Performance (Docker + Windows bind mount)

The project is bind-mounted from Windows into the `php8.5-apache` container, so `stat()` is ~340x slower than the container filesystem. With OPcache `validate_timestamps=On` (default), every request re-stats hundreds of files and page loads jump from ~0.35s to 2-8s. `docker/php/zzz-performance.ini` disables timestamp validation and grows the realpath cache:

```sh
docker cp docker/php/zzz-performance.ini php8.5-apache:/usr/local/etc/php/conf.d/
docker restart php8.5-apache
```

Trade-off: PHP changes are only picked up after OPcache is reset. Two ways to do that:

- Automatic (recommended): run the watcher, which invalidates just the changed files through the dev-only endpoint `GET /__dev/opcache` (guarded by `APP_ENV=local` + `DEV_OPCACHE_TOKEN`).

```sh
composer watch:opcache          # or: powershell -ExecutionPolicy Bypass -File scripts/watch-opcache.ps1
```

- Manual: `docker restart php8.5-apache`, or `curl "http://localhost/poolstream/__dev/opcache?token=<DEV_OPCACHE_TOKEN>"` for a full reset.

## Future Plans / Roadmap

1. **Real Hardware Integration (Relay Biliar)**
   - Replace dummy script `relay_controller.py` with actual USB HID Relay integration using python `hidapi` library to toggle lights automatically on session start/stop.
2. **Real-Time Dashboard (WebSockets / Server-Sent Events)**
   - Integrate SSE or Laravel Reverb for real-time countdown of billiard sessions and auto-deactivation alerts without page refreshes.
3. **Direct Thermal Printing (ESC/POS)**
   - Use direct raw printing protocols (like QZ Tray or local python print listener) to print receipts automatically without popping up a browser print window.
4. **Export Reports (PDF / Excel)**
   - Add export buttons to reports (F&B Sales, Table Transactions, Revenue) using Laravel Excel or DomPDF packages.
5. **Audit Log System**
   - Track critical operator actions (starts, manual stops, cancels, adjustments) in an `audit_logs` table for fraud prevention.
