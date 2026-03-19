<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->date('date')->after('name');
            $table->enum('type', ['regular', 'special', 'local'])->after('date');
            $table->string('pay_rate', 10)->default('200%')->after('type');
            $table->string('region', 100)->nullable()->after('pay_rate');
            $table->boolean('yearly')->default(false)->after('region');
            $table->softDeletes()->after('yearly');
        });
    }

    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropColumn(['name', 'date', 'type', 'pay_rate', 'region', 'yearly', 'deleted_at']);
        });
    }
};
