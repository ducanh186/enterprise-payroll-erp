import { useEffect, useState } from "react";
import { Outlet, useLocation } from "react-router-dom";
import Sidebar from "../components/Sidebar";
import Topbar from "../components/Topbar";

const ROUTE_TITLES: Record<string, string> = {
  "/": "Dashboard vận hành",
  "/attendance": "Tổng quan chấm công",
  "/attendance/logs": "Dữ liệu thời gian vào - ra",
  "/attendance/summary": "Tổng hợp công",
  "/attendance/shift-assignments": "Phân ca làm việc",
  "/attendance/leave-requests": "Đơn xin nghỉ phép",
  "/attendance/manual": "Chấm công bổ sung",
  "/payroll": "Tổng quan tính lương",
  "/payroll/run": "Tính lương",
  "/payroll/payslips": "Phiếu lương",
  "/payroll/parameters": "Bộ công thức và tham số lương",
  "/payroll/bonus-deductions": "Khen thưởng và kỷ luật",
  "/payroll/periods": "Bảng lương",
  "/contracts": "Hợp đồng lao động",
  "/employees": "Hồ sơ cán bộ nhân viên",
  "/reference/contract-types": "Danh mục loại hợp đồng",
  "/reference/salary-levels": "Danh mục thang bậc lương",
  "/reference/allowances": "Phụ cấp",
  "/reference/late-early-rules": "Quy định đi trễ về sớm",
  "/reference/holidays": "Danh mục ngày nghỉ trong năm",
  "/reference/shifts": "Danh mục ca làm việc",
  "/reports": "Trung tâm báo cáo",
  "/admin": "Quản trị hệ thống",
  "/admin/users": "Quản lý người dùng",
  "/admin/roles": "Phân quyền",
};

const ROUTE_SUBTITLES: Record<string, string> = {
  "/": "Tổng quan",
  "/attendance": "Chấm công",
  "/attendance/logs": "Chấm công • Biến động",
  "/attendance/summary": "Chấm công • Biến động",
  "/attendance/shift-assignments": "Chấm công • Biến động",
  "/attendance/leave-requests": "Chấm công • Biến động",
  "/attendance/manual": "Chấm công • Biến động",
  "/payroll": "Tính lương",
  "/payroll/run": "Tính lương • Biến động",
  "/payroll/payslips": "Tính lương • Báo cáo",
  "/payroll/parameters": "Tính lương • Danh mục",
  "/payroll/bonus-deductions": "Tính lương • Biến động",
  "/payroll/periods": "Tính lương • Biến động",
  "/contracts": "Nhân sự & HĐLĐ • Biến động",
  "/employees": "Nhân sự & HĐLĐ • Danh mục",
  "/reference/contract-types": "Nhân sự & HĐLĐ • Danh mục",
  "/reference/salary-levels": "Nhân sự & HĐLĐ • Danh mục",
  "/reference/allowances": "Nhân sự & HĐLĐ • Biến động",
  "/reference/late-early-rules": "Chấm công • Danh mục",
  "/reference/holidays": "Chấm công • Danh mục",
  "/reference/shifts": "Chấm công • Danh mục",
  "/reports": "Báo cáo",
  "/admin": "Quản trị",
  "/admin/users": "Quản trị",
  "/admin/roles": "Quản trị",
};

function resolveTitle(pathname: string): string {
  return ROUTE_TITLES[pathname] ?? "Payroll ERP";
}

function resolveSubtitle(pathname: string): string {
  return ROUTE_SUBTITLES[pathname] ?? "Payroll ERP";
}

export default function AppLayout() {
  const location = useLocation();
  const [mobileNavOpen, setMobileNavOpen] = useState(false);
  const [collapsed, setCollapsed] = useState(() => {
    try { return localStorage.getItem("sidebar-collapsed") === "true"; } catch { return false; }
  });

  useEffect(() => {
    setMobileNavOpen(false);
  }, [location.pathname]);

  useEffect(() => {
    try { localStorage.setItem("sidebar-collapsed", String(collapsed)); } catch { /* noop */ }
  }, [collapsed]);

  const title = resolveTitle(location.pathname);
  const subtitle = resolveSubtitle(location.pathname);

  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(56,189,248,0.14),_transparent_30%),radial-gradient(circle_at_top_right,_rgba(99,102,241,0.12),_transparent_24%),linear-gradient(180deg,_#f8fbff_0%,_#eef4ff_100%)] text-slate-900">
      <Sidebar
        open={mobileNavOpen}
        onClose={() => setMobileNavOpen(false)}
        collapsed={collapsed}
        onToggleCollapse={() => setCollapsed((c) => !c)}
      />

      <div className={`transition-[padding-left] duration-300 ${collapsed ? "lg:pl-16" : "lg:pl-72"}`}>
        <Topbar
          title={title}
          subtitle={subtitle}
          onMenuClick={() => setMobileNavOpen(true)}
        />

        <main className="px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
          <Outlet />
        </main>
      </div>
    </div>
  );
}

