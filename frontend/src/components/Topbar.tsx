import { Menu, LogOut, Sparkles } from "lucide-react";
import { useAuth } from "../context/AuthContext";
import { formatDateTime } from "../lib/format";

interface TopbarProps {
  title: string;
  subtitle?: string;
  onMenuClick: () => void;
}

export default function Topbar({ title, subtitle, onMenuClick }: TopbarProps) {
  const { user, logout } = useAuth();

  return (
    <header className="sticky top-0 z-20 border-b border-white/60 bg-white/85 backdrop-blur">
      <div className="flex items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
        <div className="flex min-w-0 items-center gap-3">
          <button
            type="button"
            onClick={onMenuClick}
            className="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 lg:hidden"
            aria-label="Mở menu"
          >
            <Menu className="h-5 w-5" />
          </button>

          <div className="min-w-0">
            <div className="flex items-center gap-2">
              <Sparkles className="h-4 w-4 text-sky-500" />
              <p className="truncate text-xs font-bold uppercase tracking-[0.24em] text-slate-500">
                {subtitle ?? "Enterprise payroll workspace"}
              </p>
            </div>
            <h1 className="truncate font-[family-name:var(--font-display)] text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">
              {title}
            </h1>
          </div>
        </div>

        <div className="flex items-center gap-3">
          <div className="hidden text-right sm:block">
            <p className="text-sm font-semibold text-slate-900">{user?.name}</p>
            <p className="text-xs text-slate-500">
              {user?.role?.replaceAll("_", " ")}
              {user?.department_name ? ` • ${user.department_name}` : ""}
            </p>
          </div>

          <div className="flex h-11 min-w-11 items-center justify-center rounded-2xl bg-slate-950 px-3 text-sm font-bold text-white shadow-[0_12px_24px_rgba(15,23,42,0.18)]">
            {user?.name?.slice(0, 1)?.toUpperCase() ?? "?"}
          </div>

          <button
            type="button"
            onClick={logout}
            className="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
          >
            <LogOut className="h-4 w-4" />
            <span className="hidden sm:inline">Đăng xuất</span>
          </button>
        </div>
      </div>

      <div className="border-t border-slate-100 px-4 py-2 text-xs text-slate-500 sm:px-6 lg:px-8">
        Cập nhật gần nhất: {formatDateTime(new Date())}
      </div>
    </header>
  );
}

