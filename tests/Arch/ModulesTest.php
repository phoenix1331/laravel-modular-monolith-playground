<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Module architecture rules
|--------------------------------------------------------------------------
|
| Deptrac answers "which layer may depend on which". These tests cover what
| a dependency graph cannot see: rules about *how* code inside a layer is
| written. Together they make a boundary that is checked, not just agreed.
|
| Two kinds of check:
| - Pest arch expectations, which inspect which classes a class uses
| - Source scans, for rules about method calls (Pest arch cannot see those),
|   reporting every offending file and line
|
| A new module needs no changes here: every folder in modules/ is picked up.
|
*/

const MODULES_PATH = __DIR__.'/../../modules';
const APP_PATH = __DIR__.'/../../app';

/**
 * @return list<string>
 */
function moduleNames(): array
{
    return array_values(array_map(basename(...), glob(MODULES_PATH.'/*', GLOB_ONLYDIR) ?: []));
}

/**
 * Every namespace a module keeps private: each top-level folder except
 * Contracts (public) and Tests, plus the classes in the module's root.
 *
 * @return list<string>
 */
function internalNamespaces(string $module): array
{
    $namespaces = [];

    foreach (glob(MODULES_PATH."/{$module}/*") ?: [] as $path) {
        $name = basename($path, '.php');

        if (in_array($name, ['Contracts', 'Tests', 'README.md'], true)) {
            continue;
        }

        if (is_dir($path) || str_ends_with($path, '.php')) {
            $namespaces[] = "Modules\\{$module}\\{$name}";
        }
    }

    return $namespaces;
}

/**
 * Lines in PHP source files under the given folders that match a pattern,
 * as "path:line: code". Module test folders are skipped.
 *
 * @param  list<string>  $folders
 * @return list<string>
 */
function sourceViolations(array $folders, string $pattern): array
{
    $violations = [];

    foreach ($folders as $folder) {
        if (! is_dir($folder)) {
            continue;
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            $path = (string) $file;

            if (! str_ends_with($path, '.php') || str_contains($path, '/Tests/')) {
                continue;
            }

            foreach (file($path) ?: [] as $number => $line) {
                if (preg_match($pattern, $line) === 1) {
                    $relative = str_replace(realpath(__DIR__.'/../..').'/', '', (string) realpath($path));
                    $violations[] = "{$relative}:".($number + 1).': '.trim($line);
                }
            }
        }
    }

    return $violations;
}

/*
| Core never uses a module. This is what lets a module be deleted without
| touching Core. Deptrac enforces it too; stating it here keeps the rule
| visible in the test run.
*/
arch('core never uses a module')
    ->expect('App')
    ->not->toUse('Modules');

/*
| A module may use another module's Contracts and nothing else. One
| expectation per pair, generated from the folders in modules/.
*/
foreach (moduleNames() as $module) {
    foreach (moduleNames() as $other) {
        if ($module === $other) {
            continue;
        }

        arch("{$module} only uses {$other} through its contracts")
            ->expect("Modules\\{$module}")
            ->not->toUse(internalNamespaces($other));
    }
}

/*
| No raw queries. DB::table() and friends skip Eloquent, which means they skip
| OrganisationScope: one line could read or change every club's data.
| DB::transaction() is fine and is not matched.
*/
test('no raw database queries in core or modules', function () {
    $pattern = '/\bDB::(table|select|insert|update|delete|statement|unprepared|affectingStatement|raw)\(/';

    expect(sourceViolations([APP_PATH, MODULES_PATH], $pattern))->toBe([]);
});

/*
| Controllers do not build queries. Reads go through query classes
| (App\Queries, Modules\{Name}\Queries) so the rules about what a user may see
| live in one place and can be reused. Writes on a model (create, update,
| delete) are fine.
*/
test('controllers do not query models directly', function () {
    $pattern = '/::(query|where\w*|find\w*|all|first\w*|pluck|count|with|latest|oldest|orderBy\w*)\(|->(where\w*|orderBy\w*|pluck|whereHas|latest|oldest|groupBy|with)\(/';

    $controllers = [APP_PATH.'/Http/Controllers', ...glob(MODULES_PATH.'/*/Http/Controllers') ?: []];

    expect(sourceViolations($controllers, $pattern))->toBe([]);
});

/*
| Services are resolved by class, never by a string key. app('fixtures') hides
| the dependency from Deptrac and PHPStan; app(FixtureLookup::class) or a
| constructor type hint keeps it visible and checkable.
*/
test('no string-keyed container resolution', function () {
    $pattern = '/\b(app|resolve)\(\s*[\'"]|->make\(\s*[\'"]|App::make\(\s*[\'"]/';

    expect(sourceViolations([APP_PATH, MODULES_PATH], $pattern))->toBe([]);
});
