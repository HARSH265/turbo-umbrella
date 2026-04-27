<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            if (!Schema::hasColumn('maintenances', 'amount_paid')) {
                $table->decimal('amount_paid', 10, 2)->default(0)->after('amount');
            }
        });

        if (Schema::hasColumn('maintenances', 'status')) {
            DB::statement("ALTER TABLE maintenances MODIFY status ENUM('unpaid','partially_paid','paid','overdue') NOT NULL DEFAULT 'unpaid'");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('maintenances', 'status')) {
            DB::statement("ALTER TABLE maintenances MODIFY status ENUM('unpaid','paid','overdue') NOT NULL DEFAULT 'unpaid'");
        }

        Schema::table('maintenances', function (Blueprint $table) {
            if (Schema::hasColumn('maintenances', 'amount_paid')) {
                $table->dropColumn('amount_paid');
            }
        });
    }
};
