import { useMemo, useState, type ReactNode } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { Pencil, Plus, RefreshCcw, Search, Trash2 } from "lucide-react";
import { apiGet, apiPost, apiPut, getApiErrorMessage } from "../lib/api";
import { formatCurrency } from "../lib/format";
import { boolValue, numberValue, textValue, toArray } from "../lib/records";
import { Badge, EmptyState, Modal, PageHeader } from "../components/ui";

type AllowanceForm = {
  code: string;
  name: string;
  default_amount: string;
  is_taxable: boolean;
  is_insurance_base: boolean;
};

const emptyForm: AllowanceForm = {
  code: "",
  name: "",
  default_amount: "",
  is_taxable: false,
  is_insurance_base: false,
};

function formFromRecord(record: Record<string, unknown>): AllowanceForm {
  return {
    code: textValue(record, ["code"], ""),
    name: textValue(record, ["name"], ""),
    default_amount: textValue(record, ["default_amount"], ""),
    is_taxable: boolValue(record, ["is_taxable"], false),
    is_insurance_base: boolValue(record, ["is_insurance_base", "is_insurable"], false),
  };
}

export default function AllowancesPage() {
  const [search, setSearch] = useState("");
  const [modalOpen, setModalOpen] = useState(false);
  const [editingId, setEditingId] = useState("");
  const [form, setForm] = useState<AllowanceForm>(emptyForm);
  const [error, setError] = useState<string | null>(null);

  const query = useQuery({
    queryKey: ["reference", "allowances"],
    queryFn: async () => apiGet<unknown>("/reference/allowances"),
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
        default_amount: form.default_amount ? Number(form.default_amount) : 0,
        is_taxable: form.is_taxable,
        is_insurance_base: form.is_insurance_base,
      };
      return editingId
        ? apiPut<unknown>(`/reference/allowances/${editingId}`, payload)
        : apiPost<unknown>("/reference/allowances", payload);
    },
    onSuccess: async () => {
      setError(null);
      setModalOpen(false);
      setEditingId("");
      setForm(emptyForm);
      await query.refetch();
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể lưu phụ cấp."));
    },
  });

  const suspendMutation = useMutation({
    mutationFn: async (id: string) => apiPost<unknown>(`/reference/allowances/${id}/suspend`),
    onSuccess: async () => {
      setError(null);
      await query.refetch();
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể đình chỉ phụ cấp."));
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
        title="Phụ cấp"
        description="Quản lý danh mục các loại phụ cấp và mức phụ cấp mặc định."
        actions={
          <>
            <button type="button" onClick={() => query.refetch()} disabled={query.isFetching} className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 disabled:opacity-60">
              <RefreshCcw className={`h-4 w-4 ${query.isFetching ? "animate-spin" : ""}`} />
              Làm mới
            </button>
            <button type="button" onClick={openCreate} className="inline-flex items-center gap-2 rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg transition hover:opacity-90 active:scale-95">
              <Plus className="h-4 w-4" />
              Thêm mới
            </button>
          </>
        }
      />

      {error && <p className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{error}</p>}

      <div className="relative w-full max-w-sm">
        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Tìm theo mã hoặc tên phụ cấp..." className={searchClass} />
      </div>

      <div className="overflow-x-auto rounded-2xl border border-slate-200/60 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
        <table className="min-w-[1040px] w-full border-collapse text-left">
          <thead className="bg-slate-50/50">
            <tr>
              <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Mã</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Tên phụ cấp</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Mức mặc định</th>
              <th className="px-4 py-4 text-center text-[10px] font-bold uppercase tracking-widest text-slate-400">Chịu thuế</th>
              <th className="px-4 py-4 text-center text-[10px] font-bold uppercase tracking-widest text-slate-400">Đóng BH</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Trạng thái</th>
              <th className="sticky right-0 bg-slate-50/95 px-4 py-4 text-right text-[10px] font-bold uppercase tracking-widest text-slate-400">Hành động</th>
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
                const defaultAmount = numberValue(item, ["default_amount", "amount", "default_value"], 0);
                const taxable = boolValue(item, ["is_taxable", "taxable"], false);
                const insurable = boolValue(item, ["is_insurance_base", "is_insurable", "insurable", "subject_to_insurance"], false);
                const isActive = boolValue(item, ["is_active"], true);
                return (
                  <tr key={`${id}-${index}`} className="transition-colors hover:bg-slate-50/50">
                    <td className="px-6 py-4 font-mono text-sm font-semibold text-slate-700">{textValue(item, ["code"], "—")}</td>
                    <td className="px-4 py-4 text-sm font-semibold text-slate-900">{textValue(item, ["name"], "—")}</td>
                    <td className="px-4 py-4 text-sm font-semibold tabular-nums text-slate-900">{defaultAmount ? formatCurrency(defaultAmount) : "—"}</td>
                    <td className="px-4 py-4 text-center">{taxable ? <Badge tone="warning">Có</Badge> : <Badge>Không</Badge>}</td>
                    <td className="px-4 py-4 text-center">{insurable ? <Badge tone="info">Có</Badge> : <Badge>Không</Badge>}</td>
                    <td className="px-4 py-4">{isActive ? <Badge tone="success">Đang dùng</Badge> : <Badge>Đã đình chỉ</Badge>}</td>
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
                <td colSpan={7} className="py-10">
                  <EmptyState title="Không có phụ cấp" description="Dữ liệu chưa được backend trả về hoặc không khớp bộ lọc." />
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editingId ? "Sửa phụ cấp" : "Thêm phụ cấp"} size="md">
        <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); saveMutation.mutate(); }}>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Mã phụ cấp"><input value={form.code} disabled={Boolean(editingId)} onChange={(e) => setForm({ ...form, code: e.target.value })} className={inputClass} required /></Field>
            <Field label="Tên phụ cấp"><input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className={inputClass} required /></Field>
            <Field label="Mức mặc định"><input type="number" min="0" value={form.default_amount} onChange={(e) => setForm({ ...form, default_amount: e.target.value })} className={inputClass} /></Field>
            <div className="space-y-3 pt-7">
              <label className="flex items-center gap-3 text-sm font-semibold text-slate-700"><input type="checkbox" checked={form.is_taxable} onChange={(e) => setForm({ ...form, is_taxable: e.target.checked })} className="rounded border-slate-300 text-indigo-600" /> Chịu thuế</label>
              <label className="flex items-center gap-3 text-sm font-semibold text-slate-700"><input type="checkbox" checked={form.is_insurance_base} onChange={(e) => setForm({ ...form, is_insurance_base: e.target.checked })} className="rounded border-slate-300 text-indigo-600" /> Tính đóng bảo hiểm</label>
            </div>
          </div>
          <div className="flex justify-end gap-3 pt-2">
            <button type="button" onClick={() => setModalOpen(false)} className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Hủy</button>
            <button type="submit" disabled={saveMutation.isPending} className="rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg disabled:opacity-60">{saveMutation.isPending ? "Đang lưu..." : "Lưu"}</button>
          </div>
        </form>
      </Modal>
    </div>
  );
}

const searchClass = "w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100";
const inputClass = "w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 disabled:bg-slate-100";

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <label className="block">
      <span className="mb-1 block text-sm font-semibold text-slate-700">{label}</span>
      {children}
    </label>
  );
}
