<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE employees MODIFY COLUMN contract_period ENUM('3 months', '6 months', '1 year', '2 years', 'Indefinite') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE employees MODIFY COLUMN contract_period ENUM('3 months', '6 months', '1 year', 'Indefinite') NULL");
    }
};