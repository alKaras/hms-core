<?php

namespace App\Models\Doctor;

use App\Models\User;
use App\Models\HServices;
use App\Models\TimeSlots;
use App\Models\Hospital\Hospital;
use App\Models\Department\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Doctor extends Model
{
    use HasFactory;

    protected $table = 'doctors';
    protected $fillable = [
        'specialization',
        'hidden',
        'user_id',
        'hospital_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'doctor_departments', 'doctor_id', 'department_id');
    }

    public function services()
    {
        return $this->belongsToMany(HServices::class, 'doctor_services', 'doctor_id', 'service_id');
    }

    public function timeSlots()
    {
        return $this->hasMany(TimeSlots::class);
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }
}
