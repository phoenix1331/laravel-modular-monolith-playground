/**
 * Match Reports' public frontend entry. Shown only to clubs with the module
 * enabled, which requires Fixtures to be enabled first.
 */
import { Trophy } from '@lucide/vue';
import { defineModule } from '@/lib/modules';
import matchReports from '@/routes/match-reports';

export default defineModule({
    key: 'match-reports',
    navigation: [
        { title: 'Match reports', href: matchReports.index(), icon: Trophy },
    ],
});
