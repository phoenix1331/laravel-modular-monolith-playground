/**
 * Each seeded club's admin signs in and sees exactly the modules the brief
 * gives their club, in dependency order, with Core's links around them.
 * Every module link opens; every module the club does not have is a 404.
 *
 * Expected menus come from the brief's seed table (ClubSeeder::CLUBS), not
 * from the app, so a gating bug cannot pass by agreeing with itself.
 */
import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';

const core = { before: ['Dashboard', 'Teams'], after: ['Audit log'] };

const clubs = [
    {
        club: 'Riverside Juniors FC',
        admin: 'admin@riverside.test',
        modules: [
            'Fixtures',
            'Venues',
            'Availability',
            'Match reports',
            'Subscriptions',
            'Kit shop',
            'Kit products',
            'Compliance',
            'Announcements',
        ],
    },
    {
        club: 'Dockside Rovers',
        admin: 'admin@dockside.test',
        modules: ['Fixtures', 'Venues', 'Availability', 'Match reports'],
    },
    {
        club: 'Hillcrest Ladies',
        admin: 'admin@hillcrest.test',
        modules: ['Fixtures', 'Venues', 'Subscriptions'],
    },
    {
        club: 'Valley Vets Walking Football',
        admin: 'admin@valley.test',
        modules: [],
    },
];

// One index page per module: anything a club does not have must look absent
const moduleHomes = [
    '/fixtures',
    '/availability',
    '/match-reports',
    '/subscriptions',
    '/kit-shop',
    '/compliance',
    '/messages',
];

async function signIn(
    page: Page,
    email: string,
    landing = '**/dashboard',
): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    // exact: the "Show password" button also has "password" in its label
    await page.getByLabel('Password', { exact: true }).fill('password');
    await page.getByRole('button', { name: 'Log in' }).click();
    await page.waitForURL(landing);
}

function mainNav(page: Page) {
    return page
        .locator('[data-sidebar="group"]')
        .first()
        .locator('[data-sidebar="menu-button"]');
}

for (const { club, admin, modules } of clubs) {
    test(`${club}: the menu shows exactly its enabled modules`, async ({
        page,
    }) => {
        await signIn(page, admin);

        await expect(mainNav(page)).toHaveText([
            ...core.before,
            ...modules,
            ...core.after,
        ]);
    });

    test(`${club}: module links open, other modules are a 404`, async ({
        page,
    }) => {
        await signIn(page, admin);

        const links = await mainNav(page).evaluateAll((elements) =>
            elements.map(
                (element) =>
                    new URL((element as HTMLAnchorElement).href).pathname,
            ),
        );

        for (const path of links) {
            const response = await page.request.get(path);
            expect(response.status(), `${path} for ${club}`).toBe(200);
        }

        for (const home of moduleHomes.filter(
            (path) => !links.some((link) => link.startsWith(path)),
        )) {
            const response = await page.request.get(home);
            expect(response.status(), `${home} for ${club}`).toBe(404);
        }
    });
}

test('the super admin sees the platform menu and no module links', async ({
    page,
}) => {
    await signIn(page, 'super@platform.test', '**/platform');

    await expect(mainNav(page)).toHaveText(['Dashboard', 'Audit log']);
});
