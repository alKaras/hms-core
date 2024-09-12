<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalDepartments extends Model
{
    use HasFactory;

    protected $table = "hospital_departments";

    protected $fillable = [
        'hospital_id',
        'department_id'
    ];

}
