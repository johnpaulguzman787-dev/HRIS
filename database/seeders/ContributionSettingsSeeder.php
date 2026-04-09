<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContributionSettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('contribution_settings')->insertOrIgnore([
            ['key' => 'sss_employee_rate',  'value' => 5.00],
            ['key' => 'sss_employer_rate',  'value' => 10.00],
            ['key' => 'sss_max_msc',        'value' => 30000],
            ['key' => 'philhealth_rate',    'value' => 5.00],
            ['key' => 'philhealth_floor',   'value' => 10000],
            ['key' => 'philhealth_ceiling', 'value' => 100000],
            ['key' => 'pagibig_low_rate',   'value' => 1.00],
            ['key' => 'pagibig_high_rate',  'value' => 2.00],
            ['key' => 'pagibig_max',        'value' => 200],
            ['key' => 'wtax_bracket_1',     'value' => 250000],
            ['key' => 'wtax_bracket_2',     'value' => 400000],
            ['key' => 'wtax_bracket_3',     'value' => 800000],
            ['key' => 'wtax_bracket_4',     'value' => 2000000],
            ['key' => 'wtax_rate_1',        'value' => 15.00],
            ['key' => 'wtax_rate_2',        'value' => 20.00],
            ['key' => 'wtax_rate_3',        'value' => 25.00],
            ['key' => 'wtax_rate_4',        'value' => 35.00],
        ]);
    }
}