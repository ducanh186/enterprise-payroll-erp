import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import {
  AlertTriangle,
  CalendarDays,
  ChevronDown,
  Clock,
  MoreHorizontal,
  TrendingUp,
  Users,
} from "lucide-react";
import { apiGet } from "../lib/api";
import { formatDate, formatNumber } from "../lib/format";
import { boolValue, numberValue, textValue, toArray } from "../lib/records";
import { Badge, EmptyState, PageHeader } from "../components/ui";

function todayISO() {
  return new Date().toISOString().slice(0, 10);
}

function extractSummary(source: unknown) {
  if (Array.isArray(source)) return source[0] ?? {};
  if (source && typeof source === "object") {
    const record = source as Record<string, unknown>;
    if (record.summary && typeof record.summary === "object") return record.summary;
    return source;
  }
  return {};
}

/** Format an ISO date string as "DD Tháng MM, YYYY" */
function formatViDate(iso: string): string {
  const d = new Date(iso);
  if (isNaN(d.getTime())) return iso;
  return d.toLocaleDateString("vi-VN", {
    day: "2-digit",
    month: "long",
    year: "numeric",
  });
}

/** Return Vietnamese day-of-week name for an ISO date */
function viDayOfWeek(iso: string): string {
  const d = new Date(iso);
  if (isNaN(d.getTime())) return "";
  return d.toLocaleDateString("vi-VN", { weekday: "long" });
}

/** Build a calendar grid for year/month (1-indexed). Returns rows of day-cells. */
function buildCalendar(year: number, month: number) {
  const firstDay = new Date(year, month - 1, 1);
  const lastDay = new Date(year, month, 0);
  // Monday-first: 0=Mon … 6=Sun
  const startOffset = (firstDay.getDay() + 6) % 7;
  const totalDays = lastDay.getDate();

  type Cell = { day: number; current: boolean } | null;
  const cells: Cell[] = [];

  for (let i = 0; i < startOffset; i++) cells.push(null);
  for (let d = 1; d <= totalDays; d++) cells.push({ day: d, current: true });
  // pad to full weeks
  while (cells.length % 7 !== 0) cells.push(null);

  const rows: Cell[][] = [];
  for (let i = 0; i < cells.length; i += 7) rows.push(cells.slice(i, i + 7));
  return rows;
}

export default function AttendancePage() {
  const date = todayISO();
  const todayDay = new Date(date).getDate();
  const [selectedDay, setSelectedDay] = useState<number>(todayDay);

  const currentYear = Number(date.slice(0, 4));
  const currentMonth = Number(date.slice(5, 7));
  const [viewYear, setViewYear] = useState(currentYear);
  const [viewMonth, setViewMonth] = useState(currentMonth);

  const dailyQuery = useQuery({
    queryKey: ["attendance", "daily", date],
    queryFn: async () => apiGet<unknown>("/attendance/daily", { date }),
  });

  const summaryQuery = useQuery({
    queryKey: ["attendance", "monthly-summary", date.slice(0, 7)],
    queryFn: async () =>
      apiGet<unknown>("/attendance/monthly-summary", {
        month: Number(date.slice(5, 7)),
        year: Number(date.slice(0, 4)),
      }),
  });

  const requestsQuery = useQuery({
    queryKey: ["attendance", "requests"],
    queryFn: async () => apiGet<unknown>("/attendance/requests", { page: 1, per_page: 5 }),
  });

  const dailyItems = useMemo(
    () => toArray<Record<string, unknown>>(dailyQuery.data?.data),
    [dailyQuery.data?.data],
  );
  const requestItems = useMemo(
    () => toArray<Record<string, unknown>>(requestsQuery.data?.data),
    [requestsQuery.data?.data],
  );
  const summarySource = useMemo(
    () => extractSummary(summaryQuery.data?.data),
    [summaryQuery.data?.data],
  );

  const totalHours = numberValue(summarySource, ["total_hours", "work_hours"], 0);
  const attendanceRate = numberValue(
    summarySource,
    ["average_attendance_rate", "attendance_rate"],
    0,
  );
  const leaveBalance = numberValue(
    summarySource,
    ["leave_balance", "total_leave_days", "leave_days"],
    0,
  );

  const calendarRows = buildCalendar(viewYear, viewMonth);
  const monthLabel = new Date(viewYear, viewMonth - 1, 1).toLocaleDateString("vi-VN", {
    month: "long",
    year: "numeric",
  });

  // Build a set of days that have attendance data (from dailyItems)
  const presentDays = useMemo(() => {
    const s = new Set<number>();
    dailyItems.forEach((item) => {
      const t = textValue(item, ["check_time", "checkin_time", "time"], "");
      if (t) {
        // If the date matches current month, mark the day
        const d = new Date(t);
        if (!isNaN(d.getTime()) && d.getMonth() + 1 === viewMonth && d.getFullYear() === viewYear) {
          s.add(d.getDate());
        }
      }
    });
    return s;
  }, [dailyItems, viewMonth, viewYear]);

  const lateDays = useMemo(() => {
    const s = new Set<number>();
    dailyItems.forEach((item) => {
      const late = numberValue(item, ["late_minutes", "lateMinutes"], 0);
      const t = textValue(item, ["check_time", "checkin_time", "time"], "");
      if (late > 0 && t) {
        const d = new Date(t);
        if (!isNaN(d.getTime()) && d.getMonth() + 1 === viewMonth && d.getFullYear() === viewYear) {
          s.add(d.getDate());
        }
      }
    });
    return s;
  }, [dailyItems, viewMonth, viewYear]);

  // Items for the selected day
  const selectedDayISO = `${viewYear}-${String(viewMonth).padStart(2, "0")}-${String(selectedDay).padStart(2, "0")}`;
  const selectedDayItems = useMemo(() => {
    return dailyItems.filter((item) => {
      const t = textValue(item, ["check_time", "checkin_time", "time"], "");
      if (!t) return false;
      return t.startsWith(selectedDayISO);
    });
  }, [dailyItems, selectedDayISO]);

  const pendingCount = requestItems.filter((item) =>
    textValue(item, ["status"], "").toLowerCase().includes("pending"),
  ).length;

  const prevMonth = () => {
    if (viewMonth === 1) { setViewMonth(12); setViewYear((y) => y - 1); }
    else setViewMonth((m) => m - 1);
  };
  const nextMonth = () => {
    if (viewMonth === 12) { setViewMonth(1); setViewYear((y) => y + 1); }
    else setViewMonth((m) => m + 1);
  };

  const DAY_LABELS = ["T2", "T3", "T4", "T5", "T6", "T7", "CN"];

  return (
    <div className="space-y-8">
      <PageHeader
        eyebrow="Chấm công"
        title="Nhật ký chấm công"
        description="Xem log theo ngày, lịch tháng và lịch sử giao dịch chấm công."
        actions={<Badge tone="neutral">Hôm nay: {formatDate(date)}</Badge>}
      />

      {/* Summary Bar — 3 metric cards with left accent border */}
      <section className="grid grid-cols-1 gap-6 md:grid-cols-3">
        {/* Total Hours */}
        <div className="flex items-center justify-between rounded-xl border-l-4 border-slate-950 bg-white p-6 shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
          <div>
            <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">
              Tổng giờ làm ({new Date(date).toLocaleDateString("vi-VN", { month: "short" })})
            </p>
            <p className="font-[family-name:var(--font-display)] text-3xl font-black tabular-nums text-slate-950">
              {totalHours > 0 ? totalHours.toFixed(1) : formatNumber(dailyItems.length * 8)}
            </p>
          </div>
          <div className="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
            <Clock className="h-5 w-5 text-slate-700" />
          </div>
        </div>

        {/* Attendance Rate */}
        <div className="flex items-center justify-between rounded-xl border-l-4 border-emerald-600 bg-white p-6 shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
          <div>
            <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">
              Tỷ lệ đúng giờ
            </p>
            <p className="font-[family-name:var(--font-display)] text-3xl font-black tabular-nums text-emerald-600">
              {attendanceRate > 0 ? `${attendanceRate.toFixed(1)}%` : "—"}
            </p>
          </div>
          <div className="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50">
            <TrendingUp className="h-5 w-5 text-emerald-600" />
          </div>
        </div>

        {/* Leave Balance */}
        <div className="flex items-center justify-between rounded-xl border-l-4 border-sky-500 bg-white p-6 shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
          <div>
            <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">
              Số ngày phép còn
            </p>
            <p className="font-[family-name:var(--font-display)] text-3xl font-black tabular-nums text-sky-600">
              {leaveBalance > 0 ? (
                <>
                  {leaveBalance}{" "}
                  <span className="text-sm font-normal text-slate-500">Ngày</span>
                </>
              ) : (
                "—"
              )}
            </p>
          </div>
          <div className="flex h-12 w-12 items-center justify-center rounded-full bg-sky-50">
            <CalendarDays className="h-5 w-5 text-sky-600" />
          </div>
        </div>
      </section>

      {/* Main Interactive Area: Calendar + Detail */}
      <div className="grid grid-cols-1 gap-8 xl:grid-cols-12">
        {/* Calendar Panel — left */}
        <div className="xl:col-span-7 space-y-6 rounded-xl bg-slate-50 p-6 shadow-[0_18px_40px_rgba(15,23,42,0.04)]">
          {/* Calendar header */}
          <div className="flex items-center justify-between">
            <h3 className="text-xl font-bold tracking-tight text-slate-900">
              Lịch chấm công
            </h3>
            <div className="flex items-center gap-2">
              <div className="relative">
                <select
                  value={`${viewYear}-${viewMonth}`}
                  onChange={(e) => {
                    const [y, m] = e.target.value.split("-").map(Number);
                    setViewYear(y);
                    setViewMonth(m);
                  }}
                  className="appearance-none rounded-lg border-none bg-white py-2 pl-4 pr-9 text-sm font-bold shadow-sm focus:ring-2 focus:ring-slate-950"
                >
                  {Array.from({ length: 12 }, (_, i) => {
                    const y = currentYear;
                    const m = i + 1;
                    const label = new Date(y, m - 1, 1).toLocaleDateString("vi-VN", {
                      month: "long",
                      year: "numeric",
                    });
                    return (
                      <option key={`${y}-${m}`} value={`${y}-${m}`}>
                        {label}
                      </option>
                    );
                  })}
                </select>
                <ChevronDown className="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
              </div>
              <button
                onClick={prevMonth}
                className="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-200"
                aria-label="Tháng trước"
              >
                &#8249;
              </button>
              <button
                onClick={nextMonth}
                className="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-200"
                aria-label="Tháng sau"
              >
                &#8250;
              </button>
            </div>
          </div>

          {/* Day-of-week headers */}
          <div className="grid grid-cols-7 gap-2">
            {DAY_LABELS.map((d) => (
              <div
                key={d}
                className="py-2 text-center text-[10px] font-black uppercase tracking-wide text-slate-500"
              >
                {d}
              </div>
            ))}

            {/* Calendar cells */}
            {calendarRows.flat().map((cell, idx) => {
              if (!cell) {
                return (
                  <div
                    key={`empty-${idx}`}
                    className="h-16 rounded-lg bg-slate-100/40 p-1.5 text-xs font-bold text-slate-400/50 sm:h-20"
                  />
                );
              }
              const { day } = cell;
              const isToday =
                day === todayDay && viewMonth === currentMonth && viewYear === currentYear;
              const isSelected = day === selectedDay;
              const hasPresent = presentDays.has(day);
              const hasLate = lateDays.has(day);
              const isWeekend = (() => {
                const dow = new Date(viewYear, viewMonth - 1, day).getDay();
                return dow === 0 || dow === 6;
              })();

              return (
                <button
                  key={day}
                  onClick={() => setSelectedDay(day)}
                  className={[
                    "h-16 rounded-lg p-1.5 text-left transition-all sm:h-20",
                    isSelected
                      ? "border-2 border-slate-950 bg-white"
                      : isWeekend
                      ? "bg-slate-200/40"
                      : "bg-white hover:bg-slate-50",
                  ].join(" ")}
                >
                  <span
                    className={[
                      "text-xs font-bold",
                      isSelected ? "text-slate-950" : isToday ? "text-sky-600" : "text-slate-700",
                    ].join(" ")}
                  >
                    {day}
                  </span>
                  <div className="mt-auto flex gap-1 pt-1">
                    {hasPresent && (
                      <div className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                    )}
                    {hasLate && (
                      <div className="h-1.5 w-1.5 rounded-full bg-rose-500" />
                    )}
                    {isToday && !hasPresent && (
                      <div className="h-1.5 w-1.5 rounded-full bg-sky-500" />
                    )}
                  </div>
                </button>
              );
            })}
          </div>

          {/* Legend */}
          <div className="flex gap-6 border-t border-slate-200/60 pt-4">
            <div className="flex items-center gap-2">
              <div className="h-2 w-2 rounded-full bg-emerald-500" />
              <span className="text-[10px] font-bold uppercase tracking-tight text-slate-600">
                Có mặt
              </span>
            </div>
            <div className="flex items-center gap-2">
              <div className="h-2 w-2 rounded-full bg-rose-500" />
              <span className="text-[10px] font-bold uppercase tracking-tight text-slate-600">
                Đi trễ
              </span>
            </div>
            <div className="flex items-center gap-2">
              <div className="h-2 w-2 rounded-full bg-sky-500" />
              <span className="text-[10px] font-bold uppercase tracking-tight text-slate-600">
                Hôm nay
              </span>
            </div>
          </div>
        </div>

        {/* Detail Panels — right */}
        <div className="xl:col-span-5 space-y-6">
          {/* Selected day detail */}
          <div className="rounded-xl bg-white p-6 shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
            <div className="mb-6 flex items-start justify-between">
              <div>
                <h4 className="font-[family-name:var(--font-display)] text-2xl font-black tracking-tighter text-slate-900">
                  {formatViDate(selectedDayISO)}
                </h4>
                <p className="text-sm font-medium capitalize text-slate-500">
                  {viDayOfWeek(selectedDayISO)}
                </p>
              </div>
              <button className="rounded-lg bg-slate-950 px-4 py-2 text-xs font-bold text-white transition-opacity hover:opacity-90">
                Yêu cầu điều chỉnh
              </button>
            </div>

            {dailyQuery.isLoading ? (
              <p className="text-sm text-slate-500">Đang tải...</p>
            ) : selectedDayItems.length > 0 ? (
              <div className="relative space-y-8">
                <div className="absolute left-[23px] top-2 bottom-2 w-0.5 bg-slate-100" />
                {selectedDayItems.slice(0, 4).map((item, index) => {
                  const checkType = textValue(item, ["check_type", "type"], "in").toLowerCase();
                  const isIn = checkType !== "out";
                  const time = textValue(item, ["check_time", "checkin_time", "time"], "—");
                  const valid = boolValue(item, ["is_valid", "valid"], true);
                  return (
                    <div key={index} className="relative pl-14">
                      <div
                        className={[
                          "absolute left-0 top-0 z-10 flex h-12 w-12 items-center justify-center rounded-full border-2 bg-white",
                          isIn ? "border-slate-950 text-slate-950" : "border-slate-300 text-slate-500",
                        ].join(" ")}
                      >
                        <Clock className="h-5 w-5" />
                      </div>
                      <div className="flex items-start justify-between">
                        <div>
                          <p className="font-[family-name:var(--font-display)] text-lg font-black tabular-nums text-slate-900">
                            {time}
                          </p>
                          <p className="text-sm text-slate-500">
                            {isIn ? "Giờ vào" : "Giờ ra"} —{" "}
                            {textValue(item, ["employee.full_name", "employee_name", "full_name"], "Nhân viên")}
                          </p>
                        </div>
                        <Badge tone={valid ? "success" : "danger"}>
                          {valid ? "Hợp lệ" : "Cần kiểm tra"}
                        </Badge>
                      </div>
                    </div>
                  );
                })}
              </div>
            ) : (
              <div className="py-6 text-center">
                <Clock className="mx-auto mb-3 h-10 w-10 text-slate-300" />
                <p className="text-sm font-medium text-slate-500">
                  Chưa có dữ liệu chấm công cho ngày này
                </p>
              </div>
            )}

            {/* Daily net duration placeholder */}
            <div className="mt-8 rounded-lg bg-slate-50 p-4 border-t border-slate-100 flex items-center justify-between">
              <span className="text-xs font-bold uppercase tracking-wide text-slate-500">
                Tổng giờ trong ngày
              </span>
              <span className="font-[family-name:var(--font-display)] text-lg font-black tabular-nums text-slate-950">
                {selectedDayItems.length > 0 ? "—" : "0h 00m"}
              </span>
            </div>
          </div>

          {/* Revision CTA card */}
          <div className="relative overflow-hidden rounded-xl bg-gradient-to-br from-slate-800 to-indigo-900 p-6 text-white">
            <div className="relative z-10">
              <h5 className="mb-2 font-bold text-white">Cần điều chỉnh?</h5>
              <p className="mb-4 text-sm opacity-80">
                Quên chấm ra hôm qua? Gửi yêu cầu điều chỉnh chấm công cho quản lý.
              </p>
              <a
                href="#"
                className="inline-flex items-center gap-2 text-xs font-black uppercase text-emerald-300 hover:underline"
              >
                Tạo yêu cầu
                <AlertTriangle className="h-4 w-4" />
              </a>
            </div>
            <Users className="pointer-events-none absolute -bottom-4 -right-4 h-24 w-24 text-white/5" />
          </div>

          {/* Monthly summary mini-grid */}
          <div className="rounded-xl bg-white p-6 shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
            <h4 className="mb-4 text-base font-semibold tracking-tight text-slate-900">
              Tổng kết tháng
            </h4>
            <p className="mb-4 text-sm text-slate-500">
              {monthLabel} — nguồn từ GET /attendance/monthly-summary
            </p>
            <div className="grid grid-cols-2 gap-3">
              {[
                [
                  "Tỷ lệ chuyên cần",
                  numberValue(summarySource, ["average_attendance_rate", "attendance_rate"], 0),
                  "rate",
                ],
                [
                  "Ngày nghỉ phép",
                  numberValue(summarySource, ["total_leave_days", "leave_days"], 0),
                  "count",
                ],
                [
                  "Số lần đi trễ",
                  numberValue(summarySource, ["total_late_instances", "late_instances"], 0),
                  "count",
                ],
                [
                  "Về sớm",
                  numberValue(
                    summarySource,
                    ["total_early_leave_instances", "early_leave_instances"],
                    0,
                  ),
                  "count",
                ],
              ].map(([label, rawValue, kind]) => (
                <div
                  key={String(label)}
                  className="rounded-xl border border-slate-200 bg-slate-50/80 p-4"
                >
                  <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                    {label as string}
                  </p>
                  <p className="mt-2 font-[family-name:var(--font-display)] text-2xl font-bold text-slate-950">
                    {kind === "rate"
                      ? `${Number(rawValue).toFixed(1)}%`
                      : formatNumber(rawValue)}
                  </p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Activity Stream Table */}
      <section className="space-y-5">
        <div className="flex items-end justify-between">
          <div>
            <h3 className="font-[family-name:var(--font-display)] text-2xl font-black tracking-tighter text-slate-900">
              Nhật ký giao dịch
            </h3>
            <p className="text-sm text-slate-500">
              Lịch sử chấm công chi tiết trong tháng hiện tại
            </p>
          </div>
          <button className="border-b-2 border-slate-900 pb-1 text-xs font-bold uppercase text-slate-900 transition-colors hover:border-slate-500 hover:text-slate-500">
            Xuất PDF
          </button>
        </div>

        <div className="overflow-hidden rounded-xl bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
          {dailyQuery.isLoading ? (
            <p className="p-6 text-sm text-slate-500">Đang tải nhật ký chấm công...</p>
          ) : dailyItems.length ? (
            <table className="w-full border-collapse text-left">
              <thead>
                <tr className="border-b border-slate-100 bg-slate-50">
                  {["Ngày", "Trạng thái", "Giờ vào", "Giờ ra", "Thời lượng", ""].map((h) => (
                    <th
                      key={h}
                      className={[
                        "px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500",
                        h === "" ? "text-right" : "",
                      ].join(" ")}
                    >
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {dailyItems.slice(0, 8).map((item, index) => {
                  const employeeName = textValue(
                    item,
                    ["employee.full_name", "employee_name", "full_name"],
                    "Nhân viên",
                  );
                  const checkTime = textValue(
                    item,
                    ["check_time", "checkin_time", "time"],
                    "—",
                  );
                  const checkType = textValue(item, ["check_type", "type"], "in").toLowerCase();
                  const valid = boolValue(item, ["is_valid", "valid"], true);
                  const lateMin = numberValue(item, ["late_minutes", "lateMinutes"], 0);
                  const statusTone = valid ? "success" : lateMin > 0 ? "warning" : "danger";
                  const statusLabel = valid ? "Xác nhận" : lateMin > 0 ? "Đi trễ" : "Cần kiểm tra";

                  return (
                    <tr
                      key={`${textValue(item, ["id"], String(index))}-${index}`}
                      className="transition-colors hover:bg-slate-50/50"
                    >
                      <td className="px-6 py-5 text-sm font-bold text-slate-800">
                        {formatDate(checkTime.slice(0, 10))}
                      </td>
                      <td className="px-6 py-5">
                        <Badge tone={statusTone}>{statusLabel}</Badge>
                      </td>
                      <td className="px-6 py-5 tabular-nums text-sm text-slate-700">
                        {checkType !== "out" ? checkTime : "—"}
                      </td>
                      <td className="px-6 py-5 tabular-nums text-sm text-slate-700">
                        {checkType === "out" ? checkTime : "—"}
                      </td>
                      <td className="px-6 py-5 text-sm font-semibold tabular-nums text-slate-800">
                        {employeeName}
                      </td>
                      <td className="px-6 py-5 text-right">
                        <button className="text-slate-400 transition-colors hover:text-slate-950">
                          <MoreHorizontal className="h-5 w-5" />
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          ) : (
            <div className="p-6">
              <EmptyState
                title="Chưa có log chấm công"
                description="Nếu backend đang ở mock mode thì dữ liệu sẽ xuất hiện khi service trả danh sách thật."
              />
            </div>
          )}
        </div>
      </section>

      {/* Recent Requests Section */}
      {requestItems.length > 0 && (
        <section className="space-y-5">
          <div className="flex items-end justify-between">
            <div>
              <h3 className="font-[family-name:var(--font-display)] text-2xl font-black tracking-tighter text-slate-900">
                Yêu cầu gần đây
              </h3>
              <p className="text-sm text-slate-500">
                Nghỉ phép, thiếu checkout, đi trễ — {pendingCount} đang chờ duyệt
              </p>
            </div>
          </div>

          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {requestItems.slice(0, 6).map((item, index) => {
              const statusRaw = textValue(item, ["status"], "pending").toLowerCase();
              const statusTone = statusRaw.includes("approve")
                ? "success"
                : statusRaw.includes("reject")
                ? "danger"
                : "warning";

              return (
                <div
                  key={`${textValue(item, ["id"], String(index))}-${index}`}
                  className="rounded-xl border border-slate-200/80 bg-white px-5 py-4 shadow-[0_4px_12px_rgba(15,23,42,0.04)]"
                >
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                      <p className="truncate font-medium text-slate-900">
                        {textValue(item, ["employee.full_name", "employee_name", "name"], "Yêu cầu")}
                      </p>
                      <p className="text-sm text-slate-500">
                        {textValue(item, ["request_type", "type"], "request")}
                        {" · "}
                        {textValue(item, ["request_date", "from_date", "date"], "N/A")}
                      </p>
                    </div>
                    <Badge tone={statusTone}>
                      {textValue(item, ["status"], "pending")}
                    </Badge>
                  </div>
                  <p className="mt-3 text-sm leading-6 text-slate-600">
                    {textValue(item, ["reason", "note", "description"], "Không có mô tả")}
                  </p>
                </div>
              );
            })}
          </div>
        </section>
      )}

      {requestsQuery.isSuccess && requestItems.length === 0 && (
        <section>
          <EmptyState
            title="Không có yêu cầu"
            description="Chưa có request chấm công nào trong gói dữ liệu hiện tại."
          />
        </section>
      )}
    </div>
  );
}
