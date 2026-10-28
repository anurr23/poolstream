<p align="center">
  <br>
  <h1 align="center">🎱 PoolStream</h1>
  <p align="center">
    <strong>Billiard &amp; Lounge Management System</strong>
  </p>
</p>

---

## 📖 About

**PoolStream** is a billiard hall management application built with Laravel 13, Inertia.js, Vue 3, and Bootstrap 5. It handles table sessions, F&amp;B orders, automated relay control, checkout, receipts, and operational reports.

Repository: <https://github.com/anurr23/poolstream>

## ✨ Features

- 🕒 **Table session management** — start, monitor, edit, and check out billiard sessions with live countdowns and automatic checkout for expired sessions.
- 🍔 **Integrated F&amp;B ordering** — order from a table or as a standalone order; edit quantities or remove items while an order is active.
- 💡 **Relay control** — Python bridge (`app/Services/relay_controller.py`) to switch table lights on/off with a session. The current implementation is a placeholder for USB HID hardware.
- 💳 **Checkout and receipts** — combined billiard and F&amp;B totals, printable 80 mm thermal receipts via the browser.
- 🌓 **Theming** — light and dark modes using Bootstrap 5 and custom SCSS (`bb-` prefix).
- 🛡️ **Role-based access** — `admin` (full access) and `kasir` (cashier dashboard).
- 📊 **Reports** — F&amp;B sales, table transactions, revenue, and analytics charts.
- 📱 **Responsive layout** — overlay sidebar on desktop, tablet, and mobile.

## 🛠️ Stack

| Layer | Technology |
| --- | --- |
| Backend | PHP 8.3+, Laravel 13 |
| Frontend | Vue 3 (Composition API), Inertia.js |
| Styling | Bootstrap 5, custom SCSS |
| Build | Vite 8 |
| Database | SQLite (default), MySQL, or PostgreSQL |
| Auth | Laravel Breeze, username-based login |
| Hardware | Python + USB HID relay (optional) |
| Tests | PHPUnit 12 |

## ✅ Requirements

- PHP **8.3+** with `pdo_sqlite` (default) or `pdo_mysql` / `pdo_pgsql`
- Composer **2.x**
- Node.js **20.19+** and npm
- Python **3.10+** only if you connect the relay hardware

## 🚀 Quick start

```bash
git clone https://github.com/anurr23/poolstream.git
cd poolstream
composer setup
composer dev
```

`composer setup` performs the first-time setup: installs PHP and npm dependencies, copies `.env.example` to `.env`, creates the SQLite file, generates the app key, migrates and seeds the database, links storage, and builds the frontend.

`composer dev` runs four processes concurrently:

- Laravel development server — <http://localhost:8000>
- Queue listener
- Vite dev server (hot reload)
- Laravel scheduler (`schedule:work`)

Open <http://localhost:8000>. Login uses **username**, not email.

Seeded accounts:

| Role | Username | Password |
| --- | --- | --- |
| Admin | `admin` | `password` |
| Cashier | `kasir` | `password` |

> Change these credentials before deploying to production.

## ⚙️ Manual setup

Use this path when you need a custom database or want to control each step.

1. **Install dependencies**

   ```bash
   composer install
   npm install
   ```

2. **Create the environment file**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure the database**

   SQLite (default):

   ```bash
   touch database/database.sqlite
   ```

   MySQL:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=poolstream
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   PostgreSQL:

   ```dotenv
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=poolstream
   DB_USERNAME=postgres
   DB_PASSWORD=
   ```

   Create the database first, then run the migrations.

4. **Migrate and seed**

   ```bash
   php artisan migrate --seed
   ```

5. **Link public storage**

   ```bash
   php artisan storage:link
   ```

6. **Build or run the frontend**

   ```bash
   npm run build   # production assets
   npm run dev     # or the Vite dev server
   ```

7. **Serve the application**

   ```bash
   php artisan serve
   ```

   For the full development stack (server, queue, Vite, scheduler), use `composer dev`.

## 🧪 Tests

```bash
composer test
```

Tests run against an in-memory SQLite database (see `phpunit.xml`). The suite covers authentication, password reset/update, profile updates, and transaction cost recalculation (`tests/Feature/TransactionServiceTest.php`).

Run a single test:

```bash
php artisan test --filter TransactionServiceTest
```

## 🗓️ Scheduler

`tables:auto-checkout` completes expired sessions and turns off their relays. It is registered in `bootstrap/app.php` to run every minute.

- Development: `composer dev` already runs `php artisan schedule:work`.
- Production: add the Laravel cron entry.

```cron
* * * * * cd /path/to/poolstream && php artisan schedule:run >> /dev/null 2>&1
```

Manual run:

```bash
php artisan tables:auto-checkout
```

## 🔌 Relay hardware

`app/Services/relay_controller.py` is called from PHP with `shell_exec`. The checked-in script is a dummy that always succeeds; the real `hidapi` implementation is commented out. To use real hardware, install `hidapi`, replace the placeholder logic, and verify the relay channel mapping in `config`/`tables` data before enabling it in production.

## 📁 Project structure

```text
poolstream/
├── app/
│   ├── Console/Commands/          # tables:auto-checkout
│   ├── Http/
│   │   ├── Controllers/           # Auth, dashboard, master data, reports
│   │   ├── Middleware/            # role and active-user checks
│   │   └── Requests/              # form validation
│   ├── Models/                    # User, Table, Package, FnbItem, Transaction, TransactionItem
│   ├── Providers/
│   └── Services/                  # TransactionService, relay_controller.py
├── bootstrap/                     # application bootstrapping and schedule
├── config/                        # Laravel configuration
├── database/
│   ├── factories/                 # model factories for tests
│   ├── migrations/                # schema history
│   └── seeders/                   # demo users, tables, packages, F&amp;B menu
├── documentation/                 # relay diagrams and reference images
├── public/
│   ├── build/                     # compiled Vite assets
│   └── images/                    # logo and public images
├── resources/
│   ├── js/
│   │   ├── Components/            # shared Vue components
│   │   ├── Composables/           # reusable composition functions
│   │   ├── Layouts/               # admin, cashier, guest layouts
│   │   ├── Pages/                 # Inertia page components
│   │   └── utils/                 # receipt printing
│   ├── scss/                      # Bootstrap theme and custom styles
│   └── views/                     # Blade root template
├── routes/                        # web, auth, console routes
├── storage/                       # logs, uploads, framework cache
├── tests/                         # PHPUnit feature and unit tests
├── .editorconfig                  # LF, UTF-8, 4-space indentation
├── .gitattributes                 # line-ending normalization
├── composer.json                  # PHP dependencies and setup scripts
├── package.json                   # frontend dependencies and scripts
└── vite.config.js                 # Vite + Vue + SCSS configuration
```

### Architecture at a glance

```text
Browser
  │  HTTP / Inertia visits
  ▼
routes/web.php ──► Middleware (auth, role, active user)
  │
  ▼
Controller ──► Service / Eloquent model ──► Database
  │
  ▼
Inertia response ──► resources/js/Pages/*.vue
  │
  ▼
Vue 3 + Bootstrap 5 UI
```

## 🌿 Git workflow

- `main` is the deployable branch.
- Use short-lived branches: `feat/<scope>`, `fix/<scope>`, `chore/<scope>`, `docs/<scope>`.
- Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/): `feat(dashboard): ...`, `fix(auth): ...`, `docs: ...`.
- Open a pull request, run `composer test` and `npm run build`, then squash-merge.
- Never commit `.env`, `vendor/`, `node_modules/`, or build caches.

Full rules and the pull-request checklist are in [CONTRIBUTING.md](CONTRIBUTING.md).

## 🧯 Troubleshooting

| Symptom | Fix |
| --- | --- |
| `Vite manifest not found` | Run `npm run build` or start `npm run dev`. |
| CSS/JS 404 after build | `vite.config.js` uses `base: '/poolstream/public/build/'`. Serve the app under that subpath, or change `base` to `/build/` for a domain-root deployment. |
| `Database file does not exist` | `touch database/database.sqlite` or point `DB_DATABASE` to an existing database. |
| `419 Page Expired` | Clear config and confirm the `sessions` table exists: `php artisan config:clear && php artisan migrate`. |
| Login fails | Use the **username** (`admin` / `kasir`), not an email. Re-run `php artisan db:seed` if the accounts are missing. |
| Relay does not switch | The Python bridge is a dummy. Install `hidapi` and replace the placeholder implementation. |
| Scheduled checkout does not run | Start `php artisan schedule:work` in development, or configure the production cron entry. |
| Permission denied on storage | `chmod -R ug+rwx storage bootstrap/cache` and re-run `php artisan storage:link`. |

## 📄 License

MIT
