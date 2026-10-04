<?php

declare(strict_types=1);

use App\Models\Organisation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
| The brief's scenario for a dependent module, against the seeded clubs:
| Hillcrest has Fixtures but not Availability, Valley Vets runs on Core alone.
*/

beforeEach(fn () => $this->seed());

function availabilityRow(array $modules): array
{
    return collect($modules)->firstWhere('key', 'availability');
}

test('Hillcrest has fixtures but availability is hidden and 404s', function () {
    $this->actingAs(User::where('email', 'admin@hillcrest.test')->sole());

    $this->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->where(
        'enabledModules',
        fn ($modules) => collect($modules)->pluck('key')->contains('fixtures')
            && ! collect($modules)->pluck('key')->contains('availability'),
    ));

    $this->get(route('availability.index'))->assertNotFound();
});

test('Dockside coaches use availability', function () {
    $this->actingAs(User::where('email', 'coach1@dockside.test')->sole())
        ->get(route('availability.index'))
        ->assertOk();
});

test('the dashboard lets Hillcrest switch availability on, but Valley must enable fixtures first', function () {
    $this->actingAs(User::where('email', 'super@platform.test')->sole());

    $hillcrest = Organisation::where('slug', 'hillcrest-ladies')->sole();
    $valley = Organisation::where('slug', 'valley-vets')->sole();

    $this->get(route('platform.organisations.show', $hillcrest))->assertInertia(
        fn (Assert $page) => expect(availabilityRow($page->toArray()['props']['modules']))
            ->toMatchArray(['enabled' => false, 'enable_blocked_by' => []]),
    );

    $this->get(route('platform.organisations.show', $valley))->assertInertia(
        fn (Assert $page) => expect(availabilityRow($page->toArray()['props']['modules']))
            ->toMatchArray(['enabled' => false, 'enable_blocked_by' => ['Fixtures']]),
    );

    $this->put(route('platform.organisations.modules.update', [$valley, 'availability']), ['enabled' => true])
        ->assertSessionHasErrors(['module' => 'Enable Fixtures before enabling Availability.']);
});

test('Riverside cannot switch fixtures off while its dependents are on', function () {
    $riverside = Organisation::where('slug', 'riverside-juniors-fc')->sole();

    $this->actingAs(User::where('email', 'super@platform.test')->sole())
        ->put(route('platform.organisations.modules.update', [$riverside, 'fixtures']), ['enabled' => false])
        ->assertSessionHasErrors('module');

    $this->get(route('platform.organisations.show', $riverside))->assertInertia(
        fn (Assert $page) => expect(collect($page->toArray()['props']['modules'])->firstWhere('key', 'fixtures')['disable_blocked_by'])
            ->toContain('Availability'),
    );
});
