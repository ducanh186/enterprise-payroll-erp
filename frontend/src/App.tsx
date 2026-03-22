import { BrowserRouter, Routes, Route } from "react-router-dom";
import { QueryClientProvider } from "@tanstack/react-query";
import { queryClient } from "./lib/query";
import { AuthProvider } from "./context/AuthContext";

import ProtectedRoute from "./components/ProtectedRoute";
import AppLayout from "./layouts/AppLayout";

import LoginPage from "./pages/LoginPage";
import DashboardPage from "./pages/DashboardPage";
import AttendancePage from "./pages/AttendancePage";
import AttendanceLogsPage from "./pages/AttendanceLogsPage";
import AttendanceSummaryPage from "./pages/AttendanceSummaryPage";
import PayrollPage from "./pages/PayrollPage";
import PayrollRunPage from "./pages/PayrollRunPage";
import PayslipsPage from "./pages/PayslipsPage";
import ContractsPage from "./pages/ContractsPage";
import ReportsPage from "./pages/ReportsPage";
import AdminUsersPage from "./pages/AdminUsersPage";
import ContractDetailPage from "./pages/ContractDetailPage";
import PayslipDetailPage from "./pages/PayslipDetailPage";
import RolePermissionsPage from "./pages/RolePermissionsPage";

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <BrowserRouter>
          <Routes>
            <Route path="/login" element={<LoginPage />} />

            {/* All app routes are protected */}
            <Route element={<ProtectedRoute />}>
              <Route element={<AppLayout />}>
                <Route index element={<DashboardPage />} />

                <Route path="attendance" element={<AttendancePage />} />
                <Route path="attendance/logs" element={<AttendanceLogsPage />} />
                <Route path="attendance/summary" element={<AttendanceSummaryPage />} />

                <Route path="payroll" element={<PayrollPage />} />
                <Route path="payroll/run" element={<PayrollRunPage />} />
                <Route path="payroll/payslips" element={<PayslipsPage />} />
                <Route path="payroll/payslips/:id" element={<PayslipDetailPage />} />

                <Route path="contracts" element={<ContractsPage />} />
                <Route path="contracts/:id" element={<ContractDetailPage />} />

                <Route path="reports" element={<ReportsPage />} />

                <Route path="admin" element={<AdminUsersPage />} />
                <Route path="admin/users" element={<AdminUsersPage />} />
                <Route path="admin/roles" element={<RolePermissionsPage />} />
              </Route>
            </Route>
          </Routes>
        </BrowserRouter>
      </AuthProvider>
    </QueryClientProvider>
  );
}
