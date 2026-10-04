<?php

declare(strict_types=1);

namespace Modules\Compliance\Database\Seeders;

use App\Enums\Role;
use App\Models\Organisation;
use App\Models\User;
use App\Modules\ModuleManager;
use App\Tenancy\CurrentOrganisation;
use Illuminate\Database\Seeder;
use Modules\Compliance\Models\Certificate;

/**
 * Certificates for every worker at clubs with Compliance enabled.
 *
 * Two deliberate gaps for the demo, from the brief:
 * - coach3@riverside.test (Gareth Lewis): Safeguarding Children expired
 * - coach2@riverside.test (Priya Shah): First Aid expiring in three weeks
 *
 * Runs without model events (DatabaseSeeder), so organisation_id is set
 * explicitly and nothing is audited.
 */
final class ComplianceSeeder extends Seeder
{
    public function run(ModuleManager $modules, CurrentOrganisation $current): void
    {
        foreach (Organisation::query()->orderBy('id')->get() as $organisation) {
            if ($modules->isEnabled($organisation, 'compliance')) {
                // A seeder has no signed-in user, so tenant-scoped queries match
                // nothing unless they run as the club
                $current->runAs($organisation, fn () => $this->seedClub($organisation));
            }
        }
    }

    private function seedClub(Organisation $organisation): void
    {
        $workers = User::query()
            ->where('organisation_id', $organisation->id)
            ->where('role', Role::Worker)
            ->get();

        foreach ($workers as $worker) {
            $this->certificate($worker, 'DBS check', now()->subYear(), now()->addYears(2));
            $this->certificate($worker, 'Safeguarding Children', now()->subMonths(6), now()->addMonths(30));
        }

        $this->override($organisation, 'coach3@', 'Safeguarding Children', now()->subYears(3), now()->subMonth());
        $this->override($organisation, 'coach2@', 'First Aid', now()->subYears(3)->addWeeks(3), now()->addWeeks(3));
    }

    private function override(Organisation $organisation, string $emailPrefix, string $type, \DateTimeInterface $issued, \DateTimeInterface $expires): void
    {
        if ($organisation->slug !== 'riverside-juniors-fc') {
            return;
        }

        $worker = User::query()->where('organisation_id', $organisation->id)->where('email', 'like', $emailPrefix.'%')->first();

        if ($worker === null) {
            return;
        }

        Certificate::query()->where('user_id', $worker->id)->where('type', $type)->delete();
        $this->certificate($worker, $type, $issued, $expires);
    }

    private function certificate(User $worker, string $type, \DateTimeInterface $issued, \DateTimeInterface $expires): void
    {
        $certificate = new Certificate([
            'user_id' => $worker->id,
            'type' => $type,
            'reference' => strtoupper(substr($type, 0, 3)).'-'.str_pad((string) $worker->id, 5, '0', STR_PAD_LEFT),
            'issued_on' => $issued->format('Y-m-d'),
            'expires_on' => $expires->format('Y-m-d'),
        ]);
        $certificate->organisation_id = (int) $worker->organisation_id;
        $certificate->save();
    }
}
