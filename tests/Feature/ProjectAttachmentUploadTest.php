<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

        foreach (ProjectAttachment::query()->get() as $attachment) {
            Storage::disk('local')->assertExists($attachment->file_path);
        }
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
            'uploaded_by_user_id' => $user->id,
        ]);
    }
}
