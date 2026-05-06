<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteNoticesTable(true);
            return;
        }

        Schema::table('notices', function (Blueprint $table) {
            $table->date('publish_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('notices')
            ->whereNull('publish_date')
            ->update(['publish_date' => now()->toDateString()]);

        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteNoticesTable(false);
            return;
        }

        Schema::table('notices', function (Blueprint $table) {
            $table->date('publish_date')->nullable(false)->change();
        });
    }

    private function rebuildSqliteNoticesTable(bool $publishDateNullable): void
    {
        $publishDateColumn = $publishDateNullable ? 'publish_date DATE NULL' : "publish_date DATE NOT NULL DEFAULT CURRENT_DATE";

        DB::statement('PRAGMA foreign_keys=OFF');

        DB::statement("
            CREATE TABLE notices_temp (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                society_id INTEGER NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                priority VARCHAR(255) NOT NULL DEFAULT 'normal' CHECK (priority IN ('low','normal','high','urgent')),
                visibility VARCHAR(255) NOT NULL DEFAULT 'public' CHECK (visibility IN ('public','personal','role_based')),
                {$publishDateColumn},
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
