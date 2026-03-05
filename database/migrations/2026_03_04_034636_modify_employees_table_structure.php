<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {

            // ✅ REMOVE old column
            $table->dropColumn('date_hired');

            // ✅ ADD new columns (nullable so existing 6 records won’t break)

            $table->string('suffix')->nullable()->after('lname');


            $table->string('employment_type')->nullable()->after('access_level');

            $table->integer('contract_period')->nullable()->after('employment_type');

            $table->date('start_date')->nullable()->after('contract_period');

            $table->date('end_date')->nullable()->after('start_date');
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