<?php

namespace App\Models\Doctor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorServices extends Model
{
    use HasFactory;

    protected $table = "doctor_services";
    protected $fillable = ['doctor_id', 'service_id'];
}
