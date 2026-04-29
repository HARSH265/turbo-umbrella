<?php

/**
 * database/migrations/2026_04_29_120000_align_notices_table_for_phase_3a.php
 */

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            if (!Schema::hasColumn('notices', 'category')) {
                $table->string('category')->default('general')->after('content');
                $table->index('category');
            }

            if (!Schema::hasColumn('notices', 'status')) {
                $table->string('status')->default('draft')->after('priority');
                $table->index('status');
            }

            if (!Schema::hasColumn('notices', 'target_user_id')) {
                $table->foreignId('target_user_id')->nullable()->after('visibility')->constrained('users')->nullOnDelete();
                $table->index('target_user_id');
            }

            if (!Schema::hasColumn('notices', 'target_role')) {
                $table->string('target_role')->nullable()->after('target_user_id');
            }

            if (!Schema::hasColumn('notices', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('target_role');
            }

            if (!Schema::hasColumn('notices', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('is_pinned');
                $table->index('published_at');
            }

            if (!Schema::hasColumn('notices', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('published_at');
                $table->index('expires_at');
            }
        });

        DB::table('notices')
            ->select(['id', 'is_active', 'publish_date', 'expiry_date'])
            ->orderBy('id')
            ->chunkById(100, function ($notices): void {
                foreach ($notices as $notice) {
                    $publishDate = $notice->publish_date ? Carbon::parse($notice->publish_date) : null;
                    $expiryDate = $notice->expiry_date ? Carbon::parse($notice->expiry_date)->endOfDay() : null;

                    $status = 'draft';

                    if ((bool) $notice->is_active && $publishDate && $publishDate->lte(now())) {
                        $status = 'published';
                    }

                    if (!(bool) $notice->is_active || ($expiryDate && $expiryDate->lt(now()))) {
                        $status = 'archived';
                    }

                    DB::table('notices')
                        ->where('id', $notice->id)
                        ->update([
                            'category' => 'general',
                            'status' => $status,
                            'published_at' => $publishDate,
                            'expires_at' => $expiryDate,
                            'is_pinned' => false,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            if (Schema::hasColumn('notices', 'target_user_id')) {
                $table->dropForeign(['target_user_id']);
            }

            $dropIndexes = [
                'notices_category_index',
                'notices_status_index',
                'notices_target_user_id_index',
                'notices_published_at_index',
                'notices_expires_at_index',
            ];

            foreach ($dropIndexes as $index) {
                try {
                    $table->dropIndex($index);
                } catch (\Throwable $e) {
                }
            }

            $columns = [
                'category',
                'status',
                'target_user_id',
                'target_role',
                'is_pinned',
                'published_at',
                'expires_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('notices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
