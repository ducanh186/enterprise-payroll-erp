import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import {
  BarChart3,
  CheckCircle2,
  ChevronDown,
  RefreshCcw,
  Shield,
  ShieldCheck,
  Users,
} from "lucide-react";
import { apiGet } from "../lib/api";
import { textValue, toArray } from "../lib/records";
import { EmptyState, PageHeader } from "../components/ui";

const MODULE_ICONS: Record<string, { icon: React.ReactNode; bg: string; color: string }> = {
  employee: {
    icon: <Users className="h-4 w-4" />,
    bg: "bg-blue-50",
    color: "text-indigo-700",
  },
  contract: {
    icon: <ShieldCheck className="h-4 w-4" />,
    bg: "bg-emerald-50",
    color: "text-emerald-600",
  },
  attendance: {
    icon: <CheckCircle2 className="h-4 w-4" />,
    bg: "bg-amber-50",
    color: "text-amber-600",
  },
  payroll: {
    icon: <BarChart3 className="h-4 w-4" />,
    bg: "bg-slate-100",
    color: "text-slate-600",
  },
  reports: {
    icon: <BarChart3 className="h-4 w-4" />,
    bg: "bg-purple-50",
    color: "text-purple-600",
  },
  admin: {
    icon: <Shield className="h-4 w-4" />,
    bg: "bg-rose-50",
    color: "text-rose-600",
  },
  auth: {
    icon: <Shield className="h-4 w-4" />,
    bg: "bg-indigo-50",
    color: "text-indigo-600",
  },
};

function getModuleStyle(module: string) {
  return (
    MODULE_ICONS[module] ?? {
      icon: <Shield className="h-4 w-4" />,
      bg: "bg-slate-50",
      color: "text-slate-500",
    }
  );
}

const ROLE_COLORS: string[] = [
  "bg-indigo-100 text-indigo-700 border-indigo-200",
  "bg-sky-100 text-sky-700 border-sky-200",
  "bg-emerald-100 text-emerald-700 border-emerald-200",
  "bg-amber-100 text-amber-700 border-amber-200",
  "bg-rose-100 text-rose-700 border-rose-200",
];

export default function RolePermissionsPage() {
  const rolesQuery = useQuery({
    queryKey: ["admin", "roles"],
    queryFn: async () => apiGet<unknown>("/roles"),
  });

  const permissionsQuery = useQuery({
    queryKey: ["admin", "permissions"],
    queryFn: async () => apiGet<unknown>("/permissions"),
  });

  const roles = useMemo(
    () => toArray<Record<string, unknown>>(rolesQuery.data?.data),
    [rolesQuery.data?.data],
  );

  const permissions = useMemo(
    () => toArray<Record<string, unknown>>(permissionsQuery.data?.data),
    [permissionsQuery.data?.data],
  );

  // Group permissions by module (e.g., "auth.login" → "auth")
  const permissionGroups = useMemo(() => {
    const groups: Record<string, Record<string, unknown>[]> = {};
    for (const perm of permissions) {
      const code = textValue(perm, ["code", "name"], "");
      const module = code.split(".")[0] || "other";
      if (!groups[module]) groups[module] = [];
      groups[module].push(perm);
    }
    return groups;
  }, [permissions]);

  // Build lookup: roleId → Set of permission codes
  const rolePermissionMap = useMemo(() => {
    const map = new Map<string, Set<string>>();
    for (const role of roles) {
      const roleId = textValue(role, ["id"], "");
      const perms = toArray<Record<string, unknown>>(role.permissions);
      map.set(roleId, new Set(perms.map((p) => textValue(p, ["code", "name"], ""))));
    }
    return map;
  }, [roles]);

  const isLoading = rolesQuery.isLoading || permissionsQuery.isLoading;

  // Active role tab for the role tab selector
  const [activeRoleIndex, setActiveRoleIndex] = useState(0);
  const [expandedModules, setExpandedModules] = useState<Record<string, boolean>>({});

  function toggleModule(module: string) {
    setExpandedModules((prev) => ({ ...prev, [module]: !prev[module] }));
  }

  const activeRole = roles[activeRoleIndex] ?? null;
  const activeRoleId = activeRole ? textValue(activeRole, ["id"], "") : "";
  const activeRolePerms = rolePermissionMap.get(activeRoleId);

  return (
    <div className="space-y-8">
      {/* Page Header */}
      <PageHeader
        eyebrow="Quản trị / Phân quyền"
        title="Ma trận vai trò và quyền hạn"
        description="Xem và quản lý phân quyền theo vai trò trong hệ thống."
        actions={
          <div className="flex items-center gap-3">
            <button
              type="button"
              onClick={() => {
                rolesQuery.refetch();
                permissionsQuery.refetch();
              }}
              className="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 active:scale-95"
            >
              <RefreshCcw className="h-4 w-4" />
              Làm mới
            </button>
          </div>
        }
      />

      {/* Role Summary Cards */}
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        {isLoading
          ? Array.from({ length: 4 }).map((_, i) => (
              <div
                key={i}
                className="rounded-xl border border-slate-100 bg-white p-5 shadow-sm animate-pulse h-28"
              />
            ))
          : roles.map((role, index) => {
              const roleId = textValue(role, ["id"], "");
              const rolePerms = rolePermissionMap.get(roleId);
              const colorClass = ROLE_COLORS[index % ROLE_COLORS.length];
              const isActive = index === activeRoleIndex;

              return (
                <button
                  key={`${roleId}-${index}`}
                  type="button"
                  onClick={() => setActiveRoleIndex(index)}
                  className={`rounded-xl border p-5 text-left transition hover:shadow-md ${
                    isActive
                      ? "border-indigo-200 bg-indigo-50 shadow-md shadow-indigo-100/50"
                      : "border-slate-100 bg-white shadow-sm hover:border-slate-200"
                  }`}
                >
                  <div className="flex items-center gap-3">
                    <div
                      className={`flex h-9 w-9 items-center justify-center rounded-lg border ${colorClass}`}
                    >
                      <ShieldCheck className="h-4 w-4" />
                    </div>
                    <div className="min-w-0">
                      <p className="truncate text-sm font-bold text-slate-900">
                        {textValue(role, ["name", "display_name"], "Role")}
                      </p>
                      <p className="text-[10px] text-slate-500">
                        {textValue(role, ["code"], "")}
                      </p>
                    </div>
                  </div>
                  <div className="mt-3 flex items-baseline gap-1">
                    <span className="text-2xl font-extrabold text-slate-900">
                      {rolePerms?.size ?? 0}
                    </span>
                    <span className="text-xs text-slate-500">quyền</span>
                  </div>
                </button>
              );
            })}
      </div>

      {/* Role Tabs + Matrix */}
      <div className="grid gap-8 lg:grid-cols-[1fr_280px]">
        {/* Main Permission Matrix */}
        <section className="space-y-4">
          {/* Role Tabs */}
          {roles.length > 0 && (
            <div className="flex gap-1 rounded-xl bg-slate-100 p-1 w-fit overflow-x-auto">
              {roles.map((role, index) => (
                <button
                  key={`tab-${textValue(role, ["id"], String(index))}`}
                  type="button"
                  onClick={() => setActiveRoleIndex(index)}
                  className={`whitespace-nowrap rounded-lg px-5 py-2 text-sm font-semibold transition ${
                    index === activeRoleIndex
                      ? "bg-white text-indigo-700 shadow-sm"
                      : "text-slate-500 hover:text-slate-900"
                  }`}
                >
                  {textValue(role, ["name", "display_name"], "Role")}
                </button>
              ))}
            </div>
          )}

          {/* Matrix Table */}
          <div className="rounded-xl border border-slate-100 bg-white shadow-sm overflow-hidden">
            {isLoading ? (
              <div className="px-6 py-12 text-center text-sm text-slate-500">
                Đang tải ma trận phân quyền...
              </div>
            ) : !permissions.length ? (
              <div className="p-8">
                <EmptyState
                  title="Không có dữ liệu phân quyền"
                  description="Backend chưa trả về danh sách phân quyền. Kiểm tra GET /permissions."
                />
              </div>
            ) : (
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-slate-50/60">
                    <th className="px-6 py-4 text-[11px] font-bold uppercase tracking-widest text-slate-400">
                      Chức năng / Quyền
                    </th>
                    {roles.map((role, index) => (
                      <th
                        key={`hdr-${textValue(role, ["id"], String(index))}`}
                        className={`px-4 py-4 text-center text-[11px] font-bold uppercase tracking-widest ${
                          index === activeRoleIndex ? "text-indigo-600" : "text-slate-400"
                        }`}
                      >
                        {textValue(role, ["name", "display_name"], "Role")}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-50">
                  {Object.entries(permissionGroups).flatMap(([module, perms]) => {
                    const style = getModuleStyle(module);
                    const isExpanded = expandedModules[module] !== false; // default expanded

                    const rows = [
                      <tr
                        key={`module-${module}`}
                        className="cursor-pointer hover:bg-slate-50/70 transition"
                        onClick={() => toggleModule(module)}
                      >
                        <td
                          colSpan={roles.length + 1}
                          className="px-6 py-3"
                        >
                          <div className="flex items-center gap-3">
                            <div
                              className={`flex h-8 w-8 items-center justify-center rounded ${style.bg} ${style.color}`}
                            >
                              {style.icon}
                            </div>
                            <span className="text-sm font-bold capitalize text-slate-700">
                              {module}
                            </span>
                            <span className="text-xs text-slate-400">({perms.length})</span>
                            <ChevronDown
                              className={`ml-auto h-4 w-4 text-slate-400 transition-transform ${
                                isExpanded ? "" : "-rotate-90"
                              }`}
                            />
                          </div>
                        </td>
                      </tr>,
                    ];

                    if (isExpanded) {
                      rows.push(
                        ...perms.map((perm, permIndex) => {
                          const permCode = textValue(perm, ["code", "name"], "");
                          const permLabel = permCode.split(".").slice(1).join(".");

                          return (
                            <tr
                              key={`perm-${module}-${permCode}-${permIndex}`}
                              className="transition hover:bg-sky-50/40"
                            >
                              <td className="border-t border-slate-50 px-6 py-3 pl-16 text-sm text-slate-700">
                                <span className="font-medium">{permLabel || permCode}</span>
                                {textValue(perm, ["description"], "") && (
                                  <span className="ml-2 text-xs text-slate-400">
                                    {textValue(perm, ["description"], "")}
                                  </span>
                                )}
                              </td>
                              {roles.map((role, roleIndex) => {
                                const roleId = textValue(role, ["id"], "");
                                const has = rolePermissionMap.get(roleId)?.has(permCode);
                                const isHighlighted = roleIndex === activeRoleIndex;

                                return (
                                  <td
                                    key={`cell-${module}-${permCode}-${roleId}-${roleIndex}`}
                                    className={`border-t border-slate-50 px-4 py-3 text-center ${
                                      isHighlighted ? "bg-indigo-50/30" : ""
                                    }`}
                                  >
                                    {has ? (
                                      <CheckCircle2 className="mx-auto h-4 w-4 text-emerald-500" />
                                    ) : (
                                      <span className="text-base text-slate-200">—</span>
                                    )}
                                  </td>
                                );
                              })}
                            </tr>
                          );
                        }),
                      );
                    }

                    return rows;
                  })}
                </tbody>
              </table>
            )}
          </div>
        </section>

        {/* Right Sidebar: Role Insights */}
        <aside className="space-y-6">
          {/* Active Role Info */}
          {activeRole && (
            <div className="rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
              <h3 className="mb-4 text-xs font-bold uppercase tracking-widest text-slate-500">
                Thông tin vai trò
              </h3>
              <div className="grid grid-cols-2 gap-3">
                <div className="rounded-lg bg-slate-50 p-3">
                  <p className="text-[10px] font-bold uppercase text-slate-400">Vai trò</p>
                  <p className="mt-1 text-sm font-bold text-slate-900 truncate">
                    {textValue(activeRole, ["name", "display_name"], "—")}
                  </p>
                </div>
                <div className="rounded-lg bg-slate-50 p-3">
                  <p className="text-[10px] font-bold uppercase text-slate-400">Quyền</p>
                  <p className="mt-1 text-2xl font-extrabold text-slate-900">
                    {activeRolePerms?.size ?? 0}
                  </p>
                </div>
              </div>

              <div className="mt-4 rounded-lg border-l-4 border-indigo-500 bg-indigo-50 p-4">
                <p className="text-[10px] font-bold uppercase tracking-widest text-indigo-700">
                  Lưu ý phân quyền
                </p>
                <p className="mt-1 text-xs leading-relaxed text-indigo-700/80">
                  Thay đổi vai trò "{textValue(activeRole, ["name", "display_name"], "vai trò này")}" sẽ
                  ảnh hưởng đến tất cả người dùng đang sử dụng vai trò này ngay sau khi lưu.
                </p>
              </div>
            </div>
          )}

          {/* System-wide Permissions Panel */}
          <div className="rounded-xl border border-slate-100 bg-slate-50 p-6">
            <div className="mb-5 flex items-center gap-2">
              <Shield className="h-4 w-4 text-indigo-700" />
              <h3 className="text-xs font-bold uppercase tracking-widest text-slate-500">
                Tổng hợp chức năng
              </h3>
            </div>
            <div className="space-y-3">
              {Object.entries(permissionGroups).map(([module, perms]) => {
                const style = getModuleStyle(module);
                const modulePermsGranted = activeRolePerms
                  ? perms.filter((p) =>
                      activeRolePerms.has(textValue(p, ["code", "name"], ""))
                    ).length
                  : 0;
                const total = perms.length;
                const pct = total > 0 ? Math.round((modulePermsGranted / total) * 100) : 0;

                return (
                  <div key={`summary-${module}`}>
                    <div className="mb-1 flex items-center justify-between">
                      <div className="flex items-center gap-2">
                        <span className={`${style.color} capitalize text-xs font-semibold`}>
                          {module}
                        </span>
                      </div>
                      <span className="text-[10px] font-bold text-slate-500">
                        {modulePermsGranted}/{total}
                      </span>
                    </div>
                    <div className="h-1.5 overflow-hidden rounded-full bg-slate-200">
                      <div
                        className="h-full rounded-full bg-indigo-500 transition-all"
                        style={{ width: `${pct}%` }}
                      />
                    </div>
                  </div>
                );
              })}
              {!Object.keys(permissionGroups).length && !isLoading && (
                <p className="text-xs text-slate-400">Không có dữ liệu phân quyền.</p>
              )}
            </div>
          </div>

          {/* Legend */}
          <div className="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
            <h3 className="mb-3 text-xs font-bold uppercase tracking-widest text-slate-500">
              Chú thích
            </h3>
            <div className="space-y-2">
              <div className="flex items-center gap-3">
                <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                <span className="text-sm text-slate-700">Đã cấp quyền</span>
              </div>
              <div className="flex items-center gap-3">
                <span className="text-base text-slate-300">—</span>
                <span className="text-sm text-slate-700">Chưa cấp quyền</span>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </div>
  );
}
