<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_locations', function (Blueprint $table) {
            $table->string('city', 150)->nullable();
            $table->string('district', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_locations', fn (Blueprint $table) => $table->dropColumn(['city', 'district']));
    }
};
