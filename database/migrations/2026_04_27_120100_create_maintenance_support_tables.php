<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('maintenance_policy_templates')) {
            Schema::create('maintenance_policy_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('billing_cycle');
                $table->string('calculation_type');
                $table->decimal('base_amount', 10, 2)->nullable();
                $table->json('type_amounts')->nullable();
                $table->string('late_fee_type')->nullable();
                $table->decimal('late_fee_value', 10, 2)->nullable();
                $table->unsignedInteger('grace_days')->default(0);
                $table->boolean('allow_partial_payment')->default(false);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('maintenance_policies')) {
            Schema::create('maintenance_policies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('society_id')->constrained()->cascadeOnDelete();
                $table->foreignId('template_id')->constrained('maintenance_policy_templates')->cascadeOnDelete();
                $table->date('effective_from')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['society_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('maintenance_payments')) {
            Schema::create('maintenance_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('maintenance_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount_paid', 10, 2);
                $table->date('payment_date');
                $table->string('payment_mode')->nullable();
                $table->string('transaction_id')->nullable();
                $table->text('remarks')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['maintenance_id', 'payment_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_payments');
        Schema::dropIfExists('maintenance_policies');
        Schema::dropIfExists('maintenance_policy_templates');
    }
};
