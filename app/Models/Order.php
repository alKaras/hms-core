<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'total_amount',
        'status',
        'created_at',
        'confirmed_at',
        'cancelled_at',
        'cancel_reason',
        'reserve_exp'
    ];

    public function orderServices()
    {
        return $this->hasMany(OrderServices::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderPayments()
    {
        return $this->has(OrderPayment::class);
    }
}
