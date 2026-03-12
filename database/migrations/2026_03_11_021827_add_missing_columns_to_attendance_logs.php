<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->after('id');
            $table->unsignedBigInteger('shift_id')->nullable()->after('employee_id');
            $table->unsignedBigInteger('holiday_id')->nullable()->after('shift_id');
            $table->date('attendance_date')->after('holiday_id');
            $table->datetime('clock_in')->nullable()->after('work_setup');
            $table->datetime('clock_out')->nullable()->after('clock_in');
            $table->unsignedInteger('late_minutes')->default(0)->after('clock_out');
            $table->unsignedInteger('undertime_minutes')->default(0)->after('late_minutes');
            $table->unsignedInteger('overtime_minutes')->default(0)->after('undertime_minutes');
            $table->decimal('total_hours', 5, 2)->default(0)->after('overtime_minutes');

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('set null');
            $table->foreign('holiday_id')->references('id')->on('holidays')->onDelete('set null');

            $table->unique(['employee_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['shift_id']);
            $table->dropForeign(['holiday_id']);
            $table->dropUnique(['employee_id', 'attendance_date']);
            $table->dropColumn([
                'employee_id', 'shift_id', 'holiday_id',
                'attendance_date', 'clock_in', 'clock_out',
                'late_minutes', 'undertime_minutes',
                'overtime_minutes', 'total_hours',
            ]);
        });
    }
};