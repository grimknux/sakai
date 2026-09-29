# Sakai Base System

A starter for internal web apps: a **CodeIgniter 4** JSON API with session-cookie authentication, role/permission access control, audit logging and login protection, plus a **Vue 3 + PrimeVue (Sakai template)** single-page frontend.

Build your modules on top of this base. It ships with user, role and permission management, a dashboard, a login-attempts view and an audit log.

## Stack

| Layer    | Technology                                                                   |
| -------- | ---------------------------------------------------------------------------- |
| Backend  | PHP 8.1+, CodeIgniter 4.6, MySQL/MariaDB                                     |
| Frontend | Vue 3, Vue Router, PrimeVue 4, Tailwind 4, Vite 5, Axios                     |
| Auth     | Server-side sessions (stored in the database), HttpOnly cookies, CSRF tokens |

## Repository layout

```
app/
  Config/          Routes, Filters, Security, Cookie, Session, Cors, Branding ...
  Controllers/Api/ AuthController, UserController, Admin/* (users, roles, permissions, audit, security)
  Filters/         AuthFilter (logged in), PermissionFilter (permission:slug)
  Services/        AuditService, LoginAttemptService, PermissionService
  Validation/      PasswordRules (strong_password)
  Database/        Migrations and Seeds
frontend/          Vue app (dev server + build)
public/            Web root; `npm run build` writes the frontend here
.env.example       Backend environment template
frontend/.env.example  Frontend environment template
```

## Requirements

- PHP 8.1 or newer (extensions: `intl`, `mbstring`, `mysqli`, `curl`, `openssl`)
- Composer
- MySQL or MariaDB
- Node.js 18+ and npm
- HTTPS in every environment, including local. Cookies are `Secure`, so plain `http://` will not keep a session. Laragon's auto virtual hosts (`https://<folder>.dev.local`) work.

> **Framework version:** the project is pinned to CodeIgniter 4.6.5, which is the last release that supports PHP 8.1. CodeIgniter 4.7 needs PHP 8.2. `composer audit` currently reports advisories fixed only in 4.7.x (uploads, `deleteBatch()`, forwarded-HTTPS headers). This base does not use those features, but plan the PHP 8.2 upgrade.

## Setup

### 1. Backend

```bash
composer install
cp .env.example .env          # then edit .env
php spark key:generate        # fills encryption.key
php spark migrate
php spark db:seed RunAllSeeders
```

Set at least `app.baseURL`, the `database.default.*` values and `FRONTEND_URL` in `.env`.

`RunAllSeeders` runs every seeder once and records them in a `seeders` table, so it is safe to run again.

Set `DEFAULT_SUPERADMIN_USERNAME`, `DEFAULT_SUPERADMIN_EMAIL` and `DEFAULT_SUPERADMIN_PASSWORD` in `.env` before seeding: the initial seeder creates the first superadmin from them and fails with a clear message if any is missing or the password is weak (8-72 characters, a letter and a number). There are no built-in defaults. You can remove the password from `.env` after seeding.

### 2. Frontend

```bash
cd frontend
cp .env.example .env
npm install
npm run dev        # https://sakai.dev.local:4747, proxies /api to the backend
```

The dev server reads its host, port and Laragon SSL certificate from [frontend/vite.config.mjs](frontend/vite.config.mjs); edit them if your setup differs.

### 3. Production build

```bash
cd frontend
npm run build      # writes into ../public
```

The built app and the API are then served from the same origin, so there is no CORS setup.

## Configuration

All environment-specific values live in `.env` (backend) and `frontend/.env` (frontend). Both are git-ignored; the `.env.example` files are the tracked templates.

### Branding

Names shown to users are not hardcoded.

| Where                                                | File            | Variables                                                                                       |
| ---------------------------------------------------- | --------------- | ----------------------------------------------------------------------------------------------- |
| Frontend (title, topbar, footer, login, error codes) | `frontend/.env` | `VITE_APP_NAME`, `VITE_APP_SHORT_NAME`, `VITE_ORG_NAME`, `VITE_ORG_SHORT_NAME`, `VITE_APP_LOGO` |
| Backend (emails)                                     | `.env`          | `branding.appName`, `branding.appShortName`, `branding.orgName`, `branding.orgShortName`        |

Frontend values are baked in at build time, so restart `npm run dev` or rebuild after changing them. Frontend code reads them from [src/config/app.js](frontend/src/config/app.js); backend code uses `config('Branding')`.

### Other settings

| Variable                        | Purpose                                                                   |
| ------------------------------- | ------------------------------------------------------------------------- |
| `CI_ENVIRONMENT`                | `development` or `production`. Production hides error details.            |
| `app.baseURL`                   | Public URL of the API. Must be HTTPS.                                     |
| `FRONTEND_URL`                  | Frontend URL, used in password-reset links and as the default CORS origin |
| `CORS_ALLOWED_ORIGINS`          | Optional comma-separated CORS origins. Not needed for same-origin.        |
| `PASSWORD_RESET_EXPIRE_MINUTES` | Lifetime of reset links (default 30)                                      |
| `email.*`                       | SMTP settings for reset emails                                            |
| `VITE_API_BASE_URL`             | Optional API base path for the frontend (default `/api`)                  |

## How authentication works

- **Sessions:** stored in the `ci_sessions` table (database driver). Cookies are `Secure`, `HttpOnly` and `SameSite=Strict`, so the frontend and API must share a site.
- **CSRF:** every POST, PUT, PATCH and DELETE requires an `X-CSRF-TOKEN` header. The frontend gets a token from `GET /api/auth/csrf`, sends it automatically, and refreshes it once if the server rejects a stale token ([frontend/src/lib/api.js](frontend/src/lib/api.js)).
- **Login protection:** throttling per IP and username, account lock after repeated failures, login attempts recorded in a table, and constant-time credential checks so unknown users can't be detected by timing.
- **Passwords:** minimum 8 characters, at least one letter and one number, not a common password, not containing the username. Enforced when a password is set (create user, change, reset), not on login. Hashes use Argon2id.
- **Sessions after a password change:** changing a password signs out the user's other sessions. Resetting a password signs out all of them.
- **Throttled endpoints:** login, forgot password, change password, reset password.
- **Headers:** the `secureheaders` filter is on globally. Set HSTS and a CSP on your web server in production.
- **Audit log:** logins, failures, password changes and admin actions are recorded via `AuditService`.

## Permissions

Routes are protected in [app/Config/Routes.php](app/Config/Routes.php) with filters:

```php
$routes->post('users', 'UsersController::create', [
    'filter' => 'auth,permission:users.create'
]);
```

- `auth` requires a logged-in session.
- `permission:<slug>` requires the user's role to hold that permission. Superadmins bypass the check.
- Permissions and roles are seeded by [InitialAuthSeeder](app/Database/Seeds/InitialAuthSeeder.php) and managed in the admin pages.
- The frontend hides menus and buttons using the permissions returned by `GET /api/auth/me`. Those are UI hints only; the backend is the source of truth.

## Adding a module

1. Create a migration (`php spark make:migration CreateThingTable`) and a model.
2. Add a controller under `app/Controllers/Api/`.
3. Add the seeder rows for its permissions (`thing.view`, `thing.create`, ...). Seeders in `app/Database/Seeds` are picked up by `RunAllSeeders` automatically.
4. Register routes in `Routes.php` with `auth` and `permission:` filters.
5. Add a page in `frontend/src/views`, a route in `frontend/src/router/index.js` (with `meta.permission` if needed) and a menu entry in `frontend/src/layout/AppMenu.vue`.

Write requests need no extra CSRF code: the shared Axios instance handles it.

## Useful commands

```bash
php spark migrate:status       # migration state
php spark routes               # list routes
composer audit                 # dependency advisories
npm run lint                   # in frontend/, lint and fix
```

## Production checklist

- `CI_ENVIRONMENT = production` and a real `encryption.key`
- HTTPS everywhere; add `Strict-Transport-Security` and a `Content-Security-Policy` at the web server
- Use a strong `DEFAULT_SUPERADMIN_PASSWORD` when seeding, and remove it from `.env` afterwards
- Real SMTP credentials; `.env` never committed
- `expose_php = Off`
- Run `php spark migrate` on deploy
- Run `composer audit` regularly

## Known leftovers

This base was trimmed from a larger project. A few files still refer to removed modules and can be deleted when you build your own: the models `Inventory*`, `Software*`, `LicenseType`, `DeviceType` and `Pms*` in `app/Models`, and `SuperadminAssetRolePermissionSeeder`. `Routes.php` also defines the users routes twice (`/api/users` and `/api/admin/users`).

## Credits

Frontend based on [Sakai Vue](https://github.com/primefaces/sakai-vue) by PrimeFaces. Backend built on [CodeIgniter 4](https://codeigniter.com).
