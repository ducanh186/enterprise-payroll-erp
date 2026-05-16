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

async function fillLabeledInput(page: Page, label: string, value: string) {
  const input = page.getByLabel(label, { exact: true });
  await input.fill(value);
}

async function selectLabeledCombobox(page: Page, label: string, value: string) {
  await page.getByRole("combobox", { name: label }).selectOption(value);
}

async function apiGetThroughPage<T>(page: Page, url: string): Promise<T> {
  return page.evaluate(async (requestUrl) => {
    const rawSession = localStorage.getItem("erp_auth_session") ?? sessionStorage.getItem("erp_auth_session");
    const session = rawSession ? JSON.parse(rawSession) as { token?: string } : null;
    const response = await fetch(`http://localhost:8001/api${requestUrl}`, {
      headers: {
        Accept: "application/json",
        ...(session?.token ? { Authorization: `Bearer ${session.token}` } : {}),
      },
    });

    if (!response.ok) {
      throw new Error(`API ${response.status} ${requestUrl}`);
    }

    return response.json();
  }, url) as Promise<T>;
}

test.beforeEach(async ({ page }) => {
  await page.addInitScript(() => {
    window.localStorage.setItem("sidebar-collapsed", "false");
  });
});

test("admin can use Fujimart HRM customer flows without FE/BE runtime errors", async ({ page }) => {
  test.setTimeout(300_000);
  const assertNoRuntimeErrors = attachRuntimeGuards(page);

  await loginThroughUi(page);
  await expect(page).toHaveTitle("Fujimart HRM");
  await expect(page.getByAltText("Fujimart").first()).toBeVisible();
  await expect(page.getByText(/Fujimart\s*HRM/i).first()).toBeVisible();
  await expect(page.getByText("Quản lý", { exact: false })).toHaveCount(0);

  await test.step("Sidebar can be collapsed and expanded", async () => {
    await expect(page.getByRole("button", { name: "Thu gọn sidebar" })).toBeVisible();
    await page.getByRole("button", { name: "Thu gọn sidebar" }).click();
    await expect(page.getByRole("button", { name: "Mở rộng sidebar" })).toBeVisible();
    await expect(page.getByText(/Fujimart\s*HRM/i).first()).toBeHidden();
    await page.getByRole("button", { name: "Mở rộng sidebar" }).click();
    await expect(page.getByRole("button", { name: "Thu gọn sidebar" })).toBeVisible();
    await expect(page.getByText(/Fujimart\s*HRM/i).first()).toBeVisible();
  });

  await test.step("Sidebar exposes the Fujimart BFD structure", async () => {
    await page.getByRole("button", { name: "Nhân sự & HĐLĐ" }).click();
    await expect(page.getByRole("link", { name: "Hồ sơ cán bộ nhân viên" })).toBeVisible();

    await page.getByRole("button", { name: "Chấm công" }).click();
    await expect(page.getByRole("link", { name: "Dữ liệu thời gian vào - ra" })).toBeVisible();
    await expect(page.getByRole("link", { name: "Bảng chấm công" })).toBeVisible();

    await page.getByRole("button", { name: "Tiền lương" }).click();
    await expect(page.getByRole("link", { name: "Tham số lương" })).toBeVisible();
    await expect(page.getByRole("link", { name: "Gửi email phiếu lương" })).toBeVisible();
    await expect(page.getByRole("link", { name: "Phiếu lương cá nhân" })).toBeVisible();
    await expect(page.getByRole("link", { name: "Bảng thanh toán lương theo chi nhánh/phòng ban" })).toBeVisible();
  });

  await test.step("Employee create/edit/detail/dependent/suspend actions persist", async () => {
    const employeeCode = `E2E${Date.now().toString().slice(-6)}`;
    const updatedName = `E2E Nguyen Van ${employeeCode}`;

    await openRoute(page, "/employees", "Hồ sơ cán bộ nhân viên");

    await page.getByRole("button", { name: "Thêm mới" }).click();
    await fillLabeledInput(page, "Mã nhân viên", employeeCode);
    await fillLabeledInput(page, "Họ và tên", `E2E Draft ${employeeCode}`);
    await selectLabeledCombobox(page, "Giới tính", "female");
    await fillLabeledInput(page, "Ngày sinh", "14/02/1996");
    await fillLabeledInput(page, "Số CCCD", `079096${employeeCode.slice(-6)}`);
    await fillLabeledInput(page, "Địa chỉ email", `${employeeCode.toLowerCase()}@fujimart.test`);
    await fillLabeledInput(page, "Số điện thoại", "0912345678");
    await fillLabeledInput(page, "Ngày vào làm", "01/01/2026");
    await page.getByRole("button", { name: "Lưu" }).click();
    await expect(page.getByRole("dialog", { name: `E2E Draft ${employeeCode}` })).toBeVisible();

    await page.getByRole("dialog", { name: `E2E Draft ${employeeCode}` }).getByRole("button", { name: "Sửa" }).click();
    await expect(page.getByRole("dialog", { name: "Sửa nhân viên" })).toBeVisible();
    await fillLabeledInput(page, "Họ và tên", updatedName);
    await fillLabeledInput(page, "Ngày nghỉ việc", "31/12/2026");
    await page.getByRole("button", { name: "Lưu" }).click();
    await expect(page.getByRole("heading", { name: updatedName })).toBeVisible({ timeout: 30_000 });

    await expect(page.getByRole("button", { name: "Hồ sơ" })).toBeVisible();
    await expect(page.getByText("Giới tính")).toBeVisible();
    await expect(page.getByText("Ngày sinh")).toBeVisible();
    await expect(page.getByText("Số CCCD")).toBeVisible();
    await expect(page.getByText("Ngày nghỉ việc")).toBeVisible();

    await page.getByRole("button", { name: "Người phụ thuộc" }).click();
    await expect(page.getByText(/người phụ thuộc/i).first()).toBeVisible();
    await page.getByRole("button", { name: "Thêm người phụ thuộc" }).click();
    await fillLabeledInput(page, "Họ tên", `Dependent ${employeeCode}`);
    await fillLabeledInput(page, "Quan hệ", "Con");
    await fillLabeledInput(page, "Ngày sinh", "01/06/2020");
    await fillLabeledInput(page, "Số giấy tờ", `DEP${employeeCode.slice(-6)}`);
    await page.getByRole("button", { name: "Lưu" }).click();
    await expect(page.getByText(`Dependent ${employeeCode}`)).toBeVisible({ timeout: 30_000 });

    await page.getByRole("button", { name: "Sửa" }).last().click();
    await fillLabeledInput(page, "Quan hệ", "Con ruột");
    await page.getByRole("button", { name: "Lưu" }).click();
    await expect(page.getByText("Con ruột")).toBeVisible({ timeout: 30_000 });

    const suspendResponse = page.waitForResponse(
      (response) =>
        response.url().includes("/api/employees/") &&
        response.url().includes("/suspend") &&
        response.request().method() === "POST",
      { timeout: 30_000 },
    );
    await page.getByRole("button", { name: "Đình chỉ" }).click();
    const suspendedResponse = await suspendResponse;
    const suspendedPayload = await suspendedResponse.json() as { data?: { status?: string } };
    expect(suspendedPayload.data?.status).toBe("inactive");

    await page.getByRole("button", { name: "Đóng" }).click();

    const persistedPayload = await apiGetThroughPage<{ data?: Array<{ employee_code?: string; status?: string }> }>(
      page,
      `/employees?keyword=${encodeURIComponent(employeeCode)}`,
    );
    const persistedEmployee = persistedPayload.data?.find((employee) => employee.employee_code === employeeCode);
    expect(persistedEmployee?.status).toBe("inactive");
  });

  await test.step("Payroll parameters expose Fujimart source views", async () => {
    await openRoute(page, "/payroll/parameters", "Tham số lương");
    await expect(page.getByRole("button", { name: "Tham số giá trị" })).toBeVisible();
    await expect(page.getByText("Nguồn view: vD20PayrollPara_ValuePara")).toBeVisible();
    await page.getByRole("button", { name: "Loại thu nhập, lương thưởng" }).click();
    await expect(page.getByText("Nguồn view: vD20PayrollPara_SalaryType")).toBeVisible();
  });

  await test.step("Salary scale page exposes D20SalaryScale to grade detail structure", async () => {
    await openRoute(page, "/reference/salary-levels", "Danh mục thang lương");
    await page.getByRole("button", { name: "Làm mới" }).click();
    await page.getByRole("button", { name: /Tab chi tiết/i }).first().click();
    await expect(page.getByRole("dialog").getByText("Mã bậc lương")).toBeVisible();
    await page.getByRole("button", { name: "Xem khoản thu nhập" }).first().click();
    await expect(page.getByRole("heading", { name: "Chi tiết khoản thu nhập" })).toBeVisible();
  });

  await test.step("Payroll slip email action runs customer procedure contract", async () => {
    await openRoute(page, "/payroll/payslips/email", "Gửi email phiếu lương");
    await expect(page.getByLabel("Ngày tính lương")).toHaveValue("05/05/2026");
    await fillLabeledInput(page, "Mã nhân viên", "NV001");
    await fillLabeledInput(page, "Mã phòng ban", "");
    await fillLabeledInput(page, "Mã chi nhánh", "A01,A02");
    await page.getByRole("button", { name: "Gửi email phiếu lương" }).click();
    await expect(page.getByText("dbo.usp_PayrollSlip")).toBeVisible({ timeout: 30_000 });
    await expect(page.getByText("@_SendEmail")).toBeVisible();
  });

  await test.step("Import customer check-in/out Excel", async () => {
    await openRoute(page, "/attendance/logs", "Nhật ký check-in");
    await page.setInputFiles('input[type="file"]', CHECKIN_SAMPLE);
    await page.getByRole("button", { name: "Import Excel" }).click();
    await expect(page.getByText(/Import hoàn tất/i)).toBeVisible({ timeout: 90_000 });
    await expect(page.getByText(/Đã nhập [1-9][0-9]* dòng/i)).toBeVisible();
  });

  await test.step("Run attendance procedure", async () => {
    await openRoute(page, "/attendance/summary", "Tổng kết điểm danh tháng");
    await page.locator("select").nth(1).selectOption("1");
    await page.locator("select").nth(2).selectOption("2026");
    const recalculateResponse = page.waitForResponse(
      (response) =>
        response.url().includes("/api/attendance/recalculate") &&
        response.request().method() === "POST",
      { timeout: 30_000 },
    );
    await page.getByRole("button", { name: "Tính lại" }).click();
    expect((await recalculateResponse).ok()).toBeTruthy();
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
      await page.getByRole("button", { name: "Làm mới" }).first().click();
      await expect(page.getByText("Bảng chấm công").first()).toBeVisible();
      await expect(page.getByText("Bảng thanh toán lương theo chi nhánh/phòng ban").first()).toBeVisible();
      await expect(page.getByText("Phiếu lương cá nhân").first()).toBeVisible();
      await expect(page.getByText("Từ ngày")).toBeVisible();
      await expect(page.getByLabel("Từ ngày", { exact: true })).toHaveValue("01/01/2026");
      await expect(page.getByLabel("Đến ngày", { exact: true })).toHaveValue("31/01/2026");
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
  await expect(page).toHaveURL(/\/login$/, { timeout: 30_000 });
  await expect(page.getByRole("button", { name: "Đăng nhập hệ thống" })).toBeVisible();
});
