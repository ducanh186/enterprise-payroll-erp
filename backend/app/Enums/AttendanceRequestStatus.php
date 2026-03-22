<?php

namespace App\Enums;

enum AttendanceRequestStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case APPLIED = 'applied';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::APPLIED => 'Applied',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::DRAFT => $next === self::PENDING,
            self::PENDING => in_array($next, [self::APPROVED, self::REJECTED]),
            self::APPROVED => $next === self::APPLIED,
            self::REJECTED => false,
            self::APPLIED => false,
        };
    }
}
