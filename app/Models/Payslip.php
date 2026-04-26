<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'basic_pay',
        'ot_pay',
        'benefits_total',
        'other_additions',
        'gross_pay',
        'sss',
        'philhealth',
        'pagibig',
        'late_deduction',
        'other_deductions',
        'withholding_tax',
        'total_deductions',
        'net_pay',
        'status',
    ];

    protected $casts = [
        'basic_pay'        => 'decimal:2',
        'ot_pay'           => 'decimal:2',
        'benefits_total'   => 'decimal:2',
        'other_additions'  => 'decimal:2',
        'gross_pay'        => 'decimal:2',
        'sss'              => 'decimal:2',
        'philhealth'       => 'decimal:2',
        'pagibig'          => 'decimal:2',
        'late_deduction'   => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'withholding_tax'  => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay'          => 'decimal:2',
    ];

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
