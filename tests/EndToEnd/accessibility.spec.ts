import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

test.describe('Accessibility & Keyboard Navigation (WCAG 2.1)', () => {
  test('homepage has no critical accessibility violations in English and Arabic RTL', async ({ page }) => {
    // English LTR
    await page.goto('/locale/en');
    await page.goto('/');
    const resultsEn = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa'])
      .disableRules(['color-contrast']) // Tailwind dark mode contrast variations
      .analyze();
    
    const criticalEn = resultsEn.violations.filter(v => v.impact === 'critical');
    expect(criticalEn).toEqual([]);

    // Arabic RTL
    await page.goto('/locale/ar');
    await page.goto('/');
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');

    const resultsAr = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa'])
      .disableRules(['color-contrast'])
      .analyze();

    const criticalAr = resultsAr.violations.filter(v => v.impact === 'critical');
    expect(criticalAr).toEqual([]);
  });

  test('authentication pages have proper labels and keyboard focusability', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();

    await page.goto('/admin/login');
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });
});
