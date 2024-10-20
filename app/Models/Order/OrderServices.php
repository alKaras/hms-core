<?php

namespace App\Models\Order;

use App\Models\TimeSlots;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderServices extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'time_slot_id',
        'price',
        'fee',
        'is_canceled',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function timeSlot()
    {
        return $this->belongsTo(TimeSlots::class);
    }
}
