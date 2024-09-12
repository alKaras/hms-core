<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hospital extends Model
{
    use HasFactory;

    protected $table = 'hospital';
    protected $fillable = [
        'alias',
        'hospital_phone',
        'hospital_email'
    ];

    public function content()
    {
        return $this->hasOne(HospitalContent::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'hospital_departments', 'hospital_id', 'department_id');
    }

    public function services()
    {
        return $this->belongsToMany(HServices::class, 'hospital_services', 'hospital_id', 'service_id');
    }
}
