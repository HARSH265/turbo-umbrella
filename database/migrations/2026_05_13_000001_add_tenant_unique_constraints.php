<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('towers', function (Blueprint $table) {
            $table->unique(['society_id', 'name'], 'towers_society_name_unique');
        });

        Schema::table('flats', function (Blueprint $table) {
            $table->unique(['tower_id', 'flat_number'], 'flats_tower_flat_unique');
        });

        Schema::table('notices', function (Blueprint $table) {
            $table->unique(['society_id', 'title'], 'notices_society_title_unique');
        });

        Schema::table('maintenances', function (Blueprint $table) {
            $table->unique(['flat_id', 'month'], 'maintenances_flat_month_unique');
        });
    }

    public function down(): void
    {
        Schema::table('towers', function (Blueprint $table) {
            $table->dropUnique('towers_society_name_unique');
        });

        Schema::table('flats', function (Blueprint $table) {
            $table->dropUnique('flats_tower_flat_unique');
        });

        Schema::table('notices', function (Blueprint $table) {
            $table->dropUnique('notices_society_title_unique');
        });

        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropUnique('maintenances_flat_month_unique');
        });
    }
};