<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Organisation;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Modules\ModuleManager;
use App\Modules\ModuleRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * The four clubs from the brief, each exercising a different module setup
 * once modules exist:
 *
 * | Club                          | Shape                                  |
 * |-------------------------------|----------------------------------------|
 * | Riverside Juniors FC          | Youth academy, every module            |
 * | Dockside Rovers               | Typical small club, match-day modules  |
 * | Hillcrest Ladies              | Fixtures and subscriptions only        |
 * | Valley Vets Walking Football  | Core only                              |
 *
 * Every login uses the password `password`. Emails follow
 * `{role}@{club}.test`, e.g. admin@riverside.test, coach1@riverside.test.
 *
 * Seeders run without model events, so tenancy auto-fill, write guards and
 * audit logging are off here. Every organisation_id is therefore set
 * explicitly.
 *
 * Module flags: each club lists the modules the brief gives it, by key.
 * Only the ones currently installed are enabled, dependencies first, through
 * ModuleManager so the same rules apply as in the dashboard. Installing a
 * module later enables it here automatically; uninstalling one simply skips
 * it, so seeding never depends on a particular module existing.
 */
class ClubSeeder extends Seeder
{
    /**
     * @var list<array{name: string, slug: string, domain: string, admin: string, workers: list<string>, modules: list<string>, teams: list<array{name: string, age_group: string, ages: array{int, int}, coaches: list<int>}>}>
     */
    public const array CLUBS = [
        [
            'name' => 'Riverside Juniors FC',
            'slug' => 'riverside-juniors-fc',
            'domain' => 'riverside.test',
            'admin' => 'Helen Marsh',
            'workers' => ['Tom Okafor', 'Priya Shah', 'Gareth Lewis'],
            'modules' => ['fixtures', 'availability', 'match-reports', 'subscriptions', 'kit-shop', 'compliance', 'messaging'],
            'teams' => [
                ['name' => 'U9 Foxes', 'age_group' => 'U9', 'ages' => [8, 9], 'coaches' => [0]],
                ['name' => 'U11 Badgers', 'age_group' => 'U11', 'ages' => [10, 11], 'coaches' => [0, 1]],
                ['name' => 'U13 Hawks', 'age_group' => 'U13', 'ages' => [12, 13], 'coaches' => [1]],
                ['name' => 'U15 Wolves', 'age_group' => 'U15', 'ages' => [14, 15], 'coaches' => [2]],
            ],
        ],
        [
            'name' => 'Dockside Rovers',
            'slug' => 'dockside-rovers',
            'domain' => 'dockside.test',
            'admin' => 'Steve Doyle',
            'workers' => ['Kemi Adeyemi', 'Liam Walsh'],
            'modules' => ['fixtures', 'availability', 'match-reports'],
            'teams' => [
                ['name' => 'First Team', 'age_group' => 'Seniors', 'ages' => [19, 34], 'coaches' => [0]],
                ['name' => 'Reserves', 'age_group' => 'Seniors', 'ages' => [18, 30], 'coaches' => [1]],
            ],
        ],
        [
            'name' => 'Hillcrest Ladies',
            'slug' => 'hillcrest-ladies',
            'domain' => 'hillcrest.test',
            'admin' => 'Rachel Price',
            'workers' => ['Amira Hassan', 'Jo Bennett'],
            'modules' => ['fixtures', 'subscriptions'],
            'teams' => [
                ['name' => 'Ladies First XI', 'age_group' => 'Seniors', 'ages' => [18, 35], 'coaches' => [0]],
                ['name' => 'U16 Girls', 'age_group' => 'U16', 'ages' => [15, 16], 'coaches' => [1]],
            ],
        ],
        [
            'name' => 'Valley Vets Walking Football',
            'slug' => 'valley-vets',
            'domain' => 'valley.test',
            'admin' => 'Brian Hughes',
            'workers' => ['Malcolm Reid', 'Pat Connolly'],
            'modules' => [],
            'teams' => [
                ['name' => 'Over 50s', 'age_group' => 'O50', 'ages' => [50, 59], 'coaches' => [0]],
                ['name' => 'Over 60s', 'age_group' => 'O60', 'ages' => [60, 74], 'coaches' => [1]],
            ],
        ],
    ];

    private const int PLAYERS_PER_TEAM = 12;

    private string $password;

    public function run(): void
    {
        // Same names on every `make fresh`, so docs and screenshots stay valid
        fake()->seed(1331);

        // Hash once: bcrypt is deliberately slow and every login shares the password
        $this->password = Hash::make('password');

        foreach (self::CLUBS as $club) {
            $organisation = Organisation::create(['name' => $club['name'], 'slug' => $club['slug']]);

            $this->user($organisation, Role::Admin, $club['admin'], "admin@{$club['domain']}");

            $workers = [];
            foreach ($club['workers'] as $index => $name) {
                $workers[] = $this->user($organisation, Role::Worker, $name, 'coach'.($index + 1)."@{$club['domain']}");
            }

            foreach ($club['teams'] as $teamData) {
                $team = new Team(['name' => $teamData['name'], 'age_group' => $teamData['age_group']]);
                $team->organisation_id = $organisation->id;
                $team->save();

                $team->workers()->attach(array_map(fn (int $index) => $workers[$index]->id, $teamData['coaches']));

                $this->players($team, $teamData['ages']);
            }

            $this->enableModules($organisation, $club['modules']);
        }
    }

    /**
     * @param  list<string>  $wanted
     */
    private function enableModules(Organisation $organisation, array $wanted): void
    {
        $modules = app(ModuleManager::class);

        foreach (app(ModuleRegistry::class)->inDependencyOrder() as $module) {
            if (in_array($module->key(), $wanted, true)) {
                $modules->enable($organisation, $module->key());
            }
        }
    }

    private function user(Organisation $organisation, Role $role, string $name, string $email): User
    {
        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => $this->password,
        ]);
        $user->role = $role;
        $user->organisation_id = $organisation->id;
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    /**
     * @param  array{int, int}  $ages
     */
    private function players(Team $team, array $ages): void
    {
        [$youngest, $oldest] = $ages;
        $shirts = range(1, self::PLAYERS_PER_TEAM);

        foreach ($shirts as $shirt) {
            Player::factory()->for($team)->create([
                'organisation_id' => $team->organisation_id,
                'date_of_birth' => fake()->dateTimeBetween("-{$oldest} years", "-{$youngest} years")->format('Y-m-d'),
                'shirt_number' => $shirt,
            ]);
        }
    }
}
