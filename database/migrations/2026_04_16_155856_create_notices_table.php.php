<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('society_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->enum('priority', ['normal', 'important', 'urgent'])->default('normal');
            $table->enum('visibility', ['all', 'specific'])->default('all');
            $table->date('publish_date');
            $table->date('expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('society_id');
            $table->index('is_active');
            $table->index('publish_date');
        });

        // Notice recipients (for specific visibility)
        Schema::create('notice_recipients', function (Blueprint $table) {
            $table->foreignId('notice_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            
            $table->primary(['notice_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_recipients');
        Schema::dropIfExists('notices');
    }
};
