import { useMemo, useState } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import {
  Database,
  Play,
  RefreshCcw,
  Settings2,
} from "lucide-react";
import { apiGet, apiPost, getApiErrorMessage } from "../lib/api";
import { formatDateTime, formatNumber } from "../lib/format";
import { Badge, EmptyState, PageHeader, Panel } from "../components/ui";

// ---------------------------------------------------------------
// Types
// ---------------------------------------------------------------

interface ProcedureListItem {
  code: string;
  label: string;
  module: string;
  description: string | null;
  param_count: number;
  column_count: number;
}

interface ParamMeta {
  name: string;
  sp_param_name: string;
  type: string;
  label: string | null;
  required: boolean;
  default: string | null;
}

interface ColumnMeta {
  key: string;
  label: string;
  type: string;
  visible: boolean;
  exportable: boolean;
}

interface ProcedureMeta {
  code: string;
  label: string;
  procedure: string;
  module: string;
  description: string | null;
  params: ParamMeta[];
  columns: ColumnMeta[];
}

interface ExecuteResult {
  procedure_code: string;
  procedure_label: string;
  columns: { key: string; label: string; type: string }[];
  records: Record<string, unknown>[];
  row_count: number;
  execution_ms: number;
  generated_at: string;
}

// ---------------------------------------------------------------
// Component
// ---------------------------------------------------------------

export default function ProceduresPage() {
  const [selectedCode, setSelectedCode] = useState<string>("");
  const [formValues, setFormValues] = useState<Record<string, string>>({});
  const [result, setResult] = useState<ExecuteResult | null>(null);
  const [error, setError] = useState<string | null>(null);

  // Fetch procedure list
  const listQuery = useQuery({
    queryKey: ["procedures"],
    queryFn: () => apiGet<ProcedureListItem[]>("/procedures"),
  });

  const procedures = useMemo(
    () => (Array.isArray(listQuery.data?.data) ? listQuery.data!.data : []),
    [listQuery.data],
  );

  // Fetch meta for selected procedure
  const metaQuery = useQuery({
    queryKey: ["procedures", selectedCode, "meta"],
    queryFn: () => apiGet<ProcedureMeta>(`/procedures/${selectedCode}/meta`),
    enabled: !!selectedCode,
  });

  const meta = metaQuery.data?.data ?? null;

  // Build initial form defaults when meta changes
  const paramDefaults = useMemo(() => {
    if (!meta) return {};
    const defaults: Record<string, string> = {};
    for (const p of meta.params) {
      if (p.type === "date" && !p.default) {
        // Smart defaults: first day of month / today
        const now = new Date();
        if (p.name.includes("from")) {
          defaults[p.name] = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10);
        } else if (p.name.includes("to")) {
          defaults[p.name] = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10);
        } else {
          defaults[p.name] = now.toISOString().slice(0, 10);
        }
      } else {
        defaults[p.name] = p.default ?? "";
      }
    }
    return defaults;
  }, [meta]);

  // When a new procedure is selected, reset form
  const handleSelect = (code: string) => {
    setSelectedCode(code);
    setFormValues({});
    setResult(null);
    setError(null);
  };

  const getFieldValue = (name: string) => formValues[name] ?? paramDefaults[name] ?? "";

  const setField = (name: string, value: string) => {
    setFormValues((prev) => ({ ...prev, [name]: value }));
  };

  // Execute mutation
  const executeMutation = useMutation({
    mutationFn: async () => {
      const body: Record<string, unknown> = {};
      if (meta) {
        for (const p of meta.params) {
          const val = getFieldValue(p.name);
          if (val !== "") body[p.name] = val;
        }
      }
      return apiPost<ExecuteResult>(`/procedures/${selectedCode}/execute`, body);
    },
    onSuccess: (response) => {
      setError(null);
      setResult((response.data ?? null) as ExecuteResult | null);
    },
    onError: (err) => {
      setError(getApiErrorMessage(err, "Lỗi khi thực thi stored procedure."));
    },
  });

  // Visible columns for table
  const visibleColumns = result?.columns ?? [];

  // ---------------------------------------------------------------
  // Render helpers
  // ---------------------------------------------------------------

  const renderParamInput = (p: ParamMeta) => {
    const value = getFieldValue(p.name);
    const label = p.label || p.name;

    const inputClass =
      "w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100";

    if (p.type === "date") {
      return (
        <label key={p.name} className="space-y-2">
          <span className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
            {label} {p.required && <span className="text-rose-500">*</span>}
          </span>
          <input
            type="date"
            value={value}
            onChange={(e) => setField(p.name, e.target.value)}
            className={inputClass}
          />
        </label>
      );
    }

    if (p.type === "tinyint" || p.type === "integer") {
      return (
        <label key={p.name} className="space-y-2">
          <span className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
            {label} {p.required && <span className="text-rose-500">*</span>}
          </span>
          <input
            type="number"
            value={value}
            onChange={(e) => setField(p.name, e.target.value)}
            className={inputClass}
          />
        </label>
      );
    }

    // Default: text/string
    return (
      <label key={p.name} className="space-y-2">
        <span className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
          {label} {p.required && <span className="text-rose-500">*</span>}
        </span>
        <input
          type="text"
          value={value}
          placeholder={p.default ?? ""}
          onChange={(e) => setField(p.name, e.target.value)}
          className={inputClass}
        />
      </label>
    );
  };

  const formatCellValue = (value: unknown, type: string): string => {
    if (value === null || value === undefined) return "—";
    if (type === "number") return formatNumber(value as number);
    if (type === "date") {
      const d = new Date(String(value));
      if (!isNaN(d.getTime())) return d.toLocaleDateString("vi-VN");
    }
    return String(value);
  };

  // ---------------------------------------------------------------
  // Page Render
  // ---------------------------------------------------------------

  return (
    <div className="space-y-8">
      <PageHeader
        eyebrow="SQL Integration"
        title="Stored Procedures"
        description="Thực thi stored procedures từ SQL Server. Khách tự quản lý SQL, hệ thống gọi theo cấu hình metadata."
        actions={
          <button
            type="button"
            onClick={() => listQuery.refetch()}
            className="inline-flex items-center gap-2 rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
          >
            <RefreshCcw className="h-3.5 w-3.5" />
            Làm mới
          </button>
        }
      />

      {/* Procedure list cards */}
      <section className="space-y-4">
        <h2 className="text-base font-semibold text-slate-900">Chọn Stored Procedure</h2>

        {listQuery.isLoading ? (
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="h-32 animate-pulse rounded-2xl border border-slate-200 bg-slate-100" />
            ))}
          </div>
        ) : procedures.length === 0 ? (
          <EmptyState
            title="Chưa có procedure nào"
            description="Đăng ký stored procedure trong bảng procedure_catalog."
          />
        ) : (
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {procedures.map((proc) => {
              const isSelected = selectedCode === proc.code;
              return (
                <button
                  key={proc.code}
                  type="button"
                  onClick={() => handleSelect(proc.code)}
                  className={[
                    "group relative rounded-2xl border-l-4 p-5 text-left transition-all duration-150",
                    "border-l-indigo-500",
                    isSelected
                      ? "border border-sky-300 bg-sky-50/80 shadow-[0_16px_30px_rgba(14,165,233,0.14)] ring-1 ring-sky-200"
                      : "border border-white/70 bg-white/80 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur hover:border-slate-200 hover:shadow-[0_18px_40px_rgba(15,23,42,0.10)]",
                  ].join(" ")}
                >
                  <div className="flex items-start gap-3">
                    <span className={`flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl ${isSelected ? "bg-sky-100 text-sky-600" : "bg-indigo-50 text-indigo-600"}`}>
                      <Database className="h-5 w-5" />
                    </span>
                    <div className="min-w-0">
                      <p className="font-semibold text-slate-950 leading-snug truncate">{proc.label}</p>
                      <p className="mt-1 text-xs text-slate-500">{proc.code}</p>
                    </div>
                  </div>
                  {proc.description && (
                    <p className="mt-3 text-sm leading-6 text-slate-500 line-clamp-2">{proc.description}</p>
                  )}
                  <div className="mt-3 flex gap-2">
                    <Badge tone="neutral">{proc.param_count} tham số</Badge>
                    <Badge tone="neutral">{proc.column_count} cột</Badge>
                    <Badge tone="accent">{proc.module}</Badge>
                  </div>
                </button>
              );
            })}
          </div>
        )}
      </section>

      {/* Parameter form + result table */}
      {selectedCode && (
        <div className="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
          {/* Left: Parameters */}
          <Panel
            title="Tham số thực thi"
            subtitle={
              meta
                ? `Procedure: ${meta.procedure}`
                : "Đang tải metadata..."
            }
          >
            {metaQuery.isLoading ? (
              <div className="h-40 animate-pulse rounded-2xl bg-slate-100" />
            ) : meta ? (
              <div className="space-y-5">
                {/* Dynamic parameter fields */}
                <div className="grid gap-4 sm:grid-cols-2">
                  {meta.params
                    .filter((p) => !["user_id"].includes(p.name))
                    .map(renderParamInput)}
                </div>

                {/* SP metadata info */}
                <div className="rounded-xl border border-slate-100 bg-slate-50/80 px-4 py-3">
                  <div className="flex items-center gap-2">
                    <Settings2 className="h-4 w-4 text-slate-400" />
                    <p className="text-xs text-slate-500">
                      SP: <code className="font-mono text-slate-600">{meta.procedure}</code>
                      {" · "}
                      {meta.params.length} tham số
                      {" · "}
                      {meta.columns.length} cột kết quả
                    </p>
                  </div>
                </div>

                {/* Error */}
                {error && (
                  <p className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {error}
                  </p>
                )}

                {/* Execute button */}
                <button
                  type="button"
                  onClick={() => executeMutation.mutate()}
                  disabled={executeMutation.isPending}
                  className="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                >
                  {executeMutation.isPending ? (
                    <RefreshCcw className="h-4 w-4 animate-spin" />
                  ) : (
                    <Play className="h-4 w-4" />
                  )}
                  Thực thi
                </button>
              </div>
            ) : (
              <EmptyState
                title="Không tải được metadata"
                description="Procedure có thể đã bị tắt hoặc không tồn tại."
              />
            )}
          </Panel>

          {/* Right: Result */}
          <Panel
            title="Kết quả thực thi"
            subtitle={
              result
                ? `${formatNumber(result.row_count)} dòng · ${result.execution_ms}ms`
                : "Chưa có kết quả"
            }
          >
            {result ? (
              <div className="space-y-4">
                {/* Summary metrics */}
                <div className="grid gap-3 sm:grid-cols-3">
                  <div className="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                    <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">Procedure</p>
                    <p className="mt-1 text-sm font-semibold text-slate-900">{result.procedure_label}</p>
                  </div>
                  <div className="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                    <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">Số dòng</p>
                    <p className="mt-1 text-sm font-semibold text-slate-900">{formatNumber(result.row_count)}</p>
                  </div>
                  <div className="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                    <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">Thời gian</p>
                    <p className="mt-1 text-sm font-semibold text-slate-900">{result.execution_ms}ms</p>
                  </div>
                </div>

                <p className="text-xs text-slate-400">
                  Tạo lúc: <span className="font-medium text-slate-600">{formatDateTime(result.generated_at)}</span>
                </p>

                {/* Data table */}
                {visibleColumns.length > 0 && result.records.length > 0 ? (
                  <div className="overflow-x-auto rounded-2xl border border-slate-200">
                    <table className="w-full border-collapse text-left">
                      <thead>
                        <tr className="bg-slate-50 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                          {visibleColumns.map((col) => (
                            <th key={col.key} className="px-4 py-3 whitespace-nowrap">
                              {col.label}
                            </th>
                          ))}
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-50">
                        {result.records.slice(0, 100).map((row, idx) => (
                          <tr key={idx} className="hover:bg-slate-50/60 transition-colors">
                            {visibleColumns.map((col) => (
                              <td
                                key={col.key}
                                className={`px-4 py-3 text-sm whitespace-nowrap ${col.type === "number" ? "text-right tabular-nums" : ""}`}
                              >
                                {formatCellValue(row[col.key], col.type)}
                              </td>
                            ))}
                          </tr>
                        ))}
                      </tbody>
                    </table>
                    {result.records.length > 100 && (
                      <div className="border-t border-slate-100 bg-slate-50/60 px-4 py-2 text-center">
                        <p className="text-xs text-slate-500">
                          Hiển thị 100 / {formatNumber(result.records.length)} dòng
                        </p>
                      </div>
                    )}
                  </div>
                ) : result.records.length === 0 ? (
                  <EmptyState
                    title="Không có dữ liệu"
                    description="Stored procedure trả về 0 dòng với bộ tham số hiện tại."
                  />
                ) : null}

                {/* Raw JSON toggle */}
                <details className="group">
                  <summary className="cursor-pointer text-xs font-semibold text-slate-400 hover:text-slate-600 transition">
                    Xem JSON thô
                  </summary>
                  <div className="mt-2 rounded-2xl overflow-hidden border border-slate-800">
                    <div className="flex items-center justify-between border-b border-slate-700 bg-slate-900 px-4 py-2">
                      <span className="text-xs font-semibold text-slate-400">JSON Response</span>
                      <span className="text-xs text-slate-500">{selectedCode}</span>
                    </div>
                    <pre className="overflow-x-auto bg-slate-950 p-4 text-xs leading-6 text-slate-300 max-h-72">
                      {JSON.stringify(result, null, 2)}
                    </pre>
                  </div>
                </details>
              </div>
            ) : (
              <EmptyState
                title="Chưa thực thi"
                description="Điền tham số và bấm Thực thi để gọi stored procedure."
              />
            )}
          </Panel>
        </div>
      )}

      {/* Bottom: procedure catalog table */}
      <section className="rounded-3xl border border-white/70 bg-white/80 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur overflow-hidden">
        <div className="flex items-center justify-between border-b border-slate-100 px-6 py-5">
          <div>
            <h2 className="text-base font-semibold text-slate-900">Danh mục Stored Procedures</h2>
            <p className="mt-0.5 text-sm text-slate-500">{procedures.length} procedure đã đăng ký</p>
          </div>
          <button
            type="button"
            onClick={() => listQuery.refetch()}
            className="text-sm font-semibold text-sky-600 hover:text-sky-800 transition-colors"
          >
            Tải lại
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full border-collapse text-left">
            <thead>
              <tr className="bg-slate-50 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                <th className="px-6 py-4">Tên</th>
                <th className="px-5 py-4">Module</th>
                <th className="px-5 py-4">Code</th>
                <th className="px-5 py-4">Tham số</th>
                <th className="px-5 py-4">Cột</th>
                <th className="px-6 py-4 text-right">Hành động</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-50">
              {procedures.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-6 py-10 text-center text-sm text-slate-400">
                    Chưa có procedure nào trong catalog.
                  </td>
                </tr>
              ) : (
                procedures.map((proc) => {
                  const isSelected = selectedCode === proc.code;
                  return (
                    <tr
                      key={proc.code}
                      className={`group transition-colors ${isSelected ? "bg-sky-50/60" : "hover:bg-slate-50/80"}`}
                    >
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-3">
                          <span className={`flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg ${isSelected ? "bg-sky-100 text-sky-600" : "bg-indigo-50 text-indigo-600"}`}>
                            <Database className="h-4 w-4" />
                          </span>
                          <span className="font-semibold text-slate-900">{proc.label}</span>
                        </div>
                      </td>
                      <td className="px-5 py-4">
                        <Badge tone="accent">{proc.module}</Badge>
                      </td>
                      <td className="px-5 py-4">
                        <code className="rounded-md bg-slate-100 px-2 py-1 text-xs font-mono text-slate-600">
                          {proc.code}
                        </code>
                      </td>
                      <td className="px-5 py-4 text-sm text-slate-700">{proc.param_count}</td>
                      <td className="px-5 py-4 text-sm text-slate-700">{proc.column_count}</td>
                      <td className="px-6 py-4 text-right">
                        <button
                          type="button"
                          onClick={() => handleSelect(proc.code)}
                          className={`inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition ${
                            isSelected
                              ? "bg-sky-600 text-white"
                              : "text-slate-600 hover:bg-slate-100"
                          }`}
                        >
                          <Play className="h-3.5 w-3.5" />
                          {isSelected ? "Đang chọn" : "Chọn"}
                        </button>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </section>
    </div>
  );
}
