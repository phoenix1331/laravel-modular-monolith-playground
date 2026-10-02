<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules;

use App\Modules\ModuleDefinition;
use App\Modules\ModuleServiceProvider;

/**
 * A module definition built in a test, so dependency rules can be exercised
 * without real modules: FakeModule::make('availability', hard: ['fixtures']).
 */
final class FakeModule extends ModuleDefinition
{
    /**
     * @param  list<string>  $hard
     * @param  list<string>  $soft
     */
    private function __construct(private readonly string $moduleKey, private readonly array $hard, private readonly array $soft) {}

    /**
     * @param  list<string>  $hard
     * @param  list<string>  $soft
     */
    public static function make(string $key, array $hard = [], array $soft = []): self
    {
        return new self($key, $hard, $soft);
    }

    public function key(): string
    {
        return $this->moduleKey;
    }

    public function name(): string
    {
        return ucwords(str_replace('-', ' ', $this->moduleKey));
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
}
