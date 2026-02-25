<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $business_id
 * @property numeric $amount
 * @property numeric $paid_amount
 * @property \Carbon\CarbonImmutable|null $due_date
 * @property string $status
 * @property string|null $description
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Business $business
 * @property-read \App\Models\Customer $customer
 * @method static \Database\Factories\DebtFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Debt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Debt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Debt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Debt whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Debt whereBusinessId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Debt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Debt whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Debt whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Debt whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Debt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Debt wherePaidAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Debt whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Debt whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Debt extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts=[
        'due_date' => 'date'
    ];
    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function business(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
