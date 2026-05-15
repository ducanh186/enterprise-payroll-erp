import { useMemo, useState, type ReactNode } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { Pencil, Plus, RefreshCcw, Search, Trash2 } from "lucide-react";
import DateInput from "../components/DateInput";
import { apiGet, apiPost, apiPut, getApiErrorMessage } from "../lib/api";
import { formatDate } from "../lib/format";
import { boolValue, textValue, toArray } from "../lib/records";
import { Badge, EmptyState, Modal, PageHeader } from "../components/ui";

type ParameterForm = {
  code: string;
  name: string;
  value: string;
  unit: string;
  type: string;
  effective_from: string;
  description: string;
};

const emptyForm: ParameterForm = {
  code: "",
  name: "",
  value: "",
  unit: "value",
  type: "value",
  effective_from: "2026-01-01",
  description: "",
};

function formFromRecord(record: Record<string, unknown>): ParameterForm {
  return {
    code: textValue(record, ["code", "param_code"], ""),
    name: textValue(record, ["name", "param_name", "label"], ""),
    value: textValue(record, ["value", "param_value"], ""),
    unit: textValue(record, ["unit", "param_unit"], "value"),
    type: textValue(record, ["type", "param_type"], "value"),
    effective_from: textValue(record, ["effective_from", "effective_date", "valid_from"], ""),
    description: textValue(record, ["description", "note", "remarks"], ""),
  };
}

export default function PayrollParametersPage() {
  const [search, setSearch] = useState("");
  const [modalOpen, setModalOpen] = useState(false);
  const [editingId, setEditingId] = useState("");
  const [form, setForm] = useState<ParameterForm>(emptyForm);
  const [activeView, setActiveView] = useState<"value" | "salaryType">("value");
  const [error, setError] = useState<string | null>(null);

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
    const hasSalaryRows = items.some((candidate) => isSalaryTypeRow(candidate));
    const viewItems = items.filter((item) => {
      const salaryTypeMatch = isSalaryTypeRow(item);
      return activeView === "salaryType"
        ? salaryTypeMatch || !hasSalaryRows
        : !salaryTypeMatch || !hasSalaryRows;
    });

    if (!q) return viewItems;
    return viewItems.filter((item) => {
      const code = textValue(item, ["code", "param_code"], "").toLowerCase();
      const name = textValue(item, ["name", "param_name", "label"], "").toLowerCase();
      return code.includes(q) || name.includes(q);
    });
  }, [activeView, items, search]);

  const saveMutation = useMutation({
    mutationFn: async () => {
      const payload = {
        code: form.code,
        name: form.name || form.code,
        value: form.value,
        unit: form.unit,
        type: form.type || form.unit,
        effective_from: form.effective_from,
        description: form.description,
      };

      return editingId
        ? apiPut<unknown>(`/reference/payroll-parameters/${editingId}`, payload)
        : apiPost<unknown>("/reference/payroll-parameters", payload);
    },
    onSuccess: async () => {
      setError(null);
      setModalOpen(false);
      setEditingId("");
      setForm(emptyForm);
      await query.refetch();
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể lưu tham số lương."));
    },
  });

  const suspendMutation = useMutation({
    mutationFn: async (id: string) => apiPost<unknown>(`/reference/payroll-parameters/${id}/suspend`),
    onSuccess: async () => {
      setError(null);
      await query.refetch();
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể đình chỉ tham số lương."));
    },
  });

  const activeViewName = activeView === "value" ? "vD20PayrollPara_ValuePara" : "vD20PayrollPara_SalaryType";
  const activeViewLabel = activeView === "value" ? "Tham số giá trị" : "Loại thu nhập, lương thưởng";

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
        eyebrow="Tiền lương"
        title="Tham số lương"
        description="Tra cứu tham số theo đúng 2 nhóm nghiệp vụ Fujimart."
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

      <div className="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-sm">
        {[
          { key: "value", label: "Tham số giá trị" },
          { key: "salaryType", label: "Loại thu nhập, lương thưởng" },
        ].map((tab) => (
          <button key={tab.key} type="button" onClick={() => setActiveView(tab.key as "value" | "salaryType")} className={`rounded-xl px-4 py-2 text-xs font-bold transition ${activeView === tab.key ? "bg-slate-950 text-white" : "text-slate-600 hover:bg-slate-100"}`}>
            {tab.label}
          </button>
        ))}
      </div>

      <div className="relative w-full max-w-sm">
        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Tìm theo mã hoặc tên tham số..." className={searchClass} />
      </div>

      <div className="overflow-x-auto rounded-2xl border border-slate-200/60 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
        <div className="border-b border-slate-100 bg-slate-50/60 px-6 py-3">
          <p className="text-[11px] font-bold text-slate-500">
            {activeViewLabel} · Nguồn view: {activeViewName}
          </p>
        </div>
        <table className="min-w-[1120px] w-full border-collapse text-left">
          <thead className="bg-slate-50/50">
            <tr>
              <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Mã</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Tên tham số</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Giá trị</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Đơn vị</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Hiệu lực từ</th>
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
                const code = textValue(item, ["code", "param_code"], "—");
                const name = textValue(item, ["name", "param_name", "label"], "—");
                const value = textValue(item, ["value", "param_value"], "—");
                const unit = textValue(item, ["unit", "param_unit"], "—");
                const effectiveDate = textValue(item, ["effective_date", "valid_from", "effective_from"], "");
                const isActive = boolValue(item, ["is_active"], true);
                return (
                  <tr key={`${id}-${index}`} className="transition-colors hover:bg-slate-50/50">
                    <td className="px-6 py-4 font-mono text-sm font-semibold text-slate-700">{code}</td>
                    <td className="px-4 py-4 text-sm font-semibold text-slate-900">{name}</td>
                    <td className="px-4 py-4 text-sm font-bold tabular-nums text-indigo-700">{value}</td>
                    <td className="px-4 py-4"><span className="rounded bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-500">{unit}</span></td>
                    <td className="px-4 py-4 text-sm tabular-nums text-slate-700">{effectiveDate ? formatDate(effectiveDate) : "—"}</td>
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
                  <EmptyState title="Không có tham số lương" description="Dữ liệu chưa được backend trả về hoặc không khớp bộ lọc." />
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editingId ? "Sửa tham số lương" : "Thêm tham số lương"} size="md">
        <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); saveMutation.mutate(); }}>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Mã tham số"><input value={form.code} disabled={Boolean(editingId)} onChange={(e) => setForm({ ...form, code: e.target.value })} className={inputClass} required /></Field>
            <Field label="Tên tham số"><input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className={inputClass} required /></Field>
            <Field label="Giá trị"><input value={form.value} onChange={(e) => setForm({ ...form, value: e.target.value })} className={inputClass} /></Field>
            <Field label="Đơn vị"><input value={form.unit} onChange={(e) => setForm({ ...form, unit: e.target.value, type: e.target.value })} className={inputClass} /></Field>
            <Field label="Hiệu lực từ"><DateInput value={form.effective_from} onChange={(value) => setForm({ ...form, effective_from: value })} className={inputClass} required /></Field>
            <Field label="Mô tả"><input value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className={inputClass} /></Field>
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

function isSalaryTypeRow(item: Record<string, unknown>) {
  const code = textValue(item, ["code", "param_code", "group"], "").toLowerCase();
  const type = textValue(item, ["type", "param_type", "category"], "").toLowerCase();
  return code.includes("salary") || code.includes("allowance") || code.includes("bonus") || code.includes("deduction") || type.includes("salary");
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
