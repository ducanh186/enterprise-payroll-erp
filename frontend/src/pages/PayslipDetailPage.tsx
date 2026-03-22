import { useMemo } from "react";
import { useParams, Link } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import {
  ArrowLeft,
  Printer,
  Download,
  CheckCircle2,
  PlusCircle,
  MinusCircle,
  ShieldCheck,
} from "lucide-react";
import { apiGet } from "../lib/api";
import { formatCurrency, formatDate } from "../lib/format";
import { numberValue, textValue, toArray } from "../lib/records";
import { Badge, EmptyState } from "../components/ui";

export default function PayslipDetailPage() {
  const { id } = useParams<{ id: string }>();

  const payslipQuery = useQuery({
    queryKey: ["payroll", "payslip-detail", id],
    queryFn: async () => apiGet<unknown>(`/payroll/payslips/${id}/details`),
    enabled: Boolean(id),
  });

  const payslip =
    payslipQuery.data?.data && typeof payslipQuery.data.data === "object"
      ? (payslipQuery.data.data as Record<string, unknown>)
      : null;

  const items = useMemo(
    () => toArray<Record<string, unknown>>(payslip?.items),
    [payslip?.items],
  );

  const earnings = items.filter(
    (i) =>
      textValue(i, ["item_group", "group", "type"], "")
        .toLowerCase()
        .includes("earn"),
  );
  const deductions = items.filter(
    (i) =>
      !textValue(i, ["item_group", "group", "type"], "")
        .toLowerCase()
        .includes("earn"),
  );

  const statusTone = (status: string) => {
    const s = status.toLowerCase();
    if (s.includes("lock")) return "accent" as const;
    if (s.includes("final")) return "success" as const;
    if (s.includes("preview")) return "info" as const;
    return "neutral" as const;
  };

  const status = payslip ? textValue(payslip, ["status"], "draft") : "draft";
  const employeeName = payslip
    ? textValue(
        payslip,
        ["employee.full_name", "employee_name", "full_name"],
        "Nhân viên",
      )
    : "Nhân viên";
  const employeeCode = payslip
    ? textValue(
        payslip,
        ["employee.employee_code", "employee_code"],
        "N/A",
      )
    : "N/A";
  const departmentName = payslip
    ? textValue(
        payslip,
        ["employee.department.name", "department_name"],
        "N/A",
      )
    : "N/A";
  const positionName = payslip
    ? textValue(payslip, ["employee.position.name", "position_name"], "N/A")
    : "N/A";
  const periodCode = payslip
    ? textValue(
        payslip,
        ["period_code", "payroll_run.attendance_period.period_code"],
        "N/A",
      )
    : "N/A";
  const createdAt = payslip ? textValue(payslip, ["created_at"], "") : "";
  const netSalary = payslip
    ? numberValue(payslip, ["net_salary", "net_pay"], 0)
    : 0;
  const grossSalary = payslip
    ? numberValue(payslip, ["gross_salary", "gross_pay"], 0)
    : 0;
  const totalEarnings = earnings.reduce(
    (sum, i) => sum + numberValue(i, ["amount", "value"], 0),
    0,
  );
  const totalDeductions = deductions.reduce(
    (sum, i) => sum + numberValue(i, ["amount", "value"], 0),
    0,
  );

  const isConfirmed =
    status.toLowerCase().includes("final") ||
    status.toLowerCase().includes("lock");

  return (
    <div className="space-y-6">
      {/* Actions bar — hidden on print */}
      <div className="no-print flex flex-col gap-4 border-b border-white/60 pb-5 md:flex-row md:items-end md:justify-between">
        <div>
          <p className="text-xs font-bold uppercase tracking-[0.24em] text-slate-500">
            Payroll / Lịch sử lương
          </p>
          <h1 className="mt-2 font-[family-name:var(--font-display)] text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
            {periodCode !== "N/A" ? `Phiếu lương ${periodCode}` : `Phiếu lương #${id}`}
          </h1>
        </div>
        <div className="flex items-center gap-3">
          <Link
            to="/payroll/payslips"
            className="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
          >
            <ArrowLeft className="h-4 w-4" />
            Quay lại
          </Link>
          <button
            type="button"
            onClick={() => window.print()}
            className="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
          >
            <Printer className="h-4 w-4" />
            In phiếu
          </button>
          <button
            type="button"
            className="inline-flex items-center gap-2 rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
          >
            <Download className="h-4 w-4" />
            Tải xuống
          </button>
        </div>
      </div>

      {/* Loading state */}
      {payslipQuery.isLoading ? (
        <div className="rounded-3xl border border-white/70 bg-white/80 p-10 text-center shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur">
          <p className="text-sm text-slate-500">Đang tải phiếu lương...</p>
        </div>
      ) : !payslip ? (
        <EmptyState
          title="Không tìm thấy phiếu lương"
          description={`Phiếu lương #${id} không tồn tại hoặc chưa có dữ liệu.`}
        />
      ) : (
        /* Ledger Sheet */
        <div className="overflow-hidden rounded-3xl border border-slate-200/60 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.08)]">

          {/* ── Section 1: Header — Employee Info + Pay Period ── */}
          <div className="border-b border-slate-100 bg-slate-50/40 p-8">
            <div className="flex flex-col gap-8 md:flex-row md:justify-between">
              {/* Left: Company logo + Employee info */}
              <div className="space-y-5">
                {/* Company identity */}
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-950">
                    <span className="text-xs font-black text-white">HR</span>
                  </div>
                  <span className="text-base font-black uppercase tracking-widest text-slate-950">
                    Enterprise Payroll ERP
                  </span>
                </div>

                {/* Employee details grid */}
                <div className="grid grid-cols-2 gap-x-12 gap-y-3">
                  <div>
                    <p className="text-[0.6875rem] font-bold uppercase tracking-wider text-slate-400">
                      Họ và tên
                    </p>
                    <p className="text-sm font-semibold text-slate-900">
                      {employeeName.toUpperCase()}
                    </p>
                  </div>
                  <div>
                    <p className="text-[0.6875rem] font-bold uppercase tracking-wider text-slate-400">
                      Mã nhân viên
                    </p>
                    <p className="text-sm font-semibold text-slate-900">
                      #{employeeCode}
                    </p>
                  </div>
                  <div>
                    <p className="text-[0.6875rem] font-bold uppercase tracking-wider text-slate-400">
                      Phòng ban
                    </p>
                    <p className="text-sm font-semibold text-slate-900">
                      {departmentName}
                    </p>
                  </div>
                  <div>
                    <p className="text-[0.6875rem] font-bold uppercase tracking-wider text-slate-400">
                      Chức vụ
                    </p>
                    <p className="text-sm font-semibold text-slate-900">
                      {positionName}
                    </p>
                  </div>
                </div>
              </div>

              {/* Right: Status + Pay period */}
              <div className="space-y-4 md:text-right">
                {isConfirmed ? (
                  <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-emerald-700 ring-1 ring-emerald-200">
                    <CheckCircle2 className="h-3.5 w-3.5" />
                    Đã xác nhận
                  </span>
                ) : (
                  <Badge tone={statusTone(status)}>{status}</Badge>
                )}

                <div className="mt-4">
                  <p className="text-[0.6875rem] font-bold uppercase tracking-wider text-slate-400">
                    Kỳ lương
                  </p>
                  <p className="text-lg font-bold text-slate-900">
                    {periodCode}
                  </p>
                  <p className="text-[0.6875rem] font-medium text-slate-400">
                    Ngày tạo: {formatDate(createdAt)}
                  </p>
                </div>
              </div>
            </div>
          </div>

          {/* ── Section 2: Earnings & Deductions two-column ledger ── */}
          <div className="grid md:grid-cols-2">
            {/* Column 1 — Earnings */}
            <div className="border-b border-slate-100 p-8 md:border-b-0 md:border-r">
              <h3 className="mb-6 flex items-center gap-2 text-sm font-black uppercase tracking-widest text-emerald-600">
                <PlusCircle className="h-4 w-4" />
                Các khoản thu nhập
              </h3>

              {earnings.length > 0 ? (
                <div className="space-y-4">
                  {earnings.map((item, index) => (
                    <div
                      key={`earn-${textValue(item, ["item_code", "code"], String(index))}-${index}`}
                      className="group flex items-center justify-between py-1"
                    >
                      <span className="text-sm font-medium text-slate-500 transition-colors group-hover:text-slate-900">
                        {textValue(item, ["item_name", "name", "label"], "Thu nhập")}
                      </span>
                      <span className="tabular-nums text-sm font-bold text-slate-900">
                        {formatCurrency(numberValue(item, ["amount", "value"], 0))}
                      </span>
                    </div>
                  ))}
                </div>
              ) : (
                /* Fallback: show gross salary as a single line when no items */
                <div className="group flex items-center justify-between py-1">
                  <span className="text-sm font-medium text-slate-500">
                    Tổng thu nhập (gộp)
                  </span>
                  <span className="tabular-nums text-sm font-bold text-slate-900">
                    {formatCurrency(grossSalary)}
                  </span>
                </div>
              )}

              <div className="mt-8 flex items-center justify-between border-t border-dashed border-slate-200 pt-4">
                <span className="text-sm font-bold uppercase tracking-tighter text-slate-900">
                  Tổng thu nhập
                </span>
                <span className="tabular-nums text-lg font-black text-slate-900">
                  {formatCurrency(earnings.length > 0 ? totalEarnings : grossSalary)}
                </span>
              </div>
            </div>

            {/* Column 2 — Deductions */}
            <div className="bg-slate-50/20 p-8">
              <h3 className="mb-6 flex items-center gap-2 text-sm font-black uppercase tracking-widest text-rose-600">
                <MinusCircle className="h-4 w-4" />
                Các khoản khấu trừ
              </h3>

              {deductions.length > 0 ? (
                <div className="space-y-4">
                  {deductions.map((item, index) => (
                    <div
                      key={`ded-${textValue(item, ["item_code", "code"], String(index))}-${index}`}
                      className="group flex items-center justify-between py-1"
                    >
                      <span className="text-sm font-medium text-slate-500 transition-colors group-hover:text-slate-900">
                        {textValue(item, ["item_name", "name", "label"], "Khấu trừ")}
                      </span>
                      <span className="tabular-nums text-sm font-bold text-slate-900">
                        -{formatCurrency(numberValue(item, ["amount", "value"], 0))}
                      </span>
                    </div>
                  ))}
                </div>
              ) : (
                /* Fallback: derive total deductions from gross - net */
                <div className="group flex items-center justify-between py-1">
                  <span className="text-sm font-medium text-slate-500">
                    Tổng khấu trừ (gộp)
                  </span>
                  <span className="tabular-nums text-sm font-bold text-slate-900">
                    -{formatCurrency(Math.max(0, grossSalary - netSalary))}
                  </span>
                </div>
              )}

              <div className="mt-8 flex items-center justify-between border-t border-dashed border-slate-200 pt-4">
                <span className="text-sm font-bold uppercase tracking-tighter text-slate-900">
                  Tổng khấu trừ
                </span>
                <span className="tabular-nums text-lg font-black text-slate-900">
                  -{formatCurrency(deductions.length > 0 ? totalDeductions : Math.max(0, grossSalary - netSalary))}
                </span>
              </div>
            </div>
          </div>

          {/* ── Section 3: Net Pay Banner ── */}
          <div className="bg-slate-950 p-8 text-white">
            <div className="flex flex-col items-center justify-between gap-6 md:flex-row">
              <div>
                <p className="text-[0.6875rem] font-bold uppercase tracking-widest text-white/60">
                  THỰC LĨNH (NET PAYMENT)
                </p>
                <p className="mt-1 font-[family-name:var(--font-display)] text-5xl font-black tracking-tighter tabular-nums md:text-6xl">
                  {formatCurrency(netSalary)}
                </p>
              </div>
              <div className="max-w-xs text-right">
                <p className="text-xs font-medium italic text-white/70">
                  Số tiền thực nhận sau tất cả các khoản khấu trừ thuế và bảo hiểm theo quy định.
                </p>
              </div>
            </div>
          </div>

          {/* ── Section 4: Attendance Summary ── */}
          <div className="border-t border-slate-100 p-8">
            <h3 className="mb-5 text-sm font-black uppercase tracking-widest text-slate-400">
              Thông tin chấm công
            </h3>
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
              {(
                [
                  ["Ngày công", ["total_workdays", "workdays"]],
                  ["Giờ thường", ["regular_hours"]],
                  ["Giờ tăng ca", ["ot_hours", "overtime_hours"]],
                  ["Phút đi trễ", ["late_minutes"]],
                ] as [string, string[]][]
              ).map(([label, paths]) => (
                <div
                  key={label}
                  className="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3"
                >
                  <p className="text-[0.6875rem] font-bold uppercase tracking-wider text-slate-400">
                    {label}
                  </p>
                  <p className="mt-1 text-lg font-bold text-slate-900">
                    {textValue(payslip, paths, "—")}
                  </p>
                </div>
              ))}
            </div>
          </div>

          {/* ── Section 5: Verification Footer ── */}
          <div className="border-t border-slate-100 p-8">
            <div className="flex flex-col items-end justify-between gap-10 md:flex-row">
              {/* Verification block */}
              <div className="flex-1 space-y-4">
                <div className="rounded-xl border border-sky-100 bg-sky-50/60 p-4">
                  <div className="flex items-start gap-3">
                    <ShieldCheck className="mt-0.5 h-5 w-5 flex-shrink-0 text-sky-700" />
                    <div>
                      <p className="text-xs font-bold uppercase tracking-wide text-sky-700">
                        Xác thực hệ thống
                      </p>
                      <p className="mt-1 break-all font-mono text-[10px] leading-tight text-slate-500">
                        ID: {id} &nbsp;·&nbsp; Kỳ: {periodCode}
                      </p>
                    </div>
                  </div>
                </div>
                <p className="text-[10px] italic text-slate-400">
                  Đây là phiếu lương được tạo tự động bởi hệ thống. Tài liệu có giá trị pháp lý mà không cần chữ ký tay.
                </p>
              </div>

              {/* Digital stamp */}
              <div className="flex flex-col items-center">
                <div className="relative mb-2 flex h-24 w-40 items-center justify-center">
                  <div className="absolute inset-0 flex items-center justify-center">
                    <div className="h-16 w-16 rounded-full border-2 border-dashed border-slate-200" />
                  </div>
                  <div className="absolute bottom-4 left-0 right-0 border-b border-slate-200" />
                  <div className="relative flex flex-col items-center justify-center">
                    <p className="font-mono text-[11px] font-bold text-slate-400">
                      KÝ SỐ HỆ THỐNG
                    </p>
                    <p className="text-[10px] font-medium text-slate-400">
                      Quản trị viên
                    </p>
                  </div>
                </div>
                <p className="text-[0.6875rem] font-bold uppercase tracking-widest text-slate-400">
                  Dấu xác nhận
                </p>
              </div>
            </div>
          </div>

          {/* ── Footer meta ── */}
          <div className="no-print flex items-center justify-between border-t border-slate-100 bg-slate-50/50 px-8 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">
            <span>© Enterprise Payroll ERP</span>
            <span>Phiếu lương #{id}</span>
          </div>
        </div>
      )}
    </div>
  );
}
