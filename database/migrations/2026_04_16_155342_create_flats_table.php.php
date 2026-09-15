<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tower_id')->constrained()->onDelete('cascade');
            $table->string('flat_number');
            $table->integer('floor_number');
            $table->enum('type', ['1BHK', '2BHK', '3BHK', '4BHK', 'Penthouse']);
            $table->decimal('carpet_area', 10, 2)->nullable();
            $table->enum('occupancy_status', ['occupied', 'vacant'])->default('vacant');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['tower_id', 'flat_number']);
            $table->index('occupancy_status');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flats');
    }
};
