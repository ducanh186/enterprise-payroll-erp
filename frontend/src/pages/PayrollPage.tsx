import { useMemo } from "react";
import { useNavigate } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { Play, Download, Receipt, ChevronLeft, ChevronRight, Lock, BarChart2, FileWarning } from "lucide-react";
import { apiGet } from "../lib/api";
import { formatCurrency, formatNumber } from "../lib/format";
import { numberValue, textValue, toArray } from "../lib/records";
import { EmptyState } from "../components/ui";

export default function PayrollPage() {
  const navigate = useNavigate();

  const periodsQuery = useQuery({
    queryKey: ["payroll", "periods"],
    queryFn: async () => apiGet<unknown>("/payroll/periods"),
  });

  const parametersQuery = useQuery({
    queryKey: ["payroll", "preview-parameters"],
    queryFn: async () => apiGet<unknown>("/payroll/runs/preview-parameters"),
  });

  const payslipsQuery = useQuery({
    queryKey: ["payroll", "payslips", "dashboard"],
    queryFn: async () => apiGet<unknown>("/payroll/payslips", { page: 1, per_page: 5 }),
  });

  const periods = useMemo(() => toArray<Record<string, unknown>>(periodsQuery.data?.data), [periodsQuery.data?.data]);
  const parameters = useMemo(() => toArray<Record<string, unknown>>(parametersQuery.data?.data), [parametersQuery.data?.data]);
  const payslips = useMemo(() => toArray<Record<string, unknown>>(payslipsQuery.data?.data), [payslipsQuery.data?.data]);
  const openPeriods = periods.filter((period) => !textValue(period, ["payroll_run.status", "status"], "").toLowerCase().includes("lock"));

  const now = new Date();
  const currentMonthLabel = now.toLocaleString("vi-VN", { month: "long", year: "numeric" });

  function getStatusBadge(status: string) {
    const s = status.toLowerCase();
    if (s.includes("lock")) {
      return (
        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800 uppercase tracking-tighter">
          <Lock className="h-2.5 w-2.5" />
          Locked
        </span>
      );
    }
    if (s.includes("final")) {
      return (
        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-tighter">
          Finalized
        </span>
      );
    }
    if (s.includes("preview") || s.includes("preview")) {
      return (
        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800 uppercase tracking-tighter">
          Generated
        </span>
      );
    }
    return (
      <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-600 uppercase tracking-tighter">
        {status || "Draft"}
      </span>
    );
  }

  return (
    <div className="space-y-8">
      {/* Header */}
      <header className="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <h1 className="text-3xl font-black text-slate-950 tracking-tight">Payroll Cycle Dashboard</h1>
          <p className="text-slate-500 font-medium mt-1">Monitoring the financial heartbeat of {currentMonthLabel}</p>
        </div>
        <div className="flex gap-3">
          <button className="flex items-center gap-2 px-5 py-2.5 bg-white text-slate-600 border border-slate-200 font-semibold rounded-lg hover:bg-slate-50 transition-colors text-sm shadow-sm">
            <Download className="h-4 w-4" />
            Export CSV
          </button>
          <button
            onClick={() => navigate("/payroll/run")}
            className="flex items-center gap-2 px-5 py-2.5 bg-gradient-to-br from-slate-950 to-indigo-700 text-white font-bold rounded-lg shadow-lg hover:opacity-90 transition-opacity text-sm"
          >
            <Play className="h-4 w-4" />
            Start New Run
          </button>
        </div>
      </header>

      {/* Stats Grid */}
      <section className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {/* Stat 1: Total Periods */}
        <div className="bg-white p-6 rounded-xl border-l-4 border-indigo-700 shadow-sm">
          <div className="flex items-center justify-between mb-4">
            <span className="text-[10px] uppercase tracking-widest font-bold text-slate-400">Total Periods</span>
            <div className="p-2 bg-indigo-50 text-indigo-700 rounded-lg">
              <Receipt className="h-5 w-5" />
            </div>
          </div>
          <div className="flex items-baseline gap-2">
            <span className="text-3xl font-black tracking-tighter tabular-nums">{formatNumber(periods.length)}</span>
            <span className="text-xs font-bold text-emerald-600">Kỳ lương</span>
          </div>
          <p className="text-xs text-slate-500 mt-2 font-medium">Loaded from /payroll/periods</p>
        </div>

        {/* Stat 2: Cycle Status */}
        <div className="bg-white p-6 rounded-xl border-l-4 border-emerald-600 shadow-sm">
          <div className="flex items-center justify-between mb-4">
            <span className="text-[10px] uppercase tracking-widest font-bold text-slate-400">Cycle Status</span>
            <div className="p-2 bg-emerald-50 text-emerald-700 rounded-lg">
              <BarChart2 className="h-5 w-5" />
            </div>
          </div>
          <div className="flex items-baseline gap-2">
            <span className="text-2xl font-black tracking-tighter">
              {openPeriods.length > 0 ? `Open — ${currentMonthLabel}` : "Đang cập nhật"}
            </span>
          </div>
          <div className="mt-4 w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
            <div
              className="bg-emerald-500 h-full transition-all"
              style={{ width: `${periods.length ? Math.round((openPeriods.length / periods.length) * 100) : 0}%` }}
            />
          </div>
          <p className="text-xs text-slate-500 mt-2 font-medium">{openPeriods.length} run đang mở</p>
        </div>

        {/* Stat 3: Payslips */}
        <div className="bg-white p-6 rounded-xl border-l-4 border-sky-700 shadow-sm">
          <div className="flex items-center justify-between mb-4">
            <span className="text-[10px] uppercase tracking-widest font-bold text-slate-400">Preview Params</span>
            <div className="p-2 bg-sky-50 text-sky-700 rounded-lg">
              <FileWarning className="h-5 w-5" />
            </div>
          </div>
          <div className="flex items-baseline gap-2">
            <span className="text-3xl font-black tracking-tighter tabular-nums">{formatNumber(parameters.length)}</span>
          </div>
          <p className="text-xs text-slate-500 mt-2 font-medium">Tham số từ preview-parameters</p>
        </div>
      </section>

      {/* Main Workspace */}
      <div className="flex flex-col lg:flex-row gap-8">
        {/* Left: Active Runs Table */}
        <div className="flex-1 space-y-6">
          <div className="bg-white rounded-xl shadow-sm overflow-hidden border border-slate-200/60">
            <div className="p-6 flex items-center justify-between border-b border-slate-100">
              <h3 className="font-bold text-slate-900">Active Payroll Runs</h3>
              <div className="flex items-center gap-2">
                <select className="pl-3 pr-8 py-1.5 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg focus:ring-0 appearance-none text-slate-600">
                  <option>All Statuses</option>
                  <option>Draft</option>
                  <option>Finalized</option>
                  <option>Locked</option>
                </select>
              </div>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead className="bg-slate-50">
                  <tr>
                    <th className="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Run Reference</th>
                    <th className="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Period</th>
                    <th className="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status</th>
                    <th className="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-right">Total Net</th>
                    <th className="px-6 py-4 text-center" />
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {periodsQuery.isLoading ? (
                    Array.from({ length: 3 }).map((_, i) => (
                      <tr key={i} className="animate-pulse">
                        <td className="px-6 py-5">
                          <div className="h-4 bg-slate-100 rounded w-32 mb-2" />
                          <div className="h-3 bg-slate-100 rounded w-20" />
                        </td>
                        <td className="px-6 py-5"><div className="h-4 bg-slate-100 rounded w-24" /></td>
                        <td className="px-6 py-5"><div className="h-6 bg-slate-100 rounded-full w-20" /></td>
                        <td className="px-6 py-5"><div className="h-4 bg-slate-100 rounded w-24 ml-auto" /></td>
                        <td className="px-6 py-5" />
                      </tr>
                    ))
                  ) : periods.length ? (
                    periods.slice(0, 8).map((period, index) => {
                      const status = textValue(period, ["payroll_run.status", "status"], "draft");
                      const netTotal = numberValue(period, ["payroll_run.total_net_salary", "total_net_salary", "net_salary"], 0);
                      const periodCode = textValue(period, ["period_code", "label", "name"], `KL-${index + 1}`);
                      const month = textValue(period, ["month", "period_month"], "N/A");
                      const year = textValue(period, ["year", "period_year"], "N/A");
                      return (
                        <tr key={`${periodCode}-${index}`} className="hover:bg-slate-50 transition-colors group">
                          <td className="px-6 py-5">
                            <div className="flex flex-col">
                              <span className="font-bold text-indigo-700">{periodCode}</span>
                              <span className="text-xs text-slate-500">
                                {textValue(period, ["payroll_run.scope_type", "scope_type"], "All Staff")}
                              </span>
                            </div>
                          </td>
                          <td className="px-6 py-5 text-sm font-medium text-slate-700">
                            Tháng {month}/{year}
                          </td>
                          <td className="px-6 py-5">{getStatusBadge(status)}</td>
                          <td className="px-6 py-5 text-right font-bold tabular-nums text-slate-600 text-sm">
                            {netTotal ? formatCurrency(netTotal) : "—"}
                          </td>
                          <td className="px-6 py-5 text-center">
                            <button className="p-2 opacity-0 group-hover:opacity-100 transition-opacity text-slate-400 hover:text-slate-700">
                              <Receipt className="h-4 w-4" />
                            </button>
                          </td>
                        </tr>
                      );
                    })
                  ) : (
                    <tr>
                      <td colSpan={5} className="px-6 py-10">
                        <EmptyState
                          title="No payroll runs found"
                          description="Adjust your filters or initiate a new payroll run for this period."
                          action={
                            <button
                              onClick={() => navigate("/payroll/run")}
                              className="px-6 py-2 bg-indigo-700 text-white rounded-lg font-bold text-sm hover:bg-indigo-800 transition-colors"
                            >
                              Create First Run
                            </button>
                          }
                        />
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>

            {/* Pagination */}
            <div className="p-4 bg-slate-50/50 flex items-center justify-between border-t border-slate-100">
              <span className="text-xs text-slate-500 font-medium">
                Showing {Math.min(periods.length, 8)} of {periods.length} runs
              </span>
              <div className="flex gap-2">
                <button disabled className="p-1 hover:bg-slate-100 rounded disabled:opacity-30">
                  <ChevronLeft className="h-5 w-5 text-slate-500" />
                </button>
                <button className="p-1 hover:bg-slate-100 rounded">
                  <ChevronRight className="h-5 w-5 text-slate-500" />
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Right Sidebar */}
        <div className="w-full lg:w-80 space-y-8">
          {/* Quick Actions */}
          <div className="space-y-4">
            <h4 className="text-xs font-black uppercase tracking-widest text-slate-400 px-1">Quick Actions</h4>
            <div className="grid grid-cols-1 gap-3">
              <button
                onClick={() => navigate("/reports")}
                className="flex items-center gap-3 p-4 bg-white hover:bg-slate-50 transition-all rounded-lg border border-slate-200/60 text-left shadow-sm group"
              >
                <BarChart2 className="h-5 w-5 text-indigo-700 group-hover:scale-110 transition-transform" />
                <div>
                  <span className="block text-sm font-bold">View Reports</span>
                  <span className="block text-[10px] text-slate-500">Tax summaries &amp; variances</span>
                </div>
              </button>
              <button
                onClick={() => navigate("/payroll/payslips")}
                className="flex items-center gap-3 p-4 bg-white hover:bg-slate-50 transition-all rounded-lg border border-slate-200/60 text-left shadow-sm group"
              >
                <Receipt className="h-5 w-5 text-rose-600 group-hover:scale-110 transition-transform" />
                <div>
                  <span className="block text-sm font-bold">Payslip History</span>
                  <span className="block text-[10px] text-slate-500">{formatNumber(payslips.length)} phiếu lương gần nhất</span>
                </div>
              </button>
            </div>
          </div>

          {/* Recent Payslips */}
          <div className="bg-slate-50 rounded-xl p-6 relative overflow-hidden">
            <div className="absolute -right-4 -top-4 w-24 h-24 bg-indigo-700/5 rounded-full" />
            <h4 className="text-xs font-black uppercase tracking-widest text-slate-400 mb-6 relative">Phiếu lương gần đây</h4>
            <div className="space-y-4 relative">
              {payslips.length ? (
                payslips.slice(0, 4).map((item, index) => {
                  const name = textValue(item, ["employee.full_name", "employee_name", "full_name"], "Nhân viên");
                  const net = numberValue(item, ["net_salary", "net_pay", "salary_net"], 0);
                  const month = textValue(item, ["payroll_run.attendance_period.month", "month"], "N/A");
                  const year = textValue(item, ["payroll_run.attendance_period.year", "year"], "N/A");
                  return (
                    <div key={`payslip-${index}`} className="flex gap-3">
                      <div className="flex flex-col items-center">
                        <div className="w-2 h-2 rounded-full bg-indigo-700 mb-1 mt-1" />
                        {index < payslips.slice(0, 4).length - 1 && <div className="w-0.5 flex-1 bg-slate-200" />}
                      </div>
                      <div className="pb-4">
                        <span className="text-[10px] font-bold text-indigo-700 block mb-1">
                          Tháng {month}/{year}
                        </span>
                        <h5 className="text-sm font-bold text-slate-900">{name}</h5>
                        <p className="text-xs text-slate-500 mt-1">{formatCurrency(net)}</p>
                      </div>
                    </div>
                  );
                })
              ) : (
                <p className="text-xs text-slate-500">Chưa có phiếu lương.</p>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
