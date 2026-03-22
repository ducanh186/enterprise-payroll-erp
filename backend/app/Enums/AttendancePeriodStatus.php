<?php

namespace App\Enums;

enum AttendancePeriodStatus: string
{
    case DRAFT = 'draft';
    case GENERATED = 'generated';
    case EMPLOYEE_CONFIRMING = 'employee_confirming';
    case CONFIRMED = 'confirmed';
    case LOCKED = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::GENERATED => 'Generated',
            self::EMPLOYEE_CONFIRMING => 'Employee Confirming',
            self::CONFIRMED => 'Confirmed',
            self::LOCKED => 'Locked',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::DRAFT => $next === self::GENERATED,
            self::GENERATED => $next === self::EMPLOYEE_CONFIRMING,
            self::EMPLOYEE_CONFIRMING => $next === self::CONFIRMED,
            self::CONFIRMED => $next === self::LOCKED,
            self::LOCKED => false,
        };
    }
}
