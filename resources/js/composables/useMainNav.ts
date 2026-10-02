import { usePage } from '@inertiajs/vue3';
import { LayoutGrid, ScrollText, Users } from '@lucide/vue';
import { computed } from 'vue';
import type { ComputedRef } from 'vue';
import TeamController from '@/actions/App/Http/Controllers/Teams/TeamController';
import { modules, navigationFor } from '@/modules/registry';
import audit from '@/routes/audit';
import platform from '@/routes/platform';
import type { NavItem } from '@/types';

/**
 * Main navigation for the signed-in user, shared by the sidebar and header.
 *
 * Items depend on role: the super admin works on the platform, club users
 * inside their club, and only admins see the audit trail. Club users also get
 * a link from each module enabled for their club (see modules/registry.ts).
 * The server still enforces every rule; hiding links just avoids offering
 * pages that would 403 or 404.
 */
export function useMainNav(): ComputedRef<NavItem[]> {
    const page = usePage();

    return computed(() => {
        const { user, home } = page.props.auth;
        const { enabledModules } = page.props;
        const items: NavItem[] = [
            { title: 'Dashboard', href: home, icon: LayoutGrid },
        ];

        if (user.role === 'super_admin') {
            items.push({
                title: 'Audit log',
                href: platform.audit.index(),
                icon: ScrollText,
            });

            return items;
        }

        items.push({
            title: 'Teams',
            href: TeamController.index(),
            icon: Users,
        });

        items.push(...navigationFor(modules, enabledModules, user.role));

        if (user.role === 'admin') {
            items.push({
                title: 'Audit log',
                href: audit.index(),
                icon: ScrollText,
            });
        }

        return items;
    });
}
