import { useState } from "react";
import type { FormEvent } from "react";
import { Navigate, useNavigate } from "react-router-dom";
import { useMutation } from "@tanstack/react-query";
import { ArrowRight, CircleAlert, Eye, EyeOff, LockKeyhole, Mail, ShieldCheck } from "lucide-react";
import { useAuth, readAuthError } from "../context/AuthContext";

export default function LoginPage() {
  const { login, isAuthenticated } = useAuth();
  const navigate = useNavigate();
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [rememberMe, setRememberMe] = useState(true);
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const loginMutation = useMutation({
    mutationFn: async () => login(username.trim(), password, { remember: rememberMe }),
    onSuccess: () => navigate("/", { replace: true }),
    onError: (err) => setError(readAuthError(err)),
  });

  if (isAuthenticated) {
    return <Navigate to="/" replace />;
  }

  const handleSubmit = (event: FormEvent) => {
    event.preventDefault();
    setError(null);

    if (!username.trim() || !password) {
      setError("Vui lòng nhập username và mật khẩu.");
      return;
    }

    loginMutation.mutate();
  };

  return (
    <div
      className="flex min-h-screen items-center justify-center p-4"
      style={{
        backgroundColor: "#0f172a",
        backgroundImage:
          "radial-gradient(circle at 2px 2px, rgba(255,255,255,0.05) 1px, transparent 0)",
        backgroundSize: "24px 24px",
      }}
    >
      {/* Decorative blobs */}
      <div className="pointer-events-none fixed inset-0">
        <div className="absolute -top-[10%] -right-[10%] h-96 w-96 rounded-full bg-indigo-900 opacity-40 blur-[120px]" />
        <div className="absolute -bottom-[5%] left-[10%] h-64 w-64 rounded-full bg-emerald-600 opacity-20 blur-[100px]" />
      </div>

      {/* Login Card */}
      <div className="relative z-10 w-full max-w-md">
        {/* Logo */}
        <div className="mb-8 flex flex-col items-center gap-3">
          <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/10">
            <ShieldCheck className="h-7 w-7 text-sky-300" />
          </div>
          <h1 className="text-2xl font-black uppercase tracking-tight text-white">
            Payroll ERP
          </h1>
        </div>

        {/* Card */}
        <div className="rounded-2xl bg-white p-8 shadow-2xl shadow-black/20 md:p-10">
          {/* Form header */}
          <header className="mb-8 text-center">
            <h3 className="font-[family-name:var(--font-display)] mb-2 text-2xl font-bold tracking-tight text-slate-900">
              Chào mừng trở lại
            </h3>
            <p className="text-sm font-medium text-slate-500">
              Nhập thông tin đăng nhập để vào hệ thống.
            </p>
          </header>

          {/* Error alert */}
          {error && (
            <div className="mb-6 flex items-center gap-3 rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-800">
              <CircleAlert className="h-5 w-5 shrink-0" />
              <p className="text-sm font-medium">{error}</p>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-5">
            {/* Username field */}
            <div className="space-y-1.5">
              <label
                htmlFor="identity"
                className="ml-1 block text-[0.6875rem] font-bold uppercase tracking-widest text-slate-500"
              >
                Email hoặc Username
              </label>
              <div className="group relative">
                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <Mail className="h-5 w-5 text-slate-400 transition-colors group-focus-within:text-slate-950" />
                </div>
                <input
                  id="identity"
                  type="text"
                  name="identity"
                  required
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  placeholder="admin01 / hr01 / payroll01"
                  className="block w-full rounded-t-lg rounded-b-none border-b-2 border-transparent bg-slate-100 py-3.5 pl-11 pr-4 font-medium text-slate-900 placeholder:text-slate-400/60 transition-all focus:border-slate-950 focus:outline-none focus:ring-0"
                />
              </div>
            </div>

            {/* Password field */}
            <div className="space-y-1.5">
              <div className="ml-1 flex items-end justify-between">
                <label
                  htmlFor="password"
                  className="block text-[0.6875rem] font-bold uppercase tracking-widest text-slate-500"
                >
                  Mật khẩu
                </label>
                <a
                  href="#"
                  className="text-[0.6875rem] font-bold uppercase tracking-widest text-slate-950 transition-colors hover:text-indigo-700"
                >
                  Quên mật khẩu?
                </a>
              </div>
              <div className="group relative">
                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <LockKeyhole className="h-5 w-5 text-slate-400 transition-colors group-focus-within:text-slate-950" />
                </div>
                <input
                  id="password"
                  type={showPassword ? "text" : "password"}
                  name="password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••••••"
                  className="block w-full rounded-t-lg rounded-b-none border-b-2 border-transparent bg-slate-100 py-3.5 pl-11 pr-12 font-medium text-slate-900 transition-all focus:border-slate-950 focus:outline-none focus:ring-0"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((prev) => !prev)}
                  className="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-900"
                >
                  {showPassword ? (
                    <EyeOff className="h-5 w-5" />
                  ) : (
                    <Eye className="h-5 w-5" />
                  )}
                </button>
              </div>
            </div>

            {/* Remember me */}
            <div className="flex items-center">
              <input
                id="remember-me"
                type="checkbox"
                name="remember-me"
                checked={rememberMe}
                onChange={(e) => setRememberMe(e.target.checked)}
                className="h-4 w-4 rounded border-slate-300 text-slate-950 focus:ring-slate-900"
              />
              <label
                htmlFor="remember-me"
                className="ml-3 block text-sm font-medium text-slate-500"
              >
                Ghi nhớ phiên trong 30 ngày
              </label>
            </div>

            {/* Submit button */}
            <button
              type="submit"
              disabled={loginMutation.isPending}
              className="flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-br from-slate-950 to-indigo-900 px-6 py-4 text-sm font-bold uppercase tracking-widest text-white shadow-lg shadow-slate-950/20 transition-all hover:shadow-slate-950/40 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-70"
            >
              <span>
                {loginMutation.isPending ? "Đang xác thực..." : "Đăng nhập hệ thống"}
              </span>
              <ArrowRight className="h-5 w-5" />
            </button>
          </form>

          {/* Footer */}
          <footer className="mt-8 border-t border-slate-200 pt-6">
            <div className="flex flex-col gap-3 text-center">
              <p className="text-sm text-slate-500">
                Chưa có tài khoản?{" "}
                <a href="#" className="font-bold text-slate-950 hover:underline">
                  Yêu cầu cấp quyền truy cập
                </a>
              </p>
              <div className="flex justify-center gap-6 text-[0.625rem] font-bold uppercase tracking-tighter text-slate-400">
                <a href="#" className="transition-colors hover:text-slate-900">
                  Bảo mật
                </a>
                <a href="#" className="transition-colors hover:text-slate-900">
                  Chính sách
                </a>
                <a href="#" className="transition-colors hover:text-slate-900">
                  Hỗ trợ
                </a>
              </div>
            </div>
          </footer>
        </div>
      </div>
    </div>
  );
}
