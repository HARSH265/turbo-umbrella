<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A maintenance policy is read as a document by residents and committee members, not
 * just applied as a calculation. These fields carry the wording that surrounds the
 * numbers: what the charge covers, how to pay, and anything the society wants on record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_policy_templates', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->text('inclusions')->nullable()->after('description');
            $table->text('payment_terms')->nullable()->after('inclusions');
            $table->text('notes')->nullable()->after('payment_terms');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_policy_templates', function (Blueprint $table) {
            $table->dropColumn(['description', 'inclusions', 'payment_terms', 'notes']);
        });
    }
};
