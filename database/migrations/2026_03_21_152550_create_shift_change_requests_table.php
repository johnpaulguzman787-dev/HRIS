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
        Schema::create('shift_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('current_shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->foreignId('requested_shift_id')->constrained('shifts')->onDelete('cascade');
            $table->string('ref_no', 20)->unique();
            $table->string('work_setup', 10)->nullable();
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->text('reason');
            $table->string('document_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_change_requests');
    }
};
