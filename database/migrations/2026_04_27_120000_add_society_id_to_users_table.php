<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'society_id')) {
                $table->foreignId('society_id')
                    ->nullable()
                    ->after('profile_photo')
                    ->constrained('societies')
                    ->nullOnDelete();

                $table->index('society_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'society_id')) {
                $table->dropConstrainedForeignId('society_id');
            }
        });
    }
};
