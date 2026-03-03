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

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}