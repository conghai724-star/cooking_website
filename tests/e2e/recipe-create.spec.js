// E2E test (Playwright) for recipe create save flow.
// Run with: npx playwright test tests/e2e/recipe-create.spec.js

const { test, expect } = require("@playwright/test");

const BASE_URL = process.env.E2E_BASE_URL || "http://localhost/cooking_website/public";
const PASSWORD = process.env.E2E_USER_PASS || "Passw0rd!";

async function register(page, username, email, password) {
  await page.goto(`${BASE_URL}/register`);
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await page.fill('input[name="confirm_password"]', password);
  await Promise.all([
    page.waitForURL(/\/login(\?|$)/),
    page.click('button[type="submit"]'),
  ]);
}

async function login(page, email, password) {
  await page.goto(`${BASE_URL}/login`);
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await Promise.all([
    page.waitForURL(/\/$/),
    page.click('button[type="submit"]'),
  ]);
}

test("save recipe stays in Hoan thien and not in Cho dang", async ({ page }) => {
  const suffix = Date.now();
  const user = {
    username: `recipeUser_${suffix}`,
    email: `recipeUser_${suffix}@local.test`,
  };

  await register(page, user.username, user.email, PASSWORD);
  await login(page, user.email, PASSWORD);

  const title = `Cong thuc luu ${suffix}`;
  const description = "Mo ta ngan cho cong thuc";

  await page.goto(`${BASE_URL}/recipes/create`);
  await page.fill('input[name="title"]', title);
  await page.fill('textarea[name="description"]', description);

  await Promise.all([
    page.waitForURL(/\/recipes\/my\?group=completed/),
    page.click('button[name="action"][value="save"]'),
  ]);

  await expect(page.getByRole("heading", { name: /Công th?c c?a tôi/i })).toBeVisible();
  await expect(page.getByText(title, { exact: false })).toBeVisible();

  await page.goto(`${BASE_URL}/recipes/my?group=pending`);
  await expect(page.getByText(title, { exact: false })).toHaveCount(0);
});
