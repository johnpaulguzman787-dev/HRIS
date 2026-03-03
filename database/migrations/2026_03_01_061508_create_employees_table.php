<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {

            $table->id();

            // relation to users
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // employee info
            $table->string('employee_code')->unique();

            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');

            $table->enum('gender', ['Male', 'Female']);

            $table->date('date_of_birth');

            $table->string('contact_no');

            $table->date('date_hired');

            $table->string('employment_status');

            $table->text('address');

            // relations
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();

            $table->foreignId('job_title_id')->constrained()->cascadeOnDelete();

            $table->softDeletes();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};