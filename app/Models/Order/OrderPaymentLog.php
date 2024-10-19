<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPaymentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_payment_id',
        'event',
        'attributes'
    ];

    public function orderPayment()
    {
        return $this->belongsTo(OrderPayment::class);
    }
}
