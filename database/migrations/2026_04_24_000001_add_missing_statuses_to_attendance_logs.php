<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE attendance_logs MODIFY COLUMN status ENUM('present','late','absent','on_leave','holiday','undertime','overtime','incomplete') NOT NULL DEFAULT 'present'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE attendance_logs MODIFY COLUMN status ENUM('present','late','absent','on_leave','holiday') NOT NULL DEFAULT 'present'");
    }
};
