import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Plus, RefreshCcw, Search } from "lucide-react";
import { apiGet } from "../lib/api";
import { formatDate } from "../lib/format";
import { textValue, toArray } from "../lib/records";
import { EmptyState, Modal, PageHeader } from "../components/ui";

export default function PayrollParametersPage() {
  const [search, setSearch] = useState("");
  const [showModal, setShowModal] = useState(false);
  const [activeView, setActiveView] = useState<"value" | "salaryType">("value");

  const query = useQuery({
    queryKey: ["reference", "payroll-parameters"],
    queryFn: async () => apiGet<unknown>("/reference/payroll-parameters"),
  });

  const items = useMemo(
    () => toArray<Record<string, unknown>>(query.data?.data),
    [query.data?.data],
  );

  const filtered = useMemo(() => {
    const q = search.toLowerCase();
    const viewItems = items.filter((item) => {
      const code = textValue(item, ["code", "param_code", "group"], "").toLowerCase();
      const type = textValue(item, ["type", "param_type", "category"], "").toLowerCase();
      const salaryTypeMatch = code.includes("salary") || code.includes("allowance") || code.includes("bonus") || code.includes("deduction") || type.includes("salary");

      if (activeView === "salaryType") {
        return salaryTypeMatch || !items.some((candidate) => {
          const candidateCode = textValue(candidate, ["code", "param_code", "group"], "").toLowerCase();
          const candidateType = textValue(candidate, ["type", "param_type", "category"], "").toLowerCase();
          return candidateCode.includes("salary") || candidateCode.includes("allowance") || candidateCode.includes("bonus") || candidateCode.includes("deduction") || candidateType.includes("salary");
        });
      }

      return !salaryTypeMatch || !items.some((candidate) => {
        const candidateCode = textValue(candidate, ["code", "param_code", "group"], "").toLowerCase();
        const candidateType = textValue(candidate, ["type", "param_type", "category"], "").toLowerCase();
        return candidateCode.includes("salary") || candidateCode.includes("allowance") || candidateCode.includes("bonus") || candidateCode.includes("deduction") || candidateType.includes("salary");
      });
    });

    if (!q) return viewItems;
    return viewItems.filter((item) => {
      const code = textValue(item, ["code", "param_code"], "").toLowerCase();
      const name = textValue(item, ["name", "param_name", "label"], "").toLowerCase();
      return code.includes(q) || name.includes(q);
    });
  }, [activeView, items, search]);

  const activeViewName = activeView === "value" ? "vD20PayrollPara_ValuePara" : "vD20PayrollPara_SalaryType";

  return (
    <div className="space-y-8 pb-10">
      <PageHeader
        eyebrow="Tính lương"
        title="Tham số lương"
        description="Tra cứu tham số theo đúng 2 view Fujimart: ValuePara và SalaryType."
        actions={
          <>
            <button
              type="button"
              onClick={() => query.refetch()}
              className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
            >
              <RefreshCcw className="h-4 w-4" />
              Làm mới
            </button>
            <button
              type="button"
              onClick={() => setShowModal(true)}
              className="inline-flex items-center gap-2 rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg transition hover:opacity-90 active:scale-95"
            >
              <Plus className="h-4 w-4" />
              Thêm mới
            </button>
          </>
        }
      />

      <div className="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-sm">
        {[
          { key: "value", label: "vD20PayrollPara_ValuePara" },
          { key: "salaryType", label: "vD20PayrollPara_SalaryType" },
        ].map((tab) => (
          <button
            key={tab.key}
            type="button"
            onClick={() => setActiveView(tab.key as "value" | "salaryType")}
            className={`rounded-xl px-4 py-2 text-xs font-bold transition ${
              activeView === tab.key
                ? "bg-slate-950 text-white"
                : "text-slate-600 hover:bg-slate-100"
            }`}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* Search */}
      <div className="relative w-full max-w-sm">
        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input
          type="text"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Tìm theo mã hoặc tên tham số..."
          className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
        />
      </div>

      {/* Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
        <div className="border-b border-slate-100 bg-slate-50/60 px-6 py-3">
          <p className="text-[11px] font-bold text-slate-500">Nguồn view: {activeViewName}</p>
        </div>
        <table className="w-full border-collapse text-left">
          <thead className="bg-slate-50/50">
            <tr>
              <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Mã</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Tên tham số</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Giá trị</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Đơn vị</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Hiệu lực từ</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Mô tả</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {query.isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="animate-pulse">
                  {Array.from({ length: 6 }).map((__, j) => (
                    <td key={j} className="px-4 py-4"><div className="h-3.5 w-24 rounded bg-slate-200" /></td>
                  ))}
                </tr>
              ))
            ) : filtered.length ? (
              filtered.map((item, index) => {
                const id = textValue(item, ["id"], String(index));
                const code = textValue(item, ["code", "param_code"], "—");
                const name = textValue(item, ["name", "param_name", "label"], "—");
                const value = textValue(item, ["value", "param_value"], "—");
                const unit = textValue(item, ["unit", "param_unit"], "—");
                const effectiveDate = textValue(item, ["effective_date", "valid_from", "effective_from"], "");
                const description = textValue(item, ["description", "note", "remarks"], "—");
                return (
                  <tr key={`${id}-${index}`} className="transition-colors hover:bg-slate-50/50">
                    <td className="px-6 py-4 font-mono text-sm font-semibold text-slate-700">{code}</td>
                    <td className="px-4 py-4 text-sm font-semibold text-slate-900">{name}</td>
                    <td className="px-4 py-4 text-sm font-bold tabular-nums text-indigo-700">{value}</td>
                    <td className="px-4 py-4">
                      <span className="rounded bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-500">{unit}</span>
                    </td>
                    <td className="px-4 py-4 text-sm tabular-nums text-slate-700">
                      {effectiveDate ? formatDate(effectiveDate) : "—"}
                    </td>
                    <td className="px-4 py-4 max-w-xs text-sm text-slate-500 truncate">{description}</td>
                  </tr>
                );
              })
            ) : (
              <tr>
                <td colSpan={6} className="py-10">
                  <EmptyState
                    title="Không có tham số lương"
                    description="Dữ liệu chưa được backend trả về hoặc không khớp bộ lọc."
                  />
                </td>
              </tr>
            )}
          </tbody>
        </table>
        <div className="flex items-center justify-between border-t border-slate-100 bg-slate-50/30 px-6 py-4">
          <p className="text-xs text-slate-500">
            Hiển thị <span className="font-bold text-slate-800">{filtered.length}</span> trong{" "}
            <span className="font-bold text-slate-800">{items.length}</span> tham số
          </p>
        </div>
      </div>

      {/* Modal */}
      <Modal open={showModal} onClose={() => setShowModal(false)} title="Thêm tham số lương mới" size="md">
        <form className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1 block text-sm font-semibold text-slate-700">Mã tham số</label>
              <input type="text" placeholder="BHXH_EE_RATE" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-semibold text-slate-700">Tên tham số</label>
              <input type="text" placeholder="Tỷ lệ BHXH NLĐ" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-semibold text-slate-700">Giá trị</label>
              <input type="text" placeholder="0.08" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-semibold text-slate-700">Đơn vị</label>
              <input type="text" placeholder="%" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
            </div>
            <div className="sm:col-span-2">
              <label className="mb-1 block text-sm font-semibold text-slate-700">Hiệu lực từ</label>
              <input type="date" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
            </div>
            <div className="sm:col-span-2">
              <label className="mb-1 block text-sm font-semibold text-slate-700">Mô tả</label>
              <textarea rows={3} placeholder="Mô tả chi tiết tham số..." className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
            </div>
          </div>
          <div className="flex items-center justify-end gap-3 pt-2">
            <button type="button" onClick={() => setShowModal(false)} className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50">Hủy</button>
            <button type="button" onClick={() => setShowModal(false)} className="rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg transition hover:opacity-90 active:scale-95">Lưu</button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
