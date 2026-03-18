<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectAttachmentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_can_be_created_with_attachments(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'Attachment Test Customer',
            'active' => true,
        ]);

        $drawingFile = UploadedFile::fake()->create('drawing.pdf', 250, 'application/pdf');
        $notesFile = UploadedFile::fake()->create('notes.txt', 10, 'text/plain');

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'project_number' => 'ATTACH-001',
            'name' => 'Attachment Upload Project',
            'customer_id' => $customer->id,
            'status' => 'active',
            'attachments' => [$drawingFile, $notesFile],
        ]);

        $response->assertRedirect(route('projects.index'));
        $this->assertDatabaseHas('projects', [
            'project_number' => 'ATTACH-001',
            'name' => 'Attachment Upload Project',
            'customer_id' => $customer->id,
        ]);
        $this->assertDatabaseCount('project_attachments', 2);
        $this->assertDatabaseHas('project_attachments', [
            'project_id' => Project::query()->where('project_number', 'ATTACH-001')->value('id'),
            'original_filename' => 'drawing.pdf',
            'document_key' => 'drawing.pdf',
            'version_number' => 1,
            'is_latest' => true,
        ]);

        foreach (ProjectAttachment::query()->get() as $attachment) {
            Storage::disk('local')->assertExists($attachment->file_path);
        }
    }

    public function test_uploading_same_attachment_name_increments_version_number(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'Versioning Customer',
            'active' => true,
        ]);
        $project = Project::create([
            'project_number' => 'VER-001',
            'name' => 'Versioned Project',
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        $firstVersion = UploadedFile::fake()->create('shop-drawings.pdf', 120, 'application/pdf');
        $secondVersion = UploadedFile::fake()->create('shop-drawings.pdf', 130, 'application/pdf');

        $this->actingAs($user)->put(route('projects.update', $project), [
            'project_number' => 'VER-001',
            'name' => 'Versioned Project',
            'customer_id' => $customer->id,
            'status' => 'active',
            'attachments' => [$firstVersion],
        ])->assertRedirect(route('projects.show', $project));

        $this->actingAs($user)->put(route('projects.update', $project), [
            'project_number' => 'VER-001',
            'name' => 'Versioned Project',
            'customer_id' => $customer->id,
            'status' => 'active',
            'attachments' => [$secondVersion],
        ])->assertRedirect(route('projects.show', $project));

        $attachments = ProjectAttachment::query()
            ->where('project_id', $project->id)
            ->where('document_key', 'shop-drawings.pdf')
            ->orderByDesc('version_number')
            ->get();

        $this->assertCount(2, $attachments);
        $this->assertSame(2, $attachments->first()->version_number);
        $this->assertTrue((bool) $attachments->first()->is_latest);
        $this->assertSame(1, $attachments->last()->version_number);
        $this->assertFalse((bool) $attachments->last()->is_latest);

        $this->actingAs($user)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Projects/Show')
                ->where('project.attachments.0.version_number', 2)
                ->where('project.attachments.0.is_latest', true)
                ->where('project.attachments.1.version_number', 1)
                ->where('project.attachments.1.is_latest', false)
            );
    }

    public function test_project_attachment_pdf_can_be_viewed_inline_and_downloaded(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'Viewer Test Customer',
            'active' => true,
        ]);
        $project = Project::create([
            'project_number' => 'VIEW-001',
            'name' => 'Viewer Project',
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        $pdf = UploadedFile::fake()->create('packet.pdf', 200, 'application/pdf');
        $storedPath = $pdf->store("project-attachments/{$project->id}", 'local');

        $attachment = ProjectAttachment::create([
            'project_id' => $project->id,
            'filename' => basename($storedPath),
            'original_filename' => 'packet.pdf',
            'document_key' => 'packet.pdf',
            'version_number' => 1,
            'is_latest' => true,
            'file_path' => $storedPath,
            'file_size' => $pdf->getSize(),
            'mime_type' => 'application/pdf',
            'uploaded_by_user_id' => $user->id,
            'uploaded_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('projects.attachments.view', [$project, $attachment]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('projects.attachments.download', [$project, $attachment]))
            ->assertDownload('packet.pdf');
    }

    public function test_project_attachment_routes_return_not_found_for_wrong_project(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'Route Check Customer',
            'active' => true,
        ]);
        $projectA = Project::create([
            'project_number' => 'ROUTE-001',
            'name' => 'Project A',
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);
        $projectB = Project::create([
            'project_number' => 'ROUTE-002',
            'name' => 'Project B',
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        $pdf = UploadedFile::fake()->create('route.pdf', 50, 'application/pdf');
        $storedPath = $pdf->store("project-attachments/{$projectA->id}", 'local');

        $attachment = ProjectAttachment::create([
            'project_id' => $projectA->id,
            'filename' => basename($storedPath),
            'original_filename' => 'route.pdf',
            'document_key' => 'route.pdf',
            'version_number' => 1,
            'is_latest' => true,
            'file_path' => $storedPath,
            'file_size' => $pdf->getSize(),
            'mime_type' => 'application/pdf',
            'uploaded_by_user_id' => $user->id,
            'uploaded_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('projects.attachments.view', [$projectB, $attachment]))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('projects.attachments.download', [$projectB, $attachment]))
            ->assertNotFound();
    }

    public function test_deleting_latest_attachment_promotes_previous_version(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'Delete Version Customer',
            'active' => true,
        ]);
        $project = Project::create([
            'project_number' => 'DEL-001',
            'name' => 'Delete Version Project',
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        $v1File = UploadedFile::fake()->create('specs.pdf', 100, 'application/pdf');
        $v2File = UploadedFile::fake()->create('specs.pdf', 110, 'application/pdf');

        $v1Path = $v1File->store("project-attachments/{$project->id}", 'local');
        $v2Path = $v2File->store("project-attachments/{$project->id}", 'local');

        $versionOne = ProjectAttachment::create([
            'project_id' => $project->id,
            'filename' => basename($v1Path),
            'original_filename' => 'specs.pdf',
            'document_key' => 'specs.pdf',
            'version_number' => 1,
            'is_latest' => false,
            'file_path' => $v1Path,
            'file_size' => $v1File->getSize(),
            'mime_type' => 'application/pdf',
            'uploaded_by_user_id' => $user->id,
            'uploaded_at' => now()->subMinute(),
        ]);

        $versionTwo = ProjectAttachment::create([
            'project_id' => $project->id,
            'filename' => basename($v2Path),
            'original_filename' => 'specs.pdf',
            'document_key' => 'specs.pdf',
            'version_number' => 2,
            'is_latest' => true,
            'file_path' => $v2Path,
            'file_size' => $v2File->getSize(),
            'mime_type' => 'application/pdf',
            'uploaded_by_user_id' => $user->id,
            'uploaded_at' => now(),
        ]);

        $this->actingAs($user)
            ->delete(route('projects.attachments.destroy', [$project, $versionTwo]))
            ->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseMissing('project_attachments', ['id' => $versionTwo->id]);
        $this->assertDatabaseHas('project_attachments', [
            'id' => $versionOne->id,
            'is_latest' => true,
        ]);

        Storage::disk('local')->assertMissing($v2Path);
        Storage::disk('local')->assertExists($v1Path);
    }

    public function test_project_update_can_add_attachments(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'Update Attachment Customer',
            'active' => true,
        ]);
        $project = Project::create([
            'project_number' => 'UPD-001',
            'name' => 'Project Before Update',
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        $attachment = UploadedFile::fake()->create('update-attachment.pdf', 200, 'application/pdf');

        $response = $this->actingAs($user)->put(route('projects.update', $project), [
            'project_number' => 'UPD-001',
            'name' => 'Project After Update',
            'customer_id' => $customer->id,
            'status' => 'active',
            'attachments' => [$attachment],
        ]);

        $response->assertRedirect(route('projects.show', $project));
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Project After Update',
        ]);
        $this->assertDatabaseHas('project_attachments', [
            'project_id' => $project->id,
            'original_filename' => 'update-attachment.pdf',
            'document_key' => 'update-attachment.pdf',
            'version_number' => 1,
            'is_latest' => true,
            'uploaded_by_user_id' => $user->id,
        ]);
    }
}
