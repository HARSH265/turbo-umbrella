<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->string('group')->default('general'); // general, file, maintenance, etc.
            $table->text('description')->nullable();
            $table->boolean('is_editable')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('key');
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_configs');
    }
};
