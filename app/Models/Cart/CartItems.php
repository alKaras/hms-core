<?php

namespace App\Models\Cart;

use App\Models\TimeSlots;
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

    public function timeslot()
    {
        return $this->belongsTo(TimeSlots::class);
    }
}
