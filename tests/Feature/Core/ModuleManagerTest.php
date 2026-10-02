<?php

declare(strict_types=1);

use App\Audit\AuditAction;
use App\Models\AuditLog;
use App\Models\Organisation;
use App\Modules\Exceptions\ModuleDependencyNotEnabled;
use App\Modules\Exceptions\ModuleHasEnabledDependents;
use App\Modules\Exceptions\UnknownModule;
use App\Modules\ModuleDefinition;
use App\Modules\ModuleManager;
use App\Modules\ModuleRegistry;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\FeatureManager;
use Tests\Fixtures\Modules\FakeModule;

beforeEach(function () {
    // The brief's dependency graph, built from fake definitions
    app()->instance(ModuleRegistry::class, new ModuleRegistry([
        FakeModule::make('fixtures'),
        FakeModule::make('availability', hard: ['fixtures']),
        FakeModule::make('match-reports', hard: ['fixtures']),
        FakeModule::make('subscriptions'),
        FakeModule::make('kit-shop', soft: ['subscriptions']),
    ]));
    app()->forgetInstance(ModuleManager::class);

    $this->modules = app(ModuleManager::class);
    $this->modules->defineFeatures();

    $this->club = Organisation::factory()->create();
    $this->otherClub = Organisation::factory()->create();
});

/**
 * @param  list<ModuleDefinition>  $modules
 * @return list<string>
 */
function moduleKeys(array $modules): array
{
    return array_map(fn (ModuleDefinition $module) => $module->key(), $modules);
}

test('installed modules start disabled for every club', function () {
    expect($this->modules->enabledFor($this->club))->toBe([])
        ->and($this->modules->isEnabled($this->club, 'fixtures'))->toBeFalse();
});

test('enabling is per organisation', function () {
    $this->modules->enable($this->club, 'fixtures');

    expect($this->modules->isEnabled($this->club, 'fixtures'))->toBeTrue()
        ->and($this->modules->isEnabled($this->otherClub, 'fixtures'))->toBeFalse();
});

test('a module cannot be enabled before its hard dependency', function () {
    $this->modules->enable($this->club, 'availability');
})->throws(ModuleDependencyNotEnabled::class, 'Enable Fixtures before enabling Availability.');

test('a module can be enabled once its hard dependency is on', function () {
    $this->modules->enable($this->club, 'fixtures');
    $this->modules->enable($this->club, 'availability');

    expect(moduleKeys($this->modules->enabledFor($this->club)))->toBe(['fixtures', 'availability']);
});

test('a dependency cannot be disabled while a dependent is on', function () {
    $this->modules->enable($this->club, 'fixtures');
    $this->modules->enable($this->club, 'availability');
    $this->modules->enable($this->club, 'match-reports');

    $this->modules->disable($this->club, 'fixtures');
})->throws(ModuleHasEnabledDependents::class, 'Disable Availability, Match Reports before disabling Fixtures.');

test('a dependency can be disabled once its dependents are off', function () {
    $this->modules->enable($this->club, 'fixtures');
    $this->modules->enable($this->club, 'availability');

    $this->modules->disable($this->club, 'availability');
    $this->modules->disable($this->club, 'fixtures');

    expect($this->modules->enabledFor($this->club))->toBe([]);
});

test('soft dependencies never block enabling or disabling', function () {
    $this->modules->enable($this->club, 'kit-shop');
    $this->modules->enable($this->club, 'subscriptions');
    $this->modules->disable($this->club, 'subscriptions');

    expect(moduleKeys($this->modules->enabledFor($this->club)))->toBe(['kit-shop']);
});

test('blockers explain what has to change first', function () {
    $this->modules->enable($this->club, 'fixtures');
    $this->modules->enable($this->club, 'availability');

    expect(moduleKeys($this->modules->blockersForEnabling($this->otherClub, 'availability')))->toBe(['fixtures'])
        ->and(moduleKeys($this->modules->blockersForDisabling($this->club, 'fixtures')))->toBe(['availability'])
        ->and($this->modules->blockersForEnabling($this->club, 'match-reports'))->toBe([]);
});

test('a dependent whose dependency flag was switched off directly is treated as disabled', function () {
    $this->modules->enable($this->club, 'fixtures');
    $this->modules->enable($this->club, 'availability');

    DB::table('features')->where('name', 'module:fixtures')->update(['value' => 'false']);
    app(FeatureManager::class)->flushCache();

    expect($this->modules->isEnabled($this->club, 'availability'))->toBeFalse();
});

test('flags are stored per organisation in the features table', function () {
    $this->modules->enable($this->club, 'fixtures');

    expect(DB::table('features')->where('name', 'module:fixtures')->value('scope'))
        ->toBe('organisation|'.$this->club->id);
});

test('enabling and disabling twice is harmless', function () {
    $this->modules->enable($this->club, 'fixtures');
    $this->modules->enable($this->club, 'fixtures');
    $this->modules->disable($this->club, 'fixtures');
    $this->modules->disable($this->club, 'fixtures');

    expect(AuditLog::where('subject_type', 'module')->count())->toBe(2);
});

test('every toggle is audited against the club', function () {
    $this->modules->enable($this->club, 'fixtures');
    $this->modules->disable($this->club, 'fixtures');

    $entries = AuditLog::where('subject_type', 'module')->orderBy('id')->get();

    expect($entries->pluck('action')->all())->toBe([AuditAction::Enable, AuditAction::Disable])
        ->and($entries->pluck('subject_id')->unique()->all())->toBe(['fixtures'])
        ->and($entries->pluck('organisation_id')->unique()->all())->toBe([$this->club->id]);
});

test('a module that is not installed is never enabled and cannot be toggled', function () {
    expect($this->modules->isEnabled($this->club, 'compliance'))->toBeFalse();

    $this->modules->enable($this->club, 'compliance');
})->throws(UnknownModule::class);
