import { useEffect, useMemo, useState } from "react";
import { Outlet, useLocation } from "react-router-dom";
import Sidebar from "../components/Sidebar";
import Topbar from "../components/Topbar";
import { useAuth } from "../context/AuthContext";
import { EmptyState, PageHeader } from "../components/ui";
import { canAccessRoute, createPermissionSet, getRouteMeta } from "../lib/rbac";

export default function AppLayout() {
  const location = useLocation();
  const { user } = useAuth();
  const [mobileNavOpen, setMobileNavOpen] = useState(false);
  const [collapsed, setCollapsed] = useState(() => {
    try {
      return localStorage.getItem("sidebar-collapsed") === "true";
    } catch {
      return false;
    }
  });

  useEffect(() => {
    setMobileNavOpen(false);
  }, [location.pathname]);

  useEffect(() => {
    try {
      localStorage.setItem("sidebar-collapsed", String(collapsed));
    } catch {
      // noop
    }
  }, [collapsed]);

  const routeMeta = getRouteMeta(location.pathname);
  const permissionSet = useMemo(() => createPermissionSet(user?.permissions), [user?.permissions]);
  const permissionsLoading = user !== null && user.permissions === undefined;
  const hasAccess = permissionsLoading ? true : canAccessRoute(location.pathname, permissionSet);

  const title = routeMeta?.title ?? "Payroll ERP";
  const subtitle = routeMeta?.subtitle ?? "Payroll ERP";

  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(56,189,248,0.14),_transparent_30%),radial-gradient(circle_at_top_right,_rgba(99,102,241,0.12),_transparent_24%),linear-gradient(180deg,_#f8fbff_0%,_#eef4ff_100%)] text-slate-900">
      <Sidebar
        open={mobileNavOpen}
        onClose={() => setMobileNavOpen(false)}
        collapsed={collapsed}
        onToggleCollapse={() => setCollapsed((current) => !current)}
      />

      <div className={`transition-[padding-left] duration-300 ${collapsed ? "lg:pl-16" : "lg:pl-72"}`}>
        <Topbar
          title={title}
          subtitle={subtitle}
          onMenuClick={() => setMobileNavOpen(true)}
        />

        <main className="px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
          {permissionsLoading ? (
            <div className="space-y-6">
              <PageHeader
                eyebrow={subtitle}
                title={title}
                description="Đang đồng bộ ma trận quyền từ phiên đăng nhập hiện tại."
              />
              <EmptyState
                title="Đang tải phân quyền"
                description="Hệ thống đang nạp quyền thao tác để hiển thị đúng menu và đúng phạm vi truy cập."
              />
            </div>
          ) : hasAccess ? (
            <Outlet />
          ) : (
            <div className="space-y-6">
              <PageHeader
                eyebrow="403"
                title="Không có quyền truy cập"
                description="Role hiện tại không được phép mở màn hình này theo ma trận phân quyền."
              />
              <EmptyState
                title="Truy cập bị từ chối"
                description="Hãy dùng đúng tài khoản nghiệp vụ hoặc gán thêm permission tương ứng trong quản trị vai trò."
              />
            </div>
          )}
        </main>
      </div>
    </div>
  );
}
