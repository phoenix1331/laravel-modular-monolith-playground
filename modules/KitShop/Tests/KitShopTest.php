<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Organisation;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Modules\ModuleManager;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\KitShop\Models\Order;
use Modules\KitShop\Models\Product;
use Modules\KitShop\Models\Stock;

/*
| Subscriptions is treated as a black box: overdue fees are raised through
| its HTTP route, and KitShop only ever sees the effect through Core's
| MembershipStatus capability.
*/

beforeEach(function () {
    $this->club = Organisation::factory()->create();
    app(ModuleManager::class)->enable($this->club, 'kit-shop');

    $this->admin = User::factory()->admin()->forOrganisation($this->club)->create();
    $this->coach = User::factory()->worker()->forOrganisation($this->club)->create();

    $this->team = Team::factory()->for($this->club)->create();
    $this->team->workers()->attach($this->coach);
    $this->player = Player::factory()->for($this->team)->create(['first_name' => 'Sam', 'last_name' => 'Jones']);

    $this->actingAs($this->admin)->post(route('kit-shop.products.store'), [
        'name' => 'Home shirt',
        'price' => '25.00',
        'stock' => 'YS=2, YM=5',
    ])->assertSessionHasNoErrors();

    $this->shirt = Product::sole();
});

function kitShopOrder(array $overrides = []): array
{
    return ['player_id' => test()->player->id, 'product_id' => test()->shirt->id, 'size' => 'YS', 'quantity' => 1, ...$overrides];
}

/**
 * Raise an overdue fee for the team through Subscriptions' own route.
 */
function kitShopOverdueFee(): void
{
    test()->actingAs(test()->admin)->post(route('subscriptions.fees.store'), [
        'team_id' => test()->team->id,
        'description' => 'Last season subs',
        'amount' => '50.00',
        'due_on' => now()->subWeek()->toDateString(),
    ])->assertSessionHasNoErrors();
}

describe('without subscriptions (soft dependency absent)', function () {
    test('the shop works and nothing is restricted', function () {
        $this->actingAs($this->coach)->get(route('kit-shop.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('kit-shop/pages/Shop')
                ->where('membershipTracked', false)
                ->where('players.0.paid_up', true));

        $this->post(route('kit-shop.orders.store'), kitShopOrder())->assertSessionHasNoErrors();

        expect(Order::count())->toBe(1);
    });

    test('kit shop can be enabled without subscriptions', function () {
        expect(app(ModuleManager::class)->blockersForEnabling(Organisation::factory()->create(), 'kit-shop'))->toBe([]);
    });

    test('fees left over from when subscriptions was on do not block orders', function () {
        app(ModuleManager::class)->enable($this->club, 'subscriptions');
        kitShopOverdueFee();
        app(ModuleManager::class)->disable($this->club, 'subscriptions');

        $this->actingAs($this->coach)->post(route('kit-shop.orders.store'), kitShopOrder())->assertSessionHasNoErrors();
    });
});

describe('with subscriptions (soft dependency present)', function () {
    beforeEach(fn () => app(ModuleManager::class)->enable($this->club, 'subscriptions'));

    test('a paid-up player can order', function () {
        $this->actingAs($this->coach)->get(route('kit-shop.index'))
            ->assertInertia(fn (Assert $page) => $page->where('membershipTracked', true)->where('players.0.paid_up', true));

        $this->post(route('kit-shop.orders.store'), kitShopOrder())->assertSessionHasNoErrors();
    });

    test('a player with overdue subscriptions is refused, with the reason', function () {
        kitShopOverdueFee();

        $this->actingAs($this->coach)->get(route('kit-shop.index'))
            ->assertInertia(fn (Assert $page) => $page->where('players.0.paid_up', false));

        $this->post(route('kit-shop.orders.store'), kitShopOrder())
            ->assertSessionHasErrors(['order' => 'Sam Jones has overdue subscriptions. Orders are limited to paid-up members.']);

        expect(Order::count())->toBe(0)
            ->and(Stock::where('size', 'YS')->value('quantity'))->toBe(2);
    });
});

describe('stock', function () {
    test('ordering takes items out of stock and totals the price', function () {
        $this->actingAs($this->coach)->post(route('kit-shop.orders.store'), kitShopOrder(['size' => 'ym', 'quantity' => 3]));

        expect(Stock::where('size', 'YM')->value('quantity'))->toBe(2)
            ->and(Order::sole()->total_pence)->toBe(7500);
    });

    test('an order larger than the stock is refused', function () {
        $this->actingAs($this->coach)
            ->post(route('kit-shop.orders.store'), kitShopOrder(['quantity' => 3]))
            ->assertSessionHasErrors(['order' => 'Only 2 Home shirt left in size YS.']);
    });

    test('admins set stock and add products; coaches cannot', function () {
        $this->actingAs($this->admin)->put(route('kit-shop.products.stock', $this->shirt), ['size' => 'YL', 'quantity' => 7]);
        expect(Stock::where('size', 'YL')->value('quantity'))->toBe(7);

        $this->actingAs($this->coach);
        $this->get(route('kit-shop.products.index'))->assertForbidden();
        $this->put(route('kit-shop.products.stock', $this->shirt), ['size' => 'YL', 'quantity' => 99])->assertForbidden();
    });

    test('stock must be written as size=quantity pairs', function () {
        $this->actingAs($this->admin)
            ->post(route('kit-shop.products.store'), ['name' => 'Away shirt', 'price' => 25, 'stock' => 'small, medium'])
            ->assertSessionHasErrors('stock');
    });
});

describe('who can order and fulfil', function () {
    test('a coach cannot order for a player outside their teams', function () {
        $outsider = Player::factory()->for(Team::factory()->for($this->club))->create();

        $this->actingAs($this->coach)
            ->post(route('kit-shop.orders.store'), kitShopOrder(['player_id' => $outsider->id]))
            ->assertForbidden();
    });

    test('admins mark orders handed out, once', function () {
        $this->actingAs($this->coach)->post(route('kit-shop.orders.store'), kitShopOrder());
        $order = Order::sole();

        $this->actingAs($this->coach)->post(route('kit-shop.orders.fulfil', $order))->assertForbidden();
        $this->actingAs($this->admin)->post(route('kit-shop.orders.fulfil', $order))->assertRedirect();
        $this->post(route('kit-shop.orders.fulfil', $order))->assertForbidden();

        expect($order->refresh()->fulfilled_at)->not->toBeNull();
    });

    test('orders are audited under the module\'s type', function () {
        $this->actingAs($this->coach)->post(route('kit-shop.orders.store'), kitShopOrder());

        expect(AuditLog::where('subject_type', 'kit-shop.order')->value('organisation_id'))->toBe($this->club->id);
    });
});
