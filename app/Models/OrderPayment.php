<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'session_id',
        'payment_id',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentLogs()
    {
        return $this->hasMany(OrderPaymentLog::class);
    }
}
