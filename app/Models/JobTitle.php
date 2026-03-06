<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobTitle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'department_id',
    ];

    // ── Relationships ──────────────────────────────

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}