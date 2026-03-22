import { useEffect, useState } from "react";
import { Outlet, useLocation } from "react-router-dom";
import Sidebar from "../components/Sidebar";
import Topbar from "../components/Topbar";

const ROUTE_TITLES: Record<string, string> = {
  "/": "Dashboard vận hành",
  "/attendance": "Tổng quan chấm công",
  "/attendance/logs": "Nhật ký check-in",
  "/attendance/summary": "Tổng kết tháng",
  "/payroll": "Tổng quan tính lương",
  "/payroll/run": "Chạy bảng lương",
  "/payroll/payslips": "Phiếu lương",
  "/contracts": "Hợp đồng lao động",
  "/reports": "Báo cáo & xuất file",
  "/admin": "Quản trị hệ thống",
  "/admin/users": "Quản lý người dùng",
};

function resolveTitle(pathname: string): string {
  return ROUTE_TITLES[pathname] ?? "Payroll ERP";
}

export default function AppLayout() {
  const location = useLocation();
  const [mobileNavOpen, setMobileNavOpen] = useState(false);

  useEffect(() => {
    setMobileNavOpen(false);
  }, [location.pathname]);

  const title = resolveTitle(location.pathname);

  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(56,189,248,0.14),_transparent_30%),radial-gradient(circle_at_top_right,_rgba(99,102,241,0.12),_transparent_24%),linear-gradient(180deg,_#f8fbff_0%,_#eef4ff_100%)] text-slate-900">
      <Sidebar open={mobileNavOpen} onClose={() => setMobileNavOpen(false)} />

      <div className="lg:pl-72">
        <Topbar
          title={title}
          subtitle={location.pathname}
          onMenuClick={() => setMobileNavOpen(true)}
        />

        <main className="px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
          <Outlet />
        </main>
      </div>
    </div>
  );
}

