/**
 * Compliance's public frontend entry. One link for every club user: admins
 * land on the club register, workers on their own certificates.
 */
import { ShieldCheck } from '@lucide/vue';
import { defineModule } from '@/lib/modules';
import compliance from '@/routes/compliance';

export default defineModule({
    key: 'compliance',
    navigation: [
        { title: 'Compliance', href: compliance.index(), icon: ShieldCheck },
    ],
});
