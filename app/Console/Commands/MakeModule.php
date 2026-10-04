<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\ModuleRegistry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Scaffolds a module that works on the first run: a gated page, its menu
 * link, a test, and every place the boundary tooling needs to know about it.
 *
 * | Creates                                   | Edits                               |
 * |-------------------------------------------|-------------------------------------|
 * | modules/{Name}/{Name}Module.php           | config/modules.php (installs it)    |
 * | modules/{Name}/{Name}ServiceProvider.php  | deptrac.yaml (layers, ruleset,      |
 * | modules/{Name}/Routes/web.php             |   graph group)                      |
 * | modules/{Name}/Http/Controllers/...       | .github/workflows/ci.yml (uninstall |
 * | modules/{Name}/Tests/{Name}Test.php       |   check matrix)                     |
 * | resources/js/modules/{key}/index.ts       |                                     |
 * | resources/js/modules/{key}/pages/Index.vue|                                     |
 *
 * Templates live in stubs/module. Edits are placed at marker comments
 * ("make:module adds new ... above this line") rather than by re-writing
 * the YAML, so the annotations in those files survive.
 *
 * Core still never imports a module: everything here is text. A module's
 * PHP folder and Deptrac layers are its key in PascalCase (the convention
 * eslint.config.js relies on too), so `--hard=match-reports` lets the new
 * module use `MatchReportsContracts`.
 */
#[Signature('make:module
    {name : The module name in PascalCase, e.g. Training}
    {--key= : Override the key (defaults to the name in kebab case)}
    {--description= : One sentence shown on the platform dashboard}
    {--hard=* : Keys of installed modules this one cannot work without}
    {--soft=* : Keys of modules this one uses when they are present}')]
#[Description('Scaffold a new module: PHP, frontend entry, Deptrac layers, config and CI')]
class MakeModule extends Command
{
    private const string MARKER = 'make:module adds new %s above this line';

    public function handle(Filesystem $files, ModuleRegistry $registry): int
    {
        $name = (string) $this->argument('name');
        $key = (string) ($this->option('key') ?: Str::kebab($name));
        /** @var list<string> $hard */
        $hard = array_values(array_unique((array) $this->option('hard')));
        /** @var list<string> $soft */
        $soft = array_values(array_unique((array) $this->option('soft')));

        $problem = $this->problemWith($files, $registry, $name, $key, $hard, $soft);

        if ($problem !== null) {
            $this->components->error($problem);

            return self::FAILURE;
        }

        $title = Str::headline($name);
        $description = (string) ($this->option('description') ?: "{$title} for each club.");

        $values = [
            'name' => $name,
            'key' => $key,
            'camel' => Str::camel($key),
            'title' => $title,
            'description' => str_replace("'", "\\'", $description),
            'descriptionHtml' => e($description),
            'dependencies' => $this->dependencyMethods($hard, $soft),
            'enable' => $this->phpList([...$this->inEnableOrder($registry, $hard), $key]),
        ];

        $module = base_path("modules/{$name}");
        $frontend = resource_path("js/modules/{$key}");

        $created = [
            "{$module}/{$name}Module.php" => 'Module.php',
            "{$module}/{$name}ServiceProvider.php" => 'ServiceProvider.php',
            "{$module}/Routes/web.php" => 'routes.php',
            "{$module}/Http/Controllers/{$name}Controller.php" => 'Controller.php',
            "{$module}/Tests/{$name}Test.php" => 'Test.php',
            "{$frontend}/index.ts" => 'index.ts',
            "{$frontend}/pages/Index.vue" => 'Index.vue',
        ];

        foreach ($created as $path => $stub) {
            $files->ensureDirectoryExists(dirname($path));
            $files->put($path, $this->render($files, $stub, $values));
            $this->components->task('Created '.$this->relative($path));
        }

        $this->format($files, ["{$frontend}/index.ts", "{$frontend}/pages/Index.vue"]);
        $this->install($files, $name);
        $this->addDeptracLayers($files, $name, $hard);
        $this->addToUninstallMatrix($files, $name);

        $this->newLine();
        $this->components->info("{$title} is installed but not enabled for any club.");
        $this->components->bulletList([
            'composer dump-autoload, then make migrate when you add a migration',
            'Enable it for a club as super@platform.test',
            'make check, make graph, make uninstall-check MODULE='.$name,
        ]);

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $hard
     * @param  list<string>  $soft
     */
    private function problemWith(Filesystem $files, ModuleRegistry $registry, string $name, string $key, array $hard, array $soft): ?string
    {
        if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $name) !== 1) {
            return "The name must be PascalCase, e.g. Training. Got [{$name}].";
        }

        if (preg_match('/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', $key) !== 1) {
            return "The key must be lowercase kebab case, e.g. match-reports. Got [{$key}].";
        }

        if ($registry->has($key) || $files->exists(base_path("modules/{$name}")) || $files->exists(resource_path("js/modules/{$key}"))) {
            return "A module called {$name} ({$key}) already exists.";
        }

        foreach ($hard as $dependency) {
            if (! $registry->has($dependency)) {
                return "Hard dependency [{$dependency}] is not installed. Hard dependencies must be installed modules.";
            }
        }

        if (array_intersect($hard, $soft) !== []) {
            return 'A dependency cannot be both hard and soft.';
        }

        return null;
    }

    /**
     * @param  list<string>  $hard
     * @param  list<string>  $soft
     */
    private function dependencyMethods(array $hard, array $soft): string
    {
        $methods = '';

        foreach (['hardDependencies' => $hard, 'softDependencies' => $soft] as $method => $keys) {
            if ($keys !== []) {
                $methods .= "\n    public function {$method}(): array\n    {\n        return {$this->phpList($keys)};\n    }\n";
            }
        }

        return $methods;
    }

    /**
     * The module's hard dependencies, plus theirs, dependencies first, so the
     * generated test can enable them without tripping the dependency rule.
     *
     * @param  list<string>  $hard
     * @return list<string>
     */
    private function inEnableOrder(ModuleRegistry $registry, array $hard): array
    {
        $needed = [];
        $collect = function (string $key) use (&$collect, &$needed, $registry): void {
            foreach ($registry->get($key)->hardDependencies() as $dependency) {
                $collect($dependency);
            }
            $needed[$key] = true;
        };

        foreach ($hard as $key) {
            $collect($key);
        }

        return array_keys($needed);
    }

    /**
     * @param  list<string>  $keys
     */
    private function phpList(array $keys): string
    {
        return '['.implode(', ', array_map(fn (string $key) => "'{$key}'", $keys)).']';
    }

    /**
     * @param  array<string, string>  $values
     */
    private function render(Filesystem $files, string $stub, array $values): string
    {
        $template = $files->get(base_path("stubs/module/{$stub}.stub"));

        return str_replace(
            array_map(fn (string $placeholder) => "{{ {$placeholder} }}", array_keys($values)),
            array_values($values),
            $template,
        );
    }

    /**
     * Wrapping in the generated TypeScript and Vue depends on how long the
     * module's name is, so no stub layout suits every name. Run the
     * project's own formatter over them instead, when it is installed.
     *
     * @param  list<string>  $paths
     */
    private function format(Filesystem $files, array $paths): void
    {
        $formatter = base_path('node_modules/.bin/vp');

        if (! $files->exists($formatter)) {
            $this->components->warn('Formatter not found: run `npx vp check --fix` after npm install.');

            return;
        }

        $result = Process::path(base_path())->run([$formatter, 'fmt', ...$paths]);

        $this->components->task('Formatted the frontend files', fn () => $result->successful());
    }

    private function install(Filesystem $files, string $name): void
    {
        $path = config_path('modules.php');
        $config = $files->get($path);

        // Add the import with the others, kept in alphabetical order
        preg_match_all('/^use Modules\\\\.+;$/m', $config, $matches);
        $imports = [...$matches[0], "use Modules\\{$name}\\{$name}Module;"];
        sort($imports);
        $config = (string) preg_replace('/^use Modules\\\\.+;\n/m', '', $config);
        $config = (string) preg_replace('/^declare\(strict_types=1\);\n/m', "declare(strict_types=1);\n".implode("\n", $imports)."\n", $config, 1);

        // Then the class at the end of the installed list
        $config = (string) preg_replace("/('installed' => \\[\n(?:.*\n)*?)(    \\],)/", "\$1        {$name}Module::class,\n\$2", $config, 1, $count);

        if ($count !== 1) {
            throw new RuntimeException('Could not find the installed list in config/modules.php.');
        }

        $files->put($path, $config);
        $this->components->task('Installed in config/modules.php');
    }

    /**
     * @param  list<string>  $hard
     */
    private function addDeptracLayers(Filesystem $files, string $name, array $hard): void
    {
        $path = base_path('deptrac.yaml');
        $yaml = $files->get($path);

        $layers = <<<YAML
                # {$name}: generated by make:module
                - name: {$name}
                  collectors:
                      - type: classLike
                        value: ^Modules\\\\{$name}\\\\(?!Contracts\\\\).*
                - name: {$name}Contracts
                  collectors:
                      - type: classLike
                        value: ^Modules\\\\{$name}\\\\Contracts\\\\.*


        YAML;

        $allowed = ['Core', 'Framework', "{$name}Contracts"];

        foreach ($hard as $dependency) {
            // A hard dependency is reached through its Contracts layer only
            $allowed[] = Str::studly($dependency).'Contracts';
        }

        $ruleset = "        {$name}:\n".implode('', array_map(fn (string $layer) => "            - {$layer}\n", $allowed))
            ."\n        {$name}Contracts:\n            - Framework\n\n";

        $group = "                {$name}:\n                    - {$name}\n                    - {$name}Contracts\n";

        $yaml = $this->insertAbove($yaml, 'module layers', $layers, 'deptrac.yaml');
        $yaml = $this->insertAbove($yaml, 'module rulesets', $ruleset, 'deptrac.yaml');
        $yaml = $this->insertAbove($yaml, 'module groups', $group, 'deptrac.yaml');

        $files->put($path, $yaml);
        $this->components->task('Added layers, ruleset and graph group to deptrac.yaml');
    }

    private function addToUninstallMatrix(Filesystem $files, string $name): void
    {
        $path = base_path('.github/workflows/ci.yml');

        if (! $files->exists($path)) {
            return;
        }

        $files->put($path, $this->insertAbove($files->get($path), 'modules', "                    - {$name}\n", 'ci.yml'));
        $this->components->task('Added to the uninstall check matrix in ci.yml');
    }

    /**
     * Insert text on the lines above a marker comment, keeping the marker.
     */
    private function insertAbove(string $contents, string $what, string $text, string $file): string
    {
        $marker = sprintf(self::MARKER, $what);

        if (preg_match('/^[ \t]*# '.preg_quote($marker, '/').'$/m', $contents, $match, PREG_OFFSET_CAPTURE) !== 1) {
            throw new RuntimeException("Could not find the marker \"# {$marker}\" in {$file}.");
        }

        return substr_replace($contents, $text, $match[0][1], 0);
    }

    private function relative(string $path): string
    {
        return Str::after($path, base_path().DIRECTORY_SEPARATOR);
    }
}
