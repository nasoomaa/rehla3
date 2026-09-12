import { test, expect } from '@playwright/test';

test.describe('Customer End-to-End Journey & Localization', () => {
  test('public catalog browsing, locale switching and RTL direction', async ({ page }) => {
    // 1. Visit homepage in English
    await page.goto('/');
    await expect(page).toHaveTitle(/Rehla/);
    await expect(page.locator('html')).toHaveAttribute('dir', 'ltr');

    // 2. Switch to Arabic and verify RTL
    await page.goto('/locale/ar');
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
    await expect(page.locator('html')).toHaveAttribute('lang', 'ar');

    // 3. Switch back to English and check services catalog
    await page.goto('/locale/en');
    await page.goto('/services');
    await expect(page.locator('body')).toContainText('Services');

    // 4. Check service details page
    await page.goto('/services/uae-tourist-visa-30-days');
    await expect(page.locator('body')).toContainText('UAE Tourist Visa (30 Days)');
    await expect(page.locator('a[href*="wa.me"]')).toBeVisible();
  });

  test('customer registration and authenticated account shell', async ({ page }) => {
    const timestamp = Date.now();
    const email = `e2e_customer_${timestamp}@rehla.test`;

    // 1. Visit register page
    await page.goto('/register');
    await page.fill('input[name="name"]', 'Fatima E2E');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', 'Password123!');
    await page.fill('input[name="password_confirmation"]', 'Password123!');
    await page.click('button[type="submit"]');

    // 2. Should redirect to account profile
    await expect(page).toHaveURL(/\/account\/profile/);
    await expect(page.locator('body')).toContainText('Fatima E2E');

    // 3. Check wallet page has initial zero balance
    await page.goto('/account/wallet');
    await expect(page.locator('body')).toContainText('0.00');
    await expect(page.locator('body')).toContainText('SDG');

    // 4. Check top-up accounts view
    await page.goto('/account/top-ups');
    await expect(page.locator('body')).toContainText('Top-Ups');
  });
});
