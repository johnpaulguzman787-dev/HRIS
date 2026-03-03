<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'start_time',
        'end_time',
        'is_active',
        'break_schedule',
        'description',
    ];

    // One shift can have many employee shifts
    public function employeeShifts()
    {
        return $this->hasMany(EmployeeShift::class);
    }

    // One shift can have many attendance logs
    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }
}