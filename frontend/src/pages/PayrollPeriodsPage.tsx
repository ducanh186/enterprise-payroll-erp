import { useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { Eye, Plus, RefreshCcw, Search } from "lucide-react";
import { apiGet } from "../lib/api";
import { formatCurrency, formatDate, formatNumber } from "../lib/format";
import { numberValue, textValue, toArray } from "../lib/records";
import { Badge, EmptyState, Modal, PageHeader } from "../components/ui";

function periodStatusBadge(status: string) {
  const s = status.toLowerCase();
  if (s === "locked") return <Badge tone="neutral">Đã khóa</Badge>;
  if (s === "finalized") return <Badge tone="success">Hoàn tất</Badge>;
  if (s === "previewed") return <Badge tone="info">Đã xem trước</Badge>;
  if (s === "draft") return <Badge tone="warning">Nháp</Badge>;
  return <Badge tone="neutral">{status}</Badge>;
}

export default function PayrollPeriodsPage() {
  const navigate = useNavigate();
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("");
  const [showModal, setShowModal] = useState(false);

  const query = useQuery({
    queryKey: ["payroll", "periods"],
    queryFn: async () =>
      apiGet<unknown>("/payroll/periods", { page: 1, per_page: 20 }),
  });

  const items = useMemo(
    () => toArray<Record<string, unknown>>(query.data?.data),
    [query.data?.data],
  );

  const filtered = useMemo(() => {
    return items.filter((item) => {
      const period = textValue(item, ["period_name", "name", "period"], "").toLowerCase();
      const status = textValue(item, ["status"], "").toLowerCase();
      const q = search.toLowerCase();
      if (q && !period.includes(q)) return false;
      if (statusFilter && !status.includes(statusFilter.toLowerCase())) return false;
      return true;
    });
  }, [items, search, statusFilter]);

  return (
    <div className="space-y-8 pb-10">
      <PageHeader
        eyebrow="Tính lương"
        title="Bảng lương"
        description="Quản lý các kỳ tính lương, xem trước và khóa bảng lương."
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
              Tạo kỳ lương
            </button>
          </>
        }
      />

      {/* Filters */}
      <div className="flex flex-wrap items-center gap-3">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Tìm theo kỳ lương..."
            className="w-64 rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
          />
        </div>
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
          className="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-600 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
        >
          <option value="">Tất cả trạng thái</option>
          <option value="draft">Nháp</option>
          <option value="previewed">Đã xem trước</option>
          <option value="finalized">Hoàn tất</option>
          <option value="locked">Đã khóa</option>
        </select>
      </div>

      {/* Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
        <table className="w-full border-collapse text-left">
          <thead className="bg-slate-50/50">
            <tr>
              <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Kỳ lương</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Tháng/Năm</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Trạng thái</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Số NV</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Tổng lương</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Ngày tạo</th>
              <th className="px-6 py-4 text-right text-[10px] font-bold uppercase tracking-widest text-slate-400">Hành động</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {query.isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="animate-pulse">
                  {Array.from({ length: 7 }).map((__, j) => (
                    <td key={j} className="px-4 py-4"><div className="h-3.5 w-24 rounded bg-slate-200" /></td>
                  ))}
                </tr>
              ))
            ) : filtered.length ? (
              filtered.map((item, index) => {
                const id = textValue(item, ["id"], String(index));
                const periodName = textValue(item, ["period_name", "name", "period"], "—");
                const month = textValue(item, ["month"], "");
                const year = textValue(item, ["year"], "");
                const monthYear = month && year ? `${month.padStart(2, "0")}/${year}` : textValue(item, ["period", "month_year"], "—");
                const status = textValue(item, ["status"], "draft");
                const employeeCount = numberValue(item, ["employee_count", "total_employees", "count"], 0);
                const totalSalary = numberValue(item, ["total_salary", "total_net_salary", "total_amount"], 0);
                const createdAt = textValue(item, ["created_at", "created_date"], "");
                return (
                  <tr key={`${id}-${index}`} className="group transition-colors hover:bg-slate-50/50">
                    <td className="px-6 py-4 text-sm font-semibold text-slate-900">{periodName}</td>
                    <td className="px-4 py-4">
                      <span className="rounded bg-indigo-50 px-2 py-1 text-xs font-bold text-indigo-700">{monthYear}</span>
                    </td>
                    <td className="px-4 py-4">{periodStatusBadge(status)}</td>
                    <td className="px-4 py-4 text-sm tabular-nums text-slate-700">
                      {employeeCount ? formatNumber(employeeCount) : "—"}
                    </td>
                    <td className="px-4 py-4 text-sm font-semibold tabular-nums text-slate-900">
                      {totalSalary ? formatCurrency(totalSalary) : "—"}
                    </td>
                    <td className="px-4 py-4 text-sm tabular-nums text-slate-700">
                      {createdAt ? formatDate(createdAt) : "—"}
                    </td>
                    <td className="px-6 py-4 text-right">
                      <div className="flex items-center justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                        <button
                          type="button"
                          onClick={() => navigate("/payroll/run")}
                          className="rounded-lg p-1.5 text-slate-400 transition hover:text-indigo-600"
                          title="Xem chi tiết kỳ lương"
                        >
                          <Eye className="h-4 w-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })
            ) : (
              <tr>
                <td colSpan={7} className="py-10">
                  <EmptyState
                    title="Không có kỳ lương"
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
            <span className="font-bold text-slate-800">{items.length}</span> kỳ lương
          </p>
        </div>
      </div>

      {/* Create Period Modal */}
      <Modal open={showModal} onClose={() => setShowModal(false)} title="Tạo kỳ lương mới" size="md">
        <form className="space-y-4">
          <div>
            <label className="mb-1 block text-sm font-semibold text-slate-700">Tên kỳ lương</label>
            <input type="text" placeholder="Ví dụ: Lương tháng 03/2026" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1 block text-sm font-semibold text-slate-700">Tháng</label>
              <select className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                {Array.from({ length: 12 }, (_, i) => i + 1).map((m) => (
                  <option key={m} value={m}>Tháng {String(m).padStart(2, "0")}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="mb-1 block text-sm font-semibold text-slate-700">Năm</label>
              <input type="number" placeholder="2026" min="2020" max="2099" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
            </div>
          </div>
          <div className="flex items-center justify-end gap-3 pt-2">
            <button type="button" onClick={() => setShowModal(false)} className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50">Hủy</button>
            <button type="button" onClick={() => setShowModal(false)} className="rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg transition hover:opacity-90 active:scale-95">Tạo kỳ lương</button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
