<?php

namespace App\Models\Order;

use App\Enums\OrderStatusEnum;
use App\Models\User\User;
use App\Models\Hospital\Hospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'hospital_id',
        'sum_total',
        'sum_subtotal',
        'status',
        'created_at',
        'confirmed_at',
        'cancelled_at',
        'cancel_reason',
        'reserve_exp'
    ];

    protected $casts = [
        'status' => OrderStatusEnum::class
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

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }
}
