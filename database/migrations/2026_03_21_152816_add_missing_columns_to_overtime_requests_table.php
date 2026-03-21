<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->string('ref_no', 20)->unique()->after('id');
            $table->time('ot_start_time')->nullable()->after('ot_date');
            $table->time('ot_end_time')->nullable()->after('ot_start_time');
            $table->string('document_path')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dropColumn(['ref_no', 'ot_start_time', 'ot_end_time', 'document_path']);
        });
    }
};