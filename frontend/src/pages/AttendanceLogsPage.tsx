import { useMemo, useState } from "react";
import type { FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { RefreshCw, UserRoundSearch } from "lucide-react";
import { apiGet, apiPost, getApiErrorMessage } from "../lib/api";
import { formatDateTime } from "../lib/format";
import { boolValue, textValue, toArray } from "../lib/records";
import { Badge, EmptyState, Panel, PageHeader } from "../components/ui";

type LogFilters = {
  date_from: string;
  date_to: string;
  employee_id: string;
  machine_number: string;
  is_valid: string;
};

const DEFAULT_FILTERS: LogFilters = {
  date_from: "",
  date_to: "",
  employee_id: "",
  machine_number: "",
  is_valid: "",
};

export default function AttendanceLogsPage() {
  const queryClient = useQueryClient();
  const [filters, setFilters] = useState<LogFilters>(DEFAULT_FILTERS);
  const [manualForm, setManualForm] = useState({
    employee_id: "",
    check_time: new Date().toISOString().slice(0, 16),
    check_type: "in",
    reason: "",
  });
  const [error, setError] = useState<string | null>(null);

  const logsQuery = useQuery({
    queryKey: ["attendance", "checkin-logs", filters],
    queryFn: async () =>
      apiGet<unknown>("/attendance/checkin-logs", {
        ...Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== "")),
        page: 1,
        per_page: 20,
      }),
  });

  const logs = useMemo(() => toArray<Record<string, unknown>>(logsQuery.data?.data), [logsQuery.data?.data]);

  const manualMutation = useMutation({
    mutationFn: async () =>
      apiPost<unknown>("/attendance/checkin-logs/manual", {
        employee_id: Number(manualForm.employee_id),
        check_time: new Date(manualForm.check_time).toISOString(),
        check_type: manualForm.check_type,
        reason: manualForm.reason || undefined,
      }),
    onSuccess: async () => {
      setError(null);
      await queryClient.invalidateQueries({ queryKey: ["attendance", "checkin-logs"] });
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể tạo manual check-in."));
    },
  });

  function updateFilter<K extends keyof LogFilters>(key: K, value: string) {
    setFilters((current) => ({ ...current, [key]: value }));
  }

  function submitManual(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    manualMutation.mutate();
  }

  return (
    <div className="space-y-6">
      <PageHeader
        eyebrow="Attendance"
        title="Nhật ký check-in"
        description="Tra cứu log theo khoảng thời gian, machine number và trạng thái hợp lệ. Có form manual check-in để hỗ trợ nhập tay."
        actions={
          <button
            type="button"
            onClick={() => logsQuery.refetch()}
            className="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300"
          >
            <RefreshCw className="h-4 w-4" />
            Tải lại
          </button>
        }
      />

      <Panel title="Bộ lọc" subtitle="Khi backend hoàn thiện, các trường này vẫn bám đúng contract controller.">
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
          {[
            ["Từ ngày", "date_from"],
            ["Đến ngày", "date_to"],
            ["Employee ID", "employee_id"],
            ["Machine", "machine_number"],
            ["Trạng thái", "is_valid"],
          ].map(([label, key]) => (
            <label key={String(key)} className="space-y-2">
              <span className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{label}</span>
              <input
                value={filters[key as keyof LogFilters]}
                onChange={(event) => updateFilter(key as keyof LogFilters, event.target.value)}
                placeholder={String(label)}
                className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
              />
            </label>
          ))}
        </div>
      </Panel>

      <div className="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <Panel title="Kết quả" subtitle="GET /attendance/checkin-logs">
          {logsQuery.isLoading ? (
            <p className="text-sm text-slate-500">Đang tải log...</p>
          ) : logs.length ? (
            <div className="overflow-x-auto">
              <table className="min-w-full border-separate border-spacing-y-2">
                <thead>
                  <tr className="text-left text-xs uppercase tracking-[0.22em] text-slate-500">
                    <th className="px-3 py-2">Nhân viên</th>
                    <th className="px-3 py-2">Thời gian</th>
                    <th className="px-3 py-2">Loại</th>
                    <th className="px-3 py-2">Machine</th>
                    <th className="px-3 py-2">Trạng thái</th>
                  </tr>
                </thead>
                <tbody>
                  {logs.map((item, index) => (
                    <tr
                      key={`${textValue(item, ["id"], String(index))}-${index}`}
                      className="rounded-2xl bg-white shadow-[0_10px_24px_rgba(15,23,42,0.04)]"
                    >
                      <td className="px-3 py-4">
                        <p className="font-medium text-slate-900">
                          {textValue(item, ["employee.full_name", "employee_name", "full_name"], "Nhân viên")}
                        </p>
                        <p className="text-xs text-slate-500">{textValue(item, ["employee.employee_code", "employee_code"], "N/A")}</p>
                      </td>
                      <td className="px-3 py-4 text-sm text-slate-700">
                        {formatDateTime(textValue(item, ["check_time", "created_at", "time"], ""))}
                      </td>
                      <td className="px-3 py-4">
                        <Badge tone={textValue(item, ["check_type", "type"], "").toLowerCase() === "out" ? "neutral" : "accent"}>
                          {textValue(item, ["check_type", "type"], "in")}
                        </Badge>
                      </td>
                      <td className="px-3 py-4 text-sm text-slate-700">
                        {textValue(item, ["machine_number", "machine"], "N/A")}
                      </td>
                      <td className="px-3 py-4">
                        {boolValue(item, ["is_valid", "valid"], true) ? (
                          <Badge tone="success">Hợp lệ</Badge>
                        ) : (
                          <Badge tone="danger">Không hợp lệ</Badge>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <EmptyState
              title="Không có log"
              description="Không tìm thấy check-in nào với bộ lọc hiện tại."
              action={
                <button
                  type="button"
                  onClick={() => setFilters(DEFAULT_FILTERS)}
                  className="inline-flex items-center gap-2 rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white"
                >
                  Xóa bộ lọc
                </button>
              }
            />
          )}
        </Panel>

        <Panel title="Manual check-in" subtitle="POST /attendance/checkin-logs/manual">
          <form className="space-y-4" onSubmit={submitManual}>
            <label className="block space-y-2">
              <span className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Employee ID</span>
              <input
                required
                value={manualForm.employee_id}
                onChange={(event) => setManualForm((current) => ({ ...current, employee_id: event.target.value }))}
                className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
              />
            </label>
            <label className="block space-y-2">
              <span className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Check time</span>
              <input
                type="datetime-local"
                required
                value={manualForm.check_time}
                onChange={(event) => setManualForm((current) => ({ ...current, check_time: event.target.value }))}
                className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
              />
            </label>
            <label className="block space-y-2">
              <span className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Check type</span>
              <select
                value={manualForm.check_type}
                onChange={(event) => setManualForm((current) => ({ ...current, check_type: event.target.value }))}
                className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
              >
                <option value="in">in</option>
                <option value="out">out</option>
              </select>
            </label>
            <label className="block space-y-2">
              <span className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Reason</span>
              <textarea
                rows={4}
                value={manualForm.reason}
                onChange={(event) => setManualForm((current) => ({ ...current, reason: event.target.value }))}
                className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
              />
            </label>
            {error && <p className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{error}</p>}
            <button
              type="submit"
              disabled={manualMutation.isPending}
              className="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
            >
              <UserRoundSearch className="h-4 w-4" />
              {manualMutation.isPending ? "Đang lưu..." : "Tạo manual log"}
            </button>
          </form>
        </Panel>
      </div>
    </div>
  );
}
