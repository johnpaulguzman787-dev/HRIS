<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'multiplier',
        'type',
        'basis',
        'status',
    ];

    protected $casts = [
        'multiplier' => 'decimal:2',
    ];
}
