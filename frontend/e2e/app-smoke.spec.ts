import path from "node:path";
import { expect, test, type Page } from "@playwright/test";

const ADMIN_PASSWORD = "password";
const CHECKIN_SAMPLE = path.resolve(
  process.cwd(),
  "../backend/storage/app/imports/Data checkinout.xlsx",
);

const FUJIMART_REPORTS = [
  {
    url: "/reports?category=attendance&code=FUJIMART_ATTENDANCE_REPORT",
    title: "Bảng chấm công",
  },
  {
    url: "/reports?category=payroll&code=FUJIMART_PAYROLL_REPORT",
    title: "Bảng thanh toán lương theo chi nhánh/phòng ban",
  },
  {
    url: "/reports?category=payroll&code=FUJIMART_PAYROLL_SLIP",
    title: "Phiếu lương cá nhân",
    employeeCode: "NV001",
  },
];

function escapeRegExp(value: string) {
  return value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

function attachRuntimeGuards(page: Page) {
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

  return () => {
    expect(pageErrors).toEqual([]);
    expect(
      apiErrors,
      apiErrors.map((error) => `${error.status} ${error.url}`).join("\n"),
    ).toEqual([]);
    expect(
      consoleErrors.filter((message) => !message.includes("favicon")),
      consoleErrors.join("\n"),
    ).toEqual([]);
  };
}

async function loginThroughUi(page: Page, username = "admin01") {
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

async function openRoute(page: Page, href: string, heading: string | RegExp) {
  await page.goto(href);
  await page.waitForLoadState("networkidle");
  await expect(page).toHaveURL(new RegExp(`${escapeRegExp(href)}$`));
  await expect(page.locator("main").getByText(heading).first()).toBeVisible();
}

test.beforeEach(async ({ page }) => {
  await page.addInitScript(() => {
    window.localStorage.setItem("sidebar-collapsed", "false");
  });
});

test("admin can use Fujimart HRM customer flows without FE/BE runtime errors", async ({ page }) => {
  const assertNoRuntimeErrors = attachRuntimeGuards(page);

  await loginThroughUi(page);
  await expect(page).toHaveTitle("Fujimart HRM");
  await expect(page.getByAltText("Fujimart").first()).toBeVisible();
  await expect(page.getByText(/Fujimart\s*HRM/i).first()).toBeVisible();
  await expect(page.getByText("Quản lý", { exact: false })).toHaveCount(0);

  await test.step("Sidebar exposes the Fujimart BFD structure", async () => {
    await page.getByRole("button", { name: "Nhân sự & HĐLĐ" }).click();
    await expect(page.getByRole("link", { name: "Hồ sơ cán bộ nhân viên" })).toBeVisible();

    await page.getByRole("button", { name: "Chấm công" }).click();
    await expect(page.getByRole("link", { name: "Dữ liệu thời gian vào - ra" })).toBeVisible();
    await expect(page.getByRole("link", { name: "Bảng chấm công" })).toBeVisible();

    await page.getByRole("button", { name: "Tính lương" }).click();
    await expect(page.getByRole("link", { name: "Bộ công thức và tham số lương" })).toBeVisible();
    await expect(page.getByRole("link", { name: "Phiếu lương cá nhân" })).toBeVisible();
    await expect(page.getByRole("link", { name: "Bảng thanh toán lương theo chi nhánh/phòng ban" })).toBeVisible();
  });

  await test.step("Employee detail includes dependents tab", async () => {
    await openRoute(page, "/employees", "Hồ sơ cán bộ nhân viên");
    await page.getByRole("button", { name: /Tab chi tiết/i }).first().click();
    await expect(page.getByRole("button", { name: "Hồ sơ" })).toBeVisible();
    await page.getByRole("button", { name: "Người phụ thuộc" }).click();
    await expect(page.getByText(/người phụ thuộc/i).first()).toBeVisible();
  });

  await test.step("Payroll parameters expose Fujimart source views", async () => {
    await openRoute(page, "/payroll/parameters", "Bộ công thức và tham số lương");
    await expect(page.getByRole("button", { name: "vD20PayrollPara_ValuePara" })).toBeVisible();
    await expect(page.getByText("Nguồn view: vD20PayrollPara_ValuePara")).toBeVisible();
    await page.getByRole("button", { name: "vD20PayrollPara_SalaryType" }).click();
    await expect(page.getByText("Nguồn view: vD20PayrollPara_SalaryType")).toBeVisible();
  });

  await test.step("Import customer check-in/out Excel", async () => {
    await openRoute(page, "/attendance/logs", "Nhật ký check-in");
    await page.setInputFiles('input[type="file"]', CHECKIN_SAMPLE);
    await page.getByRole("button", { name: "Import Excel" }).click();
    await expect(page.getByText(/Import hoàn tất/i)).toBeVisible({ timeout: 30_000 });
    await expect(page.getByText(/Đã nhập [1-9][0-9]* dòng/i)).toBeVisible();
  });

  await test.step("Run attendance procedure", async () => {
    await openRoute(page, "/attendance/summary", "Tổng kết điểm danh tháng");
    await page.locator("select").nth(1).selectOption("1");
    await page.locator("select").nth(2).selectOption("2026");
    await page.getByRole("button", { name: /Tính lại|Chạy/i }).first().click();
    await expect(page.locator("main").getByText(/Đã chạy tính công/i).first()).toBeVisible({
      timeout: 30_000,
    });
  });

  await test.step("Run payroll procedure", async () => {
    await openRoute(page, "/payroll/run", "Trình chạy bảng lương");
    await page.locator("select").first().selectOption("1");
    await page.locator('input[type="number"]').first().fill("2026");
    await page.getByRole("button", { name: "Chạy tính lương" }).click();
    await expect(page.locator("main").getByText(/Nhân viên được liệt kê|Tóm tắt xem trước/i).first()).toBeVisible({
      timeout: 30_000,
    });
  });

  for (const report of FUJIMART_REPORTS) {
    await test.step(`Preview and export ${report.title}`, async () => {
      await openRoute(page, report.url, "Trung tâm báo cáo");
      await expect(page.getByText(report.title).first()).toBeVisible();
      if (report.employeeCode) {
        await page.getByPlaceholder("Dùng cho phiếu lương cá nhân").fill(report.employeeCode);
      }
      await page.getByRole("button", { name: "Xem trước" }).click();
      await expect(page.getByText(/Phản hồi JSON|Tạo lúc/i).first()).toBeVisible({ timeout: 30_000 });

      await page.getByRole("button", { name: "Xuất báo cáo" }).click();
      await expect(page.getByText("Export thành công")).toBeVisible({ timeout: 30_000 });
      const downloadPromise = page.waitForEvent("download", { timeout: 30_000 });
      await page.getByRole("button", { name: "Tải file" }).click();
      const download = await downloadPromise;
      expect(download.suggestedFilename()).toMatch(/\.xlsx$/);
    });
  }

  assertNoRuntimeErrors();
});

for (const username of ["admin01", "hr01", "payroll01", "manager01", "emp001"]) {
  test(`seed user ${username} can log in through the UI`, async ({ page }) => {
    await loginThroughUi(page, username);
    await expect(page.locator("main").getByText(/Dashboard vận hành|Tổng quan/i).first()).toBeVisible();
  });
}

test("stale stored session is cleared when the API rejects the token", async ({ page }) => {
  await page.addInitScript(() => {
    window.localStorage.setItem(
      "erp_auth_session",
      JSON.stringify({
        token: "stale-token",
        user: {
          id: 1,
          name: "Stale User",
          role: "system_admin",
          permissions: ["payroll.run", "payroll.view"],
        },
      }),
    );
  });

  await page.goto("/payroll/run");
  await page.getByRole("button", { name: "Chạy tính lương" }).click();
  await expect(page).toHaveURL(/\/login$/);
  await expect(page.getByRole("button", { name: "Đăng nhập hệ thống" })).toBeVisible();
});
