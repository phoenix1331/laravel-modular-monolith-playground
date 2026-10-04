/**
 * Subscriptions' public frontend entry. Admin-only link: fees are the
 * treasurer's job, and the server enforces the same rule in FeePolicy.
 */
import { PoundSterling } from '@lucide/vue';
import { defineModule } from '@/lib/modules';
import subscriptions from '@/routes/subscriptions';

export default defineModule({
    key: 'subscriptions',
    navigation: [
        {
            title: 'Subscriptions',
            href: subscriptions.index(),
            icon: PoundSterling,
            roles: ['admin'],
        },
    ],
});
