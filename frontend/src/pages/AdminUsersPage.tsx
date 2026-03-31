import { useEffect, useMemo, useState } from "react";
import type { FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  KeyRound,
  LockKeyhole,
  Pencil,
  RefreshCcw,
  Search,
  Shield,
  ShieldCheck,
  Trash2,
  UserPlus,
  Users,
} from "lucide-react";
import { apiGet, apiPost, apiPut, getApiErrorMessage } from "../lib/api";
import { formatDateTime, formatNumber } from "../lib/format";
import { boolValue, textValue, toArray } from "../lib/records";
import { Badge, EmptyState, PageHeader } from "../components/ui";

type NewUserState = {
  username: string;
  name: string;
  email: string;
  password: string;
  role: string;
  department: string;
};

const initialNewUser: NewUserState = {
  username: "",
  name: "",
  email: "",
  password: "",
  role: "hr_staff",
  department: "",
};

function getRoleBadgeClasses(role: string): string {
  switch (role) {
    case "system_admin":
      return "bg-indigo-100 text-indigo-700 ring-indigo-200";
    case "hr_staff":
      return "bg-sky-100 text-sky-700 ring-sky-200";
    case "accountant":
      return "bg-slate-100 text-slate-600 ring-slate-200";
    case "management":
      return "bg-emerald-50 text-emerald-700 ring-emerald-200";
    default:
      return "bg-slate-100 text-slate-600 ring-slate-200";
  }
}

function getRoleIcon(role: string) {
  switch (role) {
    case "system_admin":
      return <LockKeyhole className="h-3 w-3" />;
    case "hr_staff":
      return <Shield className="h-3 w-3" />;
    default:
      return <Shield className="h-3 w-3" />;
  }
}

function getUserInitials(name: string): string {
  return name
    .split(" ")
    .slice(0, 2)
    .map((part) => part[0] ?? "")
    .join("")
    .toUpperCase();
}

export default function AdminUsersPage() {
  const queryClient = useQueryClient();
  const [selectedId, setSelectedId] = useState<string>("");
  const [newUser, setNewUser] = useState<NewUserState>(initialNewUser);
  const [editUser, setEditUser] = useState({
    name: "",
    email: "",
    role: "hr_staff",
    department: "",
    is_active: true,
  });
  const [error, setError] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [roleFilter, setRoleFilter] = useState("");
  const [statusFilter, setStatusFilter] = useState("");
  const [showCreateForm, setShowCreateForm] = useState(false);

  const usersQuery = useQuery({
    queryKey: ["admin", "users"],
    queryFn: async () => apiGet<unknown>("/users"),
  });

  const rolesQuery = useQuery({
    queryKey: ["admin", "roles"],
    queryFn: async () => apiGet<unknown>("/roles"),
  });

  const users = useMemo(() => toArray<Record<string, unknown>>(usersQuery.data?.data), [usersQuery.data?.data]);
  const roles = useMemo(() => toArray<Record<string, unknown>>(rolesQuery.data?.data), [rolesQuery.data?.data]);
  const currentSelected = useMemo(
    () => users.find((user) => String(textValue(user, ["id"], "")) === selectedId) ?? users[0] ?? null,
    [selectedId, users],
  );

  const filteredUsers = useMemo(() => {
    return users.filter((user) => {
      const name = textValue(user, ["name"], "").toLowerCase();
      const email = textValue(user, ["email"], "").toLowerCase();
      const username = textValue(user, ["username"], "").toLowerCase();
      const role = textValue(user, ["role"], "");
      const isActive = boolValue(user, ["is_active"], true);
      const q = searchQuery.toLowerCase();

      const matchesSearch = !q || name.includes(q) || email.includes(q) || username.includes(q);
      const matchesRole = !roleFilter || role === roleFilter;
      const matchesStatus =
        !statusFilter ||
        (statusFilter === "active" && isActive) ||
        (statusFilter === "inactive" && !isActive);

      return matchesSearch && matchesRole && matchesStatus;
    });
  }, [users, searchQuery, roleFilter, statusFilter]);

  useEffect(() => {
    if (!selectedId && currentSelected) {
      setSelectedId(String(textValue(currentSelected, ["id"], "")));
    }
  }, [currentSelected, selectedId]);

  useEffect(() => {
    if (currentSelected) {
      setEditUser({
        name: textValue(currentSelected, ["name"], ""),
        email: textValue(currentSelected, ["email"], ""),
        role: textValue(currentSelected, ["role"], "hr_staff"),
        department: textValue(currentSelected, ["department", "department_name"], ""),
        is_active: boolValue(currentSelected, ["is_active"], true),
      });
    }
  }, [currentSelected]);

  const createMutation = useMutation({
    mutationFn: async () => apiPost<unknown>("/users", newUser),
    onSuccess: async () => {
      setError(null);
      setNewUser(initialNewUser);
      setShowCreateForm(false);
      await queryClient.invalidateQueries({ queryKey: ["admin", "users"] });
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể tạo user."));
    },
  });

  const updateMutation = useMutation({
    mutationFn: async () =>
      apiPut<unknown>(`/users/${selectedId}`, {
        ...editUser,
        is_active: editUser.is_active,
      }),
    onSuccess: async () => {
      setError(null);
      await queryClient.invalidateQueries({ queryKey: ["admin", "users"] });
    },
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể cập nhật user."));
    },
  });

  const resetPasswordMutation = useMutation({
    mutationFn: async () => apiPost<unknown>(`/users/${selectedId}/reset-password`),
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể reset password."));
    },
  });

  const assignRolesMutation = useMutation({
    mutationFn: async () =>
      apiPost<unknown>(`/users/${selectedId}/roles`, {
        roles: [editUser.role],
      }),
    onError: (mutationError) => {
      setError(getApiErrorMessage(mutationError, "Không thể gán role."));
    },
  });

  function submitCreate(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    createMutation.mutate();
  }

  function submitUpdate(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    updateMutation.mutate();
  }

  const activeCount = users.filter((u) => boolValue(u, ["is_active"], true)).length;

  return (
    <div className="space-y-8">
      {/* Page Header */}
      <PageHeader
        eyebrow="Quản trị"
        title="Quản lý người dùng"
        description="Quản lý tài khoản và phân quyền hệ thống."
        actions={
          <button
            type="button"
            onClick={() => setShowCreateForm(true)}
            className="inline-flex items-center gap-2 rounded-lg bg-gradient-to-br from-slate-950 to-indigo-700 px-6 py-3 text-sm font-bold text-white shadow-lg transition hover:shadow-xl hover:opacity-90 active:scale-95"
          >
            <UserPlus className="h-4 w-4" />
            Thêm người dùng mới
          </button>
        }
      />

      {/* Stats Row */}
      <div className="grid gap-4 md:grid-cols-3">
        <div className="rounded-xl border border-indigo-100 bg-indigo-50/60 p-5">
          <div className="flex items-center gap-4">
            <div className="flex h-11 w-11 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
              <Users className="h-5 w-5" />
            </div>
            <div>
              <p className="text-xs font-bold uppercase tracking-widest text-slate-500">Tổng người dùng</p>
              <p className="text-2xl font-extrabold text-slate-900">{formatNumber(users.length)}</p>
            </div>
          </div>
        </div>
        <div className="rounded-xl border border-emerald-100 bg-emerald-50/60 p-5">
          <div className="flex items-center gap-4">
            <div className="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
              <ShieldCheck className="h-5 w-5" />
            </div>
            <div>
              <p className="text-xs font-bold uppercase tracking-widest text-slate-500">Đang hoạt động</p>
              <p className="text-2xl font-extrabold text-slate-900">{formatNumber(activeCount)}</p>
            </div>
          </div>
        </div>
        <div className="rounded-xl border border-slate-100 bg-slate-50 p-5">
          <div className="flex items-center gap-4">
            <div className="flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-600">
              <Shield className="h-5 w-5" />
            </div>
            <div>
              <p className="text-xs font-bold uppercase tracking-widest text-slate-500">Vai trò</p>
              <p className="text-2xl font-extrabold text-slate-900">{formatNumber(roles.length)}</p>
            </div>
          </div>
        </div>
      </div>

      {error && (
        <p className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{error}</p>
      )}

      {/* Filters & Controls */}
      <div className="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
        <div className="flex flex-wrap items-center gap-4">
          <div className="relative min-w-[280px] flex-1">
            <Search className="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Tìm theo tên, email hoặc tên đăng nhập..."
              className="w-full rounded-lg border-0 bg-slate-100 py-2.5 pl-11 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-200 outline-none transition"
            />
          </div>
          <div className="flex items-center gap-3">
            <div className="relative">
              <select
                value={roleFilter}
                onChange={(e) => setRoleFilter(e.target.value)}
                className="appearance-none rounded-lg border-0 bg-slate-100 py-2.5 pl-4 pr-9 text-sm font-medium text-slate-600 focus:ring-2 focus:ring-indigo-200 outline-none cursor-pointer"
              >
                <option value="">Tất cả vai trò</option>
                <option value="system_admin">Quản trị hệ thống</option>
                <option value="hr_staff">Nhân sự</option>
                <option value="accountant">Kế toán</option>
                <option value="management">Quản lý</option>
              </select>
            </div>
            <div className="relative">
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="appearance-none rounded-lg border-0 bg-slate-100 py-2.5 pl-4 pr-9 text-sm font-medium text-slate-600 focus:ring-2 focus:ring-indigo-200 outline-none cursor-pointer"
              >
                <option value="">Tất cả</option>
                <option value="active">Hoạt động</option>
                <option value="inactive">Ngưng hoạt động</option>
              </select>
            </div>
            <button
              type="button"
              onClick={() => usersQuery.refetch()}
              className="rounded-lg border border-slate-200 p-2.5 text-slate-500 transition hover:bg-slate-50 active:scale-95"
              title="Làm mới"
            >
              <RefreshCcw className="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>

      {/* Main layout: table + edit panel */}
      <div className="grid gap-6 xl:grid-cols-[1fr_420px]">
        {/* Users Table */}
        <div className="rounded-xl border border-slate-100 bg-white shadow-sm overflow-hidden">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-slate-50/60">
                <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Thông tin người dùng</th>
                <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Vai trò hệ thống</th>
                <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Phòng ban</th>
                <th className="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Trạng thái</th>
                <th className="px-6 py-4 text-right text-[10px] font-bold uppercase tracking-widest text-slate-500">Hành động</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {usersQuery.isLoading ? (
                <tr>
                  <td colSpan={5} className="px-6 py-10 text-center text-sm text-slate-500">
                    Đang tải...
                  </td>
                </tr>
              ) : filteredUsers.length === 0 ? (
                <tr>
                  <td colSpan={5} className="px-6 py-10 text-center">
                    <EmptyState
                      title="Không tìm thấy người dùng"
                      description="Thử điều chỉnh bộ lọc hoặc thêm người dùng mới."
                    />
                  </td>
                </tr>
              ) : (
                filteredUsers.map((user, index) => {
                  const id = String(textValue(user, ["id"], String(index)));
                  const isSelected = id === selectedId;
                  const isActive = boolValue(user, ["is_active"], true);
                  const name = textValue(user, ["name"], "User");
                  const email = textValue(user, ["email"], "");
                  const role = textValue(user, ["role"], "");

                  return (
                    <tr
                      key={`${id}-${index}`}
                      onClick={() => setSelectedId(id)}
                      className={`cursor-pointer transition hover:bg-slate-50/60 group ${
                        isSelected ? "bg-indigo-50/40 border-l-2 border-l-indigo-500" : ""
                      }`}
                    >
                      <td className="px-6 py-5">
                        <div className="flex items-center gap-3">
                          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-slate-100 to-slate-200 text-xs font-bold text-slate-600 select-none">
                            {getUserInitials(name)}
                          </div>
                          <div>
                            <p className="text-sm font-bold text-slate-900">{name}</p>
                            <p className="text-xs text-slate-500">{email}</p>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-5">
                        <span
                          className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${getRoleBadgeClasses(role)}`}
                        >
                          {getRoleIcon(role)}
                          {role || "—"}
                        </span>
                      </td>
                      <td className="px-6 py-5 text-sm text-slate-600">
                        {textValue(user, ["department", "department_name"], "—")}
                      </td>
                      <td className="px-6 py-5">
                        {isActive ? (
                          <Badge tone="success">Hoạt động</Badge>
                        ) : (
                          <Badge tone="neutral">Ngưng hoạt động</Badge>
                        )}
                      </td>
                      <td className="px-6 py-5 text-right">
                        <div className="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                          <button
                            type="button"
                            onClick={(e) => {
                              e.stopPropagation();
                              setSelectedId(id);
                            }}
                            className="rounded p-1.5 text-slate-400 transition hover:text-indigo-600"
                            title="Chỉnh sửa người dùng"
                          >
                            <Pencil className="h-4 w-4" />
                          </button>
                          <button
                            type="button"
                            onClick={(e) => {
                              e.stopPropagation();
                              setSelectedId(id);
                              resetPasswordMutation.mutate();
                            }}
                            className="rounded p-1.5 text-slate-400 transition hover:text-indigo-600"
                            title="Đặt lại mật khẩu"
                          >
                            <KeyRound className="h-4 w-4" />
                          </button>
                          <button
                            type="button"
                            onClick={(e) => e.stopPropagation()}
                            className="rounded p-1.5 text-slate-400 transition hover:text-rose-600"
                            title="Vô hiệu hóa"
                          >
                            <Trash2 className="h-4 w-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>

          {/* Table Footer */}
          <div className="flex items-center justify-between border-t border-slate-100 bg-slate-50/40 px-6 py-4">
            <p className="text-xs text-slate-500">
              Hiển thị <span className="font-bold text-slate-900">{filteredUsers.length}</span> trong tổng số{" "}
              <span className="font-bold text-slate-900">{users.length}</span> người dùng
            </p>
          </div>
        </div>

        {/* Right Panel: Edit or Create */}
        <div className="space-y-6">
          {/* Edit Panel */}
          <section className="rounded-xl border border-slate-100 bg-white shadow-sm overflow-hidden">
            <div className="border-b border-slate-100 px-6 py-4">
              <p className="text-xs font-bold uppercase tracking-widest text-slate-500">
                {selectedId ? "Chỉnh sửa người dùng" : "Chọn người dùng"}
              </p>
              <h3 className="mt-0.5 text-sm font-semibold text-slate-900">
                {currentSelected ? textValue(currentSelected, ["name"], "Người dùng") : "Chưa chọn người dùng"}
              </h3>
            </div>

            {!selectedId || !currentSelected ? (
              <div className="px-6 py-8">
                <EmptyState
                  title="Chưa chọn người dùng"
                  description="Nhấp vào một dòng trong bảng để chỉnh sửa."
                />
              </div>
            ) : (
              <div className="p-6 space-y-4">
                {/* Quick info */}
                <div className="grid grid-cols-2 gap-3">
                  {[
                    ["Tên đăng nhập", textValue(currentSelected, ["username"], "—")],
                    ["Đăng nhập lần cuối", formatDateTime(textValue(currentSelected, ["last_login"], ""))],
                  ].map(([label, value]) => (
                    <div key={String(label)} className="rounded-lg bg-slate-50 px-4 py-3">
                      <p className="text-[10px] font-bold uppercase tracking-widest text-slate-400">{label as string}</p>
                      <p className="mt-1 text-sm font-medium text-slate-900">{String(value)}</p>
                    </div>
                  ))}
                </div>

                <form className="space-y-4" onSubmit={submitUpdate}>
                  <div className="grid gap-4 sm:grid-cols-2">
                    {[
                      ["Họ tên", "name"],
                      ["Email", "email"],
                      ["Vai trò", "role"],
                      ["Phòng ban", "department"],
                    ].map(([label, key]) => (
                      <label key={key} className="space-y-1.5">
                        <span className="text-xs font-semibold uppercase tracking-widest text-slate-500">
                          {label}
                        </span>
                        <input
                          value={editUser[key as keyof typeof editUser] as string}
                          onChange={(e) =>
                            setEditUser((cur) => ({ ...cur, [key]: e.target.value }))
                          }
                          className="w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 transition"
                        />
                      </label>
                    ))}
                  </div>

                  <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={editUser.is_active}
                      onChange={(e) =>
                        setEditUser((cur) => ({ ...cur, is_active: e.target.checked }))
                      }
                      className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <span className="text-sm font-medium text-slate-700">Tài khoản hoạt động</span>
                  </label>

                  <div className="grid gap-3 sm:grid-cols-2">
                    <button
                      type="submit"
                      disabled={updateMutation.isPending}
                      className="inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-br from-slate-950 to-indigo-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                      <Users className="h-4 w-4" />
                      {updateMutation.isPending ? "Đang lưu..." : "Cập nhật"}
                    </button>
                    <button
                      type="button"
                      onClick={() => resetPasswordMutation.mutate()}
                      disabled={resetPasswordMutation.isPending}
                      className="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                      <KeyRound className="h-4 w-4" />
                      Đặt lại mật khẩu
                    </button>
                    <button
                      type="button"
                      onClick={() => assignRolesMutation.mutate()}
                      disabled={assignRolesMutation.isPending}
                      className="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 disabled:cursor-not-allowed disabled:opacity-60 sm:col-span-2"
                    >
                      <Shield className="h-4 w-4" />
                      {assignRolesMutation.isPending ? "Đang gán..." : "Gán vai trò"}
                    </button>
                  </div>
                </form>
              </div>
            )}
          </section>

          {/* Create User Panel */}
          <section className="rounded-xl border border-slate-100 bg-white shadow-sm overflow-hidden">
            <button
              type="button"
              onClick={() => setShowCreateForm((v) => !v)}
              className="flex w-full items-center justify-between px-6 py-4 text-left transition hover:bg-slate-50"
            >
              <div className="flex items-center gap-2">
                <UserPlus className="h-4 w-4 text-indigo-600" />
                <span className="text-sm font-semibold text-slate-900">Thêm người dùng mới</span>
              </div>
              <span className="text-xs text-slate-400">POST /users</span>
            </button>

            {showCreateForm && (
              <div className="border-t border-slate-100 p-6">
                <form className="grid gap-4 sm:grid-cols-2" onSubmit={submitCreate}>
                  {[
                    ["Tên đăng nhập", "username", "text"],
                    ["Họ và tên", "name", "text"],
                    ["Email", "email", "email"],
                    ["Mật khẩu", "password", "password"],
                    ["Vai trò", "role", "text"],
                    ["Phòng ban", "department", "text"],
                  ].map(([label, key, type]) => (
                    <label
                      key={key}
                      className={`space-y-1.5 ${key === "department" ? "sm:col-span-2" : ""}`}
                    >
                      <span className="text-xs font-semibold uppercase tracking-widest text-slate-500">
                        {label}
                      </span>
                      <input
                        type={type}
                        value={newUser[key as keyof NewUserState]}
                        onChange={(e) =>
                          setNewUser((cur) => ({ ...cur, [key]: e.target.value }))
                        }
                        className="w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 transition"
                      />
                    </label>
                  ))}
                  <button
                    type="submit"
                    disabled={createMutation.isPending}
                    className="inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-br from-slate-950 to-indigo-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60 sm:col-span-2"
                  >
                    <UserPlus className="h-4 w-4" />
                    {createMutation.isPending ? "Đang tạo..." : "Tạo người dùng"}
                  </button>
                </form>
              </div>
            )}
          </section>
        </div>
      </div>

      {/* Footer Info Grid */}
      <div className="grid gap-6 md:grid-cols-3">
        <div className="rounded-xl border border-indigo-100 bg-indigo-50/40 p-6">
          <div className="flex items-center gap-4">
            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
              <ShieldCheck className="h-6 w-6" />
            </div>
            <div>
              <h4 className="font-bold text-slate-900">Bảo mật hệ thống</h4>
              <p className="text-xs text-slate-500">Xác thực 2 yếu tố đã được kích hoạt cho 98% người dùng.</p>
            </div>
          </div>
        </div>
        <div className="rounded-xl border border-emerald-100 bg-emerald-50/40 p-6">
          <div className="flex items-center gap-4">
            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
              <Users className="h-6 w-6" />
            </div>
            <div>
              <h4 className="font-bold text-slate-900">Chờ phê duyệt</h4>
              <p className="text-xs text-slate-500">Yêu cầu tạo tài khoản mới đang chờ xác minh từ bộ phận nhân sự.</p>
            </div>
          </div>
        </div>
        <div className="rounded-xl border border-slate-200 bg-slate-50 p-6">
          <div className="flex items-center gap-4">
            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
              <Shield className="h-6 w-6" />
            </div>
            <div>
              <h4 className="font-bold text-slate-900">Nhật ký kiểm toán</h4>
              <p className="text-xs text-slate-500">Xem lại lịch sử truy cập và thay đổi quản trị gần đây.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
