import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Plus, RefreshCcw, Search, UsersRound } from "lucide-react";
import { useAuth } from "../context/AuthContext";
import { apiGet } from "../lib/api";
import { useDetachedEditor } from "../lib/detachedEditor";
import { formatDate } from "../lib/format";
import { createPermissionSet, hasPermissionAccess } from "../lib/rbac";
import { textValue, toArray } from "../lib/records";
import { Badge, EmptyState, Modal, PageHeader } from "../components/ui";

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
  if (s === "inactive" || s === "nghỉ việc") return <Badge tone="neutral">Nghỉ việc</Badge>;
  if (s === "probation" || s === "thử việc") return <Badge tone="warning">Thử việc</Badge>;
  return <Badge tone="info">{status}</Badge>;
}

export default function EmployeesPage() {
  const { user } = useAuth();
  const [search, setSearch] = useState("");
  const [showModal, setShowModal] = useState(false);
  const [selectedEmployee, setSelectedEmployee] = useState<Record<string, unknown> | null>(null);
  const [detailTab, setDetailTab] = useState<"profile" | "dependents">("profile");
  const { editorAction, openCreateTab, closeDetachedEditor } = useDetachedEditor("/employees");
  const permissionSet = useMemo(() => createPermissionSet(user?.permissions), [user?.permissions]);
  const canCreateEmployee = hasPermissionAccess(permissionSet, "employee.create");
  const modalOpen = canCreateEmployee && (showModal || editorAction === "create");

  function handleCloseModal() {
    if (editorAction === "create") {
      closeDetachedEditor();
      return;
    }

    setShowModal(false);
  }

  const query = useQuery({
    queryKey: ["employees"],
    queryFn: async () => apiGet<unknown>("/employees", { page: 1, per_page: 50 }),
  });

  const employees = useMemo(
    () => toArray<Record<string, unknown>>(query.data?.data),
    [query.data?.data],
  );

  const selectedEmployeeId = selectedEmployee ? textValue(selectedEmployee, ["id"], "") : "";
  const dependentsQuery = useQuery({
    queryKey: ["employees", selectedEmployeeId, "dependents"],
    queryFn: async () => apiGet<unknown>(`/employees/${selectedEmployeeId}/dependents`),
    enabled: Boolean(selectedEmployeeId) && detailTab === "dependents",
  });
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

  return (
    <div className="space-y-8 pb-10">
      <PageHeader
        eyebrow="Nhân sự"
        title="Hồ sơ cán bộ nhân viên"
        description="Quản lý hồ sơ và thông tin nhân viên trong tổ chức."
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
            {canCreateEmployee && (
              <button
                type="button"
                onClick={openCreateTab}
                className="inline-flex items-center gap-2 rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg transition hover:opacity-90 active:scale-95"
              >
                <Plus className="h-4 w-4" />
                Thêm mới
              </button>
            )}
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
          placeholder="Tìm theo tên, mã NV, phòng ban..."
          className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
        />
      </div>

      {/* Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
        <table className="w-full border-collapse text-left">
          <thead className="bg-slate-50/50">
            <tr>
              <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Mã NV</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Họ tên</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Phòng ban</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Chức vụ</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Ngày vào làm</th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">Trạng thái</th>
              <th className="px-4 py-4 text-right text-[10px] font-bold uppercase tracking-widest text-slate-400">Chi tiết</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {query.isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="animate-pulse">
                  <td className="px-6 py-4"><div className="h-3.5 w-20 rounded bg-slate-200" /></td>
                  <td className="px-4 py-4">
                    <div className="flex items-center gap-3">
                      <div className="h-8 w-8 rounded-full bg-slate-200" />
                      <div className="h-3.5 w-32 rounded bg-slate-200" />
                    </div>
                  </td>
                  <td className="px-4 py-4"><div className="h-3.5 w-24 rounded bg-slate-200" /></td>
                  <td className="px-4 py-4"><div className="h-3.5 w-24 rounded bg-slate-200" /></td>
                  <td className="px-4 py-4"><div className="h-3.5 w-20 rounded bg-slate-200" /></td>
                  <td className="px-4 py-4"><div className="h-5 w-20 rounded-full bg-slate-200" /></td>
                  <td className="px-4 py-4"><div className="ml-auto h-8 w-20 rounded bg-slate-200" /></td>
                </tr>
              ))
            ) : filtered.length ? (
              filtered.map((emp, index) => {
                const id = textValue(emp, ["id"], String(index));
                const name = textValue(emp, ["full_name", "name"], "Nhân viên");
                const code = textValue(emp, ["employee_code", "code"], "N/A");
                const dept = textValue(emp, ["department.name", "department_name"], "—");
                const position = textValue(emp, ["position.name", "position_name", "position", "job_title"], "—");
                const startDate = textValue(emp, ["hire_date", "start_date", "joined_at"], "");
                const status = textValue(emp, ["status"], "active");
                const colorClass = avatarColor(name);
                return (
                  <tr key={`${id}-${index}`} className="transition-colors hover:bg-slate-50/50">
                    <td className="px-6 py-4 font-mono text-sm font-medium text-slate-500 tabular-nums">{code}</td>
                    <td className="px-4 py-4">
                      <div className="flex items-center gap-3">
                        <div className={`flex h-8 w-8 items-center justify-center rounded-full text-[10px] font-bold ${colorClass}`}>
                          {getInitials(name)}
                        </div>
                        <p className="text-sm font-semibold text-slate-900">{name}</p>
                      </div>
                    </td>
                    <td className="px-4 py-4">
                      <span className="rounded bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-500">{dept}</span>
                    </td>
                    <td className="px-4 py-4 text-sm text-slate-700">{position}</td>
                    <td className="px-4 py-4 text-sm tabular-nums text-slate-700">{startDate ? formatDate(startDate) : "—"}</td>
                    <td className="px-4 py-4">{statusBadge(status)}</td>
                    <td className="px-4 py-4 text-right">
                      <button
                        type="button"
                        onClick={() => {
                          setSelectedEmployee(emp);
                          setDetailTab("profile");
                        }}
                        className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
                      >
                        <UsersRound className="h-3.5 w-3.5" />
                        Tab chi tiết
                      </button>
                    </td>
                  </tr>
                );
              })
            ) : (
              <tr>
                <td colSpan={7} className="py-10">
                  <EmptyState
                    title="Không có nhân viên"
                    description="Dữ liệu nhân viên chưa được backend trả về hoặc không khớp bộ lọc."
                  />
                </td>
              </tr>
            )}
          </tbody>
        </table>
        <div className="flex items-center justify-between border-t border-slate-100 bg-slate-50/30 px-6 py-4">
          <p className="text-xs text-slate-500">
            Hiển thị <span className="font-bold text-slate-800">{filtered.length}</span> trong{" "}
            <span className="font-bold text-slate-800">{employees.length}</span> nhân viên
          </p>
        </div>
      </div>

      {/* Add Employee Modal */}
      <Modal open={modalOpen} onClose={handleCloseModal} title="Thêm nhân viên mới" size="md">
        <form className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1 block text-sm font-semibold text-slate-700">Mã nhân viên</label>
              <input type="text" placeholder="Ví dụ: NV001" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-semibold text-slate-700">Họ và tên</label>
              <input type="text" placeholder="Nguyễn Văn A" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-semibold text-slate-700">Phòng ban</label>
              <input type="text" placeholder="Phòng kế toán" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-semibold text-slate-700">Chức vụ</label>
              <input type="text" placeholder="Nhân viên" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-semibold text-slate-700">Ngày vào làm</label>
              <input type="date" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-semibold text-slate-700">Trạng thái</label>
              <select className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                <option value="active">Đang làm việc</option>
                <option value="probation">Thử việc</option>
                <option value="inactive">Nghỉ việc</option>
              </select>
            </div>
          </div>
          <div className="flex items-center justify-end gap-3 pt-2">
            <button type="button" onClick={handleCloseModal} className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50">
              Hủy
            </button>
            <button type="button" onClick={handleCloseModal} className="rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg transition hover:opacity-90 active:scale-95">
              Lưu
            </button>
          </div>
        </form>
      </Modal>

      <Modal
        open={Boolean(selectedEmployee)}
        onClose={() => setSelectedEmployee(null)}
        title={selectedEmployee ? textValue(selectedEmployee, ["full_name", "name"], "Chi tiết nhân viên") : "Chi tiết nhân viên"}
        size="xl"
      >
        {selectedEmployee && (
          <div className="space-y-5">
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
                    detailTab === tab.key
                      ? "bg-slate-950 text-white"
                      : "text-slate-600 hover:bg-white"
                  }`}
                >
                  {tab.label}
                </button>
              ))}
            </div>

            {detailTab === "profile" ? (
              <div className="grid gap-3 sm:grid-cols-2">
                {[
                  ["Mã NV", textValue(selectedEmployee, ["employee_code", "code"], "N/A")],
                  ["Họ tên", textValue(selectedEmployee, ["full_name", "name"], "—")],
                  ["Phòng ban", textValue(selectedEmployee, ["department.name", "department_name"], "—")],
                  ["Chức vụ", textValue(selectedEmployee, ["position.name", "position_name", "position", "job_title"], "—")],
                  ["Ngày vào làm", textValue(selectedEmployee, ["hire_date", "start_date", "joined_at"], "")],
                  ["Trạng thái", textValue(selectedEmployee, ["status"], "active")],
                ].map(([label, value]) => (
                  <div key={label} className="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                    <p className="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">{label}</p>
                    <p className="mt-1 text-sm font-semibold text-slate-900">{value || "—"}</p>
                  </div>
                ))}
              </div>
            ) : dependentsQuery.isLoading ? (
              <p className="text-sm text-slate-500">Đang tải người phụ thuộc...</p>
            ) : dependents.length ? (
              <div className="overflow-x-auto">
                <table className="w-full border-collapse text-left">
                  <thead>
                    <tr className="bg-slate-50 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                      <th className="px-4 py-3">Họ tên</th>
                      <th className="px-4 py-3">Quan hệ</th>
                      <th className="px-4 py-3">Ngày sinh</th>
                      <th className="px-4 py-3">Mã số thuế</th>
                      <th className="px-4 py-3">Giảm trừ từ</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {dependents.map((dependent, index) => (
                      <tr key={`${textValue(dependent, ["id"], String(index))}-${index}`}>
                        <td className="px-4 py-3 text-sm font-semibold text-slate-900">{textValue(dependent, ["full_name", "name"], "—")}</td>
                        <td className="px-4 py-3 text-sm text-slate-600">{textValue(dependent, ["relationship"], "—")}</td>
                        <td className="px-4 py-3 text-sm text-slate-600">{textValue(dependent, ["date_of_birth", "dob"], "") || "—"}</td>
                        <td className="px-4 py-3 text-sm text-slate-600">{textValue(dependent, ["tax_code", "tax_no"], "—")}</td>
                        <td className="px-4 py-3 text-sm text-slate-600">{textValue(dependent, ["tax_reduction_from"], "") || "—"}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <EmptyState
                title="Chưa có người phụ thuộc"
                description="Backend không trả về dữ liệu người phụ thuộc cho nhân viên này."
              />
            )}
          </div>
        )}
      </Modal>
    </div>
  );
}
