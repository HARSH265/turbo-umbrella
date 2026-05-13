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

        DB::table('amenity_bookings')
            ->join('amenities', 'amenity_bookings.amenity_id', '=', 'amenities.id')
            ->whereNull('amenity_bookings.society_id')
            ->update([
                'amenity_bookings.society_id' => DB::raw('amenities.society_id'),
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
