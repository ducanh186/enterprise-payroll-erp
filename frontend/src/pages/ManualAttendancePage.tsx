import { useMemo, useState } from "react";
import type { FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Plus, RefreshCcw } from "lucide-react";
import { apiGet, apiPost, getApiErrorMessage } from "../lib/api";
import { useAuth } from "../context/AuthContext";
import { formatDateTime } from "../lib/format";
import { textValue, toArray } from "../lib/records";
import { createPermissionSet, hasPermissionAccess } from "../lib/rbac";
import { Badge, EmptyState, Modal, PageHeader } from "../components/ui";

type ManualForm = {
  employee_id: string;
  date: string;
  check_in: string;
  check_out: string;
  reason: string;
};

const DEFAULT_FORM: ManualForm = {
  employee_id: "",
  date: new Date().toISOString().slice(0, 10),
  check_in: "08:00",
  check_out: "17:00",
  reason: "",
};

export default function ManualAttendancePage() {
  const { user } = useAuth();
  const queryClient = useQueryClient();
  const [showModal, setShowModal] = useState(false);
  const [form, setForm] = useState<ManualForm>(DEFAULT_FORM);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);
  const permissionSet = createPermissionSet(user?.permissions);
  const canCreateManualAttendance = hasPermissionAccess(permissionSet, "attendance.manage_request");
  const canViewManualEntries = hasPermissionAccess(permissionSet, "attendance.import_logs");

  const query = useQuery({
    queryKey: ["attendance", "manual-entries"],
    queryFn: async () =>
      apiGet<unknown>("/attendance/checkin-logs", {
        source: "manual",
        page: 1,
        per_page: 20,
      }),
    enabled: canViewManualEntries,
  });

  const items = useMemo(
    () => toArray<Record<string, unknown>>(query.data?.data),
    [query.data?.data],
  );

  const mutation = useMutation({
    mutationFn: async () =>
      apiPost<unknown>("/attendance/checkin-logs/manual", {
        employee_id: Number(form.employee_id),
        check_time: new Date(`${form.date}T${form.check_in}`).toISOString(),
        check_type: "in",
        reason: form.reason || undefined,
      }),
    onSuccess: async () => {
      setError(null);
      setSuccess(true);
      setForm(DEFAULT_FORM);
      setShowModal(false);
      await queryClient.invalidateQueries({ queryKey: ["attendance", "manual-entries"] });
      setTimeout(() => setSuccess(false), 3000);
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể ghi nhận chấm công."));
    },
  });

  function updateForm<K extends keyof ManualForm>(key: K, value: string) {
    setForm((cur) => ({ ...cur, [key]: value }));
  }

  function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    if (!canCreateManualAttendance) {
      return;
    }
    mutation.mutate();
  }

  return (
    <div className="space-y-8 pb-10">
      <PageHeader
        eyebrow="Chấm công"
        title="Chấm công bổ sung"
        description="Nhập thủ công dữ liệu chấm công bổ sung cho nhân viên."
        actions={
          <>
            <button
              type="button"
              onClick={() => {
                if (canViewManualEntries) {
                  void query.refetch();
                }
              }}
              className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
            >
              <RefreshCcw className="h-4 w-4" />
              Làm mới
            </button>
            {canCreateManualAttendance && (
              <button
                type="button"
                onClick={() => { setShowModal(true); setError(null); setSuccess(false); }}
                className="inline-flex items-center gap-2 rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg transition hover:opacity-90 active:scale-95"
              >
                <Plus className="h-4 w-4" />
                Thêm chấm công
              </button>
            )}
          </>
        }
      />

      {success && (
        <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
          Ghi nhận chấm công bổ sung thành công.
        </div>
      )}

      {/* Recent Manual Entries Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
        <div className="border-b border-slate-100 px-6 py-4">
          <p className="text-xs font-bold uppercase tracking-widest text-slate-500">Chấm công bổ sung gần đây</p>
        </div>
        <table className="w-full border-collapse text-left">
          <thead className="bg-slate-50/50">
            <tr>
              <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Nhân viên</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Thời gian</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Loại</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Nguồn</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Trạng thái</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {!canViewManualEntries ? (
              <tr>
                <td colSpan={5} className="py-10">
                  <EmptyState
                    title="Không có quyền xem danh sách chấm công bổ sung"
                    description="Tài khoản này không được truy cập log chấm công thủ công."
                  />
                </td>
              </tr>
            ) : query.isLoading ? (
              Array.from({ length: 4 }).map((_, i) => (
                <tr key={i} className="animate-pulse">
                  {Array.from({ length: 5 }).map((__, j) => (
                    <td key={j} className="px-4 py-4"><div className="h-3.5 w-24 rounded bg-slate-200" /></td>
                  ))}
                </tr>
              ))
            ) : items.length ? (
              items.map((item, index) => {
                const id = textValue(item, ["id"], String(index));
                const name = textValue(item, ["employee.full_name", "employee_name"], "Nhân viên");
                const checkTime = textValue(item, ["check_time", "created_at"], "");
                const checkType = textValue(item, ["check_type", "type"], "in");
                const source = textValue(item, ["source", "entry_source"], "manual");
                const isValid = textValue(item, ["is_valid"], "true") !== "false";
                return (
                  <tr key={`${id}-${index}`} className="transition-colors hover:bg-slate-50/50">
                    <td className="px-6 py-4 text-sm font-semibold text-slate-900">{name}</td>
                    <td className="px-4 py-4 text-sm tabular-nums text-slate-700">
                      {checkTime ? formatDateTime(checkTime) : "—"}
                    </td>
                    <td className="px-4 py-4">
                      <Badge tone={checkType === "out" ? "neutral" : "accent"}>{checkType}</Badge>
                    </td>
                    <td className="px-4 py-4">
                      <span className="rounded bg-amber-50 px-2 py-1 text-[10px] font-bold text-amber-700">{source}</span>
                    </td>
                    <td className="px-4 py-4">
                      {isValid ? <Badge tone="success">Hợp lệ</Badge> : <Badge tone="danger">Không hợp lệ</Badge>}
                    </td>
                  </tr>
                );
              })
            ) : (
              <tr>
                <td colSpan={5} className="py-10">
                  <EmptyState
                    title="Chưa có chấm công bổ sung"
                    description={
                      canCreateManualAttendance
                        ? "Nhấn Thêm chấm công để ghi nhận chấm công thủ công."
                        : "Chưa có bản ghi nào trong phạm vi được hiển thị."
                    }
                    action={canCreateManualAttendance ? (
                      <button
                        type="button"
                        onClick={() => setShowModal(true)}
                        className="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800"
                      >
                        <Plus className="h-4 w-4" />
                        Thêm chấm công
                      </button>
                    ) : undefined}
                  />
                </td>
              </tr>
            )}
          </tbody>
        </table>
        <div className="flex items-center justify-between border-t border-slate-100 bg-slate-50/30 px-6 py-4">
          <p className="text-xs text-slate-500">
            <span className="font-bold text-slate-800">{items.length}</span> bản ghi gần đây
          </p>
        </div>
      </div>

      {/* Manual Attendance Modal */}
      <Modal open={showModal && canCreateManualAttendance} onClose={() => setShowModal(false)} title="Chấm công bổ sung" size="md">
        <form className="space-y-4" onSubmit={handleSubmit}>
          <div>
            <label className="mb-1 block text-sm font-semibold text-slate-700">Mã nhân viên</label>
            <input
              type="text"
              required
              value={form.employee_id}
              onChange={(e) => updateForm("employee_id", e.target.value)}
              placeholder="Ví dụ: 1 hoặc NV001"
              className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
            />
          </div>
          <div>
            <label className="mb-1 block text-sm font-semibold text-slate-700">Ngày</label>
            <input
              type="date"
              required
              value={form.date}
              onChange={(e) => updateForm("date", e.target.value)}
              className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
            />
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1 block text-sm font-semibold text-slate-700">Giờ vào</label>
              <input
                type="time"
                required
                value={form.check_in}
                onChange={(e) => updateForm("check_in", e.target.value)}
                className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-semibold text-slate-700">Giờ ra</label>
              <input
                type="time"
                value={form.check_out}
                onChange={(e) => updateForm("check_out", e.target.value)}
                className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
              />
            </div>
          </div>
          <div>
            <label className="mb-1 block text-sm font-semibold text-slate-700">Lý do</label>
            <textarea
              rows={3}
              required
              value={form.reason}
              onChange={(e) => updateForm("reason", e.target.value)}
              placeholder="Lý do chấm công bổ sung..."
              className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
            />
          </div>
          {error && (
            <p className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{error}</p>
          )}
          <div className="flex items-center justify-end gap-3 pt-2">
            <button
              type="button"
              onClick={() => setShowModal(false)}
              className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50"
            >
              Hủy
            </button>
            <button
              type="submit"
              disabled={mutation.isPending}
              className="rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg transition hover:opacity-90 active:scale-95 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {mutation.isPending ? "Đang lưu..." : "Ghi nhận"}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
