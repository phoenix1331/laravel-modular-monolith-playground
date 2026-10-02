# Context

Tutorial repo: a Laravel 13 + Vue 3 modular monolith for grassroots sports clubs. Feature modules switch on or off per organisation (club) and can be deleted from the codebase without breaking the rest of the app. Boundaries are enforced by Deptrac and Pest arch tests, not by convention.

Primary audience is people learning how to draw and enforce module boundaries in Laravel, so code that defines a boundary is annotated to explain why it exists and how a new module would follow the same pattern.

## Core principles

| Principle                        | Meaning                                                                                                                                   |
| -------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| Core knows nothing about modules | Organisations, users, roles, teams, players, flags, module registry and audit log live in Core. Core never imports from `Modules\`        |
| Modules talk through contracts   | Each module's `Contracts/` folder (interfaces, DTOs, events) is public. Everything else is internal                                       |
| Installed is not enabled         | Installed: the module's definition is listed in `config/modules.php` and its provider registers. Enabled: a per-organisation Pennant flag |
| Boundaries are enforced          | Deptrac (PHP), Pest arch tests (rules inside a module) and `eslint-plugin-boundaries` (frontend) fail the build on violations             |

## Stack

| Layer         | Choice                                                                |
| ------------- | --------------------------------------------------------------------- |
| Framework     | Laravel 13, PHP 8.4                                                   |
| Runtime       | Docker, FrankenPHP (`dunglas/frankenphp:php8.4`)                      |
| Database      | MySQL 8.4                                                             |
| Frontend      | Laravel Vue starter kit: Inertia, Vue 3, TypeScript, Tailwind 4, Vite |
| Auth          | Laravel Fortify (via the starter kit)                                 |
| Feature flags | Laravel Pennant, database driver, scoped to `Organisation`            |
| Boundaries    | Deptrac, Pest arch tests, `eslint-plugin-boundaries`                  |
| Tests         | Pest                                                                  |

## Directory map

| Path                          | Purpose                                                                                                                                                           |
| ----------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `app/`                        | Core (`App\`). Never references `Modules\`                                                                                                                        |
| `modules/{Name}/`             | One folder per module (`Modules\{Name}\`): `Contracts/`, `Database/`, `Http/`, `Models/`, `Policies/`, `Routes/`, `{Name}Module.php`, `{Name}ServiceProvider.php` |
| `config/modules.php`          | Installed modules list. Removing a line uninstalls a module                                                                                                       |
| `resources/js/modules/{key}/` | Module frontend. `index.ts` is the only public entry                                                                                                              |
| `deptrac.yaml`                | PHP layer definitions and allowed dependencies                                                                                                                    |
| `.husky/`                     | Tracked git hooks: `commit-msg` (lowercase conventional commits), `pre-commit` (Pint + osv-scanner). Activated by `npm install` via the `prepare` script          |
| `docker/entrypoint.sh`        | Installs `vendor/` and `node_modules/` on first boot                                                                                                              |
| `docs/`                       | GitHub Pages site and the rendered module graph                                                                                                                   |

## Runtime

| Service      | Container port | Host port                | Notes                                      |
| ------------ | -------------- | ------------------------ | ------------------------------------------ |
| `app`        | 8000           | `APP_PORT` (8000)        | FrankenPHP serving `public/`               |
| `app` (Vite) | 5173           | `VITE_PORT` (5173)       | HMR, browser connects via `localhost:5173` |
| `mysql`      | 3306           | `FORWARD_DB_PORT` (3307) | Host 3306 was already in use               |

- Container main process is `php artisan dev --inline` (server, queue, logs, Vite)
- `AppServiceProvider` overrides the `server` dev command to `frankenphp php-server` so `php artisan dev` stays the single entry point while FrankenPHP serves requests
- Container runs as the host UID/GID (build args) so bind-mounted files stay host-owned
- Fresh volume needs `migrate` before the queue worker stops logging missing-table errors

## Decisions

| Decision                                   | Why                                                                                                                                                               |
| ------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Laravel Vue starter kit (TypeScript)       | Ships auth pages, layouts and tooling out of the box, so effort goes into module boundaries rather than auth UI. TypeScript is accepted as part of the kit        |
| Single-database tenancy                    | `organisation_id` column + `BelongsToOrganisation` global scope. Super admin bypasses explicitly, never implicitly                                                |
| Module tables survive disabling            | Toggling a flag off hides the module (404 + no nav) but keeps data, so toggling back on restores it                                                               |
| Disabled module routes return 404          | A disabled module should look like it does not exist, not like a permission error                                                                                 |
| Hard vs soft dependencies                  | Hard: cannot enable without the dependency, cannot disable a dependency while dependents are on. Soft: Core binds a null implementation that the module overrides |
| Skeleton `CLAUDE.md` / `AGENTS.md` removed | They instructed host PHP installs, which conflicts with the Docker-only workflow                                                                                  |
| Faker locale `en_GB`                       | UK grassroots clubs                                                                                                                                               |
