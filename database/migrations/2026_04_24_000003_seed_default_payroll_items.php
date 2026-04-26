<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (!DB::table('payroll_items')->where('name', 'Regular OT')->exists()) {
            DB::table('payroll_items')->insert([
                'name'       => 'Regular OT',
                'multiplier' => 1.25,
                'type'       => 'Addition',
                'basis'      => 'Hourly Rate',
                'status'     => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (!DB::table('payroll_items')->where('name', 'Late Deduction')->exists()) {
            DB::table('payroll_items')->insert([
                'name'       => 'Late Deduction',
                'multiplier' => 1.00,
                'type'       => 'Deduction',
                'basis'      => 'Hourly Rate',
                'status'     => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('payroll_items')->whereIn('name', ['Regular OT', 'Late Deduction'])->delete();
    }
};
