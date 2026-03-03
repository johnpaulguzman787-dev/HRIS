<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {

            $table->id();

            // belongs to employee
            $table->foreignId('employee_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // file info
            $table->string('file_name');

            $table->string('file_path');

            $table->string('file_type');

            $table->bigInteger('file_size');

            // who uploaded (user)
            $table->foreignId('uploaded_by')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->softDeletes();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};