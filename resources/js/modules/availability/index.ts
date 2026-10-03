/**
 * Availability's public frontend entry. The link only appears for clubs with
 * Availability enabled, which the server only allows once Fixtures is on.
 */
import { UserCheck } from '@lucide/vue';
import { defineModule } from '@/lib/modules';
import availability from '@/routes/availability';

export default defineModule({
    key: 'availability',
    navigation: [
        { title: 'Availability', href: availability.index(), icon: UserCheck },
    ],
});
