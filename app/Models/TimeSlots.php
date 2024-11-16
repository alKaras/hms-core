<?php

namespace App\Models;

use App\Enums\TimeslotStateEnum;
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
        'state',
        'online',
    ];

    protected $table = 'time_slots';

    protected $casts = [
        'state' => TimeslotStateEnum::class,
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function service()
    {
        return $this->belongsTo(HServices::class, 'service_id');
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('start_time', $date);
    }

    public function items()
    {
        return $this->hasMany(CartItems::class);
    }

    public function appointments()
    {
        return $this->hasMany(MedAppointments::class);
    }
}
