<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->restrictOnDelete(); // don't delete history

            $table->foreignId('shift_id')
                  ->nullable()
                  ->constrained('shifts')
                  ->nullOnDelete();

            $table->foreignId('holiday_id')
                  ->nullable()
                  ->constrained('holidays')
                  ->nullOnDelete();

            $table->date('attendance_date');

            $table->dateTime('clock_in')->nullable();
            $table->dateTime('clock_out')->nullable();

            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('undertime_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);

            $table->decimal('total_hours', 5, 2)->default(0);

            $table->timestamps();

            $table->unique(['employee_id', 'attendance_date']);
            $table->index('attendance_date'); // for payroll filtering
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};