<?php

namespace Tests\Feature\Files;

use App\Models\Complaint;
use App\Models\File;
use App\Models\Flat;
use App\Models\Role;
use App\Models\Society;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * files:cleanup-orphans deletes from disk permanently, so its "what is still valid"
 * set has to be exactly right. Two ways it was wrong:
 *
 *   1. Complaint::pluck('id') applies the SoftDeletes scope, so attachments of
 *      soft-deleted (still restorable) complaints were erased.
 *   2. whereNotIn('entity_id', []) compiles to "1 = 1", so an empty valid set
 *      matched every file for the module.
 */
class CleanupOrphanFilesTest extends TestCase
{
    use RefreshDatabase;

    private Flat $flat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            \Database\Seeders\RoleSeeder::class,
            \Database\Seeders\PermissionSeeder::class,
            \Database\Seeders\RolePermissionSeeder::class,
        ]);

        $this->systemUser = User::factory()->create([
            'created_by' => null,
            'updated_by' => null,
            'society_id' => null,
        ]);
        $role = Role::where('slug', 'super-admin')->firstOrFail();
        $this->systemUser->roles()->syncWithoutDetaching([$role->id]);
        $this->systemUser->flushAccessCache();

        $society = Society::create([
            'name' => 'Orphan Society',
            'code' => 'ORP-' . fake()->unique()->numerify('###'),
            'address' => fake()->address(),
            'city' => 'Indore',
            'state' => 'MP',
            'pincode' => '452001',
            'contact_number' => '900000' . fake()->unique()->numerify('####'),
            'email' => fake()->unique()->safeEmail(),
            'is_active' => true,
            'created_by' => $this->systemUser->id,
        ]);

        $tower = Tower::create([
            'society_id' => $society->id,
            'name' => 'ORP-T1',
            'total_floors' => 5,
            'is_active' => true,
            'created_by' => $this->systemUser->id,
        ]);

        $this->flat = Flat::create([
            'tower_id' => $tower->id,
            'flat_number' => 'O-101',
            'floor_number' => 1,
            'type' => '2BHK',
            'carpet_area' => 900,
            'occupancy_status' => 'occupied',
            'is_active' => true,
            'created_by' => $this->systemUser->id,
        ]);
    }

    public function test_attachments_of_soft_deleted_complaints_are_kept(): void
    {
        $live = $this->createComplaint();
        $softDeleted = $this->createComplaint();

        $liveFile = $this->createFileFor('complaints', $live->id);
        $keptFile = $this->createFileFor('complaints', $softDeleted->id);

        $softDeleted->delete();
        $this->assertSoftDeleted('complaints', ['id' => $softDeleted->id]);

        $this->artisan('files:cleanup-orphans')->assertSuccessful();

        $this->assertDatabaseHas('files', ['id' => $liveFile->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('files', ['id' => $keptFile->id, 'deleted_at' => null]);
    }

    public function test_genuinely_orphaned_files_are_removed(): void
    {
        $live = $this->createComplaint();
        $liveFile = $this->createFileFor('complaints', $live->id);

        // entity_id points at a complaint that never existed
        $orphan = $this->createFileFor('complaints', 999999);

        $this->artisan('files:cleanup-orphans')->assertSuccessful();

        $this->assertDatabaseHas('files', ['id' => $liveFile->id, 'deleted_at' => null]);

        // Orphan cleanup purges rather than soft-deletes: the parent is gone for good,
        // so there is nothing to restore and the disk space is reclaimed.
        $this->assertDatabaseMissing('files', ['id' => $orphan->id]);
    }

    public function test_no_complaints_at_all_deletes_nothing(): void
    {
        // Files exist but the complaints table is empty — the empty valid-ID set must
        // not be read as "every file is an orphan".
        $a = $this->createFileFor('complaints', 1);
        $b = $this->createFileFor('complaints', 2);

        $this->assertSame(0, Complaint::withTrashed()->count());

        $this->artisan('files:cleanup-orphans')->assertSuccessful();

        $this->assertDatabaseHas('files', ['id' => $a->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('files', ['id' => $b->id, 'deleted_at' => null]);
    }

    public function test_dry_run_reports_without_deleting(): void
    {
        $live = $this->createComplaint();
        $this->createFileFor('complaints', $live->id);
        $orphan = $this->createFileFor('complaints', 999999);

        $this->artisan('files:cleanup-orphans', ['--dry-run' => true])
            ->expectsOutputToContain('Would clean 1 orphan complaints file(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('files', ['id' => $orphan->id, 'deleted_at' => null]);
    }

    private function createComplaint(): Complaint
    {
        return Complaint::create([
            'user_id' => $this->systemUser->id,
            'flat_id' => $this->flat->id,
            'category' => 'plumbing',
            'subject' => 'Leak ' . fake()->unique()->numerify('####'),
            'description' => 'Water is leaking.',
            'priority' => 'medium',
            'status' => 'open',
            'created_by' => $this->systemUser->id,
        ]);
    }

    private function createFileFor(string $module, int $entityId): File
    {
        return File::create([
            'module' => $module,
            'entity_id' => $entityId,
            'original_name' => 'evidence.jpg',
            'stored_name' => fake()->unique()->numerify('########') . '.jpg',
            'path' => "{$module}/{$entityId}/2026/09/" . fake()->unique()->numerify('########') . '.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'uploaded_by' => $this->systemUser->id,
            'is_active' => true,
        ]);
    }
}
