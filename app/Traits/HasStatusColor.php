<?php

namespace App\Traits;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasStatusColor
{
    public function statusColor(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $status = $attributes['subscription_status'] ?? $attributes['status'] ?? null;

                return match ($status) {
                    Status::ACTIVE,
                    Status::PAID => 'green',
                    Status::UNPAID,
                    Status::INACTIVE => 'red',
                    Status::PENDING => 'blue',
                    Status::PARTIAL => 'orange',
                    Status::CANCELLED => 'yellow',
                    Status::TRIAL => 'purple',
                    default => 'gray',
                };
            },
        );
    }
}
