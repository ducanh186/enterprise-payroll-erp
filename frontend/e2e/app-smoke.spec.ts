import { expect, test, type Page } from "@playwright/test";

const ADMIN_PASSWORD = "password";
const SIDEBAR_ROUTES = [
  { href: "/", heading: "Dashboard vận hành" },
  { href: "/attendance", heading: "Nhật ký chấm công" },
  { href: "/attendance/logs", heading: "Nhật ký check-in" },
  { href: "/attendance/summary", heading: "Tổng kết điểm danh tháng" },
  { href: "/payroll", heading: "Payroll Cycle Dashboard" },
  { href: "/payroll/run", heading: "Payroll Run Wizard" },
  { href: "/payroll/payslips", heading: "Lịch sử phiếu lương" },
  { href: "/contracts", heading: "Danh mục hợp đồng" },
  { href: "/reports", heading: "Reports Center" },
  { href: "/admin", heading: "User Management Directory" },
  { href: "/admin/users", heading: "User Management Directory" },
  { href: "/admin/roles", heading: "Role & Permissions Matrix" },
];
const SEED_USERS = ["admin01", "hr01", "payroll01", "manager01", "emp001"];

function escapeRegExp(value: string) {
  return value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

async function loginThroughUi(page: Page, username: string) {
  await page.goto("/login");
  await page.fill("#identity", username);
  await page.fill("#password", ADMIN_PASSWORD);
  await Promise.all([
    page.waitForURL("**/"),
    page.getByRole("button", { name: "Đăng nhập hệ thống" }).click(),
  ]);
  await page.waitForLoadState("networkidle");
  await expect(page.getByRole("button", { name: "Đăng xuất" })).toBeVisible();
}

async function openSidebarRoute(page: Page, href: string, heading: string) {
  await page.locator(`aside a[href="${href}"]`).first().click();
  await expect(page).toHaveURL(href === "/" ? /\/$/ : new RegExp(`${escapeRegExp(href)}$`));
  await page.waitForLoadState("networkidle");
  await expect(page.locator("main").getByText(heading, { exact: false }).first()).toBeVisible();
}

test.beforeEach(async ({ page }) => {
  await page.addInitScript(() => {
    window.localStorage.setItem("sidebar-collapsed", "false");
  });
});

test("admin can click through the main app without FE/BE runtime errors", async ({ page }) => {
  const apiErrors: Array<{ status: number; url: string }> = [];
  const consoleErrors: string[] = [];
  const pageErrors: string[] = [];

  page.on("response", (response) => {
    if (response.url().includes("/api/") && response.status() >= 400) {
      apiErrors.push({ status: response.status(), url: response.url() });
    }
  });
  page.on("console", (message) => {
    if (message.type() === "error") {
      consoleErrors.push(message.text());
    }
  });
  page.on("pageerror", (error) => {
    pageErrors.push(error.message);
  });

  await loginThroughUi(page, "admin01");

  for (const route of SIDEBAR_ROUTES) {
    await test.step(`Open ${route.href}`, async () => {
      await openSidebarRoute(page, route.href, route.heading);
    });
  }

  await test.step("Open first contract detail", async () => {
    await openSidebarRoute(page, "/contracts", "Danh mục hợp đồng");
    await page.getByTitle("Xem chi tiết").first().click();
    await expect(page.getByText("Chi tiết hợp đồng")).toBeVisible();
    await expect(page.getByText("Không có chi tiết")).toHaveCount(0);
    await page.goto("/contracts/1");
    await page.waitForLoadState("networkidle");
    await expect(page).toHaveURL(/\/contracts\/1$/);
    await expect(page.getByRole("heading", { name: /Chi tiết Hợp đồng/i })).toBeVisible();
    await expect(page.getByText("Không tìm thấy hợp đồng")).toHaveCount(0);
  });

  await test.step("Open first payslip detail", async () => {
    await openSidebarRoute(page, "/payroll/payslips", "Lịch sử phiếu lương");
    await expect(page.getByText(/Không có phiếu lương|Bảng kê hàng tháng/i)).toBeVisible();
    await page.goto("/payroll/payslips/1");
    await page.waitForLoadState("networkidle");
    await expect(page).toHaveURL(/\/payroll\/payslips\/1$/);
    await expect(page.getByRole("heading", { name: /Phiếu lương/i })).toBeVisible();
    await expect(page.getByText("Không tìm thấy phiếu lương")).toHaveCount(0);
  });

  await test.step("Role matrix is populated", async () => {
    await openSidebarRoute(page, "/admin/roles", "Role & Permissions Matrix");
    await expect(page.getByText("No permission data")).toHaveCount(0);
    await expect(page.locator("main").getByText("auth", { exact: false }).first()).toBeVisible();
  });

  expect(pageErrors).toEqual([]);
  expect(
    apiErrors,
    apiErrors.map((error) => `${error.status} ${error.url}`).join("\n"),
  ).toEqual([]);
  expect(
    consoleErrors.filter((message) => !message.includes("favicon")),
    consoleErrors.join("\n"),
  ).toEqual([]);
});

for (const username of SEED_USERS) {
  test(`seed user ${username} can log in through the UI`, async ({ page }) => {
    await loginThroughUi(page, username);
    await expect(page.locator("main").getByText(/Dashboard vận hành|Tổng quan/i).first()).toBeVisible();
  });
}
