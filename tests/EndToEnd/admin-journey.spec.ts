import { test, expect } from '@playwright/test';

test.describe('Admin Staff Operations Panel Journey', () => {
  test('enforces admin authentication and MFA verification', async ({ page }) => {
    // 1. Unauthenticated visit to /admin/overview should redirect to /admin/login
    await page.goto('/admin/overview');
    await expect(page).toHaveURL(/\/admin\/login/);
    await expect(page.locator('body')).toContainText('Staff Access Portal');

    // 2. Login with seeded super admin credentials
    await page.fill('input[name="email"]', 'admin@rehla.test');
    await page.fill('input[name="password"]', 'AdminSecret123!');
    await page.click('button[type="submit"]');

    // 3. Confirm MFA
    if (page.url().includes('/admin/mfa')) {
      await page.fill('input[name="totp_code"]', '123456');
      await page.click('button[type="submit"]');
    }

    // 4. Land on operations overview
    await expect(page).toHaveURL(/\/admin\/overview/);
    await expect(page.locator('body')).toContainText('Platform Operations Overview');

    // 5. Navigate through operational resources
    await page.goto('/admin/services');
    await expect(page.locator('body')).toContainText('Service Catalog');

    await page.goto('/admin/top-up-requests');
    await expect(page.locator('body')).toContainText('Top-up Requests');

    await page.goto('/admin/audit-log');
    await expect(page.locator('body')).toContainText('Audit Log');

    // 6. Logout
    await page.click('button:has-text("Log Out")');
    await expect(page).toHaveURL(/\/admin\/login/);
  });
});
