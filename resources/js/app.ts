import { createInertiaApp } from '@inertiajs/vue3';
import type { DefineComponent } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    // Core pages live in ./pages and are named by their path there
    // (`teams/Index`). Module pages live in ./modules/{key}/pages and are
    // named by their path under ./modules (`fixtures/pages/Index`), so
    // deleting a module's folder removes its pages with no other change.
    resolve: async (name) => {
        const pages = import.meta.glob<DefineComponent>([
            './pages/**/*.vue',
            './modules/*/pages/**/*.vue',
        ]);
        const page =
            pages[`./pages/${name}.vue`] ?? pages[`./modules/${name}.vue`];

        if (!page) {
            throw new Error(`Page not found: ${name}`);
        }

        const module = await page();

        return (module as { default?: DefineComponent }).default ?? module;
    },
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    withApp: (app) => {
        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
