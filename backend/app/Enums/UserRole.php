<?php

namespace App\Enums;

enum UserRole: string
{
    case EMPLOYEE = 'employee';
    case HR_STAFF = 'hr_staff';
    case ACCOUNTANT = 'accountant';
    case SYSTEM_ADMIN = 'system_admin';
    case MANAGEMENT = 'management';

    public function label(): string
    {
        return match ($this) {
            self::EMPLOYEE => 'Employee',
            self::HR_STAFF => 'HR Staff',
            self::ACCOUNTANT => 'Accountant',
            self::SYSTEM_ADMIN => 'System Admin',
            self::MANAGEMENT => 'Management',
        };
    }
}
