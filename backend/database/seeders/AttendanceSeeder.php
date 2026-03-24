<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Database\Seeders\Concerns\IdentityInsert;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceSeeder extends Seeder
{
    use IdentityInsert;
    /**
     * Employee IDs that participate in attendance (all 15 employees, but we focus on
     * the 10 regular employees emp001-emp010 = employee IDs 6-15, plus the 5 staff).
     */
    private const EMPLOYEE_IDS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];

    /**
     * Employees that use afternoon shift (HC13): emp002 (7), emp006 (11)
     * Employee that uses night shift (N22): emp004 (9)
     * All others use standard day shift (HC08, id=1)
     */
    private const SHIFT_MAP = [
        7  => 2, // HC13
        9  => 3, // N22
        11 => 2, // HC13
    ];

    public function run(): void
    {
        DB::table('attendance_monthly_summary')->delete();
        DB::table('attendance_daily')->delete();
        DB::table('attendance_request_details')->delete();
        DB::table('attendance_requests')->delete();
        DB::table('time_logs')->delete();
        DB::table('shift_assignments')->delete();
        DB::table('attendance_periods')->delete();

        $now = now();

        // ---------------------------------------------------------------
        // Attendance Periods (3 months: Jan, Feb, Mar 2026)
        // ---------------------------------------------------------------
        $this->insertWithIdentity('attendance_periods', [
            ['id' => 1, 'period_code' => '2026-01', 'month' => 1, 'year' => 2026, 'from_date' => '2026-01-01', 'to_date' => '2026-01-31', 'status' => 'locked',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'period_code' => '2026-02', 'month' => 2, 'year' => 2026, 'from_date' => '2026-02-01', 'to_date' => '2026-02-28', 'status' => 'confirmed', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'period_code' => '2026-03', 'month' => 3, 'year' => 2026, 'from_date' => '2026-03-01', 'to_date' => '2026-03-31', 'status' => 'draft',     'created_at' => $now, 'updated_at' => $now],
        ]);

        // ---------------------------------------------------------------
        // Generate shift assignments and time logs for Feb 2026 (20 working days)
        // Focus on Feb because it's the "confirmed" period with full data
        // ---------------------------------------------------------------
        $feb2026WorkDays = $this->getWorkDays(2026, 2);
        $shiftAssignments = [];
        $timeLogs = [];
        $saId = 1;
        $tlId = 1;

        // Holidays in Feb 2026: Feb 1 (Tet Mung 3), Feb 2 (Tet Mung 4 bu) - already in holidays table
        $holidays = ['2026-02-01', '2026-02-02'];

        // Filter out holidays from working days
        $feb2026WorkDays = array_filter($feb2026WorkDays, fn ($d) => !in_array($d, $holidays));
        $feb2026WorkDays = array_values($feb2026WorkDays);

        // Anomaly schedule: which employee+day combos have anomalies
        // We need at least 15 anomaly cases
        $anomalies = [
            // [employee_id, day_index, anomaly_type]
            [6,  2,  'late_15'],         // emp001 late 15 min on 3rd working day
            [6,  8,  'late_35'],         // emp001 late 35 min
            [7,  1,  'early_20'],        // emp002 leave 20 min early
            [7,  5,  'absent'],          // emp002 absent (no time log)
            [8,  3,  'late_10'],         // emp003 late 10 min
            [8,  10, 'missing_out'],     // emp003 forgot to check out
            [9,  4,  'late_45'],         // emp004 late 45 min
            [10, 6,  'early_10'],        // emp005 leave 10 min early
            [10, 12, 'absent'],          // emp005 absent
            [11, 7,  'late_20'],         // emp006 late 20 min
            [11, 14, 'missing_in'],      // emp006 forgot to check in
            [12, 2,  'late_65'],         // emp007 late 65 min (half day)
            [13, 9,  'early_25'],        // emp008 leave 25 min early
            [14, 11, 'late_8'],          // emp009 late 8 min
            [15, 0,  'duplicate_log'],   // emp010 duplicate log entry
            [15, 13, 'late_12'],         // emp010 late 12 min
        ];

        $anomalyMap = [];
        foreach ($anomalies as $a) {
            $anomalyMap[$a[0] . '_' . $a[1]] = $a[2];
        }

        foreach (self::EMPLOYEE_IDS as $empId) {
            $shiftId = self::SHIFT_MAP[$empId] ?? 1; // default HC08

            foreach ($feb2026WorkDays as $dayIdx => $dateStr) {
                // Shift assignment
                $shiftAssignments[] = [
                    'id' => $saId,
                    'employee_id' => $empId,
                    'work_date' => $dateStr,
                    'shift_id' => $shiftId,
                    'source' => 'schedule',
                    'note' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $anomalyKey = $empId . '_' . $dayIdx;
                $anomalyType = $anomalyMap[$anomalyKey] ?? null;

                // Generate time logs based on shift and anomaly
                $logs = $this->generateTimeLogs($empId, $dateStr, $shiftId, $anomalyType, $tlId, $now);
                foreach ($logs as $log) {
                    $timeLogs[] = $log;
                    $tlId++;
                }

                $saId++;
            }
        }

        // Also add some time logs for March 2026 (first 15 working days - period in progress)
        $mar2026WorkDays = $this->getWorkDays(2026, 3);
        $mar2026WorkDays = array_slice($mar2026WorkDays, 0, 15); // only first 15 days

        foreach (self::EMPLOYEE_IDS as $empId) {
            $shiftId = self::SHIFT_MAP[$empId] ?? 1;

            foreach ($mar2026WorkDays as $dayIdx => $dateStr) {
                $shiftAssignments[] = [
                    'id' => $saId++,
                    'employee_id' => $empId,
                    'work_date' => $dateStr,
                    'shift_id' => $shiftId,
                    'source' => 'schedule',
                    'note' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // Normal logs for March (no anomalies, simpler data)
                $logs = $this->generateTimeLogs($empId, $dateStr, $shiftId, null, $tlId, $now);
                foreach ($logs as $log) {
                    $timeLogs[] = $log;
                    $tlId++;
                }
            }
        }

        $this->insertWithIdentity('shift_assignments', $shiftAssignments);
        $this->insertWithIdentity('time_logs', $timeLogs);

        // ---------------------------------------------------------------
        // Attendance Requests (various statuses)
        // ---------------------------------------------------------------
        $this->insertWithIdentity('attendance_requests', [
            // emp002 (7) - leave request for the absent day, approved
            ['id' => 1, 'employee_id' => 7,  'request_type' => 'annual_leave',     'from_date' => $feb2026WorkDays[5], 'to_date' => $feb2026WorkDays[5], 'reason' => 'Nghi phep nam - viec gia dinh',        'status' => 'approved', 'submitted_at' => Carbon::parse($feb2026WorkDays[5])->subDays(3), 'approved_by' => 2, 'approved_at' => Carbon::parse($feb2026WorkDays[5])->subDays(2), 'created_at' => $now, 'updated_at' => $now],

            // emp005 (10) - leave request for absent day, approved
            ['id' => 2, 'employee_id' => 10, 'request_type' => 'annual_leave',     'from_date' => $feb2026WorkDays[12], 'to_date' => $feb2026WorkDays[12], 'reason' => 'Nghi phep nam - kham benh',           'status' => 'approved', 'submitted_at' => Carbon::parse($feb2026WorkDays[12])->subDays(5), 'approved_by' => 2, 'approved_at' => Carbon::parse($feb2026WorkDays[12])->subDays(4), 'created_at' => $now, 'updated_at' => $now],

            // emp003 (8) - missing checkout correction, applied
            ['id' => 3, 'employee_id' => 8,  'request_type' => 'correction',       'from_date' => $feb2026WorkDays[10], 'to_date' => $feb2026WorkDays[10], 'reason' => 'Quen cham cong ra - lam den 17:00',   'status' => 'applied',  'submitted_at' => Carbon::parse($feb2026WorkDays[10])->addDay(), 'approved_by' => 2, 'approved_at' => Carbon::parse($feb2026WorkDays[10])->addDays(2), 'created_at' => $now, 'updated_at' => $now],

            // emp006 (11) - missing checkin correction, pending
            ['id' => 4, 'employee_id' => 11, 'request_type' => 'correction',       'from_date' => $feb2026WorkDays[14], 'to_date' => $feb2026WorkDays[14], 'reason' => 'Quen cham cong vao - den luc 13:00',  'status' => 'pending',  'submitted_at' => Carbon::parse($feb2026WorkDays[14])->addDay(), 'approved_by' => null, 'approved_at' => null, 'created_at' => $now, 'updated_at' => $now],

            // emp009 (14) - OT request, approved
            ['id' => 5, 'employee_id' => 14, 'request_type' => 'overtime',         'from_date' => $feb2026WorkDays[15] ?? $feb2026WorkDays[count($feb2026WorkDays) - 1], 'to_date' => $feb2026WorkDays[15] ?? $feb2026WorkDays[count($feb2026WorkDays) - 1], 'reason' => 'Tang ca hoan thanh du an',             'status' => 'approved', 'submitted_at' => Carbon::parse($feb2026WorkDays[15] ?? $feb2026WorkDays[count($feb2026WorkDays) - 1])->subDay(), 'approved_by' => 1, 'approved_at' => Carbon::parse($feb2026WorkDays[15] ?? $feb2026WorkDays[count($feb2026WorkDays) - 1]), 'created_at' => $now, 'updated_at' => $now],

            // emp001 (6) - sick leave, draft (not submitted yet)
            ['id' => 6, 'employee_id' => 6,  'request_type' => 'sick_leave',       'from_date' => '2026-03-15', 'to_date' => '2026-03-15', 'reason' => 'Nghi om',                              'status' => 'draft',    'submitted_at' => null, 'approved_by' => null, 'approved_at' => null, 'created_at' => $now, 'updated_at' => $now],

            // emp007 (12) - late correction, rejected
            ['id' => 7, 'employee_id' => 12, 'request_type' => 'correction',       'from_date' => $feb2026WorkDays[2], 'to_date' => $feb2026WorkDays[2], 'reason' => 'Ket xe, xin bo sung cham cong',       'status' => 'rejected', 'submitted_at' => Carbon::parse($feb2026WorkDays[2])->addDay(), 'approved_by' => 2, 'approved_at' => Carbon::parse($feb2026WorkDays[2])->addDays(2), 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Attendance request details
        $this->insertWithIdentity('attendance_request_details', [
            ['id' => 1, 'request_id' => 1, 'work_date' => $feb2026WorkDays[5],  'requested_check_in' => null, 'requested_check_out' => null, 'requested_hours' => 8.0, 'note' => 'Nghi ca ngay'],
            ['id' => 2, 'request_id' => 2, 'work_date' => $feb2026WorkDays[12], 'requested_check_in' => null, 'requested_check_out' => null, 'requested_hours' => 8.0, 'note' => 'Nghi ca ngay'],
            ['id' => 3, 'request_id' => 3, 'work_date' => $feb2026WorkDays[10], 'requested_check_in' => null, 'requested_check_out' => $feb2026WorkDays[10] . ' 17:00:00', 'requested_hours' => null, 'note' => 'Bo sung gio ra'],
            ['id' => 4, 'request_id' => 4, 'work_date' => $feb2026WorkDays[14], 'requested_check_in' => $feb2026WorkDays[14] . ' 13:00:00', 'requested_check_out' => null, 'requested_hours' => null, 'note' => 'Bo sung gio vao'],
            ['id' => 5, 'request_id' => 5, 'work_date' => $feb2026WorkDays[15] ?? $feb2026WorkDays[count($feb2026WorkDays) - 1], 'requested_check_in' => null, 'requested_check_out' => null, 'requested_hours' => 3.0, 'note' => 'Tang ca 3 gio'],
            ['id' => 6, 'request_id' => 6, 'work_date' => '2026-03-15', 'requested_check_in' => null, 'requested_check_out' => null, 'requested_hours' => 8.0, 'note' => 'Nghi om'],
            ['id' => 7, 'request_id' => 7, 'work_date' => $feb2026WorkDays[2], 'requested_check_in' => $feb2026WorkDays[2] . ' 08:00:00', 'requested_check_out' => null, 'requested_hours' => null, 'note' => 'Xin sua gio vao'],
        ]);

        // ---------------------------------------------------------------
        // Attendance Daily (for Feb 2026 - the confirmed period)
        // ---------------------------------------------------------------
        $attendanceDaily = [];
        $adId = 1;

        // Build a map of shift_assignment IDs for Feb
        $febSaMap = [];
        foreach ($shiftAssignments as $sa) {
            if (in_array($sa['work_date'], $feb2026WorkDays)) {
                $febSaMap[$sa['employee_id'] . '_' . $sa['work_date']] = $sa['id'];
            }
        }

        foreach (self::EMPLOYEE_IDS as $empId) {
            foreach ($feb2026WorkDays as $dayIdx => $dateStr) {
                $anomalyKey = $empId . '_' . $dayIdx;
                $anomalyType = $anomalyMap[$anomalyKey] ?? null;
                $saKey = $empId . '_' . $dateStr;
                $saIdRef = $febSaMap[$saKey] ?? null;

                $daily = $this->buildDailyRecord($adId, $empId, $dateStr, 2, $saIdRef, $anomalyType, $now);
                $attendanceDaily[] = $daily;
                $adId++;
            }
        }

        $this->insertWithIdentity('attendance_daily', $attendanceDaily);

        // ---------------------------------------------------------------
        // Attendance Monthly Summary (Feb 2026)
        // ---------------------------------------------------------------
        $monthlySummaries = [];
        $msId = 1;

        foreach (self::EMPLOYEE_IDS as $empId) {
            // Aggregate from daily records
            $empDailies = array_filter($attendanceDaily, fn ($d) => $d['employee_id'] === $empId);
            $totalWorkdays = array_sum(array_column($empDailies, 'workday_value'));
            $regularHours = array_sum(array_column($empDailies, 'regular_hours'));
            $otHours = array_sum(array_column($empDailies, 'ot_hours'));
            $nightHours = array_sum(array_column($empDailies, 'night_hours'));
            $lateMinutes = array_sum(array_column($empDailies, 'late_minutes'));
            $earlyMinutes = array_sum(array_column($empDailies, 'early_minutes'));
            $mealCount = array_sum(array_column($empDailies, 'meal_count'));

            $absentDays = count(array_filter($empDailies, fn ($d) => $d['attendance_status'] === 'absent'));
            $leaveDays = count(array_filter($empDailies, fn ($d) => $d['attendance_status'] === 'leave'));

            $monthlySummaries[] = [
                'id' => $msId++,
                'attendance_period_id' => 2, // Feb 2026
                'employee_id' => $empId,
                'total_workdays' => (float) $totalWorkdays,
                'regular_hours' => (float) $regularHours,
                'ot_hours' => (float) $otHours,
                'night_hours' => (float) $nightHours,
                'paid_leave_days' => (int) $leaveDays,
                'unpaid_leave_days' => (int) $absentDays,
                'late_minutes' => (int) $lateMinutes,
                'early_minutes' => (int) $earlyMinutes,
                'meal_count' => (int) $mealCount,
                'status' => 'confirmed',
                'generated_at' => $now,
                'confirmed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->insertWithIdentity('attendance_monthly_summary', $monthlySummaries);
    }

    /**
     * Get working days (Mon-Fri) for a given month/year.
     */
    private function getWorkDays(int $year, int $month): array
    {
        $days = [];
        $start = Carbon::create($year, $month, 1);
        $end = $start->copy()->endOfMonth();

        while ($start->lte($end)) {
            if ($start->isWeekday()) {
                $days[] = $start->format('Y-m-d');
            }
            $start->addDay();
        }

        return $days;
    }

    /**
     * Generate time log records for a single employee on a single day.
     */
    private function generateTimeLogs(int $empId, string $dateStr, int $shiftId, ?string $anomalyType, int $startId, $now): array
    {
        $logs = [];

        // Determine shift times
        [$checkIn, $checkOut] = match ($shiftId) {
            2 => ['13:00', '22:00'],
            3 => ['22:00', '06:00'],
            default => ['08:00', '17:00'],
        };

        if ($anomalyType === 'absent') {
            // No logs at all
            return [];
        }

        // Random small variance (-3 to +3 minutes for normal)
        $inVariance = rand(-3, 2);
        $outVariance = rand(-2, 3);

        $inTime = Carbon::parse("$dateStr $checkIn")->addMinutes($inVariance);
        $outTime = Carbon::parse("$dateStr $checkOut")->addMinutes($outVariance);

        // Handle overnight shift
        if ($shiftId === 3) {
            $outTime = Carbon::parse($dateStr)->addDay()->setTime(6, 0)->addMinutes($outVariance);
        }

        // Apply anomaly modifications
        switch ($anomalyType) {
            case 'late_8':
                $inTime = Carbon::parse("$dateStr $checkIn")->addMinutes(8);
                break;
            case 'late_10':
                $inTime = Carbon::parse("$dateStr $checkIn")->addMinutes(10);
                break;
            case 'late_12':
                $inTime = Carbon::parse("$dateStr $checkIn")->addMinutes(12);
                break;
            case 'late_15':
                $inTime = Carbon::parse("$dateStr $checkIn")->addMinutes(15);
                break;
            case 'late_20':
                $inTime = Carbon::parse("$dateStr $checkIn")->addMinutes(20);
                break;
            case 'late_35':
                $inTime = Carbon::parse("$dateStr $checkIn")->addMinutes(35);
                break;
            case 'late_45':
                $inTime = Carbon::parse("$dateStr $checkIn")->addMinutes(45);
                break;
            case 'late_65':
                $inTime = Carbon::parse("$dateStr $checkIn")->addMinutes(65);
                break;
            case 'early_10':
                $outTime = Carbon::parse("$dateStr $checkOut")->subMinutes(10);
                if ($shiftId === 3) {
                    $outTime = Carbon::parse($dateStr)->addDay()->setTime(5, 50);
                }
                break;
            case 'early_20':
                $outTime = Carbon::parse("$dateStr $checkOut")->subMinutes(20);
                break;
            case 'early_25':
                $outTime = Carbon::parse("$dateStr $checkOut")->subMinutes(25);
                break;
            case 'missing_out':
                // Only check-in, no check-out
                $logs[] = [
                    'id' => $startId,
                    'employee_id' => $empId,
                    'log_time' => $inTime->format('Y-m-d H:i:s'),
                    'machine_number' => 'MC01',
                    'log_type' => 'in',
                    'source' => 'machine',
                    'is_valid' => true,
                    'invalid_reason' => null,
                    'raw_ref' => 'RAW-' . $empId . '-' . $dateStr . '-IN',
                    'created_at' => $now,
                ];
                return $logs;
            case 'missing_in':
                // Only check-out, no check-in
                $logs[] = [
                    'id' => $startId,
                    'employee_id' => $empId,
                    'log_time' => $outTime->format('Y-m-d H:i:s'),
                    'machine_number' => 'MC01',
                    'log_type' => 'out',
                    'source' => 'machine',
                    'is_valid' => true,
                    'invalid_reason' => null,
                    'raw_ref' => 'RAW-' . $empId . '-' . $dateStr . '-OUT',
                    'created_at' => $now,
                ];
                return $logs;
            case 'duplicate_log':
                // Normal in+out plus a duplicate in log 2 minutes later
                $logs[] = [
                    'id' => $startId,
                    'employee_id' => $empId,
                    'log_time' => $inTime->format('Y-m-d H:i:s'),
                    'machine_number' => 'MC01',
                    'log_type' => 'in',
                    'source' => 'machine',
                    'is_valid' => true,
                    'invalid_reason' => null,
                    'raw_ref' => 'RAW-' . $empId . '-' . $dateStr . '-IN',
                    'created_at' => $now,
                ];
                $logs[] = [
                    'id' => $startId + 1,
                    'employee_id' => $empId,
                    'log_time' => $inTime->copy()->addMinutes(2)->format('Y-m-d H:i:s'),
                    'machine_number' => 'MC02',
                    'log_type' => 'in',
                    'source' => 'machine',
                    'is_valid' => false,
                    'invalid_reason' => 'Duplicate check-in within 5 minutes',
                    'raw_ref' => 'RAW-' . $empId . '-' . $dateStr . '-IN-DUP',
                    'created_at' => $now,
                ];
                $logs[] = [
                    'id' => $startId + 2,
                    'employee_id' => $empId,
                    'log_time' => $outTime->format('Y-m-d H:i:s'),
                    'machine_number' => 'MC01',
                    'log_type' => 'out',
                    'source' => 'machine',
                    'is_valid' => true,
                    'invalid_reason' => null,
                    'raw_ref' => 'RAW-' . $empId . '-' . $dateStr . '-OUT',
                    'created_at' => $now,
                ];
                return $logs;
        }

        // Normal in/out pair
        $logs[] = [
            'id' => $startId,
            'employee_id' => $empId,
            'log_time' => $inTime->format('Y-m-d H:i:s'),
            'machine_number' => 'MC01',
            'log_type' => 'in',
            'source' => 'machine',
            'is_valid' => true,
            'invalid_reason' => null,
            'raw_ref' => 'RAW-' . $empId . '-' . $dateStr . '-IN',
            'created_at' => $now,
        ];
        $logs[] = [
            'id' => $startId + 1,
            'employee_id' => $empId,
            'log_time' => $outTime->format('Y-m-d H:i:s'),
            'machine_number' => 'MC01',
            'log_type' => 'out',
            'source' => 'machine',
            'is_valid' => true,
            'invalid_reason' => null,
            'raw_ref' => 'RAW-' . $empId . '-' . $dateStr . '-OUT',
            'created_at' => $now,
        ];

        return $logs;
    }

    /**
     * Build a single attendance_daily record.
     */
    private function buildDailyRecord(int $id, int $empId, string $dateStr, int $periodId, ?int $saId, ?string $anomalyType, $now): array
    {
        $shiftId = self::SHIFT_MAP[$empId] ?? 1;

        [$checkIn, $checkOut] = match ($shiftId) {
            2 => ['13:00', '22:00'],
            3 => ['22:00', '06:00'],
            default => ['08:00', '17:00'],
        };

        // Defaults for a normal day
        $firstIn = Carbon::parse("$dateStr $checkIn");
        $lastOut = Carbon::parse("$dateStr $checkOut");
        if ($shiftId === 3) {
            $lastOut = Carbon::parse($dateStr)->addDay()->setTime(6, 0);
        }

        $lateMin = 0;
        $earlyMin = 0;
        $regularHours = 8.0;
        $otHours = 0.0;
        $nightHours = ($shiftId === 3) ? 7.5 : 0.0;
        $workdayValue = 1.0;
        $mealCount = 1;
        $status = 'present';
        $sourceStatus = null;

        switch ($anomalyType) {
            case 'absent':
                $firstIn = null;
                $lastOut = null;
                $regularHours = 0;
                $nightHours = 0;
                $workdayValue = 0;
                $mealCount = 0;
                $status = 'absent';
                break;

            case 'late_8':
                $lateMin = 8;
                $firstIn = Carbon::parse("$dateStr $checkIn")->addMinutes(8);
                $regularHours = 7.9;
                $status = 'present';
                $sourceStatus = 'late';
                break;

            case 'late_10':
                $lateMin = 10;
                $firstIn = Carbon::parse("$dateStr $checkIn")->addMinutes(10);
                $regularHours = 7.8;
                $status = 'present';
                $sourceStatus = 'late';
                break;

            case 'late_12':
                $lateMin = 12;
                $firstIn = Carbon::parse("$dateStr $checkIn")->addMinutes(12);
                $regularHours = 7.8;
                $status = 'present';
                $sourceStatus = 'late';
                break;

            case 'late_15':
                $lateMin = 15;
                $firstIn = Carbon::parse("$dateStr $checkIn")->addMinutes(15);
                $regularHours = 7.8;
                $status = 'present';
                $sourceStatus = 'late';
                break;

            case 'late_20':
                $lateMin = 20;
                $firstIn = Carbon::parse("$dateStr $checkIn")->addMinutes(20);
                $regularHours = 7.7;
                $status = 'present';
                $sourceStatus = 'late';
                break;

            case 'late_35':
                $lateMin = 35;
                $firstIn = Carbon::parse("$dateStr $checkIn")->addMinutes(35);
                $regularHours = 7.4;
                $status = 'partial';
                $sourceStatus = 'late';
                break;

            case 'late_45':
                $lateMin = 45;
                $firstIn = Carbon::parse("$dateStr $checkIn")->addMinutes(45);
                $regularHours = 7.3;
                $status = 'partial';
                $sourceStatus = 'late';
                break;

            case 'late_65':
                $lateMin = 65;
                $firstIn = Carbon::parse("$dateStr $checkIn")->addMinutes(65);
                $regularHours = 6.9;
                $workdayValue = 0.5;
                $mealCount = 0;
                $status = 'partial';
                $sourceStatus = 'late_half_day';
                break;

            case 'early_10':
                $earlyMin = 10;
                $lastOut = Carbon::parse("$dateStr $checkOut")->subMinutes(10);
                if ($shiftId === 3) {
                    $lastOut = Carbon::parse($dateStr)->addDay()->setTime(5, 50);
                }
                $regularHours = 7.8;
                $status = 'present';
                $sourceStatus = 'early';
                break;

            case 'early_20':
                $earlyMin = 20;
                $lastOut = Carbon::parse("$dateStr $checkOut")->subMinutes(20);
                $regularHours = 7.7;
                $status = 'present';
                $sourceStatus = 'early';
                break;

            case 'early_25':
                $earlyMin = 25;
                $lastOut = Carbon::parse("$dateStr $checkOut")->subMinutes(25);
                $regularHours = 7.6;
                $status = 'present';
                $sourceStatus = 'early';
                break;

            case 'missing_out':
                $lastOut = null;
                $regularHours = 0;
                $nightHours = 0;
                $workdayValue = 0;
                $mealCount = 0;
                $status = 'anomaly';
                $sourceStatus = 'missing_checkout';
                break;

            case 'missing_in':
                $firstIn = null;
                $regularHours = 0;
                $nightHours = 0;
                $workdayValue = 0;
                $mealCount = 0;
                $status = 'anomaly';
                $sourceStatus = 'missing_checkin';
                break;

            case 'duplicate_log':
                // Treated as normal after de-duplication
                $status = 'present';
                $sourceStatus = 'duplicate_filtered';
                break;
        }

        return [
            'id' => $id,
            'employee_id' => $empId,
            'work_date' => $dateStr,
            'attendance_period_id' => $periodId,
            'shift_assignment_id' => $saId,
            'first_in' => $firstIn ? $firstIn->format('Y-m-d H:i:s') : null,
            'last_out' => $lastOut ? $lastOut->format('Y-m-d H:i:s') : null,
            'late_minutes' => (int) $lateMin,
            'early_minutes' => (int) $earlyMin,
            'regular_hours' => (float) $regularHours,
            'ot_hours' => (float) $otHours,
            'night_hours' => (float) $nightHours,
            'workday_value' => (float) $workdayValue,
            'meal_count' => (int) $mealCount,
            'attendance_status' => $status,
            'source_status' => $sourceStatus,
            'is_confirmed_by_employee' => 1,
            'confirmed_at' => $now,
            'confirmed_by' => $empId <= 5 ? $empId : null,
            'calculation_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}

