<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const APPROVAL_CONSTRAINT = 'visitors_approval_state_check';
    private const EXIT_CONSTRAINT = 'visitors_exit_state_check';

    public function up(): void
    {
        if ($this->isSqlite()) {
            $this->rebuildSqliteVisitorsTable(withChecks: true);
            return;
        }

        if ($this->supportsCheckConstraints()) {
            DB::statement("
                ALTER TABLE visitors
                ADD CONSTRAINT " . self::APPROVAL_CONSTRAINT . "
                CHECK (
                    (approval_status = 'pending' AND approved_by IS NULL AND approved_at IS NULL)
                    OR
                    (approval_status IN ('approved', 'rejected') AND approved_by IS NOT NULL AND approved_at IS NOT NULL)
                )
            ");

            DB::statement("
                ALTER TABLE visitors
                ADD CONSTRAINT " . self::EXIT_CONSTRAINT . "
                CHECK (
                    exit_time IS NULL OR approval_status = 'approved'
                )
            ");
        }
    }

    public function down(): void
    {
        if ($this->isSqlite()) {
            $this->rebuildSqliteVisitorsTable(withChecks: false);
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE visitors DROP CHECK ' . self::APPROVAL_CONSTRAINT);
            DB::statement('ALTER TABLE visitors DROP CHECK ' . self::EXIT_CONSTRAINT);
            return;
        }

        if (DB::getDriverName() === 'mariadb') {
            DB::statement('ALTER TABLE visitors DROP CONSTRAINT ' . self::APPROVAL_CONSTRAINT);
            DB::statement('ALTER TABLE visitors DROP CONSTRAINT ' . self::EXIT_CONSTRAINT);
        }
    }

    private function supportsCheckConstraints(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function isSqlite(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }

    private function rebuildSqliteVisitorsTable(bool $withChecks): void
    {
        $approvalCheck = $withChecks
            ? "CHECK (
                (approval_status = 'pending' AND approved_by IS NULL AND approved_at IS NULL)
                OR
                (approval_status IN ('approved', 'rejected') AND approved_by IS NOT NULL AND approved_at IS NOT NULL)
            )"
            : '';

        $exitCheck = $withChecks
            ? "CHECK (exit_time IS NULL OR approval_status = 'approved')"
            : '';

        DB::transaction(function () use ($approvalCheck, $exitCheck) {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement("
                CREATE TABLE visitors_tmp (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    flat_id INTEGER NOT NULL,
                    name VARCHAR NOT NULL,
                    phone VARCHAR(15) NOT NULL,
                    purpose VARCHAR NOT NULL,
                    entry_time DATETIME NOT NULL,
                    exit_time DATETIME NULL
                        {$exitCheck},
                    approval_status VARCHAR NOT NULL DEFAULT 'pending'
                        CHECK (approval_status IN ('pending', 'approved', 'rejected'))
                        {$approvalCheck},
                    approved_by INTEGER NULL,
                    approved_at DATETIME NULL,
                    remarks TEXT NULL,
                    created_by INTEGER NOT NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    FOREIGN KEY(flat_id) REFERENCES flats(id) ON DELETE CASCADE,
                    FOREIGN KEY(approved_by) REFERENCES users(id) ON DELETE SET NULL,
                    FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE CASCADE
                )
            ");

            DB::statement("
                INSERT INTO visitors_tmp (
                    id, flat_id, name, phone, purpose, entry_time, exit_time, approval_status,
                    approved_by, approved_at, remarks, created_by, created_at, updated_at
                )
                SELECT
                    id, flat_id, name, phone, purpose, entry_time, exit_time, approval_status,
                    approved_by, approved_at, remarks, created_by, created_at, updated_at
                FROM visitors
            ");

            DB::statement('DROP TABLE visitors');
            DB::statement('ALTER TABLE visitors_tmp RENAME TO visitors');
            DB::statement('CREATE INDEX visitors_flat_id_index ON visitors (flat_id)');
            DB::statement('CREATE INDEX visitors_entry_time_index ON visitors (entry_time)');
            DB::statement('CREATE INDEX visitors_approval_status_index ON visitors (approval_status)');
            DB::statement('PRAGMA foreign_keys = ON');
        });
    }
};
