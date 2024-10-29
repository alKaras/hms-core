<?php

namespace App\Models;

use App\Models\Cart\CartItems;
use App\Models\Doctor\Doctor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSlots extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'service_id',
        'start_time',
        'end_time',
        'price',
        'state'
    ];

    protected $table = 'time_slots';

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function service()
    {
        return $this->belongsTo(HServices::class);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('start_time', $date);
    }

    public function items()
    {
        return $this->hasMany(CartItems::class);
    }
}
