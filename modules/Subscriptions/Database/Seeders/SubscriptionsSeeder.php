<?php

declare(strict_types=1);

namespace Modules\Subscriptions\Database\Seeders;

use App\Models\Organisation;
use App\Models\Player;
use App\Models\Team;
use App\Modules\ModuleManager;
use App\Tenancy\CurrentOrganisation;
use Illuminate\Database\Seeder;
use Modules\Subscriptions\Actions\NotifyOverdueFees;
use Modules\Subscriptions\Actions\TakePayment;
use Modules\Subscriptions\Models\Fee;

/**
 * Season subs for every player at clubs with Subscriptions enabled, paid
 * through the fake gateway, with two deliberate gaps per club:
 *
 * | Who                                           | State                       |
 * |-----------------------------------------------|-----------------------------|
 * | Highest shirt number in the first two teams   | Season subs overdue         |
 * | Everyone in the first team                    | Training levy due in 3 weeks |
 *
 * Payments go through TakePayment and overdue fees through NotifyOverdueFees,
 * the same actions the app and the daily command use. That fires
 * PaymentOverdue, so a club with Messaging on gets its admins-only reminders
 * and KitShop sees the overdue players through MembershipStatus. Neither
 * module is named here.
 */
final class SubscriptionsSeeder extends Seeder
{
    public function run(ModuleManager $modules, CurrentOrganisation $current, TakePayment $pay, NotifyOverdueFees $notify): void
    {
        foreach (Organisation::query()->orderBy('id')->get() as $organisation) {
            if ($modules->isEnabled($organisation, 'subscriptions')) {
                $current->runAs($organisation, function () use ($organisation, $pay, $notify): void {
                    $this->seedClub($organisation, $pay);
                    $notify->handle();
                });
            }
        }
    }

    private function seedClub(Organisation $organisation, TakePayment $pay): void
    {
        $teams = Team::query()->where('organisation_id', $organisation->id)->with('players')->orderBy('id')->get();

        $overdue = $teams->take(2)->map(fn (Team $team) => $team->players->sortByDesc('shirt_number')->first()?->id)->filter()->all();

        foreach ($teams as $index => $team) {
            $season = str_starts_with($team->age_group, 'U') ? 4500 : 9000;

            foreach ($team->players as $player) {
                $fee = $this->fee($player, '2026/27 season subs', $season, now()->subWeeks(5));

                if (! in_array($player->id, $overdue, true)) {
                    $pay->handle($fee);
                }

                if ($index === 0) {
                    $this->fee($player, 'Winter training levy', 1500, now()->addWeeks(3));
                }
            }
        }
    }

    private function fee(Player $player, string $description, int $pence, \DateTimeInterface $due): Fee
    {
        $fee = new Fee([
            'player_id' => $player->id,
            'description' => $description,
            'amount_pence' => $pence,
            'due_on' => $due->format('Y-m-d'),
        ]);
        $fee->organisation_id = (int) $player->organisation_id;
        $fee->save();

        return $fee;
    }
}
