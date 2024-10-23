<?php

namespace App\Models;

use App\Models\User\User;
use App\Models\Hospital\Hospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HospitalReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'hospital_id',
        'body',
        'rating'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }
}
