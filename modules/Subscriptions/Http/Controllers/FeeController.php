<?php

declare(strict_types=1);

namespace Modules\Subscriptions\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Subscriptions\Actions\ChargeTeam;
use Modules\Subscriptions\Actions\TakePayment;
use Modules\Subscriptions\Http\Requests\ChargeTeamRequest;
use Modules\Subscriptions\Models\Fee;
use Modules\Subscriptions\Queries\FeeLedger;

class FeeController extends Controller
{
    public function index(Request $request, FeeLedger $ledger): Response
    {
        Gate::authorize('viewAny', Fee::class);

        $filter = in_array($request->query('status'), FeeLedger::FILTERS, true) ? (string) $request->query('status') : 'due';

        return Inertia::render('subscriptions/pages/Index', [
            'filter' => $filter,
            'totals' => $ledger->totals(),
            'fees' => $ledger->fees($filter)->map(fn (Fee $fee) => [
                'id' => $fee->id,
                'player' => $fee->player->full_name,
                'team' => $fee->player->team->name,
                'description' => $fee->description,
                'amount_pence' => $fee->amount_pence,
                'due_on' => $fee->due_on->toDateString(),
                'paid_at' => $fee->paid_at?->toIso8601String(),
                'overdue' => $fee->isOverdue(),
            ])->values(),
            'teams' => $ledger->teams(),
        ]);
    }

    public function store(ChargeTeamRequest $request, ChargeTeam $charge, FeeLedger $ledger): RedirectResponse
    {
        Gate::authorize('create', Fee::class);

        $count = $charge->handle(
            $ledger->team($request->integer('team_id')),
            (string) $request->validated('description'),
            $request->amountPence(),
            (string) $request->validated('due_on'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice(':count fee raised.|:count fees raised.', $count)]);

        return to_route('subscriptions.index');
    }

    public function pay(Fee $fee, TakePayment $payment): RedirectResponse
    {
        Gate::authorize('pay', $fee);

        $result = $payment->handle($fee);

        if (! $result->successful) {
            throw ValidationException::withMessages(['payment' => "Payment failed: {$result->failureReason}"]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment taken.')]);

        return back();
    }

    public function destroy(Fee $fee): RedirectResponse
    {
        Gate::authorize('delete', $fee);

        $fee->delete();

        return back();
    }
}
