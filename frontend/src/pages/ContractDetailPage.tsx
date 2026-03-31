import { useMemo } from "react";
import { useParams, Link } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import {
  ArrowLeft,
  FileText,
  User,
  Building2,
  Calendar,
  DollarSign,
  CheckCircle2,
  Pencil,
  Printer,
  Info,
  RefreshCw,
  ShieldCheck,
} from "lucide-react";
import { apiGet } from "../lib/api";
import { useAuth } from "../context/AuthContext";
import { formatCurrency, formatDate } from "../lib/format";
import { numberValue, textValue, toArray } from "../lib/records";
import { createPermissionSet, hasPermissionAccess } from "../lib/rbac";
import { Badge, EmptyState, Panel } from "../components/ui";

export default function ContractDetailPage() {
  const { user } = useAuth();
  const { id } = useParams<{ id: string }>();
  const permissionSet = createPermissionSet(user?.permissions);
  const canEditContract = hasPermissionAccess(permissionSet, "contract.update");
  const canRenewContract = hasPermissionAccess(permissionSet, "contract.renew");

  const contractQuery = useQuery({
    queryKey: ["contracts", id],
    queryFn: async () => apiGet<unknown>(`/contracts/${id}`),
    enabled: Boolean(id),
  });

  const contract =
    contractQuery.data?.data && typeof contractQuery.data.data === "object"
      ? (contractQuery.data.data as Record<string, unknown>)
      : null;

  const allowances = useMemo(
    () => toArray<Record<string, unknown>>(contract?.allowances),
    [contract?.allowances],
  );

  const statusTone = (status: string) => {
    const s = status.toLowerCase();
    if (s.includes("active")) return "success" as const;
    if (s.includes("expired") || s.includes("terminated")) return "danger" as const;
    if (s.includes("pending")) return "warning" as const;
    return "neutral" as const;
  };

  // Derived display values
  const employeeName = contract
    ? textValue(contract, ["employee.full_name", "employee_name", "full_name"], "Nhân viên")
    : `Hợp đồng #${id}`;
  const employeeCode = contract
    ? textValue(contract, ["employee.employee_code", "employee_code"], "")
    : "";
  const position = contract
    ? textValue(contract, ["employee.position", "position", "employee.job_title", "job_title"], "")
    : "";
  const department = contract
    ? textValue(contract, ["employee.department.name", "department_name"], "")
    : "";
  const contractNo = contract
    ? textValue(contract, ["contract_no", "number"], `#${id}`)
    : `#${id}`;
  const contractType = contract
    ? textValue(contract, ["contractType.name", "contract_type.name", "contract_type", "type"], "N/A")
    : "N/A";
  const status = contract ? textValue(contract, ["status"], "draft") : "draft";
  const baseSalary = contract ? numberValue(contract, ["base_salary"], 0) : 0;
  const insuranceSalary = contract
    ? numberValue(contract, ["insurance_salary", "social_insurance_salary"], 0)
    : 0;
  const salaryLevel = contract
    ? textValue(contract, ["salaryLevel.name", "salary_level.name", "salary_level"], "")
    : "";
  const salaryCoefficient = contract
    ? textValue(contract, ["salary_coefficient", "coefficient"], "")
    : "";
  const startDate = contract ? textValue(contract, ["start_date"], "") : "";
  const endDate = contract ? textValue(contract, ["end_date"], "") : "";
  const probationEndDate = contract
    ? textValue(contract, ["probation_end_date", "probation_end"], "")
    : "";
  const signedDate = contract ? textValue(contract, ["signed_date", "created_at"], "") : "";
  const notes = contract ? textValue(contract, ["notes", "note"], "") : "";
  const payrollTypeName = contract
    ? textValue(contract, ["payrollType.name", "payroll_type.name", "payroll_type"], "")
    : "";

  const totalAllowances = allowances.reduce(
    (sum, a) => sum + numberValue(a, ["amount", "value"], 0),
    0,
  );

  if (contractQuery.isLoading) {
    return (
      <div className="space-y-6">
        <Panel>
          <p className="text-sm text-slate-500">Đang tải chi tiết hợp đồng...</p>
        </Panel>
      </div>
    );
  }

  if (!contract) {
    return (
      <div className="space-y-6">
        <div className="flex items-center gap-2 text-slate-400">
          <ArrowLeft className="h-4 w-4" />
          <Link
            to="/contracts"
            className="text-xs font-bold uppercase tracking-widest hover:text-slate-700 transition-colors"
          >
            Quay lại danh sách
          </Link>
        </div>
        <EmptyState
          title="Không tìm thấy hợp đồng"
          description={`Hợp đồng #${id} không tồn tại hoặc backend chưa trả dữ liệu.`}
        />
      </div>
    );
  }

  return (
    <div className="space-y-8">
      {/* Header & Action Bar */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div className="space-y-1">
          {/* Back link */}
          <div className="flex items-center gap-2 text-slate-400 mb-2">
            <ArrowLeft className="h-3.5 w-3.5" />
            <Link
              to="/contracts"
              className="text-xs font-bold uppercase tracking-widest hover:text-slate-700 transition-colors"
            >
              Quay lại danh sách
            </Link>
          </div>
          {/* Title */}
          <h1 className="font-[family-name:var(--font-display)] text-3xl font-extrabold tracking-tight text-slate-950">
            Chi tiết Hợp đồng &mdash; {employeeName}{" "}
            {employeeCode && (
              <span className="text-slate-400 font-normal">({employeeCode})</span>
            )}
          </h1>
          {/* Subtitle */}
          <p className="text-slate-500 text-sm">
            {[position, department].filter(Boolean).join(" · ") ||
              "Xem điều khoản hợp đồng và lịch sử lao động"}
          </p>
        </div>

        {/* Action buttons */}
        <div className="flex items-center gap-3 shrink-0">
          <button className="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-slate-500 hover:text-slate-800 transition-all bg-white rounded-xl border border-slate-200 shadow-sm active:scale-95">
            <Printer className="h-4 w-4" />
            Tải PDF
          </button>
          {canEditContract && (
            <button className="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-slate-500 hover:text-slate-800 transition-all bg-white rounded-xl border border-slate-200 shadow-sm active:scale-95">
              <Pencil className="h-4 w-4" />
              Chỉnh sửa
            </button>
          )}
          {canRenewContract && (
            <button className="flex items-center gap-2 px-6 py-2 text-sm font-bold text-white rounded-xl bg-gradient-to-br from-slate-800 to-slate-950 shadow-md active:scale-95 transition-all hover:from-slate-700 hover:to-slate-900">
              Gia hạn hợp đồng
            </button>
          )}
        </div>
      </div>

      {/* Bento Grid */}
      <div className="grid grid-cols-12 gap-6">
        {/* Left Column — Summary + Dates */}
        <div className="col-span-12 lg:col-span-7 space-y-6">
          {/* Summary Card */}
          <section className="rounded-2xl bg-white/80 backdrop-blur border border-white/70 shadow-[0_18px_40px_rgba(15,23,42,0.06)] p-8 border-l-4 border-l-emerald-500">
            <h2 className="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
              <Info className="h-4 w-4" />
              Tóm tắt hợp đồng
            </h2>
            <div className="grid grid-cols-2 gap-y-8 gap-x-6">
              {/* Contract Type */}
              <div>
                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                  Loại hợp đồng
                </label>
                <p className="text-lg font-semibold text-slate-900">{contractType}</p>
              </div>

              {/* Status */}
              <div>
                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                  Trạng thái
                </label>
                <Badge tone={statusTone(status)} className="inline-flex items-center gap-1.5">
                  <CheckCircle2 className="h-3 w-3" />
                  {status}
                </Badge>
              </div>

              {/* Base Salary */}
              <div>
                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                  Lương cơ bản
                </label>
                <p className="text-2xl font-extrabold text-slate-950 tabular-nums">
                  {formatCurrency(baseSalary)}{" "}
                  <span className="text-sm font-normal text-slate-400">/ tháng</span>
                </p>
              </div>

              {/* Contract No */}
              <div>
                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                  Số hợp đồng
                </label>
                <p className="text-lg font-semibold text-slate-900">{contractNo}</p>
              </div>

              {/* Payroll Type */}
              {payrollTypeName && (
                <div>
                  <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    Loại lương
                  </label>
                  <p className="text-base font-semibold text-slate-900">{payrollTypeName}</p>
                </div>
              )}

              {/* Salary Level */}
              {salaryLevel && (
                <div>
                  <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    Bậc lương
                  </label>
                  <p className="text-base font-semibold text-slate-900">
                    {salaryLevel}
                    {salaryCoefficient && (
                      <span className="ml-2 text-sm font-normal text-slate-400">
                        (HS: {salaryCoefficient})
                      </span>
                    )}
                  </p>
                </div>
              )}
            </div>

            {/* Notes row — spans full width if present */}
            {notes && (
              <div className="mt-6 pt-5 border-t border-slate-100">
                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                  Ghi chú
                </label>
                <p className="text-sm text-slate-600">{notes}</p>
              </div>
            )}
          </section>

          {/* Key Dates Card */}
          <section className="rounded-2xl bg-white/80 backdrop-blur border border-white/70 shadow-[0_18px_40px_rgba(15,23,42,0.06)] p-8">
            <h2 className="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
              <Calendar className="h-4 w-4" />
              Các mốc thời gian
            </h2>
            <div className="flex flex-col md:flex-row items-stretch gap-4">
              {/* Start Date */}
              <div className="flex-1 p-4 bg-slate-50 rounded-xl border-b-2 border-sky-500">
                <div className="flex items-start justify-between">
                  <div>
                    <p className="text-[10px] font-bold text-sky-600 uppercase tracking-wider mb-1">
                      Ngày bắt đầu
                    </p>
                    <p className="text-xl font-bold text-slate-900 tabular-nums">
                      {startDate ? formatDate(startDate) : "Chưa có"}
                    </p>
                  </div>
                  <Calendar className="h-5 w-5 text-sky-300 mt-0.5" />
                </div>
                {signedDate && (
                  <p className="text-xs text-slate-500 mt-2">
                    Ký ngày: {formatDate(signedDate)}
                  </p>
                )}
              </div>

              {/* Probation End (if any) */}
              {probationEndDate && (
                <div className="flex-1 p-4 bg-slate-50 rounded-xl border-b-2 border-indigo-500">
                  <div className="flex items-start justify-between">
                    <div>
                      <p className="text-[10px] font-bold text-indigo-600 uppercase tracking-wider mb-1">
                        Kết thúc thử việc
                      </p>
                      <p className="text-xl font-bold text-slate-900 tabular-nums">
                        {formatDate(probationEndDate)}
                      </p>
                    </div>
                    <ShieldCheck className="h-5 w-5 text-indigo-300 mt-0.5" />
                  </div>
                  <p className="text-xs text-slate-500 mt-2">Hoàn thành thử việc</p>
                </div>
              )}

              {/* End Date */}
              <div className="flex-1 p-4 bg-slate-50 rounded-xl border-b-2 border-emerald-500">
                <div className="flex items-start justify-between">
                  <div>
                    <p className="text-[10px] font-bold text-emerald-600 uppercase tracking-wider mb-1">
                      Ngày kết thúc
                    </p>
                    <p className="text-xl font-bold text-slate-900 tabular-nums">
                      {endDate ? formatDate(endDate) : "Không xác định"}
                    </p>
                  </div>
                  <RefreshCw className="h-5 w-5 text-emerald-300 mt-0.5" />
                </div>
                <p className="text-xs text-slate-500 mt-2">
                  {endDate ? "Hợp đồng xác định thời hạn" : "Hợp đồng không xác định thời hạn"}
                </p>
              </div>
            </div>
          </section>
        </div>

        {/* Right Column — Allowances + Insurance */}
        <div className="col-span-12 lg:col-span-5 space-y-6">
          {/* Allowances */}
          <section className="rounded-2xl bg-white/80 backdrop-blur border border-white/70 shadow-[0_18px_40px_rgba(15,23,42,0.06)] p-8">
            <h2 className="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
              <DollarSign className="h-4 w-4" />
              Phụ cấp ({allowances.length} khoản)
            </h2>

            {allowances.length > 0 ? (
              <div className="space-y-3">
                {allowances.map((item, index) => (
                  <div
                    key={`${textValue(item, ["name"], String(index))}-${index}`}
                    className="group flex items-center justify-between p-4 rounded-xl bg-slate-50 border border-transparent hover:border-slate-200 hover:bg-slate-100/60 transition-all"
                  >
                    <div className="flex items-center gap-3">
                      <div className="w-9 h-9 rounded-lg bg-sky-50 flex items-center justify-center text-sky-600 shrink-0">
                        <DollarSign className="h-4 w-4" />
                      </div>
                      <div>
                        <p className="text-sm font-semibold text-slate-800">
                          {textValue(item, ["allowanceType.name", "name", "label"], "Phụ cấp")}
                        </p>
                        <p className="text-[10px] text-slate-400">
                          {textValue(item, ["allowanceType.code", "code"], "")}
                        </p>
                      </div>
                    </div>
                    <span className="text-sm font-bold text-slate-950 tabular-nums">
                      {formatCurrency(numberValue(item, ["amount", "value"], 0))}
                    </span>
                  </div>
                ))}
                {/* Total row */}
                <div className="flex items-center justify-between p-4 rounded-xl bg-sky-50 ring-1 ring-sky-200 mt-2">
                  <span className="text-sm font-semibold text-sky-800">Tổng phụ cấp</span>
                  <span className="text-sm font-bold text-sky-900 tabular-nums">
                    {formatCurrency(totalAllowances)}
                  </span>
                </div>
              </div>
            ) : (
              <EmptyState
                title="Không có phụ cấp"
                description="Hợp đồng này chưa có khoản phụ cấp nào."
              />
            )}
          </section>

          {/* Insurance & Salary Details */}
          <section className="rounded-2xl bg-white/80 backdrop-blur border border-white/70 shadow-[0_18px_40px_rgba(15,23,42,0.06)] p-8">
            <h2 className="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
              <FileText className="h-4 w-4" />
              Thông tin lương & BHXH
            </h2>
            <div className="space-y-3">
              {[
                {
                  icon: <User className="h-4 w-4 text-slate-500" />,
                  label: "Nhân viên",
                  value: employeeName,
                },
                {
                  icon: <Building2 className="h-4 w-4 text-slate-500" />,
                  label: "Phòng ban",
                  value: department || "N/A",
                },
                {
                  icon: <DollarSign className="h-4 w-4 text-slate-500" />,
                  label: "Lương cơ bản",
                  value: formatCurrency(baseSalary),
                },
                {
                  icon: <DollarSign className="h-4 w-4 text-slate-500" />,
                  label: "Lương BHXH",
                  value: insuranceSalary > 0 ? formatCurrency(insuranceSalary) : "N/A",
                },
              ].map(({ icon, label, value }) => (
                <div
                  key={label}
                  className="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3"
                >
                  <div className="flex items-center gap-2">
                    {icon}
                    <span className="text-sm font-medium text-slate-600">{label}</span>
                  </div>
                  <span className="text-sm font-semibold text-slate-900 tabular-nums">{value}</span>
                </div>
              ))}
            </div>
          </section>
        </div>
      </div>

      {/* Footer Meta */}
      <div className="flex items-center justify-between pt-6 border-t border-slate-100 text-[10px] text-slate-400 font-medium uppercase tracking-widest">
        <div className="flex gap-4">
          <span>Hợp đồng: {contractNo}</span>
          {signedDate && <span>Ký: {formatDate(signedDate)}</span>}
        </div>
        <Link
          to="/contracts"
          className="flex items-center gap-1.5 hover:text-slate-700 transition-colors"
        >
          <ArrowLeft className="h-3 w-3" />
          Về danh sách hợp đồng
        </Link>
      </div>
    </div>
  );
}
