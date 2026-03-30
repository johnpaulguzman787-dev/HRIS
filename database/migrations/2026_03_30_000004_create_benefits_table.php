<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benefits', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['Allowance', 'Bonus', 'Incentive']);
            $table->decimal('amount', 12, 2);
            $table->enum('tax', ['Taxable', 'Non-taxable'])->default('Non-taxable');
            $table->string('frequency');
            $table->string('eligibility');
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benefits');
    }
};
