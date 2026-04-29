<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function supportsEnumModify(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function isSqlite(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }

    public function up(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            if (!Schema::hasColumn('maintenances', 'amount_paid')) {
                $table->decimal('amount_paid', 10, 2)->default(0)->after('amount');
            }
        });

        if ($this->supportsEnumModify() && Schema::hasColumn('maintenances', 'status')) {
            DB::statement("ALTER TABLE maintenances MODIFY status ENUM('unpaid','partially_paid','paid','overdue') NOT NULL DEFAULT 'unpaid'");
        } elseif ($this->isSqlite() && Schema::hasColumn('maintenances', 'status')) {
            $this->rebuildSqliteMaintenancesTable(
                "status IN ('unpaid','partially_paid','paid','overdue')",
                'unpaid'
            );
        }
    }

    public function down(): void
    {
        if ($this->supportsEnumModify() && Schema::hasColumn('maintenances', 'status')) {
            DB::statement("ALTER TABLE maintenances MODIFY status ENUM('unpaid','paid','overdue') NOT NULL DEFAULT 'unpaid'");
        } elseif ($this->isSqlite() && Schema::hasColumn('maintenances', 'status')) {
            $this->rebuildSqliteMaintenancesTable(
                "status IN ('unpaid','paid','overdue')",
                'unpaid'
            );
        }

        Schema::table('maintenances', function (Blueprint $table) {
            if (Schema::hasColumn('maintenances', 'amount_paid')) {
                $table->dropColumn('amount_paid');
            }
        });
    }

    private function rebuildSqliteMaintenancesTable(string $statusCheck, string $defaultStatus): void
    {
        DB::transaction(function () use ($statusCheck, $defaultStatus) {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement("
                CREATE TABLE maintenances_tmp (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    flat_id INTEGER NOT NULL,
                    month VARCHAR NOT NULL,
                    amount NUMERIC NOT NULL,
                    amount_paid NUMERIC NOT NULL DEFAULT 0,
                    due_date DATE NOT NULL,
                    late_fee NUMERIC NOT NULL DEFAULT 0,
                    status VARCHAR NOT NULL DEFAULT '{$defaultStatus}' CHECK ({$statusCheck}),
                    paid_date DATE NULL,
                    payment_mode VARCHAR NULL,
                    transaction_id VARCHAR NULL,
                    remarks TEXT NULL,
                    created_by INTEGER NOT NULL,
                    updated_by INTEGER NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    FOREIGN KEY(flat_id) REFERENCES flats(id) ON DELETE CASCADE,
                    FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
                )
            ");

            DB::statement("
                INSERT INTO maintenances_tmp (
                    id, flat_id, month, amount, amount_paid, due_date, late_fee, status,
                    paid_date, payment_mode, transaction_id, remarks, created_by, updated_by,
                    created_at, updated_at
                )
                SELECT
                    id, flat_id, month, amount, amount_paid, due_date, late_fee, status,
                    paid_date, payment_mode, transaction_id, remarks, created_by, updated_by,
                    created_at, updated_at
                FROM maintenances
            ");

            DB::statement('DROP TABLE maintenances');
            DB::statement('ALTER TABLE maintenances_tmp RENAME TO maintenances');
            DB::statement('CREATE UNIQUE INDEX maintenances_flat_id_month_unique ON maintenances (flat_id, month)');
            DB::statement('CREATE INDEX maintenances_status_index ON maintenances (status)');
            DB::statement('CREATE INDEX maintenances_month_index ON maintenances (month)');
            DB::statement('CREATE INDEX maintenances_due_date_index ON maintenances (due_date)');
            DB::statement('PRAGMA foreign_keys = ON');
        });
    }
};
