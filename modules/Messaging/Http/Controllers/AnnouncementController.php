<?php

declare(strict_types=1);

namespace Modules\Messaging\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Messaging\Http\Requests\AnnouncementRequest;
use Modules\Messaging\Models\Announcement;
use Modules\Messaging\Queries\Noticeboard;

class AnnouncementController extends Controller
{
    public function index(Request $request, Noticeboard $board): Response
    {
        Gate::authorize('viewAny', Announcement::class);

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('messaging/pages/Index', [
            'announcements' => $board->for($user)->map(fn (Announcement $announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'team' => $announcement->team?->name,
                'audience' => $announcement->audience,
                'source' => $announcement->source,
                'author' => $announcement->author?->name,
                'created_at' => $announcement->created_at?->toIso8601String(),
                'can_delete' => $user->can('delete', $announcement),
            ])->values(),
            'teams' => $board->postableTeams($user),
            'can' => ['postClubWide' => $user->isAdmin()],
        ]);
    }

    public function store(AnnouncementRequest $request, Noticeboard $board): RedirectResponse
    {
        $team = $board->team($request->validated('team_id') === null ? null : $request->integer('team_id'));
        Gate::authorize('create', [Announcement::class, $team]);

        Announcement::create([
            'team_id' => $team?->id,
            'audience' => Announcement::AUDIENCE_TEAM,
            'source' => 'manual',
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
            'author_id' => $request->user()?->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement posted.')]);

        return to_route('messaging.index');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        Gate::authorize('delete', $announcement);

        $announcement->delete();

        return back();
    }
}
