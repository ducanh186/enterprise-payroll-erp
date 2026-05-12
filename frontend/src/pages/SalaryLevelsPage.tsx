import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { ChevronRight, RefreshCcw, Search } from "lucide-react";
import { apiGet } from "../lib/api";
import { formatCurrency, formatDate } from "../lib/format";
import { numberValue, textValue, toArray } from "../lib/records";
import { Badge, EmptyState, Modal, PageHeader } from "../components/ui";

export default function SalaryLevelsPage() {
  const [search, setSearch] = useState("");
  const [selectedScale, setSelectedScale] = useState<Record<string, unknown> | null>(null);
  const [selectedGrade, setSelectedGrade] = useState<Record<string, unknown> | null>(null);

  const query = useQuery({
    queryKey: ["reference", "salary-scales"],
    queryFn: async () => apiGet<unknown>("/reference/salary-scales"),
  });

  const scales = useMemo(
    () => toArray<Record<string, unknown>>(query.data?.data),
    [query.data?.data],
  );

  const filtered = useMemo(() => {
    const q = search.toLowerCase();
    if (!q) return scales;
    return scales.filter((item) => {
      const code = textValue(item, ["code"], "").toLowerCase();
      const name = textValue(item, ["name"], "").toLowerCase();
      return code.includes(q) || name.includes(q);
    });
  }, [scales, search]);

  const grades = selectedScale ? toArray<Record<string, unknown>>(selectedScale.grades) : [];
  const gradeDetails = selectedGrade ? toArray<Record<string, unknown>>(selectedGrade.details) : [];

  return (
    <div className="space-y-8 pb-10">
      <PageHeader
        eyebrow="Danh mục"
        title="Danh mục thang lương"
        description="Hiển thị theo cấu trúc D20SalaryScale -> D20SalaryGrade -> D20SalaryGradeDetail."
        actions={
          <button
            type="button"
            onClick={() => query.refetch()}
            className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
          >
            <RefreshCcw className="h-4 w-4" />
            Làm mới
          </button>
        }
      />

      <div className="relative w-full max-w-sm">
        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input
          type="text"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Tìm theo mã hoặc tên thang lương..."
          className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
        />
      </div>

      <div className="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
        <table className="w-full border-collapse text-left">
          <thead className="bg-slate-50/50">
            <tr>
              <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Mã thang</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Tên thang lương</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Số bậc</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Nguồn</th>
              <th className="px-4 py-4 text-right text-[10px] font-bold uppercase tracking-widest text-slate-400">Action</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {query.isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="animate-pulse">
                  {Array.from({ length: 5 }).map((__, j) => (
                    <td key={j} className="px-4 py-4"><div className="h-3.5 w-24 rounded bg-slate-200" /></td>
                  ))}
                </tr>
              ))
            ) : filtered.length ? (
              filtered.map((item, index) => {
                const code = textValue(item, ["code"], String(index));
                const name = textValue(item, ["name"], "—");
                const gradeCount = toArray(item.grades).length;
                return (
                  <tr key={`${code}-${index}`} className="transition-colors hover:bg-slate-50/50">
                    <td className="px-6 py-4 font-mono text-sm font-semibold text-slate-700">{code}</td>
                    <td className="px-4 py-4 text-sm font-semibold text-slate-900">{name}</td>
                    <td className="px-4 py-4 text-sm tabular-nums text-slate-700">{gradeCount}</td>
                    <td className="px-4 py-4"><Badge tone="neutral">{textValue(item, ["source"], "customer")}</Badge></td>
                    <td className="px-4 py-4 text-right">
                      <button
                        type="button"
                        onClick={() => {
                          setSelectedScale(item);
                          setSelectedGrade(null);
                        }}
                        className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
                      >
                        Tab chi tiết
                        <ChevronRight className="h-3.5 w-3.5" />
                      </button>
                    </td>
                  </tr>
                );
              })
            ) : (
              <tr>
                <td colSpan={5} className="py-10">
                  <EmptyState
                    title="Không có thang lương"
                    description="Customer DB chưa có D20SalaryScale hoặc không có dữ liệu khớp bộ lọc."
                  />
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      <Modal
        open={Boolean(selectedScale)}
        onClose={() => setSelectedScale(null)}
        title={selectedScale ? textValue(selectedScale, ["name"], "Chi tiết thang lương") : "Chi tiết thang lương"}
        size="xl"
      >
        {selectedScale && (
          <div className="space-y-5">
            <div className="grid gap-3 sm:grid-cols-3">
              <InfoCard label="Code" value={textValue(selectedScale, ["code"], "—")} />
              <InfoCard label="Name" value={textValue(selectedScale, ["name"], "—")} />
              <InfoCard label="Description" value={textValue(selectedScale, ["description"], "—")} />
            </div>

            <div className="overflow-x-auto rounded-2xl border border-slate-200">
              <table className="w-full border-collapse text-left">
                <thead className="bg-slate-50 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                  <tr>
                    <th className="px-4 py-3">Id</th>
                    <th className="px-4 py-3">SalaryLevel</th>
                    <th className="px-4 py-3">EffectiveDate</th>
                    <th className="px-4 py-3">Description</th>
                    <th className="px-4 py-3 text-right">Action</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {grades.map((grade, index) => (
                    <tr key={`${textValue(grade, ["id"], String(index))}-${index}`}>
                      <td className="px-4 py-3 font-mono text-sm text-slate-700">{textValue(grade, ["id"], "—")}</td>
                      <td className="px-4 py-3 text-sm font-semibold text-slate-900">{textValue(grade, ["salary_level"], "—")}</td>
                      <td className="px-4 py-3 text-sm text-slate-600">
                        {textValue(grade, ["effective_date"], "") ? formatDate(textValue(grade, ["effective_date"], "")) : "—"}
                      </td>
                      <td className="px-4 py-3 text-sm text-slate-600">{textValue(grade, ["description"], "—")}</td>
                      <td className="px-4 py-3 text-right">
                        <button
                          type="button"
                          onClick={() => setSelectedGrade(grade)}
                          className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
                        >
                          Xem khoản thu nhập
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {selectedGrade && (
              <div className="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div>
                  <h3 className="text-sm font-bold text-slate-900">D20SalaryGradeDetail</h3>
                  <p className="text-xs text-slate-500">
                    Grade: {textValue(selectedGrade, ["id"], "—")} · SalaryLevel {textValue(selectedGrade, ["salary_level"], "—")}
                  </p>
                </div>
                {gradeDetails.length ? (
                  <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table className="w-full border-collapse text-left">
                      <thead className="bg-slate-50 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                        <tr>
                          <th className="px-4 py-3">RowId</th>
                          <th className="px-4 py-3">SalaryType</th>
                          <th className="px-4 py-3">Amount</th>
                          <th className="px-4 py-3">Description</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {gradeDetails.map((detail, index) => (
                          <tr key={`${textValue(detail, ["row_id"], String(index))}-${index}`}>
                            <td className="px-4 py-3 font-mono text-sm text-slate-700">{textValue(detail, ["row_id"], "—")}</td>
                            <td className="px-4 py-3 text-sm text-slate-700">{textValue(detail, ["salary_type"], "—")}</td>
                            <td className="px-4 py-3 text-sm font-semibold tabular-nums text-slate-900">
                              {formatCurrency(numberValue(detail, ["amount"], 0))}
                            </td>
                            <td className="px-4 py-3 text-sm text-slate-600">{textValue(detail, ["description"], "—")}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                ) : (
                  <EmptyState title="Chưa có chi tiết thu nhập" description="Grade này chưa trả về D20SalaryGradeDetail." />
                )}
              </div>
            )}
          </div>
        )}
      </Modal>
    </div>
  );
}

function InfoCard({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
      <p className="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">{label}</p>
      <p className="mt-1 text-sm font-semibold text-slate-900">{value || "—"}</p>
    </div>
  );
}
