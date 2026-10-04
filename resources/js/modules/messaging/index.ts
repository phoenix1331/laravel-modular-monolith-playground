/**
 * Messaging's public frontend entry.
 */
import { Megaphone } from '@lucide/vue';
import { defineModule } from '@/lib/modules';
import messaging from '@/routes/messaging';

export default defineModule({
    key: 'messaging',
    navigation: [
        { title: 'Announcements', href: messaging.index(), icon: Megaphone },
    ],
});
