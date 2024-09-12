<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'alias',
        'email',
        'phone'
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
}
