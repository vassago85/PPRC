import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright configuration for PPRC's end-to-end smoke suite.
 *
 * We keep the browser install lean (Chromium only) and point at a running
 * Laravel instance rather than orchestrating `php artisan serve` from
 * Playwright — Laragon already serves the app locally, and the CI wrapper
 * is expected to bring its own server up. Override the URL via `E2E_BASE_URL`
 * when needed (e.g. `E2E_BASE_URL=http://pprc.test npm run e2e`).
 */
export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: [['list']],
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://pprc.test',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
