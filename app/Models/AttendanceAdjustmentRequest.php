<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceAdjustmentRequest extends Model
{
    protected $fillable = [
        'ref_no', 'employee_id', 'attendance_log_id', 'attendance_date',
        'original_clock_in', 'original_clock_out',
        'requested_clock_in', 'requested_clock_out',
        'reason', 'document_path', 'status', 'rejection_reason',
        'supervisor_approved_by', 'supervisor_approved_at',
        'approved_by', 'approved_at',
        'rejected_by', 'rejected_at',
    ];

    protected $casts = [
        'attendance_date'       => 'date',
        'supervisor_approved_at'=> 'datetime',
        'approved_at'           => 'datetime',
        'rejected_at'           => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendanceLog()
    {
        return $this->belongsTo(AttendanceLog::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function supervisorApprover()
    {
        return $this->belongsTo(User::class, 'supervisor_approved_by');
    }
}
