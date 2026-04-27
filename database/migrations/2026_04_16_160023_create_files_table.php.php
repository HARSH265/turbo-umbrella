<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create files table for centralized file management
     * Stores metadata for all uploaded files across modules
     */
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('module'); // complaints, notices, users, etc.
            $table->unsignedBigInteger('entity_id'); // ID of related record
            $table->string('original_name');
            $table->string('stored_name'); // timestamp_hash.ext
            $table->string('path'); // full storage path
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size'); // bytes
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Composite index for efficient lookups
            $table->index(['module', 'entity_id']);
            $table->index('uploaded_by');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
