<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->timestamp('break_start')->nullable()->after('clock_in');
            $table->timestamp('break_end')->nullable()->after('break_start');
            $table->unsignedInteger('break_minutes')->default(0)->after('break_end');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropColumn(['break_start', 'break_end', 'break_minutes']);
        });
    }
};