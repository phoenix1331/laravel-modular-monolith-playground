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

## Rules

| Rule | Enforced by |
|---|---|
| Only `Contracts/` is public. Other modules never reference anything else in this folder | Deptrac (`{Name}` and `{Name}Contracts` layers in `deptrac.yaml`) |
| Every class belongs to a layer | `make deptrac` (`--fail-on-uncovered` and `debug:unassigned`) |
| Installed is not enabled | Listed in `config/modules.php` = installed. Per-organisation Pennant flag = enabled |
