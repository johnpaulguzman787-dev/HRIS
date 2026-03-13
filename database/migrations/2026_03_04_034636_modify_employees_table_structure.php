<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'date_hired')) {
                $table->dropColumn('date_hired');
            }

            if (!Schema::hasColumn('employees', 'suffix')) {
                $table->string('suffix')->nullable()->after('lname');
            }
            if (!Schema::hasColumn('employees', 'employment_type')) {
                $table->string('employment_type')->nullable(); 
            }
            if (!Schema::hasColumn('employees', 'contract_period')) {
                $table->integer('contract_period')->nullable(); 
            }
            if (!Schema::hasColumn('employees', 'start_date')) {
                $table->date('start_date')->nullable(); 
            }
            if (!Schema::hasColumn('employees', 'end_date')) {
                $table->date('end_date')->nullable(); 
            }
        });
    }
    
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {

            // Restore removed column
            $table->date('date_hired');

            // Remove newly added columns
            $table->dropColumn([
                'suffix',
                'employment_type',
                'contract_period',
                'start_date',
                'end_date'
            ]);
        });
    }
};