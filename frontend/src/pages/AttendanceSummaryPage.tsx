import { useMemo, useState } from "react";
import type { FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  RefreshCw,
  Filter,
  ChevronLeft,
  ChevronRight,
  MoreVertical,
  AlertTriangle,
  Lock,
  CheckCircle2,
} from "lucide-react";
import { apiGet, apiPost, getApiErrorMessage } from "../lib/api";
import { useAuth } from "../context/AuthContext";
import { formatDate, formatNumber, formatPercent } from "../lib/format";
import { numberValue, textValue, toArray } from "../lib/records";
import { createPermissionSet, hasPermissionAccess } from "../lib/rbac";
import { Badge, EmptyState } from "../components/ui";

type SummaryFilters = {
  month: string;
  year: string;
  department_id: string;
};

const current = new Date();

const MONTHS = [
  { value: "1", label: "Tháng 1" },
  { value: "2", label: "Tháng 2" },
  { value: "3", label: "Tháng 3" },
  { value: "4", label: "Tháng 4" },
  { value: "5", label: "Tháng 5" },
  { value: "6", label: "Tháng 6" },
  { value: "7", label: "Tháng 7" },
  { value: "8", label: "Tháng 8" },
  { value: "9", label: "Tháng 9" },
  { value: "10", label: "Tháng 10" },
  { value: "11", label: "Tháng 11" },
  { value: "12", label: "Tháng 12" },
];

const YEARS = Array.from({ length: 5 }, (_, i) => {
  const y = current.getFullYear() - i;
  return { value: String(y), label: String(y) };
});

function getStatusBadge(status: string) {
  const s = status.toLowerCase();
  if (s.includes("ready") || s.includes("approved")) {
    return (
      <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-bold uppercase tracking-tight text-emerald-700 ring-1 ring-emerald-200">
        <span className="h-1.5 w-1.5 rounded-full bg-emerald-600" />
        {status}
      </span>
    );
  }
  if (s.includes("locked") || s.includes("finalized")) {
    return (
      <span className="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1 text-[10px] font-bold uppercase tracking-tight text-indigo-700 ring-1 ring-indigo-200">
        <Lock className="h-3 w-3" />
        {status}
      </span>
    );
  }
  if (s.includes("review") || s.includes("manual") || s.includes("anomaly")) {
    return (
      <span className="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-[10px] font-bold uppercase tracking-tight text-rose-700 ring-1 ring-rose-200">
        <span className="h-3 w-3 rounded-sm bg-rose-500 text-white flex items-center justify-center text-[7px]">!</span>
        {status}
      </span>
    );
  }
  return (
    <span className="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-[10px] font-bold uppercase tracking-tight text-slate-500 ring-1 ring-slate-200">
      {status}
    </span>
  );
}

function getInitials(name: string): string {
  return name
    .split(" ")
    .map((w) => w[0])
    .join("")
    .slice(0, 2)
    .toUpperCase();
}

const AVATAR_COLORS = [
  "bg-indigo-100 text-indigo-700",
  "bg-sky-100 text-sky-700",
  "bg-teal-100 text-teal-700",
  "bg-violet-100 text-violet-700",
  "bg-amber-100 text-amber-700",
];

function avatarColor(name: string): string {
  let hash = 0;
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
  return AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length];
}

export default function AttendanceSummaryPage() {
  const { user } = useAuth();
  const queryClient = useQueryClient();
  const [filters, setFilters] = useState<SummaryFilters>({
    month: String(current.getMonth() + 1),
    year: String(current.getFullYear()),
    department_id: "",
  });
  const [error, setError] = useState<string | null>(null);
  const permissionSet = createPermissionSet(user?.permissions);
  const canRecalculateAttendance = hasPermissionAccess(permissionSet, "attendance.calculate");
  const canConfirmAttendance = hasPermissionAccess(permissionSet, "attendance.confirm");
  const canExportAttendance = hasPermissionAccess(permissionSet, "reports.export");

  const summaryQuery = useQuery({
    queryKey: ["attendance", "monthly-summary", filters],
    queryFn: async () =>
      apiGet<unknown>("/attendance/monthly-summary", {
        month: Number(filters.month),
        year: Number(filters.year),
        ...(filters.department_id ? { department_id: Number(filters.department_id) } : {}),
      }),
  });

  const recalculateMutation = useMutation({
    mutationFn: async () =>
      apiPost<unknown>("/attendance/recalculate", {
        month: Number(filters.month),
        year: Number(filters.year),
      }),
    onSuccess: async () => {
      setError(null);
      await queryClient.invalidateQueries({ queryKey: ["attendance", "monthly-summary"] });
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể chạy recalculation."));
    },
  });

  const rows = useMemo(() => toArray<Record<string, unknown>>(summaryQuery.data?.data), [summaryQuery.data?.data]);
  const totals = useMemo(() => {
    const first = rows[0] ?? (summaryQuery.data?.data && typeof summaryQuery.data.data === "object" ? (summaryQuery.data.data as Record<string, unknown>) : {});
    return first;
  }, [rows, summaryQuery.data?.data]);

  const totalEmployees = formatNumber(numberValue(totals, ["total_employees", "employee_count"], rows.length));
  const readyCount = formatNumber(numberValue(totals, ["ready_count", "approved_count"], 0));
  const pendingCount = formatNumber(numberValue(totals, ["pending_count", "review_count"], 0));
  const attendanceRate = formatPercent(numberValue(totals, ["average_attendance_rate", "attendance_rate"], 0));

  const currentMonthLabel = MONTHS.find((m) => m.value === filters.month)?.label ?? `Tháng ${filters.month}`;

  function submitRecalculate(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!canRecalculateAttendance) {
      return;
    }
    recalculateMutation.mutate();
  }

  return (
    <div className="space-y-8 pb-10">
      {/* Page Header */}
      <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
        <div>
          <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">
            Tổng kết điểm danh tháng
          </h2>
          <p className="mt-1 text-sm font-medium text-slate-500">
            Đang xem dữ liệu kỳ{" "}
            <span className="font-bold text-indigo-700">
              {currentMonthLabel} {filters.year}
            </span>
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-3">
          {canRecalculateAttendance && (
            <form onSubmit={submitRecalculate}>
              <button
                type="submit"
                disabled={recalculateMutation.isPending}
                className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
              >
                <RefreshCw className={`h-4 w-4 ${recalculateMutation.isPending ? "animate-spin" : ""}`} />
                {recalculateMutation.isPending ? "Đang chạy..." : "Tính lại"}
              </button>
            </form>
          )}
          {canExportAttendance && (
            <button
              type="button"
              className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-slate-300"
            >
              Xuất Excel
            </button>
          )}
          {canConfirmAttendance && (
            <button
              type="button"
              className="inline-flex items-center gap-2 rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-6 py-2.5 text-sm font-bold text-white shadow-lg transition hover:opacity-90 active:scale-95"
            >
              <CheckCircle2 className="h-4 w-4" />
              Xác nhận tất cả
            </button>
          )}
        </div>
      </div>

      {/* Error Banner */}
      {error && (
        <p className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
          {error}
        </p>
      )}

      {/* Filter Bar */}
      <div className="rounded-2xl border border-white/70 bg-white/80 px-5 py-4 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur">
        <div className="flex flex-wrap items-end gap-6">
          <div className="flex flex-col gap-1.5">
            <label className="text-[10px] font-bold uppercase tracking-widest text-slate-400">
              Phòng ban
            </label>
            <select
              value={filters.department_id}
              onChange={(e) => setFilters((f) => ({ ...f, department_id: e.target.value }))}
              className="rounded-xl border border-slate-200 bg-slate-50 py-2 pl-3 pr-10 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 min-w-[160px]"
            >
              <option value="">Tất cả phòng ban</option>
            </select>
          </div>

          <div className="flex flex-col gap-1.5">
            <label className="text-[10px] font-bold uppercase tracking-widest text-slate-400">
              Tháng
            </label>
            <select
              value={filters.month}
              onChange={(e) => setFilters((f) => ({ ...f, month: e.target.value }))}
              className="rounded-xl border border-slate-200 bg-slate-50 py-2 pl-3 pr-10 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 min-w-[160px]"
            >
              {MONTHS.map((m) => (
                <option key={m.value} value={m.value}>
                  {m.label} {filters.year}
                </option>
              ))}
            </select>
          </div>

          <div className="flex flex-col gap-1.5">
            <label className="text-[10px] font-bold uppercase tracking-widest text-slate-400">
              Năm
            </label>
            <select
              value={filters.year}
              onChange={(e) => setFilters((f) => ({ ...f, year: e.target.value }))}
              className="rounded-xl border border-slate-200 bg-slate-50 py-2 pl-3 pr-10 text-sm font-semibold text-slate-800 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 min-w-[120px]"
            >
              {YEARS.map((y) => (
                <option key={y.value} value={y.value}>
                  {y.label}
                </option>
              ))}
            </select>
          </div>

          <button
            type="button"
            className="self-end rounded-xl border border-slate-200 bg-slate-100 p-2 text-slate-500 transition hover:text-indigo-700"
          >
            <Filter className="h-5 w-5" />
          </button>
        </div>
      </div>

      {/* Data Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
        <div className="overflow-x-auto">
          <table className="w-full border-collapse text-left">
            <thead>
              <tr className="bg-slate-50/50">
                <th className="px-6 py-4 text-[11px] font-extrabold uppercase tracking-widest text-slate-400">
                  Mã NV
                </th>
                <th className="px-6 py-4 text-[11px] font-extrabold uppercase tracking-widest text-slate-400">
                  Tên nhân viên
                </th>
                <th className="px-6 py-4 text-[11px] font-extrabold uppercase tracking-widest text-slate-400">
                  Phòng ban
                </th>
                <th className="px-6 py-4 text-right text-[11px] font-extrabold uppercase tracking-widest text-slate-400">
                  Ngày công
                </th>
                <th className="px-6 py-4 text-right text-[11px] font-extrabold uppercase tracking-widest text-slate-400">
                  OT (giờ)
                </th>
                <th className="px-6 py-4 text-right text-[11px] font-extrabold uppercase tracking-widest text-slate-400">
                  NP không lương
                </th>
                <th className="px-6 py-4 text-right text-[11px] font-extrabold uppercase tracking-widest text-slate-400">
                  NP có lương
                </th>
                <th className="px-6 py-4 text-[11px] font-extrabold uppercase tracking-widest text-slate-400">
                  Trạng thái
                </th>
                <th className="px-6 py-4" />
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {summaryQuery.isLoading ? (
                Array.from({ length: 4 }).map((_, i) => (
                  <tr key={i} className="animate-pulse bg-slate-50/20">
                    <td className="px-6 py-4">
                      <div className="h-4 w-16 rounded bg-slate-200" />
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-3">
                        <div className="h-8 w-8 rounded-full bg-slate-200" />
                        <div className="h-4 w-32 rounded bg-slate-200" />
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="h-4 w-24 rounded bg-slate-200" />
                    </td>
                    <td className="px-6 py-4">
                      <div className="ml-auto h-4 w-12 rounded bg-slate-200" />
                    </td>
                    <td className="px-6 py-4">
                      <div className="ml-auto h-4 w-12 rounded bg-slate-200" />
                    </td>
                    <td className="px-6 py-4">
                      <div className="ml-auto h-4 w-12 rounded bg-slate-200" />
                    </td>
                    <td className="px-6 py-4">
                      <div className="ml-auto h-4 w-12 rounded bg-slate-200" />
                    </td>
                    <td className="px-6 py-4">
                      <div className="h-6 w-24 rounded-full bg-slate-200" />
                    </td>
                    <td className="px-6 py-4" />
                  </tr>
                ))
              ) : rows.length ? (
                rows.map((item, index) => {
                  const name = textValue(item, ["employee.full_name", "employee_name", "full_name", "name"], "Nhân viên");
                  const empCode = textValue(item, ["employee.employee_code", "employee_code"], "N/A");
                  const department = textValue(item, ["department.name", "department", "department_name"], "N/A");
                  const attendanceDays = formatNumber(numberValue(item, ["attendance_days", "present_days", "workdays"], 0));
                  const totalDays = formatNumber(numberValue(item, ["total_working_days", "working_days"], 22));
                  const otHours = formatNumber(numberValue(item, ["overtime_hours", "ot_hours", "late_minutes"], 0));
                  const unpaidLeave = formatNumber(numberValue(item, ["unpaid_leave_days", "absent_days", "absence_days"], 0));
                  const paidLeave = formatNumber(numberValue(item, ["paid_leave_days", "leave_days"], 0));
                  const status = textValue(item, ["status", "final_status"], "pending");
                  const initials = getInitials(name);
                  const colorClass = avatarColor(name);
                  const hasAnomaly =
                    status.toLowerCase().includes("anomaly") ||
                    status.toLowerCase().includes("review") ||
                    status.toLowerCase().includes("manual");

                  return (
                    <tr
                      key={`${textValue(item, ["id"], String(index))}-${index}`}
                      className={`group transition-colors hover:bg-slate-50 ${hasAnomaly ? "border-l-4 border-rose-400" : ""}`}
                    >
                      <td className="px-6 py-4 font-mono text-sm font-medium text-slate-500 tabular-nums">
                        {empCode}
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-3">
                          <div className={`flex h-8 w-8 items-center justify-center rounded-full text-[10px] font-bold ${colorClass}`}>
                            {initials}
                          </div>
                          <span className="text-sm font-bold text-slate-900">{name}</span>
                        </div>
                      </td>
                      <td className="px-6 py-4 text-sm text-slate-500">{department}</td>
                      <td className="px-6 py-4 text-right text-sm tabular-nums text-slate-800">
                        {attendanceDays} / {totalDays}
                      </td>
                      <td className="px-6 py-4 text-right text-sm tabular-nums text-slate-800">
                        {otHours}h
                      </td>
                      <td className="px-6 py-4 text-right text-sm tabular-nums text-slate-800">
                        {unpaidLeave}
                      </td>
                      <td className="px-6 py-4 text-right text-sm tabular-nums text-slate-800">
                        {paidLeave}
                      </td>
                      <td className="px-6 py-4">{getStatusBadge(status)}</td>
                      <td className="px-6 py-4 text-right">
                        <button className="rounded-lg p-1.5 text-slate-400 opacity-0 transition-all hover:bg-white group-hover:opacity-100">
                          <MoreVertical className="h-4 w-4" />
                        </button>
                      </td>
                    </tr>
                  );
                })
              ) : (
                <tr>
                  <td colSpan={9} className="py-10">
                    <EmptyState
                      title="Không có dữ liệu chi tiết"
                      description="Backend có thể mới chỉ trả object summary cho kỳ này."
                    />
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        <div className="flex items-center justify-between border-t border-slate-100 bg-white px-6 py-4">
          <p className="text-xs font-semibold text-slate-400">
            Hiển thị{" "}
            <span className="text-slate-800">1 - {rows.length}</span> trong{" "}
            <span className="text-slate-800">{rows.length}</span> nhân viên
          </p>
          <div className="flex items-center gap-2">
            <button
              disabled
              className="rounded-xl border border-slate-200 p-1.5 text-slate-400 disabled:opacity-30"
            >
              <ChevronLeft className="h-4 w-4" />
            </button>
            <button className="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-700 text-xs font-bold text-white shadow-sm">
              1
            </button>
            <button
              disabled
              className="rounded-xl border border-slate-200 p-1.5 text-slate-400 disabled:opacity-30"
            >
              <ChevronRight className="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>

      {/* Summary Metric Cards */}
      <div className="grid grid-cols-1 gap-6 md:grid-cols-4">
        <div className="rounded-2xl border border-white/70 bg-white/80 p-6 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur border-l-4 border-l-indigo-600">
          <p className="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-2">
            Tổng nhân viên
          </p>
          <div className="flex items-end gap-2">
            <span className="text-2xl font-black text-slate-900">{totalEmployees}</span>
            <Badge tone="info" className="mb-1">Kỳ hiện tại</Badge>
          </div>
        </div>

        <div className="rounded-2xl border border-white/70 bg-white/80 p-6 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur border-l-4 border-l-emerald-500">
          <p className="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-2">
            Sẵn sàng chạy lương
          </p>
          <div className="flex items-end gap-2">
            <span className="text-2xl font-black text-slate-900">{readyCount}</span>
            <span className="mb-1 text-[10px] font-bold text-slate-400">{attendanceRate}</span>
          </div>
        </div>

        <div className="rounded-2xl border border-white/70 bg-white/80 p-6 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur border-l-4 border-l-rose-500">
          <p className="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-2">
            Cần xét duyệt
          </p>
          <div className="flex items-end gap-2">
            <span className="text-2xl font-black text-slate-900">{pendingCount}</span>
            <span className="mb-1 text-[10px] font-bold text-rose-500">
              <AlertTriangle className="inline h-3 w-3" /> Cần xử lý
            </span>
          </div>
        </div>

        <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-950 to-indigo-700 p-6 shadow-lg">
          <div className="absolute -bottom-4 -right-4 opacity-10">
            <RefreshCw className="h-24 w-24 text-white" />
          </div>
          <p className="text-[10px] font-extrabold uppercase tracking-widest text-indigo-300 mb-2">
            Kỳ lương tiếp theo
          </p>
          <span className="text-2xl font-black text-white">
            {formatDate(`${filters.year}-${String(Number(filters.month) + 1).padStart(2, "0")}-01`)}
          </span>
        </div>
      </div>
    </div>
  );
}
