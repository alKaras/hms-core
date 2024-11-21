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

    protected $casts = [
        'firstname' => 'encrypted',
        'lastname' => 'encrypted',
        'date_birthday' => 'encrypted',
        'contact_number' => 'encrypted',
        'emergency_contact_name' => 'encrypted',
        'emergency_contact_phone' => 'encrypted',
        'allergies' => 'encrypted',
        'chronic_conditions' => 'encrypted',
        'insurance_details' => 'encrypted',
        'address' => 'encrypted',
        'blood_type' => 'encrypted',
        'current_medications' => 'encrypted',
        'additional_notes' => 'encrypted',
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
