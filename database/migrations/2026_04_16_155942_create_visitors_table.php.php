<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flat_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('phone', 15);
            $table->string('purpose');
            $table->timestamp('entry_time');
            $table->timestamp('exit_time')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index('flat_id');
            $table->index('entry_time');
            $table->index('approval_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
