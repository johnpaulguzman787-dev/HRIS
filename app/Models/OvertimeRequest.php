<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'attendance_log_id',
        'requested_by',
        'approved_by',
        'ot_date',
        'requested_hours',
        'approved_hours',
        'reason',
        'status',
        'approved_at',
        'rejection_reason',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

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