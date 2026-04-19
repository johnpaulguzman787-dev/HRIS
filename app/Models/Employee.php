<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_code',
        'fname',
        'mi',
        'lname',
        'suffix',
        'gender',
        'date_of_birth',
        'contact_no',
        'address',
        'employment_type',
        'employment_status',
        'contract_period',
        'start_date',
        'end_date',
        'department_id',
        'job_title_id',
        'salary_grade_id',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'start_date'    => 'date',
        'end_date'      => 'date',
        'deleted_at'    => 'datetime',
    ];

    // ── Relationships ──────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function overtimeRequests()
    {
        return $this->hasMany(OvertimeRequest::class);
    }

    public function shifts()
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function salaryGrade()
    {
        return $this->belongsTo(SalaryGrade::class);
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    public function benefits()
    {
        return $this->belongsToMany(Benefit::class, 'employee_benefits');
    }

    // ── Accessors ──────────────────────────────────

    public function getFullNameAttribute(): string
    {
        $parts = [$this->fname];

        if ($this->mi) {
            $parts[] = $this->mi . '.';
        }

        $parts[] = $this->lname;

        if ($this->suffix && $this->suffix !== 'None') {
            $parts[] = $this->suffix;
        }

        return implode(' ', $parts);
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(
            substr($this->fname, 0, 1) . substr($this->lname, 0, 1)
        );
    }

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth->age;
    }
}