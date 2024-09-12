<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorDepartments extends Model
{
    use HasFactory;

    protected $table = "doctor_departments";

    protected $fillable = ['departmnent_id', 'doctor_id'];
}
