<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderServices extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'time_slot_id',
        'price'
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
