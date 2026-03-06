<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
    ];

    // ── Relationships ──────────────────────────────

    public function jobTitles()
    {
        return $this->hasMany(JobTitle::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    // ── Accessors ──────────────────────────────────

    public function getEmployeeCountAttribute(): int
    {
        return $this->employees()->count();
    }
}