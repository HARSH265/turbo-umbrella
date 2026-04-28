<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function supportsEnumModify(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    public function up(): void
    {
        if ($this->supportsEnumModify()) {
            DB::statement("ALTER TABLE complaints MODIFY status ENUM('open','in_progress','resolved','disputed','closed') NOT NULL DEFAULT 'open'");
        }
    }

    public function down(): void
    {
        if ($this->supportsEnumModify()) {
            DB::statement("ALTER TABLE complaints MODIFY status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open'");
        }
    }
};
