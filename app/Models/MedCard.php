<?php

namespace App\Models;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedCard extends Model
{
    use HasFactory;

    protected $table = 'medcards';

    protected $fillable = [
        'user_id',
        'firstname',
        'lastname',
        'date_birthday',
        'gender',
        'contact_number',
        'address',
        'blood_type',
        'allergies',
        'chronic_conditions',
        'current_medications',
        'emergency_contact_name',
        'emergency_contact_phone',
        'insurance_details',
        'additional_notes',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointments()
    {
        return $this->hasMany(MedAppointments::class, 'medcard_id', 'id');
    }
}
