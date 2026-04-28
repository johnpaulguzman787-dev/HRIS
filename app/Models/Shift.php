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
        'is_flexi',
        'required_hours',
        'break_schedule',
        'description',
    ];

    protected $casts = [
        'is_flexi'       => 'boolean',
        'required_hours' => 'float',
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