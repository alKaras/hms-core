<?php

namespace App\Models;

use App\Models\Department\Department;
use App\Models\Doctor\Doctor;
use App\Models\Hospital\Hospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HServices extends Model
{
    use HasFactory;

    protected $table = "services";

    protected $fillable = [
        'name',
        'description',
        'is_public',
        'department_id',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function hospitals()
    {
        return $this->belongsToMany(Hospital::class, 'hospital_services', 'service_id', 'hospital_id');
    }

    public function doctors()
    {
        return $this->belongsToMany(Doctor::class, 'doctor_services', 'service_id', 'doctor_id');
    }
}
