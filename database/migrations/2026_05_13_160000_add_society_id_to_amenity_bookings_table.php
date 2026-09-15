<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amenity_bookings', function (Blueprint $table) {
            $table->foreignId('society_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->nullOnDelete();

            $table->index(['society_id', 'status']);
        });

        // Backfill via a correlated subquery. An UPDATE ... JOIN is MySQL-only and
        // breaks on SQLite (used by the test suite), which cannot resolve a joined
        // table's column inside the SET clause.
        DB::table('amenity_bookings')
            ->whereNull('society_id')
            ->update([
                'society_id' => DB::raw(
                    '(select society_id from amenities where amenities.id = amenity_bookings.amenity_id)'
                ),
            ]);
    }

    public function down(): void
    {
        Schema::table('amenity_bookings', function (Blueprint $table) {
            $table->dropIndex(['society_id', 'status']);
            $table->dropConstrainedForeignId('society_id');
        });
    }
};
