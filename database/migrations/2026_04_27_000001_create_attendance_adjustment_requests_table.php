<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_adjustment_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ref_no')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('attendance_log_id')->nullable()->constrained('attendance_logs')->nullOnDelete();
            $table->date('attendance_date');
            $table->time('original_clock_in')->nullable();
            $table->time('original_clock_out')->nullable();
            $table->time('requested_clock_in');
            $table->time('requested_clock_out')->nullable();
            $table->text('reason');
            $table->string('document_path')->nullable();
            $table->enum('status', ['pending', 'supervisor_approved', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('supervisor_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('supervisor_approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_adjustment_requests');
    }
};
