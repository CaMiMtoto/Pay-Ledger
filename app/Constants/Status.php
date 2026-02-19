<?php

namespace App\Constants;

class Status
{
    const TRIAL = 'trial';
    const ACTIVE = 'active';
    const INACTIVE = 'inactive';
    const PENDING = 'pending';
    const CANCELLED = 'cancelled';
    const UNPAID = 'unpaid';
    const PAID = 'paid';
    const PARTIAL = 'partial';

    public static function debtStatuses(): array
    {
        return [
            ['id'=>(string)self::UNPAID, 'name'=>'Unpaid'],
            ['id'=>(string)self::PAID, 'name'=>'Paid'],
            ['id'=>(string)self::PARTIAL, 'name'=>'Partial'],
        ];
    }


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
