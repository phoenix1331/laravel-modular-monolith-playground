<?php

declare(strict_types=1);

namespace Modules\Messaging;

use App\Modules\ModuleDefinition;
use App\Modules\ModuleRegistry;
use App\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\Fixtures\Contracts\Events\FixtureScheduled;
use Modules\Messaging\Listeners\AnnounceFixture;
use Modules\Messaging\Listeners\RemindOverduePayment;
use Modules\Subscriptions\Contracts\Events\PaymentOverdue;

/**
 * Subscribes to other modules' public events, but only to modules that are
 * installed. `FixtureScheduled::class` is just a string at this point, so
 * nothing from Fixtures is loaded unless the event actually fires; and if
 * Fixtures is removed from config/modules.php the listener is never
 * registered at all.
 *
 * Whether Messaging is *enabled* for the club is checked inside each
 * listener, per event.
 */
final class MessagingServiceProvider extends ModuleServiceProvider
{
    protected function definition(): ModuleDefinition
    {
        return new MessagingModule;
    }

    public function boot(): void
    {
        parent::boot();

        $installed = $this->app->make(ModuleRegistry::class);

        if ($installed->has('fixtures')) {
            Event::listen(FixtureScheduled::class, AnnounceFixture::class);
        }

        if ($installed->has('subscriptions')) {
            Event::listen(PaymentOverdue::class, RemindOverduePayment::class);
        }
    }
}
