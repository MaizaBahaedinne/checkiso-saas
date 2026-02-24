# Copilot instructions (checkiso-saas)

## Project snapshot
- Framework: CodeIgniter 4 (`codeigniter4/framework`), PHP 8.2 (see `composer.json`).
- Web entrypoint is `public/index.php`; Docker nginx serves `/var/www/html/public` (see `docker/nginx/default.conf`).
- Current routing is minimal (`/` -> `Home::index`) in `app/Config/Routes.php`.

## Local dev workflows
- Install deps: `composer install`.
- Run stack (nginx + php-fpm + mysql): `docker compose up --build` then open `http://localhost:8080/`.
- Configure environment via `.env` (copy from `env`). `app.baseURL` defaults to `http://localhost:8080/` in `app/Config/App.php`.
- MySQL in Docker: database `checkiso`, user `checkiso`, password `checkiso`, root password `root` (see `docker-compose.yml`).
  - From inside the `app` container the DB host is `db`.
  - From the host machine the DB is exposed on `localhost:33060`.

## Database & domain model (migrations)
- Migrations live in `app/Database/Migrations/` and are enabled in `app/Config/Migrations.php`.
- Domain schema already defined (Feb 24, 2026 migrations):
  - SaaS tenancy: `tenants`, `companies`, `org_units` (tree via `parent_id`).
  - Identity: `users`, and `memberships` (user↔tenant, optionally company/org unit).
  - RBAC: `roles` (tenant|platform), `permissions`, `role_permissions`, `membership_roles`.
  - Standards catalog: `standards`, `standard_versions` (global, versioned).
  - ISO hierarchy: `domains` → `clauses` → `controls` (global, linked to `standard_versions`).
- Migration conventions used here (follow existing files): `BIGINT UNSIGNED` ids, InnoDB engine, explicit indexes, and foreign keys with cascading rules; most tenant-scoped tables include `created_at/updated_at/deleted_at`.
- Apply migrations: `php spark migrate` (in Docker: `docker compose exec app php spark migrate`).

## Sessions
- App config currently uses file sessions (`Config\Session::$driver = FileHandler`).
- A DB session table migration exists (`ci_sessions`). If switching to DB sessions, use `CodeIgniter\Session\Handlers\DatabaseHandler` and set `Config\Session::$savePath = 'ci_sessions'`.

## Adding new HTTP features
- Put new controllers under `app/Controllers/` (folders `Api/` and `Web/` exist but are currently empty). Extend `app/Controllers/BaseController.php`.
- Register routes explicitly in `app/Config/Routes.php` (don’t rely on implicit discovery).

## Tests
- Run: `composer test` or (Windows) `vendor\bin\phpunit`.
- PHPUnit is configured by `phpunit.xml.dist` and boots CI via `vendor/codeigniter4/framework/system/Test/bootstrap.php`.
