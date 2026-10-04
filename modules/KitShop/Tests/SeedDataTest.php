<?php

declare(strict_types=1);

use App\Capabilities\MembershipStatus;
use App\Models\Organisation;
use App\Tenancy\CurrentOrganisation;
use Modules\KitShop\Models\Order;
use Modules\KitShop\Models\Stock;

beforeEach(fn () => $this->seed());

test('Riverside\'s shop is stocked, has a sold-out size and orders only from paid-up members', function () {
    $riverside = Organisation::where('slug', 'riverside-juniors-fc')->sole();

    app(CurrentOrganisation::class)->runAs($riverside, function () {
        $orders = Order::all();

        expect($orders)->toHaveCount(8)
            ->and($orders->whereNotNull('fulfilled_at'))->toHaveCount(4)
            ->and($orders->every(fn (Order $order) => app(MembershipStatus::class)->isPaidUp($order->player_id)))->toBeTrue()
            ->and(Stock::where('quantity', 0)->count())->toBe(1);
    });
});
