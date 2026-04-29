<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
        if ($this->supportsEnumModify()) {
            DB::statement("ALTER TABLE complaints MODIFY status ENUM('open','in_progress','resolved','disputed','closed') NOT NULL DEFAULT 'open'");
        } elseif ($this->isSqlite()) {
            $this->rebuildSqliteComplaintsTable(
                "status IN ('open','in_progress','resolved','disputed','closed')",
                'open'
            );
        }
    }

    public function down(): void
    {
        if ($this->supportsEnumModify()) {
            DB::statement("ALTER TABLE complaints MODIFY status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open'");
        } elseif ($this->isSqlite()) {
            $this->rebuildSqliteComplaintsTable(
                "status IN ('open','in_progress','resolved','closed')",
                'open'
            );
        }
    }

    private function rebuildSqliteComplaintsTable(string $statusCheck, string $defaultStatus): void
    {
        DB::transaction(function () use ($statusCheck, $defaultStatus) {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement("
                CREATE TABLE complaints_tmp (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    ticket_number VARCHAR NOT NULL,
                    user_id INTEGER NOT NULL,
                    flat_id INTEGER NOT NULL,
                    category VARCHAR NOT NULL,
                    subject VARCHAR NOT NULL,
                    description TEXT NOT NULL,
                    priority VARCHAR NOT NULL DEFAULT 'medium' CHECK (priority IN ('low','medium','high','urgent')),
                    status VARCHAR NOT NULL DEFAULT '{$defaultStatus}' CHECK ({$statusCheck}),
                    assigned_to INTEGER NULL,
                    assigned_at DATETIME NULL,
                    resolved_at DATETIME NULL,
                    resolution_note TEXT NULL,
                    created_by INTEGER NOT NULL,
                    updated_by INTEGER NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    deleted_at DATETIME NULL,
                    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY(flat_id) REFERENCES flats(id) ON DELETE CASCADE,
                    FOREIGN KEY(assigned_to) REFERENCES users(id) ON DELETE SET NULL,
                    FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
                )
            ");

            DB::statement("
                INSERT INTO complaints_tmp (
                    id, ticket_number, user_id, flat_id, category, subject, description,
                    priority, status, assigned_to, assigned_at, resolved_at, resolution_note,
                    created_by, updated_by, created_at, updated_at, deleted_at
                )
                SELECT
                    id, ticket_number, user_id, flat_id, category, subject, description,
                    priority, status, assigned_to, assigned_at, resolved_at, resolution_note,
                    created_by, updated_by, created_at, updated_at, deleted_at
                FROM complaints
            ");

            DB::statement('DROP TABLE complaints');
            DB::statement('ALTER TABLE complaints_tmp RENAME TO complaints');
            DB::statement('CREATE UNIQUE INDEX complaints_ticket_number_unique ON complaints (ticket_number)');
            DB::statement('CREATE INDEX complaints_ticket_number_index ON complaints (ticket_number)');
            DB::statement('CREATE INDEX complaints_status_index ON complaints (status)');
            DB::statement('CREATE INDEX complaints_priority_index ON complaints (priority)');
            DB::statement('CREATE INDEX complaints_user_id_status_index ON complaints (user_id, status)');
            DB::statement('CREATE INDEX complaints_created_at_index ON complaints (created_at)');
            DB::statement('PRAGMA foreign_keys = ON');
        });
    }
};
