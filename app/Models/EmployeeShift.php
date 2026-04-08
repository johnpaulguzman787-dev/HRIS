<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'shift_id',
        'work_setup',
        'days_off',
        'effective_date',
        'end_date',
        'is_active',
        'break_schedule',
        'description',
    ];

    protected $casts = [
        'days_off'       => 'array',
        'effective_date' => 'date',
        'end_date'       => 'date',
        'is_active'      => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}