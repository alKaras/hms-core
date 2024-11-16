<?php

namespace App\Models;

use App\Enums\AppointmentsStatusEnum;
use App\Models\User\User;
use App\Models\Doctor\Doctor;
use App\Models\User\UserReferral;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedAppointments extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'user_id',
        'time_slot_id',
        'referral_id',
        'summary',
        'notes',
        'recommendations',
        'status',
        'medcard_id',
    ];

    protected $casts = [
        'status' => AppointmentsStatusEnum::class,
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function timeslot()
    {
        return $this->belongsTo(TimeSlots::class, 'time_slot_id');
    }

    public function referral()
    {
        return $this->belongsTo(UserReferral::class, 'referral_id');
    }

    public function medcard()
    {
        return $this->belongsTo(MedCard::class);
    }
}
