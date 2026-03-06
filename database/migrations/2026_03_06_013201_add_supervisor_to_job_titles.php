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
    $departments = [1, 2, 3, 4, 5]; // IT, Healthcare, Finance, HR, Operations
    foreach ($departments as $deptId) {
        \DB::table('job_titles')->insert([
            'title'         => 'Supervisor',
            'department_id' => $deptId,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }
}

public function down(): void
{
    \DB::table('job_titles')->where('title', 'Supervisor')->delete();
}
};
