<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalaryGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_code',
        'level_name',
        'monthly_basic_salary',
    ];

    protected $casts = [
        'monthly_basic_salary' => 'decimal:2',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function getSemiMonthlyPayAttribute(): float
    {
        return $this->monthly_basic_salary / 2;
    }
}
