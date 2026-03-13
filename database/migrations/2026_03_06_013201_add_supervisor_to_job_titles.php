<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $departments = \DB::table('departments')->pluck('id');

        foreach ($departments as $deptId) {
            // Avoid duplicates if migration is re-run
            $exists = \DB::table('job_titles')
                ->where('title', 'Supervisor')
                ->where('department_id', $deptId)
                ->exists();

            if (!$exists) {
                \DB::table('job_titles')->insert([
                    'title'         => 'Supervisor',
                    'department_id' => $deptId,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        \DB::table('job_titles')->where('title', 'Supervisor')->delete();
    }
};
