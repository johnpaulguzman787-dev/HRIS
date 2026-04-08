<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            // If the second migration never ran on the server, holiday_date exists
            // but date does not. Rename it to match what the controller expects.
            if (Schema::hasColumn('holidays', 'holiday_date') && !Schema::hasColumn('holidays', 'date')) {
                $table->renameColumn('holiday_date', 'date');
            }

            // Add missing columns only if they don't exist yet.
            if (!Schema::hasColumn('holidays', 'pay_rate')) {
                $table->string('pay_rate', 10)->default('200%')->after('type');
            }

            if (!Schema::hasColumn('holidays', 'region')) {
                $table->string('region', 100)->nullable()->after('pay_rate');
            }

            if (!Schema::hasColumn('holidays', 'yearly')) {
                $table->boolean('yearly')->default(false)->after('region');
            }

            if (!Schema::hasColumn('holidays', 'deleted_at')) {
                $table->softDeletes()->after('yearly');
            }

            // Drop legacy column from the original migration if still present.
            if (Schema::hasColumn('holidays', 'is_paid')) {
                $table->dropColumn('is_paid');
            }
        });

        // Extend the type enum to include 'local' if it's not already there.
        $columnType = DB::select("SHOW COLUMNS FROM holidays LIKE 'type'")[0]->Type ?? '';
        if (!str_contains($columnType, 'local')) {
            DB::statement("ALTER TABLE holidays MODIFY COLUMN `type` ENUM('regular', 'special', 'local') NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            if (Schema::hasColumn('holidays', 'date') && !Schema::hasColumn('holidays', 'holiday_date')) {
                $table->renameColumn('date', 'holiday_date');
            }

            foreach (['pay_rate', 'region', 'yearly', 'deleted_at'] as $col) {
                if (Schema::hasColumn('holidays', $col)) {
                    $table->dropColumn($col);
                }
            }

            if (!Schema::hasColumn('holidays', 'is_paid')) {
                $table->boolean('is_paid')->default(true);
            }
        });
    }
};
