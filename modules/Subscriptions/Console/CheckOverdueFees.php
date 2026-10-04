<?php

declare(strict_types=1);

namespace Modules\Subscriptions\Console;

use App\Models\Organisation;
use App\Modules\ModuleManager;
use App\Tenancy\CurrentOrganisation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Modules\Subscriptions\Actions\NotifyOverdueFees;

/**
 * Runs daily (scheduled by SubscriptionsServiceProvider).
 *
 * A console command has no signed-in user, so there is no current
 * organisation and the tenancy scope would match nothing. The command loops
 * over clubs with Subscriptions enabled and runs the check *as* each one with
 * CurrentOrganisation::runAs(). Clubs without the module are skipped: their
 * old fees stay untouched until the module is switched back on.
 */
#[Signature('subscriptions:check-overdue')]
#[Description('Announce membership fees that have become overdue')]
final class CheckOverdueFees extends Command
{
    public function handle(ModuleManager $modules, CurrentOrganisation $current, NotifyOverdueFees $notify): int
    {
        $total = 0;

        foreach (Organisation::query()->orderBy('id')->get() as $organisation) {
            if (! $modules->isEnabled($organisation, 'subscriptions')) {
                continue;
            }

            $count = $current->runAs($organisation, fn () => $notify->handle());
            $total += $count;

            if ($count > 0) {
                $this->components->info("{$organisation->name}: {$count} newly overdue");
            }
        }

        $this->components->info("{$total} overdue fees announced.");

        return self::SUCCESS;
    }
}
