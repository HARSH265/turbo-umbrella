<?php

namespace Tests\Feature\Files;

use App\Models\Complaint;
use App\Models\File;
use App\Models\Flat;
use App\Models\Society;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FilePolicy decides who may download an attachment, and it had no coverage.
 * Attachments are complaint evidence and personal photos, so cross-tenant leakage
 * here matters more than in most modules.
 */
class FileAccessTest extends TestCase
{
    use RefreshDatabase;

    private Society $society;

    private Society $otherSociety;

    private Flat $flat;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('ssms');

        $this->seed([
            \Database\Seeders\RoleSeeder::class,
            \Database\Seeders\PermissionSeeder::class,
            \Database\Seeders\RolePermissionSeeder::class,
        ]);

        $this->systemUser = User::factory()->create([
            'created_by' => null, 'updated_by' => null, 'society_id' => null,
        ]);
        $this->assignRole($this->systemUser, 'super-admin');

        [$this->society] = $this->createSocietyContext('fil');
        [$this->otherSociety] = $this->createSocietyContext('fol');

        $this->flat = $this->createFlatForSociety($this->society, 'F-101');
    }

    public function test_complaint_owner_can_download_their_own_attachment(): void
    {
        $resident = $this->createUserWithRole('resident', $this->society, $this->flat);
        $file = $this->attachmentFor($this->complaintBy($resident), $resident);

        $this->actingAs($resident)->get(route('files.download', $file))->assertOk();
    }

    public function test_an_unrelated_resident_cannot_download_the_attachment(): void
    {
        $owner = $this->createUserWithRole('resident', $this->society, $this->flat);
        $stranger = $this->createUserWithRole('resident', $this->society);

        $file = $this->attachmentFor($this->complaintBy($owner), $owner);

        $this->actingAs($stranger)->get(route('files.download', $file))->assertForbidden();
    }

    public function test_assigned_staff_can_download_the_attachment(): void
    {
        $owner = $this->createUserWithRole('resident', $this->society, $this->flat);
        $staff = $this->createUserWithRole('staff', $this->society);

        $complaint = $this->complaintBy($owner, ['assigned_to' => $staff->id, 'assigned_at' => now()]);
        $file = $this->attachmentFor($complaint, $owner);

        $this->actingAs($staff)->get(route('files.download', $file))->assertOk();
    }

    public function test_society_admin_from_another_society_cannot_download(): void
    {
        $owner = $this->createUserWithRole('resident', $this->society, $this->flat);
        $foreignAdmin = $this->createUserWithRole('society-admin', $this->otherSociety);

        $file = $this->attachmentFor($this->complaintBy($owner), $owner);

        $this->actingAs($foreignAdmin)->get(route('files.download', $file))->assertForbidden();
    }

    public function test_super_admin_can_download_anything(): void
    {
        $owner = $this->createUserWithRole('resident', $this->society, $this->flat);
        $file = $this->attachmentFor($this->complaintBy($owner), $owner);

        $this->actingAs($this->systemUser)->get(route('files.download', $file))->assertOk();
    }

    public function test_a_stranger_cannot_delete_someone_elses_attachment(): void
    {
        $owner = $this->createUserWithRole('resident', $this->society, $this->flat);
        $stranger = $this->createUserWithRole('resident', $this->society);

        $file = $this->attachmentFor($this->complaintBy($owner), $owner);

        $this->actingAs($stranger)->delete(route('files.destroy', $file))->assertForbidden();
        $this->assertDatabaseHas('files', ['id' => $file->id, 'deleted_at' => null]);
    }

    private function complaintBy(User $user, array $overrides = []): Complaint
    {
        return Complaint::create(array_merge([
            'user_id' => $user->id,
            'flat_id' => $this->flat->id,
            'category' => 'plumbing',
            'subject' => 'Leak ' . fake()->unique()->numerify('####'),
            'description' => 'Water is leaking.',
            'priority' => 'medium',
            'status' => 'open',
            'created_by' => $user->id,
        ], $overrides));
    }

    private function attachmentFor(Complaint $complaint, User $uploader): File
    {
        $this->actingAs($uploader);

        return app(\App\Services\FileService::class)->upload(
            UploadedFile::fake()->image('evidence.jpg'),
            'complaints',
            $complaint->id
        );
    }
}
