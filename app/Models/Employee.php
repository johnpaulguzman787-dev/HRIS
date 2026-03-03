<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_code',
        'fname',
        'mname',
        'lname',
        'gender',
        'date_of_birth',
        'contact_no',
        'date_hired',
        'employment_status',
        'department_id',
        'job_title_id',
        'address',
    ];

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}