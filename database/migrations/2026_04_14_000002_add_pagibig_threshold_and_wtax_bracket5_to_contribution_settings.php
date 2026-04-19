<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add new Pag-IBIG threshold-based keys
        DB::table('contribution_settings')->insertOrIgnore([
            ['key' => 'pagibig_threshold',   'value' => 10000],
            ['key' => 'pagibig_low_amount',  'value' => 100],
            ['key' => 'pagibig_high_amount', 'value' => 200],
        ]);

        // Add the missing 30% W/Tax bracket (₱2M – ₱8M)
        DB::table('contribution_settings')->insertOrIgnore([
            ['key' => 'wtax_bracket_5', 'value' => 8000000],
            ['key' => 'wtax_rate_5',    'value' => 35.00],
        ]);

        // Fix wtax_rate_4: was incorrectly 35% (should be 30% for ₱2M–₱8M bracket)
        DB::table('contribution_settings')
            ->where('key', 'wtax_rate_4')
            ->update(['value' => 30.00]);
    }

    public function down(): void
    {
        DB::table('contribution_settings')
            ->whereIn('key', [
                'pagibig_threshold', 'pagibig_low_amount', 'pagibig_high_amount',
                'wtax_bracket_5', 'wtax_rate_5',
            ])->delete();

        DB::table('contribution_settings')
            ->where('key', 'wtax_rate_4')
            ->update(['value' => 35.00]);
    }
};
