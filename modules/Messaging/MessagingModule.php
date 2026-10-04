<?php

declare(strict_types=1);

namespace Modules\Messaging;

use App\Modules\ModuleDefinition;

/**
 * Messaging: a club noticeboard that other modules' events can post to.
 *
 * Soft-depends on Fixtures and Subscriptions, through their public events
 * only: when a fixture is scheduled the team hears about it, and when a
 * payment goes overdue admins get a reminder. With neither installed it is
 * still a working noticeboard.
 */
final class MessagingModule extends ModuleDefinition
{
    public function key(): string
    {
        return 'messaging';
    }

    public function name(): string
    {
        return 'Messaging';
    }

    public function description(): string
    {
        return 'Team announcements, including automatic ones from other modules.';
    }

    public function softDependencies(): array
    {
        return ['fixtures', 'subscriptions'];
    }

    public function serviceProvider(): string
    {
        return MessagingServiceProvider::class;
    }
}
