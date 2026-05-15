import {
  CircleDollarSign,
  Clock,
  LayoutDashboard,
  Shield,
  Users,
  type LucideIcon,
} from "lucide-react";
import { matchPath } from "react-router-dom";

export type PermissionRule =
  | string
  | {
      anyOf?: string[];
      allOf?: string[];
    };

export interface NavigationLeaf {
  to: string;
  label: string;
  required?: PermissionRule;
}

export interface NavigationSection {
  heading: string;
  items: NavigationLeaf[];
}

export interface NavigationItem {
  to?: string;
  label: string;
  icon: LucideIcon;
  required?: PermissionRule;
  subCategories?: NavigationSection[];
  children?: NavigationLeaf[];
}

export interface RouteMeta {
  pattern: string;
  title: string;
  subtitle: string;
  required?: PermissionRule;
}

const anyOf = (...permissions: string[]): PermissionRule => ({ anyOf: permissions });
const allOf = (...permissions: string[]): PermissionRule => ({ allOf: permissions });

const ATTENDANCE_ACCESS = anyOf(
  "attendance.view",
  "attendance.manage_period",
  "attendance.import_logs",
  "attendance.calculate",
  "attendance.manage_request",
  "attendance.confirm",
);

const PAYROLL_ACCESS = anyOf(
  "payroll.view",
  "payroll.manage_param",
  "payroll.adjust",
  "payroll.run",
  "payroll.finalize",
  "payroll.lock",
);

const ADMIN_ACCESS = anyOf("admin.users", "admin.roles");

const NAV_ITEMS: NavigationItem[] = [
  {
    to: "/",
    label: "Dashboard",
    icon: LayoutDashboard,
    required: "dashboard.view",
  },
  {
    label: "Nhân sự & HĐLĐ",
    icon: Users,
    subCategories: [
      {
        heading: "Danh mục",
        items: [
          { to: "/employees", label: "Hồ sơ cán bộ nhân viên", required: "employee.view" },
          {
            to: "/reference/contract-types",
            label: "Danh mục loại hợp đồng",
            required: "reference.manage",
          },
          {
            to: "/reference/salary-levels",
            label: "Danh mục thang bậc lương",
            required: "reference.manage",
          },
        ],
      },
      {
        heading: "Biến động",
        items: [
          { to: "/contracts", label: "Hợp đồng lao động", required: "contract.view" },
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
          {
            to: "/reference/late-early-rules",
            label: "Quy định đi trễ về sớm",
            required: "reference.manage",
          },
          {
            to: "/reference/holidays",
            label: "Danh mục ngày nghỉ",
            required: "reference.manage",
          },
          {
            to: "/reference/shifts",
            label: "Danh mục ca làm việc",
            required: "reference.manage",
          },
        ],
      },
      {
        heading: "Biến động",
        items: [
          {
            to: "/attendance/shift-assignments",
            label: "Phân ca làm việc",
            required: "attendance.manage_period",
          },
          {
            to: "/attendance/logs",
            label: "Dữ liệu thời gian vào - ra",
            required: "attendance.import_logs",
          },
          {
            to: "/attendance/leave-requests",
            label: "Đơn xin nghỉ phép",
            required: "attendance.manage_request",
          },
          {
            to: "/attendance/manual",
            label: "Chấm công bổ sung",
            required: anyOf("attendance.manage_request", "attendance.confirm"),
          },
          { to: "/attendance/summary", label: "Tổng hợp công", required: "attendance.view" },
        ],
      },
      {
        heading: "Báo cáo",
        items: [
          {
            to: "/reports?category=attendance&code=FUJIMART_ATTENDANCE_REPORT",
            label: "Bảng chấm công",
            required: allOf("attendance.view", "reports.view"),
          },
        ],
      },
    ],
  },
  {
    label: "Tiền lương",
    icon: CircleDollarSign,
    subCategories: [
      {
        heading: "Biến động",
        items: [
          { to: "/payroll/run", label: "Tính lương", required: "payroll.run" },
          {
            to: "/payroll/payslips",
            label: "Bảng lương",
            required: anyOf("payroll.view", "payroll.run", "payroll.finalize", "payroll.lock"),
          },
          {
            to: "/payroll/payslips/email",
            label: "Gửi email phiếu lương",
            required: "payroll.run",
          },
          {
            to: "/payroll/bonus-deductions",
            label: "Khen thưởng và kỷ luật",
            required: "payroll.adjust",
          },
        ],
      },
      {
        heading: "Danh mục",
        items: [
          {
            to: "/payroll/parameters",
            label: "Tham số lương",
            required: "payroll.manage_param",
          },
        ],
      },
      {
        heading: "Báo cáo",
        items: [
          {
            to: "/reports?category=payroll&code=FUJIMART_PAYROLL_SLIP",
            label: "Phiếu lương cá nhân",
            required: allOf("payroll.view", "reports.view"),
          },
          {
            to: "/reports?category=payroll&code=FUJIMART_PAYROLL_REPORT",
            label: "Bảng thanh toán lương theo chi nhánh/phòng ban",
            required: allOf("payroll.view", "reports.view"),
          },
        ],
      },
    ],
  },
  {
    label: "Quản trị",
    icon: Shield,
    children: [
      { to: "/admin/users", label: "Người dùng", required: "admin.users" },
      { to: "/admin/roles", label: "Phân quyền", required: "admin.roles" },
    ],
  },
];

const ROUTE_META: RouteMeta[] = [
  { pattern: "/", title: "Dashboard vận hành", subtitle: "Tổng quan", required: "dashboard.view" },

  { pattern: "/employees", title: "Hồ sơ cán bộ nhân viên", subtitle: "Nhân sự & HĐLĐ • Danh mục", required: "employee.view" },
  {
    pattern: "/reference/contract-types",
    title: "Danh mục loại hợp đồng",
    subtitle: "Nhân sự & HĐLĐ • Danh mục",
    required: "reference.manage",
  },
  {
    pattern: "/reference/salary-levels",
    title: "Danh mục thang bậc lương",
    subtitle: "Nhân sự & HĐLĐ • Danh mục",
    required: "reference.manage",
  },
  {
    pattern: "/reference/allowances",
    title: "Phụ cấp",
    subtitle: "Nhân sự & HĐLĐ • Biến động",
    required: "reference.manage",
  },
  { pattern: "/contracts", title: "Hợp đồng lao động", subtitle: "Nhân sự & HĐLĐ • Biến động", required: "contract.view" },
  {
    pattern: "/contracts/:id",
    title: "Chi tiết hợp đồng lao động",
    subtitle: "Nhân sự & HĐLĐ • Biến động",
    required: "contract.view",
  },

  { pattern: "/attendance", title: "Tổng quan chấm công", subtitle: "Chấm công", required: ATTENDANCE_ACCESS },
  {
    pattern: "/reference/late-early-rules",
    title: "Quy định đi trễ về sớm",
    subtitle: "Chấm công • Danh mục",
    required: "reference.manage",
  },
  {
    pattern: "/reference/holidays",
    title: "Danh mục ngày nghỉ trong năm",
    subtitle: "Chấm công • Danh mục",
    required: "reference.manage",
  },
  {
    pattern: "/reference/shifts",
    title: "Danh mục ca làm việc",
    subtitle: "Chấm công • Danh mục",
    required: "reference.manage",
  },
  {
    pattern: "/attendance/shift-assignments",
    title: "Phân ca làm việc",
    subtitle: "Chấm công • Biến động",
    required: "attendance.manage_period",
  },
  {
    pattern: "/attendance/logs",
    title: "Dữ liệu thời gian vào - ra",
    subtitle: "Chấm công • Biến động",
    required: "attendance.import_logs",
  },
  {
    pattern: "/attendance/leave-requests",
    title: "Đơn xin nghỉ phép",
    subtitle: "Chấm công • Biến động",
    required: "attendance.manage_request",
  },
  {
    pattern: "/attendance/manual",
    title: "Chấm công bổ sung",
    subtitle: "Chấm công • Biến động",
    required: anyOf("attendance.manage_request", "attendance.confirm"),
  },
  {
    pattern: "/attendance/summary",
    title: "Tổng hợp công",
    subtitle: "Chấm công • Biến động",
    required: "attendance.view",
  },

  { pattern: "/payroll", title: "Tổng quan tiền lương", subtitle: "Tiền lương", required: PAYROLL_ACCESS },
  {
    pattern: "/payroll/parameters",
    title: "Tham số lương",
    subtitle: "Tiền lương • Danh mục",
    required: "payroll.manage_param",
  },
  {
    pattern: "/payroll/bonus-deductions",
    title: "Khen thưởng và kỷ luật",
    subtitle: "Tiền lương • Biến động",
    required: "payroll.adjust",
  },
  { pattern: "/payroll/run", title: "Tính lương", subtitle: "Tiền lương • Biến động", required: "payroll.run" },
  {
    pattern: "/payroll/payslips/email",
    title: "Gửi email phiếu lương",
    subtitle: "Tiền lương • Biến động",
    required: "payroll.run",
  },
  {
    pattern: "/payroll/periods",
    title: "Bảng lương",
    subtitle: "Tiền lương • Biến động",
    required: anyOf("payroll.view", "payroll.run", "payroll.finalize", "payroll.lock"),
  },
  { pattern: "/payroll/payslips", title: "Bảng lương", subtitle: "Tiền lương • Biến động", required: "payroll.view" },
  {
    pattern: "/payroll/payslips/:id",
    title: "Chi tiết bảng lương",
    subtitle: "Tiền lương • Biến động",
    required: "payroll.view",
  },

  { pattern: "/reports", title: "Trung tâm báo cáo", subtitle: "Báo cáo", required: "reports.view" },
  { pattern: "/procedures", title: "Thủ tục SQL", subtitle: "Stored Procedures", required: "reports.view" },

  { pattern: "/admin", title: "Quản trị hệ thống", subtitle: "Quản trị", required: ADMIN_ACCESS },
  { pattern: "/admin/users", title: "Người dùng", subtitle: "Quản trị", required: "admin.users" },
  { pattern: "/admin/roles", title: "Phân quyền", subtitle: "Quản trị", required: "admin.roles" },
];

export function createPermissionSet(permissions?: string[]): Set<string> {
  return new Set(permissions ?? []);
}

export function hasPermissionAccess(permissionSet: Set<string>, rule?: PermissionRule): boolean {
  if (!rule) {
    return true;
  }

  if (typeof rule === "string") {
    return permissionSet.has(rule);
  }

  if (Array.isArray(rule.allOf) && rule.allOf.length > 0) {
    return rule.allOf.every((permission) => permissionSet.has(permission));
  }

  if (Array.isArray(rule.anyOf) && rule.anyOf.length > 0) {
    return rule.anyOf.some((permission) => permissionSet.has(permission));
  }

  return true;
}

function filterSection(section: NavigationSection, permissionSet: Set<string>): NavigationSection | null {
  const items = section.items.filter((item) => hasPermissionAccess(permissionSet, item.required));
  return items.length ? { ...section, items } : null;
}

function filterChildren(children: NavigationLeaf[], permissionSet: Set<string>): NavigationLeaf[] {
  return children.filter((item) => hasPermissionAccess(permissionSet, item.required));
}

export function getNavigationForPermissions(permissions?: string[]): NavigationItem[] {
  const permissionSet = createPermissionSet(permissions);

  return NAV_ITEMS.flatMap((item) => {
    if (item.to) {
      return hasPermissionAccess(permissionSet, item.required) ? [item] : [];
    }

    const subCategories = item.subCategories
      ?.map((section) => filterSection(section, permissionSet))
      .filter((section): section is NavigationSection => section !== null);

    const children = item.children ? filterChildren(item.children, permissionSet) : undefined;

    if ((subCategories?.length ?? 0) === 0 && (children?.length ?? 0) === 0) {
      return [];
    }

    if (!hasPermissionAccess(permissionSet, item.required)) {
      return [];
    }

    return [
      {
        ...item,
        subCategories,
        children,
      },
    ];
  });
}

export function getRouteMeta(pathname: string): RouteMeta | undefined {
  return ROUTE_META.find((route) => matchPath({ path: route.pattern, end: true }, pathname));
}

export function canAccessRoute(pathname: string, permissionSet: Set<string>): boolean {
  const route = getRouteMeta(pathname);
  return route ? hasPermissionAccess(permissionSet, route.required) : true;
}
