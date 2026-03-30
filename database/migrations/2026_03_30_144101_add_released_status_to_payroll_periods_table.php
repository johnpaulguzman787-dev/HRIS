<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Expand enum to include both old and new values so existing data isn't truncated
        DB::statement("ALTER TABLE payroll_periods MODIFY COLUMN status ENUM('Pending','Submitted','Completed','Released') NOT NULL DEFAULT 'Pending'");
        // Rename any legacy 'Completed' rows to 'Released'
        DB::statement("UPDATE payroll_periods SET status = 'Released' WHERE status = 'Completed'");
        // Drop 'Completed' now that no rows use it
        DB::statement("ALTER TABLE payroll_periods MODIFY COLUMN status ENUM('Pending','Submitted','Released') NOT NULL DEFAULT 'Pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payroll_periods MODIFY COLUMN status ENUM('Pending','Submitted','Completed') NOT NULL DEFAULT 'Pending'");
    }
};
