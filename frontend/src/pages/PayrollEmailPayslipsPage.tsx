import { useState } from "react";
import { useMutation } from "@tanstack/react-query";
import { MailCheck, RefreshCcw, Send } from "lucide-react";
import DateInput from "../components/DateInput";
import { EmptyState, PageHeader, Panel } from "../components/ui";
import { apiPost, getApiErrorMessage } from "../lib/api";

export default function PayrollEmailPayslipsPage() {
  const [form, setForm] = useState({
    doc_date: "2026-05-05",
    employee_code: "",
    department_code: "",
    branch_code: "A01,A02",
  });
  const [result, setResult] = useState<Record<string, unknown> | null>(null);
  const [error, setError] = useState<string | null>(null);

  const sendMutation = useMutation({
    mutationFn: async () => apiPost<unknown>("/payroll/payslips/email", form),
    onSuccess: (response) => {
      setError(null);
      setResult({
        ...((response.data ?? {}) as Record<string, unknown>),
        message: response.success ? "Gửi email phiếu lương thành công" : response.message,
      });
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể gửi email phiếu lương."));
    },
  });

  return (
    <div className="space-y-8 pb-10">
      <PageHeader
        eyebrow="Tiền lương"
        title="Gửi email phiếu lương"
        actions={
          <button
            type="button"
            onClick={() => {
              setResult(null);
              setError(null);
            }}
            className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
          >
            <RefreshCcw className="h-4 w-4" />
            Làm mới
          </button>
        }
      />

      <div className="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <Panel title="Điều kiện gửi" subtitle="Bốn biến được phép nhập từ yêu cầu Fujimart">
          <div className="space-y-5">
            <div className="grid gap-4 sm:grid-cols-2">
              <label className="space-y-2">
                <span className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Ngày tính lương</span>
                <DateInput
                  value={form.doc_date}
                  onChange={(value) => setForm((current) => ({ ...current, doc_date: value }))}
                  className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                />
              </label>
              <label className="space-y-2">
                <span className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Mã nhân viên</span>
                <input
                  value={form.employee_code}
                  onChange={(event) => setForm((current) => ({ ...current, employee_code: event.target.value }))}
                  placeholder="Tất cả nếu để trống"
                  className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                />
              </label>
              <label className="space-y-2">
                <span className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Mã phòng ban</span>
                <input
                  value={form.department_code}
                  onChange={(event) => setForm((current) => ({ ...current, department_code: event.target.value }))}
                  placeholder="Tất cả nếu để trống"
                  className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                />
              </label>
              <label className="space-y-2">
                <span className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Mã chi nhánh</span>
                <input
                  value={form.branch_code}
                  onChange={(event) => setForm((current) => ({ ...current, branch_code: event.target.value }))}
                  className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                />
              </label>
            </div>

            {error && (
              <p className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {error}
              </p>
            )}

            <button
              type="button"
              onClick={() => sendMutation.mutate()}
              disabled={sendMutation.isPending}
              className="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {sendMutation.isPending ? <RefreshCcw className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
              Gửi email phiếu lương
            </button>
          </div>
        </Panel>

        <Panel title="Kết quả gửi email" subtitle="Thông báo thành công hoặc lỗi từ hệ thống">
          {result ? (
            <div className="space-y-4">
              <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                <div className="flex items-start gap-3">
                  <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                    <MailCheck className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-sm font-semibold text-emerald-900">
                      {String(result.message ?? "Đã nhận phản hồi từ backend")}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          ) : (
            <EmptyState
              title="Chưa có kết quả gửi"
              description="Bấm Gửi email phiếu lương để xem phản hồi."
            />
          )}
        </Panel>
      </div>
    </div>
  );
}
