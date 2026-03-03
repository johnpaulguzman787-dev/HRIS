<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Add role column
            $table->string('role')
                  ->default('employee')
                  ->after('password');

            // Remove name column
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Add back name if rollback
            $table->string('name')->after('id');

            // Remove role if rollback
            $table->dropColumn('role');
        });
    }
};
