import { useEffect, useMemo, useState } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import {
  BarChart3,
  Building2,
  CalendarDays,
  ChevronDown,
  Download,
  FileSearch2,
  FileText,
  Filter,
  PieChart,
  Receipt,
  RefreshCcw,
  Table2,
  TrendingUp,
  Users,
  Wallet,
} from "lucide-react";
import { apiGet, apiPost, getApiErrorMessage } from "../lib/api";
import { formatDateTime, formatNumber } from "../lib/format";
import { numberValue, textValue, toArray } from "../lib/records";
import { Badge, EmptyState, PageHeader, Panel } from "../components/ui";

type Template = Record<string, unknown>;
type Department = Record<string, unknown>;

// Map report category/code to a lucide icon
function TemplateIcon({ category, code }: { category: string; code: string }) {
  const key = (code + category).toLowerCase();
  if (key.includes("payroll") || key.includes("salary") || key.includes("luong"))
    return <Wallet className="h-5 w-5" />;
  if (key.includes("attendance") || key.includes("cham_cong") || key.includes("checkin"))
    return <CalendarDays className="h-5 w-5" />;
  if (key.includes("employee") || key.includes("nhan_vien"))
    return <Users className="h-5 w-5" />;
  if (key.includes("insurance") || key.includes("bhxh") || key.includes("tax") || key.includes("pit"))
    return <Receipt className="h-5 w-5" />;
  if (key.includes("department") || key.includes("phong"))
    return <Building2 className="h-5 w-5" />;
  if (key.includes("summary") || key.includes("overview") || key.includes("trend"))
    return <TrendingUp className="h-5 w-5" />;
  if (key.includes("chart") || key.includes("pie") || key.includes("donut"))
    return <PieChart className="h-5 w-5" />;
  if (key.includes("table") || key.includes("list"))
    return <Table2 className="h-5 w-5" />;
  if (key.includes("bar") || key.includes("stat"))
    return <BarChart3 className="h-5 w-5" />;
  return <FileText className="h-5 w-5" />;
}

// Category color accent for the left border on cards
function categoryAccent(category: string): string {
  switch (category.toLowerCase()) {
    case "payroll":
    case "financial":
      return "border-l-sky-500";
    case "attendance":
    case "hr":
      return "border-l-indigo-500";
    case "insurance":
    case "tax":
    case "compliance":
      return "border-l-emerald-500";
    case "employee":
      return "border-l-violet-500";
    default:
      return "border-l-slate-400";
  }
}

function categoryIconBg(category: string): string {
  switch (category.toLowerCase()) {
    case "payroll":
    case "financial":
      return "bg-sky-50 text-sky-600";
    case "attendance":
    case "hr":
      return "bg-indigo-50 text-indigo-600";
    case "insurance":
    case "tax":
    case "compliance":
      return "bg-emerald-50 text-emerald-600";
    case "employee":
      return "bg-violet-50 text-violet-600";
    default:
      return "bg-slate-100 text-slate-500";
  }
}

export default function ReportsPage() {
  const [selectedCode, setSelectedCode] = useState<string>("");
  const [form, setForm] = useState({
    month: String(new Date().getMonth() + 1),
    year: String(new Date().getFullYear()),
    department_id: "",
    active_status: "",
    format: "xlsx",
  });
  const [preview, setPreview] = useState<Record<string, unknown> | null>(null);
  const [exportResult, setExportResult] = useState<Record<string, unknown> | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [categoryFilter, setCategoryFilter] = useState<string>("all");

  const templatesQuery = useQuery({
    queryKey: ["reports", "templates"],
    queryFn: async () => apiGet<unknown>("/reports/templates"),
  });

  const departmentsQuery = useQuery({
    queryKey: ["reference", "departments"],
    queryFn: async () => apiGet<unknown>("/reference/departments"),
  });

  const templates = useMemo(() => toArray<Template>(templatesQuery.data?.data), [templatesQuery.data?.data]);
  const departments = useMemo(() => toArray<Department>(departmentsQuery.data?.data), [departmentsQuery.data?.data]);
  const selectedTemplate = templates.find((t) => textValue(t, ["code"], "") === selectedCode) ?? templates[0] ?? null;
  const selectedParams = useMemo(() => toArray<Record<string, unknown>>(selectedTemplate?.parameters), [selectedTemplate]);

  useEffect(() => {
    if (!selectedCode && selectedTemplate) {
      setSelectedCode(textValue(selectedTemplate, ["code"], ""));
    }
  }, [selectedCode, selectedTemplate]);

  const previewMutation = useMutation({
    mutationFn: async () =>
      apiPost<unknown>(`/reports/${selectedCode}/preview`, {
        month: Number(form.month),
        year: Number(form.year),
        ...(form.department_id ? { department_id: Number(form.department_id) } : {}),
        ...(form.active_status ? { active_status: form.active_status } : {}),
      }),
    onSuccess: (response) => {
      setError(null);
      setPreview((response.data ?? {}) as Record<string, unknown>);
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể preview report."));
    },
  });

  const exportMutation = useMutation({
    mutationFn: async () =>
      apiPost<unknown>(`/reports/${selectedCode}/export`, {
        format: form.format,
        month: Number(form.month),
        year: Number(form.year),
        ...(form.department_id ? { department_id: Number(form.department_id) } : {}),
        ...(form.active_status ? { active_status: form.active_status } : {}),
      }),
    onSuccess: (response) => {
      setError(null);
      setExportResult((response.data ?? {}) as Record<string, unknown>);
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể export report."));
    },
  });

  const templateCards = templates.map((template) => ({
    code: textValue(template, ["code"], ""),
    title: textValue(template, ["name", "title"], "Report"),
    description: textValue(template, ["description"], ""),
    category: textValue(template, ["category"], "general"),
    formats: toArray<string>(template.export_formats),
  }));

  // Derive unique categories for filter tabs
  const categories = useMemo(() => {
    const cats = Array.from(new Set(templateCards.map((t) => t.category)));
    return cats;
  }, [templateCards]);

  const filteredCards = categoryFilter === "all"
    ? templateCards
    : templateCards.filter((t) => t.category === categoryFilter);

  const MONTHS = [
    "Tháng 1", "Tháng 2", "Tháng 3", "Tháng 4", "Tháng 5", "Tháng 6",
    "Tháng 7", "Tháng 8", "Tháng 9", "Tháng 10", "Tháng 11", "Tháng 12",
  ];

  return (
    <div className="space-y-8">
      {/* Page header */}
      <div className="flex flex-col gap-4 border-b border-white/60 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p className="text-xs font-bold uppercase tracking-[0.24em] text-slate-500">Báo cáo</p>
          <h1 className="mt-2 font-[family-name:var(--font-display)] text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
            Reports Center
          </h1>
          <p className="mt-2 text-sm leading-6 text-slate-500">
            Chọn template, thiết lập tham số, preview nhanh hoặc export file.
          </p>
        </div>

        {/* Filter bar — matches Stitch header controls */}
        <div className="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200/80 bg-slate-50/80 p-1.5 backdrop-blur">
          {/* Month/Year pill */}
          <div className="flex items-center gap-2 rounded-xl bg-white px-4 py-2 shadow-sm">
            <CalendarDays className="h-4 w-4 text-slate-400" />
            <span className="text-sm font-medium text-slate-700">
              {MONTHS[Number(form.month) - 1]} {form.year}
            </span>
          </div>
          {/* Department filter pill */}
          <div className="flex items-center gap-2 rounded-xl bg-white px-4 py-2 shadow-sm">
            <Filter className="h-4 w-4 text-slate-400" />
            <span className="text-sm font-medium text-slate-700">
              {form.department_id
                ? (departments.find((d) => textValue(d, ["id"], "") === form.department_id)
                    ? textValue(departments.find((d) => textValue(d, ["id"], "") === form.department_id)!, ["name"], "Phòng ban")
                    : "Phòng ban")
                : "Tất cả phòng ban"}
            </span>
            <ChevronDown className="h-3.5 w-3.5 text-slate-400" />
          </div>
          <button
            type="button"
            onClick={() => templatesQuery.refetch()}
            className="flex items-center gap-2 rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
          >
            <RefreshCcw className="h-3.5 w-3.5" />
            Làm mới
          </button>
        </div>
      </div>

      {/* Template Report Cards — category filter tabs + card grid */}
      <section className="space-y-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <h2 className="text-base font-semibold text-slate-900">Chọn báo cáo</h2>
          {/* Category tab filters */}
          {categories.length > 0 && (
            <div className="flex flex-wrap gap-1.5">
              <button
                type="button"
                onClick={() => setCategoryFilter("all")}
                className={`rounded-full px-3.5 py-1.5 text-xs font-semibold transition ${
                  categoryFilter === "all"
                    ? "bg-slate-950 text-white shadow-sm"
                    : "bg-slate-100 text-slate-600 hover:bg-slate-200"
                }`}
              >
                Tất cả
              </button>
              {categories.map((cat) => (
                <button
                  key={cat}
                  type="button"
                  onClick={() => setCategoryFilter(cat)}
                  className={`rounded-full px-3.5 py-1.5 text-xs font-semibold capitalize transition ${
                    categoryFilter === cat
                      ? "bg-slate-950 text-white shadow-sm"
                      : "bg-slate-100 text-slate-600 hover:bg-slate-200"
                  }`}
                >
                  {cat}
                </button>
              ))}
            </div>
          )}
        </div>

        {templatesQuery.isLoading ? (
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {[1, 2, 3].map((i) => (
              <div
                key={i}
                className="h-36 animate-pulse rounded-2xl border border-slate-200 bg-slate-100"
              />
            ))}
          </div>
        ) : filteredCards.length === 0 ? (
          <EmptyState
            title="Không có report template"
            description="Nếu service templates chưa sẵn sàng, page vẫn giữ khung."
          />
        ) : (
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {filteredCards.map((template) => {
              const isSelected = selectedCode === template.code;
              return (
                <button
                  key={template.code}
                  type="button"
                  onClick={() => setSelectedCode(template.code)}
                  className={[
                    "group relative rounded-2xl border-l-4 p-5 text-left transition-all duration-150",
                    categoryAccent(template.category),
                    isSelected
                      ? "border border-sky-300 bg-sky-50/80 shadow-[0_16px_30px_rgba(14,165,233,0.14)] ring-1 ring-sky-200"
                      : "border border-white/70 bg-white/80 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur hover:border-slate-200 hover:shadow-[0_18px_40px_rgba(15,23,42,0.10)]",
                  ].join(" ")}
                >
                  <div className="flex items-start justify-between gap-3">
                    <div className="flex items-center gap-3">
                      <span
                        className={`flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl ${
                          isSelected ? "bg-sky-100 text-sky-600" : categoryIconBg(template.category)
                        }`}
                      >
                        <TemplateIcon category={template.category} code={template.code} />
                      </span>
                      <p className="font-semibold text-slate-950 leading-snug">{template.title}</p>
                    </div>
                    <Badge tone={isSelected ? "info" : "neutral"}>{template.category}</Badge>
                  </div>
                  {template.description && (
                    <p className="mt-3 text-sm leading-6 text-slate-500 line-clamp-2">{template.description}</p>
                  )}
                  {template.formats.length > 0 && (
                    <div className="mt-4 flex flex-wrap gap-1.5">
                      {template.formats.map((fmt) => (
                        <Badge key={fmt} tone="neutral">
                          {fmt}
                        </Badge>
                      ))}
                    </div>
                  )}
                </button>
              );
            })}
          </div>
        )}
      </section>

      {/* Parameters + Preview/Export split */}
      <div className="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        {/* Parameters panel */}
        <Panel
          title="Tham số báo cáo"
          subtitle={
            selectedTemplate
              ? `Template: ${textValue(selectedTemplate, ["code"], "")}`
              : "Chưa chọn template"
          }
        >
          {selectedTemplate ? (
            <div className="space-y-5">
              {/* Month / Year */}
              <div className="grid gap-4 sm:grid-cols-2">
                <label className="space-y-2">
                  <span className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                    Tháng
                  </span>
                  <select
                    value={form.month}
                    onChange={(e) => setForm((c) => ({ ...c, month: e.target.value }))}
                    className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                  >
                    {MONTHS.map((label, idx) => (
                      <option key={idx + 1} value={String(idx + 1)}>
                        {label}
                      </option>
                    ))}
                  </select>
                </label>
                <label className="space-y-2">
                  <span className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                    Năm
                  </span>
                  <input
                    type="number"
                    value={form.year}
                    min={2020}
                    max={2099}
                    onChange={(e) => setForm((c) => ({ ...c, year: e.target.value }))}
                    className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                  />
                </label>
              </div>

              {/* Format / Active status */}
              <div className="grid gap-4 sm:grid-cols-2">
                <label className="space-y-2">
                  <span className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                    Format
                  </span>
                  <select
                    value={form.format}
                    onChange={(e) => setForm((c) => ({ ...c, format: e.target.value }))}
                    className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                  >
                    <option value="xlsx">xlsx</option>
                    <option value="pdf">pdf</option>
                    <option value="csv">csv</option>
                  </select>
                </label>
                <label className="space-y-2">
                  <span className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                    Trạng thái
                  </span>
                  <select
                    value={form.active_status}
                    onChange={(e) => setForm((c) => ({ ...c, active_status: e.target.value }))}
                    className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                  >
                    <option value="">Tất cả</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </label>
              </div>

              {/* Department */}
              <label className="block space-y-2">
                <span className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                  Phòng ban
                </span>
                <select
                  value={form.department_id}
                  onChange={(e) => setForm((c) => ({ ...c, department_id: e.target.value }))}
                  className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                >
                  <option value="">Tất cả phòng ban</option>
                  {departments.map((dept, idx) => (
                    <option
                      key={`${textValue(dept, ["id"], String(idx))}-${idx}`}
                      value={textValue(dept, ["id"], "")}
                    >
                      {textValue(dept, ["name"], "Phòng ban")}
                    </option>
                  ))}
                </select>
              </label>

              {/* Template parameter metadata */}
              {selectedParams.length > 0 && (
                <div className="space-y-2">
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                    Template parameters
                  </p>
                  <div className="grid gap-2 sm:grid-cols-2">
                    {selectedParams.map((param, idx) => (
                      <div
                        key={`${textValue(param, ["name"], String(idx))}-${idx}`}
                        className="rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3"
                      >
                        <p className="text-sm font-medium text-slate-900">
                          {textValue(param, ["name"], "param")}
                        </p>
                        <p className="text-xs text-slate-500">
                          {textValue(param, ["type"], "string")}
                          {" · "}
                          {String(textValue(param, ["required"], "false")) === "true" ? (
                            <span className="text-rose-500">required</span>
                          ) : (
                            "optional"
                          )}
                        </p>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Error */}
              {error && (
                <p className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                  {error}
                </p>
              )}

              {/* Action buttons */}
              <div className="grid gap-3 sm:grid-cols-2">
                <button
                  type="button"
                  onClick={() => previewMutation.mutate()}
                  disabled={previewMutation.isPending || !selectedCode}
                  className="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                >
                  {previewMutation.isPending ? (
                    <RefreshCcw className="h-4 w-4 animate-spin" />
                  ) : (
                    <FileSearch2 className="h-4 w-4" />
                  )}
                  Preview
                </button>
                <button
                  type="button"
                  onClick={() => exportMutation.mutate()}
                  disabled={exportMutation.isPending || !selectedCode}
                  className="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                >
                  {exportMutation.isPending ? (
                    <RefreshCcw className="h-4 w-4 animate-spin" />
                  ) : (
                    <Download className="h-4 w-4" />
                  )}
                  Export
                </button>
              </div>
            </div>
          ) : (
            <EmptyState
              title="Chưa có report template"
              description="Nếu service templates chưa sẵn sàng, page vẫn giữ khung."
            />
          )}
        </Panel>

        {/* Preview / Export output panel */}
        <Panel title="Preview / Export output" subtitle="Kết quả trả về từ controller">
          <div className="space-y-5">
            {preview ? (
              <div className="space-y-4">
                {/* Summary metrics from preview */}
                <div className="grid gap-3 sm:grid-cols-2">
                  {[
                    {
                      label: "Tiêu đề",
                      value: textValue(preview, ["title"], "N/A"),
                    },
                    {
                      label: "Mã báo cáo",
                      value: textValue(preview, ["report_code"], selectedCode),
                    },
                    {
                      label: "Tổng nhân viên",
                      value: formatNumber(
                        numberValue(preview, ["summary.total_employees", "total_employees"], 0)
                      ),
                    },
                    {
                      label: "Tổng lương gộp",
                      value: textValue(
                        preview,
                        ["summary.total_gross_salary", "summary.total_amount"],
                        "—"
                      ),
                    },
                  ].map(({ label, value }) => (
                    <div
                      key={label}
                      className="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3"
                    >
                      <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                        {label}
                      </p>
                      <p className="mt-1 text-sm font-semibold text-slate-900">{String(value)}</p>
                    </div>
                  ))}
                </div>

                {/* Generated at */}
                <p className="text-xs text-slate-400">
                  Tạo lúc:{" "}
                  <span className="font-medium text-slate-600">
                    {formatDateTime(textValue(preview, ["generated_at"], ""))}
                  </span>
                </p>

                {/* Raw JSON */}
                <div className="rounded-2xl overflow-hidden border border-slate-800">
                  <div className="flex items-center justify-between border-b border-slate-700 bg-slate-900 px-4 py-2">
                    <span className="text-xs font-semibold text-slate-400">JSON response</span>
                    <span className="text-xs text-slate-500">{selectedCode}</span>
                  </div>
                  <pre className="overflow-x-auto bg-slate-950 p-4 text-xs leading-6 text-slate-300 max-h-72">
                    {JSON.stringify(preview, null, 2)}
                  </pre>
                </div>
              </div>
            ) : (
              <EmptyState
                title="Chưa có preview"
                description="Bấm Preview để xem dữ liệu báo cáo trước khi export."
              />
            )}

            {/* Export result banner */}
            {exportResult && (
              <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                <div className="flex items-start gap-3">
                  <div className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                    <Download className="h-4 w-4" />
                  </div>
                  <div>
                    <p className="text-sm font-semibold text-emerald-900">Export thành công</p>
                    <p className="mt-1 text-sm text-emerald-700">
                      {textValue(exportResult, ["file_name"], "report")}
                      {" · "}
                      <span className="uppercase font-medium">
                        {textValue(exportResult, ["format"], form.format)}
                      </span>
                    </p>
                    {textValue(exportResult, ["file_url"], "") && (
                      <a
                        href={textValue(exportResult, ["file_url"], "#")}
                        className="mt-2 inline-block text-sm font-semibold text-emerald-700 underline hover:text-emerald-900"
                        target="_blank"
                        rel="noreferrer"
                      >
                        Tải file
                      </a>
                    )}
                  </div>
                </div>
              </div>
            )}
          </div>
        </Panel>
      </div>

      {/* Recent generated reports table — Stitch design's bottom section */}
      <section className="rounded-3xl border border-white/70 bg-white/80 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur overflow-hidden">
        {/* Table header */}
        <div className="flex items-center justify-between border-b border-slate-100 px-6 py-5">
          <div>
            <h2 className="text-base font-semibold text-slate-900">Danh sách template báo cáo</h2>
            <p className="mt-0.5 text-sm text-slate-500">
              {templateCards.length} template từ backend
            </p>
          </div>
          <button
            type="button"
            onClick={() => templatesQuery.refetch()}
            className="text-sm font-semibold text-sky-600 hover:text-sky-800 transition-colors"
          >
            Tải lại
          </button>
        </div>

        {/* Table body */}
        <div className="overflow-x-auto">
          <table className="w-full border-collapse text-left">
            <thead>
              <tr className="bg-slate-50 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                <th className="px-6 py-4">Tên báo cáo</th>
                <th className="px-5 py-4">Danh mục</th>
                <th className="px-5 py-4">Mã</th>
                <th className="px-5 py-4">Formats</th>
                <th className="px-6 py-4 text-right">Chọn</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-50">
              {templateCards.length === 0 ? (
                <tr>
                  <td colSpan={5} className="px-6 py-10 text-center text-sm text-slate-400">
                    Chưa có dữ liệu templates.
                  </td>
                </tr>
              ) : (
                templateCards.map((template) => {
                  const isSelected = selectedCode === template.code;
                  return (
                    <tr
                      key={template.code}
                      className={`group transition-colors ${
                        isSelected
                          ? "bg-sky-50/60"
                          : "hover:bg-slate-50/80"
                      }`}
                    >
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-3">
                          <span
                            className={`flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg ${
                              isSelected
                                ? "bg-sky-100 text-sky-600"
                                : categoryIconBg(template.category)
                            }`}
                          >
                            <TemplateIcon category={template.category} code={template.code} />
                          </span>
                          <span className="font-semibold text-slate-900">{template.title}</span>
                        </div>
                      </td>
                      <td className="px-5 py-4">
                        <Badge
                          tone={
                            template.category === "payroll" || template.category === "financial"
                              ? "info"
                              : template.category === "attendance"
                              ? "accent"
                              : template.category === "insurance" || template.category === "tax"
                              ? "success"
                              : "neutral"
                          }
                        >
                          {template.category}
                        </Badge>
                      </td>
                      <td className="px-5 py-4">
                        <code className="rounded-md bg-slate-100 px-2 py-1 text-xs font-mono text-slate-600">
                          {template.code}
                        </code>
                      </td>
                      <td className="px-5 py-4">
                        <div className="flex flex-wrap gap-1">
                          {template.formats.map((fmt) => (
                            <Badge key={fmt} tone="neutral">
                              {fmt}
                            </Badge>
                          ))}
                        </div>
                      </td>
                      <td className="px-6 py-4 text-right">
                        <button
                          type="button"
                          onClick={() => setSelectedCode(template.code)}
                          className={`inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition ${
                            isSelected
                              ? "bg-sky-600 text-white"
                              : "text-slate-600 hover:bg-slate-100"
                          }`}
                        >
                          <FileSearch2 className="h-3.5 w-3.5" />
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

        {/* Table footer */}
        {templateCards.length > 0 && (
          <div className="border-t border-slate-100 bg-slate-50/60 px-6 py-3">
            <p className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
              {templateCards.length} template · đã chọn:{" "}
              <span className="text-slate-600">
                {templateCards.find((t) => t.code === selectedCode)?.title ?? "—"}
              </span>
            </p>
          </div>
        )}
      </section>
    </div>
  );
}
