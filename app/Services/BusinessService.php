<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BusinessService
{
    public function getListBuilder(
        string $search = '',
        string $filterStatus = '',
        string $sortBy = '',
        string $sortDirection = 'desc'
    )
    {
        return Business::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            })
            ->when($filterStatus && $filterStatus != 'All', fn(Builder $query) => $query->where(DB::raw('lower(subscription_status)'), strtolower($filterStatus)))
            ->orderBy($sortBy, $sortDirection);
    }
}
