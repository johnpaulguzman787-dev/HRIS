<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Benefit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'amount',
        'tax',
        'frequency',
        'eligibility',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
