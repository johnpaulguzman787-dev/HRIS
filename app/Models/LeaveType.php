<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'days_entitled',
        'is_paid',
        'requires_document',
        'applicable_to',
        'carry_over',
        'is_active',
    ];

    protected $casts = [
        'is_paid'            => 'boolean',
        'requires_document'  => 'boolean',
        'carry_over'         => 'boolean',
        'is_active'          => 'boolean',
    ];

    public function credits()
    {
        return $this->hasMany(LeaveCredit::class);
    }

    public function requests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
}