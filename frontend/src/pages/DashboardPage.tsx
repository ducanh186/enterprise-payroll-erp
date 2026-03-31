import { useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { useAuth } from "../context/AuthContext";
import { apiGet } from "../lib/api";
import { formatCurrency, formatNumber } from "../lib/format";
import { boolValue, numberValue, textValue, toArray } from "../lib/records";
import { createPermissionSet, hasPermissionAccess } from "../lib/rbac";
import { Badge, EmptyState, MetricCard, Panel, PageHeader } from "../components/ui";

function todayISO() {
  return new Date().toISOString().slice(0, 10);
}

const ROLE_LABELS: Record<string, string> = {
  system_admin: "Quản trị hệ thống",
  hr_staff: "Nhân sự",
  accountant: "Kế toán tiền lương",
  management: "Quản lý",
  employee: "Nhân viên",
};

export default function DashboardPage() {
  const { user } = useAuth();
  const date = todayISO();
  const permissionSet = useMemo(() => createPermissionSet(user?.permissions), [user?.permissions]);

  const canViewEmployees = hasPermissionAccess(permissionSet, "employee.view");
  const canViewAttendance = hasPermissionAccess(permissionSet, "attendance.view");
  const canViewContracts = hasPermissionAccess(permissionSet, "contract.view");
  const canViewReports = hasPermissionAccess(permissionSet, "reports.view");
  const canViewAdminUsers = hasPermissionAccess(permissionSet, "admin.users");
  const canViewPayslips = hasPermissionAccess(permissionSet, "payroll.view");
  const canViewPayrollPeriods = hasPermissionAccess(permissionSet, {
    anyOf: ["payroll.view", "payroll.run", "payroll.finalize", "payroll.lock"],
  });

  const employeesQuery = useQuery({
    queryKey: ["dashboard", "employees"],
    queryFn: async () => apiGet<unknown>("/employees", { page: 1, per_page: 1 }),
    enabled: canViewEmployees,
  });

  const attendanceQuery = useQuery({
    queryKey: ["dashboard", "attendance", date],
    queryFn: async () => apiGet<unknown>("/attendance/daily", { date }),
    enabled: canViewAttendance,
  });

  const contractsQuery = useQuery({
    queryKey: ["dashboard", "contracts"],
    queryFn: async () => apiGet<unknown>("/contracts", { page: 1, per_page: 5 }),
    enabled: canViewContracts,
  });

  const payslipsQuery = useQuery({
    queryKey: ["dashboard", "payslips"],
    queryFn: async () => apiGet<unknown>("/payroll/payslips", { page: 1, per_page: 5 }),
    enabled: canViewPayslips,
  });

  const periodsQuery = useQuery({
    queryKey: ["dashboard", "periods"],
    queryFn: async () => apiGet<unknown>("/payroll/periods"),
    enabled: canViewPayrollPeriods,
  });

  const reportsQuery = useQuery({
    queryKey: ["dashboard", "reports"],
    queryFn: async () => apiGet<unknown>("/reports/templates"),
    enabled: canViewReports,
  });

  const usersQuery = useQuery({
    queryKey: ["dashboard", "users"],
    queryFn: async () => apiGet<unknown>("/users"),
    enabled: canViewAdminUsers,
  });

  const employeesTotal = employeesQuery.data?.meta?.total ?? toArray(employeesQuery.data?.data).length;
  const attendanceItems = useMemo(
    () => toArray<Record<string, unknown>>(attendanceQuery.data?.data),
    [attendanceQuery.data?.data],
  );
  const contractItems = useMemo(
    () => toArray<Record<string, unknown>>(contractsQuery.data?.data),
    [contractsQuery.data?.data],
  );
  const payslipItems = useMemo(
    () => toArray<Record<string, unknown>>(payslipsQuery.data?.data),
    [payslipsQuery.data?.data],
  );
  const periodItems = useMemo(
    () => toArray<Record<string, unknown>>(periodsQuery.data?.data),
    [periodsQuery.data?.data],
  );
  const reportTemplates = useMemo(
    () => toArray<Record<string, unknown>>(reportsQuery.data?.data),
    [reportsQuery.data?.data],
  );
  const users = useMemo(() => toArray<Record<string, unknown>>(usersQuery.data?.data), [usersQuery.data?.data]);

  const metrics = [
    {
      label: "Role hiện tại",
      value: ROLE_LABELS[user?.role ?? "employee"] ?? (user?.role ?? "N/A"),
      hint: "Dashboard tự co giãn theo ma trận phân quyền.",
      tag: <Badge tone="neutral">RBAC</Badge>,
    },
    {
      label: "Số permission",
      value: formatNumber(permissionSet.size),
      hint: "Nguồn dữ liệu từ login payload và /me/permissions.",
      tag: <Badge tone="info">Security</Badge>,
    },
    ...(canViewEmployees
      ? [
          {
            label: "Tổng nhân viên",
            value: formatNumber(employeesTotal),
            hint: "Tổng số nhân viên trong hệ thống",
            tag: <Badge tone="info">Nhân sự</Badge>,
          },
        ]
      : []),
    ...(canViewAttendance
      ? [
          {
            label: "Chấm công hôm nay",
            value: formatNumber(attendanceItems.length),
            hint: "Số bản ghi chấm công trong ngày hiện tại",
            tag: <Badge tone="success">Attendance</Badge>,
          },
        ]
      : []),
    ...(canViewPayslips
      ? [
          {
            label: "Phiếu lương",
            value: formatNumber(payslipItems.length),
            hint: "5 phiếu lương gần nhất",
            tag: <Badge tone="accent">Payroll</Badge>,
          },
        ]
      : []),
    ...(canViewReports
      ? [
          {
            label: "Template báo cáo",
            value: formatNumber(reportTemplates.length),
            hint: "Các mẫu báo cáo hiện có",
            tag: <Badge tone="neutral">Reports</Badge>,
          },
        ]
      : []),
  ];

  const hasOperationalPanels =
    canViewAttendance ||
    canViewContracts ||
    canViewPayslips ||
    canViewReports ||
    canViewAdminUsers;

  return (
    <div className="space-y-6">
      <PageHeader
        eyebrow="Tổng quan"
        title="Dashboard vận hành"
        description="Widget trên dashboard chỉ hiển thị dữ liệu mà role hiện tại được phép xem theo ma trận phân quyền."
        actions={<Badge tone="neutral">Role: {ROLE_LABELS[user?.role ?? "employee"] ?? (user?.role ?? "N/A")}</Badge>}
      />

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {metrics.map((metric) => (
          <MetricCard key={String(metric.label)} {...metric} />
        ))}
      </div>

      {hasOperationalPanels ? (
        <div className="grid gap-6 xl:grid-cols-2">
          {canViewAttendance && (
            <Panel title="Chấm công hôm nay" subtitle="Xem nhanh dữ liệu chấm công trong ngày">
              {attendanceQuery.isLoading ? (
                <p className="text-sm text-slate-500">Đang tải...</p>
              ) : attendanceItems.length ? (
                <div className="space-y-3">
                  {attendanceItems.slice(0, 5).map((item, index) => (
                    <div
                      key={`${textValue(item, ["id"], String(index))}-${index}`}
                      className="flex items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3"
                    >
                      <div>
                        <p className="font-medium text-slate-900">
                          {textValue(item, ["employee.full_name", "employee_name", "full_name"], "Nhân viên")}
                        </p>
                        <p className="text-sm text-slate-500">
                          {textValue(item, ["employee.employee_code", "employee_code"], "N/A")}
                        </p>
                      </div>
                      <div className="text-right">
                        <p className="text-sm font-medium text-slate-800">
                          {textValue(item, ["first_in", "check_in", "check_time"], "N/A")}
                        </p>
                        <p className="text-xs text-slate-500">
                          {textValue(item, ["attendance_status", "status"], "ok")}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <EmptyState
                  title="Không có attendance data"
                  description="Danh sách chấm công sẽ xuất hiện khi API daily attendance có dữ liệu."
                />
              )}
            </Panel>
          )}

          {(canViewPayslips || canViewPayrollPeriods) && (
            <Panel title="Tổng quan phiếu lương" subtitle="Kỳ lương và phiếu lương gần nhất">
              <div className="grid gap-3 sm:grid-cols-2">
                <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Kỳ lương</p>
                  <p className="mt-2 text-2xl font-bold text-slate-950">{formatNumber(periodItems.length)}</p>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Phiếu lương</p>
                  <p className="mt-2 text-2xl font-bold text-slate-950">{formatNumber(payslipItems.length)}</p>
                </div>
              </div>
              <div className="mt-4 space-y-3">
                {payslipItems.length ? (
                  payslipItems.slice(0, 4).map((item, index) => (
                    <div
                      key={`${textValue(item, ["id"], String(index))}-${index}`}
                      className="rounded-2xl border border-slate-200/80 bg-white px-4 py-3"
                    >
                      <div className="flex items-center justify-between gap-3">
                        <div>
                          <p className="font-medium text-slate-900">
                            {textValue(item, ["employee.full_name", "employee_name", "full_name"], "Nhân viên")}
                          </p>
                          <p className="text-sm text-slate-500">{textValue(item, ["status"], "draft")}</p>
                        </div>
                        <Badge tone="accent">
                          {formatCurrency(numberValue(item, ["net_salary", "net_pay"], 0))}
                        </Badge>
                      </div>
                    </div>
                  ))
                ) : (
                  <EmptyState
                    title="Không có phiếu lương"
                    description="Role hiện tại có quyền payroll nhưng chưa có dữ liệu phù hợp để hiển thị."
                  />
                )}
              </div>
            </Panel>
          )}

          {canViewContracts && (
            <Panel title="Hợp đồng" subtitle="5 bản ghi gần nhất">
              {contractItems.length ? (
                <div className="space-y-3">
                  {contractItems.slice(0, 4).map((item, index) => (
                    <div
                      key={`${textValue(item, ["id"], String(index))}-${index}`}
                      className="rounded-2xl border border-slate-200/80 bg-white px-4 py-3"
                    >
                      <p className="font-medium text-slate-900">
                        {textValue(item, ["employee.full_name", "employee_name", "full_name"], "Nhân viên")}
                      </p>
                      <p className="text-sm text-slate-500">
                        {textValue(item, ["contract_no"], "N/A")} •{" "}
                        {textValue(item, ["contractType.name", "contract_type.name", "type"], "contract")}
                      </p>
                    </div>
                  ))}
                </div>
              ) : (
                <EmptyState
                  title="Không có hợp đồng"
                  description="Danh sách hợp đồng sẽ xuất hiện khi API contract trả dữ liệu."
                />
              )}
            </Panel>
          )}

          {canViewReports && (
            <Panel title="Mẫu báo cáo" subtitle="Danh sách template đang khả dụng">
              {reportsQuery.isLoading ? (
                <p className="text-sm text-slate-500">Đang tải...</p>
              ) : reportTemplates.length ? (
                <div className="space-y-3">
                  {reportTemplates.slice(0, 5).map((item, index) => (
                    <div
                      key={`${textValue(item, ["code", "id"], String(index))}-${index}`}
                      className="rounded-2xl border border-slate-200/80 bg-white px-4 py-3"
                    >
                      <div className="flex items-center justify-between gap-3">
                        <div>
                          <p className="font-medium text-slate-900">
                            {textValue(item, ["name", "label", "title"], "Report template")}
                          </p>
                          <p className="text-sm text-slate-500">{textValue(item, ["code", "category"], "N/A")}</p>
                        </div>
                        <Badge tone="neutral">{textValue(item, ["format"], "template")}</Badge>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <EmptyState
                  title="Không có template báo cáo"
                  description="API reports/templates chưa trả danh sách template cho role này."
                />
              )}
            </Panel>
          )}

          {canViewAdminUsers && (
            <Panel title="Quản trị người dùng" subtitle="Tóm tắt tài khoản đang hoạt động">
              {usersQuery.isLoading ? (
                <p className="text-sm text-slate-500">Đang tải...</p>
              ) : (
                <div className="grid gap-3 sm:grid-cols-2">
                  <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                    <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tổng user</p>
                    <p className="mt-2 text-2xl font-bold text-slate-950">{formatNumber(users.length)}</p>
                  </div>
                  <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                    <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Đang hoạt động</p>
                    <p className="mt-2 text-2xl font-bold text-slate-950">
                      {formatNumber(users.filter((item) => boolValue(item, ["is_active"], true)).length)}
                    </p>
                  </div>
                </div>
              )}
            </Panel>
          )}
        </div>
      ) : (
        <Panel title="Dashboard theo role" subtitle="Không lộ dữ liệu ngoài phạm vi phân quyền">
          <EmptyState
            title="Role hiện tại không có widget nghiệp vụ tổng hợp"
            description="Điều này là bình thường với role chỉ có self-service hoặc role quản trị hẹp. Khi các màn self-service được triển khai, dashboard có thể bổ sung widget riêng cho employee."
          />
        </Panel>
      )}
    </div>
  );
}
