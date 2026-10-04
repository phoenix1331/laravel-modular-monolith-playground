/**
 * Browser tests: the seeded clubs, signed in for real, checking that each
 * club's menu shows exactly its enabled modules.
 *
 * They run in the `playwright` service (docker-compose.yml) against the
 * running stack, so the app, MySQL, the seed data and Vite are the real ones.
 * Run `make fresh` first, then `make e2e`.
 */
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: 'tests/e2e',
    // Not *.spec.ts or *.test.ts, which Vitest would pick up too
    testMatch: '**/*.e2e.ts',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:8000',
        trace: 'retain-on-failure',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
