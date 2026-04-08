// E2E test (Playwright) for follow/unfollow flow.
// Run with: npx playwright test tests/e2e/followers.spec.js

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

async function getProfileUserId(page) {
  await page.goto(`${BASE_URL}/profile`);
  const followersLink = page.locator('a[href*="/followers"]').first();
  const href = await followersLink.getAttribute("href");
  const match = href && href.match(/\/users\/(\d+)\/followers/);
  if (!match) {
    throw new Error("Could not find profile user id from followers link");
  }
  return match[1];
}

test("user can follow another user and appear in followers list", async ({ page }) => {
  const suffix = Date.now();
  const userA = {
    username: `userA_${suffix}`,
    email: `userA_${suffix}@local.test`,
  };
  const userB = {
    username: `userB_${suffix}`,
    email: `userB_${suffix}@local.test`,
  };

  await register(page, userA.username, userA.email, PASSWORD);
  await login(page, userA.email, PASSWORD);

  await page.context().clearCookies();

  await register(page, userB.username, userB.email, PASSWORD);
  await login(page, userB.email, PASSWORD);
  const userBId = await getProfileUserId(page);

  await page.context().clearCookies();

  await login(page, userA.email, PASSWORD);
  await page.goto(`${BASE_URL}/users/${userBId}`);
  await page.getByRole("button", { name: "Theo dõi" }).click();
  await expect(page.getByRole("button", { name: "Hủy theo dõi" })).toBeVisible();

  await page.goto(`${BASE_URL}/users/${userBId}/followers`);
  await expect(page.getByText(userA.username, { exact: false })).toBeVisible();
});
