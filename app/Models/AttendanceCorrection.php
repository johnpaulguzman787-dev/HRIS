<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_log_id',
        'requested_by',
        'approved_by',
        'original_values',
        'corrected_values',
        'correction_reason',
        'status',
        'approved_at',
    ];

    public function attendanceLog()
    {
        return $this->belongsTo(AttendanceLog::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}