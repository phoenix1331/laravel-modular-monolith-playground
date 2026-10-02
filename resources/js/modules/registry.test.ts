import { describe, expect, test } from 'vite-plus/test';
import type { FrontendModule } from '@/types';
import { collectModules, navigationFor } from './registry';

const fixtures: FrontendModule = {
    key: 'fixtures',
    navigation: [{ title: 'Fixtures', href: '/fixtures' }],
};

const subscriptions: FrontendModule = {
    key: 'subscriptions',
    navigation: [
        { title: 'Subscriptions', href: '/subscriptions', roles: ['admin'] },
    ],
};

const modules = collectModules({
    './fixtures/index.ts': { default: fixtures },
    './subscriptions/index.ts': { default: subscriptions },
});

describe('collectModules', () => {
    test('indexes modules by key', () => {
        expect([...modules.keys()]).toEqual(['fixtures', 'subscriptions']);
    });

    test('rejects a key that does not match the folder', () => {
        expect(() =>
            collectModules({
                './fixtures/index.ts': { default: { key: 'other' } },
            }),
        ).toThrow('must match the folder name');
    });
});

describe('navigationFor', () => {
    test('only enabled modules contribute links', () => {
        const nav = navigationFor(
            modules,
            [{ key: 'fixtures', name: 'Fixtures' }],
            'admin',
        );

        expect(nav.map((item) => item.title)).toEqual(['Fixtures']);
    });

    test('nothing is shown when no modules are enabled', () => {
        expect(navigationFor(modules, [], 'admin')).toEqual([]);
    });

    test('links follow the enabled order from the server', () => {
        const nav = navigationFor(
            modules,
            [
                { key: 'subscriptions', name: 'Subscriptions' },
                { key: 'fixtures', name: 'Fixtures' },
            ],
            'admin',
        );

        expect(nav.map((item) => item.title)).toEqual([
            'Subscriptions',
            'Fixtures',
        ]);
    });

    test('role-limited links are hidden from other roles', () => {
        const enabled = [{ key: 'subscriptions', name: 'Subscriptions' }];

        expect(navigationFor(modules, enabled, 'admin')).toHaveLength(1);
        expect(navigationFor(modules, enabled, 'worker')).toEqual([]);
    });

    test('an enabled module without a frontend entry is skipped', () => {
        expect(
            navigationFor(
                modules,
                [{ key: 'compliance', name: 'Compliance' }],
                'admin',
            ),
        ).toEqual([]);
    });

    test('the super admin gets no module links', () => {
        expect(
            navigationFor(
                modules,
                [{ key: 'fixtures', name: 'Fixtures' }],
                'super_admin',
            ),
        ).toEqual([]);
    });

    test('the roles field never reaches the rendered nav item', () => {
        const [item] = navigationFor(
            modules,
            [{ key: 'subscriptions', name: 'Subscriptions' }],
            'admin',
        );

        expect(item).not.toHaveProperty('roles');
    });
});
