// E2E test (Playwright) for admin login and dashboard access.
// Run with: npx playwright test tests/e2e/admin-login.spec.js

const { test, expect } = require("@playwright/test");

const BASE_URL = process.env.E2E_BASE_URL || "http://localhost/cooking_website/public";
const ADMIN_USER = process.env.E2E_ADMIN_USER || "admin";
const ADMIN_PASS = process.env.E2E_ADMIN_PASS || "Admin@123";

test("admin can log in and access dashboard", async ({ page }) => {
  await page.goto(`${BASE_URL}/login`);

  await page.fill('input[name="username"]', ADMIN_USER);
  await page.fill('input[name="password"]', ADMIN_PASS);
  await page.click('button[type="submit"]');

  await page.goto(`${BASE_URL}/admin`);
  await expect(page).toHaveURL(/\/admin$/);
  await expect(page.getByRole("heading", { name: /quan tri|bảng điều khiển/i })).toBeVisible();
});
