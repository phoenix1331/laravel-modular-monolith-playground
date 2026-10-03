<?php

declare(strict_types=1);

namespace Modules\Fixtures\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Fixtures\Http\Requests\VenueRequest;
use Modules\Fixtures\Models\Venue;
use Modules\Fixtures\Queries\FixtureFormOptions;

class VenueController extends Controller
{
    public function index(FixtureFormOptions $options): Response
    {
        Gate::authorize('viewAny', Venue::class);

        return Inertia::render('fixtures/pages/Venues', [
            'venues' => $options->venues(),
        ]);
    }

    public function store(VenueRequest $request): RedirectResponse
    {
        Gate::authorize('create', Venue::class);

        Venue::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Venue added.')]);

        return to_route('fixtures.venues.index');
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        Gate::authorize('delete', $venue);

        $venue->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Venue removed. Its fixtures now have no venue.')]);

        return to_route('fixtures.venues.index');
    }
}
