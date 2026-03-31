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

// New real pages
import EmployeesPage from "./pages/EmployeesPage";
import ContractTypesPage from "./pages/ContractTypesPage";
import SalaryLevelsPage from "./pages/SalaryLevelsPage";
import AllowancesPage from "./pages/AllowancesPage";
import LateEarlyRulesPage from "./pages/LateEarlyRulesPage";
import HolidaysPage from "./pages/HolidaysPage";
import ShiftsPage from "./pages/ShiftsPage";
import PayrollParametersPage from "./pages/PayrollParametersPage";
import ShiftAssignmentsPage from "./pages/ShiftAssignmentsPage";
import LeaveRequestsPage from "./pages/LeaveRequestsPage";
import ManualAttendancePage from "./pages/ManualAttendancePage";
import BonusDeductionsPage from "./pages/BonusDeductionsPage";
import PayrollPeriodsPage from "./pages/PayrollPeriodsPage";

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

                {/* Nhân sự & HĐLĐ */}
                <Route path="employees" element={<EmployeesPage />} />
                <Route path="reference/contract-types" element={<ContractTypesPage />} />
                <Route path="reference/salary-levels" element={<SalaryLevelsPage />} />
                <Route path="reference/allowances" element={<AllowancesPage />} />
                <Route path="contracts" element={<ContractsPage />} />
                <Route path="contracts/:id" element={<ContractDetailPage />} />

                {/* Chấm công */}
                <Route path="reference/late-early-rules" element={<LateEarlyRulesPage />} />
                <Route path="reference/holidays" element={<HolidaysPage />} />
                <Route path="reference/shifts" element={<ShiftsPage />} />
                <Route path="attendance" element={<AttendancePage />} />
                <Route path="attendance/shift-assignments" element={<ShiftAssignmentsPage />} />
                <Route path="attendance/logs" element={<AttendanceLogsPage />} />
                <Route path="attendance/leave-requests" element={<LeaveRequestsPage />} />
                <Route path="attendance/manual" element={<ManualAttendancePage />} />
                <Route path="attendance/summary" element={<AttendanceSummaryPage />} />

                {/* Tính lương */}
                <Route path="payroll" element={<PayrollPage />} />
                <Route path="payroll/parameters" element={<PayrollParametersPage />} />
                <Route path="payroll/bonus-deductions" element={<BonusDeductionsPage />} />
                <Route path="payroll/run" element={<PayrollRunPage />} />
                <Route path="payroll/periods" element={<PayrollPeriodsPage />} />
                <Route path="payroll/payslips" element={<PayslipsPage />} />
                <Route path="payroll/payslips/:id" element={<PayslipDetailPage />} />

                {/* Báo cáo */}
                <Route path="reports" element={<ReportsPage />} />

                {/* Quản trị */}
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
