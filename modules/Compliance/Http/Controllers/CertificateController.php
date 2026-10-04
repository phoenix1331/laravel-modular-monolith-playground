<?php

declare(strict_types=1);

namespace Modules\Compliance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Queries\ClubWorkers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Compliance\Http\Requests\CertificateRequest;
use Modules\Compliance\Models\Certificate;
use Modules\Compliance\Models\CertificateStatus;
use Modules\Compliance\Queries\CertificateRegister;

/**
 * One page, two views: admins get the whole club's register; workers get
 * their own certificates and nothing else.
 */
class CertificateController extends Controller
{
    public function index(Request $request, CertificateRegister $register, ClubWorkers $workers): Response
    {
        Gate::authorize('viewAny', Certificate::class);

        /** @var User $user */
        $user = $request->user();

        if (! $user->can('manage', Certificate::class)) {
            return Inertia::render('compliance/pages/Mine', [
                'certificates' => $register->heldBy($user),
            ]);
        }

        $filter = in_array($request->query('status'), CertificateRegister::FILTERS, true) ? (string) $request->query('status') : 'attention';

        return Inertia::render('compliance/pages/Register', [
            'filter' => $filter,
            'workers' => $register->workers($filter),
            'options' => [
                'workers' => $workers->query()->get(['id', 'name']),
                'types' => Certificate::TYPES,
            ],
            'expiringWithinDays' => CertificateStatus::EXPIRING_WITHIN_DAYS,
        ]);
    }

    public function store(CertificateRequest $request): RedirectResponse
    {
        Gate::authorize('manage', Certificate::class);

        Certificate::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Certificate recorded.')]);

        return back();
    }

    public function destroy(Certificate $certificate): RedirectResponse
    {
        Gate::authorize('delete', $certificate);

        $certificate->delete();

        return back();
    }
}
