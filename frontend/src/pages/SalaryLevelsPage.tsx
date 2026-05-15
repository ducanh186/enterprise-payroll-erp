import { useMemo, useState, type ReactNode } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { ChevronRight, Pencil, Plus, RefreshCcw, Search, Trash2 } from "lucide-react";
import DateInput from "../components/DateInput";
import { apiGet, apiPost, apiPut, getApiErrorMessage } from "../lib/api";
import { formatCurrency, formatDate } from "../lib/format";
import { boolValue, numberValue, textValue, toArray } from "../lib/records";
import { Badge, EmptyState, Modal, PageHeader } from "../components/ui";

type ScaleForm = { code: string; name: string; description: string };
type GradeForm = { code: string; level_no: string; amount: string; effective_from: string; effective_to: string };

const emptyScaleForm: ScaleForm = { code: "", name: "", description: "" };
const emptyGradeForm: GradeForm = { code: "", level_no: "", amount: "", effective_from: "2026-01-01", effective_to: "" };

export default function SalaryLevelsPage() {
  const [search, setSearch] = useState("");
  const [selectedScale, setSelectedScale] = useState<Record<string, unknown> | null>(null);
  const [selectedGrade, setSelectedGrade] = useState<Record<string, unknown> | null>(null);
  const [scaleModalOpen, setScaleModalOpen] = useState(false);
  const [gradeModalOpen, setGradeModalOpen] = useState(false);
  const [editingScaleId, setEditingScaleId] = useState("");
  const [editingGradeId, setEditingGradeId] = useState("");
  const [scaleForm, setScaleForm] = useState<ScaleForm>(emptyScaleForm);
  const [gradeForm, setGradeForm] = useState<GradeForm>(emptyGradeForm);
  const [error, setError] = useState<string | null>(null);

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

  const saveScaleMutation = useMutation({
    mutationFn: async () => editingScaleId
      ? apiPut<unknown>(`/reference/salary-scales/${editingScaleId}`, scaleForm)
      : apiPost<unknown>("/reference/salary-scales", scaleForm),
    onSuccess: async () => {
      setError(null);
      setScaleModalOpen(false);
      setEditingScaleId("");
      setScaleForm(emptyScaleForm);
      await query.refetch();
    },
    onError: (mutationError) => setError(getApiErrorMessage(mutationError, "Không thể lưu thang lương.")),
  });

  const saveGradeMutation = useMutation({
    mutationFn: async () => {
      const payload = {
        code: gradeForm.code,
        level_no: Number(gradeForm.level_no),
        amount: Number(gradeForm.amount),
        effective_from: gradeForm.effective_from,
        effective_to: gradeForm.effective_to || null,
      };

      return editingGradeId
        ? apiPut<unknown>(`/reference/salary-grades/${editingGradeId}`, payload)
        : apiPost<unknown>(`/reference/salary-scales/${textValue(selectedScale, ["id"], "")}/grades`, payload);
    },
    onSuccess: async () => {
      setError(null);
      setGradeModalOpen(false);
      setEditingGradeId("");
      setGradeForm(emptyGradeForm);
      await query.refetch();
      setSelectedScale(null);
      setSelectedGrade(null);
    },
    onError: (mutationError) => setError(getApiErrorMessage(mutationError, "Không thể lưu bậc lương.")),
  });

  const suspendScaleMutation = useMutation({
    mutationFn: async (id: string) => apiPost<unknown>(`/reference/salary-scales/${id}/suspend`),
    onSuccess: async () => {
      setError(null);
      await query.refetch();
    },
    onError: (mutationError) => setError(getApiErrorMessage(mutationError, "Không thể đình chỉ thang lương.")),
  });

  const suspendGradeMutation = useMutation({
    mutationFn: async (id: string) => apiPost<unknown>(`/reference/salary-grades/${id}/suspend`),
    onSuccess: async () => {
      setError(null);
      await query.refetch();
      setSelectedScale(null);
      setSelectedGrade(null);
    },
    onError: (mutationError) => setError(getApiErrorMessage(mutationError, "Không thể đình chỉ bậc lương.")),
  });

  const openCreateScale = () => {
    setEditingScaleId("");
    setScaleForm(emptyScaleForm);
    setError(null);
    setScaleModalOpen(true);
  };

  const openEditScale = (record: Record<string, unknown>) => {
    setEditingScaleId(textValue(record, ["id"], ""));
    setScaleForm({
      code: textValue(record, ["code"], ""),
      name: textValue(record, ["name"], ""),
      description: textValue(record, ["description"], ""),
    });
    setError(null);
    setScaleModalOpen(true);
  };

  const openCreateGrade = () => {
    setEditingGradeId("");
    setGradeForm(emptyGradeForm);
    setError(null);
    setGradeModalOpen(true);
  };

  const openEditGrade = (record: Record<string, unknown>) => {
    setEditingGradeId(textValue(record, ["id"], ""));
    setGradeForm({
      code: textValue(record, ["code", "description"], ""),
      level_no: textValue(record, ["level_no", "salary_level"], ""),
      amount: textValue(record, ["amount"], ""),
      effective_from: textValue(record, ["effective_from", "effective_date"], ""),
      effective_to: textValue(record, ["effective_to"], ""),
    });
    setError(null);
    setGradeModalOpen(true);
  };

  return (
    <div className="space-y-8 pb-10">
      <PageHeader
        eyebrow="Danh mục"
        title="Danh mục thang lương"
        description="Hiển thị theo cấu trúc D20SalaryScale -> D20SalaryGrade -> D20SalaryGradeDetail."
        actions={
          <>
            <button type="button" onClick={() => query.refetch()} disabled={query.isFetching} className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 disabled:opacity-60">
              <RefreshCcw className={`h-4 w-4 ${query.isFetching ? "animate-spin" : ""}`} />
              Làm mới
            </button>
            <button type="button" onClick={openCreateScale} className="inline-flex items-center gap-2 rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg transition hover:opacity-90 active:scale-95">
              <Plus className="h-4 w-4" />
              Thêm thang
            </button>
          </>
        }
      />

      {error && <p className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{error}</p>}

      <div className="relative w-full max-w-sm">
        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Tìm theo mã hoặc tên thang lương..." className={searchClass} />
      </div>

      <div className="overflow-x-auto rounded-2xl border border-slate-200/60 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
        <table className="min-w-[1040px] w-full border-collapse text-left">
          <thead className="bg-slate-50/50">
            <tr>
              <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Mã thang lương</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Tên thang lương</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Mô tả</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Số bậc</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Trạng thái</th>
              <th className="sticky right-0 bg-slate-50/95 px-4 py-4 text-right text-[10px] font-bold uppercase tracking-widest text-slate-400">Hành động</th>
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
                const id = textValue(item, ["id"], "");
                const code = textValue(item, ["code"], String(index));
                const name = textValue(item, ["name"], "—");
                const description = textValue(item, ["description"], "—");
                const gradeCount = toArray(item.grades).length;
                const canMutate = /^\d+$/.test(id);
                const isActive = boolValue(item, ["is_active"], true);
                return (
                  <tr key={`${code}-${index}`} className="transition-colors hover:bg-slate-50/50">
                    <td className="px-6 py-4 font-mono text-sm font-semibold text-slate-700">{code}</td>
                    <td className="px-4 py-4 text-sm font-semibold text-slate-900">{name}</td>
                    <td className="px-4 py-4 text-sm text-slate-600">{description}</td>
                    <td className="px-4 py-4 text-sm tabular-nums text-slate-700">{gradeCount}</td>
                    <td className="px-4 py-4">{isActive ? <Badge tone="success">Đang dùng</Badge> : <Badge>Đã đình chỉ</Badge>}</td>
                    <td className="sticky right-0 bg-white px-4 py-4">
                      <div className="flex justify-end gap-2">
                        <button type="button" onClick={() => { setSelectedScale(item); setSelectedGrade(null); }} className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50">
                          Tab chi tiết
                          <ChevronRight className="h-3.5 w-3.5" />
                        </button>
                        {canMutate && (
                          <button type="button" onClick={() => openEditScale(item)} className="rounded-lg border border-slate-200 p-2 text-slate-500 hover:text-indigo-600" title="Sửa">
                            <Pencil className="h-4 w-4" />
                          </button>
                        )}
                        {canMutate && isActive && (
                          <button type="button" onClick={() => suspendScaleMutation.mutate(id)} className="rounded-lg border border-rose-200 bg-rose-50 p-2 text-rose-600 hover:bg-rose-100" title="Đình chỉ">
                            <Trash2 className="h-4 w-4" />
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                );
              })
            ) : (
              <tr>
                <td colSpan={6} className="py-10">
                  <EmptyState title="Không có thang lương" description="Customer DB chưa có D20SalaryScale hoặc không có dữ liệu khớp bộ lọc." />
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      <Modal open={Boolean(selectedScale)} onClose={() => setSelectedScale(null)} title={selectedScale ? textValue(selectedScale, ["name"], "Chi tiết thang lương") : "Chi tiết thang lương"} size="xl">
        {selectedScale && (
          <div className="space-y-5">
            <div className="grid gap-3 sm:grid-cols-3">
              <InfoCard label="Mã thang lương" value={textValue(selectedScale, ["code"], "—")} />
              <InfoCard label="Tên thang lương" value={textValue(selectedScale, ["name"], "—")} />
              <InfoCard label="Mô tả" value={textValue(selectedScale, ["description"], "—")} />
            </div>

            {/^\d+$/.test(textValue(selectedScale, ["id"], "")) && (
              <div className="flex justify-end">
                <button type="button" onClick={openCreateGrade} className="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-3 py-2 text-xs font-bold text-white hover:bg-slate-800">
                  <Plus className="h-3.5 w-3.5" />
                  Thêm bậc lương
                </button>
              </div>
            )}

            <div className="overflow-x-auto rounded-2xl border border-slate-200">
              <table className="min-w-[1120px] w-full border-collapse text-left">
                <thead className="bg-slate-50 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                  <tr>
                    <th className="px-4 py-3">Mã bậc lương</th>
                    <th className="px-4 py-3">Tên bậc</th>
                    <th className="px-4 py-3">Mức lương</th>
                    <th className="px-4 py-3">Loại lương</th>
                    <th className="px-4 py-3">Hiệu lực từ</th>
                    <th className="px-4 py-3">Hiệu lực đến</th>
                    <th className="px-4 py-3">Trạng thái</th>
                    <th className="sticky right-0 w-56 bg-slate-50 px-4 py-3 text-right">Hành động</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {grades.length ? grades.map((grade, index) => {
                    const id = textValue(grade, ["id"], String(index));
                    const canMutate = /^\d+$/.test(id);
                    const isActive = boolValue(grade, ["is_active"], true);
                    const firstDetail = toArray<Record<string, unknown>>(grade.details)[0];
                    const levelName = textValue(grade, ["description"], "") || `Bậc ${textValue(grade, ["salary_level", "level_no"], "—")}`;
                    return (
                      <tr key={`${id}-${index}`}>
                        <td className="px-4 py-3 font-mono text-sm text-slate-700">{textValue(grade, ["code", "description"], "—")}</td>
                        <td className="px-4 py-3 text-sm font-semibold text-slate-900">{levelName}</td>
                        <td className="px-4 py-3 text-sm font-semibold text-slate-900">{formatCurrency(numberValue(grade, ["amount"], 0))}</td>
                        <td className="px-4 py-3 text-sm text-slate-600">{textValue(firstDetail, ["salary_type"], "—")}</td>
                        <td className="px-4 py-3 text-sm text-slate-600">{textValue(grade, ["effective_date", "effective_from"], "") ? formatDate(textValue(grade, ["effective_date", "effective_from"], "")) : "—"}</td>
                        <td className="px-4 py-3 text-sm text-slate-600">{textValue(grade, ["effective_to"], "") ? formatDate(textValue(grade, ["effective_to"], "")) : "—"}</td>
                        <td className="px-4 py-3">{isActive ? <Badge tone="success">Đang dùng</Badge> : <Badge>Đã đình chỉ</Badge>}</td>
                        <td className="sticky right-0 w-56 bg-white px-4 py-3 text-right">
                          <div className="flex justify-end gap-2">
                            <button type="button" onClick={() => setSelectedGrade(grade)} className="inline-flex whitespace-nowrap items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50">
                              Xem khoản thu nhập
                            </button>
                            {canMutate && <button type="button" onClick={() => openEditGrade(grade)} className="rounded-lg border border-slate-200 p-2 text-slate-500 hover:text-indigo-600"><Pencil className="h-4 w-4" /></button>}
                            {canMutate && isActive && <button type="button" onClick={() => suspendGradeMutation.mutate(id)} className="rounded-lg border border-rose-200 bg-rose-50 p-2 text-rose-600 hover:bg-rose-100"><Trash2 className="h-4 w-4" /></button>}
                          </div>
                        </td>
                      </tr>
                    );
                  }) : (
                    <tr><td colSpan={8} className="py-8"><EmptyState title="Chưa có bậc lương" /></td></tr>
                  )}
                </tbody>
              </table>
            </div>

            {selectedGrade && (
              <div className="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div>
                  <h3 className="text-sm font-bold text-slate-900">Chi tiết khoản thu nhập</h3>
                  <p className="text-xs text-slate-500">Grade: {textValue(selectedGrade, ["id"], "—")} · SalaryLevel {textValue(selectedGrade, ["salary_level"], "—")}</p>
                </div>
                {gradeDetails.length ? (
                  <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table className="w-full border-collapse text-left">
                      <thead className="bg-slate-50 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                        <tr>
                          <th className="px-4 py-3">Khoản thu nhập</th>
                          <th className="px-4 py-3">Loại khoản</th>
                          <th className="px-4 py-3">Công thức/cách tính</th>
                          <th className="px-4 py-3">Giá trị</th>
                          <th className="px-4 py-3">Ghi chú</th>
                          <th className="px-4 py-3">Trạng thái</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {gradeDetails.map((detail, index) => (
                          <tr key={`${textValue(detail, ["row_id"], String(index))}-${index}`}>
                            <td className="px-4 py-3 font-mono text-sm text-slate-700">{textValue(detail, ["row_id"], "—")}</td>
                            <td className="px-4 py-3 text-sm text-slate-700">{textValue(detail, ["salary_type"], "—")}</td>
                            <td className="px-4 py-3 text-sm text-slate-600">{textValue(detail, ["formula", "calculation"], "—")}</td>
                            <td className="px-4 py-3 text-sm font-semibold tabular-nums text-slate-900">{formatCurrency(numberValue(detail, ["amount"], 0))}</td>
                            <td className="px-4 py-3 text-sm text-slate-600">{textValue(detail, ["description"], "—")}</td>
                            <td className="px-4 py-3"><Badge>{textValue(detail, ["status"], "active")}</Badge></td>
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

      <Modal open={scaleModalOpen} onClose={() => setScaleModalOpen(false)} title={editingScaleId ? "Sửa thang lương" : "Thêm thang lương"} size="md">
        <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); saveScaleMutation.mutate(); }}>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Mã thang lương"><input value={scaleForm.code} disabled={Boolean(editingScaleId)} onChange={(e) => setScaleForm({ ...scaleForm, code: e.target.value })} className={inputClass} required /></Field>
            <Field label="Tên thang lương"><input value={scaleForm.name} onChange={(e) => setScaleForm({ ...scaleForm, name: e.target.value })} className={inputClass} required /></Field>
            <Field label="Mô tả"><input value={scaleForm.description} onChange={(e) => setScaleForm({ ...scaleForm, description: e.target.value })} className={inputClass} /></Field>
          </div>
          <ModalActions pending={saveScaleMutation.isPending} onCancel={() => setScaleModalOpen(false)} />
        </form>
      </Modal>

      <Modal open={gradeModalOpen} onClose={() => setGradeModalOpen(false)} title={editingGradeId ? "Sửa bậc lương" : "Thêm bậc lương"} size="md" zIndexClass="z-[60]">
        <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); saveGradeMutation.mutate(); }}>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Mã bậc lương"><input value={gradeForm.code} onChange={(e) => setGradeForm({ ...gradeForm, code: e.target.value })} className={inputClass} required /></Field>
            <Field label="Số bậc lương"><input type="number" min="1" value={gradeForm.level_no} onChange={(e) => setGradeForm({ ...gradeForm, level_no: e.target.value })} className={inputClass} required /></Field>
            <Field label="Mức lương"><input type="number" min="0" value={gradeForm.amount} onChange={(e) => setGradeForm({ ...gradeForm, amount: e.target.value })} className={inputClass} required /></Field>
            <Field label="Hiệu lực từ"><DateInput value={gradeForm.effective_from} onChange={(value) => setGradeForm({ ...gradeForm, effective_from: value })} className={inputClass} required /></Field>
            <Field label="Hiệu lực đến"><DateInput value={gradeForm.effective_to} onChange={(value) => setGradeForm({ ...gradeForm, effective_to: value })} className={inputClass} /></Field>
          </div>
          <ModalActions pending={saveGradeMutation.isPending} onCancel={() => setGradeModalOpen(false)} />
        </form>
      </Modal>
    </div>
  );
}

function ModalActions({ pending, onCancel }: { pending: boolean; onCancel: () => void }) {
  return (
    <div className="flex justify-end gap-3 pt-2">
      <button type="button" onClick={onCancel} className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Hủy</button>
      <button type="submit" disabled={pending} className="rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg disabled:opacity-60">{pending ? "Đang lưu..." : "Lưu"}</button>
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

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <label className="block">
      <span className="mb-1 block text-sm font-semibold text-slate-700">{label}</span>
      {children}
    </label>
  );
}

const searchClass = "w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-indigo-100";
const inputClass = "w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 disabled:bg-slate-100";
