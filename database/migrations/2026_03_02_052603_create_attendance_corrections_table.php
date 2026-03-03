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
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_log_id')
                  ->constrained('attendance_logs')
                  ->cascadeOnDelete();

            $table->foreignId('requested_by')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignId('approved_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->json('original_values');
            $table->json('corrected_values');

            $table->text('correction_reason');

            $table->enum('status', ['pending', 'approved', 'rejected'])
                  ->default('pending');

            $table->dateTime('approved_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};