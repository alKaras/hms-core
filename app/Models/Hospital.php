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
}
