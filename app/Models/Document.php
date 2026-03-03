<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    // The table is automatically 'documents', but you can specify if different
    // protected $table = 'documents';

    protected $fillable = [
        'employee_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'uploaded_by',
    ];

    // Relationship: Document belongs to an Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Relationship: Document uploaded by a User
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}