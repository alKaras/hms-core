<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_amount',
        'status'
    ];

    public function orderServices()
    {
        return $this->hasMany(OrderServices::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
