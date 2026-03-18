<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_shifts', function (Blueprint $table) {
            $table->enum('work_setup', ['office', 'wfh'])->nullable()->after('shift_id');
            $table->json('days_off')->nullable()->after('work_setup');
        });
    }

    public function down(): void
    {
        Schema::table('employee_shifts', function (Blueprint $table) {
            $table->dropColumn(['work_setup', 'days_off']);
        });
    }
};