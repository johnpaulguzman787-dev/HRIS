<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'shift_id',
        'holiday_id',
        'attendance_date',
        'work_setup',
        'clock_in',
        'clock_out',
        'late_minutes',
        'undertime_minutes',
        'overtime_minutes',
        'total_hours',
        'status',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'clock_in'        => 'datetime',
        'clock_out'       => 'datetime',
        'late_minutes'    => 'integer',
        'undertime_minutes' => 'integer',
        'overtime_minutes'  => 'integer',
        'total_hours'     => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function holiday()
    {
        return $this->belongsTo(Holiday::class);
    }

    public function corrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    public function overtimeRequests()
    {
        return $this->hasMany(OvertimeRequest::class);
    }
}