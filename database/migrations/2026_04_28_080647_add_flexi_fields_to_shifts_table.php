<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->boolean('is_flexi')->default(false)->after('is_active');
            $table->decimal('required_hours', 4, 2)->nullable()->after('is_flexi');
        });

        DB::table('shifts')->insertOrIgnore([
            'name'           => 'Flexible Schedule',
            'code'           => 'FLEXI',
            'start_time'     => '08:00:00',
            'end_time'       => '17:00:00',
            'is_active'      => true,
            'is_flexi'       => true,
            'required_hours' => 8.00,
            'break_schedule' => null,
            'description'    => 'Flexible schedule — no fixed start/end time. Status is based on total hours worked vs required hours.',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('shifts')->where('code', 'FLEXI')->delete();

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['is_flexi', 'required_hours']);
        });
    }
};
