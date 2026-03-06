<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ── Relationships ──────────────────────────────

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    // ── Role Helpers ───────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isHRManager(): bool
    {
        return $this->role === 'hr_manager';
    }

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    public function isPayrollOfficer(): bool
    {
        return $this->role === 'payroll_officer';
    }

    public function isFinanceOfficer(): bool
    {
        return $this->role === 'finance_officer';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles);
    }
}