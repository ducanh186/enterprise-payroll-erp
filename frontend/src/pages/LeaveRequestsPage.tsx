import { useMemo, useState, type ReactNode } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { Eye, Pencil, Plus, RefreshCcw, Search } from "lucide-react";
import DateInput from "../components/DateInput";
import { apiGet, apiPost, apiPut, getApiErrorMessage } from "../lib/api";
import { formatDate } from "../lib/format";
import { numberValue, textValue, toArray } from "../lib/records";
import { Badge, EmptyState, Modal, PageHeader } from "../components/ui";

type LeaveForm = {
  employee_code: string;
  manager_code: string;
  request_date: string;
  to_date: string;
  reason: string;
  working_hours: string;
  working_days: string;
  absence_type: string;
  detail_description: string;
};

const emptyLeaveForm: LeaveForm = {
  employee_code: "",
  manager_code: "",
  request_date: new Date().toISOString().slice(0, 10),
  to_date: new Date().toISOString().slice(0, 10),
  reason: "",
  working_hours: "8",
  working_days: "1",
  absence_type: "1",
  detail_description: "",
};

function leaveStatusBadge(status: string) {
  const s = status.toLowerCase();
  if (s.includes("approved") || s.includes("accept") || s.includes("duyệt") || s === "active") return <Badge tone="success">Đang dùng</Badge>;
  if (s.includes("pending") || s.includes("waiting") || s.includes("chờ")) return <Badge tone="warning">Chờ duyệt</Badge>;
  if (s.includes("rejected") || s.includes("denied") || s.includes("từ chối")) return <Badge tone="danger">Từ chối</Badge>;
  if (s.includes("cancelled") || s.includes("cancel") || s.includes("hủy") || s === "inactive") return <Badge tone="neutral">Đã hủy</Badge>;
  return <Badge tone="info">{status}</Badge>;
}

export default function LeaveRequestsPage() {
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("");
  const [selectedRequest, setSelectedRequest] = useState<Record<string, unknown> | null>(null);
  const [formOpen, setFormOpen] = useState(false);
  const [editingId, setEditingId] = useState("");
  const [form, setForm] = useState<LeaveForm>(emptyLeaveForm);
  const [error, setError] = useState<string | null>(null);

  const query = useQuery({
    queryKey: ["attendance", "leave-requests"],
    queryFn: async () => apiGet<unknown>("/attendance/requests", { page: 1, per_page: 50 }),
  });

  const items = useMemo(
    () => toArray<Record<string, unknown>>(query.data?.data),
    [query.data?.data],
  );

  const filtered = useMemo(() => {
    return items.filter((item) => {
      const name = textValue(item, ["employee.full_name", "employee_name"], "").toLowerCase();
      const code = textValue(item, ["employee_code"], "").toLowerCase();
      const docNo = textValue(item, ["doc_no"], "").toLowerCase();
      const reason = textValue(item, ["reason", "description"], "").toLowerCase();
      const status = textValue(item, ["status"], "").toLowerCase();
      const q = search.toLowerCase();

      if (q && !name.includes(q) && !code.includes(q) && !docNo.includes(q) && !reason.includes(q)) return false;
      if (statusFilter && !status.includes(statusFilter.toLowerCase())) return false;
      return true;
    });
  }, [items, search, statusFilter]);

  const detailQuery = useQuery({
    queryKey: ["attendance", "leave-request", textValue(selectedRequest, ["id"], "")],
    queryFn: async () => apiGet<unknown>(`/attendance/requests/${textValue(selectedRequest, ["id"], "")}`),
    enabled: Boolean(selectedRequest),
  });

  const selectedDetail = (detailQuery.data?.data as Record<string, unknown> | undefined) ?? selectedRequest;
  const detailRows = toArray<Record<string, unknown>>(selectedDetail?.details);

  const saveMutation = useMutation({
    mutationFn: async () => {
      const payload = {
        doc_type: "AL",
        employee_code: form.employee_code,
        manager_code: form.manager_code,
        request_date: form.request_date,
        to_date: form.to_date || form.request_date,
        reason: form.reason,
        working_hours: Number(form.working_hours || 0),
        working_days: Number(form.working_days || 0),
        absence_type: Number(form.absence_type || 1),
        detail_description: form.detail_description || null,
      };

      return editingId
        ? apiPut<unknown>(`/attendance/requests/${editingId}`, payload)
        : apiPost<unknown>("/attendance/requests", payload);
    },
    onSuccess: async (result) => {
      setError(null);
      setFormOpen(false);
      setEditingId("");
      setForm(emptyLeaveForm);
      setSelectedRequest(result.data as Record<string, unknown>);
      await query.refetch();
      await detailQuery.refetch();
    },
    onError: (mutationError) => setError(getApiErrorMessage(mutationError, "Không thể lưu đơn nghỉ phép.")),
  });

  const openCreate = () => {
    setEditingId("");
    setForm(emptyLeaveForm);
    setError(null);
    setFormOpen(true);
  };

  const openEdit = (record: Record<string, unknown>) => {
    const firstDetail = toArray<Record<string, unknown>>(record.details)[0];
    setEditingId(textValue(record, ["id"], ""));
    setForm({
      employee_code: textValue(record, ["employee_code"], ""),
      manager_code: textValue(record, ["manager_code"], ""),
      request_date: textValue(record, ["request_date", "from_date"], new Date().toISOString().slice(0, 10)),
      to_date: textValue(record, ["to_date", "request_date"], new Date().toISOString().slice(0, 10)),
      reason: textValue(record, ["reason", "description"], ""),
      working_hours: textValue(firstDetail, ["working_hours", "requested_hours"], "8"),
      working_days: textValue(firstDetail, ["working_days"], "1"),
      absence_type: textValue(firstDetail, ["absence_type"], "1"),
      detail_description: textValue(firstDetail, ["description", "note"], ""),
    });
    setError(null);
    setFormOpen(true);
  };

  return (
    <div className="space-y-8 pb-10">
      <PageHeader
        eyebrow="Chấm công"
        title="Đơn xin nghỉ phép"
        description="Quản lý đơn nghỉ phép AL và chi tiết ngày nghỉ."
        actions={
          <>
            <button type="button" onClick={() => query.refetch()} disabled={query.isFetching} className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 disabled:opacity-60">
              <RefreshCcw className={`h-4 w-4 ${query.isFetching ? "animate-spin" : ""}`} />
              Làm mới
            </button>
            <button type="button" onClick={openCreate} className="inline-flex items-center gap-2 rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg transition hover:opacity-90 active:scale-95">
              <Plus className="h-4 w-4" />
              Tạo đơn
            </button>
          </>
        }
      />

      {error && <p className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{error}</p>}

      <div className="flex flex-wrap items-center gap-3">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Tìm theo nhân viên, mã đơn hoặc lý do..." className={inputSearchClass} />
        </div>
        <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} className={selectClass}>
          <option value="">Tất cả trạng thái</option>
          <option value="active">Đang dùng</option>
          <option value="pending">Chờ duyệt</option>
          <option value="approved">Đã duyệt</option>
          <option value="rejected">Từ chối</option>
        </select>
      </div>

      <div className="overflow-x-auto rounded-2xl border border-slate-200/60 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
        <table className="min-w-[1120px] w-full border-collapse text-left">
          <thead className="bg-slate-50/50">
            <tr>
              <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Mã đơn</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Nhân viên</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Từ ngày</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Đến ngày</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Số ngày</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Lý do</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Trạng thái</th>
              <th className="sticky right-0 bg-slate-50/95 px-4 py-4 text-right text-[10px] font-bold uppercase tracking-widest text-slate-400">Hành động</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {query.isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="animate-pulse">
                  {Array.from({ length: 8 }).map((__, j) => (
                    <td key={j} className="px-4 py-4"><div className="h-3.5 w-24 rounded bg-slate-200" /></td>
                  ))}
                </tr>
              ))
            ) : filtered.length ? (
              filtered.map((item, index) => {
                const id = textValue(item, ["id"], String(index));
                const fromDate = textValue(item, ["from_date", "request_date"], "");
                const toDate = textValue(item, ["to_date"], fromDate);
                const days = numberValue(toArray<Record<string, unknown>>(item.details)[0], ["working_days"], numberValue(item, ["days", "total_days", "duration"], 0));
                const reason = textValue(item, ["reason", "description"], "—");
                const status = textValue(item, ["status"], "pending");

                return (
                  <tr key={`${id}-${index}`} className="transition-colors hover:bg-slate-50/50">
                    <td className="px-6 py-4 font-mono text-sm font-semibold text-slate-700">{textValue(item, ["doc_no", "doc_id", "id"], "—")}</td>
                    <td className="px-4 py-4">
                      <p className="text-sm font-semibold text-slate-900">{textValue(item, ["employee_name"], "Nhân viên")}</p>
                      <p className="mt-0.5 font-mono text-xs text-slate-400">{textValue(item, ["employee_code"], "")}</p>
                    </td>
                    <td className="px-4 py-4 text-sm tabular-nums text-slate-700">{fromDate ? formatDate(fromDate) : "—"}</td>
                    <td className="px-4 py-4 text-sm tabular-nums text-slate-700">{toDate ? formatDate(toDate) : "—"}</td>
                    <td className="px-4 py-4 text-sm tabular-nums font-semibold text-slate-800">{days || "—"}</td>
                    <td className="px-4 py-4 max-w-xs truncate text-sm text-slate-500">{reason}</td>
                    <td className="px-4 py-4">{leaveStatusBadge(status)}</td>
                    <td className="sticky right-0 bg-white px-4 py-4">
                      <div className="flex justify-end gap-2">
                        <button type="button" onClick={() => setSelectedRequest(item)} className="rounded-lg border border-slate-200 p-2 text-slate-500 hover:text-indigo-600" title="Xem">
                          <Eye className="h-4 w-4" />
                        </button>
                        <button type="button" onClick={() => openEdit(item)} className="rounded-lg border border-slate-200 p-2 text-slate-500 hover:text-indigo-600" title="Sửa">
                          <Pencil className="h-4 w-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })
            ) : (
              <tr>
                <td colSpan={8} className="py-10">
                  <EmptyState title="Không có đơn nghỉ phép" description="Dữ liệu chưa được backend trả về hoặc không khớp bộ lọc." />
                </td>
              </tr>
            )}
          </tbody>
        </table>
        <div className="flex items-center justify-between border-t border-slate-100 bg-slate-50/30 px-6 py-4">
          <p className="text-xs text-slate-500">Hiển thị <span className="font-bold text-slate-800">{filtered.length}</span> trong <span className="font-bold text-slate-800">{items.length}</span> đơn</p>
        </div>
      </div>

      <Modal open={Boolean(selectedRequest)} onClose={() => setSelectedRequest(null)} title="Chi tiết đơn nghỉ phép" size="xl">
        {selectedDetail && (
          <div className="space-y-5">
            <div className="grid gap-3 sm:grid-cols-3">
              <InfoCard label="Mã đơn" value={textValue(selectedDetail, ["doc_no", "doc_id", "id"], "—")} />
              <InfoCard label="Nhân viên" value={`${textValue(selectedDetail, ["employee_name"], "—")} ${textValue(selectedDetail, ["employee_code"], "")}`.trim()} />
              <InfoCard label="Ngày đơn" value={textValue(selectedDetail, ["request_date"], "") ? formatDate(textValue(selectedDetail, ["request_date"], "")) : "—"} />
              <InfoCard label="Quản lý" value={textValue(selectedDetail, ["manager_code"], "—")} />
              <InfoCard label="Lý do" value={textValue(selectedDetail, ["reason"], "—")} />
              <InfoCard label="Trạng thái" value={textValue(selectedDetail, ["status"], "—")} />
            </div>

            <div className="flex justify-end">
              <button type="button" onClick={() => openEdit(selectedDetail)} className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50">
                <Pencil className="h-3.5 w-3.5" />
                Sửa
              </button>
            </div>

            <div className="overflow-x-auto rounded-2xl border border-slate-200">
              <table className="min-w-[760px] w-full border-collapse text-left">
                <thead className="bg-slate-50 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                  <tr>
                    <th className="px-4 py-3">Dòng</th>
                    <th className="px-4 py-3">Ngày nghỉ</th>
                    <th className="px-4 py-3">Số giờ</th>
                    <th className="px-4 py-3">Số ngày</th>
                    <th className="px-4 py-3">Loại nghỉ</th>
                    <th className="px-4 py-3">Ghi chú</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {detailRows.length ? detailRows.map((detail, index) => (
                    <tr key={`${textValue(detail, ["id", "row_id"], String(index))}-${index}`}>
                      <td className="px-4 py-3 font-mono text-sm text-slate-700">{textValue(detail, ["row_id", "id"], "—")}</td>
                      <td className="px-4 py-3 text-sm tabular-nums text-slate-700">{textValue(detail, ["work_date", "date"], "") ? formatDate(textValue(detail, ["work_date", "date"], "")) : "—"}</td>
                      <td className="px-4 py-3 text-sm tabular-nums text-slate-700">{numberValue(detail, ["working_hours", "requested_hours"], 0)}</td>
                      <td className="px-4 py-3 text-sm tabular-nums text-slate-700">{numberValue(detail, ["working_days"], 0)}</td>
                      <td className="px-4 py-3 text-sm text-slate-700">{textValue(detail, ["absence_type"], "—")}</td>
                      <td className="px-4 py-3 text-sm text-slate-600">{textValue(detail, ["description", "note"], "—")}</td>
                    </tr>
                  )) : (
                    <tr><td colSpan={6} className="py-8"><EmptyState title="Chưa có dòng chi tiết" /></td></tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </Modal>

      <Modal open={formOpen} onClose={() => setFormOpen(false)} title={editingId ? "Sửa đơn xin nghỉ phép" : "Tạo đơn xin nghỉ phép"} size="md" zIndexClass="z-[60]">
        <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); saveMutation.mutate(); }}>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Mã nhân viên"><input value={form.employee_code} onChange={(e) => setForm({ ...form, employee_code: e.target.value })} className={inputClass} required /></Field>
            <Field label="Mã quản lý"><input value={form.manager_code} onChange={(e) => setForm({ ...form, manager_code: e.target.value })} className={inputClass} /></Field>
            <Field label="Từ ngày"><DateInput value={form.request_date} onChange={(value) => setForm({ ...form, request_date: value, to_date: form.to_date || value })} className={inputClass} required /></Field>
            <Field label="Đến ngày"><DateInput value={form.to_date} onChange={(value) => setForm({ ...form, to_date: value })} className={inputClass} required /></Field>
            <Field label="Số giờ"><input type="number" min="0" max="24" step="0.5" value={form.working_hours} onChange={(e) => setForm({ ...form, working_hours: e.target.value })} className={inputClass} required /></Field>
            <Field label="Số ngày"><input type="number" min="0" max="2" step="0.5" value={form.working_days} onChange={(e) => setForm({ ...form, working_days: e.target.value })} className={inputClass} required /></Field>
            <Field label="Loại nghỉ"><input type="number" min="0" value={form.absence_type} onChange={(e) => setForm({ ...form, absence_type: e.target.value })} className={inputClass} /></Field>
            <Field label="Lý do"><input value={form.reason} onChange={(e) => setForm({ ...form, reason: e.target.value })} className={inputClass} required /></Field>
            <Field label="Ghi chú dòng chi tiết"><input value={form.detail_description} onChange={(e) => setForm({ ...form, detail_description: e.target.value })} className={inputClass} /></Field>
          </div>
          <div className="flex justify-end gap-3 pt-2">
            <button type="button" onClick={() => setFormOpen(false)} className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Hủy</button>
            <button type="button" onClick={() => saveMutation.mutate()} disabled={saveMutation.isPending} className="rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg disabled:opacity-60">{saveMutation.isPending ? "Đang lưu..." : "Lưu"}</button>
          </div>
        </form>
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

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <label className="block">
      <span className="mb-1 block text-sm font-semibold text-slate-700">{label}</span>
      {children}
    </label>
  );
}

const inputSearchClass = "w-80 rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100";
const selectClass = "rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-600 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100";
const inputClass = "w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 disabled:bg-slate-100";
