<?php

namespace App\Models\Doctor;

use App\Models\Department\Department;
use App\Models\HServices;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;

    protected $table = 'doctors';
    protected $fillable = [
        'specialization',
        'hidden'
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
}