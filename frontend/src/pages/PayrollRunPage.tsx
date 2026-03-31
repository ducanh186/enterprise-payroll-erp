import { useMemo, useState } from "react";
import type { FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { ArrowLeft, ArrowRight, Play, ShieldCheck, Search, Filter, Users, CheckCircle2 } from "lucide-react";
import { apiGet, apiPost, getApiErrorMessage } from "../lib/api";
import { formatCurrency, formatNumber } from "../lib/format";
import { numberValue, textValue, toArray } from "../lib/records";
import { EmptyState } from "../components/ui";

const current = new Date();

type Step = 1 | 2 | 3;

export default function PayrollRunPage() {
  const queryClient = useQueryClient();
  const [currentStep, setCurrentStep] = useState<Step>(1);
  const [form, setForm] = useState({
    month: String(current.getMonth() + 1),
    year: String(current.getFullYear()),
    scope: "all",
    department_id: "",
  });
  const [result, setResult] = useState<Record<string, unknown> | null>(null);
  const [error, setError] = useState<string | null>(null);

  const departmentsQuery = useQuery({
    queryKey: ["reference", "departments"],
    queryFn: async () => apiGet<unknown>("/reference/departments"),
  });

  const previewParamsQuery = useQuery({
    queryKey: ["payroll", "preview-parameters"],
    queryFn: async () => apiGet<unknown>("/payroll/runs/preview-parameters"),
  });

  const departments = useMemo(() => toArray<Record<string, unknown>>(departmentsQuery.data?.data), [departmentsQuery.data?.data]);
  const previewParams = useMemo(() => toArray<Record<string, unknown>>(previewParamsQuery.data?.data), [previewParamsQuery.data?.data]);

  const previewMutation = useMutation({
    mutationFn: async () =>
      apiPost<unknown>("/payroll/runs/preview", {
        month: Number(form.month),
        year: Number(form.year),
        scope: form.scope,
        ...(form.department_id ? { department_id: Number(form.department_id) } : {}),
        parameters: {},
        adjustments: [],
      }),
    onSuccess: async (response) => {
      setError(null);
      setResult((response.data ?? {}) as Record<string, unknown>);
      await queryClient.invalidateQueries({ queryKey: ["payroll", "periods"] });
      setCurrentStep(2);
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể preview payroll run."));
    },
  });

  const openPeriodMutation = useMutation({
    mutationFn: async () =>
      apiPost<unknown>("/payroll/periods/open", {
        month: Number(form.month),
        year: Number(form.year),
      }),
    onSuccess: async () => {
      setError(null);
      await queryClient.invalidateQueries({ queryKey: ["payroll", "periods"] });
      setCurrentStep(3);
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể mở kỳ lương."));
    },
  });

  function submitPreview(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    previewMutation.mutate();
  }

  const stepLabels: Record<Step, string> = {
    1: "Thiết lập",
    2: "Nhập liệu và điều chỉnh",
    3: "Xem trước và hoàn tất",
  };

  const totalEmployees = result ? numberValue(result, ["total_employees", "summary.total_employees"], 0) : 0;
  const totalNet = result ? numberValue(result, ["net_salary", "summary.total_net_salary"], 0) : 0;
  const totalGross = result ? numberValue(result, ["gross_salary", "summary.total_gross_salary"], 0) : 0;
  const totalDeductions = result
    ? numberValue(result, ["insurance_employee", "summary.total_insurance_employee"], 0) +
      numberValue(result, ["pit_amount", "summary.total_pit"], 0)
    : 0;

  const previewItems = result
    ? toArray<Record<string, unknown>>(
        (result as Record<string, unknown>).items ?? (result as Record<string, unknown>).payslips ?? [],
      )
    : [];

  return (
    <div className="space-y-8">
      {/* Header + Stepper */}
      <div className="space-y-8">
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
          <div>
            <h1 className="text-3xl font-black tracking-tight text-indigo-700">Trình chạy bảng lương</h1>
            <p className="text-slate-500 mt-1">Thiết lập và chạy bảng lương theo kỳ.</p>
          </div>

          {/* Step Indicator */}
          <nav className="flex items-center gap-2 p-1 bg-slate-100 rounded-xl">
            {([1, 2, 3] as Step[]).map((step, idx) => (
              <div key={step} className="flex items-center gap-1">
                <div
                  className={`flex items-center gap-2 px-4 py-2 rounded-lg transition-all ${
                    currentStep === step
                      ? "bg-white shadow-sm"
                      : "opacity-50"
                  }`}
                >
                  <span
                    className={`w-6 h-6 rounded-full text-[10px] flex items-center justify-center font-bold ${
                      currentStep === step
                        ? "bg-indigo-700 text-white"
                        : step < currentStep
                        ? "bg-emerald-600 text-white"
                        : "bg-slate-400 text-white"
                    }`}
                  >
                    {step < currentStep ? <CheckCircle2 className="h-3 w-3" /> : step}
                  </span>
                  <span
                    className={`text-xs font-bold uppercase tracking-wider ${
                      currentStep === step ? "text-indigo-700" : "text-slate-500"
                    }`}
                  >
                    {stepLabels[step]}
                  </span>
                </div>
                {idx < 2 && <div className="h-4 w-px bg-slate-300" />}
              </div>
            ))}
          </nav>
        </div>

        {/* Linear Step Indicator (top bar style) */}
        <div className="grid grid-cols-3 gap-4">
          {([1, 2, 3] as Step[]).map((step) => (
            <div
              key={step}
              className={`relative pt-4 border-t-4 ${
                currentStep === step
                  ? "border-indigo-700"
                  : step < currentStep
                  ? "border-emerald-500"
                  : "border-slate-200"
              }`}
            >
              {currentStep === step && (
                <span className="absolute -top-3 left-0 text-[10px] font-bold tracking-widest uppercase text-indigo-700">
                  Đang thực hiện
                </span>
              )}
              <div className={`flex items-center gap-3 ${currentStep !== step ? "opacity-50" : ""}`}>
                <span
                  className={`flex items-center justify-center w-6 h-6 rounded-full text-[10px] font-bold ${
                    step < currentStep
                      ? "bg-emerald-500 text-white"
                      : currentStep === step
                      ? "bg-indigo-700 text-white"
                      : "bg-slate-300 text-slate-600"
                  }`}
                >
                  {step}
                </span>
                <p className={`font-bold text-sm ${currentStep === step ? "text-slate-900" : "text-slate-500"}`}>
                  {stepLabels[step]}
                </p>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* ── STEP 1: Setup Form ── */}
      {currentStep === 1 && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-10">
          {/* Left: Selection Form */}
          <div className="lg:col-span-2 space-y-8">
            <div className="bg-white p-8 rounded-xl shadow-sm border border-slate-200/60 space-y-8">
              <h3 className="text-[11px] font-bold tracking-[0.05em] uppercase text-slate-500 flex items-center gap-2">
                <Play className="h-4 w-4" />
                Tham số thực thi
              </h3>

              <form onSubmit={submitPreview} className="space-y-6">
                <div className="grid grid-cols-2 gap-8">
                  {/* Month/Year */}
                  <div className="space-y-2">
                    <label className="text-[12px] font-bold text-slate-500">Tháng lương</label>
                    <select
                      value={form.month}
                      onChange={(e) => setForm((f) => ({ ...f, month: e.target.value }))}
                      className="w-full border-b-2 border-slate-300 bg-slate-100 rounded-t-lg px-4 h-12 text-sm font-medium focus:outline-none focus:border-indigo-700 appearance-none"
                    >
                      {Array.from({ length: 12 }, (_, i) => i + 1).map((m) => (
                        <option key={m} value={String(m)}>
                          Tháng {m}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div className="space-y-2">
                    <label className="text-[12px] font-bold text-slate-500">Năm</label>
                    <input
                      type="number"
                      value={form.year}
                      onChange={(e) => setForm((f) => ({ ...f, year: e.target.value }))}
                      className="w-full border-b-2 border-slate-300 bg-slate-100 rounded-t-lg px-4 h-12 text-sm font-medium focus:outline-none focus:border-indigo-700"
                    />
                  </div>

                  {/* Scope */}
                  <div className="col-span-2 space-y-2">
                    <label className="text-[12px] font-bold text-slate-500">Phạm vi tính lương</label>
                    <div className="grid grid-cols-2 gap-px bg-slate-300 rounded-lg overflow-hidden border border-slate-300">
                      <button
                        type="button"
                        onClick={() => setForm((f) => ({ ...f, scope: "all" }))}
                        className={`py-2 text-xs font-bold transition-colors ${
                          form.scope === "all" ? "bg-white text-indigo-700" : "bg-slate-50 text-slate-500 hover:bg-white"
                        }`}
                      >
                        Toàn bộ (cả tháng)
                      </button>
                      <button
                        type="button"
                        onClick={() => setForm((f) => ({ ...f, scope: "department" }))}
                        className={`py-2 text-xs font-bold transition-colors ${
                          form.scope === "department" ? "bg-white text-indigo-700" : "bg-slate-50 text-slate-500 hover:bg-white"
                        }`}
                      >
                        Theo phòng ban
                      </button>
                    </div>
                  </div>

                  {/* Department (conditional) */}
                  {form.scope === "department" && (
                    <div className="col-span-2 space-y-2">
                      <label className="text-[12px] font-bold text-slate-500">Phạm vi phòng ban</label>
                      <select
                        value={form.department_id}
                        onChange={(e) => setForm((f) => ({ ...f, department_id: e.target.value }))}
                        className="w-full border-b-2 border-slate-300 bg-slate-100 rounded-t-lg px-4 h-12 text-sm font-medium focus:outline-none focus:border-indigo-700 appearance-none"
                      >
                        <option value="">Tất cả phòng ban</option>
                        {departments.map((dept, i) => (
                          <option key={`dept-${i}`} value={textValue(dept, ["id"], "")}>
                            {textValue(dept, ["name"], "Department")}
                          </option>
                        ))}
                      </select>
                    </div>
                  )}
                </div>

                {/* Info alert */}
                <div className="bg-emerald-50 border-l-4 border-emerald-600 p-4 rounded flex items-start gap-4">
                  <Users className="h-5 w-5 text-emerald-600 mt-0.5 flex-shrink-0" />
                  <div>
                    <p className="text-sm font-bold text-emerald-900">
                      {previewParams.length} Tham số đã tải
                    </p>
                    <p className="text-[12px] text-emerald-800 opacity-80 mt-0.5">
                      Hệ thống đã tải {previewParams.length} tham số cho phạm vi "{form.scope}" của Tháng {form.month}/{form.year}.
                    </p>
                  </div>
                </div>

                {error && (
                  <div className="bg-rose-50 border border-rose-200 rounded-lg px-4 py-3 text-sm text-rose-700">
                    {error}
                  </div>
                )}

                {/* Actions */}
                <div className="flex items-center justify-between pt-4">
                  <button
                    type="button"
                    onClick={() => openPeriodMutation.mutate()}
                    disabled={openPeriodMutation.isPending}
                    className="text-sm font-bold text-slate-500 hover:underline disabled:opacity-50"
                  >
                    <ShieldCheck className="h-4 w-4 inline mr-1" />
                    {openPeriodMutation.isPending ? "Đang mở..." : "Mở kỳ lương"}
                  </button>
                  <button
                    type="submit"
                    disabled={previewMutation.isPending}
                    className="bg-gradient-to-br from-slate-950 to-indigo-700 text-white px-8 py-3 rounded-lg font-bold text-sm shadow-sm hover:opacity-90 transition-opacity disabled:opacity-60 flex items-center gap-2"
                  >
                    {previewMutation.isPending ? "Đang tạo bản xem trước..." : "Tiếp tục bước tiếp theo"}
                    {!previewMutation.isPending && <ArrowRight className="h-4 w-4" />}
                  </button>
                </div>
              </form>
            </div>
          </div>

          {/* Right: Reference Summary */}
          <div className="space-y-6">
            <div className="bg-slate-50 p-6 rounded-xl border-l-4 border-emerald-600">
              <h3 className="text-[11px] font-bold tracking-[0.05em] uppercase text-slate-500 mb-6 flex items-center gap-2">
                Tham chiếu tháng trước
              </h3>
              <div className="space-y-6">
                <div className="flex justify-between items-end border-b border-slate-200 pb-4">
                  <div>
                    <p className="text-[10px] text-slate-500 font-medium uppercase tracking-wider">Tổng lương thực nhận</p>
                    <p className="text-xl font-black text-slate-900 tabular-nums">—</p>
                  </div>
                  <div className="text-right">
                    <span className="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full text-[10px] font-bold">Đã xác nhận</span>
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="p-3 bg-white rounded border border-slate-200">
                    <p className="text-[10px] text-slate-500 uppercase font-medium">Tham số đã tải</p>
                    <p className="text-sm font-bold text-emerald-600 tabular-nums">{previewParams.length}</p>
                  </div>
                  <div className="p-3 bg-white rounded border border-slate-200">
                    <p className="text-[10px] text-slate-500 uppercase font-medium">Phòng ban</p>
                    <p className="text-sm font-bold text-slate-900 tabular-nums">{departments.length}</p>
                  </div>
                </div>
              </div>
            </div>

            <div className="bg-slate-100 p-6 rounded-xl border border-dashed border-slate-300">
              <h4 className="text-xs font-bold mb-2 text-slate-700">Ghi chú</h4>
              <p className="text-[11px] text-slate-500 leading-relaxed italic">
                "Đảm bảo tất cả bảng chấm công đã được phê duyệt trước khi chuyển sang Bước 2. Giờ chưa phê duyệt sẽ được tính theo giá trị hợp đồng cơ bản."
              </p>
            </div>
          </div>
        </div>
      )}

      {/* ── STEP 2: Preview Result (Inputs & Adjustments) ── */}
      {currentStep === 2 && result && (
        <div className="space-y-8">
          {/* Filters row */}
          <div className="flex flex-wrap items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <div className="relative">
                <select className="appearance-none bg-white border border-slate-200 rounded-lg pl-4 pr-10 py-2.5 text-sm font-medium shadow-sm focus:ring-2 focus:ring-indigo-200">
                  <option>Tất cả phòng ban</option>
                  {departments.map((d, i) => (
                    <option key={i}>{textValue(d, ["name"], "Department")}</option>
                  ))}
                </select>
                <Filter className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
              </div>
            </div>
            <div className="flex items-center gap-3 text-sm font-bold text-slate-500 bg-slate-100 px-4 py-2 rounded-lg">
              <Users className="h-4 w-4 text-indigo-700" />
              {formatNumber(totalEmployees)} Nhân viên được liệt kê
            </div>
          </div>

          {/* Summary stats */}
          <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div className="bg-white p-6 rounded-xl border-l-4 border-emerald-600 shadow-sm flex flex-col justify-between h-36">
              <div className="flex justify-between items-start">
                <span className="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Tổng lương thực nhận</span>
              </div>
              <div>
                <div className="text-2xl font-black text-slate-900 tabular-nums">{formatCurrency(totalNet)}</div>
              </div>
            </div>
            <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex flex-col justify-between h-36">
              <span className="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Tổng khấu trừ</span>
              <div className="text-2xl font-black text-slate-900 tabular-nums">{formatCurrency(totalDeductions)}</div>
            </div>
            <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex flex-col justify-between h-36">
              <span className="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Tổng lương gộp</span>
              <div className="text-2xl font-black text-slate-900 tabular-nums">{formatCurrency(totalGross)}</div>
            </div>
            <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex flex-col justify-between h-36">
              <span className="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Tổng số nhân viên</span>
              <div className="text-2xl font-black text-slate-900 tabular-nums">{formatNumber(totalEmployees)}</div>
            </div>
          </div>

          {/* Data Table */}
          <div className="bg-white rounded-xl shadow-sm border border-slate-200/60 overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-slate-50 uppercase text-[10px] tracking-widest font-black text-slate-500 border-b border-slate-100">
                    <th className="px-6 py-4">Nhân viên</th>
                    <th className="px-6 py-4">Lương cơ bản</th>
                    <th className="px-6 py-4">Thưởng</th>
                    <th className="px-6 py-4">Khấu trừ</th>
                    <th className="px-6 py-4 text-right">Thực nhận</th>
                    <th className="px-6 py-4">Trạng thái</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-50">
                  {previewItems.length ? (
                    previewItems.slice(0, 10).map((item, index) => {
                      const name = textValue(item, ["employee.full_name", "employee_name", "full_name"], `Employee ${index + 1}`);
                      const empCode = textValue(item, ["employee.employee_code", "employee_code"], "N/A");
                      const initials = name.split(" ").slice(-2).map((n) => n[0]).join("").toUpperCase().slice(0, 2);
                      const base = numberValue(item, ["base_salary_snapshot", "base_salary"], 0);
                      const gross = numberValue(item, ["gross_salary", "gross_pay"], 0);
                      const net = numberValue(item, ["net_salary", "net_pay"], 0);
                      const ded = gross - net;
                      const status = textValue(item, ["status"], "pending");
                      return (
                        <tr key={`item-${index}`} className="hover:bg-slate-50 transition-colors group">
                          <td className="px-6 py-4">
                            <div className="flex items-center gap-3">
                              <div className="w-8 h-8 rounded-full bg-sky-100 text-sky-800 flex items-center justify-center text-[10px] font-bold">
                                {initials}
                              </div>
                              <div>
                                <p className="text-sm font-bold text-slate-900">{name}</p>
                                <p className="text-[10px] text-slate-500">{empCode}</p>
                              </div>
                            </div>
                          </td>
                          <td className="px-6 py-4 text-sm font-medium tabular-nums text-slate-900">
                            {formatCurrency(base)}
                          </td>
                          <td className="px-6 py-4 text-sm font-medium tabular-nums text-emerald-600">
                            +{formatCurrency(gross - base > 0 ? gross - base : 0)}
                          </td>
                          <td className="px-6 py-4 text-sm font-medium tabular-nums text-rose-600">
                            -{formatCurrency(ded > 0 ? ded : 0)}
                          </td>
                          <td className="px-6 py-4 text-sm font-black tabular-nums text-right text-slate-900">
                            {formatCurrency(net)}
                          </td>
                          <td className="px-6 py-4">
                            <span className="px-2 py-1 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-600 flex items-center gap-1 w-fit">
                              <span className="w-1 h-1 rounded-full bg-slate-400" />
                              {status}
                            </span>
                          </td>
                        </tr>
                      );
                    })
                  ) : (
                    <tr>
                      <td colSpan={6} className="px-6 py-10">
                        <EmptyState
                          title="Dữ liệu xem trước đã tải"
                          description="Chi tiết từng nhân viên sẽ hiển thị tại đây khi dịch vụ trả về dữ liệu chi tiết."
                        />
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
            {previewItems.length > 0 && (
              <div className="px-6 py-4 bg-slate-50 flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-slate-500">
                <span>Hiển thị {Math.min(10, previewItems.length)} / {previewItems.length} Nhân viên</span>
                <div className="flex items-center gap-4">
                  <span className="text-emerald-600">Tổng thực nhận: {formatCurrency(totalNet)}</span>
                  <span className="text-slate-300">|</span>
                  <span className="text-rose-500">Khấu trừ: {formatCurrency(totalDeductions)}</span>
                </div>
              </div>
            )}
          </div>

          {/* Raw JSON summary */}
          <div className="bg-slate-950 rounded-xl p-6 text-white">
            <p className="text-sm font-semibold mb-3">Tóm tắt xem trước (JSON)</p>
            <pre className="overflow-x-auto text-xs leading-6 text-slate-300">{JSON.stringify(result, null, 2)}</pre>
          </div>

          {error && (
            <div className="bg-rose-50 border border-rose-200 rounded-lg px-4 py-3 text-sm text-rose-700">
              {error}
            </div>
          )}

          {/* Sticky Action Bar */}
          <div className="bg-white border border-slate-200 rounded-xl px-8 py-5 flex justify-between items-center shadow-sm">
            <button
              onClick={() => { setCurrentStep(1); setResult(null); }}
              className="flex items-center gap-2 text-slate-500 font-bold text-sm hover:text-slate-900 transition-colors"
            >
              <ArrowLeft className="h-4 w-4" />
              Quay lại thiết lập
            </button>
            <div className="flex items-center gap-6">
              <div className="text-right">
                <p className="text-[10px] uppercase font-bold text-slate-400 tracking-tighter">Tổng tính</p>
                <p className="text-xl font-black text-indigo-700 tabular-nums leading-tight">{formatCurrency(totalNet)}</p>
              </div>
              <button
                onClick={() => setCurrentStep(3)}
                className="bg-gradient-to-br from-slate-950 to-indigo-700 text-white px-8 py-3 rounded-lg font-bold text-sm flex items-center gap-3 shadow-lg hover:opacity-90 transition-opacity"
              >
                Tiếp tục xem trước
                <ArrowRight className="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ── STEP 3: Finalize ── */}
      {currentStep === 3 && (
        <div className="space-y-8">
          {/* Summary Bento Grid */}
          <section className="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div className="bg-white p-6 rounded-xl border-l-4 border-emerald-600 shadow-sm flex flex-col justify-between h-40">
              <div className="flex justify-between items-start">
                <span className="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Tổng lương thực nhận</span>
              </div>
              <div>
                <div className="text-3xl font-black text-slate-900 tabular-nums">{formatCurrency(totalNet)}</div>
                <div className="text-xs text-emerald-600 font-medium flex items-center gap-1 mt-1">
                  Sẵn sàng chi trả
                </div>
              </div>
            </div>
            <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex flex-col justify-between h-40">
              <span className="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Tổng khấu trừ</span>
              <div>
                <div className="text-3xl font-black text-slate-900 tabular-nums">{formatCurrency(totalDeductions)}</div>
                <div className="text-xs text-slate-500 font-medium mt-1">Thuế, phúc lợi và các khoản khấu trừ</div>
              </div>
            </div>
            <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex flex-col justify-between h-40">
              <span className="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Tổng số nhân viên</span>
              <div>
                <div className="text-3xl font-black text-slate-900 tabular-nums">{formatNumber(totalEmployees)}</div>
                <div className="text-xs text-slate-500 font-medium mt-1">Trong lần chạy này</div>
              </div>
            </div>
            <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex flex-col justify-between h-40">
              <span className="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Kỳ lương</span>
              <div>
                <div className="text-2xl font-black text-slate-900">T{form.month}/{form.year}</div>
                <div className="text-xs text-slate-500 font-medium mt-1">Phạm vi: {form.scope}</div>
              </div>
            </div>
          </section>

          {/* Payslip Ledger */}
          <section className="bg-white rounded-xl shadow-sm overflow-hidden border border-slate-200/60">
            <div className="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
              <div className="flex items-center gap-3">
                <h2 className="font-bold text-lg text-slate-900">Bảng phiếu lương nhân viên</h2>
                <div className="bg-slate-100 px-2 py-0.5 rounded text-[10px] font-bold text-slate-500 uppercase">
                  {formatNumber(totalEmployees)} Mục
                </div>
              </div>
              <div className="flex items-center gap-3">
                <div className="relative w-72">
                  <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                  <input
                    className="w-full pl-10 pr-4 py-2 bg-slate-50 border-none rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 transition-all"
                    placeholder="Tìm kiếm tên hoặc mã nhân viên..."
                    type="text"
                  />
                </div>
                <button className="p-2 bg-slate-50 rounded-lg text-slate-500">
                  <Filter className="h-4 w-4" />
                </button>
              </div>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-slate-50 text-slate-500">
                    <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-wider">Nhân viên</th>
                    <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-wider">Phòng ban</th>
                    <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-wider text-right">Lương gộp</th>
                    <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-wider text-right">Thuế & KT</th>
                    <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-wider text-right">Thực nhận</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-50">
                  {previewItems.length ? (
                    previewItems.slice(0, 5).map((item, index) => {
                      const name = textValue(item, ["employee.full_name", "employee_name", "full_name"], `Employee ${index + 1}`);
                      const empCode = textValue(item, ["employee.employee_code", "employee_code"], "N/A");
                      const dept = textValue(item, ["employee.department.name", "department_name"], "—");
                      const initials = name.split(" ").slice(-2).map((n) => n[0]).join("").toUpperCase().slice(0, 2);
                      const gross = numberValue(item, ["gross_salary", "gross_pay"], 0);
                      const net = numberValue(item, ["net_salary", "net_pay"], 0);
                      const ded = gross - net;
                      return (
                        <tr key={`fin-${index}`} className="hover:bg-slate-50/50 transition-colors group">
                          <td className="px-6 py-5">
                            <div className="flex items-center gap-3">
                              <div className="w-9 h-9 rounded-lg bg-indigo-50 flex items-center justify-center font-bold text-indigo-700 text-xs">
                                {initials}
                              </div>
                              <div>
                                <div className="font-bold text-sm text-slate-900">{name}</div>
                                <div className="text-[10px] text-sky-700 uppercase tracking-tight">{empCode}</div>
                              </div>
                            </div>
                          </td>
                          <td className="px-6 py-5">
                            <span className="text-sm font-medium text-slate-700">{dept}</span>
                          </td>
                          <td className="px-6 py-5 text-right font-medium tabular-nums text-sm text-slate-700">
                            {formatCurrency(gross)}
                          </td>
                          <td className="px-6 py-5 text-right font-medium tabular-nums text-sm text-rose-600">
                            {formatCurrency(ded > 0 ? ded : 0)}
                          </td>
                          <td className="px-6 py-5 text-right font-black tabular-nums text-sm text-indigo-700">
                            {formatCurrency(net)}
                          </td>
                        </tr>
                      );
                    })
                  ) : (
                    <tr>
                      <td colSpan={5} className="px-6 py-10">
                        <EmptyState title="Không có dữ liệu nhân viên" description="Xem trước không trả về dữ liệu chi tiết nhân viên." />
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </section>

          {/* Final Confirmation */}
          <section className="max-w-2xl mx-auto text-center space-y-6 pt-10 pb-10">
            <div className="flex flex-col items-center gap-2">
              <div className="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-700 mb-2">
                <CheckCircle2 className="h-6 w-6" />
              </div>
              <h3 className="text-xl font-extrabold tracking-tight text-slate-900">Sẵn sàng hoàn tất?</h3>
              <p className="text-slate-500 text-sm max-w-md">
                Sau khi hoàn tất, bảng lương sẽ được mở cho kỳ này và sẵn sàng chi trả. Bạn sẽ không thể chỉnh sửa các giá trị này nếu không can thiệp thủ công.
              </p>
            </div>

            {error && (
              <div className="bg-rose-50 border border-rose-200 rounded-lg px-4 py-3 text-sm text-rose-700">
                {error}
              </div>
            )}

            <div className="flex flex-col md:flex-row gap-4 justify-center">
              <button
                onClick={() => setCurrentStep(2)}
                className="px-8 py-4 border border-slate-200 rounded-lg font-bold text-slate-900 hover:bg-slate-50 transition-all flex items-center gap-2 justify-center"
              >
                <ArrowLeft className="h-4 w-4" />
                Quay lại xem trước
              </button>
              <button
                onClick={() => openPeriodMutation.mutate()}
                disabled={openPeriodMutation.isPending}
                className="px-10 py-4 bg-gradient-to-br from-slate-950 to-indigo-700 text-white rounded-lg shadow-xl hover:opacity-90 active:scale-95 transition-all font-black tracking-wide text-lg disabled:opacity-60 flex items-center gap-2 justify-center"
              >
                <ShieldCheck className="h-5 w-5" />
                {openPeriodMutation.isPending ? "Đang xử lý..." : "Hoàn tất và mở kỳ lương"}
              </button>
            </div>
          </section>
        </div>
      )}
    </div>
  );
}
