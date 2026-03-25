<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DrawingRequest;
use App\Models\DrawingSubmittal;
use App\Models\FabQueue;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\SubmittalFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FabQueueDocumentViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_fab_queue_show_includes_submittal_and_project_documents(): void
    {
        Storage::fake('local');

        [$user, $fabQueue, $pdfFile, $imageFile, $latestAttachment] = $this->createFabQueueWithDocuments();

        $this->actingAs($user)
            ->get(route('fab-queue.show', $fabQueue))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('FabQueue/Show')
                ->has('submittalFiles', 2)
                ->where('submittalFiles.0.id', $pdfFile->id)
                ->where('submittalFiles.0.filename', 'drawing.pdf')
                ->where('submittalFiles.0.source', 'submittal')
                ->where('submittalFiles.0.view_url', route('submittals.files.view', [$fabQueue->submittal_id, $pdfFile->id]))
                ->where('submittalFiles.0.download_url', route('submittals.files.download', [$fabQueue->submittal_id, $pdfFile->id]))
                ->where('submittalFiles.1.id', $imageFile->id)
                ->where('submittalFiles.1.filename', 'sketch.png')
                ->has('projectFiles', 1)
                ->where('projectFiles.0.id', $latestAttachment->id)
                ->where('projectFiles.0.filename', 'specs-v2.pdf')
                ->where('projectFiles.0.source', 'project')
                ->where('projectFiles.0.view_url', route('projects.attachments.view', [$fabQueue->project_id, $latestAttachment->id]))
                ->where('projectFiles.0.download_url', route('projects.attachments.download', [$fabQueue->project_id, $latestAttachment->id]))
            );
    }

    public function test_fab_queue_show_only_includes_latest_project_attachments(): void
    {
        Storage::fake('local');

        [$user, $fabQueue, , , $latestAttachment, $olderAttachment] = $this->createFabQueueWithDocuments();

        $this->actingAs($user)
            ->get(route('fab-queue.show', $fabQueue))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('FabQueue/Show')
                ->has('projectFiles', 1)
                ->where('projectFiles.0.id', $latestAttachment->id)
                ->where('projectFiles.0.id', fn (int $id) => $id !== $olderAttachment->id)
            );
    }

    /**
     * @return array{0: User, 1: FabQueue, 2: SubmittalFile, 3: SubmittalFile, 4: ProjectAttachment, 5: ProjectAttachment}
     */
    private function createFabQueueWithDocuments(): array
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'name' => 'Fab Viewer Customer',
            'active' => true,
        ]);

        $project = Project::create([
            'project_number' => 'FV-001',
            'name' => 'Fab Viewer Project',
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        $drawingRequest = DrawingRequest::create([
            'request_number' => 'DR-2026-0501',
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'requested_by_user_id' => $user->id,
            'assigned_to_user_id' => $user->id,
            'title' => 'Fab Viewer Request',
            'priority' => 'normal',
            'drawing_type' => 'structural',
            'requested_date' => now()->toDateString(),
            'required_date' => now()->addDays(5)->toDateString(),
            'status' => 'in_progress',
        ]);

        $submittal = DrawingSubmittal::create([
            'submittal_number' => 'SUB-2026-0501',
            'drawing_request_id' => $drawingRequest->id,
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'revision' => 'A',
            'submitted_by_user_id' => $user->id,
            'status' => 'approved',
            'purpose' => 'for_approval',
        ]);

        $pdfUpload = UploadedFile::fake()->create('drawing.pdf', 200, 'application/pdf');
        $pdfPath = $pdfUpload->store("submittal-files/{$submittal->id}", 'local');

        $imageUpload = UploadedFile::fake()->image('sketch.png', 100, 100);
        $imagePath = $imageUpload->store("submittal-files/{$submittal->id}", 'local');

        $pdfFile = SubmittalFile::create([
            'submittal_id' => $submittal->id,
            'file_type' => 'drawing',
            'filename' => basename($pdfPath),
            'original_filename' => 'drawing.pdf',
            'file_path' => $pdfPath,
            'file_size' => $pdfUpload->getSize(),
            'mime_type' => 'application/pdf',
            'version' => 1,
            'is_current' => true,
            'uploaded_by_user_id' => $user->id,
            'uploaded_at' => now(),
        ]);

        $imageFile = SubmittalFile::create([
            'submittal_id' => $submittal->id,
            'file_type' => 'drawing',
            'filename' => basename($imagePath),
            'original_filename' => 'sketch.png',
            'file_path' => $imagePath,
            'file_size' => $imageUpload->getSize(),
            'mime_type' => 'image/png',
            'version' => 1,
            'is_current' => true,
            'uploaded_by_user_id' => $user->id,
            'uploaded_at' => now(),
        ]);

        $oldAttachmentUpload = UploadedFile::fake()->create('specs-v1.pdf', 120, 'application/pdf');
        $oldAttachmentPath = $oldAttachmentUpload->store("project-attachments/{$project->id}", 'local');

        $olderAttachment = ProjectAttachment::create([
            'project_id' => $project->id,
            'filename' => basename($oldAttachmentPath),
            'original_filename' => 'specs-v1.pdf',
            'document_key' => 'specs.pdf',
            'category' => 'specs',
            'version_number' => 1,
            'is_latest' => false,
            'file_path' => $oldAttachmentPath,
            'file_size' => $oldAttachmentUpload->getSize(),
            'mime_type' => 'application/pdf',
            'uploaded_by_user_id' => $user->id,
            'uploaded_at' => now()->subMinute(),
        ]);

        $latestAttachmentUpload = UploadedFile::fake()->create('specs-v2.pdf', 140, 'application/pdf');
        $latestAttachmentPath = $latestAttachmentUpload->store("project-attachments/{$project->id}", 'local');

        $latestAttachment = ProjectAttachment::create([
            'project_id' => $project->id,
            'filename' => basename($latestAttachmentPath),
            'original_filename' => 'specs-v2.pdf',
            'document_key' => 'specs.pdf',
            'category' => 'specs',
            'version_number' => 2,
            'is_latest' => true,
            'file_path' => $latestAttachmentPath,
            'file_size' => $latestAttachmentUpload->getSize(),
            'mime_type' => 'application/pdf',
            'uploaded_by_user_id' => $user->id,
            'uploaded_at' => now(),
        ]);

        $fabQueue = FabQueue::create([
            'submittal_id' => $submittal->id,
            'project_id' => $project->id,
            'queue_number' => 'FAB-2026-0501',
            'priority' => 2,
            'assigned_to_user_id' => $user->id,
            'assigned_at' => now(),
            'status' => 'queued',
            'cnc_files_attached' => true,
        ]);

        return [$user, $fabQueue, $pdfFile, $imageFile, $latestAttachment, $olderAttachment];
    }
}
