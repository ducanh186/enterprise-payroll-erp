<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\IdentityInsert;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftHolidaySeeder extends Seeder
{
    use IdentityInsert;
    public function run(): void
    {
        DB::table('late_early_rules')->delete();
        DB::table('holidays')->delete();
        DB::table('shifts')->delete();

        $now = now();

        // ---------------------------------------------------------------
        // Shifts
        // ---------------------------------------------------------------
        $this->insertWithIdentity('shifts', [
            [
                'id' => 1,
                'code' => 'HC08',
                'name' => 'Hanh Chinh Sang',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_start_time' => '12:00:00',
                'break_end_time' => '13:00:00',
                'workday_value' => 1.0,
                'timesheet_type' => 'standard',
                'is_overnight' => false,
                'min_meal_hours' => 4.0,
                'grace_late_minutes' => 5,
                'grace_early_minutes' => 5,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'code' => 'HC13',
                'name' => 'Hanh Chinh Chieu',
                'start_time' => '13:00:00',
                'end_time' => '22:00:00',
                'break_start_time' => '17:00:00',
                'break_end_time' => '17:30:00',
                'workday_value' => 1.0,
                'timesheet_type' => 'standard',
                'is_overnight' => false,
                'min_meal_hours' => 4.0,
                'grace_late_minutes' => 5,
                'grace_early_minutes' => 5,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'code' => 'N22',
                'name' => 'Ca Dem',
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'break_start_time' => '01:00:00',
                'break_end_time' => '01:30:00',
                'workday_value' => 1.0,
                'timesheet_type' => 'night',
                'is_overnight' => true,
                'min_meal_hours' => 4.0,
                'grace_late_minutes' => 5,
                'grace_early_minutes' => 5,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'code' => 'HC07',
                'name' => 'Hanh Chinh Som',
                'start_time' => '07:00:00',
                'end_time' => '16:00:00',
                'break_start_time' => '11:30:00',
                'break_end_time' => '12:30:00',
                'workday_value' => 1.0,
                'timesheet_type' => 'standard',
                'is_overnight' => false,
                'min_meal_hours' => 4.0,
                'grace_late_minutes' => 5,
                'grace_early_minutes' => 5,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ---------------------------------------------------------------
        // Vietnamese Holidays 2026
        // ---------------------------------------------------------------
        $this->insertWithIdentity('holidays', [
            // Tet Duong Lich (New Year)
            ['id' => 1,  'holiday_date' => '2026-01-01', 'name' => 'Tet Duong Lich',                         'multiplier' => 3.0, 'is_paid' => true, 'created_at' => $now, 'updated_at' => $now],

            // Tet Nguyen Dan 2026 (Year of the Horse) - Jan 28 to Feb 2
            ['id' => 2,  'holiday_date' => '2026-01-28', 'name' => 'Tet Nguyen Dan - Ngay 29 Tet',           'multiplier' => 3.0, 'is_paid' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3,  'holiday_date' => '2026-01-29', 'name' => 'Tet Nguyen Dan - Ngay 30 Tet',           'multiplier' => 3.0, 'is_paid' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4,  'holiday_date' => '2026-01-30', 'name' => 'Tet Nguyen Dan - Mung 1',                'multiplier' => 3.0, 'is_paid' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5,  'holiday_date' => '2026-01-31', 'name' => 'Tet Nguyen Dan - Mung 2',                'multiplier' => 3.0, 'is_paid' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6,  'holiday_date' => '2026-02-01', 'name' => 'Tet Nguyen Dan - Mung 3',                'multiplier' => 3.0, 'is_paid' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7,  'holiday_date' => '2026-02-02', 'name' => 'Tet Nguyen Dan - Mung 4 (bu)',           'multiplier' => 3.0, 'is_paid' => true, 'created_at' => $now, 'updated_at' => $now],

            // Gio To Hung Vuong (10/3 AL ~ Apr 6 in 2026)
            ['id' => 8,  'holiday_date' => '2026-04-06', 'name' => 'Gio To Hung Vuong',                      'multiplier' => 3.0, 'is_paid' => true, 'created_at' => $now, 'updated_at' => $now],

            // Ngay Thong Nhat (Reunification Day)
            ['id' => 9,  'holiday_date' => '2026-04-30', 'name' => 'Ngay Giai Phong Mien Nam Thong Nhat Dat Nuoc', 'multiplier' => 3.0, 'is_paid' => true, 'created_at' => $now, 'updated_at' => $now],

            // Quoc Te Lao Dong (Labour Day)
            ['id' => 10, 'holiday_date' => '2026-05-01', 'name' => 'Ngay Quoc Te Lao Dong',                  'multiplier' => 3.0, 'is_paid' => true, 'created_at' => $now, 'updated_at' => $now],

            // Quoc Khanh (National Day)
            ['id' => 11, 'holiday_date' => '2026-09-02', 'name' => 'Ngay Quoc Khanh',                        'multiplier' => 3.0, 'is_paid' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 12, 'holiday_date' => '2026-09-03', 'name' => 'Ngay Quoc Khanh (nghỉ bu)',              'multiplier' => 3.0, 'is_paid' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ---------------------------------------------------------------
        // Late/Early Rules
        // ---------------------------------------------------------------
        $this->insertWithIdentity('late_early_rules', [
            ['id' => 1, 'code' => 'LATE_01', 'name' => 'Di tre 6-15 phut',     'from_minute' => 6,   'to_minute' => 15,  'deduction_type' => 'fixed',   'deduction_value' => 50000.00,   'exclude_meal' => false, 'effective_from' => '2024-01-01', 'effective_to' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'code' => 'LATE_02', 'name' => 'Di tre 16-30 phut',    'from_minute' => 16,  'to_minute' => 30,  'deduction_type' => 'fixed',   'deduction_value' => 100000.00,  'exclude_meal' => false, 'effective_from' => '2024-01-01', 'effective_to' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'code' => 'LATE_03', 'name' => 'Di tre 31-60 phut',    'from_minute' => 31,  'to_minute' => 60,  'deduction_type' => 'fixed',   'deduction_value' => 200000.00,  'exclude_meal' => true,  'effective_from' => '2024-01-01', 'effective_to' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'code' => 'LATE_04', 'name' => 'Di tre tren 60 phut',  'from_minute' => 61,  'to_minute' => 999, 'deduction_type' => 'half_day', 'deduction_value' => 0.50,      'exclude_meal' => true,  'effective_from' => '2024-01-01', 'effective_to' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'code' => 'EARLY_01','name' => 'Ve som 6-15 phut',     'from_minute' => 6,   'to_minute' => 15,  'deduction_type' => 'fixed',   'deduction_value' => 50000.00,   'exclude_meal' => false, 'effective_from' => '2024-01-01', 'effective_to' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'code' => 'EARLY_02','name' => 'Ve som 16-30 phut',    'from_minute' => 16,  'to_minute' => 30,  'deduction_type' => 'fixed',   'deduction_value' => 100000.00,  'exclude_meal' => false, 'effective_from' => '2024-01-01', 'effective_to' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}

