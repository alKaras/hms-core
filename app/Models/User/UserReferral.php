<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserReferral extends Model
{
    use HasFactory;

    protected $table = 'user_referrals';

    protected $fillable = [
        'referral_code',
        'encoded_data',
        'decoded_data',
        'user_id',
        'expired_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
