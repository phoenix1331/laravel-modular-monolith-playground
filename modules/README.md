# Modules

Each folder here is a feature module with its own namespace, `Modules\{Name}\`. Core (`app/`) never imports from this folder, so any module can be deleted without touching Core.

`Fixtures/` is the reference module. Every file in it is annotated; copy its shape when building a new module.

## Anatomy

```
modules/{Name}/
  Contracts/                public: interfaces, DTOs, events other modules may use
  Database/                 migrations, factories, seeders
  Http/                     controllers, requests
  Models/
  Policies/
  Routes/web.php
  {Name}Module.php          key, name, hard and soft dependencies
  {Name}ServiceProvider.php
```

## Installing a module

1. `{Name}Module.php` extends `App\Modules\ModuleDefinition`: key, name, hard and soft dependencies (by key, never by class)
2. `{Name}ServiceProvider.php` extends `App\Modules\ModuleServiceProvider`: loads `Routes/web.php` into the club area and `Database/Migrations`
3. Add the definition class to `config/modules.php`
4. `php artisan modules:list` confirms it is installed

5. Frontend: `resources/js/modules/{key}/index.ts` (the only file other modules may import) default-exports `defineModule({ key, navigation })` from `@/lib/modules`. Its links appear only while the module is enabled for the club
6. Pages go in `resources/js/modules/{key}/pages/` and are rendered by their path under `resources/js/modules`, e.g. `Inertia::render('fixtures/pages/Index')`

A missing hard dependency or a dependency cycle stops the application booting with a message naming the modules involved.

## Hard and soft dependencies

|                              | Hard                                                                    | Soft                                                                          |
| ---------------------------- | ----------------------------------------------------------------------- | ----------------------------------------------------------------------------- |
| Declared                     | `hardDependencies(): ['fixtures']`                                      | `softDependencies(): ['subscriptions']`                                       |
| Enabling                     | Blocked until the dependency is enabled                                 | Never blocked                                                                 |
| How code talks               | Uses the dependency's `Contracts/` (Deptrac allows `FixturesContracts`) | Asks a Core capability (`App\Capabilities\*`); never imports the other module |
| When the other module is off | Cannot happen                                                           | Core's null implementation answers, so the module keeps working               |

A soft dependency goes through a **capability**: an interface in `app/Capabilities/` with a null implementation that Core binds by default. The providing module rebinds it in its provider with a closure that checks, on every resolve, whether it is enabled for the current club. See `MembershipStatus` and `SubscriptionsServiceProvider`.

## Rules

| Rule                                                                                     | Enforced by                                                                                                    |
| ---------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| Only `Contracts/` is public. Other modules never reference anything else in this folder  | Deptrac (`{Name}` and `{Name}Contracts` layers in `deptrac.yaml`) and Pest arch (`tests/Arch/ModulesTest.php`) |
| Every class belongs to a layer                                                           | `make deptrac` (`--fail-on-uncovered` and `debug:unassigned`)                                                  |
| Installed is not enabled                                                                 | Listed in `config/modules.php` = installed. Per-organisation Pennant flag = enabled                            |
| No raw `DB::table()` style queries: they skip the tenancy scope                          | Pest source scan (`tests/Arch/ModulesTest.php`)                                                                |
| Controllers do not build queries; reads go through `Queries/` classes                    | Pest source scan                                                                                               |
| Services resolved by class, never by string key                                          | Pest source scan                                                                                               |
| Frontend: a module imports shared code, its own files and other modules' `index.ts` only | `eslint-plugin-boundaries` (`eslint.config.js`, `make lint-js`, pre-commit hook)                               |

## Uninstalling a module

| State       | How                                                             | What happens to its tests                      |
| ----------- | --------------------------------------------------------------- | ---------------------------------------------- |
| Installed   | Listed in `config/modules.php`                                  | Run with the suite                             |
| Uninstalled | Remove its line from `config/modules.php`                       | Skipped by `tests/Pest.php`, naming the reason |
| Deleted     | Also delete `modules/{Name}/` and `resources/js/modules/{key}/` | Gone with the folder                           |

`make uninstall-check MODULE={Name}` is the proof that a module is detachable. In a temp copy of the project it removes the config line, deletes both folders, regenerates the Wayfinder route helpers, then runs Pest, Deptrac, the frontend type check and Vitest. Any file outside the module that still referenced it fails one of those gates. The working tree is never touched.

A module other installed modules hard-depend on cannot be removed alone: the check stops and names the dependents. `make uninstall-check MODULE=Fixtures` fails until Availability and MatchReports go too.
