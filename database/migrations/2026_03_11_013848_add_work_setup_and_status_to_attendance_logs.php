<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->enum('work_setup', ['office', 'wfh'])->nullable();
            $table->enum('status', ['present', 'late', 'absent', 'on_leave', 'holiday'])->default('present');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropColumn(['work_setup', 'status']);
        });
    }
};