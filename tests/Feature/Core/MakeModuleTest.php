<?php

declare(strict_types=1);

use App\Modules\ModuleRegistry;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Tests\Fixtures\Modules\FakeModule;

/*
| make:module writes into the project, so each test points the app at a temp
| folder holding copies of the files the command reads and edits. The real
| repository is never touched. The full end-to-end proof (generate, then run
| every gate and the uninstall check) is done by hand; see CONTEXT.md.
*/

beforeEach(function () {
    $this->realBase = base_path();
    $this->sandbox = sys_get_temp_dir().'/make-module-'.bin2hex(random_bytes(4));

    $files = new Filesystem;
    foreach (['config/modules.php', 'deptrac.yaml', '.github/workflows/ci.yml'] as $file) {
        $files->ensureDirectoryExists(dirname("{$this->sandbox}/{$file}"));
        $files->copy("{$this->realBase}/{$file}", "{$this->sandbox}/{$file}");
    }
    $files->copyDirectory("{$this->realBase}/stubs", "{$this->sandbox}/stubs");

    app()->setBasePath($this->sandbox);

    // Installed modules for dependency checks, independent of what is really installed
    app()->instance(ModuleRegistry::class, new ModuleRegistry([
        FakeModule::make('fixtures'),
        FakeModule::make('availability', hard: ['fixtures']),
        FakeModule::make('subscriptions'),
    ]));
});

afterEach(function () {
    app()->setBasePath($this->realBase);
    (new Filesystem)->deleteDirectory($this->sandbox);
});

function makeModuleFile(string $path): string
{
    return (string) file_get_contents(test()->sandbox.'/'.$path);
}

test('it scaffolds the PHP module, its frontend entry and page', function () {
    $this->artisan('make:module', ['name' => 'Rehearsal', '--description' => "Coaches' rehearsal sessions."])
        ->assertSuccessful();

    foreach ([
        'modules/Rehearsal/RehearsalModule.php',
        'modules/Rehearsal/RehearsalServiceProvider.php',
        'modules/Rehearsal/Routes/web.php',
        'modules/Rehearsal/Http/Controllers/RehearsalController.php',
        'modules/Rehearsal/Tests/RehearsalTest.php',
        'resources/js/modules/rehearsal/index.ts',
        'resources/js/modules/rehearsal/pages/Index.vue',
    ] as $path) {
        expect("{$this->sandbox}/{$path}")->toBeFile();
        expect(makeModuleFile($path))->not->toContain('{{');
    }

    expect(makeModuleFile('modules/Rehearsal/RehearsalModule.php'))
        ->toContain("return 'rehearsal';")
        ->toContain("return 'Coaches\\' rehearsal sessions.';")
        ->not->toContain('hardDependencies');

    expect(makeModuleFile('resources/js/modules/rehearsal/index.ts'))
        ->toContain("import rehearsal from '@/routes/rehearsal';");
});

test('it installs the module in config/modules.php with its import in order', function () {
    $this->artisan('make:module', ['name' => 'Rehearsal'])->assertSuccessful();

    $config = makeModuleFile('config/modules.php');

    preg_match_all('/^use Modules\\\\.+;$/m', $config, $imports);
    $sorted = $imports[0];
    sort($sorted);

    expect($imports[0])->toContain('use Modules\Rehearsal\RehearsalModule;')
        ->and($imports[0])->toBe($sorted)
        ->and($config)->toMatch("/        RehearsalModule::class,\n    \\],/");

    // Still a valid config file that lists the new class
    expect((require "{$this->sandbox}/config/modules.php")['installed'])->toContain('Modules\Rehearsal\RehearsalModule');
});

test('it gives the module Deptrac layers, a ruleset reaching hard dependencies through their contracts, and a graph group', function () {
    $this->artisan('make:module', ['name' => 'Rehearsal', '--hard' => ['availability'], '--soft' => ['subscriptions']])
        ->assertSuccessful();

    $deptrac = Yaml::parse(makeModuleFile('deptrac.yaml'))['deptrac'];
    $layers = array_column($deptrac['layers'], 'collectors', 'name');

    expect($layers['Rehearsal'][0]['value'])->toBe('^Modules\\\\Rehearsal\\\\(?!Contracts\\\\).*')
        ->and($layers['RehearsalContracts'][0]['value'])->toBe('^Modules\\\\Rehearsal\\\\Contracts\\\\.*')
        ->and($deptrac['ruleset']['Rehearsal'])->toBe(['Core', 'Framework', 'RehearsalContracts', 'AvailabilityContracts'])
        ->and($deptrac['ruleset']['RehearsalContracts'])->toBe(['Framework'])
        ->and($deptrac['formatters']['graphviz']['groups']['Rehearsal'])->toBe(['Rehearsal', 'RehearsalContracts']);

    // Annotations and markers survive the edit
    expect(makeModuleFile('deptrac.yaml'))
        ->toContain('# make:module adds new module layers above this line')
        ->toContain('Deptrac reads every class under `paths`');

    expect(makeModuleFile('modules/Rehearsal/RehearsalModule.php'))
        ->toContain("return ['availability'];")
        ->toContain("return ['subscriptions'];");

    // The generated test enables dependencies first
    expect(makeModuleFile('modules/Rehearsal/Tests/RehearsalTest.php'))
        ->toContain("foreach (['fixtures', 'availability', 'rehearsal'] as \$key)");
});

test('it adds the module to the CI uninstall matrix', function () {
    $this->artisan('make:module', ['name' => 'Rehearsal'])->assertSuccessful();

    $matrix = Yaml::parse(makeModuleFile('.github/workflows/ci.yml'))['jobs']['uninstall']['strategy']['matrix']['module'];

    expect(end($matrix))->toBe('Rehearsal');
});

test('it derives a kebab key from a multi-word name', function () {
    $this->artisan('make:module', ['name' => 'RehearsalPlans'])->assertSuccessful();

    expect("{$this->sandbox}/resources/js/modules/rehearsal-plans/index.ts")->toBeFile()
        ->and(makeModuleFile('resources/js/modules/rehearsal-plans/index.ts'))->toContain("import rehearsalPlans from '@/routes/rehearsal-plans';")
        ->and(makeModuleFile('modules/RehearsalPlans/RehearsalPlansModule.php'))->toContain("return 'Rehearsal Plans';");
});

test('it refuses bad input without writing anything', function (array $arguments, string $message) {
    $this->artisan('make:module', $arguments)->expectsOutputToContain($message)->assertFailed();

    expect("{$this->sandbox}/modules")->not->toBeDirectory()
        ->and(makeModuleFile('config/modules.php'))->toBe(file_get_contents("{$this->realBase}/config/modules.php"));
})->with([
    'not PascalCase' => [['name' => 'rehearsal'], 'must be PascalCase'],
    'key already installed' => [['name' => 'Fixtures'], 'already exists'],
    'hard dependency not installed' => [['name' => 'Rehearsal', '--hard' => ['kit-shop']], 'is not installed'],
    'both hard and soft' => [['name' => 'Rehearsal', '--hard' => ['fixtures'], '--soft' => ['fixtures']], 'both hard and soft'],
]);
