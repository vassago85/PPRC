import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { expect, test } from '@playwright/test';

const execFileAsync = promisify(execFile);

/**
 * Family-household happy path.
 *
 * The Livewire form flows use Blade + Alpine.js, so we drive it end-to-end
 * in Chromium: sign in as an active adult, add a junior from the Membership
 * page, enter the junior into an open match, then confirm the junior appears
 * under "My Registrations".
 *
 * Fixture state is seeded through a small artisan command so the test is
 * deterministic across runs — same email, same match slug, family list
 * cleared on every invocation.
 */

const PARENT_EMAIL = 'e2e-parent@example.test';
const PARENT_PASSWORD = 'Password123!';
const MATCH_SLUG = 'e2e-family-match';

test.beforeAll(async () => {
    // php artisan e2e:seed-household — deterministic parent + open match.
    // Assumes the Laravel app is running with a working DB. See README /
    // deployment notes for the env variables Playwright expects.
    await execFileAsync('php', ['artisan', 'e2e:seed-household'], {
        cwd: process.cwd(),
        env: process.env,
    });
});

test('parent can add a junior and enter them in an open match', async ({ page }) => {
    // 1. Sign in as the parent.
    await page.goto('/login');
    await page.getByLabel('Email').fill(PARENT_EMAIL);
    await page.getByLabel('Password').fill(PARENT_PASSWORD);
    await page.getByRole('button', { name: /sign in/i }).click();
    await expect(page).toHaveURL(/\/portal/);

    // 2. Navigate to Membership and add a junior.
    await page.goto('/portal/membership');
    await expect(page.getByRole('heading', { name: /my membership/i })).toBeVisible();

    await page.getByRole('button', { name: /add a junior/i }).click();

    // Fill the inline form.
    const uniqueSuffix = String(Date.now()).slice(-6);
    const juniorFirst = `Junior${uniqueSuffix}`;
    const juniorLast = 'Parent';
    const dob = new Date();
    dob.setFullYear(dob.getFullYear() - 12);
    const dobIso = dob.toISOString().slice(0, 10);

    await page.locator('[dusk="family-first-name"]').fill(juniorFirst);
    await page.locator('[dusk="family-last-name"]').fill(juniorLast);
    await page.locator('[dusk="family-dob"]').fill(dobIso);

    await page.locator('[dusk="family-add-submit"]').click();

    // The new junior should appear in the family list.
    const juniorCard = page.locator(`text=${juniorFirst} ${juniorLast}`);
    await expect(juniorCard).toBeVisible();

    // 3. Enter the junior into the open match.
    await page.goto(`/matches/${MATCH_SLUG}`);
    await expect(page.getByRole('heading', { name: /enter this match/i })).toBeVisible();

    const householdPicker = page.locator('[dusk="household-picker"]');
    await expect(householdPicker).toBeVisible();

    const enterJuniorButton = householdPicker.getByRole('button', { name: new RegExp(`enter ${juniorFirst}`, 'i') });
    await enterJuniorButton.click();

    // After entering, the button should be replaced with an "Entered" badge.
    await expect(householdPicker.getByText(/entered/i).first()).toBeVisible();

    // 4. My Registrations should now list the junior's entry.
    await page.goto('/portal/registrations');
    await expect(page.getByRole('heading', { name: /my registrations/i })).toBeVisible();
    await expect(page.getByText(`${juniorFirst} ${juniorLast}`)).toBeVisible();
    await expect(page.getByText(/family smoke match/i).first()).toBeVisible();
});
