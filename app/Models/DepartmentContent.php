<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentContent extends Model
{
    use HasFactory;

    protected $table = "department_content";

    protected $fillable = [
        'title',
        'description',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
