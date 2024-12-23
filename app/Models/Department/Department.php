<?php

namespace App\Models\Department;

use App\Models\Doctor\Doctor;
use App\Models\Hospital\Hospital;
use App\Models\HServices;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $table = "department";

    protected $fillable = [
        'alias',
        'email',
        'phone',
    ];

    public function content()
    {
        return $this->hasOne(DepartmentContent::class, 'department_id');
    }

    public function doctors()
    {
        return $this->belongsToMany(Doctor::class, 'doctor_departments', 'department_id', 'doctor_id');
    }

    public function hospitals()
    {
        return $this->belongsToMany(Hospital::class, 'hospital_departments', 'department_id', 'hospital_id');
    }

    public function service()
    {
        return $this->hasOne(HServices::class, 'department_id');
    }
}
