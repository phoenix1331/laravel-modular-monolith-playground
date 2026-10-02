import type { Role } from '@/types/auth';
import type { NavItem } from '@/types/navigation';

/**
 * A module enabled for the signed-in user's club, shared on every page as
 * `enabledModules`. Disabled and uninstalled modules are never sent.
 */
export type EnabledModule = {
    key: string;
    name: string;
};

/**
 * A navigation link a module contributes. `roles` limits it to some club
 * roles, e.g. admin-only settings; omit it to show the link to every club user.
 */
export type ModuleNavItem = NavItem & {
    roles?: Exclude<Role, 'super_admin'>[];
};

/**
 * What a module's public entry, `resources/js/modules/{key}/index.ts`,
 * default-exports. This is the only file other code may import from a module.
 */
export type FrontendModule = {
    /** Must match the backend module key and the folder name. */
    key: string;
    /** Links added to the main navigation while the module is enabled. */
    navigation?: ModuleNavItem[];
};
