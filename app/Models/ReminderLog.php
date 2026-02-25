<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $customer_id
 * @property numeric $total_amount
 * @property string $method
 * @property string $status
 * @property string|null $sent_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReminderLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReminderLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReminderLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReminderLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReminderLog whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReminderLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReminderLog whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReminderLog whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReminderLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReminderLog whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReminderLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ReminderLog extends Model
{
    //
}
