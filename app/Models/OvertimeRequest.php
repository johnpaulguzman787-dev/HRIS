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
        'ref_no',
        'ot_date',
        'ot_start_time',
        'ot_end_time',
        'requested_hours',
        'approved_hours',
        'reason',
        'document_path',
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