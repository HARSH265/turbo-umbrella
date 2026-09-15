<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flat_id')->constrained()->onDelete('cascade');
            $table->string('month', 7); // YYYY-MM format
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->decimal('late_fee', 10, 2)->default(0);
            $table->enum('status', ['unpaid', 'paid', 'overdue'])->default('unpaid');
            $table->date('paid_date')->nullable();
            $table->string('payment_mode')->nullable();
            $table->string('transaction_id')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['flat_id', 'month']);
            $table->index('status');
            $table->index('month');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
