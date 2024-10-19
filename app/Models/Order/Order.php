<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'sum_total',
        'sum_subtotal',
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
        return $this->hasOne(OrderPayment::class);
    }

    public function orderStatus()
    {
        return $this->belongsTo(OrderStatusRef::class);
    }
}
