<?php

declare(strict_types=1);

namespace Modules\MatchReports;

use App\Modules\ModuleDefinition;

/**
 * MatchReports: the score, who scored and how each player did, filed after a
 * fixture is played.
 *
 * Hard-depends on Fixtures (by key), like Availability. The two dependents
 * know nothing about each other; each talks only to Fixtures' contracts.
 */
final class MatchReportsModule extends ModuleDefinition
{
    public function key(): string
    {
        return 'match-reports';
    }

    public function name(): string
    {
        return 'Match Reports';
    }

    public function description(): string
    {
        return 'Scores, scorers and player ratings for played fixtures.';
    }

    public function hardDependencies(): array
    {
        return ['fixtures'];
    }

    public function serviceProvider(): string
    {
        return MatchReportsServiceProvider::class;
    }
}
