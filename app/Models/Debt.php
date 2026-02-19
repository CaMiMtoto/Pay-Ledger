<?php

namespace App\Models;

use App\Traits\HasStatusColor;
use Dompdf\Css\Content\Attr;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $business_id
 * @property int $customer_id
 * @property numeric $amount
 * @property numeric $remaining_amount
 * @property string|null $description
 * @property \Carbon\CarbonImmutable|null $due_date
 * @property string $status
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read mixed $balance
 * @property-read \App\Models\Business $business
 * @property-read \App\Models\Customer $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Debt whereRemainingAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Debt whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Debt whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Debt extends Model
{
    use HasFactory, HasStatusColor;

    protected $casts = [
        'due_date' => 'date'
    ];

    public function business(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Calculate remaining balance
    public function balance(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->amount - $this->payments()->sum('amount')
        );
    }
}
