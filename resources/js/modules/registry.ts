/**
 * Frontend module registry: the frontend half of "installed is not enabled".
 *
 * Installed: every `resources/js/modules/{key}/index.ts` is picked up by the
 * glob below at build time. Deleting a module's folder removes it from the
 * bundle with no other change.
 *
 * Enabled: the server shares `enabledModules` for the signed-in user's club.
 * Navigation is built only from modules in that list, so a disabled module
 * never shows a link, and its routes 404 on the server anyway.
 *
 * Each module's `index.ts` is its only public entry. Core and other modules
 * import a module through here or through its index.ts, never from its
 * `pages/` or internal components.
 */
import type {
    EnabledModule,
    FrontendModule,
    ModuleNavItem,
    NavItem,
    Role,
} from '@/types';

type ModuleEntries = Record<string, { default: FrontendModule }>;

/**
 * Index entries by key, checking each module's key matches its folder so the
 * frontend and backend always agree on names.
 */
export function collectModules(
    entries: ModuleEntries,
): Map<string, FrontendModule> {
    const modules = new Map<string, FrontendModule>();

    for (const [path, entry] of Object.entries(entries)) {
        const folder = path.split('/').at(-2);
        const module = entry.default;

        if (module.key !== folder) {
            throw new Error(
                `Module in "${folder}" declares key "${module.key}". The key must match the folder name.`,
            );
        }

        modules.set(module.key, module);
    }

    return modules;
}

/**
 * Navigation links from enabled modules, in the server's order (dependencies
 * first), filtered by the user's role. Enabled modules with no frontend entry
 * are skipped.
 */
export function navigationFor(
    modules: Map<string, FrontendModule>,
    enabled: EnabledModule[],
    role: Role,
): NavItem[] {
    if (role === 'super_admin') {
        return [];
    }

    return enabled.flatMap(({ key }) =>
        (modules.get(key)?.navigation ?? [])
            .filter(
                (item: ModuleNavItem) =>
                    !item.roles || item.roles.includes(role),
            )
            .map(({ roles: _roles, ...item }) => item),
    );
}

export const modules = collectModules(
    import.meta.glob<{ default: FrontendModule }>('./*/index.ts', {
        eager: true,
    }),
);
