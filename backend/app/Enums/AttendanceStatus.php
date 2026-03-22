<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case LEAVE = 'leave';
    case HOLIDAY = 'holiday';
    case ABSENT = 'absent';
    case PARTIAL = 'partial';
    case ANOMALY = 'anomaly';

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'Present',
            self::LEAVE => 'Leave',
            self::HOLIDAY => 'Holiday',
            self::ABSENT => 'Absent',
            self::PARTIAL => 'Partial',
            self::ANOMALY => 'Anomaly',
        };
    }
}
