<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('society_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->enum('type', ['clubhouse', 'pool', 'gym', 'tennis', 'badminton', 'party_hall', 'garden', 'other'])->default('other');
            $table->integer('capacity')->nullable();
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->integer('booking_duration')->default(60)->comment('Duration in minutes');
            $table->integer('advance_booking_days')->default(7);
            $table->integer('cancellation_hours')->default(24);
            $table->decimal('charge_per_hour', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['society_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenities');
    }
};