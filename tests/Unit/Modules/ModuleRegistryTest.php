<?php

declare(strict_types=1);

use App\Modules\Exceptions\InvalidModuleConfiguration;
use App\Modules\Exceptions\UnknownModule;
use App\Modules\ModuleDefinition;
use App\Modules\ModuleRegistry;
use App\Modules\ModuleServiceProvider;

/**
 * @param  list<string>  $hard
 * @param  list<string>  $soft
 */
function module(string $key, array $hard = [], array $soft = []): ModuleDefinition
{
    return new class($key, $hard, $soft) extends ModuleDefinition
    {
        /**
         * @param  list<string>  $hard
         * @param  list<string>  $soft
         */
        public function __construct(private string $moduleKey, private array $hard, private array $soft) {}

        public function key(): string
        {
            return $this->moduleKey;
        }

        public function name(): string
        {
            return ucfirst($this->moduleKey);
        }

        public function serviceProvider(): string
        {
            return ModuleServiceProvider::class;
        }

        public function hardDependencies(): array
        {
            return $this->hard;
        }

        public function softDependencies(): array
        {
            return $this->soft;
        }
    };
}

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
    $registry = new ModuleRegistry([module('fixtures')]);

    expect($registry->has('fixtures'))->toBeTrue()
        ->and($registry->has('kit-shop'))->toBeFalse()
        ->and($registry->get('fixtures')->name())->toBe('Fixtures');
});

test('looking up a module that is not installed throws', function () {
    (new ModuleRegistry([]))->get('fixtures');
})->throws(UnknownModule::class);

test('keys must be lowercase kebab case', function (string $key) {
    new ModuleRegistry([module($key)]);
})->with(['Fixtures', 'match_reports', 'kit shop', '-fixtures', '1fixtures', ''])
    ->throws(InvalidModuleConfiguration::class, 'must be lowercase');

test('keys must be unique', function () {
    new ModuleRegistry([module('fixtures'), module('fixtures')]);
})->throws(InvalidModuleConfiguration::class, 'Two installed modules');

test('a hard dependency must be installed', function () {
    new ModuleRegistry([module('availability', hard: ['fixtures'])]);
})->throws(InvalidModuleConfiguration::class, 'needs [fixtures]');

test('a soft dependency may be missing', function () {
    $registry = new ModuleRegistry([module('kit-shop', soft: ['subscriptions'])]);

    expect($registry->installedSoftDependenciesOf('kit-shop'))->toBe([]);
});

test('installed soft dependencies are reported', function () {
    $registry = new ModuleRegistry([module('kit-shop', soft: ['subscriptions']), module('subscriptions')]);

    expect(keys($registry->installedSoftDependenciesOf('kit-shop')))->toBe(['subscriptions']);
});

test('dependency cycles are rejected with the path', function () {
    new ModuleRegistry([
        module('a', hard: ['b']),
        module('b', hard: ['c']),
        module('c', hard: ['a']),
    ]);
})->throws(InvalidModuleConfiguration::class, 'a -> b -> c -> a');

test('a module depending on itself is a cycle', function () {
    new ModuleRegistry([module('a', hard: ['a'])]);
})->throws(InvalidModuleConfiguration::class, 'a -> a');

test('hard dependents are the modules that would break', function () {
    $registry = new ModuleRegistry([
        module('fixtures'),
        module('availability', hard: ['fixtures']),
        module('match-reports', hard: ['fixtures']),
        module('messaging', soft: ['fixtures']),
    ]);

    expect(keys($registry->hardDependentsOf('fixtures')))->toBe(['availability', 'match-reports']);
});

test('dependency order puts every module after its hard dependencies', function () {
    $registry = new ModuleRegistry([
        module('match-reports', hard: ['fixtures']),
        module('kit-shop', soft: ['subscriptions']),
        module('availability', hard: ['fixtures']),
        module('fixtures'),
        module('subscriptions'),
    ]);

    $order = keys($registry->inDependencyOrder());

    expect($order)->toHaveCount(5)
        ->and(array_search('fixtures', $order))->toBeLessThan(array_search('match-reports', $order))
        ->and(array_search('fixtures', $order))->toBeLessThan(array_search('availability', $order));
});

test('a class in config that is not a definition is rejected', function () {
    ModuleRegistry::fromClasses([stdClass::class]);
})->throws(InvalidModuleConfiguration::class, 'is not a ModuleDefinition');
