/**
 * A module enabled for the signed-in user's club, shared on every page as
 * `enabledModules`. Disabled and uninstalled modules are never sent.
 */
export type EnabledModule = {
    key: string;
    name: string;
};
