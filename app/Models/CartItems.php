<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItems extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'time_slot_id',
        'price'
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function timeSlots()
    {
        return $this->belongsTo(TimeSlots::class);
    }
}
