import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Plus, RefreshCcw, Search } from "lucide-react";
import { apiGet } from "../lib/api";
import { formatDate } from "../lib/format";
import { textValue, toArray } from "../lib/records";
import { EmptyState, Modal, PageHeader } from "../components/ui";

const AVATAR_COLORS = [
  "bg-indigo-100 text-indigo-700",
  "bg-sky-100 text-sky-700",
  "bg-teal-100 text-teal-700",
  "bg-violet-100 text-violet-700",
  "bg-amber-100 text-amber-700",
  "bg-emerald-100 text-emerald-700",
];

function avatarColor(name: string): string {
  let hash = 0;
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
  return AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length];
}

function getInitials(name: string): string {
  return name.split(" ").map((w) => w[0]).join("").slice(0, 2).toUpperCase();
}

export default function ShiftAssignmentsPage() {
  const [search, setSearch] = useState("");
  const [showModal, setShowModal] = useState(false);

  const query = useQuery({
    queryKey: ["attendance", "shift-assignments"],
    queryFn: async () =>
      apiGet<unknown>("/attendance/shift-assignments", { page: 1, per_page: 30 }),
  });

  const items = useMemo(
    () => toArray<Record<string, unknown>>(query.data?.data),
    [query.data?.data],
  );

  const filtered = useMemo(() => {
    const q = search.toLowerCase();
    if (!q) return items;
    return items.filter((item) => {
      const name = textValue(item, ["employee.full_name", "employee_name"], "").toLowerCase();
      const shift = textValue(item, ["shift.name", "shift_name"], "").toLowerCase();
      return name.includes(q) || shift.includes(q);
    });
  }, [items, search]);

  return (
    <div className="space-y-8 pb-10">
      <PageHeader
        eyebrow="Chấm công"
        title="Phân ca làm việc"
        description="Quản lý phân công ca làm việc cho nhân viên."
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
          placeholder="Tìm theo nhân viên hoặc ca làm việc..."
          className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
        />
      </div>

      {/* Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
        <table className="w-full border-collapse text-left">
          <thead className="bg-slate-50/50">
            <tr>
              <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Nhân viên</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Ca làm việc</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Ngày</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Nguồn</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Ghi chú</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {query.isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="animate-pulse">
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-3">
                      <div className="h-8 w-8 rounded-full bg-slate-200" />
                      <div className="h-3.5 w-32 rounded bg-slate-200" />
                    </div>
                  </td>
                  {Array.from({ length: 4 }).map((__, j) => (
                    <td key={j} className="px-4 py-4"><div className="h-3.5 w-24 rounded bg-slate-200" /></td>
                  ))}
                </tr>
              ))
            ) : filtered.length ? (
              filtered.map((item, index) => {
                const id = textValue(item, ["id"], String(index));
                const name = textValue(item, ["employee.full_name", "employee_name"], "Nhân viên");
                const code = textValue(item, ["employee.employee_code", "employee_code"], "");
                const shiftName = textValue(item, ["shift.name", "shift_name", "shift"], "—");
                const date = textValue(item, ["date", "work_date", "assigned_date"], "");
                const source = textValue(item, ["source", "assignment_source"], "—");
                const note = textValue(item, ["note", "notes", "remark"], "");
                const colorClass = avatarColor(name);
                return (
                  <tr key={`${id}-${index}`} className="transition-colors hover:bg-slate-50/50">
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-3">
                        <div className={`flex h-8 w-8 items-center justify-center rounded-full text-[10px] font-bold ${colorClass}`}>
                          {getInitials(name)}
                        </div>
                        <div>
                          <p className="text-sm font-semibold text-slate-900">{name}</p>
                          {code && <p className="text-[11px] text-slate-400">{code}</p>}
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-4">
                      <span className="rounded bg-indigo-50 px-2 py-1 text-[10px] font-bold text-indigo-700">{shiftName}</span>
                    </td>
                    <td className="px-4 py-4 text-sm tabular-nums text-slate-700">{date ? formatDate(date) : "—"}</td>
                    <td className="px-4 py-4 text-sm text-slate-700">{source}</td>
                    <td className="px-4 py-4 text-sm text-slate-500">{note || "—"}</td>
                  </tr>
                );
              })
            ) : (
              <tr>
                <td colSpan={5} className="py-10">
                  <EmptyState
                    title="Không có phân ca"
                    description="Dữ liệu phân ca chưa được backend trả về hoặc không khớp bộ lọc."
                  />
                </td>
              </tr>
            )}
          </tbody>
        </table>
        <div className="flex items-center justify-between border-t border-slate-100 bg-slate-50/30 px-6 py-4">
          <p className="text-xs text-slate-500">
            Hiển thị <span className="font-bold text-slate-800">{filtered.length}</span> trong{" "}
            <span className="font-bold text-slate-800">{items.length}</span> phân ca
          </p>
        </div>
      </div>

      {/* Modal */}
      <Modal open={showModal} onClose={() => setShowModal(false)} title="Thêm phân ca làm việc" size="md">
        <form className="space-y-4">
          <div>
            <label className="mb-1 block text-sm font-semibold text-slate-700">Mã nhân viên</label>
            <input type="text" placeholder="Ví dụ: NV001" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
          </div>
          <div>
            <label className="mb-1 block text-sm font-semibold text-slate-700">Ca làm việc</label>
            <input type="text" placeholder="Ca sáng" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
          </div>
          <div>
            <label className="mb-1 block text-sm font-semibold text-slate-700">Ngày</label>
            <input type="date" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
          </div>
          <div>
            <label className="mb-1 block text-sm font-semibold text-slate-700">Ghi chú</label>
            <textarea rows={3} placeholder="Ghi chú thêm..." className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
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
