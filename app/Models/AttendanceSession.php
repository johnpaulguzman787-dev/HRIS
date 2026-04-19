<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    protected $fillable = [
        'attendance_log_id',
        'clock_in',
        'clock_out',
        'break_start',
        'break_end',
        'break_minutes',
    ];

    protected $casts = [
        'clock_in'      => 'datetime',
        'clock_out'     => 'datetime',
        'break_start'   => 'datetime',
        'break_end'     => 'datetime',
        'break_minutes' => 'integer',
    ];

    public function attendanceLog()
    {
        return $this->belongsTo(AttendanceLog::class);
    }
}
