# Modules

Each folder here is a feature module with its own namespace, `Modules\{Name}\`. Core (`app/`) never imports from this folder, so any module can be deleted without touching Core.

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

A missing hard dependency or a dependency cycle stops the application booting with a message naming the modules involved.

## Rules

| Rule                                                                                    | Enforced by                                                                         |
| --------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| Only `Contracts/` is public. Other modules never reference anything else in this folder | Deptrac (`{Name}` and `{Name}Contracts` layers in `deptrac.yaml`)                   |
| Every class belongs to a layer                                                          | `make deptrac` (`--fail-on-uncovered` and `debug:unassigned`)                       |
| Installed is not enabled                                                                | Listed in `config/modules.php` = installed. Per-organisation Pennant flag = enabled |
