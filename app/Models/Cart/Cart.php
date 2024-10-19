<?php

namespace App\Models\Cart;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'expired_at',
    ];

    public function items()
    {
        return $this->hasMany(CartItems::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
