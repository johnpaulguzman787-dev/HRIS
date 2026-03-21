<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE leave_requests MODIFY COLUMN status ENUM('pending','supervisor_approved','approved','rejected','cancelled') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE overtime_requests MODIFY COLUMN status ENUM('pending','supervisor_approved','approved','rejected','cancelled') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE shift_change_requests MODIFY COLUMN status ENUM('pending','supervisor_approved','approved','rejected','cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE leave_requests MODIFY COLUMN status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE overtime_requests MODIFY COLUMN status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE shift_change_requests MODIFY COLUMN status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending'");
    }
};