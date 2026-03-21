<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftChangeRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'current_shift_id',
        'requested_shift_id',
        'ref_no',
        'work_setup',
        'effective_from',
        'effective_until',
        'reason',
        'document_path',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function currentShift()
    {
        return $this->belongsTo(Shift::class, 'current_shift_id');
    }

    public function requestedShift()
    {
        return $this->belongsTo(Shift::class, 'requested_shift_id');
    }

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }
}