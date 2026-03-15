<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = [
        'role',
        'module',
        'can_view',
        'can_create',
        'can_edit',
        'can_archive',
        'can_import',
        'can_export',
    ];

    protected $casts = [
        'can_view'    => 'boolean',
        'can_create'  => 'boolean',
        'can_edit'    => 'boolean',
        'can_archive' => 'boolean',
        'can_import'  => 'boolean',
        'can_export'  => 'boolean',
    ];
}