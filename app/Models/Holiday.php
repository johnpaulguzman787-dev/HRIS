<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'date',
        'type',
        'pay_rate',
        'region',
        'yearly',
    ];

    protected $casts = [
        'date'   => 'date',
        'yearly' => 'boolean',
    ];
}