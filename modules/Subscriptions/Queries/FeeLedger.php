<?php

declare(strict_types=1);

namespace Modules\Subscriptions\Queries;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Subscriptions\Models\Fee;

/**
 * The club's fees, filtered by status, with totals for the summary bar.
 */
final class FeeLedger
{
    public const array FILTERS = ['due', 'overdue', 'paid', 'all'];

    /**
     * @return Collection<int, Fee>
     */
    public function fees(string $filter): Collection
    {
        $query = Fee::query()->with('player.team:id,name')->orderBy('due_on')->orderBy('id');

        match ($filter) {
            'due' => $query->whereNull('paid_at'),
            'overdue' => $query->overdue(),
            'paid' => $query->whereNotNull('paid_at'),
            default => null,
        };

        return $query->limit(500)->get();
    }

    /**
     * @return array{outstanding_pence: int, overdue_pence: int, collected_pence: int}
     */
    public function totals(): array
    {
        $sum = fn (Builder $query): int => (int) $query->sum('amount_pence');

        return [
            'outstanding_pence' => $sum(Fee::query()->whereNull('paid_at')),
            'overdue_pence' => $sum(Fee::query()->overdue()),
            'collected_pence' => $sum(Fee::query()->whereNotNull('paid_at')),
        ];
    }

    /**
     * Teams that can be charged, for the form.
     *
     * @return Collection<int, Team>
     */
    public function teams(): Collection
    {
        return Team::query()->orderBy('name')->get(['id', 'name']);
    }

    public function team(int $teamId): Team
    {
        return Team::findOrFail($teamId);
    }
}
