<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DrawingRequest;
use App\Models\DrawingSubmittal;
use App\Models\PdfMarkup;
use App\Models\Project;
use App\Models\SubmittalFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmittalPdfMarkupTest extends TestCase
{
    use RefreshDatabase;

    public function test_submittal_pdf_can_be_viewed_inline_and_downloaded(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $this->actingAs($user)
            ->get(route('submittals.files.view', [$submittal, $submittalFile]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('submittals.files.download', [$submittal, $submittalFile]))
            ->assertDownload('shop-drawing.pdf');
    }

    public function test_markups_can_be_listed_for_submittal_pdf_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        PdfMarkup::create([
            'submittal_file_id' => $submittalFile->id,
            'user_id' => $user->id,
            'page_number' => 1,
            'markup_type' => 'circle',
            'markup_data' => [
                'x' => 10,
                'y' => 20,
                'width' => 15,
                'height' => 10,
                'color' => '#ef4444',
            ],
        ]);

        $this->actingAs($user)
            ->getJson(route('submittals.files.markups.index', [$submittal, $submittalFile]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.markup_type', 'circle')
            ->assertJsonPath('data.0.user.id', $user->id);
    }

    public function test_markup_can_be_saved_for_submittal_pdf_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $this->actingAs($user)
            ->postJson(route('submittals.files.markups.store', [$submittal, $submittalFile]), [
                'page_number' => 2,
                'markup_type' => 'highlight',
                'markup_data' => [
                    'x' => 20,
                    'y' => 10,
                    'width' => 30,
                    'height' => 15,
                    'color' => '#fde047',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.markup_type', 'highlight')
            ->assertJsonPath('data.page_number', 2)
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertDatabaseHas('pdf_markups', [
            'submittal_file_id' => $submittalFile->id,
            'user_id' => $user->id,
            'page_number' => 2,
            'markup_type' => 'highlight',
        ]);
    }

    public function test_markup_export_returns_json_download(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        PdfMarkup::create([
            'submittal_file_id' => $submittalFile->id,
            'user_id' => $user->id,
            'page_number' => 1,
            'markup_type' => 'arrow',
            'markup_data' => [
                'x' => 10,
                'y' => 10,
                'x2' => 35,
                'y2' => 30,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('submittals.files.markups.export', [$submittal, $submittalFile]))
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertHeader('content-disposition', 'attachment; filename=markups-'.$submittalFile->id.'.json');
    }

    public function test_stamp_markup_can_be_saved_for_submittal_pdf_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $this->actingAs($user)
            ->postJson(route('submittals.files.markups.store', [$submittal, $submittalFile]), [
                'page_number' => 1,
                'markup_type' => 'stamp',
                'markup_data' => [
                    'x' => 55,
                    'y' => 25,
                    'label' => 'APPROVED',
                    'color' => '#b91c1c',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.markup_type', 'stamp');

        $this->assertDatabaseHas('pdf_markups', [
            'submittal_file_id' => $submittalFile->id,
            'user_id' => $user->id,
            'markup_type' => 'stamp',
        ]);
    }

    public function test_submittal_file_routes_require_file_to_belong_to_submittal(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittalA, $submittalFileA] = $this->createSubmittalWithFile($user);
        [$submittalB] = $this->createSubmittalWithFile($user, 'DRAW-B');

        $this->actingAs($user)
            ->get(route('submittals.files.view', [$submittalB, $submittalFileA]))
            ->assertNotFound();

        $this->actingAs($user)
            ->postJson(route('submittals.files.markups.store', [$submittalB, $submittalFileA]), [
                'page_number' => 1,
                'markup_type' => 'circle',
                'markup_data' => [
                    'x' => 10,
                    'y' => 10,
                    'width' => 10,
                    'height' => 10,
                ],
            ])
            ->assertNotFound();
    }

    public function test_markup_validation_rejects_unknown_markup_type(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $this->actingAs($user)
            ->postJson(route('submittals.files.markups.store', [$submittal, $submittalFile]), [
                'page_number' => 1,
                'markup_type' => 'polygon',
                'markup_data' => [
                    'x' => 10,
                    'y' => 20,
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['markup_type']);
    }

    private function createSubmittalWithFile(User $user, string $suffix = 'DRAW'): array
    {
        $customer = Customer::create([
            'name' => "Customer {$suffix}",
            'code' => "CUST-{$suffix}",
            'active' => true,
        ]);

        $project = Project::create([
            'project_number' => "PRJ-{$suffix}",
            'name' => "Project {$suffix}",
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        $drawingRequest = DrawingRequest::create([
            'request_number' => "DR-{$suffix}-0001",
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'requested_by_user_id' => $user->id,
            'assigned_to_user_id' => $user->id,
            'title' => 'Shop Drawing Package',
            'priority' => 'normal',
            'drawing_type' => 'structural',
            'requested_date' => now()->toDateString(),
            'status' => 'in_progress',
        ]);

        $submittal = DrawingSubmittal::create([
            'submittal_number' => "SUB-{$suffix}-0001",
            'drawing_request_id' => $drawingRequest->id,
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'revision' => 'A',
            'submitted_by_user_id' => $user->id,
            'purpose' => 'for_approval',
            'status' => 'draft',
        ]);

        $pdf = UploadedFile::fake()->create('shop-drawing.pdf', 250, 'application/pdf');
        $storedPath = $pdf->store("submittal-files/{$submittal->id}", 'local');

        $submittalFile = SubmittalFile::create([
            'submittal_id' => $submittal->id,
            'file_type' => 'drawing',
            'filename' => basename($storedPath),
            'original_filename' => 'shop-drawing.pdf',
            'file_path' => $storedPath,
            'file_size' => $pdf->getSize(),
            'mime_type' => 'application/pdf',
            'version' => 1,
            'is_current' => true,
            'uploaded_by_user_id' => $user->id,
            'uploaded_at' => now(),
        ]);

        return [$submittal, $submittalFile];
    }
}
