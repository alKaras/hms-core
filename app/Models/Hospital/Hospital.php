<?php

namespace App\Models\Hospital;

use App\Models\Cart\Cart;
use App\Models\Doctor\Doctor;
use App\Models\HServices;
use App\Models\User\User;
use App\Models\Order\Order;
use App\Models\HospitalReview;
use App\Models\Department\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hospital extends Model
{
    use HasFactory;

    protected $table = 'hospital';
    protected $fillable = [
        'id',
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

    public function workers()
    {
        return $this->hasMany(User::class);
    }

    public function reviews()
    {
        return $this->hasMany(HospitalReview::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }
}
