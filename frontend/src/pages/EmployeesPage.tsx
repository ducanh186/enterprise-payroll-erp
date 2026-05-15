import { useMemo, useState } from "react";
import type { ReactNode } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { Pencil, Plus, RefreshCcw, Search, Trash2, UsersRound } from "lucide-react";
import DateInput from "../components/DateInput";
import { Badge, EmptyState, Modal, PageHeader } from "../components/ui";
import { apiGet, apiPost, apiPut, getApiErrorMessage } from "../lib/api";
import { formatDate } from "../lib/format";
import { textValue, toArray } from "../lib/records";

type EmployeeForm = {
  employee_code: string;
  full_name: string;
  gender: string;
  birth_date: string;
  id_card_no: string;
  email: string;
  mobile: string;
  hire_date: string;
  resign_date: string;
  status: string;
};

type DependentForm = {
  full_name: string;
  relationship: string;
  date_of_birth: string;
  identity_number: string;
  tax_deduction_from: string;
  tax_deduction_to: string;
};

const emptyEmployeeForm: EmployeeForm = {
  employee_code: "",
  full_name: "",
  gender: "",
  birth_date: "",
  id_card_no: "",
  email: "",
  mobile: "",
  hire_date: "2026-01-01",
  resign_date: "",
  status: "active",
};

const emptyDependentForm: DependentForm = {
  full_name: "",
  relationship: "",
  date_of_birth: "",
  identity_number: "",
  tax_deduction_from: "",
  tax_deduction_to: "",
};

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

function statusBadge(status: string) {
  const s = status.toLowerCase();
  if (s === "active" || s === "đang làm việc") return <Badge tone="success">Đang làm việc</Badge>;
  if (s === "inactive" || s === "suspended") return <Badge tone="neutral">Đình chỉ</Badge>;
  if (s === "terminated" || s === "nghỉ việc") return <Badge tone="neutral">Nghỉ việc</Badge>;
  return <Badge tone="info">{status || "active"}</Badge>;
}

function formFromEmployee(employee: Record<string, unknown>): EmployeeForm {
  return {
    employee_code: textValue(employee, ["employee_code", "code"], ""),
    full_name: textValue(employee, ["full_name", "name"], ""),
    gender: textValue(employee, ["gender"], ""),
    birth_date: textValue(employee, ["birth_date", "date_of_birth"], ""),
    id_card_no: textValue(employee, ["id_card_no", "identity_number"], ""),
    email: textValue(employee, ["email"], ""),
    mobile: textValue(employee, ["mobile", "phone"], ""),
    hire_date: textValue(employee, ["hire_date", "join_date", "start_date"], ""),
    resign_date: textValue(employee, ["resign_date"], ""),
    status: textValue(employee, ["status"], "active"),
  };
}

function formFromDependent(dependent: Record<string, unknown>): DependentForm {
  return {
    full_name: textValue(dependent, ["full_name", "name"], ""),
    relationship: textValue(dependent, ["relationship"], ""),
    date_of_birth: textValue(dependent, ["date_of_birth", "dob"], ""),
    identity_number: textValue(dependent, ["identity_number", "national_id"], ""),
    tax_deduction_from: textValue(dependent, ["tax_deduction_from", "tax_reduction_from"], ""),
    tax_deduction_to: textValue(dependent, ["tax_deduction_to", "tax_reduction_to"], ""),
  };
}

export default function EmployeesPage() {
  const [search, setSearch] = useState("");
  const [selectedEmployeeId, setSelectedEmployeeId] = useState<string>("");
  const [detailTab, setDetailTab] = useState<"profile" | "dependents">("profile");
  const [employeeForm, setEmployeeForm] = useState<EmployeeForm>(emptyEmployeeForm);
  const [editingEmployeeId, setEditingEmployeeId] = useState<string>("");
  const [employeeModalOpen, setEmployeeModalOpen] = useState(false);
  const [dependentForm, setDependentForm] = useState<DependentForm>(emptyDependentForm);
  const [editingDependentId, setEditingDependentId] = useState<string>("");
  const [dependentModalOpen, setDependentModalOpen] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const employeesQuery = useQuery({
    queryKey: ["employees"],
    queryFn: async () => apiGet<unknown>("/employees", { page: 1, per_page: 50 }),
  });

  const employeeDetailQuery = useQuery({
    queryKey: ["employees", selectedEmployeeId],
    queryFn: async () => apiGet<unknown>(`/employees/${selectedEmployeeId}`),
    enabled: Boolean(selectedEmployeeId),
  });

  const dependentsQuery = useQuery({
    queryKey: ["employees", selectedEmployeeId, "dependents"],
    queryFn: async () => apiGet<unknown>(`/employees/${selectedEmployeeId}/dependents`),
    enabled: Boolean(selectedEmployeeId) && detailTab === "dependents",
  });

  const employees = useMemo(
    () => toArray<Record<string, unknown>>(employeesQuery.data?.data),
    [employeesQuery.data?.data],
  );
  const selectedEmployee = (employeeDetailQuery.data?.data ?? null) as Record<string, unknown> | null;
  const dependents = useMemo(
    () => toArray<Record<string, unknown>>(dependentsQuery.data?.data),
    [dependentsQuery.data?.data],
  );

  const filtered = useMemo(() => {
    const q = search.toLowerCase();
    if (!q) return employees;
    return employees.filter((emp) => {
      const name = textValue(emp, ["full_name", "name"], "").toLowerCase();
      const code = textValue(emp, ["employee_code", "code"], "").toLowerCase();
      const dept = textValue(emp, ["department.name", "department_name"], "").toLowerCase();
      return name.includes(q) || code.includes(q) || dept.includes(q);
    });
  }, [employees, search]);

  const saveEmployeeMutation = useMutation({
    mutationFn: async () => editingEmployeeId
      ? apiPut<unknown>(`/employees/${editingEmployeeId}`, employeeForm)
      : apiPost<unknown>("/employees", employeeForm),
    onSuccess: async (response) => {
      setError(null);
      setEmployeeModalOpen(false);
      const saved = (response.data ?? {}) as Record<string, unknown>;
      const savedId = textValue(saved, ["id"], editingEmployeeId);
      if (savedId) setSelectedEmployeeId(savedId);
      await employeesQuery.refetch();
      if (savedId) await employeeDetailQuery.refetch();
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể lưu nhân viên."));
    },
  });

  const suspendEmployeeMutation = useMutation({
    mutationFn: async (id: string) => apiPost<unknown>(`/employees/${id}/suspend`),
    onSuccess: async () => {
      setError(null);
      await employeesQuery.refetch();
      await employeeDetailQuery.refetch();
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể đình chỉ nhân viên."));
    },
  });

  const saveDependentMutation = useMutation({
    mutationFn: async () => editingDependentId
      ? apiPut<unknown>(`/employees/${selectedEmployeeId}/dependents/${editingDependentId}`, dependentForm)
      : apiPost<unknown>(`/employees/${selectedEmployeeId}/dependents`, dependentForm),
    onSuccess: async () => {
      setError(null);
      setDependentModalOpen(false);
      await dependentsQuery.refetch();
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể lưu người phụ thuộc."));
    },
  });

  const openCreateEmployee = () => {
    setEditingEmployeeId("");
    setEmployeeForm(emptyEmployeeForm);
    setEmployeeModalOpen(true);
  };

  const openEditEmployee = (employee: Record<string, unknown>) => {
    setEditingEmployeeId(textValue(employee, ["id"], ""));
    setEmployeeForm(formFromEmployee(employee));
    setEmployeeModalOpen(true);
  };

  const openCreateDependent = () => {
    setEditingDependentId("");
    setDependentForm(emptyDependentForm);
    setDependentModalOpen(true);
  };

  const openEditDependent = (dependent: Record<string, unknown>) => {
    setEditingDependentId(textValue(dependent, ["id"], ""));
    setDependentForm(formFromDependent(dependent));
    setDependentModalOpen(true);
  };

  const refreshEmployees = async () => {
    setError(null);
    await Promise.all([
      employeesQuery.refetch(),
      selectedEmployeeId ? employeeDetailQuery.refetch() : Promise.resolve(),
      selectedEmployeeId && detailTab === "dependents" ? dependentsQuery.refetch() : Promise.resolve(),
    ]);
  };

  return (
    <div className="space-y-8 pb-10">
      <PageHeader
        eyebrow="Nhân sự"
        title="Hồ sơ cán bộ nhân viên"
        description="Quản lý hồ sơ, thông tin cá nhân và người phụ thuộc."
        actions={
          <>
            <button
              type="button"
              onClick={refreshEmployees}
              className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
            >
              <RefreshCcw className="h-4 w-4" />
              Làm mới
            </button>
            <button
              type="button"
              onClick={openCreateEmployee}
              className="inline-flex items-center gap-2 rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg transition hover:opacity-90 active:scale-95"
            >
              <Plus className="h-4 w-4" />
              Thêm mới
            </button>
          </>
        }
      />

      {error && (
        <p className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{error}</p>
      )}

      <div className="relative w-full max-w-sm">
        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input
          type="text"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Tìm theo tên, mã NV, phòng ban..."
          className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
        />
      </div>

      <div className="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
        <table className="w-full border-collapse text-left">
          <thead className="bg-slate-50/50">
            <tr>
              <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Mã NV</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Họ tên</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Email</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Số điện thoại</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Ngày vào làm</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Trạng thái</th>
              <th className="px-4 py-4 text-right text-[10px] font-bold uppercase tracking-widest text-slate-400">Action</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {employeesQuery.isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="animate-pulse">
                  {Array.from({ length: 7 }).map((__, j) => (
                    <td key={j} className="px-4 py-4"><div className="h-3.5 w-24 rounded bg-slate-200" /></td>
                  ))}
                </tr>
              ))
            ) : filtered.length ? (
              filtered.map((emp, index) => {
                const id = textValue(emp, ["id"], String(index));
                const name = textValue(emp, ["full_name", "name"], "Nhân viên");
                const code = textValue(emp, ["employee_code", "code"], "N/A");
                const startDate = textValue(emp, ["hire_date", "start_date", "joined_at"], "");
                const status = textValue(emp, ["status"], "active");
                return (
                  <tr key={`${id}-${index}`} className="transition-colors hover:bg-slate-50/50">
                    <td className="px-6 py-4 font-mono text-sm font-medium text-slate-500 tabular-nums">{code}</td>
                    <td className="px-4 py-4">
                      <div className="flex items-center gap-3">
                        <div className={`flex h-8 w-8 items-center justify-center rounded-full text-[10px] font-bold ${avatarColor(name)}`}>
                          {getInitials(name)}
                        </div>
                        <p className="text-sm font-semibold text-slate-900">{name}</p>
                      </div>
                    </td>
                    <td className="px-4 py-4 text-sm text-slate-700">{textValue(emp, ["email"], "—")}</td>
                    <td className="px-4 py-4 text-sm text-slate-700">{textValue(emp, ["mobile", "phone"], "—")}</td>
                    <td className="px-4 py-4 text-sm tabular-nums text-slate-700">{startDate ? formatDate(startDate) : "—"}</td>
                    <td className="px-4 py-4">{statusBadge(status)}</td>
                    <td className="px-4 py-4">
                      <div className="flex justify-end gap-2">
                        <button
                          type="button"
                          onClick={() => {
                            setSelectedEmployeeId(id);
                            setDetailTab("profile");
                          }}
                          className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
                        >
                          <UsersRound className="h-3.5 w-3.5" />
                          Tab chi tiết
                        </button>
                        <button
                          type="button"
                          onClick={() => openEditEmployee(emp)}
                          className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
                        >
                          <Pencil className="h-3.5 w-3.5" />
                          Sửa
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })
            ) : (
              <tr>
                <td colSpan={7} className="py-10">
                  <EmptyState title="Không có nhân viên" description="Không có dữ liệu khớp bộ lọc hiện tại." />
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      <EmployeeEditorModal
        open={employeeModalOpen}
        editing={Boolean(editingEmployeeId)}
        form={employeeForm}
        pending={saveEmployeeMutation.isPending}
        onClose={() => setEmployeeModalOpen(false)}
        onChange={setEmployeeForm}
        onSave={() => saveEmployeeMutation.mutate()}
      />

      <DependentEditorModal
        open={dependentModalOpen}
        editing={Boolean(editingDependentId)}
        form={dependentForm}
        pending={saveDependentMutation.isPending}
        onClose={() => setDependentModalOpen(false)}
        onChange={setDependentForm}
        onSave={() => saveDependentMutation.mutate()}
      />

      <Modal
        open={Boolean(selectedEmployeeId)}
        onClose={() => setSelectedEmployeeId("")}
        title={selectedEmployee ? textValue(selectedEmployee, ["full_name", "name"], "Chi tiết nhân viên") : "Chi tiết nhân viên"}
        size="xl"
      >
        {selectedEmployee ? (
          <div className="space-y-5">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div className="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-1.5">
                {[
                  { key: "profile", label: "Hồ sơ" },
                  { key: "dependents", label: "Người phụ thuộc" },
                ].map((tab) => (
                  <button
                    key={tab.key}
                    type="button"
                    onClick={() => setDetailTab(tab.key as "profile" | "dependents")}
                    className={`rounded-xl px-4 py-2 text-xs font-bold transition ${
                      detailTab === tab.key ? "bg-slate-950 text-white" : "text-slate-600 hover:bg-white"
                    }`}
                  >
                    {tab.label}
                  </button>
                ))}
              </div>
              <div className="flex gap-2">
                <button
                  type="button"
                  onClick={() => openEditEmployee(selectedEmployee)}
                  className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
                >
                  <Pencil className="h-3.5 w-3.5" />
                  Sửa
                </button>
                <button
                  type="button"
                  onClick={() => suspendEmployeeMutation.mutate(selectedEmployeeId)}
                  className="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100"
                >
                  <Trash2 className="h-3.5 w-3.5" />
                  Đình chỉ
                </button>
              </div>
            </div>

            {detailTab === "profile" ? (
              <div className="grid gap-3 sm:grid-cols-2">
                {[
                  ["Mã NV", textValue(selectedEmployee, ["employee_code", "code"], "N/A")],
                  ["Họ tên", textValue(selectedEmployee, ["full_name", "name"], "—")],
                  ["Giới tính", textValue(selectedEmployee, ["gender"], "—")],
                  ["Ngày sinh", formatDate(textValue(selectedEmployee, ["birth_date", "date_of_birth"], ""))],
                  ["Số CCCD", textValue(selectedEmployee, ["id_card_no", "identity_number"], "—")],
                  ["Địa chỉ email", textValue(selectedEmployee, ["email"], "—")],
                  ["Số điện thoại", textValue(selectedEmployee, ["mobile", "phone"], "—")],
                  ["Ngày vào làm", textValue(selectedEmployee, ["hire_date", "start_date", "joined_at"], "") ? formatDate(textValue(selectedEmployee, ["hire_date", "start_date", "joined_at"], "")) : ""],
                  ["Ngày nghỉ việc", textValue(selectedEmployee, ["resign_date"], "") ? formatDate(textValue(selectedEmployee, ["resign_date"], "")) : ""],
                  ["Trạng thái", textValue(selectedEmployee, ["status"], "active")],
                ].map(([label, value]) => (
                  <div key={label} className="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                    <p className="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">{label}</p>
                    <p className="mt-1 text-sm font-semibold text-slate-900">{value || "—"}</p>
                  </div>
                ))}
              </div>
            ) : (
              <div className="space-y-3">
                <div className="flex justify-end">
                  <button
                    type="button"
                    onClick={openCreateDependent}
                    className="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-3 py-2 text-xs font-bold text-white transition hover:bg-slate-800"
                  >
                    <Plus className="h-3.5 w-3.5" />
                    Thêm người phụ thuộc
                  </button>
                </div>
                {dependentsQuery.isLoading ? (
                  <p className="text-sm text-slate-500">Đang tải người phụ thuộc...</p>
                ) : dependents.length ? (
                  <div className="overflow-x-auto">
                    <table className="w-full border-collapse text-left">
                      <thead>
                        <tr className="bg-slate-50 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                          <th className="px-4 py-3">Họ tên</th>
                          <th className="px-4 py-3">Quan hệ</th>
                          <th className="px-4 py-3">Ngày sinh</th>
                          <th className="px-4 py-3">Số giấy tờ</th>
                          <th className="px-4 py-3 text-right">Action</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {dependents.map((dependent, index) => (
                          <tr key={`${textValue(dependent, ["id"], String(index))}-${index}`}>
                            <td className="px-4 py-3 text-sm font-semibold text-slate-900">{textValue(dependent, ["full_name", "name"], "—")}</td>
                            <td className="px-4 py-3 text-sm text-slate-600">{textValue(dependent, ["relationship"], "—")}</td>
                            <td className="px-4 py-3 text-sm text-slate-600">{textValue(dependent, ["date_of_birth", "dob"], "") || "—"}</td>
                            <td className="px-4 py-3 text-sm text-slate-600">{textValue(dependent, ["identity_number", "national_id"], "—")}</td>
                            <td className="px-4 py-3 text-right">
                              <button
                                type="button"
                                onClick={() => openEditDependent(dependent)}
                                className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
                              >
                                <Pencil className="h-3.5 w-3.5" />
                                Sửa
                              </button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                ) : (
                  <EmptyState title="Chưa có người phụ thuộc" description="Có thể thêm mới người phụ thuộc ngay trong tab chi tiết." />
                )}
              </div>
            )}
          </div>
        ) : (
          <p className="text-sm text-slate-500">Đang tải chi tiết nhân viên...</p>
        )}
      </Modal>
    </div>
  );
}

function EmployeeEditorModal({
  open,
  editing,
  form,
  pending,
  onClose,
  onChange,
  onSave,
}: {
  open: boolean;
  editing: boolean;
  form: EmployeeForm;
  pending: boolean;
  onClose: () => void;
  onChange: (form: EmployeeForm) => void;
  onSave: () => void;
}) {
  const inputClass = "w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100";

  return (
    <Modal open={open} onClose={onClose} title={editing ? "Sửa nhân viên" : "Thêm nhân viên mới"} size="xl" zIndexClass="z-[60]">
      <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); onSave(); }}>
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Mã nhân viên"><input value={form.employee_code} disabled={editing} onChange={(e) => onChange({ ...form, employee_code: e.target.value })} className={inputClass} /></Field>
          <Field label="Họ và tên"><input value={form.full_name} onChange={(e) => onChange({ ...form, full_name: e.target.value })} className={inputClass} /></Field>
          <Field label="Giới tính">
            <select value={form.gender} onChange={(e) => onChange({ ...form, gender: e.target.value })} className={inputClass}>
              <option value="">Chưa chọn</option>
              <option value="male">Nam</option>
              <option value="female">Nữ</option>
              <option value="other">Khác</option>
            </select>
          </Field>
          <Field label="Ngày sinh"><DateInput value={form.birth_date} onChange={(value) => onChange({ ...form, birth_date: value })} className={inputClass} /></Field>
          <Field label="Số CCCD"><input value={form.id_card_no} onChange={(e) => onChange({ ...form, id_card_no: e.target.value })} className={inputClass} /></Field>
          <Field label="Địa chỉ email"><input type="email" value={form.email} onChange={(e) => onChange({ ...form, email: e.target.value })} className={inputClass} /></Field>
          <Field label="Số điện thoại"><input value={form.mobile} pattern="[0-9+\\-\\s]{8,20}" onChange={(e) => onChange({ ...form, mobile: e.target.value })} className={inputClass} /></Field>
          <Field label="Ngày vào làm"><DateInput value={form.hire_date} onChange={(value) => onChange({ ...form, hire_date: value })} className={inputClass} /></Field>
          <Field label="Ngày nghỉ việc"><DateInput value={form.resign_date} onChange={(value) => onChange({ ...form, resign_date: value })} className={inputClass} /></Field>
          <Field label="Trạng thái">
            <select value={form.status} onChange={(e) => onChange({ ...form, status: e.target.value })} className={inputClass}>
              <option value="active">Đang làm việc</option>
              <option value="inactive">Đình chỉ</option>
              <option value="terminated">Nghỉ việc</option>
            </select>
          </Field>
        </div>
        <div className="flex items-center justify-end gap-3 pt-2">
          <button type="button" onClick={onClose} className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50">Hủy</button>
          <button type="submit" disabled={pending} className="rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg transition hover:opacity-90 active:scale-95 disabled:opacity-60">
            {pending ? "Đang lưu..." : "Lưu"}
          </button>
        </div>
      </form>
    </Modal>
  );
}

function DependentEditorModal({
  open,
  editing,
  form,
  pending,
  onClose,
  onChange,
  onSave,
}: {
  open: boolean;
  editing: boolean;
  form: DependentForm;
  pending: boolean;
  onClose: () => void;
  onChange: (form: DependentForm) => void;
  onSave: () => void;
}) {
  const inputClass = "w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100";

  return (
    <Modal open={open} onClose={onClose} title={editing ? "Sửa người phụ thuộc" : "Thêm người phụ thuộc"} size="md" zIndexClass="z-[60]">
      <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); onSave(); }}>
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Họ tên"><input value={form.full_name} onChange={(e) => onChange({ ...form, full_name: e.target.value })} className={inputClass} /></Field>
          <Field label="Quan hệ"><input value={form.relationship} onChange={(e) => onChange({ ...form, relationship: e.target.value })} className={inputClass} /></Field>
          <Field label="Ngày sinh"><DateInput value={form.date_of_birth} onChange={(value) => onChange({ ...form, date_of_birth: value })} className={inputClass} /></Field>
          <Field label="Số giấy tờ"><input value={form.identity_number} onChange={(e) => onChange({ ...form, identity_number: e.target.value })} className={inputClass} /></Field>
          <Field label="Giảm trừ từ"><DateInput value={form.tax_deduction_from} onChange={(value) => onChange({ ...form, tax_deduction_from: value })} className={inputClass} /></Field>
          <Field label="Giảm trừ đến"><DateInput value={form.tax_deduction_to} onChange={(value) => onChange({ ...form, tax_deduction_to: value })} className={inputClass} /></Field>
        </div>
        <div className="flex items-center justify-end gap-3 pt-2">
          <button type="button" onClick={onClose} className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50">Hủy</button>
          <button type="submit" disabled={pending} className="rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg transition hover:opacity-90 active:scale-95 disabled:opacity-60">
            {pending ? "Đang lưu..." : "Lưu"}
          </button>
        </div>
      </form>
    </Modal>
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
