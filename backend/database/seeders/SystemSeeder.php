<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Database\Seeders\Concerns\IdentityInsert;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSeeder extends Seeder
{
    use IdentityInsert;
    public function run(): void
    {
        DB::table('audit_logs')->delete();
        DB::table('system_configs')->delete();
        DB::table('report_templates')->delete();

        $now = now();

        // ---------------------------------------------------------------
        // Report Templates
        // ---------------------------------------------------------------
        $this->insertWithIdentity('report_templates', [
            ['id' => 1, 'code' => 'RPT_ATTENDANCE_DAILY',   'name' => 'Báo cáo chấm công hàng ngày',             'module' => 'attendance', 'sp_name' => 'sp_Report_AttendanceDaily',    'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'code' => 'RPT_ATTENDANCE_MONTHLY',  'name' => 'Báo cáo chấm công hàng tháng',            'module' => 'attendance', 'sp_name' => 'sp_Report_AttendanceMonthly',  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'code' => 'RPT_PAYROLL_SUMMARY',     'name' => 'Báo cáo tổng hợp lương',                  'module' => 'payroll',    'sp_name' => 'sp_Report_PayrollSummary',     'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'code' => 'RPT_PAYSLIP',             'name' => 'Phiếu lương cá nhân',                     'module' => 'payroll',    'sp_name' => 'sp_Report_Payslip',            'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'code' => 'RPT_INSURANCE',           'name' => 'Báo cáo bảo hiểm xã hội',                 'module' => 'payroll',    'sp_name' => 'sp_Report_Insurance',          'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'code' => 'RPT_PIT',                 'name' => 'Báo cáo thuế thu nhập cá nhân',           'module' => 'payroll',    'sp_name' => 'sp_Report_PIT',                'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ---------------------------------------------------------------
        // System Configs
        // ---------------------------------------------------------------
        $this->insertWithIdentity('system_configs', [
            ['id' => 1,  'config_key' => 'company_name',              'config_value' => 'Cong Ty TNHH ABC',                    'description' => 'Ten cong ty',                              'created_at' => $now, 'updated_at' => $now],
            ['id' => 2,  'config_key' => 'company_tax_code',          'config_value' => '0123456789',                           'description' => 'Ma so thue cong ty',                       'created_at' => $now, 'updated_at' => $now],
            ['id' => 3,  'config_key' => 'company_address',           'config_value' => '123 Nguyen Hue, Quan 1, TP.HCM',      'description' => 'Dia chi cong ty',                          'created_at' => $now, 'updated_at' => $now],
            ['id' => 4,  'config_key' => 'payroll_cutoff_day',        'config_value' => '25',                                   'description' => 'Ngay chot cong hang thang',                'created_at' => $now, 'updated_at' => $now],
            ['id' => 5,  'config_key' => 'payroll_payment_day',       'config_value' => '5',                                    'description' => 'Ngay tra luong hang thang',                'created_at' => $now, 'updated_at' => $now],
            ['id' => 6,  'config_key' => 'default_shift',             'config_value' => 'HC08',                                 'description' => 'Ca lam viec mac dinh',                     'created_at' => $now, 'updated_at' => $now],
            ['id' => 7,  'config_key' => 'overtime_rate_weekday',     'config_value' => '1.5',                                  'description' => 'He so tang ca ngay thuong',                'created_at' => $now, 'updated_at' => $now],
            ['id' => 8,  'config_key' => 'overtime_rate_weekend',     'config_value' => '2.0',                                  'description' => 'He so tang ca cuoi tuan',                  'created_at' => $now, 'updated_at' => $now],
            ['id' => 9,  'config_key' => 'overtime_rate_holiday',     'config_value' => '3.0',                                  'description' => 'He so tang ca ngay le',                    'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'config_key' => 'insurance_region',          'config_value' => '1',                                    'description' => 'Vung luong toi thieu ap dung',             'created_at' => $now, 'updated_at' => $now],
            ['id' => 11, 'config_key' => 'max_annual_leave_days',     'config_value' => '12',                                   'description' => 'So ngay phep nam toi da',                  'created_at' => $now, 'updated_at' => $now],
            ['id' => 12, 'config_key' => 'currency',                  'config_value' => 'VND',                                  'description' => 'Don vi tien te',                           'created_at' => $now, 'updated_at' => $now],
            ['id' => 13, 'config_key' => 'standard_work_hours_day',   'config_value' => '8',                                    'description' => 'So gio lam viec tieu chuan moi ngay',      'created_at' => $now, 'updated_at' => $now],
            ['id' => 14, 'config_key' => 'standard_work_days_month',  'config_value' => '22',                                   'description' => 'So ngay lam viec tieu chuan moi thang',    'created_at' => $now, 'updated_at' => $now],
        ]);

        // ---------------------------------------------------------------
        // Audit Logs (sample entries)
        // ---------------------------------------------------------------
        $auditLogs = [
            [
                'id' => 1,
                'actor_user_id' => 1,
                'module' => 'auth',
                'action' => 'login',
                'ref_table' => 'users',
                'ref_id' => 1,
                'before_json' => null,
                'after_json' => json_encode(['last_login_at' => $now->toISOString()]),
                'ip_address' => '192.168.1.100',
                'created_at' => Carbon::parse('2026-03-01 08:00:00'),
            ],
            [
                'id' => 2,
                'actor_user_id' => 2,
                'module' => 'auth',
                'action' => 'login',
                'ref_table' => 'users',
                'ref_id' => 2,
                'before_json' => null,
                'after_json' => json_encode(['last_login_at' => $now->toISOString()]),
                'ip_address' => '192.168.1.101',
                'created_at' => Carbon::parse('2026-03-01 08:05:00'),
            ],
            [
                'id' => 3,
                'actor_user_id' => 4,
                'module' => 'payroll',
                'action' => 'create_run',
                'ref_table' => 'payroll_runs',
                'ref_id' => 2,
                'before_json' => null,
                'after_json' => json_encode(['status' => 'draft', 'attendance_period_id' => 2]),
                'ip_address' => '192.168.1.102',
                'created_at' => Carbon::parse('2026-03-02 08:30:00'),
            ],
            [
                'id' => 4,
                'actor_user_id' => 4,
                'module' => 'payroll',
                'action' => 'preview_run',
                'ref_table' => 'payroll_runs',
                'ref_id' => 2,
                'before_json' => json_encode(['status' => 'draft']),
                'after_json' => json_encode(['status' => 'previewed']),
                'ip_address' => '192.168.1.102',
                'created_at' => Carbon::parse('2026-03-02 09:00:00'),
            ],
            [
                'id' => 5,
                'actor_user_id' => 1,
                'module' => 'payroll',
                'action' => 'lock_run',
                'ref_table' => 'payroll_runs',
                'ref_id' => 1,
                'before_json' => json_encode(['status' => 'finalized']),
                'after_json' => json_encode(['status' => 'locked']),
                'ip_address' => '192.168.1.100',
                'created_at' => Carbon::parse('2026-02-05 14:00:00'),
            ],
            [
                'id' => 6,
                'actor_user_id' => 2,
                'module' => 'attendance',
                'action' => 'approve_request',
                'ref_table' => 'attendance_requests',
                'ref_id' => 1,
                'before_json' => json_encode(['status' => 'pending']),
                'after_json' => json_encode(['status' => 'approved']),
                'ip_address' => '192.168.1.101',
                'created_at' => Carbon::parse('2026-02-10 10:30:00'),
            ],
            [
                'id' => 7,
                'actor_user_id' => 2,
                'module' => 'attendance',
                'action' => 'reject_request',
                'ref_table' => 'attendance_requests',
                'ref_id' => 7,
                'before_json' => json_encode(['status' => 'pending']),
                'after_json' => json_encode(['status' => 'rejected']),
                'ip_address' => '192.168.1.101',
                'created_at' => Carbon::parse('2026-02-12 09:15:00'),
            ],
            [
                'id' => 8,
                'actor_user_id' => 2,
                'module' => 'employee',
                'action' => 'update',
                'ref_table' => 'employees',
                'ref_id' => 15,
                'before_json' => json_encode(['phone' => '0901000015']),
                'after_json' => json_encode(['phone' => '0901000015', 'bank_name' => 'Techcombank']),
                'ip_address' => '192.168.1.101',
                'created_at' => Carbon::parse('2026-02-15 14:20:00'),
            ],
            [
                'id' => 9,
                'actor_user_id' => 1,
                'module' => 'admin',
                'action' => 'update_config',
                'ref_table' => 'system_configs',
                'ref_id' => 4,
                'before_json' => json_encode(['config_value' => '26']),
                'after_json' => json_encode(['config_value' => '25']),
                'ip_address' => '192.168.1.100',
                'created_at' => Carbon::parse('2026-01-15 11:00:00'),
            ],
            [
                'id' => 10,
                'actor_user_id' => 2,
                'module' => 'attendance',
                'action' => 'import_time_logs',
                'ref_table' => 'time_logs',
                'ref_id' => null,
                'before_json' => null,
                'after_json' => json_encode(['imported_count' => 450, 'period' => '2026-02']),
                'ip_address' => '192.168.1.101',
                'created_at' => Carbon::parse('2026-03-01 07:00:00'),
            ],
        ];

        $this->insertWithIdentity('audit_logs', $auditLogs);
    }
}

