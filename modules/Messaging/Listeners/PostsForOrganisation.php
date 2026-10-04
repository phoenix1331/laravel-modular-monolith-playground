<?php

declare(strict_types=1);

namespace Modules\Messaging\Listeners;

use App\Models\Organisation;
use App\Modules\ModuleManager;
use App\Tenancy\CurrentOrganisation;
use Modules\Messaging\Models\Announcement;

/**
 * Shared by Messaging's listeners: post as the event's club, and only if that
 * club has Messaging enabled.
 *
 * Events arrive from anywhere: a web request, a queued job, the daily
 * console command. The event's organisationId says which club it belongs
 * to, so the listener checks the flag for that club and writes inside
 * runAs(), never relying on whoever happens to be signed in.
 *
 * organisation_id is set explicitly as well: events also fire while seeding,
 * when model events (and so the tenancy auto-fill) are switched off.
 */
trait PostsForOrganisation
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    private function postFor(int $organisationId, array $attributes): void
    {
        $organisation = Organisation::find($organisationId);

        if ($organisation === null || ! app(ModuleManager::class)->isEnabled($organisation, 'messaging')) {
            return;
        }

        app(CurrentOrganisation::class)->runAs($organisation, function () use ($organisation, $attributes): void {
            $announcement = new Announcement($attributes);
            $announcement->organisation_id = $organisation->id;
            $announcement->save();
        });
    }
}
