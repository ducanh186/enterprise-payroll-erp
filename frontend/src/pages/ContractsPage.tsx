import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import {
  Search,
  Filter,
  Download,
  Eye,
  Edit,
  RefreshCcw,
  ChevronLeft,
  ChevronRight,
  Plus,
} from "lucide-react";
import { apiGet } from "../lib/api";
import { formatCurrency, formatDate } from "../lib/format";
import { numberValue, textValue, toArray } from "../lib/records";
import { Badge, EmptyState } from "../components/ui";

function getStatusBadge(status: string) {
  const s = status.toLowerCase();
  if (s.includes("active") || s.includes("confirmed")) {
    return (
      <span className="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-200">
        <span className="mr-1.5 h-1 w-1 rounded-full bg-emerald-600" />
        Đang hiệu lực
      </span>
    );
  }
  if (s.includes("expir") || s.includes("near") || s.includes("soon")) {
    return (
      <span className="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-amber-200">
        <span className="mr-1.5 h-1 w-1 rounded-full bg-amber-600" />
        Sắp hết hạn
      </span>
    );
  }
  if (s.includes("expired") || s.includes("terminated") || s.includes("ended")) {
    return (
      <span className="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500 ring-1 ring-slate-200">
        <span className="mr-1.5 h-1 w-1 rounded-full bg-slate-400" />
        Hết hạn
      </span>
    );
  }
  return (
    <span className="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 ring-1 ring-indigo-200">
      <span className="mr-1.5 h-1 w-1 rounded-full bg-indigo-600" />
      {status}
    </span>
  );
}

function getInitials(name: string): string {
  return name
    .split(" ")
    .map((w) => w[0])
    .join("")
    .slice(0, 2)
    .toUpperCase();
}

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

export default function ContractsPage() {
  const [selectedId, setSelectedId] = useState<string>("");
  const [search, setSearch] = useState("");
  const [deptFilter, setDeptFilter] = useState("");
  const [typeFilter, setTypeFilter] = useState("");
  const [statusFilter, setStatusFilter] = useState("");

  const contractsQuery = useQuery({
    queryKey: ["contracts"],
    queryFn: async () => apiGet<unknown>("/contracts", { page: 1, per_page: 20 }),
  });

  const selectedQuery = useQuery({
    queryKey: ["contracts", selectedId],
    queryFn: async () => apiGet<unknown>(`/contracts/${selectedId}`),
    enabled: Boolean(selectedId),
  });

  const contracts = useMemo(() => toArray<Record<string, unknown>>(contractsQuery.data?.data), [contractsQuery.data?.data]);
  const selected =
    selectedQuery.data?.data && typeof selectedQuery.data.data === "object"
      ? (selectedQuery.data.data as Record<string, unknown>)
      : null;

  const filteredContracts = useMemo(() => {
    return contracts.filter((item) => {
      const name = textValue(item, ["employee.full_name", "employee_name", "full_name"], "").toLowerCase();
      const code = textValue(item, ["employee.employee_code", "employee_code"], "").toLowerCase();
      const dept = textValue(item, ["employee.department.name", "department_name"], "").toLowerCase();
      const type = textValue(item, ["contractType.name", "contract_type.name", "contract_type", "type"], "").toLowerCase();
      const status = textValue(item, ["status"], "").toLowerCase();

      if (search && !name.includes(search.toLowerCase()) && !code.includes(search.toLowerCase())) return false;
      if (deptFilter && !dept.includes(deptFilter.toLowerCase())) return false;
      if (typeFilter && !type.includes(typeFilter.toLowerCase())) return false;
      if (statusFilter && !status.includes(statusFilter.toLowerCase())) return false;
      return true;
    });
  }, [contracts, search, deptFilter, typeFilter, statusFilter]);

  // Summary stats
  const activeCount = contracts.filter((c) => textValue(c, ["status"], "").toLowerCase().includes("active")).length;
  const expiringCount = contracts.filter((c) => {
    const s = textValue(c, ["status"], "").toLowerCase();
    return s.includes("expir") && !s.includes("expired");
  }).length;
  const totalSalary = contracts.reduce((sum, c) => sum + numberValue(c, ["base_salary"], 0), 0);

  return (
    <div className="space-y-8 pb-10">
      {/* Page Title */}
      <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
        <div>
          <p className="text-xs font-bold uppercase tracking-[0.24em] text-slate-500">HR</p>
          <h2 className="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">
            Danh mục hợp đồng
          </h2>
          <p className="mt-1 text-sm text-slate-500">
            Quản lý và theo dõi tất cả hợp đồng lao động
          </p>
        </div>
        <div className="flex items-center gap-3">
          <button
            type="button"
            onClick={() => contractsQuery.refetch()}
            className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
          >
            <RefreshCcw className="h-4 w-4" />
            Làm mới
          </button>
          <button
            type="button"
            className="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-bold text-indigo-700 transition hover:bg-indigo-100"
          >
            <Download className="h-4 w-4" />
            Xuất CSV
          </button>
          <button
            type="button"
            className="inline-flex items-center gap-2 rounded-xl bg-gradient-to-br from-slate-950 to-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg transition hover:opacity-90 active:scale-95"
          >
            <Plus className="h-4 w-4" />
            Hợp đồng mới
          </button>
        </div>
      </div>

      {/* Filter Bar */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          {/* Search */}
          <div className="relative">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Tìm theo tên / mã NV..."
              className="w-64 rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-4 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
            />
          </div>

          {/* Grouped selects */}
          <div className="flex items-center rounded-xl border border-slate-200 bg-white shadow-sm">
            <select
              value={deptFilter}
              onChange={(e) => setDeptFilter(e.target.value)}
              className="border-none bg-transparent py-2 pl-3 pr-2 text-xs font-semibold text-slate-600 focus:ring-0"
            >
              <option value="">Tất cả phòng ban</option>
            </select>
            <span className="h-4 w-px bg-slate-200" />
            <select
              value={typeFilter}
              onChange={(e) => setTypeFilter(e.target.value)}
              className="border-none bg-transparent py-2 pl-3 pr-2 text-xs font-semibold text-slate-600 focus:ring-0"
            >
              <option value="">Loại hợp đồng</option>
              <option value="full">Toàn thời gian</option>
              <option value="part">Bán thời gian</option>
              <option value="probation">Thử việc</option>
            </select>
            <span className="h-4 w-px bg-slate-200" />
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="border-none bg-transparent py-2 pl-3 pr-2 text-xs font-semibold text-slate-600 focus:ring-0"
            >
              <option value="">Trạng thái</option>
              <option value="active">Đang hiệu lực</option>
              <option value="expir">Sắp hết hạn</option>
              <option value="expired">Hết hạn</option>
            </select>
          </div>

          <button
            type="button"
            onClick={() => { setSearch(""); setDeptFilter(""); setTypeFilter(""); setStatusFilter(""); }}
            className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-200"
          >
            <Filter className="h-3.5 w-3.5" />
            Xóa bộ lọc
          </button>
        </div>
      </div>

      {/* Main Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
        <table className="w-full border-collapse text-left">
          <thead className="bg-slate-50/50">
            <tr>
              <th className="px-6 py-4">
                <input type="checkbox" className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-200" />
              </th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                Tên nhân viên
              </th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                Mã NV
              </th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                Phòng ban
              </th>
              <th className="px-4 py-4 text-center text-[10px] font-bold uppercase tracking-widest text-slate-400">
                Loại
              </th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                Ngày hiệu lực
              </th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                Lương cơ bản
              </th>
              <th className="px-4 py-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                Trạng thái
              </th>
              <th className="px-6 py-4 text-right text-[10px] font-bold uppercase tracking-widest text-slate-400">
                Hành động
              </th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {contractsQuery.isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="animate-pulse">
                  <td className="px-6 py-4"><div className="h-4 w-4 rounded bg-slate-200" /></td>
                  <td className="px-4 py-4">
                    <div className="flex items-center gap-3">
                      <div className="h-8 w-8 rounded-full bg-slate-200" />
                      <div className="space-y-1">
                        <div className="h-3.5 w-32 rounded bg-slate-200" />
                        <div className="h-3 w-24 rounded bg-slate-100" />
                      </div>
                    </div>
                  </td>
                  <td className="px-4 py-4"><div className="h-3.5 w-20 rounded bg-slate-200" /></td>
                  <td className="px-4 py-4"><div className="h-5 w-24 rounded bg-slate-200" /></td>
                  <td className="px-4 py-4 text-center"><div className="mx-auto h-3.5 w-16 rounded bg-slate-200" /></td>
                  <td className="px-4 py-4"><div className="h-3.5 w-24 rounded bg-slate-200" /></td>
                  <td className="px-4 py-4"><div className="h-3.5 w-24 rounded bg-slate-200" /></td>
                  <td className="px-4 py-4"><div className="h-5 w-20 rounded-full bg-slate-200" /></td>
                  <td className="px-6 py-4" />
                </tr>
              ))
            ) : filteredContracts.length ? (
              filteredContracts.map((item, index) => {
                const id = String(textValue(item, ["id"], String(index)));
                const isSelected = id === selectedId;
                const name = textValue(item, ["employee.full_name", "employee_name", "full_name"], "Nhân viên");
                const code = textValue(item, ["employee.employee_code", "employee_code"], "N/A");
                const dept = textValue(item, ["employee.department.name", "department_name"], "N/A");
                const contractType = textValue(item, ["contractType.name", "contract_type.name", "contract_type", "type"], "N/A");
                const startDate = formatDate(textValue(item, ["start_date"], ""));
                const endDate = textValue(item, ["end_date"], "");
                const baseSalary = formatCurrency(numberValue(item, ["base_salary"], 0));
                const status = textValue(item, ["status"], "draft");
                const initials = getInitials(name);
                const colorClass = avatarColor(name);

                return (
                  <tr
                    key={`${id}-${index}`}
                    onClick={() => setSelectedId(id === selectedId ? "" : id)}
                    className={`group cursor-pointer transition-colors hover:bg-slate-50/50 ${isSelected ? "bg-indigo-50/50 ring-2 ring-inset ring-indigo-200" : ""}`}
                  >
                    <td className="px-6 py-4">
                      <input
                        type="checkbox"
                        onClick={(e) => e.stopPropagation()}
                        className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-200"
                      />
                    </td>
                    <td className="px-4 py-4">
                      <div className="flex items-center gap-3">
                        <div className={`flex h-8 w-8 items-center justify-center rounded-full text-[10px] font-bold ${colorClass}`}>
                          {initials}
                        </div>
                        <div>
                          <p className="text-sm font-semibold text-slate-900">{name}</p>
                          <p className="text-[11px] text-slate-400">{code}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-4 font-mono text-sm font-medium text-slate-500 tabular-nums">
                      #{code}
                    </td>
                    <td className="px-4 py-4">
                      <span className="rounded bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-500">
                        {dept}
                      </span>
                    </td>
                    <td className="px-4 py-4 text-center text-xs font-medium text-slate-700">
                      {contractType}
                    </td>
                    <td className="px-4 py-4">
                      <div className="tabular-nums">
                        <p className="text-xs font-semibold text-slate-800">{startDate}</p>
                        {endDate ? (
                          <p className="text-[10px] text-slate-400">{formatDate(endDate)}</p>
                        ) : (
                          <p className="text-[10px] text-slate-400">Không xác định</p>
                        )}
                      </div>
                    </td>
                    <td className="px-4 py-4 text-sm font-semibold tabular-nums text-slate-800">
                      {baseSalary}
                    </td>
                    <td className="px-4 py-4">
                      {getStatusBadge(status)}
                    </td>
                    <td className="px-6 py-4 text-right">
                      <div className="flex items-center justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                        <button
                          onClick={(e) => { e.stopPropagation(); setSelectedId(id); }}
                          className="rounded-lg p-1.5 text-slate-400 transition hover:text-indigo-600"
                          title="Xem chi tiết"
                        >
                          <Eye className="h-4 w-4" />
                        </button>
                        <button
                          onClick={(e) => e.stopPropagation()}
                          className="rounded-lg p-1.5 text-slate-400 transition hover:text-indigo-600"
                          title="Chỉnh sửa"
                        >
                          <Edit className="h-4 w-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })
            ) : (
              <tr>
                <td colSpan={9} className="py-10">
                  <EmptyState
                    title="Không có hợp đồng"
                    description="Dữ liệu hợp đồng chưa được backend trả về hoặc không khớp bộ lọc."
                  />
                </td>
              </tr>
            )}
          </tbody>
        </table>

        {/* Pagination Footer */}
        <div className="flex items-center justify-between border-t border-slate-100 bg-slate-50/30 px-6 py-4">
          <div className="flex items-center gap-4">
            <p className="text-xs text-slate-500">
              Hiển thị{" "}
              <span className="font-bold text-slate-800">1-{filteredContracts.length}</span> trong{" "}
              <span className="font-bold text-slate-800">{contracts.length}</span> hợp đồng
            </p>
            <span className="h-4 w-px bg-slate-200" />
            <div className="flex items-center gap-2">
              <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                Hàng loạt:
              </span>
              <button className="text-xs font-semibold text-indigo-600 hover:underline">
                Đánh dấu hết hạn
              </button>
              <button className="text-xs font-semibold text-indigo-600 hover:underline">
                Gia hạn hàng loạt
              </button>
            </div>
          </div>
          <div className="flex items-center gap-1">
            <button disabled className="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40">
              <ChevronLeft className="h-4 w-4" />
            </button>
            <button className="rounded-lg bg-indigo-700 px-3 py-1 text-xs font-bold text-white">1</button>
            <button disabled className="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40">
              <ChevronRight className="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>

      {/* Selected Detail Panel */}
      {selectedId && (
        <div className="rounded-2xl border border-white/70 bg-white/80 p-6 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur">
          <div className="mb-4 flex items-center justify-between">
            <h3 className="text-base font-semibold text-slate-900">Chi tiết hợp đồng</h3>
            <button
              onClick={() => setSelectedId("")}
              className="rounded-lg px-3 py-1 text-xs font-semibold text-slate-500 transition hover:bg-slate-100"
            >
              Đóng
            </button>
          </div>
          {selectedQuery.isLoading ? (
            <p className="text-sm text-slate-500">Đang tải chi tiết...</p>
          ) : selected ? (
            <div className="space-y-5">
              <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                  Số hợp đồng
                </p>
                <p className="mt-2 text-xl font-bold text-slate-950">
                  {textValue(selected, ["contract_no"], "N/A")}
                </p>
                <p className="mt-1 text-sm text-slate-500">
                  {textValue(selected, ["employee.full_name", "employee_name", "full_name"], "Nhân viên")}
                </p>
              </div>

              <div className="grid gap-3 sm:grid-cols-2">
                {(
                  [
                    ["Lương cơ bản", ["base_salary"]] as const,
                    ["Phụ cấp", ["total_allowance", "allowance_total"]] as const,
                    ["Ngày bắt đầu", ["start_date"]] as const,
                    ["Ngày kết thúc", ["end_date"]] as const,
                  ] as Array<[string, string[]]>
                ).map(([label, paths]) => (
                  <div key={label} className="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                      {label}
                    </p>
                    <p className="mt-1 text-lg font-bold text-slate-950">
                      {label === "Ngày bắt đầu" || label === "Ngày kết thúc"
                        ? formatDate(textValue(selected, paths, ""))
                        : formatCurrency(numberValue(selected, paths, 0))}
                    </p>
                  </div>
                ))}
              </div>

              <div className="rounded-2xl border border-slate-200 bg-white p-4">
                <p className="text-sm font-semibold text-slate-900">Phụ cấp</p>
                <div className="mt-3 space-y-2">
                  {toArray<Record<string, unknown>>(selected.allowances).length ? (
                    toArray<Record<string, unknown>>(selected.allowances).map((item, index) => (
                      <div
                        key={`${textValue(item, ["name"], String(index))}-${index}`}
                        className="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3"
                      >
                        <span className="font-medium text-slate-800">
                          {textValue(item, ["allowanceType.name", "name", "label"], "Phụ cấp")}
                        </span>
                        <span className="text-slate-600">
                          {formatCurrency(numberValue(item, ["amount", "value"], 0))}
                        </span>
                      </div>
                    ))
                  ) : (
                    <p className="text-sm text-slate-500">Không có allowance details.</p>
                  )}
                </div>
              </div>
            </div>
          ) : (
            <EmptyState title="Không có chi tiết" description="Backend chưa trả contract detail." />
          )}
        </div>
      )}

      {/* Summary Stats */}
      <div className="grid grid-cols-1 gap-6 md:grid-cols-4">
        <div className="rounded-2xl border border-white/70 bg-white/80 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur border-l-4 border-l-indigo-600">
          <p className="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">
            Đang hiệu lực
          </p>
          <div className="flex items-baseline gap-2">
            <h3 className="text-2xl font-extrabold text-slate-900">{activeCount}</h3>
            <Badge tone="success">Tháng này</Badge>
          </div>
        </div>

        <div className="rounded-2xl border border-white/70 bg-white/80 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur border-l-4 border-l-amber-500">
          <p className="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">
            Cần gia hạn
          </p>
          <div className="flex items-baseline gap-2">
            <h3 className="text-2xl font-extrabold text-slate-900">{expiringCount}</h3>
            <span className="text-[10px] font-bold text-rose-500">Cần xử lý</span>
          </div>
        </div>

        <div className="rounded-2xl border border-white/70 bg-white/80 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur border-l-4 border-l-emerald-500">
          <p className="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">
            Tổng base salary
          </p>
          <div className="flex items-baseline gap-2">
            <h3 className="text-xl font-extrabold text-slate-900">{formatCurrency(totalSalary)}</h3>
          </div>
        </div>

        <div className="rounded-2xl border border-white/70 bg-white/80 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur border-l-4 border-l-slate-400">
          <p className="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">
            Tổng hợp đồng
          </p>
          <div className="flex items-baseline gap-2">
            <h3 className="text-2xl font-extrabold text-slate-900">{contracts.length}</h3>
            <span className="text-[10px] font-bold text-slate-400">Trang hiện tại</span>
          </div>
        </div>
      </div>
    </div>
  );
}
