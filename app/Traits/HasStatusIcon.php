<?php

namespace App\Traits;

use App\Constants\Status;

trait HasStatusIcon
{
    public function getStatusIconFromTrait(): string
    {
        $status = strtolower($this->status);

        return match ($status) {
            strtolower(Status::Pending) => 'warning',

            strtolower(Status::Submitted),
            strtolower(Status::InProgress),
            strtolower(Status::Confirmed) => 'info-circle',

            strtolower(Status::Completed),
            strtolower(Status::Approved) => 'check',

            strtolower(Status::Rejected),
            strtolower(Status::Cancelled),
            'inactive' => 'exclamation-circle',

            default => 'question-circle',
        };
    }

    // Keep the original method (optional)
    public function getStatusIconAttribute(): string
    {
        return $this->getStatusIconFromTrait();
    }
}
