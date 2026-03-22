import { useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { apiGet } from "../lib/api";
import { formatCurrency, formatNumber } from "../lib/format";
import { boolValue, numberValue, textValue, toArray } from "../lib/records";
import { Badge, EmptyState, MetricCard, Panel, PageHeader } from "../components/ui";

function todayISO() {
  return new Date().toISOString().slice(0, 10);
}

export default function DashboardPage() {
  const date = todayISO();

  const employeesQuery = useQuery({
    queryKey: ["dashboard", "employees"],
    queryFn: async () => apiGet<unknown>("/employees", { page: 1, per_page: 1 }),
  });

  const attendanceQuery = useQuery({
    queryKey: ["dashboard", "attendance", date],
    queryFn: async () => apiGet<unknown>("/attendance/daily", { date }),
  });

  const contractsQuery = useQuery({
    queryKey: ["dashboard", "contracts"],
    queryFn: async () => apiGet<unknown>("/contracts", { page: 1, per_page: 5 }),
  });

  const payslipsQuery = useQuery({
    queryKey: ["dashboard", "payslips"],
    queryFn: async () => apiGet<unknown>("/payroll/payslips", { page: 1, per_page: 5 }),
  });

  const periodsQuery = useQuery({
    queryKey: ["dashboard", "periods"],
    queryFn: async () => apiGet<unknown>("/payroll/periods"),
  });

  const reportsQuery = useQuery({
    queryKey: ["dashboard", "reports"],
    queryFn: async () => apiGet<unknown>("/reports/templates"),
  });

  const usersQuery = useQuery({
    queryKey: ["dashboard", "users"],
    queryFn: async () => apiGet<unknown>("/users"),
  });

  const employeesTotal = employeesQuery.data?.meta?.total ?? toArray(employeesQuery.data?.data).length;
  const attendanceItems = useMemo(() => toArray<Record<string, unknown>>(attendanceQuery.data?.data), [attendanceQuery.data?.data]);
  const contractItems = useMemo(() => toArray<Record<string, unknown>>(contractsQuery.data?.data), [contractsQuery.data?.data]);
  const payslipItems = useMemo(() => toArray<Record<string, unknown>>(payslipsQuery.data?.data), [payslipsQuery.data?.data]);
  const periodItems = useMemo(() => toArray<Record<string, unknown>>(periodsQuery.data?.data), [periodsQuery.data?.data]);
  const reportTemplates = useMemo(() => toArray<Record<string, unknown>>(reportsQuery.data?.data), [reportsQuery.data?.data]);
  const users = useMemo(() => toArray<Record<string, unknown>>(usersQuery.data?.data), [usersQuery.data?.data]);

  const metrics = [
    {
      label: "Tổng nhân viên",
      value: formatNumber(employeesTotal),
      hint: "Từ /employees",
      tag: <Badge tone="info">HR</Badge>,
    },
    {
      label: "Chấm công hôm nay",
      value: formatNumber(attendanceItems.length),
      hint: "Từ /attendance/daily",
      tag: <Badge tone="success">Live</Badge>,
    },
    {
      label: "Phiếu lương",
      value: formatNumber(payslipItems.length),
      hint: "Top 5 gần nhất",
      tag: <Badge tone="accent">Payroll</Badge>,
    },
    {
      label: "Template báo cáo",
      value: formatNumber(reportTemplates.length),
      hint: "Seed report templates",
      tag: <Badge tone="neutral">Reports</Badge>,
    },
  ];

  return (
    <div className="space-y-6">
      <PageHeader
        eyebrow="Overview"
        title="Dashboard vận hành"
        description="Điểm vào để xem tình trạng HR, attendance, payroll và admin trong một màn hình."
        actions={<Badge tone="neutral">Tự động làm mới khi chuyển route</Badge>}
      />

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {metrics.map((metric) => (
          <MetricCard key={metric.label} {...metric} />
        ))}
      </div>

      <div className="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <Panel title="Chấm công hôm nay" subtitle="Quick view từ API hiện có">
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
                    <p className="text-sm text-slate-500">{textValue(item, ["employee.employee_code", "employee_code"], "N/A")}</p>
                  </div>
                  <div className="text-right">
                    <p className="text-sm font-medium text-slate-800">
                      {textValue(item, ["first_in", "check_in", "check_time"], "N/A")}
                    </p>
                    <p className="text-xs text-slate-500">{textValue(item, ["attendance_status", "status"], "ok")}</p>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <EmptyState
              title="Không có attendance data"
              description="Khi service trả daily attendance, danh sách sẽ xuất hiện tại đây."
            />
          )}
        </Panel>

        <div className="space-y-6">
          <Panel title="Payroll snapshots" subtitle="Kỳ lương và phiếu lương gần nhất">
            <div className="grid gap-3 sm:grid-cols-2">
              <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Kỳ lương</p>
                <p className="mt-2 text-2xl font-bold text-slate-950">{formatNumber(periodItems.length)}</p>
              </div>
              <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Users active</p>
                <p className="mt-2 text-2xl font-bold text-slate-950">
                  {formatNumber(users.filter((user) => boolValue(user, ["is_active"], true)).length)}
                </p>
              </div>
            </div>
            <div className="mt-4 space-y-3">
              {payslipItems.slice(0, 4).map((item, index) => (
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
                    <Badge tone="accent">{formatCurrency(numberValue(item, ["net_salary", "net_pay"], 0))}</Badge>
                  </div>
                </div>
              ))}
            </div>
          </Panel>

          <Panel title="Contracts" subtitle="5 bản ghi gần nhất">
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
              <EmptyState title="Không có hợp đồng" description="Danh sách contract sẽ ở đây khi API trả data." />
            )}
          </Panel>
        </div>
      </div>
    </div>
  );
}
