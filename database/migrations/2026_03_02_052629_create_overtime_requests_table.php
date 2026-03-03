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
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->restrictOnDelete(); // keep history

            $table->foreignId('attendance_log_id')
                  ->nullable()
                  ->constrained('attendance_logs')
                  ->nullOnDelete();

            $table->foreignId('requested_by')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignId('approved_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->date('ot_date');

            $table->decimal('requested_hours', 4, 2);
            $table->decimal('approved_hours', 4, 2)->nullable();

            $table->text('reason');

            $table->enum('status', ['pending', 'approved', 'rejected'])
                  ->default('pending');

            $table->dateTime('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
    }
};