<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveCredit extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'total_days',
        'used_days',
        'remaining_days',
    ];

    protected $casts = [
        'total_days'     => 'decimal:1',
        'used_days'      => 'decimal:1',
        'remaining_days' => 'decimal:1',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}