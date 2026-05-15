import { useMemo, useState, type ReactNode } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { Pencil, Plus, RefreshCcw, Search, Trash2 } from "lucide-react";
import { apiGet, apiPost, apiPut, getApiErrorMessage } from "../lib/api";
import { boolValue, numberValue, textValue, toArray } from "../lib/records";
import { Badge, EmptyState, Modal, PageHeader } from "../components/ui";

type ContractTypeForm = {
  code: string;
  name: string;
  duration_months: string;
  is_probationary: boolean;
};

const emptyForm: ContractTypeForm = {
  code: "",
  name: "",
  duration_months: "",
  is_probationary: false,
};

function statusBadge(isActive: boolean) {
  return isActive ? <Badge tone="success">Đang dùng</Badge> : <Badge tone="neutral">Đã đình chỉ</Badge>;
}

function formFromRecord(record: Record<string, unknown>): ContractTypeForm {
  return {
    code: textValue(record, ["code"], ""),
    name: textValue(record, ["name"], ""),
    duration_months: textValue(record, ["duration_months"], ""),
    is_probationary: boolValue(record, ["is_probationary"], false),
  };
}

export default function ContractTypesPage() {
  const [search, setSearch] = useState("");
  const [modalOpen, setModalOpen] = useState(false);
  const [editingId, setEditingId] = useState("");
  const [form, setForm] = useState<ContractTypeForm>(emptyForm);
  const [error, setError] = useState<string | null>(null);

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

  const saveMutation = useMutation({
    mutationFn: async () => {
      const payload = {
        code: form.code,
        name: form.name,
        duration_months: form.duration_months ? Number(form.duration_months) : null,
        is_probationary: form.is_probationary,
      };
      return editingId
        ? apiPut<unknown>(`/reference/contract-types/${editingId}`, payload)
        : apiPost<unknown>("/reference/contract-types", payload);
    },
    onSuccess: async () => {
      setError(null);
      setModalOpen(false);
      setEditingId("");
      setForm(emptyForm);
      await query.refetch();
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể lưu loại hợp đồng."));
    },
  });

  const suspendMutation = useMutation({
    mutationFn: async (id: string) => apiPost<unknown>(`/reference/contract-types/${id}/suspend`),
    onSuccess: async () => {
      setError(null);
      await query.refetch();
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể đình chỉ loại hợp đồng."));
    },
  });

  const openCreate = () => {
    setEditingId("");
    setForm(emptyForm);
    setError(null);
    setModalOpen(true);
  };

  const openEdit = (record: Record<string, unknown>) => {
    setEditingId(textValue(record, ["id"], ""));
    setForm(formFromRecord(record));
    setError(null);
    setModalOpen(true);
  };

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
              className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 disabled:opacity-60"
              disabled={query.isFetching}
            >
              <RefreshCcw className={`h-4 w-4 ${query.isFetching ? "animate-spin" : ""}`} />
              Làm mới
            </button>
            <button
              type="button"
              onClick={openCreate}
              className="inline-flex items-center gap-2 rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg transition hover:opacity-90 active:scale-95"
            >
              <Plus className="h-4 w-4" />
              Thêm mới
            </button>
          </>
        }
      />

      {error && <p className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{error}</p>}

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

      <div className="overflow-x-auto rounded-2xl border border-slate-200/60 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
        <table className="min-w-[920px] w-full border-collapse text-left">
          <thead className="bg-slate-50/50">
            <tr>
              <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Mã</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Tên loại HĐ</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Thời hạn</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Thử việc</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Trạng thái</th>
              <th className="sticky right-0 bg-slate-50/95 px-4 py-4 text-right text-[10px] font-bold uppercase tracking-widest text-slate-400">Hành động</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {query.isLoading ? (
              Array.from({ length: 4 }).map((_, i) => (
                <tr key={i} className="animate-pulse">
                  {Array.from({ length: 6 }).map((__, j) => (
                    <td key={j} className="px-4 py-4"><div className="h-3.5 w-24 rounded bg-slate-200" /></td>
                  ))}
                </tr>
              ))
            ) : filtered.length ? (
              filtered.map((item, index) => {
                const id = textValue(item, ["id"], String(index));
                const isActive = boolValue(item, ["is_active"], true);
                return (
                  <tr key={`${id}-${index}`} className="transition-colors hover:bg-slate-50/50">
                    <td className="px-6 py-4 font-mono text-sm font-semibold text-slate-700">{textValue(item, ["code"], "—")}</td>
                    <td className="px-4 py-4 text-sm font-semibold text-slate-900">{textValue(item, ["name"], "—")}</td>
                    <td className="px-4 py-4 text-sm tabular-nums text-slate-700">{numberValue(item, ["duration_months"], 0) || "—"} tháng</td>
                    <td className="px-4 py-4">{boolValue(item, ["is_probationary"], false) ? <Badge tone="info">Có</Badge> : <Badge>Không</Badge>}</td>
                    <td className="px-4 py-4">{statusBadge(isActive)}</td>
                    <td className="sticky right-0 bg-white px-4 py-4">
                      <div className="flex justify-end gap-2">
                        <button type="button" onClick={() => openEdit(item)} className="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">
                          <Pencil className="h-3.5 w-3.5" />
                          Sửa
                        </button>
                        {isActive ? (
                          <button type="button" onClick={() => suspendMutation.mutate(id)} className="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-100">
                            <Trash2 className="h-3.5 w-3.5" />
                            Đình chỉ
                          </button>
                        ) : (
                          <Badge>Đã đình chỉ</Badge>
                        )}
                      </div>
                    </td>
                  </tr>
                );
              })
            ) : (
              <tr>
                <td colSpan={6} className="py-10">
                  <EmptyState title="Không có loại hợp đồng" description="Dữ liệu chưa được backend trả về hoặc không khớp bộ lọc." />
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editingId ? "Sửa loại hợp đồng" : "Thêm loại hợp đồng"} size="md">
        <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); saveMutation.mutate(); }}>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Mã loại hợp đồng">
              <input value={form.code} disabled={Boolean(editingId)} onChange={(e) => setForm({ ...form, code: e.target.value })} className={inputClass} required />
            </Field>
            <Field label="Tên loại hợp đồng">
              <input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className={inputClass} required />
            </Field>
            <Field label="Thời hạn hợp đồng (tháng)">
              <input type="number" min="0" value={form.duration_months} onChange={(e) => setForm({ ...form, duration_months: e.target.value })} className={inputClass} />
            </Field>
            <label className="flex items-center gap-3 pt-8 text-sm font-semibold text-slate-700">
              <input type="checkbox" checked={form.is_probationary} onChange={(e) => setForm({ ...form, is_probationary: e.target.checked })} className="rounded border-slate-300 text-indigo-600" />
              Hợp đồng thử việc
            </label>
          </div>
          <div className="flex justify-end gap-3 pt-2">
            <button type="button" onClick={() => setModalOpen(false)} className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Hủy</button>
            <button type="submit" disabled={saveMutation.isPending} className="rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg disabled:opacity-60">
              {saveMutation.isPending ? "Đang lưu..." : "Lưu"}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}

const inputClass = "w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 disabled:bg-slate-100";

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <label className="block">
      <span className="mb-1 block text-sm font-semibold text-slate-700">{label}</span>
      {children}
    </label>
  );
}
