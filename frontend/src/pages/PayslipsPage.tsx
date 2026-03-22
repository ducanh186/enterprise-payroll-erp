import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import {
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  CheckCircle,
  Download,
  FileText,
  Lock,
  Receipt,
  RefreshCcw,
  Wallet,
} from "lucide-react";
import { apiGet } from "../lib/api";
import { formatCurrency } from "../lib/format";
import { numberValue, textValue, toArray } from "../lib/records";
import { EmptyState, PageHeader } from "../components/ui";

type FilterState = {
  month: string;
  year: string;
  employee_id: string;
  department_id: string;
  status: string;
};

const current = new Date();

const MONTHS_VI = [
  "Tháng 1", "Tháng 2", "Tháng 3", "Tháng 4",
  "Tháng 5", "Tháng 6", "Tháng 7", "Tháng 8",
  "Tháng 9", "Tháng 10", "Tháng 11", "Tháng 12",
];

const YEARS = Array.from({ length: 5 }, (_, i) => String(current.getFullYear() - i));

function isFinalized(status: string): boolean {
  const s = status.toLowerCase();
  return s.includes("final") || s.includes("locked") || s.includes("confirmed");
}

function StatusBadge({ status }: { status: string }) {
  if (isFinalized(status)) {
    return (
      <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-700 ring-1 ring-inset ring-emerald-200">
        <CheckCircle className="h-3 w-3 fill-emerald-500 text-emerald-500" />
        Đã xác nhận
      </span>
    );
  }
  return (
    <span className="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-slate-500 ring-1 ring-inset ring-slate-200">
      <FileText className="h-3 w-3" />
      Nháp
    </span>
  );
}

export default function PayslipsPage() {
  const [filters, setFilters] = useState<FilterState>({
    month: String(current.getMonth() + 1),
    year: String(current.getFullYear()),
    employee_id: "",
    department_id: "",
    status: "",
  });
  const [selectedId, setSelectedId] = useState<string>("");

  const payslipsQuery = useQuery({
    queryKey: ["payroll", "payslips", filters],
    queryFn: async () =>
      apiGet<unknown>("/payroll/payslips", {
        month: Number(filters.month),
        year: Number(filters.year),
        ...(filters.employee_id ? { employee_id: Number(filters.employee_id) } : {}),
        ...(filters.department_id ? { department_id: Number(filters.department_id) } : {}),
        ...(filters.status ? { status: filters.status } : {}),
        page: 1,
        per_page: 20,
      }),
  });

  const detailsQuery = useQuery({
    queryKey: ["payroll", "payslip-details", selectedId],
    queryFn: async () => apiGet<unknown>(`/payroll/payslips/${selectedId}/details`),
    enabled: Boolean(selectedId),
  });

  const payslips = useMemo(
    () => toArray<Record<string, unknown>>(payslipsQuery.data?.data),
    [payslipsQuery.data?.data],
  );

  const selected =
    detailsQuery.data?.data && typeof detailsQuery.data.data === "object"
      ? (detailsQuery.data.data as Record<string, unknown>)
      : null;

  // Determine the latest finalized payslip for the summary card
  const latestFinalized = useMemo(
    () =>
      payslips.find((p) =>
        isFinalized(textValue(p, ["status"], "")),
      ) ?? payslips[0] ?? null,
    [payslips],
  );

  return (
    <div className="space-y-8">
      {/* Page Header */}
      <PageHeader
        eyebrow="Payroll"
        title="Lịch sử phiếu lương"
        description="Xem và tải phiếu lương hàng tháng. Lọc theo kỳ để tra cứu nhanh."
        actions={
          <div className="flex items-center gap-3">
            {/* Year filter */}
            <div className="relative">
              <select
                value={filters.year}
                onChange={(e) => setFilters((f) => ({ ...f, year: e.target.value }))}
                className="appearance-none rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-4 pr-9 text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-200 cursor-pointer"
              >
                {YEARS.map((y) => (
                  <option key={y} value={y}>
                    Năm {y}
                  </option>
                ))}
              </select>
              <ChevronDown className="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            </div>

            {/* Month filter */}
            <div className="relative">
              <select
                value={filters.month}
                onChange={(e) => setFilters((f) => ({ ...f, month: e.target.value }))}
                className="appearance-none rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-4 pr-9 text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-200 cursor-pointer"
              >
                {MONTHS_VI.map((label, idx) => (
                  <option key={idx + 1} value={String(idx + 1)}>
                    {label}
                  </option>
                ))}
              </select>
              <ChevronDown className="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            </div>

            {/* Export / Refresh */}
            <button
              type="button"
              onClick={() => payslipsQuery.refetch()}
              className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300"
            >
              <RefreshCcw className="h-4 w-4" />
              Làm mới
            </button>

            <button
              type="button"
              className="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 active:scale-95"
            >
              <Download className="h-4 w-4" />
              Xuất tất cả
            </button>
          </div>
        }
      />

      {/* Main Bento Grid */}
      <div className="grid grid-cols-12 gap-6">
        {/* Left column — summary + promo card */}
        <div className="col-span-12 space-y-5 lg:col-span-4">
          {/* Latest-period summary card */}
          <div className="relative overflow-hidden rounded-2xl border-l-4 border-emerald-500 bg-white p-6 shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
            <div className="relative z-10">
              <p className="mb-4 text-[0.6875rem] font-bold uppercase tracking-widest text-emerald-600">
                Kỳ gần nhất
              </p>
              {latestFinalized ? (
                <>
                  <h3 className="mb-1 font-[family-name:var(--font-display)] text-2xl font-extrabold tracking-tight text-slate-950">
                    Tháng{" "}
                    {textValue(
                      latestFinalized,
                      ["payroll_run.attendance_period.month", "month"],
                      "N/A",
                    )}
                    /
                    {textValue(
                      latestFinalized,
                      ["payroll_run.attendance_period.year", "year"],
                      filters.year,
                    )}
                  </h3>
                  <p className="mb-6 text-sm text-slate-500">
                    Trạng thái:{" "}
                    {textValue(latestFinalized, ["status"], "—")}
                  </p>
                  <div className="flex items-end justify-between">
                    <div>
                      <p className="mb-1 text-[0.6875rem] font-bold uppercase tracking-widest text-slate-400">
                        Thực lĩnh
                      </p>
                      <p className="font-[family-name:var(--font-display)] text-3xl font-black text-slate-950 tabular-nums">
                        {formatCurrency(
                          numberValue(latestFinalized, ["net_salary", "net_pay"], 0),
                        )}
                      </p>
                    </div>
                    <button
                      type="button"
                      onClick={() =>
                        setSelectedId(
                          String(textValue(latestFinalized, ["id"], "")),
                        )
                      }
                      className="rounded-xl bg-slate-100 p-3 text-slate-700 transition hover:bg-slate-200"
                      title="Xem chi tiết"
                    >
                      <Wallet className="h-5 w-5" />
                    </button>
                  </div>
                </>
              ) : (
                <p className="text-sm text-slate-500">Chưa có phiếu lương xác nhận.</p>
              )}
            </div>
            {/* decorative circle */}
            <div className="absolute -bottom-8 -right-8 h-32 w-32 rounded-full bg-emerald-500/5" />
          </div>

          {/* Request documents promo card */}
          <div className="relative overflow-hidden rounded-2xl bg-slate-950 p-6 text-white shadow-[0_18px_40px_rgba(15,23,42,0.10)]">
            <div className="relative z-10">
              <h4 className="mb-2 font-semibold">Cần báo cáo đặc biệt?</h4>
              <p className="mb-4 text-sm leading-relaxed text-slate-300">
                Yêu cầu tổng kết cuối năm hoặc tài liệu thuế trực tiếp từ bộ phận HR.
              </p>
              <button
                type="button"
                className="rounded-xl bg-white/10 px-4 py-2 text-xs font-bold backdrop-blur-md transition hover:bg-white/20"
              >
                Yêu cầu tài liệu
              </button>
            </div>
            <Receipt className="absolute -bottom-4 -right-4 h-24 w-24 text-white/5" />
          </div>

          {/* Detail panel — shown below promo when a payslip is selected */}
          {selectedId && (
            <div className="rounded-2xl border border-white/70 bg-white/80 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur">
              <p className="mb-4 text-xs font-bold uppercase tracking-widest text-slate-500">
                Chi tiết phiếu lương
              </p>

              {detailsQuery.isLoading ? (
                <p className="text-sm text-slate-500">Đang tải chi tiết...</p>
              ) : selected ? (
                <div className="space-y-4">
                  {/* Employee info */}
                  <div className="rounded-xl bg-slate-50 px-4 py-3">
                    <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">
                      Người nhận
                    </p>
                    <p className="mt-1 font-bold text-slate-950">
                      {textValue(
                        selected,
                        ["employee.full_name", "employee_name", "full_name"],
                        "Nhân viên",
                      )}
                    </p>
                    <p className="text-xs text-slate-500">
                      {textValue(
                        selected,
                        ["employee.employee_code", "employee_code"],
                        "N/A",
                      )}
                    </p>
                  </div>

                  {/* Key figures */}
                  <div className="grid grid-cols-2 gap-2">
                    {(
                      [
                        ["Gross", ["gross_salary", "summary.gross_salary"]],
                        ["Thực lĩnh", ["net_salary", "summary.net_salary"]],
                        ["Bảo hiểm", ["insurance_employee", "summary.insurance_employee"]],
                        ["Thuế TNCN", ["pit_amount", "summary.pit_amount"]],
                      ] as [string, string[]][]
                    ).map(([label, paths]) => (
                      <div
                        key={label}
                        className="rounded-xl border border-slate-100 bg-white px-3 py-2.5"
                      >
                        <p className="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                          {label}
                        </p>
                        <p className="mt-0.5 text-sm font-bold text-slate-950 tabular-nums">
                          {formatCurrency(numberValue(selected, paths, 0))}
                        </p>
                      </div>
                    ))}
                  </div>

                  {/* Line items */}
                  {toArray<Record<string, unknown>>(selected.items).length > 0 && (
                    <div className="rounded-xl border border-slate-100 bg-white p-3">
                      <p className="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Các khoản
                      </p>
                      <div className="space-y-1.5">
                        {toArray<Record<string, unknown>>(selected.items).map(
                          (item, idx) => (
                            <div
                              key={`${textValue(item, ["code"], String(idx))}-${idx}`}
                              className="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-xs"
                            >
                              <span className="font-medium text-slate-700">
                                {textValue(item, ["code", "name", "label"], "—")}
                              </span>
                              <span className="tabular-nums text-slate-600">
                                {formatCurrency(numberValue(item, ["amount", "value"], 0))}
                              </span>
                            </div>
                          ),
                        )}
                      </div>
                    </div>
                  )}

                  {/* Raw JSON debug */}
                  <div className="rounded-xl bg-slate-950 p-3">
                    <pre className="overflow-x-auto text-[10px] leading-5 text-slate-300">
                      {JSON.stringify(selected, null, 2)}
                    </pre>
                  </div>
                </div>
              ) : (
                <EmptyState
                  title="Không có chi tiết"
                  description="Phiếu lương hiện tại chưa có detail payload."
                />
              )}
            </div>
          )}

          {!selectedId && (
            <div className="rounded-2xl border border-dashed border-slate-200 bg-white/60 px-5 py-8 text-center">
              <p className="text-sm font-medium text-slate-500">
                Chọn một phiếu lương ở bảng bên phải để xem chi tiết.
              </p>
            </div>
          )}
        </div>

        {/* Right column — statements table */}
        <div className="col-span-12 lg:col-span-8">
          <div className="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
            {/* Table header bar */}
            <div className="flex items-center justify-between border-b border-slate-100 px-6 py-4">
              <h3 className="font-semibold text-slate-900">Bảng kê hàng tháng</h3>
              <div className="flex gap-3">
                <div className="flex items-center gap-1.5 rounded-full bg-slate-50 px-3 py-1">
                  <div className="h-2 w-2 rounded-full bg-emerald-500" />
                  <span className="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                    Xác nhận
                  </span>
                </div>
                <div className="flex items-center gap-1.5 rounded-full bg-slate-50 px-3 py-1">
                  <div className="h-2 w-2 rounded-full bg-slate-300" />
                  <span className="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                    Nháp
                  </span>
                </div>
              </div>
            </div>

            {/* Table */}
            <div className="overflow-x-auto">
              {payslipsQuery.isLoading ? (
                <div className="px-6 py-12 text-center">
                  <p className="text-sm text-slate-500">Đang tải phiếu lương...</p>
                </div>
              ) : payslips.length ? (
                <table className="w-full border-collapse text-left">
                  <thead className="bg-slate-50">
                    <tr>
                      {["Nhân viên", "Kỳ", "Gross", "Trạng thái", "Thực lĩnh", "Tải về"].map(
                        (col) => (
                          <th
                            key={col}
                            className={`px-6 py-4 text-[0.6875rem] font-bold uppercase tracking-widest text-slate-500 ${col === "Thực lĩnh" ? "text-right" : col === "Tải về" ? "text-center" : ""}`}
                          >
                            {col}
                          </th>
                        ),
                      )}
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-50">
                    {payslips.map((item, index) => {
                      const id = String(textValue(item, ["id"], String(index)));
                      const selectedRow = id === selectedId;
                      const status = textValue(item, ["status"], "draft");
                      const finalized = isFinalized(status);

                      return (
                        <tr
                          key={`${id}-${index}`}
                          onClick={() => setSelectedId(id)}
                          className={`cursor-pointer transition-colors hover:bg-slate-50/70 ${
                            selectedRow ? "bg-sky-50/60 outline outline-1 -outline-offset-1 outline-sky-200" : ""
                          } ${!finalized ? "opacity-70" : ""}`}
                        >
                          {/* Employee */}
                          <td className="px-6 py-5">
                            <p className={`font-bold ${finalized ? "text-slate-900" : "text-slate-500 italic"}`}>
                              {textValue(
                                item,
                                ["employee.full_name", "employee_name", "full_name"],
                                "Nhân viên",
                              )}
                            </p>
                            <p className="text-xs text-slate-400">
                              {textValue(
                                item,
                                ["employee.employee_code", "employee_code"],
                                "N/A",
                              )}
                            </p>
                          </td>

                          {/* Period */}
                          <td className={`px-6 py-5 text-sm ${finalized ? "text-slate-600" : "italic text-slate-400"}`}>
                            Th
                            {textValue(
                              item,
                              ["payroll_run.attendance_period.month", "month"],
                              "N/A",
                            )}
                            /
                            {textValue(
                              item,
                              ["payroll_run.attendance_period.year", "year"],
                              "N/A",
                            )}
                          </td>

                          {/* Gross */}
                          <td className="px-6 py-5 text-sm tabular-nums text-slate-600">
                            {formatCurrency(numberValue(item, ["gross_salary", "gross_pay"], 0))}
                          </td>

                          {/* Status badge */}
                          <td className="px-6 py-5">
                            <StatusBadge status={status} />
                          </td>

                          {/* Net pay */}
                          <td className={`px-6 py-5 text-right font-bold tabular-nums ${finalized ? "text-slate-900" : "text-slate-400"}`}>
                            {formatCurrency(numberValue(item, ["net_salary", "net_pay"], 0))}
                          </td>

                          {/* Download action */}
                          <td className="px-6 py-5 text-center">
                            {finalized ? (
                              <button
                                type="button"
                                onClick={(e) => {
                                  e.stopPropagation();
                                  // download handler placeholder
                                }}
                                className="inline-flex items-center justify-center rounded-lg p-2 text-indigo-600 transition hover:bg-indigo-50 active:scale-95"
                                title="Tải PDF"
                              >
                                <Download className="h-4 w-4" />
                              </button>
                            ) : (
                              <Lock className="mx-auto h-4 w-4 text-slate-300" aria-label="Chưa sẵn sàng" />
                            )}
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              ) : (
                <div className="px-6 py-12">
                  <EmptyState
                    title="Không có phiếu lương"
                    description="Chưa tìm thấy phiếu lương nào cho kỳ đã chọn."
                  />
                </div>
              )}
            </div>

            {/* Pagination footer */}
            {payslips.length > 0 && (
              <div className="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-6 py-4">
                <span className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                  Hiển thị {payslips.length} phiếu lương
                </span>
                <div className="flex gap-2">
                  <button
                    type="button"
                    disabled
                    className="rounded-lg border border-slate-200 bg-white p-2 text-slate-300 cursor-not-allowed"
                    aria-label="Trang trước"
                  >
                    <ChevronLeft className="h-4 w-4" />
                  </button>
                  <button
                    type="button"
                    className="rounded-lg border border-slate-200 bg-white p-2 text-indigo-600 transition hover:bg-slate-100"
                    aria-label="Trang sau"
                  >
                    <ChevronRight className="h-4 w-4" />
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
