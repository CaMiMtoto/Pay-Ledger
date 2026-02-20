<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
//    use BelongsToBusiness;
    protected $fillable = [
        'business_id',
        'customer_id',
        'amount',
        'direction', // 1 for debt, -1 for payment
        'description',
        'reference',
        'transaction_date',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
    ];

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getSignedAmountAttribute(): float|int
    {
        return $this->amount * $this->direction;
    }
}
