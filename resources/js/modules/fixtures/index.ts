/**
 * Fixtures' public frontend entry. The only file outside this folder may
 * import from it.
 *
 * The registry (modules/registry.ts) picks this up at build time and adds the
 * links below to the main navigation, but only for clubs that have Fixtures
 * enabled. Pages in ./pages are reached through Inertia, never imported.
 */
import { CalendarDays, MapPin } from '@lucide/vue';
import { defineModule } from '@/lib/modules';
import fixtures from '@/routes/fixtures';

export default defineModule({
    key: 'fixtures',
    navigation: [
        { title: 'Fixtures', href: fixtures.index(), icon: CalendarDays },
        {
            title: 'Venues',
            href: fixtures.venues.index(),
            icon: MapPin,
            roles: ['admin'],
        },
    ],
});
