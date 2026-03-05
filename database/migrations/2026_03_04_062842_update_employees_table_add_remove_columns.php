<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {

            // ✅ REMOVE access_level if it exists
            if (Schema::hasColumn('employees', 'access_level')) {
                $table->dropColumn('access_level');
            }

            // ✅ ADD new columns (nullable so existing data won't break)
            if (!Schema::hasColumn('employees', 'suffix')) {
                $table->string('suffix')->nullable()->after('lname');
            }

            if (!Schema::hasColumn('employees', 'employment_type')) {
                $table->string('employment_type')->nullable()->after('suffix');
            }

            if (!Schema::hasColumn('employees', 'contract_period')) {
                $table->integer('contract_period')->nullable()->after('employment_type');
            }

            if (!Schema::hasColumn('employees', 'start_date')) {
                $table->date('start_date')->nullable()->after('contract_period');
            }

            if (!Schema::hasColumn('employees', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {

            // ✅ DROP the new columns
            $columns = ['suffix', 'employment_type', 'contract_period', 'start_date', 'end_date'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $table->dropColumn($col);
                }
            }

            // ✅ restore access_level
            if (!Schema::hasColumn('employees', 'access_level')) {
                $table->string('access_level')->nullable()->after('user_id');
            }
        });
    }
};