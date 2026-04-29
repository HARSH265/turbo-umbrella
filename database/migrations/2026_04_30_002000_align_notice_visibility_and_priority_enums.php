<?php

/**
 * database/migrations/2026_04_30_002000_align_notice_visibility_and_priority_enums.php
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notices')
            ->where('visibility', 'all')
            ->update(['visibility' => 'public']);

        DB::table('notices')
            ->where('visibility', 'specific')
            ->update(['visibility' => 'personal']);

        DB::table('notices')
            ->where('priority', 'important')
            ->update(['priority' => 'high']);

        if ($this->supportsEnumModify()) {
            DB::statement("ALTER TABLE notices MODIFY visibility ENUM('public','personal','role_based') NOT NULL DEFAULT 'public'");
            DB::statement("ALTER TABLE notices MODIFY priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal'");
            return;
        }

        if ($this->isSqlite()) {
            $this->rebuildSqliteNoticesTable(
                "visibility IN ('public','personal','role_based')",
                "priority IN ('low','normal','high','urgent')"
            );
        }
    }

    public function down(): void
    {
        DB::table('notices')
            ->where('visibility', 'public')
            ->update(['visibility' => 'all']);

        DB::table('notices')
            ->where('visibility', 'personal')
            ->update(['visibility' => 'specific']);

        DB::table('notices')
            ->where('priority', 'high')
            ->update(['priority' => 'important']);

        if ($this->supportsEnumModify()) {
            DB::statement("ALTER TABLE notices MODIFY visibility ENUM('all','specific') NOT NULL DEFAULT 'all'");
            DB::statement("ALTER TABLE notices MODIFY priority ENUM('normal','important','urgent') NOT NULL DEFAULT 'normal'");
            return;
        }

        if ($this->isSqlite()) {
            $this->rebuildSqliteNoticesTable(
                "visibility IN ('all','specific')",
                "priority IN ('normal','important','urgent')"
            );
        }
    }

    private function supportsEnumModify(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function isSqlite(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }

    private function rebuildSqliteNoticesTable(string $visibilityCheck, string $priorityCheck): void
    {
        DB::statement('PRAGMA foreign_keys=OFF');

        DB::statement("
            CREATE TABLE notices_temp (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                society_id INTEGER NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                priority VARCHAR(255) NOT NULL DEFAULT 'normal' CHECK ({$priorityCheck}),
                visibility VARCHAR(255) NOT NULL DEFAULT 'public' CHECK ({$visibilityCheck}),
                publish_date DATE NOT NULL,
                expiry_date DATE NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_by INTEGER NOT NULL,
                updated_by INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                category VARCHAR(255) NOT NULL DEFAULT 'general',
                status VARCHAR(255) NOT NULL DEFAULT 'draft',
                target_user_id INTEGER NULL,
                target_role VARCHAR(255) NULL,
                is_pinned TINYINT(1) NOT NULL DEFAULT 0,
                published_at DATETIME NULL,
                expires_at DATETIME NULL
            )
        ");

        DB::statement("
            INSERT INTO notices_temp (
                id, society_id, title, content, priority, visibility, publish_date, expiry_date,
                is_active, created_by, updated_by, created_at, updated_at, deleted_at,
                category, status, target_user_id, target_role, is_pinned, published_at, expires_at
            )
            SELECT
                id, society_id, title, content, priority, visibility, publish_date, expiry_date,
                is_active, created_by, updated_by, created_at, updated_at, deleted_at,
                category, status, target_user_id, target_role, is_pinned, published_at, expires_at
            FROM notices
        ");

        Schema::drop('notices');
        Schema::rename('notices_temp', 'notices');

        Schema::table('notices', function (Blueprint $table) {
            $table->index('society_id');
            $table->index('is_active');
            $table->index('publish_date');
            $table->index('category');
            $table->index('status');
            $table->index('target_user_id');
            $table->index('published_at');
            $table->index('expires_at');
        });

        DB::statement('PRAGMA foreign_keys=ON');
    }
};
