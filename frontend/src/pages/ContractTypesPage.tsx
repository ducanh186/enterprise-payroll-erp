import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Plus, RefreshCcw, Search } from "lucide-react";
import { apiGet } from "../lib/api";
import { numberValue, textValue, toArray } from "../lib/records";
import { Badge, EmptyState, Modal, PageHeader } from "../components/ui";

function statusBadge(status: string) {
  const s = status.toLowerCase();
  if (s === "active" || s === "1" || s === "true") return <Badge tone="success">Đang dùng</Badge>;
  return <Badge tone="neutral">Ngừng dùng</Badge>;
}

export default function ContractTypesPage() {
  const [search, setSearch] = useState("");
  const [showModal, setShowModal] = useState(false);

  const query = useQuery({
    queryKey: ["reference", "contract-types"],
    queryFn: async () => apiGet<unknown>("/reference/contract-types"),
  });

  const items = useMemo(
    () => toArray<Record<string, unknown>>(query.data?.data),
    [query.data?.data],
  );

  const filtered = useMemo(() => {
    const q = search.toLowerCase();
    if (!q) return items;
    return items.filter((item) => {
      const code = textValue(item, ["code"], "").toLowerCase();
      const name = textValue(item, ["name"], "").toLowerCase();
      return code.includes(q) || name.includes(q);
    });
  }, [items, search]);

  return (
    <div className="space-y-8 pb-10">
      <PageHeader
        eyebrow="Danh mục"
        title="Danh mục loại hợp đồng"
        description="Quản lý các loại hợp đồng lao động trong tổ chức."
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

      {/* Search */}
      <div className="relative w-full max-w-sm">
        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input
          type="text"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Tìm theo mã hoặc tên loại HĐ..."
          className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
        />
      </div>

      {/* Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
        <table className="w-full border-collapse text-left">
          <thead className="bg-slate-50/50">
            <tr>
              <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Mã</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Tên loại HĐ</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Thời gian thử việc tối đa</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Trạng thái</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {query.isLoading ? (
              Array.from({ length: 4 }).map((_, i) => (
                <tr key={i} className="animate-pulse">
                  <td className="px-6 py-4"><div className="h-3.5 w-16 rounded bg-slate-200" /></td>
                  <td className="px-4 py-4"><div className="h-3.5 w-40 rounded bg-slate-200" /></td>
                  <td className="px-4 py-4"><div className="h-3.5 w-24 rounded bg-slate-200" /></td>
                  <td className="px-4 py-4"><div className="h-5 w-20 rounded-full bg-slate-200" /></td>
                </tr>
              ))
            ) : filtered.length ? (
              filtered.map((item, index) => {
                const id = textValue(item, ["id"], String(index));
                const code = textValue(item, ["code"], "—");
                const name = textValue(item, ["name"], "—");
                const probation = numberValue(item, ["max_probation_days", "probation_days", "probation_months"], 0);
                const probationUnit = textValue(item, ["probation_unit"], "ngày");
                const status = textValue(item, ["status", "is_active"], "active");
                return (
                  <tr key={`${id}-${index}`} className="transition-colors hover:bg-slate-50/50">
                    <td className="px-6 py-4 font-mono text-sm font-semibold text-slate-700">{code}</td>
                    <td className="px-4 py-4 text-sm font-semibold text-slate-900">{name}</td>
                    <td className="px-4 py-4 text-sm tabular-nums text-slate-700">
                      {probation ? `${probation} ${probationUnit}` : "—"}
                    </td>
                    <td className="px-4 py-4">{statusBadge(status)}</td>
                  </tr>
                );
              })
            ) : (
              <tr>
                <td colSpan={4} className="py-10">
                  <EmptyState
                    title="Không có loại hợp đồng"
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
            <span className="font-bold text-slate-800">{items.length}</span> loại hợp đồng
          </p>
        </div>
      </div>

      {/* Modal */}
      <Modal open={showModal} onClose={() => setShowModal(false)} title="Thêm loại hợp đồng mới" size="md">
        <form className="space-y-4">
          <div>
            <label className="mb-1 block text-sm font-semibold text-slate-700">Mã loại HĐ</label>
            <input type="text" placeholder="Ví dụ: HDTV" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
          </div>
          <div>
            <label className="mb-1 block text-sm font-semibold text-slate-700">Tên loại hợp đồng</label>
            <input type="text" placeholder="Hợp đồng thử việc" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
          </div>
          <div>
            <label className="mb-1 block text-sm font-semibold text-slate-700">Thời gian thử việc tối đa (ngày)</label>
            <input type="number" placeholder="60" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
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
