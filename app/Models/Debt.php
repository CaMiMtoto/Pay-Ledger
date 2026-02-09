<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $business_id
 * @property int $customer_id
 * @property numeric $amount
 * @property numeric $remaining_amount
 * @property string|null $description
 * @property string|null $due_date
 * @property string $status
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
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
    //
}
