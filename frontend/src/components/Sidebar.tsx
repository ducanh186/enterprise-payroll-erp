import { useState } from "react";
import { NavLink } from "react-router-dom";
import { LayoutDashboard, CircleDollarSign, Clock, Shield, Users, ChevronDown, ChevronRight, PanelLeftClose, PanelLeftOpen } from "lucide-react";

interface SubItem {
  to: string;
  label: string;
}

interface SubCategory {
  heading: string;
  items: SubItem[];
}

interface NavItem {
  to?: string;
  label: string;
  icon: typeof LayoutDashboard;
  subCategories?: SubCategory[];
  children?: SubItem[];
}

const NAV_ITEMS: NavItem[] = [
  { to: "/", label: "Dashboard", icon: LayoutDashboard },
  {
    label: "Nhân sự & HĐLĐ",
    icon: Users,
    subCategories: [
      {
        heading: "Danh mục",
        items: [
          { to: "/employees", label: "Hồ sơ cán bộ nhân viên" },
          { to: "/reference/contract-types", label: "Danh mục loại hợp đồng" },
          { to: "/reference/salary-levels", label: "Danh mục thang bậc lương" },
        ],
      },
      {
        heading: "Biến động",
        items: [
          { to: "/contracts", label: "Hợp đồng lao động" },
          { to: "/reference/allowances", label: "Phụ cấp" },
        ],
      },
      {
        heading: "Báo cáo",
        items: [
          { to: "/reports?category=employee", label: "DS người lao động theo loại HĐ" },
        ],
      },
    ],
  },
  {
    label: "Chấm công",
    icon: Clock,
    subCategories: [
      {
        heading: "Danh mục",
        items: [
          { to: "/reference/late-early-rules", label: "Quy định đi trễ về sớm" },
          { to: "/reference/holidays", label: "Danh mục ngày nghỉ" },
          { to: "/reference/shifts", label: "Danh mục ca làm việc" },
        ],
      },
      {
        heading: "Biến động",
        items: [
          { to: "/attendance/shift-assignments", label: "Phân ca làm việc" },
          { to: "/attendance/logs", label: "Dữ liệu thời gian vào - ra" },
          { to: "/attendance/leave-requests", label: "Đơn xin nghỉ phép" },
          { to: "/attendance/manual", label: "Chấm công bổ sung" },
          { to: "/attendance/summary", label: "Tổng hợp công" },
        ],
      },
      {
        heading: "Báo cáo",
        items: [
          { to: "/reports?category=attendance&code=shift", label: "Bảng phân ca hàng ngày" },
          { to: "/reports?category=attendance&code=late", label: "Bảng tổng hợp đi trễ về sớm" },
          { to: "/reports?category=attendance&code=monthly", label: "Bảng chấm công" },
        ],
      },
    ],
  },
  {
    label: "Tính lương",
    icon: CircleDollarSign,
    subCategories: [
      {
        heading: "Danh mục",
        items: [
          { to: "/payroll/parameters", label: "Bộ công thức và tham số lương" },
        ],
      },
      {
        heading: "Biến động",
        items: [
          { to: "/payroll/bonus-deductions", label: "Khen thưởng và kỷ luật" },
          { to: "/payroll/run", label: "Tính lương" },
          { to: "/payroll/periods", label: "Bảng lương" },
        ],
      },
      {
        heading: "Báo cáo",
        items: [
          { to: "/payroll/payslips", label: "Phiếu lương" },
          { to: "/reports?category=payroll", label: "Bảng tổng hợp thanh toán lương" },
        ],
      },
    ],
  },
  {
    label: "Quản trị",
    icon: Shield,
    children: [
      { to: "/admin/users", label: "Người dùng" },
      { to: "/admin/roles", label: "Phân quyền" },
    ],
  },
];

const activeClass = "bg-white/12 text-white shadow-[0_10px_20px_rgba(15,23,42,0.2)]";
const inactiveClass = "text-slate-300 hover:bg-white/6 hover:text-white";

interface SidebarProps {
  open: boolean;
  onClose: () => void;
  collapsed: boolean;
  onToggleCollapse: () => void;
}

export default function Sidebar({ open, onClose, collapsed, onToggleCollapse }: SidebarProps) {
  const [expandedModules, setExpandedModules] = useState<Record<string, boolean>>({});

  const toggleModule = (label: string) => {
    setExpandedModules((prev) => ({
      ...prev,
      [label]: !prev[label],
    }));
  };

  const linkBase = collapsed
    ? "flex items-center justify-center rounded-2xl p-2.5 text-sm font-medium transition-all duration-200"
    : "flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition-all duration-200";

  return (
    <>
      <div
        className={`fixed inset-0 z-30 bg-slate-950/45 transition-opacity lg:hidden ${
          open ? "opacity-100" : "pointer-events-none opacity-0"
        }`}
        onClick={onClose}
      />

      <aside
        className={`fixed inset-y-0 left-0 z-40 transform border-r border-white/10 bg-slate-950/95 px-4 py-5 text-white shadow-2xl transition-all duration-300 lg:translate-x-0 ${
          open ? "translate-x-0" : "-translate-x-full"
        } ${collapsed ? "lg:w-16 lg:px-2" : "w-72"}`}
      >
        <div className={`mb-6 flex items-center ${collapsed ? "justify-center" : "justify-between px-2"}`}>
          {!collapsed && (
            <>
              <div>
                <p className="font-[family-name:var(--font-display)] text-lg font-bold tracking-tight text-white">
                  Payroll ERP
                </p>
                <p className="text-xs text-slate-400">Chấm công, tính lương, hợp đồng</p>
              </div>
              <div className="rounded-2xl border border-white/10 bg-white/5 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-emerald-300">
                Trực tuyến
              </div>
            </>
          )}
          {collapsed && (
            <p className="font-[family-name:var(--font-display)] text-lg font-bold text-white">P</p>
          )}
        </div>

        <nav className="flex flex-col gap-2 overflow-y-auto" style={{ maxHeight: "calc(100vh - 140px)" }}>
          {NAV_ITEMS.map((item) => {
            const Icon = item.icon;
            const isExpanded = expandedModules[item.label];

            if (item.to) {
              return (
                <NavLink
                  key={item.to}
                  to={item.to}
                  end={item.to === "/"}
                  onClick={onClose}
                  className={({ isActive }) =>
                    `${linkBase} ${isActive ? activeClass : inactiveClass}`
                  }
                  title={collapsed ? item.label : undefined}
                >
                  <Icon className="h-4.5 w-4.5 shrink-0" />
                  {!collapsed && <span>{item.label}</span>}
                </NavLink>
              );
            }

            if (collapsed) {
              return (
                <div
                  key={item.label}
                  className="flex items-center justify-center rounded-2xl p-2.5 text-sm font-medium text-slate-300 transition-all duration-200"
                  title={item.label}
                >
                  <Icon className="h-4.5 w-4.5 shrink-0" />
                </div>
              );
            }

            return (
              <div key={item.label}>
                <button
                  type="button"
                  onClick={() => toggleModule(item.label)}
                  className={`flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition-all duration-200 ${inactiveClass}`}
                >
                  <Icon className="h-4.5 w-4.5 shrink-0" />
                  <span className="flex-1 text-left">{item.label}</span>
                  {isExpanded ? (
                    <ChevronDown className="h-3.5 w-3.5 shrink-0" />
                  ) : (
                    <ChevronRight className="h-3.5 w-3.5 shrink-0" />
                  )}
                </button>

                {isExpanded && item.subCategories && (
                  <div className="mt-1 space-y-3 pl-4">
                    {item.subCategories.map((subCategory) => (
                      <div key={subCategory.heading}>
                        <div className="mb-1.5 px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                          {subCategory.heading}
                        </div>
                        <div className="space-y-1">
                          {subCategory.items.map((subItem) => (
                            <NavLink
                              key={subItem.to}
                              to={subItem.to}
                              onClick={onClose}
                              className={({ isActive }) =>
                                `flex items-center gap-3 rounded-2xl px-3 py-2.5 text-xs font-medium transition-all duration-200 ${isActive ? activeClass : inactiveClass}`
                              }
                            >
                              <span className="h-1.5 w-1.5 rounded-full bg-current opacity-70" />
                              <span>{subItem.label}</span>
                            </NavLink>
                          ))}
                        </div>
                      </div>
                    ))}
                  </div>
                )}

                {isExpanded && item.children && (
                  <div className="mt-1 space-y-1.5 pl-4">
                    {item.children.map((child) => (
                      <NavLink
                        key={child.to}
                        to={child.to}
                        onClick={onClose}
                        className={({ isActive }) =>
                          `flex items-center gap-3 rounded-2xl px-3 py-2.5 text-xs font-medium transition-all duration-200 ${isActive ? activeClass : inactiveClass}`
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

        <button
          type="button"
          onClick={onToggleCollapse}
          className="absolute bottom-5 left-1/2 hidden -translate-x-1/2 items-center justify-center rounded-2xl border border-white/10 bg-white/5 p-2 text-slate-400 transition hover:bg-white/10 hover:text-white lg:flex"
          aria-label={collapsed ? "Mở rộng sidebar" : "Thu gọn sidebar"}
          title={collapsed ? "Mở rộng sidebar" : "Thu gọn sidebar"}
        >
          {collapsed ? <PanelLeftOpen className="h-4 w-4" /> : <PanelLeftClose className="h-4 w-4" />}
        </button>
      </aside>
    </>
  );
}
