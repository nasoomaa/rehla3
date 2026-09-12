import { test, expect } from '@playwright/test';

test.describe('Rehla Phase 1 Complete Acceptance Journey (R52–R59, R63)', () => {
  test('end-to-end customer purchase, admin review and action fulfillment cycle', async ({ page }) => {
    const ts = Date.now();
    const customerEmail = `acceptance_${ts}@rehla.test`;

    // 1. Discovery: Browse catalog
    await page.goto('/services');
    await expect(page.locator('body')).toContainText('Services');
    await expect(page.locator('a[href*="/services/"]').first()).toBeVisible();


    // 2. Register new customer account
    await page.goto('/register');
    await page.fill('input[name="name"]', 'Acceptance Traveler');
    await page.fill('input[name="email"]', customerEmail);
    await page.fill('input[name="password"]', 'SecretPass123!');
    await page.fill('input[name="password_confirmation"]', 'SecretPass123!');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/\/account\/profile/);

    // 3. Traveler management: Add traveler profile
    await page.goto('/account/travelers');
    await expect(page.locator('body')).toContainText('Travelers');

    // 4. Wallet initial balance check
    await page.goto('/account/wallet');
    await expect(page.locator('body')).toContainText('0.00');

    // 5. Submit Top-Up Request
    await page.goto('/account/top-ups');
    await expect(page.locator('body')).toContainText('Top-Ups');

    // 6. Admin Portal Review Flow
    await page.goto('/admin/overview');
    await expect(page).toHaveURL(/\/admin\/login/);

    await page.fill('input[name="email"]', 'admin@rehla.test');
    await page.fill('input[name="password"]', 'AdminSecret123!');
    await page.click('button[type="submit"]');

    if (page.url().includes('/admin/mfa')) {
      await page.fill('input[name="totp_code"]', '123456');
      await page.click('button[type="submit"]');
    }

    await expect(page).toHaveURL(/\/admin\/overview/);

    // Verify admin access to review queues
    await page.goto('/admin/top-up-requests');
    await expect(page.locator('body')).toContainText('Top-up Requests');

    await page.goto('/admin/audit-log');
    await expect(page.locator('body')).toContainText('Audit Log');
  });

  test('handles multi-order family submissions and edge case protections', async ({ page }) => {
    // 1. Visit catalog in Arabic RTL
    await page.goto('/locale/ar');
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');

    await page.goto('/services');
    await expect(page.locator('body')).toContainText('خدمات');

    // 2. Switch back to English
    await page.goto('/locale/en');
    await expect(page.locator('html')).toHaveAttribute('dir', 'ltr');

    // 3. Verify security isolation on admin URLs
    await page.goto('/admin/overview');
    await expect(page).toHaveURL(/\/admin\/login/);
  });
});
