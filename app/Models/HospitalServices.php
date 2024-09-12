<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalServices extends Model
{
    use HasFactory;

    protected $table = "hospital_services";

    protected $fillable = [
        'hospital_id',
        'service_id'
    ];
}
