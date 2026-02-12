<?php

namespace App\Constants;

class Status
{
    const TRIAL = 'trial';
    const ACTIVE = 'active';
    const INACTIVE = 'inactive';
    const PENDING = 'pending';
    const CANCELLED = 'cancelled';

    public static function businessStatuses(): array
    {
        return [
            self::TRIAL,
            self::PENDING,
            self::ACTIVE,
            self::INACTIVE,
            self::CANCELLED,
        ];
    }

}
