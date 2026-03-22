<?php

namespace App\Enums;

enum PayrollRunStatus: string
{
    case DRAFT = 'draft';
    case PREVIEWED = 'previewed';
    case FINALIZED = 'finalized';
    case LOCKED = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PREVIEWED => 'Previewed',
            self::FINALIZED => 'Finalized',
            self::LOCKED => 'Locked',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::DRAFT => $next === self::PREVIEWED,
            self::PREVIEWED => in_array($next, [self::FINALIZED, self::DRAFT]),
            self::FINALIZED => $next === self::LOCKED,
            self::LOCKED => false,
        };
    }
}
