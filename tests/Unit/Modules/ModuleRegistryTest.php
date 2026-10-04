<?php

declare(strict_types=1);

use App\Modules\Exceptions\InvalidModuleConfiguration;
use App\Modules\Exceptions\UnknownModule;
use App\Modules\ModuleDefinition;
use App\Modules\ModuleRegistry;
use Tests\Fixtures\Modules\FakeModule;

/**
 * @param  list<ModuleDefinition>  $modules
 * @return list<string>
 */
function keys(array $modules): array
{
    return array_map(fn (ModuleDefinition $module) => $module->key(), $modules);
}

test('an empty registry is valid: Core runs on its own', function () {
    expect((new ModuleRegistry([]))->all())->toBe([]);
});

test('modules are looked up by key', function () {
    $registry = new ModuleRegistry([FakeModule::make('fixtures')]);

    expect($registry->has('fixtures'))->toBeTrue()
        ->and($registry->has('kit-shop'))->toBeFalse()
        ->and($registry->get('fixtures')->name())->toBe('Fixtures');
});

test('looking up a module that is not installed throws', function () {
    (new ModuleRegistry([]))->get('fixtures');
})->throws(UnknownModule::class);

test('keys must be lowercase kebab case', function (string $key) {
    new ModuleRegistry([FakeModule::make($key)]);
})->with(['Fixtures', 'match_reports', 'kit shop', '-fixtures', '1fixtures', ''])
    ->throws(InvalidModuleConfiguration::class, 'must be lowercase');

test('keys must be unique', function () {
    new ModuleRegistry([FakeModule::make('fixtures'), FakeModule::make('fixtures')]);
})->throws(InvalidModuleConfiguration::class, 'Two installed modules');

test('a hard dependency must be installed', function () {
    new ModuleRegistry([FakeModule::make('availability', hard: ['fixtures'])]);
})->throws(InvalidModuleConfiguration::class, 'needs [fixtures]');

test('a soft dependency may be missing', function () {
    $registry = new ModuleRegistry([FakeModule::make('kit-shop', soft: ['subscriptions'])]);

    expect($registry->installedSoftDependenciesOf('kit-shop'))->toBe([]);
});

test('installed soft dependencies are reported', function () {
    $registry = new ModuleRegistry([FakeModule::make('kit-shop', soft: ['subscriptions']), FakeModule::make('subscriptions')]);

    expect(keys($registry->installedSoftDependenciesOf('kit-shop')))->toBe(['subscriptions']);
});

test('dependency cycles are rejected with the path', function () {
    new ModuleRegistry([
        FakeModule::make('a', hard: ['b']),
        FakeModule::make('b', hard: ['c']),
        FakeModule::make('c', hard: ['a']),
    ]);
})->throws(InvalidModuleConfiguration::class, 'a -> b -> c -> a');

test('a module depending on itself is a cycle', function () {
    new ModuleRegistry([FakeModule::make('a', hard: ['a'])]);
})->throws(InvalidModuleConfiguration::class, 'a -> a');

test('hard dependents are the modules that would break', function () {
    $registry = new ModuleRegistry([
        FakeModule::make('fixtures'),
        FakeModule::make('availability', hard: ['fixtures']),
        FakeModule::make('match-reports', hard: ['fixtures']),
        FakeModule::make('messaging', soft: ['fixtures']),
    ]);

    expect(keys($registry->hardDependentsOf('fixtures')))->toBe(['availability', 'match-reports']);
});

test('dependency order puts every module after its hard dependencies', function () {
    $registry = new ModuleRegistry([
        FakeModule::make('match-reports', hard: ['fixtures']),
        FakeModule::make('kit-shop', soft: ['subscriptions']),
        FakeModule::make('availability', hard: ['fixtures']),
        FakeModule::make('fixtures'),
        FakeModule::make('subscriptions'),
    ]);

    $order = keys($registry->inDependencyOrder());

    expect($order)->toHaveCount(5)
        ->and(array_search('fixtures', $order))->toBeLessThan(array_search('match-reports', $order))
        ->and(array_search('fixtures', $order))->toBeLessThan(array_search('availability', $order));
});

test('seeding order also puts installed soft dependencies first', function () {
    $registry = new ModuleRegistry([
        FakeModule::make('messaging', soft: ['fixtures', 'subscriptions']),
        FakeModule::make('kit-shop', soft: ['subscriptions', 'not-installed']),
        FakeModule::make('availability', hard: ['fixtures']),
        FakeModule::make('subscriptions'),
        FakeModule::make('fixtures'),
    ]);

    $order = keys($registry->inSeedingOrder());

    expect($order)->toHaveCount(5)
        ->and(array_search('subscriptions', $order))->toBeLessThan(array_search('kit-shop', $order))
        ->and(array_search('subscriptions', $order))->toBeLessThan(array_search('messaging', $order))
        ->and(array_search('fixtures', $order))->toBeLessThan(array_search('messaging', $order))
        ->and(array_search('fixtures', $order))->toBeLessThan(array_search('availability', $order));
});

test('modules that soft-depend on each other still get a seeding order', function () {
    $registry = new ModuleRegistry([
        FakeModule::make('alpha', soft: ['beta']),
        FakeModule::make('beta', soft: ['alpha']),
    ]);

    expect(keys($registry->inSeedingOrder()))->toBe(['beta', 'alpha']);
});

test('a class in config that is not a definition is rejected', function () {
    ModuleRegistry::fromClasses([stdClass::class]);
})->throws(InvalidModuleConfiguration::class, 'is not a ModuleDefinition');
