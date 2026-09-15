<?php

namespace Tests\Feature\Files;

use App\Exceptions\FileException;
use App\Models\File;
use App\Models\User;
use App\Services\FileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * delete() used to erase the bytes from disk while only soft-deleting the row, leaving
 * a record that permanently pointed at nothing — a soft delete that could never be
 * undone. The two semantics are now separate and explicit:
 *
 *   delete()  soft, reversible, bytes retained
 *   purge()   permanent, bytes removed, row gone
 */
class FileDeletionSemanticsTest extends TestCase
{
    use RefreshDatabase;

    private FileService $files;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('ssms');

        $this->systemUser = User::factory()->create([
            'created_by' => null, 'updated_by' => null, 'society_id' => null,
        ]);
        $this->actingAs($this->systemUser);

        $this->files = app(FileService::class);
    }

    public function test_delete_is_reversible_and_keeps_the_bytes(): void
    {
        $file = $this->upload();

        $this->files->delete($file->id);

        $this->assertSoftDeleted('files', ['id' => $file->id]);
        Storage::disk('ssms')->assertExists($file->path);

        $this->files->restore($file->id);

        $this->assertDatabaseHas('files', ['id' => $file->id, 'deleted_at' => null]);
        Storage::disk('ssms')->assertExists($file->path);
    }

    public function test_purge_removes_the_row_and_the_bytes(): void
    {
        $file = $this->upload();

        $this->files->purge($file->id);

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
        Storage::disk('ssms')->assertMissing($file->path);
    }

    public function test_purge_works_on_an_already_soft_deleted_file(): void
    {
        $file = $this->upload();
        $this->files->delete($file->id);

        $this->files->purge($file->id);

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
        Storage::disk('ssms')->assertMissing($file->path);
    }

    public function test_restore_refuses_when_the_bytes_are_gone(): void
    {
        $file = $this->upload();
        $this->files->delete($file->id);

        Storage::disk('ssms')->delete($file->path);

        $this->expectException(FileException::class);
        $this->files->restore($file->id);
    }

    private function upload(): File
    {
        return $this->files->upload(
            UploadedFile::fake()->image('evidence.jpg'),
            'complaints',
            1
        );
    }
}
