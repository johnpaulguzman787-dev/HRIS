<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SssContributionsSeeder extends Seeder
{
    /**
     * SSS contribution table based on salary brackets.
     *
     * Pattern visible from the official schedule:
     *   - First bracket (₱0 – ₱5,249.99): employee ₱250, employer ₱528
     *   - Subsequent brackets: ₱500-wide salary range, +₱25 employee share per bracket
     *   - Employer share ≈ employee × (9.5 / 4.5) — standard SSS ratio
     *   - Last bracket is open-ended (salary_to = null) at employee ₱1,000
     *
     * Update these values whenever the government issues a new SSS circular.
     */
    public function run(): void
    {
        DB::table('sss_contributions')->truncate();

        $rows = [];

        // ── Bracket 1: ₱0 – ₱5,249.99 ──────────────────────────────────────
        $rows[] = [
            'salary_from'    => 0,
            'salary_to'      => 5249.99,
            'employee_share' => 250.00,
            'employer_share' => 528.00,
            'created_at'     => now(),
            'updated_at'     => now(),
        ];

        // ── Brackets 2–30: ₱5,250 upward, ₱500-wide, +₱25/bracket ──────────
        $salaryFrom = 5250.00;
        $empShare   = 275.00;

        for ($i = 0; $i < 29; $i++) {
            $rows[] = [
                'salary_from'    => $salaryFrom,
                'salary_to'      => $salaryFrom + 499.99,
                'employee_share' => $empShare,
                'employer_share' => round($empShare * (9.5 / 4.5), 2),
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
            $salaryFrom += 500;
            $empShare   += 25;
        }

        // ── Last bracket: ₱19,750+ (open-ended) ─────────────────────────────
        $rows[] = [
            'salary_from'    => 19750.00,
            'salary_to'      => null,
            'employee_share' => 1000.00,
            'employer_share' => 2111.11,
            'created_at'     => now(),
            'updated_at'     => now(),
        ];

        DB::table('sss_contributions')->insert($rows);
    }
}
