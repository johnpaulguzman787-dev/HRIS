<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::create('contribution_settings', function (Blueprint $table) {
        $table->id();
        $table->string('key')->unique();
        $table->decimal('value', 15, 4);
        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('contribution_settings');
    }
};
