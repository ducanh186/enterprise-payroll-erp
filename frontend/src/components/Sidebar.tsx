import { NavLink } from "react-router-dom";
import { LayoutDashboard, CircleDollarSign, FileText, Shield, BriefcaseBusiness, BellRing } from "lucide-react";

interface NavItem {
  to: string;
  label: string;
  icon: typeof LayoutDashboard;
  children?: { to: string; label: string }[];
}

const NAV_ITEMS: NavItem[] = [
  { to: "/", label: "Dashboard", icon: LayoutDashboard },
  {
    to: "/attendance",
    label: "Chấm công",
    icon: BellRing,
    children: [
      { to: "/attendance/logs", label: "Nhật ký check-in" },
      { to: "/attendance/summary", label: "Tổng kết tháng" },
    ],
  },
  {
    to: "/payroll",
    label: "Tính lương",
    icon: CircleDollarSign,
    children: [
      { to: "/payroll/run", label: "Chạy bảng lương" },
      { to: "/payroll/payslips", label: "Phiếu lương" },
    ],
  },
  { to: "/contracts", label: "Hợp đồng", icon: BriefcaseBusiness },
  { to: "/reports", label: "Báo cáo", icon: FileText },
  {
    to: "/admin",
    label: "Quản trị",
    icon: Shield,
    children: [
      { to: "/admin/users", label: "Người dùng" },
      { to: "/admin/roles", label: "Phân quyền" },
    ],
  },
];

const linkBase =
  "flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition-all duration-200";
const activeClass = "bg-white/12 text-white shadow-[0_10px_20px_rgba(15,23,42,0.2)]";
const inactiveClass = "text-slate-300 hover:bg-white/6 hover:text-white";

interface SidebarProps {
  open: boolean;
  onClose: () => void;
}

export default function Sidebar({ open, onClose }: SidebarProps) {
  return (
    <>
      <div
        className={`fixed inset-0 z-30 bg-slate-950/45 transition-opacity lg:hidden ${
          open ? "opacity-100" : "pointer-events-none opacity-0"
        }`}
        onClick={onClose}
      />

      <aside
        className={`fixed inset-y-0 left-0 z-40 w-72 transform border-r border-white/10 bg-slate-950/95 px-4 py-5 text-white shadow-2xl transition-transform duration-300 lg:translate-x-0 ${
          open ? "translate-x-0" : "-translate-x-full"
        }`}
      >
        <div className="mb-6 flex items-center justify-between px-2">
          <div>
            <p className="font-[family-name:var(--font-display)] text-lg font-bold tracking-tight text-white">
              Payroll ERP
            </p>
            <p className="text-xs text-slate-400">Attendance, payroll, contract</p>
          </div>
          <div className="rounded-2xl border border-white/10 bg-white/5 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-emerald-300">
            Live
          </div>
        </div>

        <nav className="flex flex-col gap-2">
          {NAV_ITEMS.map((item) => {
            const Icon = item.icon;

            return (
              <div key={item.to}>
                <NavLink
                  to={item.to}
                  end={item.to === "/"}
                  onClick={onClose}
                  className={({ isActive }) =>
                    `${linkBase} ${isActive ? activeClass : inactiveClass}`
                  }
                >
                  <Icon className="h-4.5 w-4.5" />
                  <span>{item.label}</span>
                </NavLink>

                {item.children && (
                  <div className="mt-1 space-y-1.5 pl-4">
                    {item.children.map((child) => (
                      <NavLink
                        key={child.to}
                        to={child.to}
                        onClick={onClose}
                        className={({ isActive }) =>
                          `${linkBase} text-xs ${isActive ? activeClass : inactiveClass}`
                        }
                      >
                        <span className="h-1.5 w-1.5 rounded-full bg-current opacity-70" />
                        <span>{child.label}</span>
                      </NavLink>
                    ))}
                  </div>
                )}
              </div>
            );
          })}
        </nav>

      </aside>
    </>
  );
}
