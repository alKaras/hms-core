<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalContent extends Model
{
    use HasFactory;

    protected $table = 'hospital_content';
    protected $fillable = [
        'title',
        'description',
        'address'
    ];

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }
}
