<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\IdentityInsert;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcedureCatalogSeeder extends Seeder
{
    use IdentityInsert;

    public function run(): void
    {
        DB::table('procedure_execution_logs')->delete();
        DB::table('procedure_columns')->delete();
        DB::table('procedure_parameters')->delete();
        DB::table('procedure_catalog')->delete();

        $now = now();

        // ---------------------------------------------------------------
        // Procedure Catalog
        // ---------------------------------------------------------------
        $this->insertWithIdentity('procedure_catalog', [
            [
                'id' => 1,
                'code' => 'attendance-collection',
                'label' => 'Tổng hợp công',
                'procedure_name' => 'dbo.usp_Hrm_AttendanceCollection',
                'module' => 'attendance',
                'description' => 'Tổng hợp ngày công của nhân viên theo khoảng thời gian.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'code' => 'attendance-report',
                'label' => 'Bảng chấm công',
                'procedure_name' => 'dbo.usp_Hrm_AttendanceReport',
                'module' => 'attendance',
                'description' => 'Bảng chấm công chi tiết với ký hiệu chấm công hàng ngày.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'code' => 'assign-shift',
                'label' => 'Bảng phân ca hàng ngày',
                'procedure_name' => 'dbo.usp_Hrm_B30HrmAssignShift',
                'module' => 'attendance',
                'description' => 'Bảng phân ca làm việc hàng ngày cho nhân viên.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'code' => 'late-early',
                'label' => 'Tổng hợp đi trễ về sớm',
                'procedure_name' => 'dbo.usp_Hrm_InOut_LaterEarly',
                'module' => 'attendance',
                'description' => 'Bảng tổng hợp số lần và số phút đi trễ, về sớm.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ---------------------------------------------------------------
        // Procedure Parameters
        // ---------------------------------------------------------------
        $paramId = 0;
        $params = [];

        // -- attendance-collection (proc 1)
        foreach ([
            ['date_from',       '@_DocDate1',               'date',    'Từ ngày',              true,  null, 1],
            ['date_to',         '@_DocDate2',               'date',    'Đến ngày',             true,  null, 2],
            ['employee_id',     '@_EmployeeId',             'string',  'Mã nhân viên',         false, '',   3],
            ['department_id',   '@_DeptId',                 'string',  'Mã phòng ban',         false, '',   4],
            ['branch_code',     '@_BranchCode',             'string',  'Mã chi nhánh',         false, '',   5],
            ['user_id',         '@_nUserId',                'integer', 'User ID',              false, '0',  6],
        ] as $p) {
            $paramId++;
            $params[] = ['id' => $paramId, 'procedure_id' => 1, 'name' => $p[0], 'sp_param_name' => $p[1], 'type' => $p[2], 'label' => $p[3], 'required' => $p[4], 'default_value' => $p[5], 'sort_order' => $p[6], 'created_at' => $now, 'updated_at' => $now];
        }

        // -- attendance-report (proc 2)
        foreach ([
            ['date_from',              '@_DocDate1',                    'date',    'Từ ngày',                      true,  null,  1],
            ['date_to',                '@_DocDate2',                    'date',    'Đến ngày',                     true,  null,  2],
            ['group_dept_level',       '@_GroupDeptLevel',              'tinyint', 'Cấp phòng ban gộp',            false, '3',   3],
            ['department_id',          '@_DeptId',                      'string',  'Mã phòng ban',                 false, '',    4],
            ['employee_id',            '@_EmployeeId',                  'string',  'Mã nhân viên',                 false, '',    5],
            ['show_data_type',         '@_ShowDataType',                'tinyint', 'Kiểu hiển thị dữ liệu',       false, '0',   6],
            ['symbol_workday',         '@_SymbolWorkday',               'tinyint', 'Ký hiệu ngày công',           false, '1',   7],
            ['not_in_out_symbol',      '@_NotInOutSymbol_RowId',        'integer', 'Ký hiệu không vào ra',        false, '0',   8],
            ['holiday1_symbol',        '@_Holiday1Symbol',              'string',  'Ký hiệu nghỉ lễ (CN)',        false, '',    9],
            ['holiday2_symbol',        '@_Holiday2Symbol',              'string',  'Ký hiệu nghỉ lễ (T7)',        false, '',    10],
            ['holiday3_symbol',        '@_Holiday3Symbol',              'string',  'Ký hiệu nghỉ lễ (khác)',      false, '',    11],
            ['dayoff_by_shift_symbol', '@_DayOffByShiftSymbol',         'string',  'Ký hiệu nghỉ theo ca',        false, '',    12],
            ['branch_code',            '@_BranchCode',                  'string',  'Mã chi nhánh',                false, '',    13],
            ['user_id',                '@_nUserId',                     'integer', 'User ID',                      false, '0',   14],
        ] as $p) {
            $paramId++;
            $params[] = ['id' => $paramId, 'procedure_id' => 2, 'name' => $p[0], 'sp_param_name' => $p[1], 'type' => $p[2], 'label' => $p[3], 'required' => $p[4], 'default_value' => $p[5], 'sort_order' => $p[6], 'created_at' => $now, 'updated_at' => $now];
        }

        // -- assign-shift (proc 3)
        foreach ([
            ['date_from',       '@_DocDate1',    'date',    'Từ ngày',              true,  null, 1],
            ['date_to',         '@_DocDate2',    'date',    'Đến ngày',             true,  null, 2],
            ['employee_id',     '@_EmployeeId',  'string',  'Mã nhân viên',         false, '',   3],
            ['department_id',   '@_DeptId',      'string',  'Mã phòng ban',         false, '',   4],
            ['user_id',         '@_nUserId',     'integer', 'User ID',              false, '0',  5],
            ['branch_code',     '@_BranchCode',  'string',  'Mã chi nhánh',         false, '',   6],
        ] as $p) {
            $paramId++;
            $params[] = ['id' => $paramId, 'procedure_id' => 3, 'name' => $p[0], 'sp_param_name' => $p[1], 'type' => $p[2], 'label' => $p[3], 'required' => $p[4], 'default_value' => $p[5], 'sort_order' => $p[6], 'created_at' => $now, 'updated_at' => $now];
        }

        // -- late-early (proc 4)
        foreach ([
            ['date_from',       '@_DocDate1',    'date',    'Từ ngày',              true,  null, 1],
            ['date_to',         '@_DocDate2',    'date',    'Đến ngày',             true,  null, 2],
            ['employee_id',     '@_EmployeeId',  'string',  'Mã nhân viên',         false, '',   3],
            ['department_id',   '@_DeptId',      'string',  'Mã phòng ban',         false, '',   4],
            ['user_id',         '@_nUserId',     'integer', 'User ID',              false, '0',  5],
            ['branch_code',     '@_BranchCode',  'string',  'Mã chi nhánh',         false, '',   6],
        ] as $p) {
            $paramId++;
            $params[] = ['id' => $paramId, 'procedure_id' => 4, 'name' => $p[0], 'sp_param_name' => $p[1], 'type' => $p[2], 'label' => $p[3], 'required' => $p[4], 'default_value' => $p[5], 'sort_order' => $p[6], 'created_at' => $now, 'updated_at' => $now];
        }

        $this->insertWithIdentity('procedure_parameters', $params);

        // ---------------------------------------------------------------
        // Procedure Columns
        // ---------------------------------------------------------------
        $colId = 0;
        $cols = [];

        // -- attendance-collection columns (proc 1)
        foreach ([
            ['employee_code',   'Mã NV',            'string',  true,  true,  1],
            ['employee_name',   'Họ và tên',         'string',  true,  true,  2],
            ['department_name', 'Phòng ban',         'string',  true,  true,  3],
            ['total_workdays',  'Tổng ngày công',    'number',  true,  true,  4],
            ['total_ot_hours',  'Giờ tăng ca',       'number',  true,  true,  5],
            ['total_leave',     'Ngày nghỉ phép',    'number',  true,  true,  6],
            ['total_absent',    'Ngày vắng mặt',     'number',  true,  true,  7],
        ] as $c) {
            $colId++;
            $cols[] = ['id' => $colId, 'procedure_id' => 1, 'key' => $c[0], 'label' => $c[1], 'type' => $c[2], 'visible' => $c[3], 'exportable' => $c[4], 'sort_order' => $c[5], 'created_at' => $now, 'updated_at' => $now];
        }

        // -- attendance-report columns (proc 2)
        foreach ([
            ['employee_code',   'Mã NV',            'string',  true,  true,  1],
            ['employee_name',   'Họ và tên',         'string',  true,  true,  2],
            ['department_name', 'Phòng ban',         'string',  true,  true,  3],
            ['work_date',       'Ngày',              'date',    true,  true,  4],
            ['workday_symbol',  'Ký hiệu',           'string',  true,  true,  5],
            ['check_in',        'Giờ vào',           'string',  true,  true,  6],
            ['check_out',       'Giờ ra',            'string',  true,  true,  7],
            ['work_hours',      'Giờ làm',           'number',  true,  true,  8],
            ['ot_hours',        'Giờ tăng ca',       'number',  true,  true,  9],
        ] as $c) {
            $colId++;
            $cols[] = ['id' => $colId, 'procedure_id' => 2, 'key' => $c[0], 'label' => $c[1], 'type' => $c[2], 'visible' => $c[3], 'exportable' => $c[4], 'sort_order' => $c[5], 'created_at' => $now, 'updated_at' => $now];
        }

        // -- assign-shift columns (proc 3)
        foreach ([
            ['employee_code',   'Mã NV',            'string',  true,  true,  1],
            ['employee_name',   'Họ và tên',         'string',  true,  true,  2],
            ['department_name', 'Phòng ban',         'string',  true,  true,  3],
            ['work_date',       'Ngày',              'date',    true,  true,  4],
            ['shift_code',      'Mã ca',             'string',  true,  true,  5],
            ['shift_name',      'Tên ca',            'string',  true,  true,  6],
            ['start_time',      'Giờ bắt đầu',      'string',  true,  true,  7],
            ['end_time',        'Giờ kết thúc',      'string',  true,  true,  8],
        ] as $c) {
            $colId++;
            $cols[] = ['id' => $colId, 'procedure_id' => 3, 'key' => $c[0], 'label' => $c[1], 'type' => $c[2], 'visible' => $c[3], 'exportable' => $c[4], 'sort_order' => $c[5], 'created_at' => $now, 'updated_at' => $now];
        }

        // -- late-early columns (proc 4)
        foreach ([
            ['employee_code',    'Mã NV',                'string',  true,  true,  1],
            ['employee_name',    'Họ và tên',             'string',  true,  true,  2],
            ['department_name',  'Phòng ban',             'string',  true,  true,  3],
            ['late_count',       'Số lần trễ',            'number',  true,  true,  4],
            ['late_minutes',     'Số phút trễ',           'number',  true,  true,  5],
            ['early_count',      'Số lần sớm',            'number',  true,  true,  6],
            ['early_minutes',    'Số phút sớm',           'number',  true,  true,  7],
            ['total_count',      'Tổng số lần',           'number',  true,  true,  8],
            ['total_minutes',    'Tổng số phút',          'number',  true,  true,  9],
        ] as $c) {
            $colId++;
            $cols[] = ['id' => $colId, 'procedure_id' => 4, 'key' => $c[0], 'label' => $c[1], 'type' => $c[2], 'visible' => $c[3], 'exportable' => $c[4], 'sort_order' => $c[5], 'created_at' => $now, 'updated_at' => $now];
        }

        $this->insertWithIdentity('procedure_columns', $cols);
    }
}
