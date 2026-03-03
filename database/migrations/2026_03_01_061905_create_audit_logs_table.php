<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {

            $table->id();

            // who performed the action
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // action performed
            $table->string('action'); 
            // examples: created, updated, deleted, uploaded

            // what model was affected
            $table->string('auditable_type');
            // example: Employee, Document, User

            $table->unsignedBigInteger('auditable_id');

            // old and new values
            $table->json('old_values')->nullable();

            $table->json('new_values')->nullable();

            $table->timestamp('created_at')->useCurrent();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};